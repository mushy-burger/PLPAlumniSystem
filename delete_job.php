<?php
session_start();
include 'admin/db_connect.php';

if (!isset($_SESSION['login_id'])) {
    $_SESSION['error'] = "Please login first to delete a job";
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid job ID";
    header("Location: alumni-job.php");
    exit;
}

$job_id = intval($_GET['id']);
$user_id = $_SESSION['login_id'];

$check_query = "SELECT * FROM careers WHERE id = ? AND user_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("is", $job_id, $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "You don't have permission to delete this job listing";
    header("Location: alumni-job.php");
    exit;
}

$delete_query = "DELETE FROM careers WHERE id = ? AND user_id = ?";
$delete_stmt = $conn->prepare($delete_query);
$delete_stmt->bind_param("is", $job_id, $user_id);

if ($delete_stmt->execute()) {
    $_SESSION['success'] = "Job listing deleted successfully";
} else {
    $_SESSION['error'] = "Failed to delete job listing: " . $conn->error;
}

header("Location: alumni-job.php");
exit;
?>
