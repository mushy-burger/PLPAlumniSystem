<?php
session_start();
include 'admin/db_connect.php';

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = ($page - 1) * $items_per_page;

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$search_condition = '';
if (!empty($search)) {
    $search_condition = " WHERE title LIKE '%$search%' OR content LIKE '%$search%'";
}

$sql = "SELECT * FROM events $search_condition ORDER BY schedule DESC LIMIT $offset, $items_per_page";
$result = $conn->query($sql);

$total_sql = "SELECT COUNT(*) as total FROM events $search_condition";
$total_result = $conn->query($total_sql);
$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $items_per_page);

function build_query_params($exclude = []) {
    $params = [];
    foreach ($_GET as $key => $value) {
        if (!in_array($key, $exclude)) {
            $params[] = htmlspecialchars($key) . '=' . htmlspecialchars($value);
        }
    }
    return implode('&', $params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Alumni Events</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="css/alumni-events.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
<div class="interface-header">
        <img src="images/logo.png" alt="PLP Logo" class="logo-interface">
        <div class="text">
            <div class="school-name">Pamantasan Ng Lungsod Ng Pasig</div>
            <p class="p-size">Alkade Jose St. Kapasigan, Pasig City</p>
        </div>
    </div>

  <div class="sidebar" id="sidebar">
      <div class="toggle-btn" onclick="toggleSidebar()">&#x25C0;</div>

      <div class="sidebar-content">
          <div class="profile-section">
              <a class="profile-pic">
                  <img src="images/avatar.png" alt="Profile Picture">
              </a>
              <div class="profile-name">ALUMNI</div>
          </div>

          <a href="alumni-home.php"><img src="images/home.png" alt="Home"><span>Home</span></a>
                    <a href="alumni-gallery.php"><img src="images/gallery.png" alt="Gallery"><span>Gallery</span></a>
                    <a href="alumni-list.php"><img src="images/alumni_list.png" alt="Alumni List"><span>Alumni List</span></a>
                    <a href="alumni-job.php"><img src="images/jobs.png" alt="Jobs"><span>Jobs</span></a>
                    <a href="alumni-forums.php"><img src="images/forums.png" alt="Forums"><span>Forums</span></a>
                    <a href="alumni-events.php" class="active"><img src="images/calendar.png" alt="Events"><span>Events</span></a>
                    <a href="alumni-about.php"><img src="images/about.png" alt="About"><span>About</span></a>
                    <a href="landing.php"><img src="images/log-out.png" alt="Log Out"><span>Log Out</span></a>
      </div>
  </div>

  <main class="content">
      <div class="alumni-events-content">
          <header>Alumni Events</header>
          <hr>
          
          <div class="top-actions">
              <div class="left-controls">
                  <div class="entries-search-wrapper">
                      <form method="GET" action="alumni-events.php" class="search-form">
                          <div class="entries-control">
                              <span>Show</span>
                              <select name="limit" onchange="this.form.submit()">
                                  <option value="10" <?php echo $items_per_page == 10 ? 'selected' : ''; ?>>10</option>
                                  <option value="25" <?php echo $items_per_page == 25 ? 'selected' : ''; ?>>25</option>
                                  <option value="50" <?php echo $items_per_page == 50 ? 'selected' : ''; ?>>50</option>
                              </select>
                              <span>Entries</span>
                          </div>
                          <div class="search-control">
                              <span>Search:</span>
                              <input type="text" name="search" class="search-input" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search events...">
                              <button type="submit" class="search-btn">Search</button>
                          </div>
                      </form>
                  </div>
              </div>
          </div>

          <div class="events-container">
              <?php 
              if($result && $result->num_rows > 0):
                  while($row = $result->fetch_assoc()):
                      $banner_path = $row['banner'];
                      if(empty($banner_path) || !file_exists($banner_path)) {
                          $banner_path = "images/no-image.jpg";
                      }
                      
                      $event_date = date('M d, Y', strtotime($row['schedule']));
                      $event_time = date('h:i A', strtotime($row['schedule']));
              ?>
              <div class="event-card">
                  <div class="event-banner">
                      <img src="<?php echo $banner_path; ?>" alt="<?php echo htmlspecialchars($row['title']); ?>">
                  </div>
                  <div class="event-info">
                      <h3 class="event-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                      <div class="event-meta">
                          <div class="event-date">
                              <i class="fas fa-calendar-alt"></i> <?php echo $event_date; ?>
                          </div>
                          <div class="event-time">
                              <i class="fas fa-clock"></i> <?php echo $event_time; ?>
                          </div>
                      </div>
                      <div class="event-description">
                          <?php 
                          $content = strip_tags($row['content']);
                          $short_content = strlen($content) > 150 ? substr($content, 0, 150) . '...' : $content;
                          echo htmlspecialchars($short_content); 
                          ?>
                      </div>
                      <div class="event-actions">
                          <button class="view-details-btn" data-id="<?php echo $row['id']; ?>" 
                                  data-title="<?php echo htmlspecialchars($row['title']); ?>"
                                  data-content="<?php echo htmlspecialchars($row['content']); ?>"
                                  data-schedule="<?php echo $row['schedule']; ?>"
                                  data-banner="<?php echo $banner_path; ?>"
                                  data-gform="<?php echo htmlspecialchars($row['gform_link'] ?? ''); ?>"
                                  onclick="viewEvent(this)">
                              View Details
                          </button>
                          <?php if(!empty($row['gform_link'])): ?>
                          <a href="<?php echo htmlspecialchars($row['gform_link']); ?>" target="_blank" class="register-btn">
                              Register Now
                          </a>
                          <?php endif; ?>
                      </div>
                  </div>
              </div>
              <?php 
                  endwhile;
              else:
              ?>
              <div class="no-events">
                  <i class="fas fa-calendar-times"></i>
                  <p>No events found</p>
                  <?php if (!empty($search)): ?>
                  <p>Try a different search term or check back later.</p>
                  <?php else: ?>
                  <p>Check back later for upcoming events.</p>
                  <?php endif; ?>
              </div>
              <?php endif; ?>
          </div>

          <?php if($total_pages > 1): ?>
          <div class="list-foot">
              <div class="pagination-info">
                  <span>Page <?php echo $page; ?> of <?php echo max(1, $total_pages); ?></span>
                  <span>Showing <?php echo min($total_records, $items_per_page); ?> of <?php echo $total_records; ?> entries</span>
                  <?php if (!empty($search)): ?>
                  <span class="filter-notice">Filtered results</span>
                  <?php endif; ?>
              </div>
              <div class="pagination">
                  <?php if($page > 1): ?>
                      <a href="?page=1&<?php echo build_query_params(['page']); ?>" class="page-link">First</a>
                      <a href="?page=<?php echo $page-1; ?>&<?php echo build_query_params(['page']); ?>" class="page-link">Previous</a>
                  <?php endif; ?>
                  
                  <?php
                  $start_page = max(1, $page - 2);
                  $end_page = min($total_pages, $page + 2);
                  
                  for($i = $start_page; $i <= $end_page; $i++):
                  ?>
                      <a href="?page=<?php echo $i; ?>&<?php echo build_query_params(['page']); ?>" 
                         class="page-link <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                  <?php endfor; ?>
                  
                  <?php if($page < $total_pages): ?>
                      <a href="?page=<?php echo $page+1; ?>&<?php echo build_query_params(['page']); ?>" class="page-link">Next</a>
                      <a href="?page=<?php echo $total_pages; ?>&<?php echo build_query_params(['page']); ?>" class="page-link">Last</a>
                  <?php endif; ?>
              </div>
          </div>
          <?php endif; ?>
      </div>
  </main>

  <div class="event-modal" id="viewEventModal">
      <div class="event-modal-content">
          <h2>Event Details</h2>
          <div class="event-banner-container">
              <img id="modalBanner" src="" alt="Event Banner">
          </div>
          <div class="event-header">
              <h3 id="modalTitle"></h3>
              <div class="event-datetime">
                  <div><i class="fas fa-calendar-alt"></i> <span id="modalDate"></span></div>
                  <div><i class="fas fa-clock"></i> <span id="modalTime"></span></div>
              </div>
          </div>
          <div id="modalContent" class="event-full-description"></div>
          <div id="modalRegistration" class="event-registration"></div>
          <button class="close-modal-btn" id="closeViewModal">Close</button>
      </div>
  </div>

  <script>
      function toggleSidebar() {
          const sidebar = document.getElementById('sidebar');
          sidebar.classList.toggle('collapsed');
          const toggleBtn = document.querySelector('.toggle-btn');
          toggleBtn.innerHTML = sidebar.classList.contains('collapsed') ? '&#x25B6;' : '&#x25C0;';
      }

      const viewModal = document.getElementById("viewEventModal");
      
      document.getElementById('closeViewModal').addEventListener("click", () => {
          viewModal.style.display = "none";
      });
      
      function viewEvent(btn) {
          const title = btn.getAttribute('data-title');
          const content = btn.getAttribute('data-content');
          const scheduleDate = new Date(btn.getAttribute('data-schedule'));
          const banner = btn.getAttribute('data-banner');
          const gform = btn.getAttribute('data-gform');

          document.getElementById('modalTitle').textContent = title;
          document.getElementById('modalDate').textContent = scheduleDate.toLocaleDateString('en-US', {
              weekday: 'long',
              year: 'numeric',
              month: 'long',
              day: 'numeric'
          });
          document.getElementById('modalTime').textContent = scheduleDate.toLocaleTimeString('en-US', {
              hour: 'numeric',
              minute: 'numeric',
              hour12: true
          });
          document.getElementById('modalContent').innerHTML = content;
          document.getElementById('modalBanner').src = banner;
          
          const registrationDiv = document.getElementById('modalRegistration');
          if (gform && gform.trim() !== '') {
              registrationDiv.innerHTML = `
                  <div class="registration-section">
                      <h4>Event Registration</h4>
                      <p>Register for this event by clicking the button below:</p>
                      <a href="${gform}" target="_blank" class="registration-btn">
                          <i class="fas fa-external-link-alt"></i> Register Now
                      </a>
                  </div>
              `;
          } else {
              registrationDiv.innerHTML = '';
          }

          viewModal.style.display = "flex";
      }
      
      window.addEventListener("click", (e) => {
          if (e.target === viewModal) {
              viewModal.style.display = "none";
          }
      });
  </script>
</body>
</html> 