<?php
session_start();
include 'admin/db_connect.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid alumni ID.";
    header("Location: admin-alumni-list.php");
    exit;
}

$alumni_id = $_GET['id'];

$query = "SELECT avatar FROM alumnus_bio WHERE alumni_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $alumni_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Alumni record not found.";
    header("Location: admin-alumni-list.php");
    exit;
}

$alumni = $result->fetch_assoc();

$delete_query = "DELETE FROM alumnus_bio WHERE alumni_id = ?";
$stmt_delete = $conn->prepare($delete_query);
$stmt_delete->bind_param("s", $alumni_id);

if ($stmt_delete->execute()) {
    $delete_user = "DELETE FROM users WHERE alumni_id = ?";
    $stmt_user = $conn->prepare($delete_user);
    $stmt_user->bind_param("s", $alumni_id);
    $stmt_user->execute();
    
    if (!empty($alumni['avatar']) && file_exists($alumni['avatar'])) {
        unlink($alumni['avatar']);
    }
    
    $_SESSION['success'] = "Alumni record deleted successfully.";
} else {
    $_SESSION['error'] = "Failed to delete alumni record: " . $conn->error;
}

header("Location: admin-alumni-list.php");
exit;
