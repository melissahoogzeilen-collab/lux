<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
  Local CAPTCHA, no API/key.
  Keep this file beside your site files, outside /protection/:
  /site-x/math_captcha.php
  It keeps the same final session flag used by your existing flow:
  $_SESSION['captcha_passed'] = true;
*/

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

function lc_random_int($min, $max) {
    try {
        return random_int($min, $max);
    } catch (Throwable $e) {
        return mt_rand($min, $max);
    }
}

function lc_token() {
    try {
        return bin2hex(random_bytes(16));
    } catch (Throwable $e) {
        return sha1(uniqid('', true) . mt_rand());
    }
}

function lc_safe_return_url($return) {
    $return = (string)$return;

    if ($return === '' || strpos($return, "\0") !== false) {
        return 'index.php';
    }

    if (strpos($return, 'http://') === 0 || strpos($return, 'https://') === 0 || strpos($return, '//') === 0) {
        return 'index.php';
    }

    if (preg_match('/[\r\n]/', $return)) {
        return 'index.php';
    }

    return $return;
}

function lc_new_challenge() {
    $mode = lc_random_int(1, 2) === 1 ? 'math' : 'word';
    $nonce = lc_token();

    if ($mode === 'math') {
        $ops = ['+', '-'];
        $op = $ops[lc_random_int(0, count($ops) - 1)];

        if ($op === '+') {
            $a = lc_random_int(8, 24);
            $b = lc_random_int(3, 16);
            $answer = (string)($a + $b);
        } else {
            $a = lc_random_int(16, 35);
            $b = lc_random_int(3, 15);
            if ($b > $a) {
                $tmp = $a;
                $a = $b;
                $b = $tmp;
            }
            $answer = (string)($a - $b);
        }

        $_SESSION['local_captcha'] = [
            'mode' => 'math',
            'question' => $a . ' ' . $op . ' ' . $b,
            'answer' => $answer,
            'nonce' => $nonce,
            'created' => time(),
            'attempts' => 0
        ];
    } else {
        $words = ['blue', 'green', 'cloud', 'river', 'apple', 'stone', 'light', 'table', 'water', 'secure'];
        $answer = $words[lc_random_int(0, count($words) - 1)];
        $choices = [$answer];

        while (count($choices) < 4) {
            $candidate = $words[lc_random_int(0, count($words) - 1)];
            if (!in_array($candidate, $choices, true)) {
                $choices[] = $candidate;
            }
        }

        shuffle($choices);

        $_SESSION['local_captcha'] = [
            'mode' => 'word',
            'question' => $answer,
            'choices' => $choices,
            'answer' => $answer,
            'nonce' => $nonce,
            'created' => time(),
            'attempts' => 0
        ];
    }
}

function lc_current_challenge() {
    if (empty($_SESSION['local_captcha']) || !is_array($_SESSION['local_captcha'])) {
        lc_new_challenge();
    }

    $challenge = $_SESSION['local_captcha'];
    $created = isset($challenge['created']) ? (int)$challenge['created'] : 0;

    if ($created <= 0 || (time() - $created) > 600) {
        lc_new_challenge();
        $challenge = $_SESSION['local_captcha'];
    }

    return $challenge;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $challenge = lc_current_challenge();
    $nonce = (string)($_POST['captcha_nonce'] ?? '');
    $trap = trim((string)($_POST['company'] ?? ''));
    $expected = (string)($challenge['answer'] ?? '');
    $created = (int)($challenge['created'] ?? 0);
    $attempts = (int)($challenge['attempts'] ?? 0);
    $mode = (string)($challenge['mode'] ?? 'math');

    $given = $mode === 'word'
        ? trim((string)($_POST['choice'] ?? ''))
        : trim((string)($_POST['answer'] ?? ''));

    $valid = $trap === ''
        && $nonce !== ''
        && hash_equals((string)($challenge['nonce'] ?? ''), $nonce)
        && $expected !== ''
        && $given !== ''
        && hash_equals(strtolower($expected), strtolower($given))
        && $created > 0
        && (time() - $created) <= 600
        && $attempts < 5;

    if ($valid) {
        $_SESSION['captcha_passed'] = true;
        $_SESSION['captcha_passed_at'] = time();
        unset($_SESSION['local_captcha'], $_SESSION['math_captcha_answer'], $_SESSION['math_captcha_time']);

        $return = lc_safe_return_url($_SESSION['return_after_captcha'] ?? 'index.php');
        header('Location: ' . $return);
        exit;
    }

    $attempts++;
    if (isset($_SESSION['local_captcha'])) {
        $_SESSION['local_captcha']['attempts'] = $attempts;
    }

    if ($attempts >= 5) {
        lc_new_challenge();
       $error = 'Please try again.';
    } else {
        lc_new_challenge();
        $error = 'Please try again.';
    }
}

$challenge = lc_current_challenge();
$remaining = max(0, 5 - (int)($challenge['attempts'] ?? 0));
$mode = (string)($challenge['mode'] ?? 'math');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Verification</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --bg: #f6f8fb;
            --card: #ffffff;
            --text: #111827;
            --muted: #667085;
            --line: #e5e7eb;
            --blue: #2563eb;
            --blue-dark: #1d4ed8;
            --danger: #b42318;
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            min-height: 100%;
            font-family: Arial, Helvetica, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, .08), transparent 36%),
                var(--bg);
            color: var(--text);
        }
        .page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px;
        }
        .card {
            width: 100%;
            max-width: 390px;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 18px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .10);
            overflow: hidden;
        }
        .top {
            padding: 26px 26px 18px;
            text-align: center;
            border-bottom: 1px solid var(--line);
        }

        h1 {
            margin: 0;
            font-size: 22px;
            line-height: 1.2;
            letter-spacing: -.02em;
        }
        .subtitle {
            margin: 9px 0 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.45;
        }
        .body { padding: 22px 26px 26px; }
        .error {
            margin: 0 0 14px;
            padding: 11px 12px;
            border-radius: 12px;
            background: #fef3f2;
            color: var(--danger);
            font-size: 14px;
            border: 1px solid #fee4e2;
        }
        .challenge {
            border: 1px solid var(--line);
            border-radius: 15px;
            background: #fafafa;
            padding: 20px 16px;
            text-align: center;
            margin-bottom: 16px;
        }
        .label {
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .12em;
            margin-bottom: 10px;
        }
        .question {
            font-size: 34px;
            line-height: 1;
            font-weight: 800;
            letter-spacing: .04em;
        }
        .word-target {
            font-size: 28px;
            line-height: 1;
            font-weight: 800;
            letter-spacing: .02em;
            text-transform: capitalize;
        }
        .field-label {
            display: block;
            font-size: 14px;
            font-weight: 700;
            margin: 0 0 8px;
        }
        .answer {
            width: 100%;
            height: 52px;
            border: 1px solid #d0d5dd;
            border-radius: 13px;
            padding: 0 14px;
            font-size: 22px;
            font-weight: 700;
            text-align: center;
            outline: none;
            background: #fff;
            color: var(--text);
        }
        .answer:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, .12);
        }
        .choices {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 2px;
        }
        .choice {
            height: 48px;
            border: 1px solid #d0d5dd;
            border-radius: 13px;
            background: #fff;
            color: var(--text);
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            text-transform: capitalize;
        }
        .choice:hover { border-color: var(--blue); }
        .submit {
            width: 100%;
            height: 52px;
            margin-top: 14px;
            border: 0;
            border-radius: 13px;
            background: var(--blue);
            color: #fff;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
        }
        .submit:hover { background: var(--blue-dark); }
        .meta {
            margin-top: 13px;
            text-align: center;
            color: var(--muted);
            font-size: 12px;
        }
        .hp {
            position: absolute;
            left: -9999px;
            width: 1px;
            height: 1px;
            overflow: hidden;
        }
        @media (max-width: 420px) {
            .card { border-radius: 16px; }
            .top { padding: 22px 20px 16px; }
            .body { padding: 20px; }
            .question { font-size: 31px; }
        }
		
		
		.brand {
    width: 56px;
    height: 56px;
    margin: 0 auto 16px;
    border-radius: 16px;
    background: #eff6ff;
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
}
    </style>
</head>
<body>
<div class="page">
    <form class="card" method="post" autocomplete="off" novalidate>
        <div class="top">
            <div class="brand" aria-hidden="true">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 3L19 6V11.5C19 16.2 16.1 20.1 12 21C7.9 20.1 5 16.2 5 11.5V6L12 3Z"
              stroke="currentColor"
              stroke-width="1.8"
              stroke-linejoin="round"/>
        <path d="M8.8 12.1L10.9 14.2L15.4 9.7"
              stroke="currentColor"
              stroke-width="1.9"
              stroke-linecap="round"
              stroke-linejoin="round"/>
    </svg>
</div>
<h1>Verify you're human</h1>
<p class="subtitle">Complete the verification to continue.</p>
        </div>

        <div class="body">
            <?php if ($error !== ''): ?>
                <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <input type="hidden" name="captcha_nonce" value="<?php echo htmlspecialchars((string)$challenge['nonce'], ENT_QUOTES, 'UTF-8'); ?>">
            <div class="hp" aria-hidden="true">
                <label>Company <input type="text" name="company" tabindex="-1" autocomplete="off"></label>
            </div>

            <?php if ($mode === 'word'): ?>
                <div class="challenge">
                    <div class="label">Select the word</div>
                    <div class="word-target"><?php echo htmlspecialchars((string)$challenge['question'], ENT_QUOTES, 'UTF-8'); ?></div>
                </div>

                <div class="choices">
                    <?php foreach ((array)$challenge['choices'] as $choice): ?>
                        <button class="choice" type="submit" name="choice" value="<?php echo htmlspecialchars((string)$choice, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars((string)$choice, ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="challenge">
                    <div class="label">Answer</div>
                    <div class="question"><?php echo htmlspecialchars((string)$challenge['question'], ENT_QUOTES, 'UTF-8'); ?> = ?</div>
                </div>

                <label class="field-label" for="answer">Your answer</label>
                <input class="answer" id="answer" name="answer" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="3" autofocus required>
                <button class="submit" type="submit">Continue</button>
            <?php endif; ?>

        </div>
    </form>
</div>
<script>
(function () {
    var input = document.getElementById('answer');
    if (!input) return;

    input.addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 3);
    });
})();
</script>
</body>
</html>
