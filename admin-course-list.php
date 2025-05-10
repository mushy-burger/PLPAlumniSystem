<?php
session_start();
include 'admin/db_connect.php';

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

$column_info_query = "SHOW COLUMNS FROM courses";
$column_info_result = $conn->query($column_info_query);
$searchable_columns = [];

while ($column = $column_info_result->fetch_assoc()) {
    if (strpos($column['Type'], 'varchar') !== false || 
        strpos($column['Type'], 'text') !== false || 
        strpos($column['Type'], 'char') !== false) {
        $searchable_columns[] = $column['Field'];
    }
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$where_clause = "";
$params = [];

if (!empty($search) && !empty($searchable_columns)) {
    $search_conditions = [];
    foreach ($searchable_columns as $column) {
        $search_conditions[] = "$column LIKE ?";
        $params[] = "%$search%";
    }
    $where_clause = " WHERE " . implode(" OR ", $search_conditions);
}

$count_query = "SELECT COUNT(*) as total FROM courses" . $where_clause;
$stmt_count = $conn->prepare($count_query);

if (count($params) > 0) {
    $types = str_repeat('s', count($params));
    $stmt_count->bind_param($types, ...$params);
}

$stmt_count->execute();
$count_result = $stmt_count->get_result();
$count_row = $count_result->fetch_assoc();
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $items_per_page);

$course_query = "SELECT * FROM courses" . $where_clause . " ORDER BY id ASC LIMIT ?, ?";
$stmt = $conn->prepare($course_query);

if (count($params) > 0) {
    $params[] = $offset;
    $params[] = $items_per_page;
    $types = str_repeat('s', count($params) - 2) . 'ii';
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param("ii", $offset, $items_per_page);
}

$stmt->execute();
$courses_result = $stmt->get_result();

$field_info = $courses_result->fetch_fields();
$has_course_code = false;
$has_department = false;

foreach ($field_info as $field) {
    if ($field->name === 'course_code') {
        $has_course_code = true;
    }
    if ($field->name === 'department') {
        $has_department = true;
    }
}

$message = '';
$message_type = '';

if (isset($_SESSION['course_message'])) {
    $message = $_SESSION['course_message'];
    $message_type = $_SESSION['course_message_type'];
    unset($_SESSION['course_message']);
    unset($_SESSION['course_message_type']);
}

function build_query_params($exclude = []) {
    $params = [];
    foreach ($_GET as $key => $value) {
        if (!in_array($key, $exclude) && $key != 'page') {
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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Course List - Alumni Portal</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="admin-course-list.css">
</head>

<body>
    <div class="interface-header">
    <img src="images/logo.png" alt="PLP Logo" class="logo-interface">
        <div class="text">
            <div class="school-name">Pamantasan Ng Lungsod Ng Pasig</div>
            <p class="p-size">Alkade Jose St. Kapasigan, Pasig City</p>
        </div>
    </div>

    <div id="al-content">
        <div class="sidebar" id="sidebar">
        <div class="toggle-btn" onclick="toggleSidebar()">&#x25C0;</div>
            <div class="sidebar-content">
                <div class="profile-section">
                    <a class="profile-pic">
                        <img src="images/avatar.png" alt="Profile Picture"></a>
                    <div class="profile-name">ADMIN</div>
                </div>

                <a href="admin-home.php"><img src="images/home.png" alt="Home"><span>Home</span></a>
                <a href="admin-gallery.php"><img src="images/gallery.png" alt="Gallery"><span>Gallery</span></a>
                <a href="admin-course-list.php" class="active"><img src="images/course-list.png" alt="Course List"><span>Course List</span></a>
                <a href="admin-alumni-list.php"><img src="images/alumni_list.png" alt="Alumni List"><span>Alumni List</span></a>
                <a href="admin-alumni-upload.php"><img src="images/upload.png" alt="Alumni Upload"><span>Alumni Upload</span></a>
                <a href="admin-job.php"><img src="images/jobs.png" alt="Jobs"><span>Jobs</span></a>
                <a href="admin-event.php"><img src="images/calendar.png" alt="Events"><span>Events</span></a>
                <a href="admin-forums.php"><img src="images/forums.png" alt="Forum"><span>Forum</span></a>
                <a href="admin-system-setting.php"><img src="images/settings.png" alt="System Settings"><span>System Settings</span></a>
                <a href="landing.php"><img src="images/log-out.png" alt="Log Out"><span>Log Out</span></a>
            </div>
        </div>

        <div class="al-main-content">
            <header>List of Courses</header> <hr>

            <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <div class="alist-search">
                <form method="GET" action="admin-course-list.php">
                    <label class="alist-label">Search: </label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Course Name or Description">
                    <button type="submit" class="alist-filter">Search</button>
                    <button type="button" class="add-course-btn" onclick="openAddCourseModal()">Add New Course</button>
                </form>
            </div>

            <div class="course-stats">
                <div class="stat-box">
                    <span class="stat-number"><?php echo $total_records; ?></span>
                    <span class="stat-label">Total Courses</span>
                </div>
                <?php if (!empty($search)): ?>
                <div class="stat-box filtered">
                    <span class="stat-number">1</span>
                    <span class="stat-label">Active Filter</span>
                </div>
                <?php endif; ?>
            </div>

            <div class="table-alist">
                <table id="courseTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <?php if ($has_course_code): ?>
                            <th>Course Code</th>
                            <?php endif; ?>
                            <th>Course Name</th>
                            <?php if ($has_department): ?>
                            <th>Department</th>
                            <?php endif; ?>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if($courses_result->num_rows > 0):
                            $counter = $offset + 1;
                            while($row = $courses_result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <?php if ($has_course_code): ?>
                            <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                            <?php endif; ?>
                            <td><?php echo htmlspecialchars($row['course']); ?></td>
                            <?php if ($has_department): ?>
                            <td><?php echo htmlspecialchars($row['department']); ?></td>
                            <?php endif; ?>
                            <td>
                                <button type="button" class="admin-course-list-btn edit" 
                                        onclick="editCourse(<?php echo $row['id']; ?>, 
                                        '<?php echo isset($row['course_code']) ? addslashes($row['course_code']) : ''; ?>', 
                                        '<?php echo addslashes($row['course']); ?>', 
                                        '<?php echo isset($row['department']) ? addslashes($row['department']) : ''; ?>')">Edit</button>
                                <button type="button" class="admin-course-list-btn delete" 
                                        onclick="confirmDelete(<?php echo $row['id']; ?>, 
                                        '<?php echo addslashes($row['course']); ?>')">Delete</button>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="<?php echo 3 + ($has_course_code ? 1 : 0) + ($has_department ? 1 : 0); ?>" class="no-records">No courses found</td>
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
    </div>

    <div class="admin-course-list-modal-overlay" id="courseEditModal">
        <div class="admin-course-list-modal">
            <h2 id="modalTitle">Edit Course</h2>
            <form id="editCourseForm" method="POST" action="admin/course_actions.php">
                <input type="hidden" id="courseId" name="course_id" value="">
                <input type="hidden" id="action" name="action" value="edit">
                
                <?php if ($has_course_code): ?>
                <label for="courseCode">Course Code</label>
                <input type="text" id="courseCode" name="course_code" required>
                <?php endif; ?>

                <label for="courseName">Course Name</label>
                <input type="text" id="courseName" name="course_name" required>

                <?php if ($has_department): ?>
                <label for="department">Department</label>
                <input type="text" id="department" name="department" required>
                <?php endif; ?>

                <div class="admin-course-list-modal-buttons">
                    <button type="submit" class="save">Save</button>
                    <button type="button" class="cancel" onclick="closeCourseModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="admin-course-list-modal-overlay" id="deleteConfirmModal">
        <div class="admin-course-list-modal">
            <h2>Delete Course</h2>
            <p id="deleteConfirmMessage">Are you sure you want to delete this course?</p>
            <form id="deleteCourseForm" method="POST" action="admin/course_actions.php">
                <input type="hidden" id="deleteCourseId" name="course_id" value="">
                <input type="hidden" name="action" value="delete">
                
                <div class="admin-course-list-modal-buttons">
                    <button type="submit" class="delete">Delete</button>
                    <button type="button" class="cancel" onclick="closeDeleteModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            const toggleBtn = document.querySelector('.toggle-btn');
            toggleBtn.innerHTML = sidebar.classList.contains('collapsed') ? '&#x25B6;' : '&#x25C0;';
        }

        function editCourse(id, code, name, dept) {
            document.getElementById('modalTitle').textContent = 'Edit Course';
            document.getElementById('courseId').value = id;
            
            <?php if ($has_course_code): ?>
            document.getElementById('courseCode').value = code || '';
            <?php endif; ?>
            
            document.getElementById('courseName').value = name || '';
            
            <?php if ($has_department): ?>
            document.getElementById('department').value = dept || '';
            <?php endif; ?>
            
            document.getElementById('action').value = 'edit';
            document.getElementById('courseEditModal').style.display = 'flex';
        }

        function openAddCourseModal() {
            document.getElementById('modalTitle').textContent = 'Add New Course';
            document.getElementById('courseId').value = '';
            
            <?php if ($has_course_code): ?>
            document.getElementById('courseCode').value = '';
            <?php endif; ?>
            
            document.getElementById('courseName').value = '';
            
            <?php if ($has_department): ?>
            document.getElementById('department').value = '';
            <?php endif; ?>
            
            document.getElementById('action').value = 'add';
            document.getElementById('courseEditModal').style.display = 'flex';
        }

        function closeCourseModal() {
            document.getElementById('courseEditModal').style.display = 'none';
        }

        function confirmDelete(id, courseName) {
            document.getElementById('deleteCourseId').value = id;
            document.getElementById('deleteConfirmMessage').textContent = 
                `Are you sure you want to delete the course "${courseName}"?`;
            document.getElementById('deleteConfirmModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteConfirmModal').style.display = 'none';
        }

        window.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
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
