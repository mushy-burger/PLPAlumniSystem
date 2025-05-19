<?php
session_start();
include 'admin/db_connect.php';

$about_query = "SELECT about_content, name, cover_img, contact, email, facebook, twitter, instagram, linkedin FROM system_settings LIMIT 1";
$about_result = $conn->query($about_query);
$about_data = $about_result->fetch_assoc();

$about_content = $about_data['about_content'] ?? '';
$system_name = $about_data['name'] ?? 'Alumni Portal';
$cover_img = $about_data['cover_img'] ?? '';

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
        <title>About - <?php echo htmlspecialchars($system_name); ?></title>
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="alumni-about.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <style>
            .about-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }

            .about-header {
                position: relative;
                background: url('<?php echo !empty($cover_img) ? $cover_img : 'images/plpasigg.jpg'; ?>') center/cover no-repeat;
                height: 300px;
                border-radius: 15px;
                margin-bottom: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }

            .about-header::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 51, 102, 0.7);
                z-index: 1;
            }

            .about-header h1 {
                position: relative;
                z-index: 2;
                color: white;
                font-size: 2.5em;
                text-align: center;
                margin: 0;
                padding: 20px;
                text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            }

            .about-content {
                background: white;
                border-radius: 15px;
                padding: 40px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            }

            .about-section {
                margin-bottom: 40px;
            }

            .about-section h2 {
                color: #003366;
                font-size: 1.8em;
                margin-bottom: 20px;
                position: relative;
                padding-bottom: 10px;
            }

            .about-section h2::after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 0;
                width: 60px;
                height: 3px;
                background: #0047AB;
            }

            .about-card {
                background: #f8f9fa;
                border-radius: 10px;
                padding: 25px;
                margin-bottom: 30px;
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }

            .about-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            }

            .about-card h3 {
                color: #0047AB;
                margin-bottom: 15px;
                font-size: 1.4em;
            }

            .about-features {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 30px;
                margin-top: 40px;
            }

            .feature-card {
                text-align: center;
                padding: 30px;
                background: #fff;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                transition: transform 0.3s ease;
            }

            .feature-card:hover {
                transform: translateY(-5px);
            }

            .feature-card i {
                font-size: 2.5em;
                color: #0047AB;
                margin-bottom: 20px;
            }

            .feature-card h3 {
                color: #003366;
                margin-bottom: 15px;
            }

            .contact-section {
                margin-top: 60px;
                background: #f8f9fa;
                border-radius: 15px;
                padding: 40px;
            }

            .contact-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 30px;
                margin-top: 30px;
            }

            .contact-item {
                display: flex;
                align-items: center;
                gap: 15px;
            }

            .contact-item i {
                font-size: 1.5em;
                color: #0047AB;
            }

            .social-links {
                display: flex;
                gap: 20px;
                margin-top: 30px;
            }

            .social-link {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                background: #0047AB;
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                text-decoration: none;
                transition: transform 0.3s ease, background-color 0.3s ease;
            }

            .social-link:hover {
                transform: translateY(-5px);
                background: #003366;
            }

            @media (max-width: 768px) {
                .about-header {
                    height: 200px;
                }

                .about-header h1 {
                    font-size: 2em;
                }

                .about-content {
                    padding: 20px;
                }

                .contact-grid {
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
                                <p>The Pamantasan ng Lungsod ng Pasig Alumni Portal serves as a dynamic platform connecting graduates, fostering collaboration, and maintaining strong ties with your alma mater.</p>
                                
                                <div class="about-card">
                                    <h3>Our Mission</h3>
                                    <p>To provide an inclusive and transformative education that empowers students to excel in their chosen fields and contribute meaningfully to society.</p>
                                </div>
                                
                                <div class="about-card">
                                    <h3>Our Vision</h3>
                                    <p>To be recognized as a premier institution that nurtures skilled, innovative, and values-driven graduates who make significant contributions to nation-building and global development.</p>
                                </div>

                                <h2>What We Offer</h2>
                                <div class="about-features">
                                    <div class="feature-card">
                                        <i class="fas fa-users"></i>
                                        <h3>Networking</h3>
                                        <p>Connect with fellow alumni and expand your professional network</p>
                                    </div>
                                    <div class="feature-card">
                                        <i class="fas fa-briefcase"></i>
                                        <h3>Career Growth</h3>
                                        <p>Access exclusive job opportunities and career development resources</p>
                                    </div>
                                    <div class="feature-card">
                                        <i class="fas fa-calendar-alt"></i>
                                        <h3>Events</h3>
                                        <p>Participate in alumni gatherings, workshops, and special events</p>
                                    </div>
                                    <div class="feature-card">
                                        <i class="fas fa-comments"></i>
                                        <h3>Forums</h3>
                                        <p>Engage in meaningful discussions and knowledge sharing</p>
                                    </div>
                                </div>
                            </div>

                            <div class="contact-section">
                                <h2>Get in Touch</h2>
                                <div class="contact-grid">
                                    <?php if(!empty($about_data['email'])): ?>
                                    <div class="contact-item">
                                        <i class="fas fa-envelope"></i>
                                        <p><?php echo htmlspecialchars($about_data['email']); ?></p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($about_data['contact'])): ?>
                                    <div class="contact-item">
                                        <i class="fas fa-phone"></i>
                                        <p><?php echo htmlspecialchars($about_data['contact']); ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="social-links">
                                    <?php if(!empty($about_data['facebook'])): ?>
                                        <a href="<?php echo htmlspecialchars($about_data['facebook']); ?>" class="social-link" target="_blank">
                                            <i class="fab fa-facebook-f"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($about_data['twitter'])): ?>
                                        <a href="<?php echo htmlspecialchars($about_data['twitter']); ?>" class="social-link" target="_blank">
                                            <i class="fab fa-twitter"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($about_data['instagram'])): ?>
                                        <a href="<?php echo htmlspecialchars($about_data['instagram']); ?>" class="social-link" target="_blank">
                                            <i class="fab fa-instagram"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($about_data['linkedin'])): ?>
                                        <a href="<?php echo htmlspecialchars($about_data['linkedin']); ?>" class="social-link" target="_blank">
                                            <i class="fab fa-linkedin-in"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
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

            document.addEventListener('DOMContentLoaded', function() {
                const cards = document.querySelectorAll('.feature-card, .about-card');
                
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }
                    });
                });

                cards.forEach(card => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.5s ease-out';
                    observer.observe(card);
                });
            });
        </script>
    </body>
</html>