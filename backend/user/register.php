<?php
require __DIR__ . '/../connection/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);

    // Prepare the mysqli statement
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $password);
    
    // Execute and redirect
    if ($stmt->execute()) {
        header("Location: ../../frontend/login.html?registered=true");
        exit;
    } else {
        echo "Error during registration: " . $stmt->error;
    }
}
?>