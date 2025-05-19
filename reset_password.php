<?php
session_start();
include 'admin/db_connect.php';

$error_msg = "";
$success_msg = "";
$token_valid = false;
$token = "";

if(isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    $sql = "SELECT u.*, a.firstname, a.lastname FROM users u 
            LEFT JOIN alumnus_bio a ON u.alumni_id = a.alumni_id 
            WHERE u.reset_token = ? AND u.token_expiry > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $token_valid = true;
    } else {
        $error_msg = "Invalid or expired reset token. Please request a new password reset link.";
    }
} else {
    $error_msg = "No reset token provided. Please request a password reset from the forgot password page.";
}

if(isset($_POST['update_password']) && $token_valid) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if($password !== $confirm_password) {
        $error_msg = "Passwords do not match.";
    } else {
        $uppercase = preg_match('/[A-Z]/', $password);
        $lowercase = preg_match('/[a-z]/', $password);
        $special = preg_match('/[^A-Za-z0-9]/', $password);
        $is_valid = true;
        $validation_errors = [];
        
        if(strlen($password) < 8) {
            $is_valid = false;
            $validation_errors[] = "Password must be at least 8 characters long";
        }
        
        if(!$uppercase) {
            $is_valid = false;
            $validation_errors[] = "Password must contain at least one uppercase letter";
        }
        
        if(!$lowercase) {
            $is_valid = false;
            $validation_errors[] = "Password must contain at least one lowercase letter";
        }
        
        if(!$special) {
            $is_valid = false;
            $validation_errors[] = "Password must contain at least one special character";
        }
        
        if(!$is_valid) {
            $error_msg = "Password does not meet requirements:<br>" . implode("<br>", $validation_errors);
        } else {
            // Update password
            $hashed_password = md5($password); // Using MD5 to match existing system
            
            $update_sql = "UPDATE users SET 
                          password = ?, 
                          reset_token = NULL, 
                          token_expiry = NULL 
                          WHERE reset_token = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ss", $hashed_password, $token);
            
            if($update_stmt->execute()) {
                $success_msg = "Your password has been updated successfully. You can now login with your new password.";
                $token_valid = false;
            } else {
                $error_msg = "Error updating password. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"> 
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PLP Alumni - Reset Password</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="css/login.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    .password-strength {
      margin-top: 5px;
      font-size: 12px;
    }
    .password-strength span {
      display: inline-block;
      width: 20%;
      height: 5px;
      background-color: #ddd;
      margin-right: 2px;
    }
    .password-strength span.active {
      background-color: #4caf50;
    }
    .password-strength span.medium {
      background-color: #ff9800;
    }
    .password-strength span.weak {
      background-color: #f44336;
    }
    .password-requirements {
      font-size: 12px;
      color: #666;
      margin-top: 5px;
    }
    .password-requirements ul {
      padding-left: 15px;
      margin-top: 3px;
      margin-bottom: 0;
    }
    .password-requirements li {
      margin-bottom: 2px;
    }
    .password-requirements li.valid {
      color: #4caf50;
    }
    .password-requirements li.valid::before {
      content: "✓ ";
    }
    .password-requirements li.invalid {
      color: #f44336;
    }
    .password-requirements li.invalid::before {
      content: "✗ ";
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
            <p class="overlay-subtext">Create a new secure password</p>
          </div>
        </div>
      </div>
      <div class="login-form-container">
        <div class="form-header">
          <h2>Create New Password</h2>
          <p>Please enter a new password for your account</p>
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
          <div style="margin-top: 15px;">
            <a href="login.php" class="login-btn">
              <i class="fas fa-sign-in-alt"></i> Go to Login
            </a>
          </div>
        </div>
        <?php endif; ?>

        <?php if($token_valid): ?>
        <form class="login-form" method="POST">
          <div class="form-group">
            <label for="password">New Password</label>
            <div class="input-icon-wrapper">
              <i class="fas fa-lock"></i>
              <input type="password" id="password" name="password" placeholder="Enter new password" required minlength="8">
            </div>
            <div class="password-strength">
              <span id="strength-1"></span>
              <span id="strength-2"></span>
              <span id="strength-3"></span>
              <span id="strength-4"></span>
              <span id="strength-5"></span>
            </div>
            <div class="password-requirements">
              Password must:
              <ul>
                <li id="req-length">Be at least 8 characters long</li>
                <li id="req-uppercase">Include at least one uppercase letter</li>
                <li id="req-lowercase">Include at least one lowercase letter</li>
                <li id="req-special">Include at least one special character</li>
              </ul>
            </div>
          </div>
          
          <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <div class="input-icon-wrapper">
              <i class="fas fa-lock"></i>
              <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your new password" required minlength="8">
            </div>
            <div class="password-options">
              <label class="show-password">
                <input type="checkbox" id="showPassword"> 
                <span>Show Password</span>
              </label>
            </div>
          </div>
          
          <button type="submit" name="update_password" class="login-btn">
            <i class="fas fa-key"></i> Update Password
          </button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <footer class="login-footer">
    <p>&copy; <?php echo date("Y"); ?> Pamantasan Ng Lungsod Ng Pasig - Alumni Portal</p>
  </footer>
</div>

<script>
  const showPasswordCheckbox = document.getElementById('showPassword');
  const passwordInput = document.getElementById('password');
  const confirmPasswordInput = document.getElementById('confirm_password');

  if (showPasswordCheckbox) {
    showPasswordCheckbox.addEventListener('change', function () {
      if (this.checked) {
        passwordInput.type = 'text';
        confirmPasswordInput.type = 'text';
      } else {
        passwordInput.type = 'password';
        confirmPasswordInput.type = 'password';
      }
    });
  }

  const passwordField = document.getElementById('password');
  if (passwordField) {
    passwordField.addEventListener('input', function() {
      const password = this.value;
      const strength = calculatePasswordStrength(password);
      updateStrengthMeter(strength);
      updateRequirements(password);
    });
  }

  function calculatePasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength += 1;
    if (password.length >= 10) strength += 1;
    if (/[A-Z]/.test(password)) strength += 1;
    if (/[0-9]/.test(password)) strength += 1;
    if (/[^A-Za-z0-9]/.test(password)) strength += 1;
    
    return strength;
  }

  function updateStrengthMeter(strength) {
    const strengthBars = [
      document.getElementById('strength-1'),
      document.getElementById('strength-2'),
      document.getElementById('strength-3'),
      document.getElementById('strength-4'),
      document.getElementById('strength-5')
    ];
    
    for (let i = 0; i < strengthBars.length; i++) {
      if (!strengthBars[i]) continue;
      
      if (i < strength) {
        strengthBars[i].className = '';
        if (strength <= 2) {
          strengthBars[i].classList.add('weak');
        } else if (strength <= 3) {
          strengthBars[i].classList.add('medium');
        } else {
          strengthBars[i].classList.add('active');
        }
      } else {
        strengthBars[i].className = '';
      }
    }
  }

  function updateRequirements(password) {
    const lengthReq = document.getElementById('req-length');
    const uppercaseReq = document.getElementById('req-uppercase');
    const lowercaseReq = document.getElementById('req-lowercase');
    const specialReq = document.getElementById('req-special');
    
    if (password.length >= 8) {
      lengthReq.classList.remove('invalid');
      lengthReq.classList.add('valid');
    } else {
      lengthReq.classList.remove('valid');
      lengthReq.classList.add('invalid');
    }
    
    if (/[A-Z]/.test(password)) {
      uppercaseReq.classList.remove('invalid');
      uppercaseReq.classList.add('valid');
    } else {
      uppercaseReq.classList.remove('valid');
      uppercaseReq.classList.add('invalid');
    }
    
    if (/[a-z]/.test(password)) {
      lowercaseReq.classList.remove('invalid');
      lowercaseReq.classList.add('valid');
    } else {
      lowercaseReq.classList.remove('valid');
      lowercaseReq.classList.add('invalid');
    }
    
    if (/[^A-Za-z0-9]/.test(password)) {
      specialReq.classList.remove('invalid');
      specialReq.classList.add('valid');
    } else {
      specialReq.classList.remove('valid');
      specialReq.classList.add('invalid');
    }
  }
  
  document.addEventListener('DOMContentLoaded', function() {
    const passwordField = document.getElementById('password');
    if (passwordField) {
      document.getElementById('req-length').classList.add('invalid');
      document.getElementById('req-uppercase').classList.add('invalid');
      document.getElementById('req-lowercase').classList.add('invalid');
      document.getElementById('req-special').classList.add('invalid');
    }
  });
</script>

</body>
</html> 