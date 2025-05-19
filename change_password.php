<?php
session_start();
include 'admin/db_connect.php';

if(!isset($_SESSION['require_password_change']) || !isset($_SESSION['temp_login_id'])) {
    header("Location: login.php");
    exit;
}

$error_msg = "";
$success_msg = "";

if(isset($_POST['update_password'])) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if($password !== $confirm_password) {
        $error_msg = "Passwords do not match.";
    } else {
        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $number    = preg_match('@[0-9]@', $password);
        $specialChars = preg_match('@[^\w]@', $password);
        
        if(!$uppercase || !$lowercase || !$number || !$specialChars || strlen($password) < 8) {
            $error_msg = "Password must be at least 8 characters long and include: uppercase letter, lowercase letter, number, and special character.";
        } else {
            $hashed_password = md5($password);
            $alumni_id = $_SESSION['temp_login_id'];
            
            $update_sql = "UPDATE users SET 
                          password = ?, 
                          is_default_password = 0 
                          WHERE alumni_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ss", $hashed_password, $alumni_id);
            
            if($update_stmt->execute()) {
                $verify_sql = "UPDATE alumnus_bio SET status = 1 WHERE alumni_id = ?";
                $verify_stmt = $conn->prepare($verify_sql);
                $verify_stmt->bind_param("s", $alumni_id);
                $verify_stmt->execute();
                
                unset($_SESSION['temp_login_id']);
                unset($_SESSION['temp_login_name']);
                unset($_SESSION['require_password_change']);
                
                $_SESSION['success'] = "Password updated successfully. Your account is now verified. Please log in with your new password.";
                header("Location: login.php");
                exit;
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
    <title>PLP Alumni - Change Password</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/login.css">
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
                        <h2 class="overlay-text">CHANGE PASSWORD</h2>
                        <p class="overlay-subtext">Please change your default password to continue</p>
                    </div>
                </div>
            </div>
            <div class="login-form-container">
                <div class="form-header">
                    <h2>Change Default Password</h2>
                    <p>For security reasons, you must change your default password before accessing your account.</p>
                </div>
                
                <?php if(!empty($error_msg)): ?>
                <div class="login-alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_msg; ?>
                </div>
                <?php endif; ?>

                <form class="login-form" method="POST">
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="Enter new password" required minlength="8">
                        </div>
                        <div class="password-requirements">
                            <p>Password must contain:</p>
                            <ul id="password-checklist">
                                <li id="length"><i class="fas fa-times"></i>8+ characters</li>
                                <li id="uppercase"><i class="fas fa-times"></i>Uppercase</li>
                                <li id="lowercase"><i class="fas fa-times"></i>Lowercase</li>
                                <li id="number"><i class="fas fa-times"></i>Number</li>
                                <li id="special"><i class="fas fa-times"></i>Special char</li>
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
            </div>
        </div>
    </section>

    <footer class="login-footer">
        <p>&copy; <?php echo date("Y"); ?> Pamantasan Ng Lungsod Ng Pasig - Alumni Portal</p>
    </footer>
</div>

<style>
    .password-requirements {
        margin-top: 8px;
        font-size: 0.75rem;
        color: #666;
    }
    .password-requirements p {
        margin-bottom: 3px;
        font-size: 0.75rem;
        color: #555;
    }
    #password-checklist {
        list-style: none;
        padding-left: 0;
        margin: 0;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    #password-checklist li {
        margin: 0;
        color: #dc3545;
        font-size: 0.75rem;
        display: flex;
        align-items: center;
    }
    #password-checklist li.valid {
        color: #28a745;
    }
    #password-checklist li i {
        margin-right: 3px;
        width: 12px;
        font-size: 0.75rem;
    }
</style>

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
            
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^A-Za-z0-9]/.test(password)
            };
            
            Object.keys(requirements).forEach(req => {
                const li = document.getElementById(req);
                if (li) {
                    const icon = li.querySelector('i');
                    if (requirements[req]) {
                        li.classList.add('valid');
                        icon.className = 'fas fa-check';
                    } else {
                        li.classList.remove('valid');
                        icon.className = 'fas fa-times';
                    }
                }
            });
            
            let strength = Object.values(requirements).filter(Boolean).length;
            updateStrengthMeter(strength);
        });
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
</script>

</body>
</html> 