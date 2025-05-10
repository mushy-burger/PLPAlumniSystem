<?php
include 'admin/db_connect.php';

$structure_query = "DESCRIBE events";
$structure_result = $conn->query($structure_query);

echo "<h2>Events Table Structure:</h2>";
echo "<pre>";
while ($row = $structure_result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

$count_query = "SELECT COUNT(*) as total FROM events";
$count_result = $conn->query($count_query);
$count_data = $count_result->fetch_assoc();
echo "<h2>Total Events: " . $count_data['total'] . "</h2>";

$upcoming_query = "SELECT COUNT(*) as upcoming FROM events WHERE schedule >= CURDATE()";
$upcoming_result = $conn->query($upcoming_query);
$upcoming_data = $upcoming_result->fetch_assoc();
echo "<h2>Upcoming Events: " . $upcoming_data['upcoming'] . "</h2>";

$events_query = "SELECT id, title, schedule, banner FROM events ORDER BY schedule DESC";
$events_result = $conn->query($events_query);

echo "<h2>All Events:</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Title</th><th>Schedule</th><th>Banner Path</th></tr>";
while ($event = $events_result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $event['id'] . "</td>";
    echo "<td>" . htmlspecialchars($event['title']) . "</td>";
    echo "<td>" . $event['schedule'] . "</td>";
    echo "<td>" . htmlspecialchars($event['banner']) . "</td>";
    echo "</tr>";
}
echo "</table>";
?> 