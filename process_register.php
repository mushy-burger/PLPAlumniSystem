<?php
session_start();
include 'admin/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alumni_id = trim($conn->real_escape_string($_POST['alumni_id']));
    $first_name = trim($conn->real_escape_string($_POST['first_name']));
    $middle_name = trim($conn->real_escape_string($_POST['middle_name'] ?? ''));
    $last_name = trim($conn->real_escape_string($_POST['last_name']));
    $gender = trim($conn->real_escape_string($_POST['gender']));
    $email = trim($conn->real_escape_string($_POST['email']));
    $batch = intval($_POST['batch']);
    $course_id = intval($_POST['course_id']);
    $connected_to = intval($_POST['connected_to']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    $_SESSION['register_old'] = [
        'alumni_id' => $alumni_id,
        'first_name' => $first_name,
        'middle_name' => $middle_name,
        'last_name' => $last_name,
        'gender' => $gender,
        'email' => $email,
        'batch' => $batch,
        'course_id' => $course_id,
        'connected_to' => $connected_to
    ];
    
    $errors = [];
    
    if (empty($alumni_id)) {
        $errors[] = "Alumni ID is required";
    } elseif (!preg_match('/^\d{4}-\d{4}$/', $alumni_id)) {
        $errors[] = "Alumni ID must be in the format ####-####";
    }
    
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    if ($batch < 1980 || $batch > date('Y')) {
        $errors[] = "Graduation year must be between 1980 and " . date('Y');
    }
    if ($course_id <= 0) {
        $errors[] = "Please select a course";
    }
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    $user_check = "SELECT * FROM users WHERE alumni_id = ?";
    $stmt = $conn->prepare($user_check);
    $stmt->bind_param("s", $alumni_id);
    $stmt->execute();
    $user_result = $stmt->get_result();
    
    $is_user_update = false;
    if ($user_result->num_rows > 0) {
        $is_user_update = true;
    }
    
    if (!empty($errors)) {
        $_SESSION['register_errors'] = $errors;
        header("Location: register.php");
        exit;
    }
    
    $conn->begin_transaction();
    
    try {
        $hashed_password = md5($password);
        
        $alumni_check = "SELECT * FROM alumnus_bio WHERE alumni_id = ?";
        $stmt = $conn->prepare($alumni_check);
        $stmt->bind_param("s", $alumni_id);
        $stmt->execute();
        $alumni_result = $stmt->get_result();
        
        $is_update = false;
        
        if ($alumni_result->num_rows > 0) {
            $is_update = true;
            
            $update_sql = "UPDATE alumnus_bio SET 
                firstname = ?, 
                middlename = ?,
                lastname = ?,
                gender = ?,
                email = ?,
                batch = ?,
                course_id = ?,
                connected_to = ? 
                WHERE alumni_id = ?";
                
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param(
                "sssssiiis", 
                $first_name, 
                $middle_name, 
                $last_name, 
                $gender, 
                $email, 
                $batch, 
                $course_id, 
                $connected_to,
                $alumni_id
            );
            $stmt->execute();
        } else {
            $insert_sql = "INSERT INTO alumnus_bio (
                alumni_id, firstname, middlename, lastname, gender, 
                email, batch, course_id, connected_to, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
            
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param(
                "sssssiiis", 
                $alumni_id, 
                $first_name, 
                $middle_name, 
                $last_name, 
                $gender, 
                $email, 
                $batch, 
                $course_id,
                $connected_to
            );
            $stmt->execute();
        }
        
        $name = $first_name . ' ' . $last_name;
        $username = strtolower(str_replace(' ', '', $first_name)) . '.' . strtolower(str_replace(' ', '', $last_name));
        
        if ($is_user_update) {
            $update_user = "UPDATE users SET name = ?, username = ?, password = ? WHERE alumni_id = ?";
            $stmt = $conn->prepare($update_user);
            $stmt->bind_param("ssss", $name, $username, $hashed_password, $alumni_id);
            $stmt->execute();
        } else {
            $base_username = $username;
            $counter = 1;
            
            while (true) {
                $check_username = "SELECT * FROM users WHERE username = ?";
                $stmt = $conn->prepare($check_username);
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    break; 
                }
                
                $username = $base_username . $counter;
                $counter++;
            }
            
            $insert_user = "INSERT INTO users (alumni_id, name, username, password, type) VALUES (?, ?, ?, ?, 3)";
            $stmt = $conn->prepare($insert_user);
            $stmt->bind_param("ssss", $alumni_id, $name, $username, $hashed_password);
            $stmt->execute();
        }
        
        $conn->commit();
        
        if ($is_user_update) {
            $_SESSION['success'] = "Your existing account has been updated successfully!";
        } else if ($is_update) {
            $_SESSION['success'] = "✅ Success! Your alumni profile was updated and your portal account has been created.";
        } else {
            $_SESSION['success'] = "Registration successful! Your account has been created.";
        }
        
        header("Location: login.php");
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        
        $_SESSION['register_errors'] = ["An error occurred during registration: " . $e->getMessage()];
        header("Location: register.php");
        exit;
    }
    
} else {
    header("Location: register.php");
    exit;
}
?>
