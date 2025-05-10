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

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
$params = [];

if (!empty($search)) {
    $search_condition = "WHERE title LIKE ? OR description LIKE ?";
    $params = ["%$search%", "%$search%"];
}

$count_query = "SELECT COUNT(*) as total FROM forum_topics $search_condition";
if (!empty($search)) {
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param(str_repeat('s', count($params)), ...$params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
} else {
    $count_result = $conn->query($count_query);
}
$count_data = $count_result->fetch_assoc();
$total_records = $count_data['total'];
$total_pages = ceil($total_records / $items_per_page);

$topics_query = "SELECT ft.*, u.name as author_name, 
                (SELECT COUNT(*) FROM forum_comments WHERE topic_id = ft.id) as comment_count 
                FROM forum_topics ft 
                LEFT JOIN users u ON ft.user_id = u.alumni_id 
                $search_condition 
                GROUP BY ft.id 
                ORDER BY ft.date_created DESC 
                LIMIT ?, ?";

$stmt = $conn->prepare($topics_query);
$stmt_params = $params;
$stmt_params[] = $offset;
$stmt_params[] = $items_per_page;

$types = str_repeat('s', count($params)) . 'ii';
$stmt->bind_param($types, ...$stmt_params);
$stmt->execute();
$topics_result = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_topic'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $user_id = $alumni_id; 
    
    if (!empty($title) && !empty($description)) {
        $insert_query = "INSERT INTO forum_topics (title, description, user_id) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("sss", $title, $description, $user_id);
        
        if ($insert_stmt->execute()) {
            $_SESSION['success'] = "Forum topic created successfully!";
            header("Location: alumni-forums.php");
            exit;
        } else {
            $_SESSION['error'] = "Error creating forum topic: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = "Title and description are required.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $topic_id = intval($_POST['topic_id']);
    $comment = $_POST['comment'];
    $user_id = $alumni_id;
    
    if (!empty($comment) && !empty($topic_id)) {
        $insert_query = "INSERT INTO forum_comments (topic_id, comment, user_id) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("iss", $topic_id, $comment, $user_id);
        
        if ($insert_stmt->execute()) {
            $_SESSION['success'] = "Comment added successfully!";
            header("Location: alumni-forums.php?view_topic=" . $topic_id);
            exit;
        } else {
            $_SESSION['error'] = "Error adding comment: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = "Comment text is required.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment'])) {
    $comment_id = intval($_POST['comment_id']);
    $topic_id = intval($_POST['topic_id']);
    
    $verify_query = "SELECT * FROM forum_comments WHERE id = ? AND user_id = ?";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param("is", $comment_id, $alumni_id);  
    
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows > 0) {
        $delete_query = "DELETE FROM forum_comments WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $comment_id);
        
        if ($delete_stmt->execute()) {
            $_SESSION['success'] = "Comment deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting comment: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = "You don't have permission to delete this comment.";
    }
    
    header("Location: alumni-forums.php?view_topic=" . $topic_id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_topic'])) {
    $topic_id = intval($_POST['topic_id']);
    $title = $_POST['title'];
    $description = $_POST['description'];
    
    $verify_query = "SELECT * FROM forum_topics WHERE id = ? AND user_id = ?";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param("is", $topic_id, $alumni_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows > 0) {
        $update_query = "UPDATE forum_topics SET title = ?, description = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ssi", $title, $description, $topic_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Forum topic updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating topic: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = "You don't have permission to edit this topic.";
    }
    
    header("Location: alumni-forums.php?view_topic=" . $topic_id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_topic'])) {
    $topic_id = intval($_POST['topic_id']);
    
    $verify_query = "SELECT * FROM forum_topics WHERE id = ? AND user_id = ?";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param("is", $topic_id, $alumni_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows > 0) {
        $delete_comments = "DELETE FROM forum_comments WHERE topic_id = ?";
        $delete_comments_stmt = $conn->prepare($delete_comments);
        $delete_comments_stmt->bind_param("i", $topic_id);
        $delete_comments_stmt->execute();
        
        $delete_query = "DELETE FROM forum_topics WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $topic_id);
        
        if ($delete_stmt->execute()) {
            $_SESSION['success'] = "Forum topic deleted successfully!";
            header("Location: alumni-forums.php");
            exit;
        } else {
            $_SESSION['error'] = "Error deleting topic: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = "You don't have permission to delete this topic.";
    }
    
    header("Location: alumni-forums.php?view_topic=" . $topic_id);
    exit;
}

$view_topic = isset($_GET['view_topic']) ? intval($_GET['view_topic']) : 0;
$topic_data = null;
$comments = [];

if ($view_topic > 0) {
    $topic_query = "SELECT ft.*, u.name as author_name FROM forum_topics ft 
                   LEFT JOIN users u ON ft.user_id = u.alumni_id 
                   WHERE ft.id = ?";
    $topic_stmt = $conn->prepare($topic_query);
    $topic_stmt->bind_param("i", $view_topic);
    $topic_stmt->execute();
    $topic_result = $topic_stmt->get_result();
    
    if ($topic_result->num_rows > 0) {
        $topic_data = $topic_result->fetch_assoc();
        
        $comments_query = "SELECT fc.*, u.name as author_name, ab.avatar 
                         FROM forum_comments fc 
                         LEFT JOIN users u ON fc.user_id = u.alumni_id 
                         LEFT JOIN alumnus_bio ab ON fc.user_id = ab.alumni_id 
                         WHERE fc.topic_id = ? 
                         GROUP BY fc.id
                         ORDER BY fc.date_created ASC";
        $comments_stmt = $conn->prepare($comments_query);
        $comments_stmt->bind_param("i", $view_topic);
        $comments_stmt->execute();
        $comments_result = $comments_stmt->get_result();
        
        while ($row = $comments_result->fetch_assoc()) {
            if (empty($row['avatar'])) {
                $row['avatar'] = 'images/avatar.png';
            }
            $comments[] = $row;
        }
    }
}

function build_query_params($exclude = []) {
    $params = $_GET;
    foreach ($exclude as $param) {
        unset($params[$param]);
    }
    return http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Forums - Alumni Portal</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="alumni-forums.css">
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
          <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Profile Picture">
        </a>
        <div class="profile-name"><?php echo htmlspecialchars($display_name); ?></div>
      </div>

      <a href="alumni-home.php"><img src="images/home.png" alt="Home"><span>Home</span></a>
      <a href="alumni-gallery.php"><img src="images/gallery.png" alt="Gallery"><span>Gallery</span></a>
      <a href="alumni-list.php"><img src="images/alumni_list.png" alt="Alumni List"><span>Alumni List</span></a>
      <a href="alumni-job.php"><img src="images/jobs.png" alt="Jobs"><span>Jobs</span></a>
      <a href="alumni-forums.php" class="active"><img src="images/forums.png" alt="Forums"><span>Forums</span></a>
      <a href="alumni-events.php"><img src="images/calendar.png" alt="Events"><span>Events</span></a>
      <a href="alumni-about.php"><img src="images/about.png" alt="About"><span>About</span></a>
      <a href="landing.php"><img src="images/log-out.png" alt="Log Out"><span>Log Out</span></a>
    </div>
  </div>

  <main class="forum-content">
    <?php if(isset($_SESSION['success'])): ?>
      <div class="alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
      </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['error'])): ?>
      <div class="alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
      </div>
    <?php endif; ?>

    <?php if($view_topic > 0 && $topic_data): ?>
      <div class="forum-container">
        <div class="forum-header">
          <a href="alumni-forums.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to Forums</a>
          <h1><?php echo htmlspecialchars($topic_data['title']); ?></h1>
          
          <?php 
          if($topic_data['user_id'] == $alumni_id): 
          ?>
            <div class="topic-actions">
              <button class="edit-topic-btn" onclick="openEditTopicModal(<?php echo $view_topic; ?>, '<?php echo addslashes(htmlspecialchars($topic_data['title'])); ?>', '<?php echo addslashes(htmlspecialchars_decode($topic_data['description'])); ?>')">
                <i class="fas fa-edit"></i> Edit
              </button>
              <button class="delete-topic-btn" onclick="confirmDeleteTopic(<?php echo $view_topic; ?>)">
                <i class="fas fa-trash-alt"></i> Delete
              </button>
            </div>
          <?php endif; ?>
        </div>

        <div class="topic-details">
          <div class="topic-meta">
            <span class="topic-author"><i class="fas fa-user"></i> Posted by: <?php echo htmlspecialchars($topic_data['author_name'] ?? 'Unknown'); ?></span>
            <span class="topic-date"><i class="far fa-clock"></i> <?php echo date('F d, Y h:i A', strtotime($topic_data['date_created'])); ?></span>
          </div>
          
          <div class="topic-content">
            <?php echo html_entity_decode($topic_data['description']); ?>
          </div>
        </div>

        <div class="comments-section">
          <h2><i class="fas fa-comments"></i> Comments (<?php echo count($comments); ?>)</h2>
          
          <?php if(count($comments) > 0): ?>
            <div class="comments-list">
              <?php foreach($comments as $comment): ?>
                <div class="comment-card">
                  <div class="comment-header">
                    <img src="<?php echo htmlspecialchars($comment['avatar']); ?>" alt="Profile" class="comment-avatar">
                    <div class="comment-info">
                      <div class="comment-author"><?php echo htmlspecialchars($comment['author_name'] ?? 'Unknown'); ?></div>
                      <div class="comment-date"><?php echo date('M d, Y h:i A', strtotime($comment['date_created'])); ?></div>
                    </div>
                    <?php 
                    if(strcmp($comment['user_id'], $alumni_id) === 0): 
                    ?>
                      <div class="comment-actions">
                        <button class="delete-comment-btn" onclick="confirmDeleteComment(<?php echo $comment['id']; ?>, <?php echo $view_topic; ?>)">
                          <i class="fas fa-trash-alt"></i>
                        </button>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="comment-content">
                    <?php echo htmlspecialchars($comment['comment']); ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="no-comments">
              <i class="far fa-comment-dots"></i>
              <p>No comments yet. Be the first to comment!</p>
            </div>
          <?php endif; ?>

          <div class="add-comment-form">
            <h3>Add a Comment</h3>
            <form method="POST" action="">
              <input type="hidden" name="topic_id" value="<?php echo $view_topic; ?>">
              <div class="form-group">
                <textarea name="comment" rows="4" placeholder="Write your comment here..." required></textarea>
              </div>
              <div class="form-group">
                <button type="submit" name="submit_comment" class="btn-submit-comment">
                  <i class="fas fa-paper-plane"></i> Submit Comment
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    <?php else: ?>
      <div class="forum-container">
        <div class="forum-header">
          <h1><i class="fas fa-comments"></i> Alumni Forums</h1>
        </div>

        <div class="forum-actions">
          <div class="search-container">
            <form method="GET" action="">
              <div class="search-input">
                <input type="text" name="search" placeholder="Search forums..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
              </div>
            </form>
          </div>
          <button class="create-topic-btn" onclick="openCreateModal()">
            <i class="fas fa-plus-circle"></i> Create New Topic
          </button>
        </div>

        <?php if($topics_result->num_rows > 0): ?>
          <div class="topics-list">
            <?php while($topic = $topics_result->fetch_assoc()): ?>
              <div class="topic-card">
                <div class="topic-icon">
                  <i class="fas fa-comments"></i>
                </div>
                <div class="topic-details">
                  <h3><a href="alumni-forums.php?view_topic=<?php echo $topic['id']; ?>"><?php echo htmlspecialchars($topic['title']); ?></a></h3>
                  <div class="topic-preview">
                    <?php echo substr(strip_tags(html_entity_decode($topic['description'])), 0, 150) . '...'; ?>
                  </div>
                  <div class="topic-meta">
                    <span class="author"><i class="fas fa-user"></i> Posted by: <?php echo htmlspecialchars($topic['author_name'] ?? 'Unknown'); ?></span>
                    <span class="date"><i class="far fa-clock"></i> <?php echo date('M d, Y', strtotime($topic['date_created'])); ?></span>
                    <span class="comments-count"><i class="far fa-comment"></i> <?php echo $topic['comment_count']; ?> comments</span>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
          
          <?php if($total_pages > 1): ?>
            <div class="pagination">
              <?php if($page > 1): ?>
                <a href="?page=1&<?php echo build_query_params(['page']); ?>" class="page-link">&laquo; First</a>
                <a href="?page=<?php echo $page-1; ?>&<?php echo build_query_params(['page']); ?>" class="page-link">&lsaquo; Prev</a>
              <?php endif; ?>
              
              <?php
              $start_page = max(1, $page - 2);
              $end_page = min($total_pages, $start_page + 4);
              for($i = $start_page; $i <= $end_page; $i++):
              ?>
                <a href="?page=<?php echo $i; ?>&<?php echo build_query_params(['page']); ?>" class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
              <?php endfor; ?>
              
              <?php if($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?>&<?php echo build_query_params(['page']); ?>" class="page-link">Next &rsaquo;</a>
                <a href="?page=<?php echo $total_pages; ?>&<?php echo build_query_params(['page']); ?>" class="page-link">Last &raquo;</a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="no-topics">
            <i class="far fa-comment-dots"></i>
            <h2>No forum topics found</h2>
            <?php if(!empty($search)): ?>
              <p>No results matching your search. Try different keywords or <a href="alumni-forums.php">view all topics</a>.</p>
            <?php else: ?>
              <p>Be the first to start a discussion!</p>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </main>

  <div id="create-topic-modal" class="forum-modal">
    <div class="forum-modal-content">
      <span class="close-modal" onclick="closeCreateModal()">&times;</span>
      <h2>Create New Forum Topic</h2>
      
      <form method="POST" action="">
        <div class="form-group">
          <label for="title">Topic Title</label>
          <input type="text" id="title" name="title" required>
        </div>
        
        <div class="form-group">
          <label for="description">Description</label>
          <textarea id="description" name="description" rows="6" required></textarea>
        </div>
        
        <div class="form-group text-center">
          <button type="submit" name="submit_topic" class="btn-submit">
            <i class="fas fa-paper-plane"></i> Post Topic
          </button>
        </div>
      </form>
    </div>
  </div>

  <div id="edit-topic-modal" class="forum-modal">
    <div class="forum-modal-content">
      <span class="close-modal" onclick="closeEditTopicModal()">&times;</span>
      <h2>Edit Forum Topic</h2>
      
      <form method="POST" action="">
        <input type="hidden" id="edit-topic-id" name="topic_id" value="">
        <input type="hidden" name="edit_topic" value="1">
        
        <div class="form-group">
          <label for="edit-title">Topic Title</label>
          <input type="text" id="edit-title" name="title" required>
        </div>
        
        <div class="form-group">
          <label for="edit-description">Description</label>
          <textarea id="edit-description" name="description" rows="6" required></textarea>
        </div>
        
        <div class="form-group text-center">
          <button type="submit" class="btn-submit">
            <i class="fas fa-save"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>

  <div id="delete-topic-modal" class="forum-modal">
    <div class="forum-modal-content delete-modal-content">
      <h3>Delete Topic</h3>
      <p>Are you sure you want to delete this topic? This will also delete all comments and cannot be undone.</p>
      <div class="modal-buttons">
        <form method="POST" action="" id="delete-topic-form">
          <input type="hidden" name="topic_id" id="delete-topic-id">
          <input type="hidden" name="delete_topic" value="1">
          <button type="button" class="btn-cancel" onclick="closeDeleteTopicModal()">Cancel</button>
          <button type="submit" class="btn-delete">Delete</button>
        </form>
      </div>
    </div>
  </div>

  <div id="delete-comment-modal" class="forum-modal">
    <div class="forum-modal-content delete-modal-content">
      <h3>Delete Comment</h3>
      <p>Are you sure you want to delete this comment? This action cannot be undone.</p>
      <div class="modal-buttons">
        <form method="POST" action="" id="delete-comment-form">
          <input type="hidden" name="comment_id" id="delete-comment-id">
          <input type="hidden" name="topic_id" id="delete-topic-id">
          <input type="hidden" name="delete_comment" value="1">
          <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
          <button type="submit" class="btn-delete">Delete</button>
        </form>
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
      document.getElementById('create-topic-modal').classList.add('show-modal');
    }

    function closeCreateModal() {
      document.getElementById('create-topic-modal').classList.remove('show-modal');
    }

    function openEditTopicModal(topicId, title, description) {
      document.getElementById('edit-topic-id').value = topicId;
      document.getElementById('edit-title').value = title;
      document.getElementById('edit-description').value = description;
      document.getElementById('edit-topic-modal').classList.add('show-modal');
    }

    function closeEditTopicModal() {
      document.getElementById('edit-topic-modal').classList.remove('show-modal');
    }

    function confirmDeleteTopic(topicId) {
      document.getElementById('delete-topic-id').value = topicId;
      document.getElementById('delete-topic-modal').classList.add('show-modal');
    }
    
    function closeDeleteTopicModal() {
      document.getElementById('delete-topic-modal').classList.remove('show-modal');
    }

    function confirmDeleteComment(commentId, topicId) {
      document.getElementById('delete-comment-id').value = commentId;
      document.getElementById('delete-topic-id').value = topicId;
      document.getElementById('delete-comment-modal').classList.add('show-modal');
    }
    
    function closeDeleteModal() {
      document.getElementById('delete-comment-modal').classList.remove('show-modal');
    }

    window.onclick = function(event) {
      const createModal = document.getElementById('create-topic-modal');
      const deleteCommentModal = document.getElementById('delete-comment-modal');
      const editTopicModal = document.getElementById('edit-topic-modal');
      const deleteTopicModal = document.getElementById('delete-topic-modal');
      
      if (event.target == createModal) {
        closeCreateModal();
      }
      
      if (event.target == deleteCommentModal) {
        closeDeleteModal();
      }
      
      if (event.target == editTopicModal) {
        closeEditTopicModal();
      }
      
      if (event.target == deleteTopicModal) {
        closeDeleteTopicModal();
      }
    }
  </script>
</body>
</html>
