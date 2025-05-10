<?php
session_start();
include 'admin/db_connect.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $event_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $event = $result->fetch_assoc();
        
        $formatted_schedule = date('F d, Y h:i A', strtotime($event['schedule']));
        
        $banner_path = $event['banner'];
        if(empty($banner_path) || !file_exists($banner_path)) {
            $banner_path = "images/no-image.jpg";
        }
        
        $response = array(
            'id' => $event['id'],
            'title' => $event['title'],
            'content' => $event['content'],
            'schedule' => $formatted_schedule,
            'banner' => $banner_path,
            'gform_link' => $event['gform_link'] ?? ''
        );
        
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'Event not found']);
    }
    
    $stmt->close();
} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid event ID']);
}

$conn->close();
?>
