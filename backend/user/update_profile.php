<?php
require __DIR__ . '/../connection/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
header('Content-Type: application/json');

try {
    if ($action === 'remove_avatar') {
        $stmt = $conn->prepare("UPDATE users SET avatar_url = NULL WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Avatar removed.']);
    } else { // Default action is to update profile
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $avatar_path = null;

        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../public/assets/avatars/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_ext;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir . $new_filename)) {
                $avatar_path = 'assets/avatars/' . $new_filename;
            }
        }

        if ($avatar_path) {
            $stmt = $conn->prepare("UPDATE users SET username = ?, avatar_url = ? WHERE id = ?");
            $stmt->bind_param("ssi", $username, $avatar_path, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->bind_param("si", $username, $user_id);
        }
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An error occurred.']);
}
