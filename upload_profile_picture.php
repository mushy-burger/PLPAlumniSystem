<?php
session_start();
include 'admin/db_connect.php';

if (!isset($_SESSION['login_id'])) {
    $_SESSION['error'] = "You must be logged in to update your profile picture.";
    header("Location: alumni-home.php");
    exit;
}

$alumni_id = $_SESSION['login_id'];

if (!isset($_FILES['profileImage']) || $_FILES['profileImage']['error'] === UPLOAD_ERR_NO_FILE) {
    $_SESSION['error'] = "No file was uploaded.";
    header("Location: alumni-manage-profile.php");
    exit;
}

$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$max_size = 5 * 1024 * 1024; 

$file = $_FILES['profileImage'];

if (!in_array($file['type'], $allowed_types)) {
    $_SESSION['error'] = "Only JPG, PNG, and GIF files are allowed.";
    header("Location: alumni-manage-profile.php");
    exit;
}

if ($file['size'] > $max_size) {
    $_SESSION['error'] = "File is too large. Maximum size is 5MB.";
    header("Location: alumni-manage-profile.php");
    exit;
}

$target_dir = "uploads/alumni/";
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$new_filename = $alumni_id . '_' . time() . '.' . $file_extension;
$target_path = $target_dir . $new_filename;

if (move_uploaded_file($file['tmp_name'], $target_path)) {
    $query = "UPDATE alumnus_bio SET avatar = ? WHERE alumni_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $target_path, $alumni_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Profile picture updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update database record: " . $conn->error;
    }
} else {
    $_SESSION['error'] = "Failed to upload profile picture.";
}

header("Location: alumni-manage-profile.php");
exit;
?>
