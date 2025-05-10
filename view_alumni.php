<?php
session_start();
include 'admin/db_connect.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid alumni ID.";
    header("Location: admin-alumni-list.php");
    exit;
}

$alumni_id = $_GET['id'];

$query = "SELECT a.*, c.course as course_name 
          FROM alumnus_bio a 
          LEFT JOIN courses c ON a.course_id = c.id 
          WHERE a.alumni_id = ?";

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

$formatted_date = date('F d, Y', strtotime($alumni['date_created']));

$user_query = "SELECT * FROM users WHERE alumni_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("s", $alumni_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$has_account = $user_result->num_rows > 0;
$user_data = $has_account ? $user_result->fetch_assoc() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($alumni['firstname'] . ' ' . $alumni['lastname']); ?> - Alumni Profile</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin-alumni-list.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .alumni-profile-container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .profile-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            padding: 30px;
            background: linear-gradient(135deg, #003893 0%, #0056b3 100%);
            color: white;
            position: relative;
        }
        
        .profile-header-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.1;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1MCIgaGVpZ2h0PSI1MCIgdmlld0JveD0iMCAwIDUwIDUwIj48ZyBmaWxsPSJub25lIiBzdHJva2U9IiNmZmYiIHN0cm9rZS13aWR0aD0iMSI+PHBhdGggZD0iTTAgMGwxMCAxME0xMCAwTDAgMTBNNDAgMGwxMCAxME01MCAwTDQwIDEwTTAgNDBsMTAgMTBNMTAgNDBMMCA1ME00MCA0MGwxMCAxME01MCA0MEw0MCA1ME0wIDIwbDEwIDEwTTEwIDIwTDAgMzBNNDAgMjBsMTAgMTBNNTAgMjBMNDAgMzBNMjAgMGwxMCAxME0zMCAwTDIwIDEwTTIwIDQwbDEwIDEwTTMwIDQwTDIwIDUwTTIwIDIwbDEwIDEwTTMwIDIwTDIwIDMwIj48L3BhdGg+PC9nPjwvc3ZnPg==');
        }
        
        .profile-avatar-container {
            position: relative;
            margin-right: 30px;
        }
        
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            background-color: white;
            position: relative;
            z-index: 1;
        }
        
        .status-indicator {
            position: absolute;
            bottom: 10px;
            right: 5px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid white;
            background-color: <?php echo $alumni['status'] == 1 ? '#28a745' : '#dc3545'; ?>;
            color: white;
            font-size: 16px;
            z-index: 2;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        .profile-info {
            flex: 1;
            min-width: 200px;
            position: relative;
            z-index: 1;
        }
        
        .profile-name {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .alumni-id-badge {
            display: inline-block;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-bottom: 15px;
            backdrop-filter: blur(5px);
        }
        
        .alumni-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }
        
        .alumni-meta-item {
            display: flex;
            align-items: center;
            font-size: 14px;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 5px 10px;
            border-radius: 4px;
        }
        
        .alumni-meta-item i {
            margin-right: 8px;
            font-size: 16px;
        }
        
        .profile-actions {
            margin-left: auto;
            display: flex;
            gap: 10px;
            align-self: flex-start;
            position: relative;
            z-index: 1;
        }
        
        .profile-tabs {
            display: flex;
            border-bottom: 1px solid #dee2e6;
            background-color: white;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .tab-item {
            padding: 15px 20px;
            font-weight: 600;
            color: #495057;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }
        
        .tab-item i {
            margin-right: 8px;
        }
        
        .tab-item:hover {
            color: #003893;
            border-bottom-color: #adb5bd;
        }
        
        .tab-item.active {
            color: #003893;
            border-bottom-color: #003893;
        }
        
        .tab-content {
            display: none;
            padding: 30px;
            background-color: white;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .tab-content.active {
            display: block;
        }
        
        .content-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            display: flex;
            align-items: center;
            font-size: 20px;
            font-weight: 600;
            color: #003893;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .section-title i {
            margin-right: 10px;
            color: #003893;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .info-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            border-left: 4px solid #007bff;
        }
        
        .info-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .info-label {
            display: block;
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 500;
            color: #212529;
            word-break: break-word;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            line-height: 1;
        }
        
        .badge i {
            margin-right: 5px;
        }
        
        .badge-verified {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .badge-unverified {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .badge-connected {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .badge-not-connected {
            background-color: #e2e3e5;
            color: #383d41;
            border: 1px solid #d6d8db;
        }
        
        .account-info {
            background-color: #e9f7fe;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            border-left: 4px solid #17a2b8;
        }
        
        .account-info-title {
            display: flex;
            align-items: center;
            color: #17a2b8;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .account-info-title i {
            margin-right: 8px;
        }
        
        .no-account-state {
            text-align: center;
            padding: 40px 20px;
        }
        
        .no-account-state i {
            font-size: 48px;
            color: #adb5bd;
            margin-bottom: 15px;
        }
        
        .no-account-state p {
            font-size: 16px;
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }
        
        .btn i {
            margin-right: 6px;
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #0069d9;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
        }
        
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background-color: #e0a800;
            box-shadow: 0 4px 10px rgba(255, 193, 7, 0.3);
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
            box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3);
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
            color: #003893;
            transform: translateX(-3px);
        }
        
        .alert {
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 4px solid;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }
        
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-avatar-container {
                margin-right: 0;
                margin-bottom: 20px;
            }
            
            .profile-actions {
                margin-left: 0;
                margin-top: 20px;
                width: 100%;
                justify-content: center;
            }
            
            .alumni-meta {
                justify-content: center;
            }
            
            .profile-tabs {
                overflow-x: auto;
                white-space: nowrap;
                padding-bottom: 5px;
            }
            
            .tab-item {
                padding: 12px 15px;
                font-size: 14px;
            }
            
            .section-title {
                font-size: 18px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
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
            <a href="admin-alumni-list.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Alumni List
            </a>
            
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <div class="alumni-profile-container">
                <div class="profile-header">
                    <div class="profile-header-pattern"></div>
                    
                    <div class="profile-avatar-container">
                        <img src="<?php echo !empty($alumni['avatar']) ? htmlspecialchars($alumni['avatar']) : 'images/avatar.png'; ?>" 
                             alt="Alumni Avatar" class="profile-avatar">
                        <div class="status-indicator">
                            <i class="fas <?php echo $alumni['status'] == 1 ? 'fa-check' : 'fa-times'; ?>"></i>
                        </div>
                    </div>
                    
                    <div class="profile-info">
                        <div class="profile-name">
                            <?php echo htmlspecialchars($alumni['firstname'] . ' ' . $alumni['lastname']); ?>
                        </div>
                        <div class="alumni-id-badge">
                            <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($alumni['alumni_id']); ?>
                        </div>
                        
                        <div class="alumni-meta">
                            <div class="alumni-meta-item">
                                <i class="fas fa-graduation-cap"></i>
                                <?php echo htmlspecialchars($alumni['course_name']); ?>
                            </div>
                            <div class="alumni-meta-item">
                                <i class="fas fa-calendar-alt"></i>
                                Batch <?php echo htmlspecialchars($alumni['batch']); ?>
                            </div>
                            <div class="alumni-meta-item">
                                <i class="fas fa-<?php echo $alumni['gender'] == 'Male' ? 'mars' : 'venus'; ?>"></i>
                                <?php echo htmlspecialchars($alumni['gender']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-actions">
                        <a href="edit_alumni.php?id=<?php echo $alumni['alumni_id']; ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <button type="button" class="btn btn-danger" onclick="confirmDelete('<?php echo $alumni['alumni_id']; ?>')">
                            <i class="fas fa-trash-alt"></i> Delete
                        </button>
                    </div>
                </div>
                
                <div class="profile-tabs">
                    <div class="tab-item active" data-tab="personal">
                        <i class="fas fa-user"></i> Personal Info
                    </div>
                    <div class="tab-item" data-tab="academic">
                        <i class="fas fa-graduation-cap"></i> Academic
                    </div>
                    <div class="tab-item" data-tab="account">
                        <i class="fas fa-shield-alt"></i> Account Status
                    </div>
                </div>
                
                <div id="personal-tab" class="tab-content active">
                    <div class="content-section">
                        <h3 class="section-title">
                            <i class="fas fa-address-card"></i> Personal Information
                        </h3>
                        
                        <div class="info-grid">
                            <div class="info-card">
                                <span class="info-label">First Name</span>
                                <div class="info-value"><?php echo htmlspecialchars($alumni['firstname']); ?></div>
                            </div>
                            
                            <div class="info-card">
                                <span class="info-label">Middle Name</span>
                                <div class="info-value">
                                    <?php echo !empty($alumni['middlename']) ? htmlspecialchars($alumni['middlename']) : '<em>Not provided</em>'; ?>
                                </div>
                            </div>
                            
                            <div class="info-card">
                                <span class="info-label">Last Name</span>
                                <div class="info-value"><?php echo htmlspecialchars($alumni['lastname']); ?></div>
                            </div>
                            
                            <div class="info-card">
                                <span class="info-label">Gender</span>
                                <div class="info-value">
                                    <i class="fas fa-<?php echo $alumni['gender'] == 'Male' ? 'mars' : 'venus'; ?>"></i>
                                    <?php echo htmlspecialchars($alumni['gender']); ?>
                                </div>
                            </div>
                            
                            <div class="info-card">
                                <span class="info-label">Email Address</span>
                                <div class="info-value">
                                    <a href="mailto:<?php echo htmlspecialchars($alumni['email']); ?>" style="color: #007bff; text-decoration: none;">
                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($alumni['email']); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="academic-tab" class="tab-content">
                    <div class="content-section">
                        <h3 class="section-title">
                            <i class="fas fa-university"></i> Academic Information
                        </h3>
                        
                        <div class="info-grid">
                            <div class="info-card">
                                <span class="info-label">Degree/Course</span>
                                <div class="info-value"><?php echo htmlspecialchars($alumni['course_name']); ?></div>
                            </div>
                            
                            <div class="info-card">
                                <span class="info-label">Batch/Year Graduated</span>
                                <div class="info-value">
                                    <i class="fas fa-user-graduate"></i> 
                                    <?php echo htmlspecialchars($alumni['batch']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="account-tab" class="tab-content">
                    <div class="content-section">
                        <h3 class="section-title">
                            <i class="fas fa-user-shield"></i> Account Status
                        </h3>
                        
                        <div class="info-grid">
                            <div class="info-card">
                                <span class="info-label">Verification Status</span>
                                <div class="info-value">
                                    <span class="badge <?php echo $alumni['status'] == 1 ? 'badge-verified' : 'badge-unverified'; ?>">
                                        <i class="fas <?php echo $alumni['status'] == 1 ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                                        <?php echo $alumni['status'] == 1 ? 'Verified' : 'Unverified'; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="info-card">
                                <span class="info-label">Connection Status</span>
                                <div class="info-value">
                                    <span class="badge <?php echo $alumni['connected_to'] == 1 ? 'badge-connected' : 'badge-not-connected'; ?>">
                                        <i class="fas <?php echo $alumni['connected_to'] == 1 ? 'fa-link' : 'fa-unlink'; ?>"></i>
                                        <?php echo $alumni['connected_to'] == 1 ? 'Connected' : 'Not Connected'; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="info-card">
                                <span class="info-label">Record Created</span>
                                <div class="info-value">
                                    <i class="far fa-calendar-alt"></i> <?php echo $formatted_date; ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($has_account): ?>
                        <div class="account-info">
                            <div class="account-info-title">
                                <i class="fas fa-user-lock"></i> Portal Account Information
                            </div>
                            
                            <div class="info-grid">
                                <div class="info-card">
                                    <span class="info-label">Username</span>
                                    <div class="info-value"><?php echo htmlspecialchars($user_data['username']); ?></div>
                                </div>
                                
                                <div class="info-card">
                                    <span class="info-label">Account Type</span>
                                    <div class="info-value">
                                        <?php 
                                            $type = "";
                                            switch($user_data['type']) {
                                                case 1: $type = "Administrator"; break;
                                                case 2: $type = "Alumni Officer"; break;
                                                case 3: $type = "Alumni"; break;
                                                default: $type = "Unknown";
                                            }
                                            echo $type;
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="no-account-state">
                            <i class="fas fa-user-slash"></i>
                            <p>This alumni does not have a portal account yet.</p>
                            <a href="#" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i> Create Account
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
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
        
        function confirmDelete(id) {
            if(confirm('Are you sure you want to delete this alumni record? This action cannot be undone.')) {
                window.location.href = 'delete_alumni.php?id=' + id;
            }
        }
        
        document.querySelectorAll('.tab-item').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.tab-item').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                this.classList.add('active');
                
                const tabId = this.getAttribute('data-tab') + '-tab';
                document.getElementById(tabId).classList.add('active');
            });
        });
    </script>
</body>
</html>
