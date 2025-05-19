<?php
session_start();
include 'admin/db_connect.php';

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = isset($_GET['limit']) ? intval($_GET['limit']) : 15;
$offset = ($page - 1) * $items_per_page;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$course_filter = isset($_GET['course']) ? intval($_GET['course']) : 0;
$batch_filter = isset($_GET['batch']) ? $_GET['batch'] : '';
$connected_filter = isset($_GET['connected']) ? $_GET['connected'] : '';

$filter_conditions = [];
$params = [];

if (!empty($search)) {
    $filter_conditions[] = "(a.firstname LIKE ? OR a.lastname LIKE ? OR a.alumni_id LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

if ($course_filter > 0) {
    $filter_conditions[] = "a.course_id = ?";
    $params[] = $course_filter;
}

if ($batch_filter !== '') {
    $filter_conditions[] = "a.batch = ?";
    $params[] = $batch_filter;
}

if ($connected_filter !== '') {
    $filter_conditions[] = "a.connected_to = ?";
    $params[] = $connected_filter;
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

function build_query_params($exclude = []) {
    $params = $_GET;
    foreach ($exclude as $param) {
        unset($params[$param]);
    }
    return http_build_query($params);
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
    $alumni_result_user = $alumni_stmt->get_result();
    $alumni_data = $alumni_result_user->fetch_assoc();
    
    if(isset($user_data['name'])) {
        $display_name = $user_data['name'];
    }
    
    if(!empty($alumni_data['avatar']) && file_exists($alumni_data['avatar'])) {
        $avatar = $alumni_data['avatar'];
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Alumni List - Alumni Portal</title>
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="alumni-list.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <style>
            .alumni-avatar {
                position: relative;
                width: 100%;
                padding-top: 100%; 
                overflow: hidden;
                background-color: #f0f2f5;
                border-radius: 8px 8px 0 0;
                box-shadow: 0 2px 6px rgba(0,0,0,0.1);
                margin-bottom: 0;
                aspect-ratio: 1/1; 
                display: block; 
            }
            
            .alumni-avatar img {
                position: absolute;
                top: 0;
                left: 0;
                width: 100% !important; 
                height: 100% !important; 
                object-fit: cover;
                object-position: center;
                transition: all 0.5s ease;
                display: block;
                min-height: 100%; 
                min-width: 100%; 
            }
            
            .alumni-card {
                display: flex;
                flex-direction: column;
                height: 100%;
                transition: all 0.3s ease;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 3px 10px rgba(0,0,0,0.1);
                background-color: white;
                border: 1px solid rgba(0,56,147,0.08);
            }
            
            .alumni-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 15px 25px rgba(0,0,0,0.18);
                border-color: rgba(0,56,147,0.2);
            }
            
            .alumni-card:hover .alumni-avatar img {
                transform: scale(1.08);
            }
            
            .connection-badge {
                position: absolute;
                bottom: 8px;
                right: 8px;
                width: 28px;
                height: 28px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 12px;
                box-shadow: 0 3px 8px rgba(0,0,0,0.2);
                border: 2px solid white;
                z-index: 2;
            }
            
            .connection-badge.connected {
                background-color: #4CAF50;
            }
            
            .connection-badge.not-connected {
                background-color: #9e9e9e;
            }
            
            .alumni-details {
                padding: 12px 10px;
                flex: 1;
                text-align: center;
                display: flex;
                flex-direction: column;
                justify-content: center;
                background: linear-gradient(to bottom, #ffffff, #f9fbff);
            }
            
            .alumni-details h3 {
                margin: 0 0 6px 0;
                font-size: 15px;
                color: #003893;
                line-height: 1.3;
                font-weight: 600;
            }
            
            .alumni-course {
                margin: 0 0 5px 0;
                font-size: 13px;
                color: #555;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .alumni-batch {
                margin: 0;
                font-size: 12px;
                color: #666;
                display: flex;
                align-items: center;
                justify-content: center;
                background-color: #f0f4fa;
                padding: 3px 10px;
                border-radius: 30px;
                width: fit-content;
                margin: 5px auto 0;
            }

            .alumni-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
                gap: 15px;
                margin-bottom: 30px;
            }

            .filter-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin-bottom: 15px;
            }
            
            .filter-group {
                min-width: unset;
            }
            
            .filter-actions {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
            }
            
            .btn-apply-filter,
            .btn-reset-filter {
                padding: 8px 15px;
                font-size: 14px;
            }
            
            .filter-group select,
            .filter-group input {
                padding: 8px 10px;
            }
            
            @media (max-width: 768px) {
                .filter-grid {
                    grid-template-columns: 1fr 1fr;
                }
            }
            
            @media (max-width: 480px) {
                .filter-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
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
                <a href="alumni-list.php" class="active"><img src="images/alumni_list.png" alt="Alumni List"><span>Alumni List</span></a>
                <a href="alumni-job.php"><img src="images/jobs.png" alt="Jobs"><span>Jobs</span></a>
                <a href="alumni-forums.php"><img src="images/forums.png" alt="Forums"><span>Forums</span></a>
                <a href="alumni-events.php"><img src="images/calendar.png" alt="Events"><span>Events</span></a>
                <a href="alumni-about.php"><img src="images/about.png" alt="About"><span>About</span></a>
                <a href="landing.php"><img src="images/log-out.png" alt="Log Out"><span>Log Out</span></a>
            </div>
        </div>

        <div class="main-content">
            <header>
                <h1><i class="fas fa-users"></i> Alumni Directory</h1>
                <p>Connect with the PLP community and discover fellow alumni</p>
            </header>
            
            <div class="top-controls">
                <div class="search-container">
                    <form method="GET" action="" id="search-form">
                        <div class="search-input">
                            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name or ID">
                            <button type="submit" class="search-icon"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                </div>
                
                <div class="filter-container">
                    <button id="toggle-filters" class="toggle-filters-btn">Show Filters</button>
                </div>

                <div class="alumni-stats">
                    <div class="stat-box">
                        <span class="stat-number"><?php echo $total_records; ?></span>
                        <span class="stat-label">Total Alumni</span>
                    </div>
                    
                    <?php if (count($filter_conditions) > 0): ?>
                    <div class="stat-box filtered">
                        <span class="stat-number"><?php echo count($filter_conditions); ?></span>
                        <span class="stat-label">Active Filters</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="filter-panel" class="filter-panel">
                <form method="GET" action="" id="filter-form">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <div class="filter-grid">
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
                        
                        <div class="filter-group">
                            <label for="connected">Connection Status:</label>
                            <select id="connected" name="connected">
                                <option value="">All</option>
                                <option value="1" <?php echo $connected_filter === '1' ? 'selected' : ''; ?>>Connected</option>
                                <option value="0" <?php echo $connected_filter === '0' ? 'selected' : ''; ?>>Not Connected</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn-apply-filter">Apply</button>
                        <a href="alumni-list.php" class="btn-reset-filter">Reset</a>
                    </div>
                </form>
            </div>

            <div class="alumni-grid">
                <?php 
                if($alumni_result->num_rows > 0):
                    while($alumnus = $alumni_result->fetch_assoc()): 
                        $avatar_img = !empty($alumnus['avatar']) ? $alumnus['avatar'] : 'images/avatar.png';
                ?>
                <div class="alumni-card">
                    <div class="alumni-avatar">
                        <img src="<?php echo $avatar_img; ?>" alt="<?php echo htmlspecialchars($alumnus['firstname'] . ' ' . $alumnus['lastname']); ?>">
                        <?php if($alumnus['connected_to'] == 1): ?>
                            <span class="connection-badge connected"><i class="fas fa-link"></i></span>
                        <?php else: ?>
                            <span class="connection-badge not-connected"><i class="fas fa-unlink"></i></span>
                        <?php endif; ?>
                    </div>
                    <div class="alumni-details">
                        <h3><?php echo htmlspecialchars($alumnus['firstname'] . ' ' . $alumnus['lastname']); ?></h3>
                        <p class="alumni-course"><?php echo htmlspecialchars($alumnus['course_name']); ?></p>
                        <p class="alumni-batch">Batch <?php echo htmlspecialchars($alumnus['batch']); ?></p>
                    </div>
                </div>
                <?php 
                    endwhile;
                else:
                ?>
                <div class="no-alumni-message">
                    <i class="fas fa-user-graduate"></i>
                    <h3>No alumni found</h3>
                    <p><?php echo count($filter_conditions) > 0 ? 'No alumni match your filter criteria. Try adjusting your filters.' : 'No alumni records are available at this time.'; ?></p>
                </div>
                <?php endif; ?>
            </div>

            <?php if($total_pages > 1): ?>
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
            <?php endif; ?>
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
            
            document.addEventListener('DOMContentLoaded', function() {
                const searchForm = document.getElementById('search-form');
                searchForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                
                    const searchValue = document.getElementById('search').value;
                    
                    const currentUrl = new URL(window.location.href);
                    
                    if (searchValue) {
                        currentUrl.searchParams.set('search', searchValue);
                    } else {
                        currentUrl.searchParams.delete('search');
                    }
                    
                    currentUrl.searchParams.delete('page');
                    
                    window.location.href = currentUrl.toString();
                });
                
                const toggleFiltersBtn = document.getElementById('toggle-filters');
                toggleFiltersBtn.addEventListener('click', function() {
                    const filterPanel = document.getElementById('filter-panel');
                    const isVisible = filterPanel.classList.toggle('active');
                    this.textContent = isVisible ? 'Hide Filters' : 'Show Filters';
                });
                
                const activeFilters = <?php echo count($filter_conditions); ?>;
                if (activeFilters > 0) {
                    document.getElementById('filter-panel').classList.add('active');
                    document.getElementById('toggle-filters').textContent = 'Hide Filters';
                }
            });
        </script>
    </body>
</html>