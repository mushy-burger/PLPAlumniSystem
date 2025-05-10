<?php
include 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'Invalid topic ID']);
    exit;
}

$topic_id = intval($_GET['id']);

$topic_query = "SELECT ft.*, u.name as posted_by 
                FROM forum_topics ft 
                LEFT JOIN users u ON ft.user_id = u.alumni_id 
                WHERE ft.id = ?";
                
$stmt = $conn->prepare($topic_query);
$stmt->bind_param('i', $topic_id);
$stmt->execute();
$topic_result = $stmt->get_result();

if ($topic_result->num_rows === 0) {
    echo json_encode(['error' => 'Topic not found']);
    exit;
}

$topic = $topic_result->fetch_assoc();
$topic['formatted_date'] = date('F j, Y \a\t g:i a', strtotime($topic['date_created']));

$comments_query = "SELECT fc.*, u.name 
                  FROM forum_comments fc 
                  LEFT JOIN users u ON fc.user_id = u.alumni_id 
                  WHERE fc.topic_id = ? 
                  ORDER BY fc.date_created ASC";
                  
$stmt = $conn->prepare($comments_query);
$stmt->bind_param('i', $topic_id);
$stmt->execute();
$comments_result = $stmt->get_result();

$comments = [];
while ($comment = $comments_result->fetch_assoc()) {
    $comment['formatted_date'] = date('F j, Y \a\t g:i a', strtotime($comment['date_created']));
    $comments[] = $comment;
}

echo json_encode([
    'topic' => $topic,
    'comments' => $comments
]);
?>
