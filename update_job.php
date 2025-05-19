<?php
session_start();
include 'admin/db_connect.php';

if (!isset($_SESSION['login_id'])) {
    $_SESSION['error'] = "Please login first to edit a job";
    header("Location: login.php");
    exit;
}

if (!isset($_POST['job_id']) || !is_numeric($_POST['job_id'])) {
    $_SESSION['error'] = "Invalid job ID";
    header("Location: alumni-job.php");
    exit;
}

$job_id = intval($_POST['job_id']);
$user_id = $_SESSION['login_id'];
$job_title = trim($_POST['job_title']);
$company = trim($_POST['company']);
$location = trim($_POST['location']);
$modality = trim($_POST['modality'] ?? 'WFH');
$description = trim($_POST['description']);

$errors = [];
if (empty($job_title)) $errors[] = "Job title is required";
if (empty($company)) $errors[] = "Company name is required";
if (empty($location)) $errors[] = "Job location is required";
if (empty($modality)) $errors[] = "Work setup is required";
if (empty($description)) $errors[] = "Job description is required";

if (!empty($errors)) {
    $_SESSION['error'] = "Please fix the following errors: " . implode(", ", $errors);
    header("Location: alumni-job.php");
    exit;
}

$combined_location = $location . ' (' . $modality . ')';

$check_query = "SELECT * FROM careers WHERE id = ? AND user_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("is", $job_id, $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "You don't have permission to edit this job listing";
    header("Location: alumni-job.php");
    exit;
}

$update_query = "UPDATE careers SET job_title = ?, company = ?, location = ?, description = ? WHERE id = ? AND user_id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("ssssis", $job_title, $company, $combined_location, $description, $job_id, $user_id);

if ($update_stmt->execute()) {
    $_SESSION['success'] = "Job listing updated successfully";
} else {
    $_SESSION['error'] = "Failed to update job listing: " . $conn->error;
}

header("Location: alumni-job.php");
exit;
?>
