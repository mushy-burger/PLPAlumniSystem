<?php
session_start();
include 'admin/db_connect.php';

$query = "SELECT * FROM gallery ORDER BY upload_date DESC";
$result = $conn->query($query);

$avatar = 'images/avatar.png';
$display_name = 'Alumni';

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery - Alumni Portal</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="alumni-gallery.css">
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

    <div class="sidebar" id="sidebar">
        <div class="toggle-btn" onclick="toggleSidebar()">&#x25C0;</div>
        <div class="sidebar-content">
            <div class="profile-section">
            <a href="alumni-manage-profile.php" class="profile-pic">
                    <img src="<?php echo $avatar; ?>" alt="Profile Picture">
                </a>
                <div class="profile-name"><?php echo $display_name; ?></div>
            </div>

            <a href="alumni-home.php"><img src="images/home.png" alt="Home"><span>Home</span></a>
            <a href="alumni-gallery.php" class="active"><img src="images/gallery.png" alt="Gallery"><span>Gallery</span></a>
            <a href="alumni-list.php"><img src="images/alumni_list.png" alt="Alumni List"><span>Alumni List</span></a>
            <a href="alumni-job.php"><img src="images/jobs.png" alt="Jobs"><span>Jobs</span></a>
            <a href="alumni-forums.php"><img src="images/forums.png" alt="Forums"><span>Forums</span></a>
            <a href="alumni-events.php"><img src="images/calendar.png" alt="Events"><span>Events</span></a>
            <a href="alumni-about.php"><img src="images/about.png" alt="About"><span>About</span></a>
            <a href="landing.php"><img src="images/log-out.png" alt="Log Out"><span>Log Out</span></a>
        </div>
    </div>

    <div class="gallery-content">
        <div class="gallery-header">
            <h1><i class="fas fa-images"></i> Alumni Gallery</h1>
            <p>Explore memorable moments from our alumni community</p>
        </div>

        <section class="gallery-section">
            <div class="gallery-container">
                <?php if($result->num_rows > 0): ?>
                    <?php while($item = $result->fetch_assoc()): ?>
                        <div class="gallery-card">
                            <div class="gallery-image">
                                <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                            </div>
                            <div class="gallery-info">
                                <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                                <p class="gallery-date">
                                    <i class="far fa-calendar-alt"></i> 
                                    <?php echo date('F d, Y', strtotime($item['upload_date'])); ?>
                                </p>
                                <div class="gallery-description">
                                    <?php echo substr(strip_tags($item['description']), 0, 100) . '...'; ?>
                                </div>
                                <button class="read-more-btn" data-id="<?php echo $item['id']; ?>" 
                                        data-title="<?php echo htmlspecialchars($item['title']); ?>"
                                        data-date="<?php echo date('F d, Y', strtotime($item['upload_date'])); ?>"
                                        data-description="<?php echo htmlspecialchars($item['description']); ?>"
                                        data-image="<?php echo htmlspecialchars($item['image_path']); ?>">
                                    Read More
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-gallery-items">
                        <i class="far fa-images"></i>
                        <h3>No gallery items available</h3>
                        <p>Check back soon for new memories!</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div id="gallery-modal" class="gallery-modal">
        <div class="gallery-modal-content">
            <span class="close-btn">&times;</span>
            <div class="modal-image-container">
                <img id="modal-image" src="" alt="Gallery Image">
            </div>
            <h3 id="modal-title"></h3>
            <p id="modal-date" class="modal-date"></p>
            <div id="modal-description" class="modal-description"></div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            const toggleBtn = document.querySelector('.toggle-btn');
            toggleBtn.innerHTML = sidebar.classList.contains('collapsed') ? '&#x25B6;' : '&#x25C0;';
        }

        const galleryModal = document.getElementById("gallery-modal"); 
        const closeBtn = document.querySelector(".close-btn");
        const readMoreButtons = document.querySelectorAll(".read-more-btn");

        function openModal(title, date, description, imagePath) {
            document.getElementById("modal-title").textContent = title;
            document.getElementById("modal-date").textContent = date;
            document.getElementById("modal-description").innerHTML = description;
            document.getElementById("modal-image").src = imagePath;
            galleryModal.style.display = "block";
            document.body.style.overflow = "hidden"; 
        }

        closeBtn.onclick = function() {  
            galleryModal.style.display = "none";
            document.body.style.overflow = "auto"; 
        }

        window.onclick = function(event) {  
            if (event.target == galleryModal) {
                galleryModal.style.display = "none";
                document.body.style.overflow = "auto";
            }
        }

        readMoreButtons.forEach(button => {
            button.addEventListener("click", function() {
                const title = this.getAttribute("data-title");
                const date = this.getAttribute("data-date");
                const description = this.getAttribute("data-description");
                const imagePath = this.getAttribute("data-image");
                openModal(title, date, description, imagePath);
            });
        });
    </script>
</body>
</html>