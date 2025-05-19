<?php
session_start();
include 'admin/db_connect.php';

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error_msg = "";
$success_msg = "";

if(isset($_POST['reset_password'])) {
    $alumni_id = $conn->real_escape_string($_POST['alumni_id']);
    $email = $conn->real_escape_string($_POST['email']);
    
    $sql = "SELECT u.*, a.email FROM users u 
            LEFT JOIN alumnus_bio a ON u.alumni_id = a.alumni_id 
            WHERE u.alumni_id = ? AND (a.email = ? OR u.username = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $alumni_id, $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $token_sql = "UPDATE users SET 
                    reset_token = ?, 
                    token_expiry = ? 
                    WHERE alumni_id = ?";
        $token_stmt = $conn->prepare($token_sql);
        $token_stmt->bind_param("sss", $token, $expiry, $alumni_id);
        
        if($token_stmt->execute()) {
            $config = require 'mailer_config.php';
            
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
                
                $email_to_use = $email;
                if (!empty($user['email']) && $user['email'] != $email) {
                    $email_to_use = $user['email'];
                } else if (!empty($user['username']) && filter_var($user['username'], FILTER_VALIDATE_EMAIL) && $user['username'] != $email) {
                    $email_to_use = $user['username'];
                }
                
                $mail->addAddress($email_to_use);
                
                $reset_link = "http://{$_SERVER['HTTP_HOST']}/ALUMNI_PORTAL/reset_password.php?token=$token";
                
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset - PLP Alumni Portal';
                $mail->Body = "
                    <h2>Password Reset Request</h2>
                    <p>Dear {$user['name']},</p>
                    <p>You recently requested to reset your password for the PLP Alumni Portal. Click the link below to reset it:</p>
                    <p><a href=\"$reset_link\">Reset Your Password</a></p>
                    <p>This link will expire in 24 hours. If you did not request a password reset, please ignore this email.</p>
                    <p>Thank you,<br>PLP Alumni Portal Team</p>
                ";
                
                $mail->send();
                $success_msg = "Password reset instructions have been sent to your email address.";
            } catch (Exception $e) {
                $error_msg = "Error sending email: " . $mail->ErrorInfo;
            }
        } else {
            $error_msg = "Error generating reset token. Please try again.";
        }
    } else {
        $error_msg = "No account found with the provided Alumni ID and email address.";
        
        $debug_sql = "SELECT a.email as bio_email, u.username as user_email 
                      FROM users u 
                      LEFT JOIN alumnus_bio a ON u.alumni_id = a.alumni_id 
                      WHERE u.alumni_id = ?";
        $debug_stmt = $conn->prepare($debug_sql);
        $debug_stmt->bind_param("s", $alumni_id);
        $debug_stmt->execute();
        $debug_result = $debug_stmt->get_result();
        
        if($debug_result->num_rows > 0) {
            $debug_info = $debug_result->fetch_assoc();
            $error_msg .= "<br><small>(Debug: Please try using this email: " . 
                         (!empty($debug_info['bio_email']) ? $debug_info['bio_email'] : $debug_info['user_email']) . 
                         ")</small>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"> 
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PLP Alumni - Forgot Password</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="css/login.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    .back-link {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      transition: background 0.2s;
      text-decoration: none;
      color: #1a237e;
    }
    .back-link:hover {
      background: #e3e6f3;
    }
    .back-link .fa-arrow-left {
      font-size: 20px;
    }
  </style>
</head>
<body>

<header class="header">
  <div class="header-left">
    <img src="images/logo.png" alt="PLP Logo" class="logo">
    <div class="text">
      <div class="school-name">Pamantasan Ng Lungsod Ng Pasig</div>
      <div class="alumni-title">ALUMNI</div>
    </div>
  </div>
  <div class="header-right">
    <a href="login.php" class="back-link" title="Back to Login"><i class="fas fa-arrow-left"></i></a>
  </div>
</header>

<div class="login-background">
  <section class="login-section">
    <div class="login-container">
      <div class="login-image-container">
        <div class="login-image">
          <img src="images/plpasigg.jpg" alt="Campus Image">
        </div>
        <div class="image-overlay">
          <div class="overlay-content">
            <h2 class="overlay-text">RESET PASSWORD</h2>
            <p class="overlay-subtext">We'll help you get back in</p>
          </div>
        </div>
      </div>
      <div class="login-form-container">
        <div class="form-header">
          <h2>Forgot Your Password?</h2>
          <p>Enter your Alumni ID and registered email address to receive a password reset link</p>
        </div>
        
        <?php if(!empty($error_msg)): ?>
        <div class="login-alert">
          <i class="fas fa-exclamation-circle"></i>
          <?php echo $error_msg; ?>
        </div>
        <?php endif; ?>

        <?php if(!empty($success_msg)): ?>
        <div class="login-success">
          <i class="fas fa-check-circle"></i>
          <?php echo $success_msg; ?>
        </div>
        <?php endif; ?>

        <form class="login-form" method="POST">
          <div class="form-group">
            <label for="alumni_id">Alumni ID</label>
            <div class="input-icon-wrapper">
              <i class="fas fa-id-card"></i>
              <input type="text" id="alumni_id" name="alumni_id" placeholder="Enter your Alumni ID" required>
            </div>
          </div>
          
          <div class="form-group">
            <label for="email">Email Address</label>
            <div class="input-icon-wrapper">
              <i class="fas fa-envelope"></i>
              <input type="email" id="email" name="email" placeholder="Enter your registered email" required>
            </div>
          </div>
          
          <button type="submit" name="reset_password" class="login-btn">
            <i class="fas fa-paper-plane"></i> Send Reset Link
          </button>
          
          <div class="form-footer">
            <p>Remember your password? <a href="login.php">Back to Login</a></p>
          </div>
        </form>
      </div>
    </div>
  </section>

  <footer class="login-footer">
    <p>&copy; <?php echo date("Y"); ?> Pamantasan Ng Lungsod Ng Pasig - Alumni Portal</p>
  </footer>
</div>

</body>
</html> 