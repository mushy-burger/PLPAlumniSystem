<?php
session_start();
include 'admin/db_connect.php';

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 6;
$offset = ($page - 1) * $items_per_page;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$location_filter = isset($_GET['location']) ? $_GET['location'] : '';

$where_clause = array();
$params = array();

if (!empty($search)) {
    $where_clause[] = "(company LIKE ? OR job_title LIKE ? OR description LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

if (!empty($location_filter)) {
    $where_clause[] = "location = ?";
    $params[] = $location_filter;
}

$where_sql = '';
if (!empty($where_clause)) {
    $where_sql = " WHERE " . implode(' AND ', $where_clause);
}

$count_query = "SELECT COUNT(*) as total FROM careers $where_sql";
$count_stmt = $conn->prepare($count_query);

if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $count_stmt->bind_param($types, ...$params);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $items_per_page);

$query = "SELECT c.*, u.name as posted_by 
          FROM careers c 
          LEFT JOIN users u ON c.user_id = u.alumni_id
          $where_sql
          ORDER BY c.date_created DESC
          LIMIT $offset, $items_per_page";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$location_query = "SELECT DISTINCT location FROM careers ORDER BY location";
$location_result = $conn->query($location_query);
$locations = array();
while ($row = $location_result->fetch_assoc()) {
    $locations[] = $row['location'];
}

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
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Job Opportunities - Alumni Portal</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="alumni-job.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    #editJobForm button[type="submit"] {
      background-color: #003893;
      color: white;
      border: none;
      padding: 12px 25px;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      width: 100%;
      margin-top: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      box-shadow: 0 4px 10px rgba(0, 56, 147, 0.2);
    } 

    #editJobForm button[type="submit"]:hover {
      background-color: #0056b3;
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(0, 56, 147, 0.25);
    }
  </style>
</head>
<body>

  <div class="interface-header">
    <img src="images/logo.png" alt="PLP Logo" class="logo-interface" />
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
          <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Profile Picture" />
        </a>
        <div class="profile-name"><?php echo htmlspecialchars($display_name); ?></div>
      </div>
      <a href="alumni-home.php"><img src="images/home.png" alt="Home" /><span>Home</span></a>
      <a href="alumni-gallery.php"><img src="images/gallery.png" alt="Gallery" /><span>Gallery</span></a>
      <a href="alumni-list.php"><img src="images/alumni_list.png" alt="Alumni List" /><span>Alumni List</span></a>
      <a href="alumni-job.php" class="active"><img src="images/jobs.png" alt="Jobs" /><span>Jobs</span></a>
      <a href="alumni-forums.php"><img src="images/forums.png" alt="Forums" /><span>Forums</span></a>
      <a href="alumni-events.php"><img src="images/calendar.png" alt="Events" /><span>Events</span></a>
      <a href="alumni-about.php"><img src="images/about.png" alt="About" /><span>About</span></a>
      <a href="landing.php"><img src="images/log-out.png" alt="Log Out" /><span>Log Out</span></a>
    </div>
  </div>

  <div class="job-main-content">
    <div class="job-header">
      <h1>Job Opportunities</h1>
      <?php if(isset($_SESSION['login_id'])): ?>
      <button class="post-job-btn" onclick="openPostJobModal()">
        <i class="fas fa-plus-circle"></i> Post a Job Opportunity
      </button>
      <?php else: ?>
      <div class="login-notice">
        <a href="login.php" class="login-link">Log in</a> to post job opportunities
      </div>
      <?php endif; ?>
    </div>

    <?php if(isset($_SESSION['success'])): ?>
    <div class="alert-success">
      <i class="fas fa-check-circle"></i>
      <?php 
        echo $_SESSION['success']; 
        unset($_SESSION['success']);
      ?>
    </div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['error'])): ?>
    <div class="alert-error">
      <i class="fas fa-exclamation-circle"></i>
      <?php 
        echo $_SESSION['error']; 
        unset($_SESSION['error']);
      ?>
    </div>
    <?php endif; ?>

    <form method="GET" action="" class="search-filter-form">
      <div class="search-bar">
        <input type="text" id="search" name="search" placeholder="Search job titles, companies, or descriptions" value="<?php echo htmlspecialchars($search); ?>" />
        <button type="submit" id="searchIcon"><i class="fas fa-search"></i></button>
      </div>

      <div class="filter-options">
        <select name="location" class="filter-select">
          <option value="">All Locations</option>
          <?php foreach($locations as $loc): ?>
          <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo $location_filter === $loc ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($loc); ?>
          </option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="apply-filter-btn">Apply</button>
        <a href="alumni-job.php" class="reset-btn">Reset</a>
      </div>
    </form>

    <?php if(isset($_SESSION['login_id'])): ?>
    <div class="add-job-section">
      <button class="add-job-btn" onclick="openPostJobModal()">
        <i class="fas fa-plus-circle"></i> Add New Job Listing
      </button>
    </div>
    <?php endif; ?>

    <div class="job-cards">
      <?php if($result->num_rows > 0): ?>
        <?php while($job = $result->fetch_assoc()): ?>
          <div class="job-card">
            <h2><span><?php echo htmlspecialchars($job['job_title']); ?></span></h2>
            <div class="job-meta">
              <div><img src="images/job-company.png" class="icon-job" /> <?php echo htmlspecialchars($job['company']); ?></div>
              <div><img src="images/job-home.png" class="icon-job" /> <?php echo htmlspecialchars($job['location']); ?></div>
            </div>
            <div class="job-date">
              <i class="far fa-calendar-alt"></i> Posted: <?php echo date('F d, Y', strtotime($job['date_created'])); ?>
            </div>
            <p><?php echo substr(strip_tags(html_entity_decode($job['description'])), 0, 150) . '...'; ?></p>
            <div class="job-footer">
              <span class="posted-by">
                <i class="fas fa-user"></i> By: <?php echo htmlspecialchars($job['posted_by'] ?? 'Admin'); ?>
              </span>
              <div class="job-actions">
                <button class="read-more" data-id="<?php echo $job['id']; ?>">Read More</button>
                
                <?php 
                $current_user = isset($_SESSION['login_id']) ? $_SESSION['login_id'] : 'Not logged in';
                $job_owner = $job['user_id'] ?? 'No user ID';
                
                $is_owner = false;
                
                if (isset($_SESSION['login_id'])) {
                  if ($_SESSION['login_id'] === $job['user_id']) {
                    $is_owner = true;
                  }
                  else if (strpos($job['user_id'], $_SESSION['login_id']) === 0) {
                    $is_owner = true;
                  }
                  else if (strpos($_SESSION['login_id'], $job['user_id']) === 0) {
                    $is_owner = true;
                  }
                }
                
                if($is_owner): 
                ?>
                <button class="edit-job-btn" 
                        data-id="<?php echo $job['id']; ?>"
                        data-title="<?php echo htmlspecialchars($job['job_title']); ?>"
                        data-company="<?php echo htmlspecialchars($job['company']); ?>"
                        data-location="<?php echo htmlspecialchars($job['location']); ?>"
                        data-description="<?php echo htmlspecialchars($job['description']); ?>"
                        onclick="openEditJobModal(this)">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="delete-job-btn" data-id="<?php echo $job['id']; ?>" onclick="confirmDelete(<?php echo $job['id']; ?>)">
                    <i class="fas fa-trash"></i> Delete
                </button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="no-jobs-message">
          <i class="fas fa-briefcase"></i>
          <h3>No job opportunities found</h3>
          <p>No job postings match your search criteria. Try adjusting your filters or check back later.</p>
        </div>
      <?php endif; ?>
    </div>

    <?php if($total_pages > 1): ?>
    <div class="pagination">
      <?php if($page > 1): ?>
        <a href="?page=1<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($location_filter) ? '&location='.urlencode($location_filter) : ''; ?>">&laquo;</a>
        <a href="?page=<?php echo $page-1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($location_filter) ? '&location='.urlencode($location_filter) : ''; ?>">Prev</a>
      <?php endif; ?>
      
      <?php
      $start_page = max(1, $page - 2);
      $end_page = min($total_pages, $start_page + 4);
      for($i = $start_page; $i <= $end_page; $i++):
      ?>
        <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($location_filter) ? '&location='.urlencode($location_filter) : ''; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
      <?php endfor; ?>
      
      <?php if($page < $total_pages): ?>
        <a href="?page=<?php echo $page+1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($location_filter) ? '&location='.urlencode($location_filter) : ''; ?>">Next</a>
        <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($location_filter) ? '&location='.urlencode($location_filter) : ''; ?>">&raquo;</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <div id="readMoreModal" class="alumni-modal">
    <div class="alumni-job-modal-content">
      <span class="close-button" id="closeReadMoreModal">&times;</span>
      <h2 id="modalJobTitle"></h2>
      <h4 id="modalJobCompany"></h4>
      <div class="job-meta">
        <p><i class="fas fa-map-marker-alt"></i> <strong>Location:</strong> <span id="modalJobLocation"></span></p>
        <p><i class="fas fa-user"></i> <strong>Posted by:</strong> <span id="modalJobPostedBy"></span></p>
        <p><i class="fas fa-calendar-alt"></i> <strong>Posted on:</strong> <span id="modalJobDate"></span></p>
      </div>
      <hr>
      <h3>Job Description</h3>
      <div id="modalFullDescription"></div>
    </div>
  </div>

  <div id="postJobModal" class="alumni-modal">
    <div class="alumni-job-modal-content">
      <span class="close-button" id="closePostJobModal">&times;</span>
      <h2>Post a Job Opportunity</h2>
      <form id="postJobForm" action="post_job.php" method="POST">
        <div class="job-form-group">
          <label for="jobTitle">Job Position:</label>
          <input type="text" id="jobTitle" name="job_title" required />
        </div>

        <div class="job-form-group">
          <label for="jobCompany">Company:</label>
          <input type="text" id="jobCompany" name="company" required />
        </div>

        <div class="job-form-group">
          <label for="jobLocation">Job Location:</label>
          <select id="jobLocation" name="location" required>
            <option value="">Select Location</option>
            <option value="Home-based">Home-based</option>
            <option value="On-site">On-site</option>
            <option value="Hybrid">Hybrid</option>
          </select>
        </div>

        <div class="job-form-group">
          <label for="fullDescription">Job Description:</label>
          <textarea id="fullDescription" name="description" rows="6" required></textarea>
        </div>

        <button type="submit">Submit Job Posting</button>
      </form>
    </div>
  </div>

  <div id="editJobModal" class="alumni-modal">
    <div class="alumni-job-modal-content">
      <span class="close-button" id="closeEditJobModal">&times;</span>
      <h2>Edit Job Posting</h2>
      <form id="editJobForm" action="update_job.php" method="POST">
        <input type="hidden" id="editJobId" name="job_id">
        
        <div class="job-form-group">
          <label for="editJobTitle">Job Position:</label>
          <input type="text" id="editJobTitle" name="job_title" required />
        </div>

        <div class="job-form-group">
          <label for="editJobCompany">Company:</label>
          <input type="text" id="editJobCompany" name="company" required />
        </div>

        <div class="job-form-group">
          <label for="editJobLocation">Job Location:</label>
          <select id="editJobLocation" name="location" required>
            <option value="">Select Location</option>
            <option value="Home-based">Home-based</option>
            <option value="On-site">On-site</option>
            <option value="Hybrid">Hybrid</option>
          </select>
        </div>

        <div class="job-form-group">
          <label for="editJobDescription">Job Description:</label>
          <textarea id="editJobDescription" name="description" rows="6" required></textarea>
        </div>

        <button type="submit"><i class="fas fa-save"></i> Update Job Posting</button>
      </form>
    </div>
  </div>

  <div id="deleteJobModal" class="alumni-modal">
    <div class="alumni-job-modal-content">
      <span class="close-button" id="closeDeleteJobModal">&times;</span>
      <h2>Delete Job Posting</h2>
      <p>Are you sure you want to delete this job posting? This action cannot be undone.</p>
      <div class="modal-buttons">
        <button id="confirmDeleteBtn" class="delete-confirm-btn">Delete</button>
        <button id="cancelDeleteBtn" class="cancel-btn">Cancel</button>
      </div>
    </div>
  </div>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      sidebar.classList.toggle('collapsed');
      document.body.classList.toggle('sidebar-collapsed');
      const toggleBtn = document.querySelector('.toggle-btn');
      toggleBtn.innerHTML = sidebar.classList.contains('collapsed') ? '&#x25B6;' : '&#x25C0;';
    }

    const readMoreModal = document.getElementById("readMoreModal");
    const postJobModal = document.getElementById("postJobModal");
    const editJobModal = document.getElementById("editJobModal");
    const deleteJobModal = document.getElementById("deleteJobModal");
    
    const closeReadMoreBtn = document.getElementById("closeReadMoreModal");
    const closePostJobModal = document.getElementById("closePostJobModal");
    const closeEditJobModal = document.getElementById("closeEditJobModal");
    const closeDeleteJobModal = document.getElementById("closeDeleteJobModal");
    
    const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
    const cancelDeleteBtn = document.getElementById("cancelDeleteBtn");
    
    let deleteJobId = null;

    document.querySelectorAll(".read-more").forEach((btn) => {
      if(!btn.dataset.id) return; 
      
      btn.addEventListener("click", function() {
        const jobId = this.dataset.id;
        
        fetch('get_job_details.php?id=' + jobId)
          .then(response => response.json())
          .then(job => {
            document.getElementById("modalJobTitle").textContent = job.job_title;
            document.getElementById("modalJobCompany").textContent = job.company;
            document.getElementById("modalJobLocation").textContent = job.location;
            document.getElementById("modalJobPostedBy").textContent = job.posted_by;
            document.getElementById("modalJobDate").textContent = job.date_posted;
            document.getElementById("modalFullDescription").innerHTML = job.description;
            
            readMoreModal.style.display = "block";
          })
          .catch(error => {
            console.error('Error fetching job details:', error);
          });
      });
    });

    function openPostJobModal() {
      postJobModal.style.display = "block";
    }
    
    function openEditJobModal(btn) {
      const jobId = btn.getAttribute('data-id');
      const jobTitle = btn.getAttribute('data-title');
      const company = btn.getAttribute('data-company');
      const location = btn.getAttribute('data-location');
      const description = btn.getAttribute('data-description');
      
      document.getElementById("editJobId").value = jobId;
      document.getElementById("editJobTitle").value = jobTitle;
      document.getElementById("editJobCompany").value = company;
      document.getElementById("editJobLocation").value = location;
      document.getElementById("editJobDescription").value = description;
      
      editJobModal.style.display = "block";
    }
    
    function confirmDelete(jobId) {
      deleteJobId = jobId;
      deleteJobModal.style.display = "block";
    }
    
    confirmDeleteBtn.addEventListener("click", function() {
      if (deleteJobId) {
        window.location.href = "delete_job.php?id=" + deleteJobId;
      }
    });
    
    cancelDeleteBtn.addEventListener("click", function() {
      deleteJobModal.style.display = "none";
    });

    closeReadMoreBtn.addEventListener("click", function() {
      readMoreModal.style.display = "none";
    });

    closePostJobModal.addEventListener("click", function() {
      postJobModal.style.display = "none";
    });
    
    closeEditJobModal.addEventListener("click", function() {
      editJobModal.style.display = "none";
    });
    
    closeDeleteJobModal.addEventListener("click", function() {
      deleteJobModal.style.display = "none";
    });

    window.addEventListener("click", function(event) {
      if (event.target === readMoreModal) {
        readMoreModal.style.display = "none";
      }
      if (event.target === postJobModal) {
        postJobModal.style.display = "none";
      }
      if (event.target === editJobModal) {
        editJobModal.style.display = "none";
      }
      if (event.target === deleteJobModal) {
        deleteJobModal.style.display = "none";
      }
    });

    document.getElementById('postJobForm').addEventListener('submit', function(e) {
      let valid = true;
      const jobTitle = document.getElementById('jobTitle').value.trim();
      const company = document.getElementById('jobCompany').value.trim();
      const location = document.getElementById('jobLocation').value;
      const description = document.getElementById('fullDescription').value.trim();
      
      if (!jobTitle) {
        valid = false;
        document.getElementById('jobTitle').classList.add('error');
      }
      
      if (!company) {
        valid = false;
        document.getElementById('jobCompany').classList.add('error');
      }
      
      if (!location) {
        valid = false;
        document.getElementById('jobLocation').classList.add('error');
      }
      
      if (!description) {
        valid = false;
        document.getElementById('fullDescription').classList.add('error');
      }
      
      if (!valid) {
        e.preventDefault();
        alert('Please fill in all required fields');
      }
    });
    
    const formInputs = document.querySelectorAll('#postJobForm input, #postJobForm select, #postJobForm textarea');
    formInputs.forEach(input => {
      input.addEventListener('focus', function() {
        this.classList.remove('error');
      });
    });
  </script>
</body>
</html>
