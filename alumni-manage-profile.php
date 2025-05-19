<?php
session_start();
include 'admin/db_connect.php';

if(!isset($_SESSION['login_id'])) {
    header('location:login.php');
    exit;
}

$alumni_id = $_SESSION['login_id'];

$query = "SELECT a.*, c.course 
          FROM alumnus_bio a 
          LEFT JOIN courses c ON a.course_id = c.id
          WHERE a.alumni_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $alumni_id);
$stmt->execute();
$result = $stmt->get_result();
$alumnus = $result->fetch_assoc();

$user_query = "SELECT name, username FROM users WHERE alumni_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("s", $alumni_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();

if(empty($alumnus['email']) && isset($user_data['username'])) {
    $alumnus['email'] = $user_data['username'];
}

$display_name = $user_data['name'];

$course_query = "SELECT id, course FROM courses ORDER BY course ASC";
$course_result = $conn->query($course_query);
$courses = [];
while($row = $course_result->fetch_assoc()) {
    $courses[$row['id']] = $row['course'];
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname']);
    $middlename = trim($_POST['middlename']);
    $lastname = trim($_POST['lastname']);
    $gender = $_POST['gender'];
    $batch = $_POST['batch'];
    $course_id = $_POST['course_id'];
    $connected_to = $_POST['connected_to'];
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $current_company = trim($_POST['current_company']);
    $current_job_title = trim($_POST['current_job_title']);
    
    $errors = [];
    if(empty($firstname)) $errors[] = "First name is required";
    if(empty($lastname)) $errors[] = "Last name is required";
    if(empty($email)) $errors[] = "Email is required";
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    
    if(!empty($password)) {
        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $number    = preg_match('@[0-9]@', $password);
        $specialChars = preg_match('@[^\w]@', $password);
        
        if(!$uppercase || !$lowercase || !$number || !$specialChars || strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long and include: uppercase letter, lowercase letter, number, and special character.";
        }
    }
    
    if(empty($errors)) {
        $check_email = "SELECT alumni_id FROM alumnus_bio WHERE email = ? AND alumni_id != ?";
        $stmt_check = $conn->prepare($check_email);
        $stmt_check->bind_param("ss", $email, $alumni_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if($result_check->num_rows > 0) {
            $errors[] = "Email already exists for another alumni.";
        } else {
            $update_query = "UPDATE alumnus_bio SET 
                            firstname = ?, 
                            middlename = ?, 
                            lastname = ?, 
                            gender = ?, 
                            batch = ?, 
                            course_id = ?, 
                            email = ?, 
                            current_company = ?,
                            current_job_title = ?,
                            connected_to = ? 
                            WHERE alumni_id = ?";
            
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("sssssisssis", 
                $firstname,
                $middlename,
                $lastname,
                $gender,
                $batch,
                $course_id,
                $email,
                $current_company,
                $current_job_title,
                $connected_to,
                $alumni_id
            );
            
            if($stmt->execute()) {
                $fullname = $firstname . ' ' . $lastname;
                $update_user = "UPDATE users SET name = ?, username = ? WHERE alumni_id = ?";
                $stmt_user = $conn->prepare($update_user);
                $stmt_user->bind_param("sss", $fullname, $email, $alumni_id);
                $stmt_user->execute();
                
                if(!empty($password)) {
                    $hashed_password = md5($password);
                    $update_pass = "UPDATE users SET password = ? WHERE alumni_id = ?";
                    $stmt_pass = $conn->prepare($update_pass);
                    $stmt_pass->bind_param("ss", $hashed_password, $alumni_id);
                    $stmt_pass->execute();
                }
                
                $_SESSION['success'] = "Profile updated successfully!";
                header("Location: alumni-manage-profile.php");
                exit;
            } else {
                $errors[] = "Failed to update profile: " . $conn->error;
            }
        }
    }
}

$avatar = 'images/avatar.png';

if(!empty($alumnus['avatar']) && file_exists($alumnus['avatar'])) {
    $avatar = $alumnus['avatar'];
}

?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Manage Profile - Alumni Portal</title>
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="alumni-profile.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    </head>
    <body>
        <div class="interface-header">
            <img src="images/logo.png" alt="PLP Logo" class="logo-interface">
            <div class="text">
                <div class="school-name">Pamantasan Ng Lungsod Ng Pasig</div>
                <div class="alumni-title">ALUMNI</div>
            </div>
        </div>

        <div class="manage-profile">
            <div class="sidebar" id="sidebar">
                <div class="toggle-btn" onclick="toggleSidebar()">&#x25C0;</div>

                <div class="sidebar-content">
                    <div class="profile-section">
                        <a href="alumni-manage-profile.php" class="profile-pic">
                            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Profile Picture">
                        </a>
                        <div class="profile-name"><?php echo htmlspecialchars($display_name); ?></div>
                    </div>

                    <a href="alumni-home.php"><img src="images/home.png" alt="Home"><span>Home</span></a>
                    <a href="alumni-gallery.php"><img src="images/gallery.png" alt="Gallery"><span>Gallery</span></a>
                    <a href="alumni-list.php"><img src="images/alumni_list.png" alt="Alumni List"><span>Alumni List</span></a>
                    <a href="alumni-job.php"><img src="images/jobs.png" alt="Jobs"><span>Jobs</span></a>
                    <a href="alumni-forums.php"><img src="images/forums.png" alt="Forums"><span>Forums</span></a>
                    <a href="alumni-about.php"><img src="images/about.png" alt="About"><span>About</span></a>
                    <a href="landing.php"><img src="images/log-out.png" alt="Log Out"><span>Log Out</span></a>
                </div>
            </div>

            <div class="main-content">
                <div class="page-header">
                    <h1><i class="fas fa-user-edit"></i> Account Details</h1>
                    <p class="subtitle">Manage your personal information and account settings</p>
                </div>
                <hr class="header-divider">
                
                <?php if(isset($_SESSION['success'])): ?>
                    <div class="alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['error'])): ?>
                    <div class="alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($errors) && !empty($errors)): ?>
                    <div class="alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <ul style="margin: 0; padding-left: 20px;">
                            <?php foreach($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="profile-container">
                    <div class="profile-sidebar">
                        <div class="profile-picture-card">
                            <h3><i class="fas fa-camera"></i> Profile Photo</h3>
                            <div class="pfp">
                                <img src="<?php echo htmlspecialchars($avatar); ?>" class="pfp-picture">
                                <button type="button" class="change-pfp" id="openProfilePictureModal">
                                    <i class="fas fa-camera"></i> Change Picture
                                </button>
                            </div>
                            <p class="profile-hint">Upload a clear photo of yourself</p>
                        </div>
                    </div>
                    
                    <div class="profile-main">
                        <form class="profile-form" method="POST" action="alumni-manage-profile.php">
                            <div class="form-card">
                                <h3 class="form-section-title"><i class="fas fa-user"></i> Personal Information</h3>
                                <div class="form-section">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="firstname">First Name</label>
                                            <input type="text" name="firstname" id="firstname" value="<?php echo htmlspecialchars($alumnus['firstname']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="middlename">Middle Name</label>
                                            <input type="text" name="middlename" id="middlename" value="<?php echo htmlspecialchars($alumnus['middlename']); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="lastname">Last Name</label>
                                            <input type="text" name="lastname" id="lastname" value="<?php echo htmlspecialchars($alumnus['lastname']); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Gender</label>
                                            <div class="radio-group">
                                                <label class="radio-item">
                                                    <input type="radio" name="gender" value="Male" <?php echo ($alumnus['gender'] === 'Male') ? 'checked' : ''; ?> required>
                                                    <span class="radio-label">Male</span>
                                                </label>
                                                <label class="radio-item">
                                                    <input type="radio" name="gender" value="Female" <?php echo ($alumnus['gender'] === 'Female') ? 'checked' : ''; ?> required>
                                                    <span class="radio-label">Female</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-card">
                                <h3 class="form-section-title"><i class="fas fa-graduation-cap"></i> Academic Information</h3>
                                <div class="form-section">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="batch">Batch Year</label>
                                            <input type="number" name="batch" id="batch" min="1980" max="<?php echo date('Y'); ?>" value="<?php echo htmlspecialchars($alumnus['batch']); ?>" required>
                                        </div>
                                        <div class="form-group wider">
                                            <label for="course_id">Course Graduated</label>
                                            <select name="course_id" id="course_id" required>
                                                <option value="">Select Course</option>
                                                <?php foreach($courses as $id => $course): ?>
                                                <option value="<?php echo $id; ?>" <?php echo ($alumnus['course_id'] == $id) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($course); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Currently Connected to PLP</label>
                                            <div class="radio-group">
                                                <label class="radio-item">
                                                    <input type="radio" name="connected_to" value="1" <?php echo ($alumnus['connected_to'] == 1) ? 'checked' : ''; ?> required>
                                                    <span class="radio-label">Yes</span>
                                                </label>
                                                <label class="radio-item">
                                                    <input type="radio" name="connected_to" value="0" <?php echo ($alumnus['connected_to'] == 0) ? 'checked' : ''; ?> required>
                                                    <span class="radio-label">No</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-card">
                                <h3 class="form-section-title"><i class="fas fa-briefcase"></i> Employment Information</h3>
                                <div class="form-section">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="current_company">Current Company/Organization</label>
                                            <input type="text" name="current_company" id="current_company" value="<?php echo isset($alumnus['current_company']) ? htmlspecialchars($alumnus['current_company']) : ''; ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="current_job_title">Current Job Title/Position</label>
                                            <input type="text" name="current_job_title" id="current_job_title" value="<?php echo isset($alumnus['current_job_title']) ? htmlspecialchars($alumnus['current_job_title']) : ''; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-card">
                                <h3 class="form-section-title"><i class="fas fa-lock"></i> Account Information</h3>
                                <div class="form-section">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="email">Email Address</label>
                                            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($alumnus['email']); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="password">New Password</label>
                                            <input type="password" name="password" id="password" placeholder="Leave blank to keep current password">
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
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-buttons">
                                <button type="submit" class="update-btn">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div id="profilePictureModal" class="alumni-modal">
            <div class="alumni-job-modal-content">
                <span class="close-button" id="closeProfilePictureModal">&times;</span>
                <h2>Update Profile Picture</h2>
                <form id="profilePictureForm" method="POST" action="upload_profile_picture.php" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="profileImage">Select Image (JPG, PNG or GIF):</label><br />
                        <input type="file" id="profileImage" name="profileImage" accept="image/jpeg, image/png" required /><br /><br />
                        <div id="imagePreview" class="preview-image" style="display: none;">
                            <p>Preview:</p>
                            <img id="previewImg" src="#" alt="Preview" />
                        </div>
                    </div>
                    <button type="submit" class="save-button">
                        <i class="fas fa-upload"></i> Upload Picture
                    </button>
                </form>
            </div>
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
            function toggleSidebar() {
                const sidebar = document.getElementById('sidebar');
                sidebar.classList.toggle('collapsed');
                const toggleBtn = document.querySelector('.toggle-btn');
                toggleBtn.innerHTML = sidebar.classList.contains('collapsed') ? '&#x25B6;' : '&#x25C0;';
            }

            const profilePictureModal = document.getElementById('profilePictureModal');
            const openProfilePictureBtn = document.getElementById('openProfilePictureModal');
            const closeProfilePictureBtn = document.getElementById('closeProfilePictureModal');
            const profileImageInput = document.getElementById('profileImage');
            const imagePreview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');

            openProfilePictureBtn.addEventListener('click', function() {
                profilePictureModal.style.display = 'block';
            });

            closeProfilePictureBtn.addEventListener('click', function() {
                profilePictureModal.style.display = 'none';
            });

            window.addEventListener('click', function(event) {
                if (event.target === profilePictureModal) {
                    profilePictureModal.style.display = 'none';
                }
            });

            profileImageInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        imagePreview.style.display = 'block';
                    }
                    reader.readAsDataURL(file);
                } else {
                    imagePreview.style.display = 'none';
                }
            });

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
                });
            }
        </script>
    </body>
</html>