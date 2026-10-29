<?php
require 'db.php'; // adjust this path if needed

$faker = new class {
    function ip() {
        return rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 255);
    }
    function country() {
        $list = ['US', 'UK', 'FR', 'DE', 'IN', 'BR', 'TN', 'CA', 'AU', 'Unknown'];
        return $list[array_rand($list)];
    }
    function userAgent() {
        $agents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
            'Mozilla/5.0 (Linux; Android 10)',
            'Googlebot/2.1 (+http://www.google.com/bot.html)',
            'curl/7.68.0',
        ];
        return $agents[array_rand($agents)];
    }
};

$pdo->beginTransaction();

for ($i = 0; $i < 200; $i++) {
    $stmt = $pdo->prepare("INSERT INTO visitors (ip_address, country, user_agent, is_bot, blocked, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())");

    $stmt->execute([
        $faker->ip(),
        $faker->country(),
        $faker->userAgent(),
        rand(0, 1), // is_bot
        rand(0, 1), // blocked
    ]);
}

$pdo->commit();

echo "✅ Seeded 200 fake visitors!";
