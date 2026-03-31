<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Local XAMPP Settings
$host = 'localhost';
$user = 'root';
$pass = ''; // MUST BE EMPTY FOR DEFAULT XAMPP
$dbname = 'expense_manager';

// Create connection using mysqli
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Connection failed: ' . $conn->connect_error]));
}
?>