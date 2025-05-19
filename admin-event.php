<?php
session_start();
include 'admin/db_connect.php';
include 'archive_functions.php';

date_default_timezone_set('Asia/Manila'); 

$check_archive_table = $conn->query("SHOW TABLES LIKE 'archived_events'");
if ($check_archive_table->num_rows == 0) {
    $create_archive_table = "CREATE TABLE archived_events (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        schedule DATETIME NOT NULL,
        banner VARCHAR(255) NOT NULL DEFAULT 'no-image.jpg',
        gform_link VARCHAR(255),
        archived_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        original_id INT(11)
    )";
    
    if (!$conn->query($create_archive_table)) {
        $_SESSION['error'] = "Error creating archived events table: " . $conn->error;
    }
}

$check_archive_date = $conn->query("SHOW COLUMNS FROM events LIKE 'archive_date'");
if ($check_archive_date->num_rows == 0) {
    $add_archive_date = "ALTER TABLE events ADD COLUMN archive_date DATETIME NULL";
    
    if (!$conn->query($add_archive_date)) {
        $_SESSION['error'] = "Error adding archive_date column: " . $conn->error;
    }
}

$archive_result = archive_expired_events($conn);

if ($archive_result['archived_count'] > 0) {
    $_SESSION['success'] = $archive_result['archived_count'] . " events were automatically archived.";
}

if (!empty($archive_result['errors'])) {
    $_SESSION['debug_archive'] = $archive_result['errors'];
}

$_SESSION['archive_debug'] = [
    'timezone' => $archive_result['debug']['php_timezone'],
    'current_time' => $archive_result['debug']['current_time_readable'],
    'mysql_time' => $archive_result['debug']['mysql_server_time'] ?? 'Not available',
    'details' => array_slice($archive_result['details'], 0, 3) 
];

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $schedule = $conn->real_escape_string($_POST['schedule']);
    $gform_link = $conn->real_escape_string($_POST['gform_link']);
    
    if (!empty($_POST['archive_date'])) {
        $archive_date_value = $_POST['archive_date'];
        
        if (strpos($archive_date_value, 'T') !== false) {
            $archive_date_value = str_replace('T', ' ', $archive_date_value);
            
            if (strpos($archive_date_value, '23:59') !== false) {
                $archive_date_value .= ':59';
            } else {
                $archive_date_value .= ':00';
            }
        }
        
        if (strlen($archive_date_value) <= 10 || strpos($archive_date_value, ':') === false) {
            $archive_date_value .= ' 23:59:59';
        }
        
        $archive_date = "'" . $conn->real_escape_string($archive_date_value) . "'";
        
        $_SESSION['debug_archive_date'] = "Archive date set to: " . $archive_date_value;
    } else {
        $archive_date = "NULL";
    }

    if ($action == 'edit_no_image') {
        $event_id = intval($_POST['event_id']);
        
        $sql = "UPDATE events SET 
                title='$title', 
                content='$content', 
                schedule='$schedule', 
                gform_link='$gform_link', 
                archive_date=$archive_date 
                WHERE id=$event_id";
        
        if ($conn->query($sql)) {
            $_SESSION['success'] = "Event updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating event: " . $conn->error;
        }
        
        header("Location: admin-event.php");
        exit();
    }
    
    $banner_img = "";
    $upload_new_banner = false;
    $keep_existing = isset($_POST['keep_existing_banner']) && $_POST['keep_existing_banner'] == '1';
    
    $file_info = '';
    if(isset($_FILES['banner'])) {
        $file_info = "File error code: " . $_FILES['banner']['error'] . 
                   ", File name: " . $_FILES['banner']['name'] . 
                   ", File size: " . $_FILES['banner']['size'] . 
                   ", Keep existing: " . ($keep_existing ? 'yes' : 'no');
    }
    
    if(isset($_FILES['banner']) && $_FILES['banner']['error'] === 0 && !empty($_FILES['banner']['name'])) {
        $upload_new_banner = true;
        $keep_existing = false;
        
        $allowed = array('jpg' => 'image/jpg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif');
        $filename = $_FILES['banner']['name'];
        $filetype = $_FILES['banner']['type'];
        $filesize = $_FILES['banner']['size'];
        
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if(!array_key_exists($ext, $allowed)) {
            $_SESSION['error'] = "Error: Please select a valid image format. Debug: $file_info";
            header("Location: admin-event.php");
            exit();
        }
        
        $maxsize = 5 * 1024 * 1024;
        if($filesize > $maxsize) {
            $_SESSION['error'] = "Error: File size is larger than the allowed limit (5MB). Debug: $file_info";
            header("Location: admin-event.php");
            exit();
        }
        
        if(in_array($filetype, $allowed)) {
            $new_filename = time() . '_' . $_FILES['banner']['name'];
            
            if(move_uploaded_file($_FILES['banner']['tmp_name'], 'uploads/events/'.$new_filename)){
                $banner_img = 'uploads/events/' . $new_filename;
            } else {
                $_SESSION['error'] = "Error uploading file. Debug: $file_info";
                header("Location: admin-event.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Error: Please select a valid image format. Debug: $file_info";
            header("Location: admin-event.php");
            exit();
        }
    } else if (isset($_FILES['banner']) && $_FILES['banner']['error'] !== 0 && $_FILES['banner']['error'] !== 4) {
        $_SESSION['error'] = "File upload error occurred. Error code: " . $_FILES['banner']['error'] . ". Debug: $file_info";
        header("Location: admin-event.php");
        exit();
    }
    
    if ($action == 'add') {
        $banner_sql = $upload_new_banner ? "'$banner_img'" : "'no-image.jpg'";
        $sql = "INSERT INTO events (title, content, schedule, banner, gform_link, archive_date) 
                VALUES ('$title', '$content', '$schedule', $banner_sql, '$gform_link', $archive_date)";
        
        if ($conn->query($sql)) {
            $_SESSION['success'] = "Event added successfully!";
        } else {
            $_SESSION['error'] = "Error adding event: " . $conn->error;
        }
    } elseif ($action == 'edit') {
        $event_id = intval($_POST['event_id']);
        
        if($upload_new_banner) {
            $curr_banner = $conn->query("SELECT banner FROM events WHERE id='$event_id'")->fetch_assoc()['banner'];
            if($curr_banner != 'no-image.jpg' && file_exists($curr_banner)) {
                unlink($curr_banner);
            }
            
            $sql = "UPDATE events SET title='$title', content='$content', 
                    schedule='$schedule', banner='$banner_img', gform_link='$gform_link', archive_date=$archive_date 
                    WHERE id=$event_id";
        } else {
            $sql = "UPDATE events SET title='$title', content='$content', 
                    schedule='$schedule', gform_link='$gform_link', archive_date=$archive_date 
                    WHERE id=$event_id";
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
        
        if($event_data['banner'] != 'no-image.jpg' && file_exists($event_data['banner'])) {
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

if (isset($_GET['delete_archived']) && is_numeric($_GET['delete_archived'])) {
    $archived_id = intval($_GET['delete_archived']);
    
    $check_result = $conn->query("SELECT banner FROM archived_events WHERE id='$archived_id'");
    
    if ($check_result && $check_result->num_rows > 0) {
        $event_data = $check_result->fetch_assoc();
        
        if($event_data['banner'] != 'no-image.jpg' && file_exists($event_data['banner'])) {
            unlink($event_data['banner']);
        }
        
        $sql = "DELETE FROM archived_events WHERE id=$archived_id";
        
        if ($conn->query($sql)) {
            $_SESSION['success'] = "Archived event deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting archived event: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = "Archived event not found.";
    }
    
    header("Location: admin-event.php?view=archived");
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

$view_archived = isset($_GET['view']) && $_GET['view'] == 'archived';
$table_name = $view_archived ? 'archived_events' : 'events';

$sql = "SELECT * FROM $table_name $search_condition ORDER BY " . ($view_archived ? "archived_date" : "schedule") . " DESC LIMIT $offset, $items_per_page";
$result = $conn->query($sql);

$total_sql = "SELECT COUNT(*) as total FROM $table_name $search_condition";
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
          <a href="admin-officers.php"><img src="images/users.png" alt="Officers"><span>Officers</span></a>
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
          
          <?php if(isset($_SESSION['debug_archive_date'])): ?>
              <div class="alert-info" style="background-color: #d9edf7; color: #31708f; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                  <?php 
                      echo $_SESSION['debug_archive_date']; 
                      unset($_SESSION['debug_archive_date']);
                  ?>
              </div>
          <?php endif; ?>
          
          <?php if(isset($_SESSION['debug_archive'])): ?>
              <div class="alert-warning" style="background-color: #fcf8e3; color: #8a6d3b; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                  <h4>Archive Process Warnings:</h4>
                  <ul>
                  <?php 
                      foreach($_SESSION['debug_archive'] as $error) {
                          echo "<li>$error</li>";
                      }
                      unset($_SESSION['debug_archive']);
                  ?>
                  </ul>
              </div>
          <?php endif; ?>
          
          <?php if(isset($_SESSION['archive_debug'])): ?>
              <div class="alert-info" style="background-color: #d9edf7; color: #31708f; padding: 15px; margin-bottom: 20px; border-radius: 4px; font-size: 0.9em;">
                  <h4>Archive Process Debug Information:</h4>
                  <p><strong>Server Timezone:</strong> <?php echo $_SESSION['archive_debug']['timezone']; ?></p>
                  <p><strong>Current Time:</strong> <?php echo $_SESSION['archive_debug']['current_time']; ?></p>
                  <p><strong>MySQL Time:</strong> <?php echo $_SESSION['archive_debug']['mysql_time']; ?></p>
                  
                  <?php if(!empty($_SESSION['archive_debug']['details'])): ?>
                  <h5>Event Details:</h5>
                  <?php foreach($_SESSION['archive_debug']['details'] as $detail): ?>
                      <div style="margin-bottom: 10px; padding: 5px; border-left: 3px solid #31708f;">
                          <p><strong>Event:</strong> <?php echo htmlspecialchars($detail['title']); ?> (ID: <?php echo $detail['id']; ?>)</p>
                          <p><strong>Archive Date:</strong> <?php echo htmlspecialchars($detail['archive_time_readable']); ?></p>
                          
                          <?php if(isset($detail['debug_time'])): ?>
                          <p><strong>Should Archive:</strong> <?php echo $detail['debug_time']['should_archive']; ?></p>
                          <p><strong>Reason:</strong> <?php echo $detail['debug_time']['reason']; ?></p>
                          <p><strong>Time Difference:</strong> <?php echo $detail['debug_time']['time_diff_seconds']; ?> seconds</p>
                          <?php endif; ?>
                          
                          <?php if(isset($detail['status'])): ?>
                          <p><strong>Status:</strong> <?php echo $detail['status']; ?></p>
                          <?php endif; ?>
                      </div>
                  <?php endforeach; ?>
                  <?php endif; ?>
                  
                  <button type="button" onclick="this.parentNode.style.display='none';" style="background: #31708f; color: white; border: none; padding: 5px 10px; cursor: pointer;">Hide Details</button>
              </div>
              <?php unset($_SESSION['archive_debug']); ?>
          <?php endif; ?>
          
          <div class="event-tabs">
              <button class="tab-btn <?php echo !isset($_GET['view']) || $_GET['view'] != 'archived' ? 'active' : ''; ?>" onclick="location.href='admin-event.php'">Active Events</button>
              <button class="tab-btn <?php echo isset($_GET['view']) && $_GET['view'] == 'archived' ? 'active' : ''; ?>" onclick="location.href='admin-event.php?view=archived'">Archived Events</button>
          </div>
          
          <div class="top-actions">
              <div class="left-controls">
                  <div class="entries-search-wrapper">
                      <form method="GET" action="admin-event.php" class="search-form">
                          <?php if ($view_archived): ?>
                          <input type="hidden" name="view" value="archived">
                          <?php endif; ?>
                          <span>Show</span>
                          <select name="limit" onchange="this.form.submit()">
                              <option value="10" <?php echo $items_per_page == 10 ? 'selected' : ''; ?>>10</option>
                              <option value="25" <?php echo $items_per_page == 25 ? 'selected' : ''; ?>>25</option>
                              <option value="50" <?php echo $items_per_page == 50 ? 'selected' : ''; ?>>50</option>
                          </select>
                          <span>Entries</span>
                          <span class="search-label">Search:</span>
                          <input type="text" name="search" class="search-input" value="<?php echo htmlspecialchars($search); ?>" placeholder="Event title, description...">
                          <button type="submit" class="alist-filter">Search</button>
                      </form>
                  </div>
              </div>

              <div class="right-controls">
                  <?php if (!$view_archived): ?>
                  <button class="new-button" id="openAddModal">+ New Event</button>
                  <?php endif; ?>
              </div>
          </div>

          <div class="event-stats">
              <div class="stat-box">
                  <span class="stat-number"><?php echo $total_records; ?></span>
                  <span class="stat-label"><?php echo $view_archived ? 'Archived Events' : 'Active Events'; ?></span>
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
                          <?php if ($view_archived): ?>
                          <th>Archived Date</th>
                          <?php endif; ?>
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
                                  $banner_path = "images/no-image.jpg";
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
                          <?php if ($view_archived): ?>
                          <td class="event-date">
                              <?php echo date('M d, Y h:i A', strtotime($row['archived_date'])); ?>
                          </td>
                          <?php endif; ?>
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
                                      <?php if (!$view_archived): ?>
                                      data-archive-date="<?php echo htmlspecialchars($row['archive_date'] ?? ''); ?>"
                                      <?php else: ?>
                                      data-archived-date="<?php echo htmlspecialchars($row['archived_date']); ?>"
                                      <?php endif; ?>
                                      onclick="<?php echo $view_archived ? 'viewArchivedEvent(this)' : 'viewEvent(this)'; ?>">View</button>
                              
                              <?php if (!$view_archived): ?>
                              <button class="edit-btn" data-id="<?php echo $row['id']; ?>"
                                      data-title="<?php echo htmlspecialchars($row['title']); ?>"
                                      data-content="<?php echo htmlspecialchars($row['content']); ?>"
                                      data-schedule="<?php echo $row['schedule']; ?>"
                                      data-banner="<?php echo $banner_path; ?>"
                                      data-gform="<?php echo htmlspecialchars($row['gform_link'] ?? ''); ?>"
                                      data-archive-date="<?php echo htmlspecialchars($row['archive_date'] ?? ''); ?>"
                                      onclick="editEvent(this)">Edit</button>
                              <button class="delete-btn" onclick="confirmDelete(<?php echo $row['id']; ?>)">Delete</button>
                              <?php else: ?>
                              <button class="delete-btn" onclick="confirmDeleteArchived(<?php echo $row['id']; ?>)">Delete</button>
                              <?php endif; ?>
                          </td>
                      </tr>
                      <?php 
                          endwhile;
                      else:
                      ?>
                      <tr>
                          <td colspan="<?php echo $view_archived ? '8' : '7'; ?>" style="text-align: center;">
                              <?php echo $view_archived ? 'No archived events found' : 'No events found'; ?>
                          </td>
                      </tr>
                      <?php endif; ?>
                  </tbody>
              </table>
          </div>

          <div class="list-foot">
              <div class="pagination-info">
                  <?php if (!empty($search)): ?>
                  <span class="filter-notice">Filtered results</span>
                  <?php endif; ?>
              </div>
              <div class="pagination">
                  <button <?php echo $page <= 1 ? 'disabled' : ''; ?> 
                          onclick="changePage(<?php echo $page - 1; ?>)">Previous</button>
                  
                  <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                      <button <?php echo $i == $page ? 'class="active"' : ''; ?> 
                              onclick="changePage(<?php echo $i; ?>)"><?php echo $i; ?></button>
                  <?php endfor; ?>
                  
                  <button <?php echo $page >= $total_pages ? 'disabled' : ''; ?> 
                          onclick="changePage(<?php echo $page + 1; ?>)">Next</button>
              </div>
          </div>
      </div>
  </main>

  <div class="event-modal" id="viewEventModal">
      <div class="event-modal-content">
          <div class="modal-header">
              <h2 id="viewModalTitle"></h2>
              <span class="close-modal" id="closeViewModal">&times;</span>
          </div>
          
          <div class="event-banner-container">
              <img id="modalBanner" src="" alt="Event Banner">
          </div>
          
          <div class="event-details">
              <div class="detail-row">
                  <div class="detail-icon">
                      <i class="fas fa-calendar-alt"></i>
                  </div>
                  <div class="detail-content">
                      <h4>Schedule</h4>
                      <p id="modalSchedule"></p>
                  </div>
              </div>
              
              <div class="detail-row" id="archiveDateContainer" style="display: none;">
                  <div class="detail-icon">
                      <i class="fas fa-archive"></i>
                  </div>
                  <div class="detail-content">
                      <h4>Auto-Archive Date</h4>
                      <p id="modalArchiveDate"></p>
                  </div>
              </div>
              
              <div class="detail-row" id="gformContainer">
                  <div class="detail-icon">
                      <i class="fas fa-link"></i>
                  </div>
                  <div class="detail-content" id="modalGform">
                      <h4>Registration</h4>
                  </div>
              </div>
              
              <div class="detail-row">
                  <div class="detail-icon">
                      <i class="fas fa-align-left"></i>
                  </div>
                  <div class="detail-content">
                      <h4>Description</h4>
                      <div class="event-description-content" id="modalContent"></div>
                  </div>
              </div>
          </div>
      </div>
  </div>

  <div class="event-modal" id="editEventModal">
      <div class="event-modal-content">
          <h2>Edit Event</h2>
          <form id="editEventForm" method="POST" action="admin-event.php" enctype="multipart/form-data">
              <input type="hidden" id="editEventId" name="event_id">
              <input type="hidden" name="action" value="edit">
              <input type="hidden" name="keep_existing_banner" value="1">
              
              <label for="editTitle">Title</label>
              <input type="text" id="editTitle" name="title" class="edit-input" required>

              <label for="editSchedule">Schedule (Date and Time)</label>
              <input type="datetime-local" id="editSchedule" name="schedule" class="date-input" required>
              
              <label for="editArchiveDate">Auto-Archive Date and Time <small>(event will be moved to archive after this date/time)</small></label>
              <input type="datetime-local" id="editArchiveDate" name="archive_date" class="date-input">
              
              <label for="editBanner">Banner Image <small>(Optional)</small></label>
              <div class="form-group">
                  <input type="file" id="editBanner" name="banner" class="file-input" accept="image/jpeg, image/png">
                  <div class="banner-preview">
                      <img id="currentBanner" src="#" style="max-width: 100%; max-height: 200px;">
                  </div>
                  <div class="keep-existing">
                      <input type="checkbox" id="keepExistingCheckbox" checked disabled>
                      <label for="keepExistingCheckbox" class="checkbox-label">Keep existing image if no new file is selected</label>
                  </div>
              </div>
              <small class="note-text">Leave empty to keep current image</small>
              
              <label for="editGformLink">Google Form Link (Optional)</label>
              <input type="url" id="editGformLink" name="gform_link" class="edit-input" placeholder="https://forms.google.com/...">
              
              <label for="editContent">Description</label>
              <textarea id="editContent" name="content" class="edit-textarea" required></textarea>

              <div class="modal-buttons">
                  <button type="submit">Save with New Image</button>
                  <button type="button" id="saveWithoutImageBtn" class="save-no-image-btn">Save without Changing Image</button>
                  <button type="button" id="cancelEditBtn" class="cancel-btn">Cancel</button>
              </div>
          </form>
          
          <form id="editEventNoImageForm" method="POST" action="admin-event.php" style="display: none;">
              <input type="hidden" id="editEventIdNoImage" name="event_id">
              <input type="hidden" name="action" value="edit_no_image">
              <input type="hidden" id="editTitleNoImage" name="title">
              <input type="hidden" id="editScheduleNoImage" name="schedule">
              <input type="hidden" id="editArchiveDateNoImage" name="archive_date">
              <input type="hidden" id="editGformLinkNoImage" name="gform_link">
              <input type="hidden" id="editContentNoImage" name="content">
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
              
              <label for="addArchiveDate">Auto-Archive Date and Time <small>(event will be moved to archive after this date/time)</small></label>
              <input type="datetime-local" id="addArchiveDate" name="archive_date" class="date-input">
              
              <label for="addBanner">Banner Image</label>
              <div class="form-group">
                  <input type="file" id="addBanner" name="banner" class="file-input" accept="image/jpeg, image/png">
                  <div class="banner-preview">
                      <img id="newBannerPreview" src="images/no-image.jpg" style="display: none; max-width: 100%; max-height: 200px;">
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

      document.addEventListener('DOMContentLoaded', function() {
          // Initialize modal variables within DOMContentLoaded
          viewModal = document.getElementById("viewEventModal");
          editModal = document.getElementById("editEventModal");
          addModal = document.getElementById("addEventModal");
          window.deleteModal = document.getElementById("deleteEventModal");
          
          const editForm = document.getElementById('editEventForm');
          const fileInput = document.getElementById('editBanner');
          const keepExistingField = document.querySelector('input[name="keep_existing_banner"]');
          
          fileInput.addEventListener('change', function() {
              if(fileInput.files && fileInput.files.length > 0) {
                  keepExistingField.value = '0';
              } else {
                  keepExistingField.value = '1';
              }
          });
          
          document.getElementById('saveWithoutImageBtn').addEventListener('click', function() {
              const eventId = document.getElementById('editEventId').value;
              const title = document.getElementById('editTitle').value;
              const schedule = document.getElementById('editSchedule').value;
              const archiveDate = document.getElementById('editArchiveDate').value;
              const gformLink = document.getElementById('editGformLink').value;
              const content = document.getElementById('editContent').value;
              
              if (!title || !schedule || !content) {
                  alert('Please fill in all required fields');
                  return;
              }
              
              if (archiveDate) {
                  console.log('Archive date being used:', archiveDate);
              }
              
              document.getElementById('editEventIdNoImage').value = eventId;
              document.getElementById('editTitleNoImage').value = title;
              document.getElementById('editScheduleNoImage').value = schedule;
              document.getElementById('editArchiveDateNoImage').value = archiveDate;
              document.getElementById('editGformLinkNoImage').value = gformLink;
              document.getElementById('editContentNoImage').value = content;
              
              document.getElementById('editEventNoImageForm').submit();
          });
          
          editForm.addEventListener('submit', function(e) {
              console.log('Form is being submitted');
              
              if(fileInput.files && fileInput.files.length > 0) {
                  console.log('File selected:', fileInput.files[0].name);
                  console.log('Keep existing:', keepExistingField.value);
              } else {
                  console.log('No file selected');
                  console.log('Keep existing:', keepExistingField.value);
              }
              
              return true;
          });
      });

      function setMinDateTime() {
          const now = new Date();
          const year = now.getFullYear();
          const month = String(now.getMonth() + 1).padStart(2, '0');
          const day = String(now.getDate()).padStart(2, '0');
          const hours = String(now.getHours()).padStart(2, '0');
          const minutes = String(now.getMinutes()).padStart(2, '0');
          
          const minDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
          const minDate = `${year}-${month}-${day}`;
          
          document.getElementById('addSchedule').min = minDateTime;
          document.getElementById('addArchiveDate').min = minDateTime;
          
          const editScheduleInput = document.getElementById('editSchedule');
          const editArchiveDateInput = document.getElementById('editArchiveDate');
          
          if(editScheduleInput) {
              editScheduleInput.addEventListener('focus', function() {
                  if(!this.value || new Date(this.value) > now) {
                      this.min = minDateTime;
                  }
              });
          }
          
          if(editArchiveDateInput) {
              editArchiveDateInput.addEventListener('focus', function() {
                  this.min = minDate;
              });
          }
      }
      
      document.addEventListener('DOMContentLoaded', setMinDateTime);

      let viewModal;
      let editModal;
      let addModal;
      
      document.addEventListener('DOMContentLoaded', function() {
          const closeViewModalBtn = document.getElementById('closeViewModal');
          if (closeViewModalBtn) {
              closeViewModalBtn.addEventListener("click", () => {
                  viewModal.style.display = "none";
              });
          }
          
          document.getElementById('cancelEditBtn').addEventListener("click", () => {
              editModal.style.display = "none";
          });
          
          document.getElementById('openAddModal').addEventListener("click", () => {
              document.getElementById('addEventForm').reset();
              document.getElementById('newBannerPreview').src = "images/no-image.jpg";
              
              const now = new Date();
              const year = now.getFullYear();
              const month = String(now.getMonth() + 1).padStart(2, '0');
              const day = String(now.getDate()).padStart(2, '0');
              const hours = String(now.getHours()).padStart(2, '0');
              const minutes = String(now.getMinutes()).padStart(2, '0');
              const minDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
              
              document.getElementById('addSchedule').min = minDateTime;
              document.getElementById('addArchiveDate').min = minDateTime;
              
              addModal.style.display = "flex";
          });
          
          document.getElementById('cancelAddBtn').addEventListener("click", () => {
              addModal.style.display = "none";
          });
      });

      document.addEventListener('DOMContentLoaded', function() {
          let deleteId = null;
          let isArchived = false;
          
          window.confirmDelete = function(id) {
              deleteId = id;
              isArchived = false;
              document.getElementById('deleteEventModal').querySelector('h2').textContent = 'Delete Event';
              window.deleteModal.style.display = "flex";
              console.log('Delete active event:', id);
          };
          
          window.confirmDeleteArchived = function(id) {
              deleteId = id;
              isArchived = true;
              document.getElementById('deleteEventModal').querySelector('h2').textContent = 'Delete Archived Event';
              window.deleteModal.style.display = "flex";
              console.log('Delete archived event:', id);
          };
          
          document.getElementById('confirmDeleteBtn').addEventListener("click", () => {
              if(deleteId) {
                  if(isArchived) {
                      console.log('Redirecting to delete archived event:', deleteId);
                      window.location.href = `admin-event.php?delete_archived=${deleteId}&view=archived`;
                  } else {
                      console.log('Redirecting to delete event:', deleteId);
                      window.location.href = `admin-event.php?delete=${deleteId}`;
                  }
              }
          });
          
          document.getElementById('cancelDeleteBtn').addEventListener("click", () => {
              window.deleteModal.style.display = "none";
          });
      });

      document.addEventListener('DOMContentLoaded', function() {
              window.viewEvent = function(btn) {
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
              const archiveDate = btn.getAttribute('data-archive-date');
    
              document.getElementById('viewModalTitle').textContent = title;
              document.getElementById('modalSchedule').textContent = schedule;
              document.getElementById('modalContent').innerHTML = content;
              document.getElementById('modalBanner').src = banner;
              
              const archiveDateContainer = document.getElementById('archiveDateContainer');
              if (archiveDate && archiveDate.trim() !== '') {
                  const formattedArchiveDate = new Date(archiveDate).toLocaleString('en-US', {
                      year: 'numeric',
                      month: 'long',
                      day: 'numeric',
                      hour: 'numeric',
                      minute: 'numeric'
                  });
                  document.getElementById('modalArchiveDate').textContent = formattedArchiveDate;
                  document.getElementById('archiveDateContainer').querySelector('h4').textContent = 'Auto-Archive Date';
                  archiveDateContainer.style.display = 'flex';
              } else {
                  archiveDateContainer.style.display = 'none';
              }
              
              const gformContainer = document.getElementById('gformContainer');
              const gformContent = document.getElementById('modalGform');
              if (gform && gform.trim() !== '') {
                  gformContent.innerHTML = `
                    <h4>Registration</h4>
                    <p>
                      <a href="${gform}" target="_blank" class="gform-link">
                        Registration Form <i class="fas fa-external-link-alt"></i>
                      </a>
                    </p>`;
                  gformContainer.style.display = 'flex';
              } else {
                  gformContent.innerHTML = `
                    <h4>Registration</h4>
                    <p class="no-link">No registration form available</p>`;
                  gformContainer.style.display = 'flex';
              }
    
              viewModal.style.display = "flex";
          }
      });

      document.addEventListener('DOMContentLoaded', function() {
          window.editEvent = function(btn) {
              const id = btn.getAttribute('data-id');
              const title = btn.getAttribute('data-title');
              const content = btn.getAttribute('data-content');
              const schedule = btn.getAttribute('data-schedule');
              const banner = btn.getAttribute('data-banner');
              const gform = btn.getAttribute('data-gform');
              const archiveDate = btn.getAttribute('data-archive-date') || '';
              
              const dateObj = new Date(schedule);
              const formattedDate = dateObj.toISOString().slice(0, 16);
              const now = new Date();
    
              document.getElementById('editEventId').value = id;
              document.getElementById('editTitle').value = title;
              document.getElementById('editSchedule').value = formattedDate;
              document.getElementById('editContent').value = content;
              document.getElementById('currentBanner').src = banner;
              document.getElementById('editGformLink').value = gform || '';
              
              if (archiveDate && archiveDate.trim() !== '') {
                  const archiveDateObj = new Date(archiveDate);
                  const formattedArchiveDate = archiveDateObj.toISOString().slice(0, 16);
                  document.getElementById('editArchiveDate').value = formattedArchiveDate;
              } else {
                  document.getElementById('editArchiveDate').value = '';
              }
              
              const editScheduleInput = document.getElementById('editSchedule');
              if (dateObj > now) {
                  const year = now.getFullYear();
                  const month = String(now.getMonth() + 1).padStart(2, '0');
                  const day = String(now.getDate()).padStart(2, '0');
                  const hours = String(now.getHours()).padStart(2, '0');
                  const minutes = String(now.getMinutes()).padStart(2, '0');
                  const minDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
                  editScheduleInput.min = minDateTime;
                  document.getElementById('editArchiveDate').min = minDateTime;
              } else {
                  editScheduleInput.min = formattedDate;
                  const year = now.getFullYear();
                  const month = String(now.getMonth() + 1).padStart(2, '0');
                  const day = String(now.getDate()).padStart(2, '0');
                  const hours = String(now.getHours()).padStart(2, '0');
                  const minutes = String(now.getMinutes()).padStart(2, '0');
                  const minDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
                  document.getElementById('editArchiveDate').min = minDateTime;
              }
    
              editModal.style.display = "flex";
          }
      });
      
      document.addEventListener('DOMContentLoaded', function() {
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
      });
      
      document.addEventListener('DOMContentLoaded', function() {
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
              if (e.target === window.deleteModal) {
                  window.deleteModal.style.display = "none";
              }
          });
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

      function changePage(page) {
          const currentUrl = new URL(window.location.href);
          currentUrl.searchParams.set('page', page);
          window.location.href = currentUrl.toString();
      }

      document.addEventListener('DOMContentLoaded', function() {
          window.viewArchivedEvent = function(btn) {
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
              const archivedDate = btn.getAttribute('data-archived-date');
    
              document.getElementById('viewModalTitle').textContent = title;
              document.getElementById('modalSchedule').textContent = schedule;
              document.getElementById('modalContent').innerHTML = content;
              document.getElementById('modalBanner').src = banner;
              
              const archiveDateContainer = document.getElementById('archiveDateContainer');
              if (archivedDate && archivedDate.trim() !== '') {
                  const formattedArchivedDate = new Date(archivedDate).toLocaleString('en-US', {
                      year: 'numeric',
                      month: 'long',
                      day: 'numeric',
                      hour: 'numeric',
                      minute: 'numeric'
                  });
                  document.getElementById('modalArchiveDate').textContent = formattedArchivedDate;
                  document.getElementById('archiveDateContainer').querySelector('h4').textContent = 'Archived On';
                  archiveDateContainer.style.display = 'flex';
              } else {
                  archiveDateContainer.style.display = 'none';
              }
              
              const gformContainer = document.getElementById('gformContainer');
              const gformContent = document.getElementById('modalGform');
              if (gform && gform.trim() !== '') {
                  gformContent.innerHTML = `
                    <h4>Registration</h4>
                    <p>
                      <a href="${gform}" target="_blank" class="gform-link">
                        Registration Form <i class="fas fa-external-link-alt"></i>
                      </a>
                    </p>`;
                  gformContainer.style.display = 'flex';
              } else {
                  gformContent.innerHTML = `
                    <h4>Registration</h4>
                    <p class="no-link">No registration form available</p>`;
                  gformContainer.style.display = 'flex';
              }
    
              viewModal.style.display = "flex";
          }
      });
  </script>
</body>
</html>
