<?php
session_start();
include 'admin/db_connect.php';

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $schedule = $conn->real_escape_string($_POST['schedule']);
    $gform_link = $conn->real_escape_string($_POST['gform_link']);
    $banner_img = "";
    
    if(isset($_FILES['banner']) && $_FILES['banner']['error'] == 0){
        $allowed = array('jpg' => 'image/jpg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif');
        $filename = $_FILES['banner']['name'];
        $filetype = $_FILES['banner']['type'];
        $filesize = $_FILES['banner']['size'];
        
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if(!array_key_exists($ext, $allowed)) {
            $_SESSION['error'] = "Error: Please select a valid image format.";
            header("Location: admin-event.php");
            exit();
        }
        
        $maxsize = 5 * 1024 * 1024;
        if($filesize > $maxsize) {
            $_SESSION['error'] = "Error: File size is larger than the allowed limit (5MB).";
            header("Location: admin-event.php");
            exit();
        }
        
        if(in_array($filetype, $allowed)) {
            $new_filename = time() . '_' . $_FILES['banner']['name'];
            
            if(move_uploaded_file($_FILES['banner']['tmp_name'], 'uploads/events/'.$new_filename)){
                $banner_img = 'uploads/events/' . $new_filename;
            } else {
                $_SESSION['error'] = "Error uploading file.";
                header("Location: admin-event.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Error: Please select a valid image format.";
            header("Location: admin-event.php");
            exit();
        }
    }
    
    if ($action == 'add') {
        $banner_sql = $banner_img ? "'$banner_img'" : "'no-image-available.png'";
        $sql = "INSERT INTO events (title, content, schedule, banner, gform_link) 
                VALUES ('$title', '$content', '$schedule', $banner_sql, '$gform_link')";
        
        if ($conn->query($sql)) {
            $_SESSION['success'] = "Event added successfully!";
        } else {
            $_SESSION['error'] = "Error adding event: " . $conn->error;
        }
    } elseif ($action == 'edit') {
        $event_id = intval($_POST['event_id']);
        
        if($banner_img) {
            $curr_banner = $conn->query("SELECT banner FROM events WHERE id='$event_id'")->fetch_assoc()['banner'];
            if($curr_banner != 'no-image-available.png' && file_exists($curr_banner)) {
                unlink($curr_banner);
            }
            
            $sql = "UPDATE events SET title='$title', content='$content', 
                    schedule='$schedule', banner='$banner_img', gform_link='$gform_link' WHERE id=$event_id";
        } else {
            $sql = "UPDATE events SET title='$title', content='$content', 
                    schedule='$schedule', gform_link='$gform_link' WHERE id=$event_id";
        }
        
        if ($conn->query($sql)) {
            $_SESSION['success'] = "Event updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating event: " . $conn->error;
        }
    }
    
    header("Location: admin-event.php");
    exit();
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $event_id = intval($_GET['delete']);
    
    $check_result = $conn->query("SELECT banner, gform_link FROM events WHERE id='$event_id'");
    
    if ($check_result && $check_result->num_rows > 0) {
        $event_data = $check_result->fetch_assoc();
        
        if($event_data['banner'] != 'no-image-available.png' && file_exists($event_data['banner'])) {
            unlink($event_data['banner']);
        }
        
        $sql = "DELETE FROM events WHERE id=$event_id";
        
        if ($conn->query($sql)) {
            $_SESSION['success'] = "Event deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting event: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = "Event not found.";
    }
    
    header("Location: admin-event.php");
    exit();
}

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

if (!file_exists('uploads/events')) {
    mkdir('uploads/events', 0777, true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Events</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="css/admin-event.css">
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
              <div class="profile-name">ADMIN</div>
          </div>

          <a href="admin-home.php"><img src="images/home.png" alt="Home"><span>Home</span></a>
          <a href="admin-gallery.php"><img src="images/gallery.png" alt="Gallery"><span>Gallery</span></a>
          <a href="admin-course-list.php"><img src="images/course-list.png" alt="Course List"><span>Course List</span></a>
          <a href="admin-alumni-list.php"><img src="images/alumni_list.png" alt="Alumni List"><span>Alumni List</span></a>
          <a href="admin-alumni-upload.php"><img src="images/upload.png" alt="Alumni Upload"><span>Alumni Upload</span></a>
          <a href="admin-job.php"><img src="images/jobs.png" alt="Jobs"><span>Jobs</span></a>
          <a href="admin-event.php" class="active"> <img src="images/calendar.png" alt="Events"><span>Events</span></a>
          <a href="admin-forums.php"><img src="images/forums.png" alt="Forum"><span>Forum</span></a>
          <a href="admin-system-setting.php"><img src="images/settings.png" alt="System Settings"><span>System Settings</span></a>
          <a href="landing.php"><img src="images/log-out.png" alt="Log Out"><span>Log Out</span></a>
      </div>
  </div>

  <main class="content">
      <div class="admin-event-content">
          <header>Event Management</header>
          <hr>
          
          <?php if(isset($_SESSION['success'])): ?>
              <div class="alert-success">
                  <?php 
                      echo $_SESSION['success']; 
                      unset($_SESSION['success']);
                  ?>
              </div>
          <?php endif; ?>
          
          <?php if(isset($_SESSION['error'])): ?>
              <div class="alert-error">
                  <?php 
                      echo $_SESSION['error']; 
                      unset($_SESSION['error']);
                  ?>
              </div>
          <?php endif; ?>
          
          <div class="top-actions">
              <div class="left-controls">
                  <div class="entries-search-wrapper">
                      <form method="GET" action="admin-event.php" class="search-form">
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
                              <input type="text" name="search" class="search-input" value="<?php echo htmlspecialchars($search); ?>" placeholder="Event title, description...">
                              <button type="submit" class="alist-filter">Filter</button>
                          </div>
                      </form>
                  </div>
              </div>

              <div class="right-controls">
                  <button class="new-button" id="openAddModal">+ New Event</button>
              </div>
          </div>

          <div class="event-stats">
              <div class="stat-box">
                  <span class="stat-number"><?php echo $total_records; ?></span>
                  <span class="stat-label">Total Events</span>
              </div>
              <?php if (!empty($search)): ?>
              <div class="stat-box filtered">
                  <span class="stat-number">1</span>
                  <span class="stat-label">Active Filter</span>
              </div>
              <?php endif; ?>
          </div>

          <div class="table-wrapper">
              <table class="event-table">
                  <thead>
                      <tr>
                          <th>#</th>
                          <th>Banner</th>
                          <th>Title</th>
                          <th>Schedule</th>
                          <th>Description</th>
                          <th>Google Form</th>
                          <th>Action</th>
                      </tr>
                  </thead>
                  <tbody>
                      <?php 
                      if($result && $result->num_rows > 0):
                          $count = $offset + 1;
                          while($row = $result->fetch_assoc()):
                              $content = strip_tags($row['content']);
                              $short_content = strlen($content) > 100 ? substr($content, 0, 100) . '...' : $content;
                              $banner_path = $row['banner'];
                              if(!file_exists($banner_path) || empty($banner_path)) {
                                  $banner_path = "images/no-image-available.png";
                              }
                      ?>
                      <tr>
                          <td><?php echo $count++; ?></td>
                          <td>
                              <img src="<?php echo $banner_path; ?>" alt="Event Banner" class="event-banner-thumb">
                          </td>
                          <td class="event-title"><?php echo htmlspecialchars($row['title']); ?></td>
                          <td class="event-date">
                              <?php echo date('M d, Y h:i A', strtotime($row['schedule'])); ?>
                          </td>
                          <td class="event-description"><?php echo htmlspecialchars($short_content); ?></td>
                          <td class="event-gform">
                              <?php if(!empty($row['gform_link'])): ?>
                                  <a href="<?php echo htmlspecialchars($row['gform_link']); ?>" target="_blank" class="gform-link">
                                      <i class="fas fa-link"></i> Form Link
                                  </a>
                              <?php else: ?>
                                  <span class="no-link">No form link</span>
                              <?php endif; ?>
                          </td>
                          <td>
                              <button class="view-btn" data-id="<?php echo $row['id']; ?>" 
                                      data-title="<?php echo htmlspecialchars($row['title']); ?>"
                                      data-content="<?php echo htmlspecialchars($row['content']); ?>"
                                      data-schedule="<?php echo $row['schedule']; ?>"
                                      data-banner="<?php echo $banner_path; ?>"
                                      data-gform="<?php echo htmlspecialchars($row['gform_link'] ?? ''); ?>"
                                      onclick="viewEvent(this)">View</button>
                              <button class="edit-btn" data-id="<?php echo $row['id']; ?>"
                                      data-title="<?php echo htmlspecialchars($row['title']); ?>"
                                      data-content="<?php echo htmlspecialchars($row['content']); ?>"
                                      data-schedule="<?php echo $row['schedule']; ?>"
                                      data-banner="<?php echo $banner_path; ?>"
                                      data-gform="<?php echo htmlspecialchars($row['gform_link'] ?? ''); ?>"
                                      onclick="editEvent(this)">Edit</button>
                              <button class="delete-btn" onclick="confirmDelete(<?php echo $row['id']; ?>)">Delete</button>
                          </td>
                      </tr>
                      <?php 
                          endwhile;
                      else:
                      ?>
                      <tr>
                          <td colspan="7" style="text-align: center;">No events found</td>
                      </tr>
                      <?php endif; ?>
                  </tbody>
              </table>
          </div>

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
      </div>
  </main>

  <div class="event-modal" id="viewEventModal">
      <div class="event-modal-content">
          <h2>Event Details</h2>
          <div class="event-banner-container">
              <img id="modalBanner" src="" alt="Event Banner">
          </div>
          <p><strong>Title:</strong> <span id="modalTitle"></span></p>
          <p><strong>Schedule:</strong> <span id="modalSchedule"></span></p>
          <div id="modalGform"></div>
          <hr>
          <h3>Description:</h3>
          <div id="modalContent"></div>
          <button class="close-job-modal" id="closeViewModal">Close</button>
      </div>
  </div>

  <div class="event-modal" id="editEventModal">
      <div class="event-modal-content">
          <h2>Edit Event</h2>
          <form id="editEventForm" method="POST" action="admin-event.php" enctype="multipart/form-data">
              <input type="hidden" id="editEventId" name="event_id">
              <input type="hidden" name="action" value="edit">

              <label for="editTitle">Title</label>
              <input type="text" id="editTitle" name="title" class="edit-input" required>

              <label for="editSchedule">Schedule (Date and Time)</label>
              <input type="datetime-local" id="editSchedule" name="schedule" class="date-input" required>
              
              <label for="editBanner">Banner Image</label>
              <div class="form-group">
                  <input type="file" id="editBanner" name="banner" class="file-input" accept="image/jpeg, image/png">
                  <div class="banner-preview">
                      <img id="currentBanner" src="#" style="max-width: 100%; max-height: 200px;">
                  </div>
              </div>
              <small>Leave empty to keep current image</small>
              
              <label for="editGformLink">Google Form Link (Optional)</label>
              <input type="url" id="editGformLink" name="gform_link" class="edit-input" placeholder="https://forms.google.com/...">
              
              <label for="editContent">Description</label>
              <textarea id="editContent" name="content" class="edit-textarea" required></textarea>

              <div class="modal-buttons">
                  <button type="submit">Save</button>
                  <button type="button" id="cancelEditBtn" class="cancel-btn">Cancel</button>
              </div>
          </form>
      </div>
  </div>

  <div class="event-modal" id="addEventModal">
      <div class="event-modal-content">
          <h2>Add New Event</h2>
          <form id="addEventForm" method="POST" action="admin-event.php" enctype="multipart/form-data">
              <input type="hidden" name="action" value="add">

              <label for="addTitle">Title</label>
              <input type="text" id="addTitle" name="title" class="edit-input" placeholder="Enter event title" required>

              <label for="addSchedule">Schedule (Date and Time)</label>
              <input type="datetime-local" id="addSchedule" name="schedule" class="date-input" required>
              
              <label for="addBanner">Banner Image</label>
              <div class="form-group">
                  <input type="file" id="addBanner" name="banner" class="file-input" accept="image/jpeg, image/png">
                  <div class="banner-preview">
                      <img id="newBannerPreview" src="#" style="display: none; max-width: 100%; max-height: 200px;">
                  </div>
              </div>
              
              <label for="addGformLink">Google Form Link (Optional)</label>
              <input type="url" id="addGformLink" name="gform_link" class="edit-input" placeholder="https://forms.google.com/...">
              
              <label for="addContent">Description</label>
              <textarea id="addContent" name="content" class="edit-textarea" placeholder="Enter event description" required></textarea>

              <div class="modal-buttons">
                  <button type="submit">Save</button>
                  <button type="button" id="cancelAddBtn" class="cancel-btn">Cancel</button>
              </div>
          </form>
      </div>
  </div>

  <div class="event-modal" id="deleteEventModal">
      <div class="event-modal-content">
          <h2>Delete Event</h2>
          <p>Are you sure you want to delete this event? This action cannot be undone.</p>
          <div class="modal-buttons">
              <button id="confirmDeleteBtn">Delete</button>
              <button id="cancelDeleteBtn" class="cancel-btn">Cancel</button>
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

      const viewModal = document.getElementById("viewEventModal");
      const editModal = document.getElementById("editEventModal");
      const addModal = document.getElementById("addEventModal");
      const deleteModal = document.getElementById("deleteEventModal");
      
      document.getElementById('closeViewModal').addEventListener("click", () => {
          viewModal.style.display = "none";
      });
      
      document.getElementById('cancelEditBtn').addEventListener("click", () => {
          editModal.style.display = "none";
      });
      
      document.getElementById('openAddModal').addEventListener("click", () => {
          document.getElementById('addEventForm').reset();
          document.getElementById('newBannerPreview').src = "images/no-image-available.png";
          addModal.style.display = "flex";
      });
      
      document.getElementById('cancelAddBtn').addEventListener("click", () => {
          addModal.style.display = "none";
      });

      let deleteId = null;
      function confirmDelete(id) {
          deleteId = id;
          deleteModal.style.display = "flex";
      }
      
      document.getElementById('confirmDeleteBtn').addEventListener("click", () => {
          if(deleteId) {
              // Proceed with the deletion regardless of Google Form link presence
              window.location.href = `admin-event.php?delete=${deleteId}`;
          }
      });
      
      document.getElementById('cancelDeleteBtn').addEventListener("click", () => {
          deleteModal.style.display = "none";
      });

      function viewEvent(btn) {
          const title = btn.getAttribute('data-title');
          const content = btn.getAttribute('data-content');
          const schedule = new Date(btn.getAttribute('data-schedule')).toLocaleString('en-US', {
              weekday: 'long',
              year: 'numeric',
              month: 'long',
              day: 'numeric',
              hour: 'numeric',
              minute: 'numeric'
          });
          const banner = btn.getAttribute('data-banner');
          const gform = btn.getAttribute('data-gform');

          document.getElementById('modalTitle').textContent = title;
          document.getElementById('modalSchedule').textContent = schedule;
          document.getElementById('modalContent').innerHTML = content;
          document.getElementById('modalBanner').src = banner;
          
          // Handle Google Form link
          const gformContainer = document.getElementById('modalGform');
          if (gform && gform.trim() !== '') {
              gformContainer.innerHTML = `<p><strong>Google Form:</strong> <a href="${gform}" target="_blank" class="gform-link">Registration Form <i class="fas fa-external-link-alt"></i></a></p>`;
          } else {
              gformContainer.innerHTML = `<p><strong>Google Form:</strong> <span class="no-link">No registration form available</span></p>`;
          }

          viewModal.style.display = "flex";
      }

      function editEvent(btn) {
          const id = btn.getAttribute('data-id');
          const title = btn.getAttribute('data-title');
          const content = btn.getAttribute('data-content');
          const schedule = btn.getAttribute('data-schedule');
          const banner = btn.getAttribute('data-banner');
          const gform = btn.getAttribute('data-gform');
          
          const dateObj = new Date(schedule);
          const formattedDate = dateObj.toISOString().slice(0, 16);

          document.getElementById('editEventId').value = id;
          document.getElementById('editTitle').value = title;
          document.getElementById('editSchedule').value = formattedDate;
          document.getElementById('editContent').value = content;
          document.getElementById('currentBanner').src = banner;
          document.getElementById('editGformLink').value = gform || '';

          editModal.style.display = "flex";
      }
      
      document.getElementById('addBanner').addEventListener('change', function(e) {
          const file = e.target.files[0];
          if (file) {
              const reader = new FileReader();
              reader.onload = function(event) {
                  document.getElementById('newBannerPreview').src = event.target.result;
              };
              reader.readAsDataURL(file);
          }
      });
      
      document.getElementById('editBanner').addEventListener('change', function(e) {
          const file = e.target.files[0];
          if (file) {
              const reader = new FileReader();
              reader.onload = function(event) {
                  document.getElementById('currentBanner').src = event.target.result;
              };
              reader.readAsDataURL(file);
          }
      });
      
      window.addEventListener("click", (e) => {
          if (e.target === viewModal) {
              viewModal.style.display = "none";
          }
          if (e.target === editModal) {
              editModal.style.display = "none";
          }
          if (e.target === addModal) {
              addModal.style.display = "none";
          }
          if (e.target === deleteModal) {
              deleteModal.style.display = "none";
          }
      });
      
      document.addEventListener('DOMContentLoaded', function() {
          const alerts = document.querySelectorAll('.alert-success, .alert-error');
          alerts.forEach(function(alert) {
              setTimeout(function() {
                  alert.style.opacity = '0';
                  setTimeout(function() {
                      alert.style.display = 'none';
                  }, 500);
              }, 5000);
          });
      });
  </script>
</body>
</html>
