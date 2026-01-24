<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

$servername = "localhost";
$port = "3306";
$username = "root";
$password = "1234";
$dbname = "expense_manager";

// Kết nối MySQL
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
