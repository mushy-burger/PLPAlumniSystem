<?php
session_start();
include 'admin/db_connect.php';

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = ($page - 1) * $items_per_page;

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$search_condition = '';
if (!empty($search)) {
    $search_condition = " WHERE ft.title LIKE '%$search%' OR ft.description LIKE '%$search%' ";
}

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == 'add') {
        $title = $conn->real_escape_string($_POST['title']);
        $description = $conn->real_escape_string($_POST['description']);
        
        $user_id = 1;
        
        $sql = "INSERT INTO forum_topics (title, description, user_id) VALUES ('$title', '$description', $user_id)";
        
        if ($conn->query($sql)) {
            $_SESSION['success'] = "Forum topic has been added successfully.";
        } else {
            $_SESSION['error'] = "Error adding forum topic: " . $conn->error;
        }
        
        header('Location: admin-forums.php');
        exit();
    }
    
    if ($action == 'edit') {
        $id = intval($_POST['topic_id']);
        $title = $conn->real_escape_string($_POST['title']);
        $description = $conn->real_escape_string($_POST['description']);
        
        $sql = "UPDATE forum_topics SET title = '$title', description = '$description' WHERE id = $id";
        
        if ($conn->query($sql)) {
            $_SESSION['success'] = "Forum topic has been updated successfully.";
        } else {
            $_SESSION['error'] = "Error updating forum topic: " . $conn->error;
        }
        
        header('Location: admin-forums.php');
        exit();
    }
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    $conn->query("DELETE FROM forum_comments WHERE topic_id = $id");
    
    $sql = "DELETE FROM forum_topics WHERE id = $id";
    
    if ($conn->query($sql)) {
        $_SESSION['success'] = "Forum topic and related comments have been deleted.";
    } else {
        $_SESSION['error'] = "Error deleting forum topic: " . $conn->error;
    }
    
    header('Location: admin-forums.php');
    exit();
}

$query = "SELECT ft.*, u.name as posted_by, 
          (SELECT COUNT(*) FROM forum_comments fc WHERE fc.topic_id = ft.id) as comment_count
          FROM forum_topics ft 
          LEFT JOIN users u ON ft.user_id = u.alumni_id
          $search_condition
          ORDER BY ft.date_created DESC 
          LIMIT $offset, $items_per_page";

$result = $conn->query($query);

$total_query = "SELECT COUNT(*) as total FROM forum_topics ft $search_condition";
$total_result = $conn->query($total_query);
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Alumni Portal - Forum Management</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="css/admin-forum.css" />
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
          <img src="images/avatar.png" alt="Profile Picture" />
        </a>
        <div class="profile-name">ADMIN</div>
      </div>
      <a href="admin-home.php"><img src="images/home.png" alt="Home"><span>Home</span></a>
      <a href="admin-gallery.php"><img src="images/gallery.png" alt="Gallery"><span>Gallery</span></a>
      <a href="admin-course-list.php"><img src="images/course-list.png" alt="Course List"><span>Course List</span></a>
      <a href="admin-alumni-list.php"><img src="images/alumni_list.png" alt="Alumni List"><span>Alumni List</span></a>
      <a href="admin-alumni-upload.php"><img src="images/upload.png" alt="Alumni Upload"><span>Alumni Upload</span></a>
      <a href="admin-job.php"><img src="images/jobs.png" alt="Jobs"><span>Jobs</span></a>
      <a href="admin-event.php"><img src="images/calendar.png" alt="Events"><span>Events</span></a>
      <a href="admin-forums.php" class="active"><img src="images/forums.png" alt="Forum"><span>Forum</span></a>
      <a href="admin-officers.php"><img src="images/officer.png" alt="Officers"><span>Officers</span></a>
      <a href="admin-system-setting.php"><img src="images/settings.png" alt="System Settings"><span>System Settings</span></a>
      <a href="landing.php"><img src="images/log-out.png" alt="Log Out"><span>Log Out</span></a>
    </div>
  </div>

  <main class="content">
    <div class="forum-container">
      <div class="forum-header">
        <h2>Forum Topics Management</h2>
      </div>
      
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

      <div class="controls-wrapper">
        <div class="table-controls">
          <div class="entries-control">
            <label for="entries">Show</label>
            <select id="entries" onchange="changeEntriesPerPage(this.value)">
              <option value="5" <?php echo $items_per_page == 5 ? 'selected' : ''; ?>>5</option>
              <option value="10" <?php echo $items_per_page == 10 ? 'selected' : ''; ?>>10</option>
              <option value="25" <?php echo $items_per_page == 25 ? 'selected' : ''; ?>>25</option>
              <option value="50" <?php echo $items_per_page == 50 ? 'selected' : ''; ?>>50</option>
            </select>
            <span>Entries</span>
          </div>

          <div class="search-control">
            <form method="GET" action="">
              <label for="search">Search:</label>
              <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by topic or description..." />
              <button type="submit" style="display:none;">Search</button>
            </form>
          </div>
        </div>

        <div class="add-topic-forum">
          <button onclick="openCreateModal()"><i class="plus-icon">+</i> Create New Post</button>
        </div>
      </div>

      <div class="table-wrapper">
        <table class="forum-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Topic</th>
              <th>Description</th>
              <th>Date Posted</th>
              <th>Comments</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            if($result && $result->num_rows > 0):
                $count = $offset + 1;
                while($row = $result->fetch_assoc()):
                    $description = strip_tags(html_entity_decode($row['description']));
                    $short_description = substr($description, 0, 100) . (strlen($description) > 100 ? '...' : '');
            ?>
            <tr>
              <td><?php echo $count++; ?></td>
              <td><?php echo htmlspecialchars($row['title']); ?></td>
              <td><?php echo $short_description; ?></td>
              <td><?php echo date('M d, Y', strtotime($row['date_created'])); ?></td>
              <td><?php echo $row['comment_count']; ?></td>
              <td>
                <div class="action-buttons-container">
                  <button class="view-btn" onclick="viewTopic(<?php echo $row['id']; ?>)">View</button>
                  <button class="edit-btn" onclick="editTopic(<?php echo $row['id']; ?>, '<?php echo addslashes($row['title']); ?>', '<?php echo addslashes(htmlspecialchars_decode($row['description'])); ?>')">Edit</button>
                  <button class="delete-btn" onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo addslashes($row['title']); ?>')">Delete</button>
                </div>
              </td>
            </tr>
            <?php 
                endwhile;
            else:
            ?>
            <tr>
              <td colspan="6">
                <div class="empty-state">
                  <p>No forum topics found</p>
                  <?php if (!empty($search)): ?>
                  <p>Try adjusting your search criteria</p>
                  <?php else: ?>
                  <p>Create a new topic to get started</p>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if($total_pages > 1): ?>
      <div class="pagination-container">
        <ul class="pagination">
          <?php if($page > 1): ?>
            <li><a href="?page=1&<?php echo build_query_params(['page']); ?>">First</a></li>
            <li><a href="?page=<?php echo $page-1; ?>&<?php echo build_query_params(['page']); ?>">Previous</a></li>
          <?php endif; ?>
          
          <?php
          $start_page = max(1, $page - 2);
          $end_page = min($total_pages, $start_page + 4);
          
          for($i = $start_page; $i <= $end_page; $i++):
          ?>
            <li class="<?php echo ($i == $page) ? 'active' : ''; ?>">
              <a href="?page=<?php echo $i; ?>&<?php echo build_query_params(['page']); ?>"><?php echo $i; ?></a>
            </li>
          <?php endfor; ?>
          
          <?php if($page < $total_pages): ?>
            <li><a href="?page=<?php echo $page+1; ?>&<?php echo build_query_params(['page']); ?>">Next</a></li>
            <li><a href="?page=<?php echo $total_pages; ?>&<?php echo build_query_params(['page']); ?>">Last</a></li>
          <?php endif; ?>
        </ul>
      </div>
      <?php endif; ?>
    </div>
  </main>

  <div id="create-post-modal" class="admin-forums-modal">
    <div class="admin-forums-modal-content">
      <button onclick="closeCreateModal()" class="admin-forums-close-button">&times;</button>

      <h2 id="modal-title" class="admin-forum-modal-title">Create New Forum Post</h2>
      <hr class="admin-forum-divider">

      <form id="forumPostForm" method="POST" action="admin-forums.php">
        <input type="hidden" id="action" name="action" value="add">
        <input type="hidden" id="topic_id" name="topic_id" value="">

        <div class="admin-forum-form-group">
          <label for="title">Topic</label>
          <input id="title" name="title" type="text" required>
        </div>

        <div class="admin-forum-form-group">
          <label for="description">Content</label>
          <textarea id="description" name="description" rows="6" required></textarea>
        </div>

        <div class="text-center">
          <button type="submit" class="admin-forum-submit">Submit</button>
        </div>
      </form>
    </div>
  </div>

  <div id="view-topic-modal" class="admin-forums-modal">
    <div class="admin-forums-modal-content view-forum-modal-content">
      <button onclick="closeViewModal()" class="admin-forums-close-button">&times;</button>

      <h2 id="view-title" class="view-forum-title"></h2>
      <div class="view-forum-meta">
        <span id="view-posted-by"></span>
        <span id="view-date"></span>
      </div>
      <hr>
      <div id="view-description" class="view-forum-description"></div>
      
      <div id="view-comments" class="view-forum-comments">
        <h3>Comments</h3>
        <div id="comments-container"></div>
      </div>
    </div>
  </div>

  <div id="delete-modal" class="admin-forums-modal">
    <div class="admin-forums-modal-content delete-confirm-modal">
      <h2>Delete Forum Topic</h2>
      <p>Are you sure you want to delete the topic "<span id="delete-topic-title"></span>"?</p>
      <p>This will also delete all comments associated with this topic.</p>
      
      <div class="btn-group">
        <button id="confirm-delete-btn" class="confirm-btn">Delete</button>
        <button onclick="closeDeleteModal()" class="cancel-btn">Cancel</button>
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

    function openCreateModal() {
      document.getElementById('modal-title').innerText = 'Create New Forum Post';
      document.getElementById('action').value = 'add';
      document.getElementById('topic_id').value = '';
      document.getElementById('title').value = '';
      document.getElementById('description').value = '';
      document.getElementById('forumPostForm').reset();
      document.getElementById('create-post-modal').classList.add('active');
    }

    function closeCreateModal() {
      document.getElementById('create-post-modal').classList.remove('active');
    }
    
    function editTopic(id, title, description) {
      document.getElementById('modal-title').innerText = 'Edit Forum Topic';
      document.getElementById('action').value = 'edit';
      document.getElementById('topic_id').value = id;
      document.getElementById('title').value = title;
      document.getElementById('description').value = description;
      document.getElementById('create-post-modal').classList.add('active');
    }
    
    function viewTopic(id) {
      fetch(`admin/get_topic.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
          document.getElementById('view-title').innerText = data.topic.title;
          document.getElementById('view-posted-by').innerText = `Posted by: ${data.topic.posted_by}`;
          document.getElementById('view-date').innerText = `Date: ${data.topic.formatted_date}`;
          document.getElementById('view-description').innerHTML = data.topic.description;
          
          const commentsContainer = document.getElementById('comments-container');
          commentsContainer.innerHTML = '';
          
          if (data.comments.length > 0) {
            data.comments.forEach(comment => {
              const commentDiv = document.createElement('div');
              commentDiv.className = 'comment-item';
              
              const commentMeta = document.createElement('div');
              commentMeta.className = 'comment-meta';
              commentMeta.innerHTML = `<span>${comment.name}</span><span>${comment.formatted_date}</span>`;
              
              const commentText = document.createElement('div');
              commentText.className = 'comment-text';
              commentText.innerText = comment.comment;
              
              commentDiv.appendChild(commentMeta);
              commentDiv.appendChild(commentText);
              commentsContainer.appendChild(commentDiv);
            });
          } else {
            commentsContainer.innerHTML = '<p>No comments yet.</p>';
          }
          
          document.getElementById('view-topic-modal').classList.add('active');
        })
        .catch(error => {
          console.error('Error fetching topic details:', error);
        });
    }
    
    function closeViewModal() {
      document.getElementById('view-topic-modal').classList.remove('active');
    }
    
    function confirmDelete(id, title) {
      document.getElementById('delete-topic-title').innerText = title;
      document.getElementById('delete-modal').classList.add('active');
      
      document.getElementById('confirm-delete-btn').onclick = function() {
        window.location.href = `admin-forums.php?delete=${id}`;
      };
    }
    
    function closeDeleteModal() {
      document.getElementById('delete-modal').classList.remove('active');
    }
    
    function changeEntriesPerPage(limit) {
      const currentUrl = new URL(window.location.href);
      currentUrl.searchParams.set('limit', limit);
      currentUrl.searchParams.delete('page');
      window.location.href = currentUrl.toString();
    }
    
    document.getElementById('search').addEventListener('keyup', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        this.form.submit();
      }
    });
    
    window.addEventListener('click', function(e) {
      const createModal = document.getElementById('create-post-modal');
      const viewModal = document.getElementById('view-topic-modal');
      const deleteModal = document.getElementById('delete-modal');
      
      if (e.target === createModal) {
        closeCreateModal();
      }
      
      if (e.target === viewModal) {
        closeViewModal();
      }
      
      if (e.target === deleteModal) {
        closeDeleteModal();
      }
    });
    
    document.addEventListener('DOMContentLoaded', function() {
      const alerts = document.querySelectorAll('.alert-success, .alert-error');
      alerts.forEach(function(alert) {
        setTimeout(function() {
          alert.style.opacity = '0';
          alert.style.transition = 'opacity 0.5s';
          setTimeout(function() {
            alert.style.display = 'none';
          }, 500);
        }, 5000);
      });
    });
  </script>
</body>
</html>
