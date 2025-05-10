<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin-job.php');
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$admin_id = 1;

switch ($action) {
    case 'add':
        if (empty($_POST['company']) || empty($_POST['job_title']) || empty($_POST['location']) || empty($_POST['description'])) {
            $_SESSION['error'] = "All fields are required";
            header('Location: ../admin-job.php');
            exit();
        }

        $company = $conn->real_escape_string($_POST['company']);
        $job_title = $conn->real_escape_string($_POST['job_title']);
        $location = $conn->real_escape_string($_POST['location']);
        $description = $conn->real_escape_string($_POST['description']);
        
        $stmt = $conn->prepare("INSERT INTO careers (company, location, job_title, description, user_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $company, $location, $job_title, $description, $admin_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Job opportunity added successfully!";
        } else {
            $_SESSION['error'] = "Failed to add job: " . $conn->error;
        }
        
        $stmt->close();
        break;

    case 'edit':
        if (empty($_POST['job_id']) || empty($_POST['company']) || empty($_POST['job_title']) || empty($_POST['location']) || empty($_POST['description'])) {
            $_SESSION['error'] = "All fields are required";
            header('Location: ../admin-job.php');
            exit();
        }
        $job_id = intval($_POST['job_id']);
        $company = $conn->real_escape_string($_POST['company']);
        $job_title = $conn->real_escape_string($_POST['job_title']);
        $location = $conn->real_escape_string($_POST['location']);
        $description = $conn->real_escape_string($_POST['description']);
        
        $stmt = $conn->prepare("UPDATE careers SET company = ?, location = ?, job_title = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $company, $location, $job_title, $description, $job_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Job opportunity updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update job: " . $conn->error;
        }
        
        $stmt->close();
        break;

    default:
        $_SESSION['error'] = "Invalid action";
        break;
}

header('Location: ../admin-job.php');
exit();
?>
