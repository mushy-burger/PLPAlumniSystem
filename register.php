<?php
session_start();
include 'admin/db_connect.php';

$course_query = "SELECT id, course FROM courses ORDER BY course ASC";
$course_result = $conn->query($course_query);
$courses = [];
if ($course_result && $course_result->num_rows > 0) {
    while($row = $course_result->fetch_assoc()) {
        $courses[] = $row;
    }
}

$errors = isset($_SESSION['register_errors']) ? $_SESSION['register_errors'] : [];
$old = isset($_SESSION['register_old']) ? $_SESSION['register_old'] : [];

unset($_SESSION['register_errors']);
unset($_SESSION['register_old']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PLP Alumni - Register</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="css/register.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
  <div class="menu" id="menuButton">
    <img src="images/menu.png" alt="Menu">
  </div>
</header>

<div id="modal" class="modal">
  <a href="register.php">Register</a>
  <a href="login.php">Log In</a>
  <a href="landing.php">Back</a>
</div>

<section class="register-section">
  <div class="register-container">
    <div class="register-header">
      <h2><i class="fas fa-user-plus"></i> Alumni Registration</h2>
      <p>Create your alumni account to access exclusive features</p>
    </div>
    
    <?php if(!empty($errors)): ?>
    <div class="alert alert-danger">
      <i class="fas fa-exclamation-circle"></i>
      <div>
        <strong>Please correct the following errors:</strong>
        <ul>
          <?php foreach($errors as $error): ?>
            <li><?php echo $error; ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <?php endif; ?>

    <form class="register-form" action="process_register.php" method="POST" id="registerForm">
      <div class="form-columns">
        <div class="form-column">
          <h3 class="section-title"><i class="fas fa-user"></i> Personal Information</h3>
          
          <div class="form-group">
            <label for="alumni_id">Alumni ID <span class="required">*</span></label>
            <input type="text" id="alumni_id" name="alumni_id" pattern="\d{4}-\d{4}" title="Please enter your Alumni ID in the format: ####-####" value="<?php echo isset($old['alumni_id']) ? htmlspecialchars($old['alumni_id']) : ''; ?>" required>
            <div class="form-hint">Your unique alumni identification number. If you've previously provided information, entering your ID will update your record.</div>
          </div>
          
          <div class="form-group">
            <label for="first_name">First Name <span class="required">*</span></label>
            <input type="text" id="first_name" name="first_name" value="<?php echo isset($old['first_name']) ? htmlspecialchars($old['first_name']) : ''; ?>" required>
          </div>
          
          <div class="form-group">
            <label for="middle_name">Middle Name</label>
            <input type="text" id="middle_name" name="middle_name" value="<?php echo isset($old['middle_name']) ? htmlspecialchars($old['middle_name']) : ''; ?>">
          </div>
          
          <div class="form-group">
            <label for="last_name">Last Name <span class="required">*</span></label>
            <input type="text" id="last_name" name="last_name" value="<?php echo isset($old['last_name']) ? htmlspecialchars($old['last_name']) : ''; ?>" required>
          </div>
          
          <div class="form-group">
            <label>Gender <span class="required">*</span></label>
            <div class="radio-group">
              <label class="radio-item">
                <input type="radio" name="gender" value="Male" <?php echo (isset($old['gender']) && $old['gender'] == 'Male') ? 'checked' : ''; ?> required>
                <span>Male</span>
              </label>
              <label class="radio-item">
                <input type="radio" name="gender" value="Female" <?php echo (isset($old['gender']) && $old['gender'] == 'Female') ? 'checked' : ''; ?> required>
                <span>Female</span>
              </label>
            </div>
          </div>
        </div>
        
        <div class="form-column">
          <h3 class="section-title"><i class="fas fa-graduation-cap"></i> Academic Information</h3>
          
          <div class="form-group">
            <label for="course">Course <span class="required">*</span></label>
            <select id="course" name="course_id" required>
              <option value="">-- Select Course --</option>
              <?php foreach($courses as $course): ?>
                <option value="<?php echo $course['id']; ?>" <?php echo (isset($old['course_id']) && $old['course_id'] == $course['id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($course['course']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="form-group">
            <label for="year_graduated">Batch Year <span class="required">*</span></label>
            <input type="number" id="year_graduated" name="batch" min="1980" max="<?php echo date('Y'); ?>" value="<?php echo isset($old['batch']) ? htmlspecialchars($old['batch']) : ''; ?>" required>
          </div>
          
          <div class="form-group">
            <label id="connectedLabel">Currently Connected to ? <span class="required">*</span></label>
            <div class="radio-group">
              <label class="radio-item">
                <input type="radio" name="connected_to" value="1" <?php echo (isset($old['connected_to']) && $old['connected_to'] == '1') ? 'checked' : ''; ?> required>
                <span>Yes</span>
              </label>
              <label class="radio-item">
                <input type="radio" name="connected_to" value="0" <?php echo (isset($old['connected_to']) && $old['connected_to'] == '0') ? 'checked' : ''; ?> required>
                <span>No</span>
              </label>
            </div>
          </div>
          
          <h3 class="section-title"><i class="fas fa-lock"></i> Account Information</h3>
          
          <div class="form-group">
            <label for="email">Email Address <span class="required">*</span></label>
            <input type="email" id="email" name="email" value="<?php echo isset($old['email']) ? htmlspecialchars($old['email']) : ''; ?>" required>
          </div>
          
          <div class="form-group">
            <label for="password">Password <span class="required">*</span></label>
            <div class="password-field">
              <input type="password" id="password" name="password" required>
              <i class="fas fa-eye toggle-password" data-target="password"></i>
            </div>
            <div class="password-strength" id="passwordStrength"></div>
          </div>
          
          <div class="form-group">
            <label for="confirm_password">Confirm Password <span class="required">*</span></label>
            <div class="password-field">
              <input type="password" id="confirm_password" name="confirm_password" required>
              <i class="fas fa-eye toggle-password" data-target="confirm_password"></i>
            </div>
          </div>
        </div>
      </div>
      
      <div class="form-footer">
        <div class="required-note"><span class="required">*</span> Required fields</div>
        
        <div class="form-buttons">
          <button type="submit" class="register-btn">
            <i class="fas fa-user-plus"></i> Create Account
          </button>
          <button type="reset" class="reset-btn">
            <i class="fas fa-redo"></i> Reset Form
          </button>
        </div>
      </div>
      
      <div class="login-link">
        Already have an account? <a href="login.php">Login</a>
      </div>
    </form>
  </div>
</section>

<script>
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
  
  document.querySelectorAll('.toggle-password').forEach(icon => {
    icon.addEventListener('click', function() {
      const targetId = this.getAttribute('data-target');
      const input = document.getElementById(targetId);
      
      if (input.type === 'password') {
        input.type = 'text';
        this.classList.replace('fa-eye', 'fa-eye-slash');
      } else {
        input.type = 'password';
        this.classList.replace('fa-eye-slash', 'fa-eye');
      }
    });
  });
  
  const courseSelect = document.getElementById('course');
  const connectedLabel = document.getElementById('connectedLabel');
  const defaultText = "Currently Connected to PLP?";
  
  courseSelect.addEventListener('change', function() {
    if (this.value === '') {
      connectedLabel.innerHTML = defaultText + ' <span class="required">*</span>';
      return;
    }
    
    const selectedOption = this.options[this.selectedIndex];
    const courseName = selectedOption.text;
    connectedLabel.innerHTML = `Currently Connected to ${courseName}? <span class="required">*</span>`;
  });
  
  if (courseSelect.value !== '') {
    const selectedOption = courseSelect.options[courseSelect.selectedIndex];
    const courseName = selectedOption.text;
    connectedLabel.innerHTML = `Currently Connected to ${courseName}? <span class="required">*</span>`;
  }
  
  const form = document.getElementById('registerForm');
  const password = document.getElementById('password');
  const confirmPassword = document.getElementById('confirm_password');
  const passwordStrength = document.getElementById('passwordStrength');
  
  password.addEventListener('input', function() {
    const value = this.value;
    let strength = 0;
    let message = '';
    
    if (value.length >= 8) strength += 1;
    if (value.match(/[a-z]+/)) strength += 1;
    if (value.match(/[A-Z]+/)) strength += 1;
    if (value.match(/[0-9]+/)) strength += 1;
    if (value.match(/[!@#$%^&*(),.?":{}|<>]+/)) strength += 1;
    
    passwordStrength.className = 'password-strength';
    
    switch (strength) {
      case 0:
        message = '';
        passwordStrength.classList.add('empty');
        break;
      case 1:
        message = 'Very weak';
        passwordStrength.classList.add('very-weak');
        break;
      case 2:
        message = 'Weak';
        passwordStrength.classList.add('weak');
        break;
      case 3:
        message = 'Medium';
        passwordStrength.classList.add('medium');
        break;
      case 4:
        message = 'Strong';
        passwordStrength.classList.add('strong');
        break;
      case 5:
        message = 'Very strong';
        passwordStrength.classList.add('very-strong');
        break;
    }
    
    passwordStrength.textContent = message;
  });
  
  form.addEventListener('submit', function(event) {
    if (password.value !== confirmPassword.value) {
      event.preventDefault();
      alert('Passwords do not match!');
      confirmPassword.focus();
    }
  });
</script>

</body>
</html>
