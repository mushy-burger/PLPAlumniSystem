<?php
session_start();
include 'admin/db_connect.php';

$error_msg = "";

if(isset($_POST['login'])) {
    $alumni_id = $conn->real_escape_string($_POST['alumni_id']);
    $password = md5($_POST['password']); // Note: MD5 is used to match existing hashing in the database
    
    $sql = "SELECT u.*, a.status FROM users u LEFT JOIN alumnus_bio a ON u.alumni_id = a.alumni_id WHERE u.alumni_id = ? AND u.password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $alumni_id, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        if($row['type'] == 3 && $row['status'] == 0) {
            $error_msg = "Your account is pending verification. Please contact the administrator.";
        } else {
            $_SESSION['login_id'] = $row['alumni_id'];
            $_SESSION['login_name'] = $row['name'];
            $_SESSION['login_type'] = $row['type'];
            
            if($row['type'] == 1) {
                header("Location: admin-home.php"); 
            } elseif($row['type'] == 2) {
                header("Location: admin-home.php"); 
            } else {
                header("Location: alumni-home.php"); 
            }
            exit;
        }
    } else {
        $error_msg = "Invalid Alumni ID or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">  cc
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PLP Alumni - Log In</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="css/login.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    /* Override styles for back button icon */
    .back-link .back-icon,
    .back-link i.fas.fa-arrow-left {
      font-size: 24px !important;
    }
    
    .back-link {
      width: 50px !important;
      height: 50px !important;
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
    <a href="landing.php" class="back-link" title="Back to Landing Page"><i class="fas fa-arrow-left back-icon" style="font-size: 36px !important;"></i></a>
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
            <h2 class="overlay-text">WELCOME BACK!</h2>
            <p class="overlay-subtext">Connect with your fellow alumni</p>
          </div>
        </div>
      </div>
      <div class="login-form-container">
        <div class="form-header">
          <h2>Sign In to Your Account</h2>
          <p>Enter your credentials to access your alumni portal</p>
        </div>
        
        <?php if(!empty($error_msg)): ?>
        <div class="login-alert">
          <i class="fas fa-exclamation-circle"></i>
          <?php echo $error_msg; ?>
        </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['success'])): ?>
        <div class="login-success">
          <i class="fas fa-check-circle"></i>
          <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['login_error'])): ?>
        <div class="login-alert">
          <i class="fas fa-exclamation-circle"></i>
          <?php echo $_SESSION['login_error']; unset($_SESSION['login_error']); ?>
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
            <label for="password">Password</label>
            <div class="input-icon-wrapper">
              <i class="fas fa-lock"></i>
              <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <div class="password-options">
              <label class="show-password">
                <input type="checkbox" id="showPassword"> 
                <span>Show Password</span>
              </label>
            </div>
          </div>
          
          <button type="submit" name="login" class="login-btn">
            <i class="fas fa-sign-in-alt"></i> Login
          </button>
        </form>
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

  showPasswordCheckbox.addEventListener('change', function () {
    if (this.checked) {
      passwordInput.type = 'text';
    } else {
      passwordInput.type = 'password';
    }
  });

  const menuButton = document.getElementById('menuButton');
  const modal = document.getElementById('modal');

  menuButton.onclick = function() {
    modal.classList.toggle('show');
  }

  window.onclick = function(event) {
    if (!event.target.closest('.menu') && !event.target.closest('#modal')) {
      modal.classList.remove('show');
    }
  }
</script>

</body>
</html>
