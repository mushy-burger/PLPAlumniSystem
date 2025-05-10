<?php
session_start();
include 'admin/db_connect.php';

$about_query = "SELECT about_content, name FROM system_settings LIMIT 1";
$about_result = $conn->query($about_query);
$about_data = $about_result->fetch_assoc();

$about_content = $about_data['about_content'] ?? '';
$system_name = $about_data['name'] ?? 'Alumni Portal';

$avatar = 'images/avatar.png';
$display_name = 'Alumni User';

if(isset($_SESSION['login_id'])) {
    $alumni_id = $_SESSION['login_id'];
    
    $user_query = "SELECT * FROM users WHERE alumni_id = ?";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("s", $alumni_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_data = $user_result->fetch_assoc();
    
    $alumni_query = "SELECT * FROM alumnus_bio WHERE alumni_id = ?";
    $alumni_stmt = $conn->prepare($alumni_query);
    $alumni_stmt->bind_param("s", $alumni_id);
    $alumni_stmt->execute();
    $alumni_result = $alumni_stmt->get_result();
    $alumni_data = $alumni_result->fetch_assoc();
    
    if(isset($user_data['name'])) {
        $display_name = $user_data['name'];
    }
    
    if(!empty($alumni_data['avatar']) && file_exists($alumni_data['avatar'])) {
        $avatar = $alumni_data['avatar'];
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>About - Alumni Portal</title>
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="alumni-about.css">
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

        <div class="about">
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
                    <a href="alumni-events.php"><img src="images/calendar.png" alt="Events"><span>Events</span></a>
                    <a href="alumni-about.php" class="active"><img src="images/about.png" alt="About"><span>About</span></a>
                    <a href="landing.php"><img src="images/log-out.png" alt="Log Out"><span>Log Out</span></a>
                </div>
            </div>

            <div class="main-content">
                <div class="about-container">
                    <div class="about-header">
                        <div class="about-header-pattern"></div>
                        <h1><i class="fas fa-info-circle"></i> About Us</h1>
                    </div>
                    
                    <div class="about-content">
                        <?php if(!empty($about_content)): ?>
                            <div class="about-section">
                                <?php echo html_entity_decode($about_content); ?>
                            </div>
                        <?php else: ?>
                            <div class="about-section">
                                <h2>Welcome to the PLP Alumni Portal</h2>
                                <p>The Pamantasan ng Lungsod ng Pasig Alumni Portal serves as a hub for connecting graduates, sharing opportunities, and staying connected with your alma mater.</p>
                                
                                <div class="about-card">
                                    <h3>Our Mission</h3>
                                    <p>To provide an inclusive and transformative education that empowers students to succeed in an ever-changing world.</p>
                                </div>
                                
                                <div class="about-card">
                                    <h3>Our Vision</h3>
                                    <p>To be recognized as a leading institution that produces skilled and values-oriented graduates who contribute to nation-building.</p>
                                </div>
                                
                                <h2>Connect With Fellow Alumni</h2>
                                <p>Our alumni portal allows you to:</p>
                                <ul>
                                    <li>Network with other graduates</li>
                                    <li>Share and find job opportunities</li>
                                    <li>Participate in events and forums</li>
                                    <li>Stay updated with university news</li>
                                    <li>Access exclusive alumni resources</li>
                                </ul>
                                
                                <img src="images/logo.png" alt="PLP Logo" class="about-image">
                            </div>
                        <?php endif; ?>
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
        </script>
    </body>
</html>