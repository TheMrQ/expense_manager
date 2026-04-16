<?php
require __DIR__ . '/../connection/config.php';

// This endpoint accepts FormData, not raw JSON.
$user_id = $_POST['user_id'] ?? null;
$file = $_FILES['profile_picture'] ?? null;

if (!$user_id || !$file) {
    echo json_encode(['status' => 'error', 'message' => 'Missing user ID or file.']); exit;
}

try {
    // 1. Validate the file is an image
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        echo json_encode(['status' => 'error', 'message' => 'File is not a valid image.']); exit;
    }

    // 2. Setup the directory (relative to this PHP file)
    $target_dir = "../uploads/";
    // Make the folder if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // 3. Create a unique filename (e.g., 'avatar_1.jpg') to avoid conflicts
    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = "avatar_" . $user_id . "_" . time() . "." . $imageFileType;
    $target_file = $target_dir . $new_filename;

    // 4. Validate file size (e.g., max 5MB) and type
    if ($file["size"] > 5000000) { // 5MB limit
        echo json_encode(['status' => 'error', 'message' => 'Image is too large (max 5MB).']); exit;
    }
    if(!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
        echo json_encode(['status' => 'error', 'message' => 'Only JPG, JPEG, PNG, & GIF files are allowed.']); exit;
    }

    // 5. Everything is valid. Try to move the file to the destination.
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        // 6. Update the database with the new filename
        $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
        if ($stmt->execute([$new_filename, $user_id])) {
            // 7. Success! Return the filename so the frontend can update.
            echo json_encode(['status' => 'success', 'filename' => $new_filename]);
        } else {
            // Database save failed, cleanup the file we just uploaded
            unlink($target_file);
            echo json_encode(['status' => 'error', 'message' => 'Failed to update database profile link.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error moving the uploaded file. Check folder permissions.']);
    }

} catch (PDOException $e) { 
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); 
}
?>