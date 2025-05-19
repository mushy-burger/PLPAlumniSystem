<?php
session_start();
include 'admin/db_connect.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid alumni ID.";
    header("Location: admin-alumni-list.php");
    exit;
}

$alumni_id = $_GET['id'];

$courses_query = "SELECT id, course FROM courses ORDER BY course ASC";
$courses_result = $conn->query($courses_query);
$courses = [];
while ($course_row = $courses_result->fetch_assoc()) {
    $courses[$course_row['id']] = $course_row['course'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname']);
    $middlename = trim($_POST['middlename']);
    $lastname = trim($_POST['lastname']);
    $gender = $_POST['gender'];
    $batch = $_POST['batch'];
    $course_id = (int)$_POST['course_id'];
    $email = trim($_POST['email']);
    $connected_to = (int)$_POST['connected_to'];
    $status = (int)$_POST['status'];
    $current_company = trim($_POST['current_company']);
    $current_job_title = trim($_POST['current_job_title']);
    
    $errors = [];
    if (empty($firstname)) $errors[] = "First name is required.";
    if (empty($lastname)) $errors[] = "Last name is required.";
    if (empty($email)) $errors[] = "Email is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    
    if (empty($errors)) {
        $check_email = "SELECT alumni_id FROM alumnus_bio WHERE email = ? AND alumni_id != ?";
        $stmt_check = $conn->prepare($check_email);
        $stmt_check->bind_param("ss", $email, $alumni_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $errors[] = "Email already exists for another alumni.";
        } else {
            $avatar = '';
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $target_dir = "uploads/alumni/";
                
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (!in_array($file_extension, $allowed_extensions)) {
                    $errors[] = "Only JPG, JPEG, PNG, and GIF files are allowed for avatar.";
                } else {
                    $new_filename = $alumni_id . '_' . time() . '.' . $file_extension;
                    $target_file = $target_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
                        $avatar = $target_file;
                    } else {
                        $errors[] = "Failed to upload avatar.";
                    }
                }
            }
            
            if (empty($errors)) {
                if (!empty($avatar)) {
                    $update_query = "UPDATE alumnus_bio SET 
                                    firstname = ?, 
                                    middlename = ?, 
                                    lastname = ?, 
                                    gender = ?, 
                                    batch = ?, 
                                    course_id = ?, 
                                    email = ?, 
                                    connected_to = ?, 
                                    avatar = ?, 
                                    status = ?,
                                    current_company = ?,
                                    current_job_title = ?
                                    WHERE alumni_id = ?";
                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param("ssssiisssssss", $firstname, $middlename, $lastname, $gender, 
                                    $batch, $course_id, $email, $connected_to, $avatar, $status,
                                    $current_company, $current_job_title, $alumni_id);
                } else {
                    $update_query = "UPDATE alumnus_bio SET 
                                    firstname = ?, 
                                    middlename = ?, 
                                    lastname = ?, 
                                    gender = ?, 
                                    batch = ?, 
                                    course_id = ?, 
                                    email = ?, 
                                    connected_to = ?, 
                                    status = ?,
                                    current_company = ?,
                                    current_job_title = ?
                                    WHERE alumni_id = ?";
                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param("ssssiisisss", $firstname, $middlename, $lastname, $gender, 
                                    $batch, $course_id, $email, $connected_to, $status,
                                    $current_company, $current_job_title, $alumni_id);
                }
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Alumni record updated successfully.";
                    header("Location: view_alumni.php?id=" . $alumni_id);
                    exit;
                } else {
                    $errors[] = "Failed to update alumni record: " . $conn->error;
                }
            }
        }
    }
}

$query = "SELECT * FROM alumnus_bio WHERE alumni_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $alumni_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Alumni record not found.";
    header("Location: admin-alumni-list.php");
    exit;
}

$alumni = $result->fetch_assoc();

$course_query = "SELECT course FROM courses WHERE id = ?";
$course_stmt = $conn->prepare($course_query);
$course_stmt->bind_param("i", $alumni['course_id']);
$course_stmt->execute();
$course_result = $course_stmt->get_result();
$course_data = $course_result->fetch_assoc();
$course_name = $course_data ? $course_data['course'] : 'Unknown Course';

$current_year = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Alumni - <?php echo htmlspecialchars($alumni['firstname'] . ' ' . $alumni['lastname']); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin-alumni-list.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .edit-alumni-container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .edit-header {
            display: flex;
            align-items: center;
            padding: 30px;
            background: linear-gradient(135deg, #3a7bd5 0%, #00d2ff 100%);
            color: white;
            position: relative;
            border-radius: 10px 10px 0 0;
        }
        
        .edit-header-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.1;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1MCIgaGVpZ2h0PSI1MCIgdmlld0JveD0iMCAwIDUwIDUwIj48ZyBmaWxsPSJub25lIiBzdHJva2U9IiNmZmYiIHN0cm9rZS13aWR0aD0iMSI+PHBhdGggZD0iTTUgNWw0MCA0ME01IDQ1bDQwLTQwIj48L3BhdGg+PC9nPjwvc3ZnPg==');
        }
        
        .edit-title {
            position: relative;
            z-index: 1;
        }
        
        .edit-title h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .edit-title p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .alumni-preview {
            display: flex;
            align-items: center;
            margin-left: auto;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 10px 15px;
            border-radius: 8px;
            backdrop-filter: blur(5px);
            position: relative;
            z-index: 1;
        }
        
        .alumni-preview img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
            margin-right: 15px;
        }
        
        .alumni-preview-info {
            line-height: 1.3;
        }
        
        .alumni-preview-info .name {
            font-weight: 600;
            font-size: 16px;
        }
        
        .alumni-preview-info .id {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .edit-tabs {
            display: flex;
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            position: relative;
            z-index: 10;
        }
        
        .edit-tab {
            padding: 15px 20px;
            font-size: 15px;
            font-weight: 600;
            color: #495057;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }
        
        .edit-tab i {
            margin-right: 8px;
        }
        
        .edit-tab:hover {
            color: #3a7bd5;
            background-color: #f1f3f5;
        }
        
        .edit-tab.active {
            color: #3a7bd5;
            border-bottom-color: #3a7bd5;
            background-color: #fff;
        }
        
        .edit-content {
            padding: 30px;
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .edit-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            display: flex;
            align-items: center;
            font-size: 18px;
            font-weight: 600;
            color: #3a7bd5;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .section-title i {
            margin-right: 10px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-grid .form-group {
            margin-bottom: 0;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-label.required::after {
            content: "*";
            color: #dc3545;
            margin-left: 5px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 15px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .form-control:focus {
            border-color: #3a7bd5;
            box-shadow: 0 0 0 3px rgba(58, 123, 213, 0.15);
            outline: none;
        }
        
        .form-control.is-invalid {
            border-color: #dc3545;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px 20px;
            padding-right: 40px;
        }
        
        .invalid-feedback {
            display: none;
            color: #dc3545;
            font-size: 13px;
            margin-top: 5px;
        }
        
        .form-control.is-invalid + .invalid-feedback {
            display: block;
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
            padding: 5px 0;
        }
        
        .radio-item {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .radio-item input {
            margin-right: 8px;
            cursor: pointer;
        }
        
        .avatar-upload {
            position: relative;
            max-width: 300px;
            margin-bottom: 20px;
        }
        
        .avatar-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            margin-bottom: 15px;
            border: 5px solid #f0f0f0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        
        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .avatar-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            opacity: 0;
            transition: opacity 0.3s;
            cursor: pointer;
        }
        
        .avatar-preview:hover .avatar-overlay {
            opacity: 1;
        }
        
        .avatar-overlay i {
            font-size: 24px;
        }
        
        .avatar-upload .file-input {
            display: none;
        }
        
        .avatar-buttons {
            display: flex;
            gap: 10px;
        }
        
        .upload-btn, .remove-btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }
        
        .upload-btn {
            background-color: #3a7bd5;
            color: white;
        }
        
        .upload-btn:hover {
            background-color: #2d62aa;
        }
        
        .remove-btn {
            background-color: #f1f3f5;
            color: #495057;
        }
        
        .remove-btn:hover {
            background-color: #e9ecef;
            color: #c82333;
        }
        
        .avatar-buttons i {
            margin-right: 6px;
        }
        
        .actions-container {
            background-color: #f8f9fa;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #dee2e6;
            border-radius: 0 0 10px 10px;
        }
        
        .action-note {
            color: #6c757d;
            font-size: 14px;
        }
        
        .action-note i {
            margin-right: 5px;
            color: #ffc107;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border-radius: 5px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            text-decoration: none;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn-primary {
            background-color: #3a7bd5;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2d62aa;
            box-shadow: 0 4px 10px rgba(58, 123, 213, 0.3);
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
            box-shadow: 0 4px 10px rgba(108, 117, 125, 0.3);
        }
        
        .btn-back {
            display: inline-flex;
            align-items: center;
            color: #6c757d;
            font-weight: 500;
            padding: 5px 0;
            margin-bottom: 15px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-back i {
            margin-right: 5px;
        }
        
        .btn-back:hover {
            color: #3a7bd5;
            transform: translateX(-3px);
        }
        
        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 15px;
            display: flex;
            align-items: flex-start;
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 18px;
            margin-top: 2px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .alert-danger ul {
            margin: 8px 0 0 25px;
            padding: 0;
        }
        
        .alert-danger li {
            margin-bottom: 5px;
        }
        
        .alert-danger li:last-child {
            margin-bottom: 0;
        }
        
        @media (max-width: 768px) {
            .edit-header {
                flex-direction: column;
                text-align: center;
            }
            
            .alumni-preview {
                margin: 20px 0 0 0;
                width: 100%;
                justify-content: center;
            }
            
            .edit-tabs {
                overflow-x: auto;
                white-space: nowrap;
            }
            
            .edit-tab {
                padding: 12px 15px;
                font-size: 14px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .actions-container {
                flex-direction: column;
                gap: 15px;
            }
            
            .action-buttons {
                width: 100%;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <div class="interface-header">
        <img src="images/logo.png" alt="PLP Logo" class="logo-interface">
        <div class="text">
            <div class="school-name">Pamantasan Ng Lungsod Ng Pasig</div>
            <p class="p-size">Alkade Jose St. Kapasigan, Pasig City</p>
        </div>
    </div>

    <div id="al-content">
        <div class="sidebar" id="sidebar">
            <div class="toggle-btn" onclick="toggleSidebar()">&#x25C0;</div>

            <div class="sidebar-content">
                <div class="profile-section">
                    <a href="profile.html" class="profile-pic">
                        <img src="images/avatar.png" alt="Profile Picture">
                    </a>
                    <div class="profile-name">ADMIN</div>
                </div>

                <a href="admin-home.php"><img src="images/home.png" alt="Home"><span>Home</span></a>
                <a href="admin-gallery.php"><img src="images/gallery.png" alt="Gallery"><span>Gallery</span></a>
                <a href="admin-course-list.php"><img src="images/course-list.png" alt="Course List"><span>Course List</span></a>
                <a href="admin-alumni-list.php" class="active"><img src="images/alumni_list.png" alt="Alumni List"><span>Alumni List</span></a>
                <a href="admin-alumni-upload.php"><img src="images/upload.png" alt="Alumni Upload"><span>Alumni Upload</span></a>
                <a href="admin-job.php"><img src="images/jobs.png" alt="Jobs"><span>Jobs</span></a>
                <a href="admin-event.php"><img src="images/calendar.png" alt="Events"><span>Events</span></a>
                <a href="admin-forums.php"><img src="images/forums.png" alt="Forum"><span>Forum</span></a>
                <a href="admin-system-setting.php"><img src="images/settings.png" alt="System Settings"><span>System Settings</span></a>
                <a href="landing.php"><img src="images/log-out.png" alt="Log Out"><span>Log Out</span></a>
            </div>
        </div>

        <div class="al-main-content">
            <a href="view_alumni.php?id=<?php echo $alumni_id; ?>" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Alumni Profile
            </a>
            
            <?php if(isset($errors) && !empty($errors)): ?>
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
            
            <div class="edit-alumni-container">
                <div class="edit-header">
                    <div class="edit-header-pattern"></div>
                    
                    <div class="edit-title">
                        <h2>Edit Alumni Profile</h2>
                        <p>Update information for Alumni ID: <?php echo htmlspecialchars($alumni['alumni_id']); ?></p>
                    </div>
                    
                    <div class="alumni-preview">
                        <img src="<?php echo !empty($alumni['avatar']) ? htmlspecialchars($alumni['avatar']) : 'images/avatar.png'; ?>" alt="Alumni">
                        <div class="alumni-preview-info">
                            <div class="name"><?php echo htmlspecialchars($alumni['firstname'] . ' ' . $alumni['lastname']); ?></div>
                            <div class="id"><?php echo htmlspecialchars($alumni_id); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="edit-tabs">
                    <div class="edit-tab active" data-tab="personal">
                        <i class="fas fa-user"></i> Personal Information
                    </div>
                    <div class="edit-tab" data-tab="academic">
                        <i class="fas fa-graduation-cap"></i> Academic
                    </div>
                    <div class="edit-tab" data-tab="account">
                        <i class="fas fa-shield-alt"></i> Account Status
                    </div>
                </div>
                
                <form method="POST" enctype="multipart/form-data" id="editAlumniForm" novalidate>
                    <!-- Personal Information Tab -->
                    <div id="personal-tab" class="edit-content active">
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-address-card"></i> Personal Details
                            </h3>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="firstname" class="form-label required">First Name</label>
                                    <input type="text" id="firstname" name="firstname" class="form-control" 
                                        value="<?php echo htmlspecialchars($alumni['firstname']); ?>" required>
                                    <div class="invalid-feedback">First name is required</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="middlename" class="form-label">Middle Name</label>
                                    <input type="text" id="middlename" name="middlename" class="form-control" 
                                        value="<?php echo htmlspecialchars($alumni['middlename']); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="lastname" class="form-label required">Last Name</label>
                                    <input type="text" id="lastname" name="lastname" class="form-control" 
                                        value="<?php echo htmlspecialchars($alumni['lastname']); ?>" required>
                                    <div class="invalid-feedback">Last name is required</div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label required">Gender</label>
                                <div class="radio-group">
                                    <label class="radio-item">
                                        <input type="radio" name="gender" value="Male" 
                                            <?php echo $alumni['gender'] === 'Male' ? 'checked' : ''; ?> required>
                                        Male
                                    </label>
                                    <label class="radio-item">
                                        <input type="radio" name="gender" value="Female" 
                                            <?php echo $alumni['gender'] === 'Female' ? 'checked' : ''; ?> required>
                                        Female
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="form-label required">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                    value="<?php echo htmlspecialchars($alumni['email']); ?>" required>
                                <div class="invalid-feedback">Please enter a valid email address</div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-camera"></i> Profile Picture
                            </h3>
                            
                            <div class="avatar-upload">
                                <div class="avatar-preview">
                                    <img id="avatarPreview" src="<?php echo !empty($alumni['avatar']) ? htmlspecialchars($alumni['avatar']) : 'images/avatar.png'; ?>" alt="Avatar Preview">
                                    <div class="avatar-overlay" id="avatarOverlay">
                                        <i class="fas fa-camera"></i>
                                    </div>
                                </div>
                                
                                <input type="file" id="avatar" name="avatar" class="file-input" accept="image/jpeg, image/png">
                                
                                <div class="avatar-buttons">
                                    <button type="button" class="upload-btn" id="uploadBtn">
                                        <i class="fas fa-upload"></i> Upload Image
                                    </button>
                                    <button type="button" class="remove-btn" id="removeBtn" <?php echo empty($alumni['avatar']) ? 'style="display:none"' : ''; ?>>
                                        <i class="fas fa-trash-alt"></i> Remove
                                    </button>
                                </div>
                                <p class="action-note" style="margin-top: 10px; font-size: 13px;">
                                    <i class="fas fa-info-circle"></i> Allowed formats: JPG, JPEG, PNG, GIF. Max size: 2MB
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div id="academic-tab" class="edit-content">
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-university"></i> Academic Information
                            </h3>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="course_id" class="form-label required">Course/Degree</label>
                                    <select id="course_id" name="course_id" class="form-control" required>
                                        <option value="">Select Course</option>
                                        <?php foreach($courses as $id => $course): ?>
                                            <option value="<?php echo $id; ?>" <?php echo $alumni['course_id'] == $id ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($course); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Please select a course</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="batch" class="form-label required">Batch/Year Graduated</label>
                                    <input type="number" id="batch" name="batch" class="form-control" 
                                        value="<?php echo htmlspecialchars($alumni['batch']); ?>" 
                                        min="1980" max="<?php echo $current_year; ?>" required>
                                    <div class="invalid-feedback">Please enter a valid graduation year (1980-<?php echo $current_year; ?>)</div>
                                </div>
                            </div>
                            
                            <div class="form-note" style="margin-top: 20px; color: #6c757d; font-size: 14px;">
                                <i class="fas fa-info-circle"></i> The course information is used to categorize alumni and provide relevant updates.
                            </div>
                        </div>
                    </div>
                    
                    <div id="account-tab" class="edit-content">
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-user-shield"></i> Account Status
                            </h3>
                            
                            <div class="form-group">
                                <label class="form-label">Verification Status</label>
                                <p class="form-text" style="margin-bottom: 10px; color: #6c757d; font-size: 14px;">
                                    Verified alumni have confirmed their identity and information.
                                </p>
                                <div class="radio-group">
                                    <label class="radio-item">
                                        <input type="radio" name="status" value="1" 
                                            <?php echo $alumni['status'] == 1 ? 'checked' : ''; ?>>
                                        <span style="color: #28a745; font-weight: 600;">Verified</span>
                                    </label>
                                    <label class="radio-item">
                                        <input type="radio" name="status" value="0" 
                                            <?php echo $alumni['status'] == 0 ? 'checked' : ''; ?>>
                                        <span style="color: #dc3545; font-weight: 600;">Unverified</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-group" style="margin-top: 25px;">
                                <label class="form-label">Connection Status</label>
                                <p class="form-text" style="margin-bottom: 10px; color: #6c757d; font-size: 14px;">
                                    Indicates whether the alumni is currently connected with the institution.
                                </p>
                                <div class="radio-group">
                                    <label class="radio-item">
                                        <input type="radio" name="connected_to" value="1" 
                                            <?php echo $alumni['connected_to'] == 1 ? 'checked' : ''; ?>>
                                        <span style="color: #17a2b8; font-weight: 600;">Connected</span>
                                    </label>
                                    <label class="radio-item">
                                        <input type="radio" name="connected_to" value="0" 
                                            <?php echo $alumni['connected_to'] == 0 ? 'checked' : ''; ?>>
                                        <span style="color: #6c757d; font-weight: 600;">Not Connected</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="section-title"><i class="fas fa-briefcase"></i> Employment Information</h3>
                        
                        <div class="form-group">
                            <label for="current_company">Current Company/Organization:</label>
                            <input type="text" id="current_company" name="current_company" class="form-control" 
                                   value="<?php echo htmlspecialchars($alumni['current_company'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="current_job_title">Current Job Title/Position:</label>
                            <input type="text" id="current_job_title" name="current_job_title" class="form-control" 
                                   value="<?php echo htmlspecialchars($alumni['current_job_title'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="actions-container">
                        <div class="action-note">
                            <i class="fas fa-exclamation-triangle"></i> Fields marked with * are required
                        </div>
                        
                        <div class="action-buttons">
                            <a href="view_alumni.php?id=<?php echo $alumni_id; ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            const toggleBtn = document.querySelector('.toggle-btn');
            toggleBtn.innerHTML = sidebar.classList.contains('collapsed') ? '&#x25B6;' : '&#x25C0;';
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.edit-tab');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    document.querySelectorAll('.edit-tab').forEach(t => t.classList.remove('active'));
                    document.querySelectorAll('.edit-content').forEach(c => c.classList.remove('active'));
                    
                    this.classList.add('active');
                    
                    const tabId = this.getAttribute('data-tab') + '-tab';
                    document.getElementById(tabId).classList.add('active');
                });
            });
            
            const avatarInput = document.getElementById('avatar');
            const avatarPreview = document.getElementById('avatarPreview');
            const uploadBtn = document.getElementById('uploadBtn');
            const removeBtn = document.getElementById('removeBtn');
            const avatarOverlay = document.getElementById('avatarOverlay');
            
            uploadBtn.addEventListener('click', function() {
                avatarInput.click();
            });
            
            avatarOverlay.addEventListener('click', function() {
                avatarInput.click();
            });
            
            avatarInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    
                    if (file.size > 2 * 1024 * 1024) {
                        alert('File size exceeds 2MB. Please choose a smaller image.');
                        this.value = '';
                        return;
                    }
                    
                    const fileType = file.type;
                    if (!fileType.match('image/jpeg') && !fileType.match('image/png') && !fileType.match('image/gif')) {
                        alert('Please select a valid image file (JPG, JPEG, PNG, GIF).');
                        this.value = '';
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        avatarPreview.src = e.target.result;
                        removeBtn.style.display = 'flex';
                    }
                    reader.readAsDataURL(file);
                }
            });
            
            removeBtn.addEventListener('click', function() {
                avatarInput.value = '';
                avatarPreview.src = 'images/avatar.png';
                this.style.display = 'none';
            });
            
            const form = document.getElementById('editAlumniForm');
            
            form.addEventListener('submit', function(event) {
                let isValid = true;
                
                const requiredFields = form.querySelectorAll('[required]');
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('is-invalid');
                        isValid = false;
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });
                
                const emailField = document.getElementById('email');
                if (emailField.value.trim()) {
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailPattern.test(emailField.value)) {
                        emailField.classList.add('is-invalid');
                        isValid = false;
                    }
                }
                
                const batchField = document.getElementById('batch');
                if (batchField.value.trim()) {
                    const year = parseInt(batchField.value);
                    if (isNaN(year) || year < 1980 || year > <?php echo $current_year; ?>) {
                        batchField.classList.add('is-invalid');
                        isValid = false;
                    }
                }
                
                if (!isValid) {
                    event.preventDefault();
                    
                    const errorField = form.querySelector('.is-invalid');
                    if (errorField) {
                        const tabContent = errorField.closest('.edit-content');
                        if (tabContent) {
                            const tabId = tabContent.id.replace('-tab', '');
                            const tab = document.querySelector(`[data-tab="${tabId}"]`);
                            if (tab) {
                                tab.click();
                                errorField.focus();
                            }
                        }
                    }
                }
            });
            
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                field.addEventListener('blur', function() {
                    if (!this.value.trim()) {
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-invalid');
                    }
                });
                
                field.addEventListener('input', function() {
                    if (this.value.trim()) {
                        this.classList.remove('is-invalid');
                    }
                });
            });
            
            const emailField = document.getElementById('email');
            emailField.addEventListener('blur', function() {
                if (this.value.trim()) {
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailPattern.test(this.value)) {
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-invalid');
                    }
                }
            });
        });
    </script>
</body>
</html>
