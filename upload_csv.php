<?php
session_start();

require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "alumni_db";

$conn = new mysqli($servername, $username, $password, $dbname);

function sendWelcomeEmail($email, $firstname, $lastname, $alumni_id, $random_password = null) {
    $config = require 'mailer_config.php';
    $school_name = "Pamantasan Ng Lungsod Ng Pasig";
    
    try {
        $mail = new PHPMailer(true);
        
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port = $config['port'];
        
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($email);
        
        $mail->isHTML(true);
        $mail->Subject = "Welcome to $school_name Alumni Portal";
        
        $message = "
        <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background-color: #3498db; color: white; padding: 20px; text-align: center;'>
                    <h2>$school_name Alumni Portal</h2>
                </div>
                <div style='padding: 20px; background-color: #f9f9f9;'>
                    <p>Dear $firstname $lastname,</p>
                    
                    <p>Welcome to the $school_name Alumni Portal! We're excited to have you join our alumni community.</p>
                    
                    <p><strong>Your account has been created with the following details:</strong></p>
                    <ul>
                        <li>Alumni ID: $alumni_id</li>
                        <li>Email/Username: $email</li>";
        
        if ($random_password) {
            $message .= "<li>Temporary Password: $random_password</li>";
        }
        
        $message .= "</ul>
                    
                    <p>You can now log in to the Alumni Portal to update your profile, connect with fellow alumni, 
                    and stay updated with the latest news and events from $school_name.</p>
                    
                    <p><a href='http://localhost/ALUMNI_PORTAL/index.php' 
                          style='display: inline-block; background-color: #3498db; color: white; padding: 10px 20px; 
                                 text-decoration: none; border-radius: 5px; margin-top: 15px;'>
                       Login to Alumni Portal</a></p>
                    
                    <p>If you have any questions or need assistance, please feel free to contact us.</p>
                    
                    <p>Best regards,<br>
                    Alumni Relations Office<br>
                    $school_name</p>
                </div>
                <div style='font-size: 12px; color: #777; text-align: center; margin-top: 20px;'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </div>";
        
        $mail->Body = $message;
        
        
        return $mail->send(); 
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

if ($conn->connect_error) {
    $_SESSION['upload_errors'] = ["Database connection failed: " . $conn->connect_error];
    header("Location: admin-alumni-upload.php?error=1&message=Database connection failed");
    exit;
}

try {
    $checkFKQuery = "SELECT * FROM information_schema.TABLE_CONSTRAINTS 
                    WHERE CONSTRAINT_TYPE = 'FOREIGN KEY' 
                    AND TABLE_NAME = 'users' 
                    AND CONSTRAINT_SCHEMA = '$dbname'";
    $fkResult = $conn->query($checkFKQuery);
    
    if ($fkResult && $fkResult->num_rows > 0) {
        while ($row = $fkResult->fetch_assoc()) {
            $constraintName = $row['CONSTRAINT_NAME'];
            $dropFKQuery = "ALTER TABLE users DROP FOREIGN KEY $constraintName";
            $conn->query($dropFKQuery);
        }
    }
    
    $alterAlumnusQuery = "ALTER TABLE alumnus_bio MODIFY alumni_id VARCHAR(20) NOT NULL";
    if (!$conn->query($alterAlumnusQuery)) {
        throw new Exception("Could not modify alumnus_bio table: " . $conn->error);
    }
    
    $alterUsersQuery = "ALTER TABLE users MODIFY alumni_id VARCHAR(20) NOT NULL";
    if (!$conn->query($alterUsersQuery)) {
        throw new Exception("Could not modify users table: " . $conn->error);
    }
    
} catch (Exception $e) {
    $_SESSION['upload_errors'] = ["Database schema modification error: " . $e->getMessage()];
    header("Location: admin-alumni-upload.php?error=1&message=Database structure error");
    exit;
}

if(isset($_POST['upload'])) {
    if(isset($_FILES["csv_file"]) && $_FILES["csv_file"]["error"] == 0) {
        $allowed = array("csv" => "text/csv", "csv" => "application/vnd.ms-excel", "csv" => "text/plain");
        $filename = $_FILES["csv_file"]["name"];
        $filetype = $_FILES["csv_file"]["type"];
        $filesize = $_FILES["csv_file"]["size"];
        
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if($ext != 'csv') {
            header("Location: admin-alumni-upload.php?error=1&message=Error: Please select a valid CSV file.");
            exit;
        }
        
        $maxsize = 5 * 1024 * 1024;
        if($filesize > $maxsize) {
            header("Location: admin-alumni-upload.php?error=1&message=Error: File size is larger than the allowed limit (5MB).");
            exit;
        }
        
        $csvFile = fopen($_FILES["csv_file"]["tmp_name"], 'r');
        
        fgetcsv($csvFile);
        
        $count = 0;
        $errors = 0;
        $errorMessages = [];
        $processedRows = 0;
        $invalidIdRows = [];
        $existingIds = [];
        $emailsSent = 0;
        
        $check_csvFile = fopen($_FILES["csv_file"]["tmp_name"], 'r');
        fgetcsv($check_csvFile); 
        $precheck_row = 0;
        
        while(($check_line = fgetcsv($check_csvFile)) !== FALSE) {
            $precheck_row++;
            if(count($check_line) >= 1) {
                $check_id = trim($check_line[0]);
                if(empty($check_id) || !preg_match('/^\d{4}-\d{4}$/', $check_id)) {
                    $invalidIdRows[] = $precheck_row;
                }
            }
        }
        fclose($check_csvFile);
        
        if(count($invalidIdRows) > 0) {
            $tmp_file = "tmp_uploads/" . time() . "_" . basename($filename);
            move_uploaded_file($_FILES["csv_file"]["tmp_name"], $tmp_file);
            
            $error_msg = "Invalid alumni IDs found in CSV. ";
            if(count($invalidIdRows) > 10) {
                $error_msg .= "Multiple rows (" . count($invalidIdRows) . " total) have invalid alumni IDs. ";
            } else {
                $error_msg .= "Rows with issues: " . implode(", ", $invalidIdRows) . ". ";
            }
            $error_msg .= "Please fix these issues or use our CSV Debug Tool.";
            
            $_SESSION['csv_file'] = $tmp_file;
            $_SESSION['upload_errors'] = ["Multiple alumni records have invalid ID format. Please fix your CSV file or use the Debug Tool below."];
            header("Location: admin-alumni-upload.php?error=1&message=" . urlencode($error_msg) . "&show_debug=1");
            exit;
        }
        
        while(($line = fgetcsv($csvFile)) !== FALSE) {
            $processedRows++;
            if(count($line) < 8) {
                $errors++;
                $errorMessages[] = "Row $processedRows: Insufficient columns, found " . count($line) . " (need 8)";
                continue;
            }
            
            try {
                $alumni_id = trim($conn->real_escape_string($line[0]));
                $firstname = trim($conn->real_escape_string($line[1]));
                $middlename = trim($conn->real_escape_string($line[2]));
                $lastname = trim($conn->real_escape_string($line[3]));
                $gender = trim($conn->real_escape_string($line[4]));
                $batch = trim($conn->real_escape_string($line[5]));
                $email = trim($conn->real_escape_string($line[6]));
                $course = trim($conn->real_escape_string($line[7]));
                
                if (!preg_match('/^\d{4}-\d{4}$/', $alumni_id)) {
                    $errors++;
                    $errorMessages[] = "Row $processedRows: Invalid alumni ID format for $firstname $lastname: '$alumni_id'. Format should be ####-####";
                    continue;
                }
                
                if(in_array($alumni_id, $existingIds)) {
                    $errors++;
                    $errorMessages[] = "Row $processedRows: Duplicate alumni ID '$alumni_id' for $firstname $lastname. Each alumni ID must be unique.";
                    continue;
                }
                $existingIds[] = $alumni_id;
                
                if (!in_array($gender, ['Male', 'Female', 'Not specified'])) {
                    $gender = 'Not specified';
                }
                
                if (!preg_match('/^\d{4}$/', $batch) || $batch < 1950 || $batch > (date('Y') + 5)) {
                    $batch = date('Y');
                    $errorMessages[] = "Row $processedRows: Invalid batch year for $firstname $lastname: '$batch'. Using current year instead.";
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors++;
                    $errorMessages[] = "Row $processedRows: Invalid email address for $firstname $lastname: '$email'";
                    continue;
                }
                
                $checkAlumnusBioQuery = "SELECT * FROM alumnus_bio WHERE alumni_id = '$alumni_id'";
                $checkUsersQuery = "SELECT * FROM users WHERE alumni_id = '$alumni_id'";
                $alumniExists = ($conn->query($checkAlumnusBioQuery)->num_rows > 0);
                $userExists = ($conn->query($checkUsersQuery)->num_rows > 0);
                
                if ($alumniExists || $userExists) {
                    if ($alumniExists) {
                        $updateQuery = "UPDATE alumnus_bio SET 
                                       firstname = '$firstname',
                                       middlename = '$middlename',
                                       lastname = '$lastname',
                                       gender = '$gender',
                                       batch = '$batch',
                                       email = '$email'
                                       WHERE alumni_id = '$alumni_id'";
                        
                        if (!$conn->query($updateQuery)) {
                            throw new Exception("Error updating alumnus_bio: " . $conn->error);
                        }
                    } else {
                        $course_id = 0;
                        $course_query = "SELECT id FROM courses WHERE course LIKE '%$course%'";
                        $course_result = $conn->query($course_query);
                        
                        if($course_result && $course_result->num_rows > 0) {
                            $course_row = $course_result->fetch_assoc();
                            $course_id = $course_row['id'];
                        } else {
                            $new_course_query = "INSERT INTO courses (course, about) VALUES ('$course', 'Added from CSV import')";
                            if($conn->query($new_course_query) === TRUE) {
                                $course_id = $conn->insert_id;
                            } else {
                                $errors++;
                                $errorMessages[] = "Error creating course: " . $conn->error;
                                continue;
                            }
                        }
                        
                        $sql = "INSERT INTO alumnus_bio (alumni_id, firstname, middlename, lastname, gender, batch, course_id, email, connected_to, avatar, status) 
                                VALUES ('$alumni_id', '$firstname', '$middlename', '$lastname', '$gender', '$batch', '$course_id', '$email', 0, 'avatar.png', 1)";
                        
                        if($conn->query($sql) !== TRUE) {
                            throw new Exception("Row $processedRows: Error creating alumni record for $firstname $lastname: " . $conn->error);
                        }
                    }
                    
                    if ($userExists) {
                        $updateUserQuery = "UPDATE users SET 
                                          name = '$firstname $lastname',
                                          username = '$email'
                                          WHERE alumni_id = '$alumni_id'";
                        
                        if (!$conn->query($updateUserQuery)) {
                            throw new Exception("Error updating users: " . $conn->error);
                        }
                    } else {
                        $random_password = substr(md5(rand()), 0, 8);
                        $hashed_password = md5($random_password);
                        
                        $name = $firstname . ' ' . $lastname;
                        $sql_user = "INSERT INTO users (alumni_id, name, username, password, type, auto_generated_pass) 
                                    VALUES ('$alumni_id', '$name', '$email', '$hashed_password', 3, '$random_password')";
                        
                        if(!$conn->query($sql_user)) {
                            throw new Exception("Error creating user account: " . $conn->error);
                        }
                        
                        if(sendWelcomeEmail($email, $firstname, $lastname, $alumni_id, $random_password)) {
                            $emailsSent++;
                        } else {
                            $errorMessages[] = "Warning: Failed to send welcome email to $email";
                        }
                    }
                    
                    $count++;
                } else {
                    $course_id = 0;
                    $course_query = "SELECT id FROM courses WHERE course LIKE '%$course%'";
                    $course_result = $conn->query($course_query);
                    
                    if($course_result && $course_result->num_rows > 0) {
                        $course_row = $course_result->fetch_assoc();
                        $course_id = $course_row['id'];
                    } else {
                        $new_course_query = "INSERT INTO courses (course, about) VALUES ('$course', 'Added from CSV import')";
                        if($conn->query($new_course_query) === TRUE) {
                            $course_id = $conn->insert_id;
                        } else {
                            $errors++;
                            $errorMessages[] = "Error creating course: " . $conn->error;
                            continue;
                        }
                    }
                    
                    $sql = "INSERT INTO alumnus_bio (alumni_id, firstname, middlename, lastname, gender, batch, course_id, email, connected_to, avatar, status) 
                            VALUES ('$alumni_id', '$firstname', '$middlename', '$lastname', '$gender', '$batch', '$course_id', '$email', 0, 'avatar.png', 1)";
                    
                    if($conn->query($sql) === TRUE) {
                        $count++;
                        
                        $random_password = substr(md5(rand()), 0, 8);
                        $hashed_password = md5($random_password);
                        
                        $name = $firstname . ' ' . $lastname;
                        $sql_user = "INSERT INTO users (alumni_id, name, username, password, type, auto_generated_pass) 
                                    VALUES ('$alumni_id', '$name', '$email', '$hashed_password', 3, '$random_password')";
                        
                        if(!$conn->query($sql_user)) {
                            $errors++;
                            $errorMessages[] = "Error creating user account: " . $conn->error;
                        } else {
                            if(sendWelcomeEmail($email, $firstname, $lastname, $alumni_id, $random_password)) {
                                $emailsSent++;
                            } else {
                                $errorMessages[] = "Warning: Failed to send welcome email to $email";
                            }
                        }
                    } else {
                        $errors++;
                        $errorMessages[] = "Row $processedRows: Error creating alumni record for $firstname $lastname: " . $conn->error;
                    }
                }
            } catch (Exception $e) {
                $errors++;
                $errorMessages[] = "Row $processedRows: Exception: " . $e->getMessage();
            }
        }
        
        fclose($csvFile);
        
        $_SESSION['upload_errors'] = $errorMessages;
        
        if($count > 0) {
            $message = "Success: $count alumni records imported or updated. ";
            if($emailsSent > 0) {
                $message .= "$emailsSent welcome emails sent. ";
            }
            if($errors > 0) {
                $message .= "$errors errors encountered. See details below.";
            }
            header("Location: admin-alumni-upload.php?success=1&message=" . urlencode($message) . ($errors > 0 ? "&show_errors=1" : ""));
        } else {
            header("Location: admin-alumni-upload.php?error=1&message=Error: No alumni records were imported. See details below.&show_errors=1");
        }
        
    } else {
        $error = "Unknown error";
        if(isset($_FILES["csv_file"]["error"])) {
            switch($_FILES["csv_file"]["error"]) {
                case 1:
                    $error = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                    break;
                case 2:
                    $error = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                    break;
                case 3:
                    $error = "The uploaded file was only partially uploaded";
                    break;
                case 4:
                    $error = "No file was uploaded";
                    break;
                case 6:
                    $error = "Missing a temporary folder";
                    break;
                case 7:
                    $error = "Failed to write file to disk";
                    break;
                case 8:
                    $error = "A PHP extension stopped the file upload";
                    break;
            }
        }
        $_SESSION['upload_errors'] = [$error];
        header("Location: admin-alumni-upload.php?error=1&message=Error: " . $error);
    }
} else {
    header("Location: admin-alumni-upload.php");
}

$conn->close();
?>
