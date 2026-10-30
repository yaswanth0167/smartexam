<?php

// Database Configuration - Auto-detect Local XAMPP vs Live Server (InfinityFree)
$http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$is_localhost = in_array($http_host, ['localhost', '127.0.0.1', '::1']) 
    || str_contains($http_host, 'localhost') 
    || str_contains($http_host, '127.0.0.1')
    || (php_sapi_name() === 'cli' && empty(getenv('DB_HOST')));

if ($is_localhost) {
    // Local XAMPP Settings
    $host     = "127.0.0.1";
    $username = "root";
    $password = "";
    $database = "smartexam_db";
} else {
    // Live Server (InfinityFree / smartexam.infinityfree.me)
    $host     = getenv('DB_HOST') ?: "sql304.infinityfree.me";
    $username = getenv('DB_USER') ?: "if0_42445904";
    $password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : "n61ktjNhqvK";
    $database = getenv('DB_NAME') ?: "if0_42445904_smartexam";
}

mysqli_report(MYSQLI_REPORT_OFF);

$conn = @new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("<div style='font-family:sans-serif;padding:30px;background:#030508;color:#fff;border-radius:12px;margin:40px auto;max-width:600px;border:1px solid #ff3366;'>
        <h2 style='color:#ff3366;margin-top:0;'>Database Connection Failed</h2>
        <p><strong>Error:</strong> " . htmlspecialchars($conn->connect_error) . "</p>
        <p><strong>Connected to Host:</strong> <code>" . htmlspecialchars($host) . "</code></p>
        <p><strong>Database User:</strong> <code>" . htmlspecialchars($username) . "</code></p>
        <p><strong>Database Name:</strong> <code>" . htmlspecialchars($database) . "</code></p>
    </div>");
}

$conn->set_charset("utf8mb4");
date_default_timezone_set("Asia/Kolkata");
@$conn->query("SET time_zone = '+05:30'");

?>