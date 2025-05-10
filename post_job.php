<?php
session_start();
include 'admin/db_connect.php';

if (!isset($_SESSION['login_id'])) {
    $_SESSION['error'] = "Please login first to post a job";
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: alumni-job.php");
    exit;
}

$job_title = trim($_POST['job_title']);
$company = trim($_POST['company']);
$location = trim($_POST['location']);
$description = trim($_POST['description']);
$user_id = $_SESSION['login_id'];

$errors = [];
if (empty($job_title)) $errors[] = "Job title is required";
if (empty($company)) $errors[] = "Company name is required";
if (empty($location)) $errors[] = "Job location is required";
if (empty($description)) $errors[] = "Job description is required";

if (!empty($errors)) {
    $_SESSION['error'] = "Please fix the following errors: " . implode(", ", $errors);
    header("Location: alumni-job.php");
    exit;
}

$query = "INSERT INTO careers (company, location, job_title, description, user_id) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("sssss", $company, $location, $job_title, $description, $user_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Job opportunity posted successfully!";
} else {
    $_SESSION['error'] = "Error posting job: " . $conn->error;
}

header("Location: alumni-job.php");
exit;
?>
