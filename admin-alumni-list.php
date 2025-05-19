<?php
session_start();
include 'admin/db_connect.php';

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = ($page - 1) * $items_per_page;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$course_filter = isset($_GET['course']) ? intval($_GET['course']) : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$connected_filter = isset($_GET['connected']) ? $_GET['connected'] : '';
$batch_filter = isset($_GET['batch']) ? $_GET['batch'] : '';
$gender_filter = isset($_GET['gender']) ? $_GET['gender'] : '';

$filter_conditions = [];
$params = [];

if (!empty($search)) {
    $filter_conditions[] = "(a.firstname LIKE ? OR a.lastname LIKE ? OR a.alumni_id LIKE ? OR c.course LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
}

if ($course_filter > 0) {
    $filter_conditions[] = "a.course_id = ?";
    $params[] = $course_filter;
}

if ($status_filter !== '') {
    $filter_conditions[] = "a.status = ?";
    $params[] = $status_filter;
}

if ($connected_filter !== '') {
    $filter_conditions[] = "a.connected_to = ?";
    $params[] = $connected_filter;
}

if ($batch_filter !== '') {
    $filter_conditions[] = "a.batch = ?";
    $params[] = $batch_filter;
}

if ($gender_filter !== '') {
    $filter_conditions[] = "a.gender = ?";
    $params[] = $gender_filter;
}

$where_clause = count($filter_conditions) > 0 ? " WHERE " . implode(" AND ", $filter_conditions) : "";

$courses_query = "SELECT id, course FROM courses ORDER BY course ASC";
$courses_result = $conn->query($courses_query);
$courses = [];
while ($course_row = $courses_result->fetch_assoc()) {
    $courses[$course_row['id']] = $course_row['course'];
}

$batch_query = "SELECT DISTINCT batch FROM alumnus_bio ORDER BY batch DESC";
$batch_result = $conn->query($batch_query);
$batches = [];
while ($batch_row = $batch_result->fetch_assoc()) {
    $batches[] = $batch_row['batch'];
}

$count_query = "SELECT COUNT(*) as total FROM alumnus_bio a LEFT JOIN courses c ON a.course_id = c.id" . $where_clause;
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

$alumni_query = "SELECT a.*, c.course as course_name FROM alumnus_bio a 
                LEFT JOIN courses c ON a.course_id = c.id" . 
                $where_clause . 
                " ORDER BY a.lastname ASC LIMIT ?, ?";

$stmt = $conn->prepare($alumni_query);

if (count($params) > 0) {
    $params[] = $offset;
    $params[] = $items_per_page;
    $types = str_repeat('s', count($params) - 2) . 'ii';
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param("ii", $offset, $items_per_page);
}

$stmt->execute();
$alumni_result = $stmt->get_result();

$verified_query = "SELECT COUNT(*) as verified FROM alumnus_bio WHERE status = 1";
$verified_result = $conn->query($verified_query);
$verified_row = $verified_result->fetch_assoc();
$verified_count = $verified_row['verified'];

$connected_query = "SELECT COUNT(*) as connected FROM alumnus_bio WHERE connected_to = 1";
$connected_result = $conn->query($connected_query);
$connected_row = $connected_result->fetch_assoc();
$connected_count = $connected_row['connected'];

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alumni List - Alumni Portal</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin-alumni-list.css">
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

    <div id="al-content">
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
                <a href="admin-alumni-list.php" class="active"><img src="images/alumni_list.png" alt="Alumni List"><span>Alumni List</span></a>
                <a href="admin-alumni-upload.php"><img src="images/upload.png" alt="Alumni Upload"><span>Alumni Upload</span></a>
                <a href="admin-job.php"><img src="images/jobs.png" alt="Jobs"><span>Jobs</span></a>
                <a href="admin-event.php"><img src="images/calendar.png" alt="Events"><span>Events</span></a>
                <a href="admin-forums.php"><img src="images/forums.png" alt="Forum"><span>Forum</span></a>
                <a href="admin-officers.php"><img src="images/users.png" alt="Officers"><span>Officers</span></a>
                <a href="admin-system-setting.php"><img src="images/settings.png" alt="System Settings"><span>System Settings</span></a>
                <a href="landing.php"><img src="images/log-out.png" alt="Log Out"><span>Log Out</span></a>
            </div>
        </div>

        <div class="al-main-content">
            <header>List of Alumni</header>
            <hr>
            
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert-error">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <div class="filter-container">
                <div class="filter-header">
                    <button id="toggle-filters" class="toggle-filters-btn">Show Filters</button>
                    <a href="export_alumni_pdf.php?<?php echo build_query_params([]); ?>" target="_blank" class="export-pdf-btn">
                        <i class="fas fa-file-pdf"></i> Export to PDF
                    </a>
                </div>
                
                <div id="filter-panel" class="filter-panel">
                    <form method="GET" action="" id="filter-form">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="search">Search:</label>
                                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, ID or Course">
                            </div>
                            
                            <div class="filter-group">
                                <label for="course">Course:</label>
                                <select id="course" name="course">
                                    <option value="0">All Courses</option>
                                    <?php foreach ($courses as $id => $course): ?>
                                    <option value="<?php echo $id; ?>" <?php echo $course_filter == $id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="batch">Batch Year:</label>
                                <select id="batch" name="batch">
                                    <option value="">All Years</option>
                                    <?php foreach ($batches as $batch): ?>
                                    <option value="<?php echo $batch; ?>" <?php echo $batch_filter == $batch ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($batch); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="status">Verification Status:</label>
                                <select id="status" name="status">
                                    <option value="">All</option>
                                    <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Verified</option>
                                    <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Unverified</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="connected">Connection Status:</label>
                                <select id="connected" name="connected">
                                    <option value="">All</option>
                                    <option value="1" <?php echo $connected_filter === '1' ? 'selected' : ''; ?>>Connected</option>
                                    <option value="0" <?php echo $connected_filter === '0' ? 'selected' : ''; ?>>Not Connected</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="gender">Gender:</label>
                                <select id="gender" name="gender">
                                    <option value="">All</option>
                                    <option value="Male" <?php echo $gender_filter === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo $gender_filter === 'Female' ? 'selected' : ''; ?>>Female</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn-apply-filter">Apply Filters</button>
                            <a href="admin-alumni-list.php" class="btn-reset-filter">Reset All</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="alumni-stats">
                <div class="stat-box">
                    <span class="stat-number"><?php echo $total_records; ?></span>
                    <span class="stat-label">Total Alumni</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?php echo $verified_count; ?></span>
                    <span class="stat-label">Verified</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?php echo $connected_count; ?></span>
                    <span class="stat-label">Connected</span>
                </div>
                
                <?php if (count($filter_conditions) > 0): ?>
                <div class="stat-box filtered">
                    <span class="stat-number"><?php echo count($filter_conditions); ?></span>
                    <span class="stat-label">Active Filters</span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="table-alist">
                <table>
                    <tr>
                        <th>#</th>
                        <th>Avatar</th>
                        <th>Alumni ID</th>
                        <th>Name</th>
                        <th>Course Graduated</th>
                        <th>Batch</th>
                        <th>Gender</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    <?php 
                    if($alumni_result->num_rows > 0):
                        $count = $offset + 1;
                        while($row = $alumni_result->fetch_assoc()): 
                            $avatar = !empty($row['avatar']) ? $row['avatar'] : 'images/avatar.png';
                            $status_badge = $row['status'] == 1 ? 'verified' : 'unverified';
                            $connection_badge = $row['connected_to'] == 1 ? 'connected' : 'not-connected';
                    ?>
                    <tr>
                        <td><?php echo $count++; ?></td>
                        <td class="avatar-cell">
                            <img src="<?php echo $avatar; ?>" alt="Avatar" class="alumni-avatar">
                        </td>
                        <td><?php echo htmlspecialchars($row['alumni_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['lastname'] . ', ' . $row['firstname'] . ' ' . $row['middlename']); ?></td>
                        <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['batch']); ?></td>
                        <td><?php echo htmlspecialchars($row['gender']); ?></td>
                        <td>
                            <span class="status-badge <?php echo $status_badge; ?>">
                                <?php echo $status_badge; ?>
                            </span>
                            <span class="status-badge <?php echo $connection_badge; ?>">
                                <?php echo str_replace('-', ' ', $connection_badge); ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <a href="view_alumni.php?id=<?php echo $row['alumni_id']; ?>" class="btn-view">View</a>
                            <a href="edit_alumni.php?id=<?php echo $row['alumni_id']; ?>" class="btn-edit">Edit</a>
                            <button type="button" class="btn-delete" onclick="confirmDelete('<?php echo $row['alumni_id']; ?>')">Delete</button>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="8" class="no-records">
                            <?php echo count($filter_conditions) > 0 ? 'No alumni match your filter criteria.' : 'No alumni records found.'; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <div class="list-foot">
                <div class="pagination-info">
                    <?php if (count($filter_conditions) > 0): ?>
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
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            const toggleBtn = document.querySelector('.toggle-btn');
            toggleBtn.innerHTML = sidebar.classList.contains('collapsed') ? '&#x25B6;' : '&#x25C0;';
        }

        function changePage(page) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('page', page);
            window.location.href = currentUrl.toString();
        }
        
        function changeEntriesPerPage(limit) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('limit', limit);
            currentUrl.searchParams.delete('page'); 
            window.location.href = currentUrl.toString();
        }
        
        function confirmDelete(id) {
            if(confirm('Are you sure you want to delete this alumni record?')) {
                window.location.href = 'delete_alumni.php?id=' + id;
            }
        }
        
        document.getElementById('toggle-filters').addEventListener('click', function() {
            const filterPanel = document.getElementById('filter-panel');
            const isVisible = filterPanel.classList.toggle('active');
            this.textContent = isVisible ? 'Hide Filters' : 'Show Filters';
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            const activeFilters = <?php echo count($filter_conditions); ?>;
            if (activeFilters > 0) {
                document.getElementById('filter-panel').classList.add('active');
                document.getElementById('toggle-filters').textContent = 'Hide Filters';
            }
        });
    </script>

</body>
</html>
