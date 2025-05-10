<?php
session_start();
include 'admin/db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'Invalid job ID']);
    exit;
}

$job_id = intval($_GET['id']);

$query = "SELECT c.*, u.name as posted_by, DATE_FORMAT(c.date_created, '%M %d, %Y') as date_posted
          FROM careers c 
          LEFT JOIN users u ON c.user_id = u.alumni_id
          WHERE c.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Job not found']);
    exit;
}

$job = $result->fetch_assoc();
$job['posted_by'] = $job['posted_by'] ?? 'Admin'; 

echo json_encode($job);
?>
