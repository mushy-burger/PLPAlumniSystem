<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    $structure_query = "DESCRIBE courses";
    $structure_result = $conn->query($structure_query);
    
    $has_course_code = false;
    $has_department = false;
    
    while ($field = $structure_result->fetch_assoc()) {
        if ($field['Field'] === 'course_code') {
            $has_course_code = true;
        } else if ($field['Field'] === 'department') {
            $has_department = true;
        }
    }
    
    switch ($action) {
        case 'add':
            $course_name = trim($_POST['course_name']);
            $course_code = $has_course_code ? trim($_POST['course_code']) : '';
            $department = $has_department ? trim($_POST['department']) : '';
            
            if (empty($course_name)) {
                $_SESSION['course_message'] = "Course name is required";
                $_SESSION['course_message_type'] = "error";
                header("Location: ../admin-course-list.php");
                exit;
            }
            
            if ($has_course_code && empty($course_code)) {
                $_SESSION['course_message'] = "Course code is required";
                $_SESSION['course_message_type'] = "error";
                header("Location: ../admin-course-list.php");
                exit;
            }
            
            if ($has_department && empty($department)) {
                $_SESSION['course_message'] = "Department is required";
                $_SESSION['course_message_type'] = "error";
                header("Location: ../admin-course-list.php");
                exit;
            }
            
            $check_query = "SELECT id FROM courses WHERE course = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("s", $course_name);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $_SESSION['course_message'] = "A course with this name already exists";
                $_SESSION['course_message_type'] = "error";
                header("Location: ../admin-course-list.php");
                exit;
            }
            
            $columns = ['course'];
            $values = ['?'];
            $types = 's';
            $params = [$course_name];
            
            if ($has_course_code) {
                $columns[] = 'course_code';
                $values[] = '?';
                $types .= 's';
                $params[] = $course_code;
            }
            
            if ($has_department) {
                $columns[] = 'department';
                $values[] = '?';
                $types .= 's';
                $params[] = $department;
            }
            
            $insert_query = "INSERT INTO courses (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                $_SESSION['course_message'] = "Course added successfully";
                $_SESSION['course_message_type'] = "success";
            } else {
                $_SESSION['course_message'] = "Error adding course: " . $conn->error;
                $_SESSION['course_message_type'] = "error";
            }
            
            break;
            
        case 'edit':
            $course_id = intval($_POST['course_id']);
            $course_name = trim($_POST['course_name']);
            $course_code = $has_course_code ? trim($_POST['course_code']) : '';
            $department = $has_department ? trim($_POST['department']) : '';
            
            if ($course_id <= 0 || empty($course_name)) {
                $_SESSION['course_message'] = "Course ID and name are required";
                $_SESSION['course_message_type'] = "error";
                header("Location: ../admin-course-list.php");
                exit;
            }
            
            if ($has_course_code && empty($course_code)) {
                $_SESSION['course_message'] = "Course code is required";
                $_SESSION['course_message_type'] = "error";
                header("Location: ../admin-course-list.php");
                exit;
            }
            
            if ($has_department && empty($department)) {
                $_SESSION['course_message'] = "Department is required";
                $_SESSION['course_message_type'] = "error";
                header("Location: ../admin-course-list.php");
                exit;
            }
            
            $check_query = "SELECT id FROM courses WHERE course = ? AND id != ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("si", $course_name, $course_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $_SESSION['course_message'] = "Another course with this name already exists";
                $_SESSION['course_message_type'] = "error";
                header("Location: ../admin-course-list.php");
                exit;
            }
            
            $updates = ['course = ?'];
            $types = 's';
            $params = [$course_name];
            
            if ($has_course_code) {
                $updates[] = 'course_code = ?';
                $types .= 's';
                $params[] = $course_code;
            }
            
            if ($has_department) {
                $updates[] = 'department = ?';
                $types .= 's';
                $params[] = $department;
            }
            
            $params[] = $course_id;
            $types .= 'i';
            
            $update_query = "UPDATE courses SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                $_SESSION['course_message'] = "Course updated successfully";
                $_SESSION['course_message_type'] = "success";
            } else {
                $_SESSION['course_message'] = "Error updating course: " . $conn->error;
                $_SESSION['course_message_type'] = "error";
            }
            
            break;
            
        case 'delete':
            $course_id = intval($_POST['course_id']);
            
            if ($course_id <= 0) {
                $_SESSION['course_message'] = "Invalid course ID";
                $_SESSION['course_message_type'] = "error";
                header("Location: ../admin-course-list.php");
                exit;
            }
            
            $check_query = "SELECT COUNT(*) as count FROM alumnus_bio WHERE course_id = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("i", $course_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                $_SESSION['course_message'] = "Cannot delete course because it is assigned to alumni records";
                $_SESSION['course_message_type'] = "error";
                header("Location: ../admin-course-list.php");
                exit;
            }
            
            $delete_query = "DELETE FROM courses WHERE id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $course_id);
            
            if ($stmt->execute()) {
                $_SESSION['course_message'] = "Course deleted successfully";
                $_SESSION['course_message_type'] = "success";
            } else {
                $_SESSION['course_message'] = "Error deleting course: " . $conn->error;
                $_SESSION['course_message_type'] = "error";
            }
            
            break;
            
        default:
            $_SESSION['course_message'] = "Invalid action";
            $_SESSION['course_message_type'] = "error";
    }
} else {
    $_SESSION['course_message'] = "Invalid request method";
    $_SESSION['course_message_type'] = "error";
}

header("Location: ../admin-course-list.php");
exit;
?>
