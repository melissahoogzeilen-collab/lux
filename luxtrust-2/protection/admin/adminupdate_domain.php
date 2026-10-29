<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_domain'])) {
    $domain = trim($_POST['new_domain']);
    $safe_domain = filter_var($domain, FILTER_SANITIZE_URL);
    file_put_contents(__DIR__ . '/current_domain.txt', $safe_domain);
    echo json_encode(['status' => 'ok']);
} else {
    echo json_encode(['status' => 'error']);
}
