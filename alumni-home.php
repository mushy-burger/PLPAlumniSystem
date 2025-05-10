<?php
session_start();
include 'admin/db_connect.php';

if(!isset($_SESSION['login_id'])) {
    header('location:login.php');
    exit;
}

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

$avatar = 'images/avatar.png'; 
if(!empty($alumni_data['avatar']) && file_exists($alumni_data['avatar'])) {
    $avatar = $alumni_data['avatar'];
}

$display_name = $user_data['name'] ?? 'Alumni User';

$event_query = "SELECT * FROM events ORDER BY ABS(DATEDIFF(schedule, CURDATE())), schedule ASC LIMIT 5";
$event_result = $conn->query($event_query);

$forum_query = "SELECT ft.*, u.name FROM forum_topics ft LEFT JOIN users u ON ft.user_id = u.alumni_id ORDER BY ft.date_created DESC LIMIT 3";
$forum_result = $conn->query($forum_query);

$job_query = "SELECT * FROM careers ORDER BY date_created DESC LIMIT 3";
$job_result = $conn->query($job_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Alumni Portal - Home</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="alumni-home.css">
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

  <div class="banner-interface"></div>

  <div class="sidebar" id="sidebar">
    <div class="toggle-btn" onclick="toggleSidebar()">&#x25C0;</div>

    <div class="sidebar-content">
      <div class="profile-section">
        <a href="alumni-manage-profile.php" class="profile-pic">
          <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Profile Picture">
        </a>
        <div class="profile-name"><?php echo htmlspecialchars($display_name); ?></div>
      </div>

      <a href="alumni-home.php" class="active"><img src="images/home.png" alt="Home"><span>Home</span></a>
      <a href="alumni-gallery.php"><img src="images/gallery.png" alt="Gallery"><span>Gallery</span></a>
      <a href="alumni-list.php"><img src="images/alumni_list.png" alt="Alumni List"><span>Alumni List</span></a>
      <a href="alumni-job.php"><img src="images/jobs.png" alt="Jobs"><span>Jobs</span></a>
      <a href="alumni-forums.php"><img src="images/forums.png" alt="Forums"><span>Forums</span></a>
      <a href="alumni-events.php"><img src="images/calendar.png" alt="Events"><span>Events</span></a>
      <a href="alumni-about.php"><img src="images/about.png" alt="About"><span>About</span></a>
      <a href="landing.php"><img src="images/log-out.png" alt="Log Out"><span>Log Out</span></a>
    </div>
  </div>

  <div class="home-main-content">
    <section class="welcome-section">
      <div class="welcome-card">
        <div class="welcome-header">
          <i class="fas fa-graduation-cap"></i>
          <h1>Welcome to the PLP Alumni Portal</h1>
        </div>
        <p>Stay connected with your alma mater and fellow alumni. Explore events, job opportunities, and more!</p>
      </div>
    </section>
    
    <section class="events-section">
      <div class="section-header">
        <i class="fas fa-calendar-alt"></i>
        <h2>Upcoming Events</h2>
        <a href="alumni-events.php" class="view-all-link">View all</a>
      </div>
      
      <div class="events-container">
        <?php if($event_result->num_rows > 0): ?>
          <?php while($event = $event_result->fetch_assoc()): ?>
            <div class="event-card">
              <div class="event-img">
                <?php 
                  $banner = $event['banner'];
                  
                  if(empty($banner) || !file_exists($banner)) {
                    if(file_exists('images/no-image.jpg')) {
                      $banner = 'images/no-image.jpg';
                    } else {
                      $banner = 'images/avatar.png';  
                    }
                  }
                ?>
                <img src="<?php echo $banner; ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" />
              </div>
              <div class="event-details">
                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                <div class="event-meta">
                  <span><i class="far fa-calendar"></i> <?php echo date('F d, Y', strtotime($event['schedule'])); ?></span>
                  <span><i class="far fa-clock"></i> <?php echo date('h:i A', strtotime($event['schedule'])); ?></span>
                </div>
                <p><?php echo substr(strip_tags(html_entity_decode($event['content'])), 0, 100) . '...'; ?></p>
                <a href="#" class="read-more" data-id="<?php echo $event['id']; ?>">Read more</a>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="no-events">
            <i class="far fa-calendar-times"></i>
            <h3>No upcoming events</h3>
            <p>Check back soon for new events!</p>
          </div>
        <?php endif; ?>
      </div>
    </section>
    
    <section class="forum-section">
      <div class="section-header">
        <i class="fas fa-comments"></i>
        <h2>Latest Forum Discussions</h2>
        <a href="alumni-forums.php" class="view-all-link">View all</a>
      </div>
      
      <div class="forum-container">
        <?php if($forum_result->num_rows > 0): ?>
          <?php while($topic = $forum_result->fetch_assoc()): ?>
            <div class="forum-card">
              <div class="forum-card-header">
                <h3><?php echo htmlspecialchars($topic['title']); ?></h3>
                <span class="forum-date">
                  <i class="far fa-clock"></i> <?php echo date('M d, Y', strtotime($topic['date_created'])); ?>
                </span>
              </div>
              <p><?php echo substr(strip_tags(html_entity_decode($topic['description'])), 0, 100) . '...'; ?></p>
              <div class="forum-card-footer">
                <span class="posted-by">Posted by: <?php echo htmlspecialchars($topic['name']); ?></span>
                <a href="alumni-forums.php" class="join-discussion">Join Discussion</a>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="no-forums">
            <i class="far fa-comment-dots"></i>
            <h3>No forum topics yet</h3>
            <p>Be the first to start a discussion!</p>
          </div>
        <?php endif; ?>
      </div>
    </section>
    
    <section class="jobs-section">
      <div class="section-header">
        <i class="fas fa-briefcase"></i>
        <h2>Recent Job Opportunities</h2>
        <a href="alumni-job.php" class="view-all-link">View all</a>
      </div>
      
      <div class="jobs-container">
        <?php if($job_result->num_rows > 0): ?>
          <?php while($job = $job_result->fetch_assoc()): ?>
            <div class="job-card">
              <div class="job-card-header">
                <h3><?php echo htmlspecialchars($job['job_title']); ?></h3>
              </div>
              <div class="job-meta">
                <div><img src="images/job-company.png" class="icon-job" /> <?php echo htmlspecialchars($job['company']); ?></div>
                <div><img src="images/job-home.png" class="icon-job" /> <?php echo htmlspecialchars($job['location']); ?></div>
              </div>
              <p><?php echo substr(strip_tags(html_entity_decode($job['description'])), 0, 100) . '...'; ?></p>
              <a href="alumni-job.php" class="view-job-btn">View Details</a>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="no-jobs">
            <i class="far fa-file-alt"></i>
            <h3>No job postings available</h3>
            <p>Check back later for new opportunities!</p>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </div>

  <div id="alumni-home-modal" class="alumni-home-modal">
    <div class="alumni-home-modal-content">
      <span class="close-btn">&times;</span>
      <div id="modal-banner-container" class="modal-banner-container">
        <img id="modal-banner" src="" alt="Event Banner">
      </div>
      <div class="modal-content-wrapper">
        <h2 id="modal-title"></h2>
        <p><strong>Date & Schedule:</strong> <span id="modal-date"></span></p>
        <div id="modal-description"></div>
        <div id="modal-gform-container"></div>
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

    const modal = document.getElementById('alumni-home-modal');
    const closeBtn = document.querySelector('.close-btn');
    const readMoreLinks = document.querySelectorAll('.read-more');

    readMoreLinks.forEach(link => {
      link.addEventListener('click', function (event) {
        event.preventDefault();
        const eventId = this.getAttribute('data-id');
        
        fetch('get_event_details.php?id=' + eventId)
          .then(response => response.json())
          .then(data => {
            document.getElementById('modal-title').innerText = data.title;
            document.getElementById('modal-date').innerText = data.schedule;
            document.getElementById('modal-description').innerHTML = data.content;
            document.getElementById('modal-banner').src = data.banner;
            
            const gformContainer = document.getElementById('modal-gform-container');
            if (data.gform_link && data.gform_link.trim() !== '') {
              gformContainer.innerHTML = `
                <div class="modal-registration">
                  <h3>Event Registration</h3>
                  <p>Register for this event by clicking the button below:</p>
                  <a href="${data.gform_link}" target="_blank" class="join-btn">Register Now</a>
                </div>
              `;
            } else {
              gformContainer.innerHTML = '';
            }
            
            modal.style.display = 'block';
          })
          .catch(error => {
            console.error('Error fetching event details:', error);
          });
      });
    });

    closeBtn.onclick = function () {
      modal.style.display = 'none';
    };

    window.onclick = function (event) {
      if (event.target == modal) {
        modal.style.display = 'none';
      }
    };
  </script>
</body>
</html>
