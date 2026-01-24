<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

// These values will be pulled from Vercel's Environment Variables
$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME') ?: 'postgres';
$username = getenv('DB_USER') ?: 'postgres';
$password = getenv('DB_PASSWORD');

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
    $conn = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    // On Vercel, error_log is better than die() for debugging
    error_log("Connection failed: " . $e->getMessage());
    exit(json_encode(['success' => false, 'error' => 'Database connection error.']));
}
