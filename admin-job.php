<?php
session_start();
include 'admin/db_connect.php';

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = ($page - 1) * $items_per_page;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$where_clause = "";
$params = [];

if (!empty($search)) {
    $where_clause = " WHERE company LIKE ? OR job_title LIKE ? OR location LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

$query = "SELECT c.*, u.name as posted_by 
          FROM careers c 
          LEFT JOIN users u ON c.user_id = u.alumni_id
          $where_clause
          ORDER BY c.date_created DESC
          LIMIT $offset, $items_per_page";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$count_query = "SELECT COUNT(*) as total FROM careers $where_clause";
$count_stmt = $conn->prepare($count_query);

if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_row = $count_result->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $items_per_page);

if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $job_id = intval($_GET['delete']);
    $delete_stmt = $conn->prepare("DELETE FROM careers WHERE id = ?");
    $delete_stmt->bind_param("i", $job_id);
    
    if($delete_stmt->execute()) {
        $_SESSION['success'] = "Job opportunity deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete job opportunity: " . $conn->error;
    }
    
    header("Location: admin-job.php");
    exit();
}

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
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Management - Alumni Portal</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/admin-job.css">
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
            <a href="admin-job.php" class="active"><img src="images/jobs.png" alt="Jobs"><span>Jobs</span></a>
            <a href="admin-event.php"> <img src="images/calendar.png" alt="Events"><span>Events</span>
            <a href="admin-forums.php"><img src="images/forums.png" alt="Forum"><span>Forum</span></a>
            <a href="admin-officers.php"><img src="images/users.png" alt="Officers"><span>Officers</span></a>
            <a href="admin-system-setting.php"><img src="images/settings.png" alt="System Settings"><span>System Settings</span></a>
            <a href="landing.php"><img src="images/log-out.png" alt="Log Out"><span>Log Out</span></a>
        </div>
    </div>

    <main class="content">
        <div class="admin-job-content">
            <header>Job Opportunity Management</header>
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
                        <form method="GET" action="admin-job.php" class="search-form">
                            <div class="entries-search-controls">
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
                                    <input type="text" name="search" class="search-input" value="<?php echo htmlspecialchars($search); ?>" placeholder="Company, Job Title...">
                                    <button type="submit" class="alist-filter">Filter</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="right-controls">
                    <button class="new-button" id="openAddModal">New Job</button>
                </div>
            </div>

            <div class="job-stats">
                <div class="stat-box">
                    <span class="stat-number"><?php echo $total_records; ?></span>
                    <span class="stat-label">Total Jobs</span>
                </div>
                <?php if (!empty($search)): ?>
                <div class="stat-box filtered">
                    <span class="stat-number">1</span>
                    <span class="stat-label">Active Filter</span>
                </div>
                <?php endif; ?>
            </div>

            <div class="table-wrapper">
                <table class="job-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Company</th>
                            <th>Job Title</th>
                            <th>Location</th>
                            <th>Posted By</th>
                            <th>Date Posted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if($result->num_rows > 0):
                            $count = $offset + 1;
                            while($row = $result->fetch_assoc()):
                                $description = strip_tags(html_entity_decode($row['description']));
                                $description = substr($description, 0, 100) . (strlen($description) > 100 ? '...' : '');
                        ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td><?php echo htmlspecialchars($row['company']); ?></td>
                            <td><?php echo htmlspecialchars($row['job_title']); ?></td>
                            <td><?php echo htmlspecialchars($row['location']); ?></td>
                            <td><?php echo htmlspecialchars($row['posted_by']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['date_created'])); ?></td>
                            <td>
                                <button class="view-btn" data-id="<?php echo $row['id']; ?>" 
                                        data-company="<?php echo htmlspecialchars($row['company']); ?>"
                                        data-title="<?php echo htmlspecialchars($row['job_title']); ?>"
                                        data-location="<?php echo htmlspecialchars($row['location']); ?>"
                                        data-description="<?php echo htmlspecialchars($row['description']); ?>"
                                        onclick="viewJob(this)"><i class="fas fa-eye"></i> View</button>
                                <button class="edit-btn" data-id="<?php echo $row['id']; ?>"
                                        data-company="<?php echo htmlspecialchars($row['company']); ?>"
                                        data-title="<?php echo htmlspecialchars($row['job_title']); ?>"
                                        data-location="<?php echo htmlspecialchars($row['location']); ?>"
                                        data-description="<?php echo htmlspecialchars($row['description']); ?>"
                                        onclick="editJob(this)"><i class="fas fa-edit"></i> Edit</button>
                                <button class="delete-btn" onclick="confirmDelete(<?php echo $row['id']; ?>)"><i class="fas fa-trash-alt"></i> Delete</button>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="7" class="no-records">
                                <i class="fas fa-info-circle"></i> No job opportunities found
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
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <button <?php echo $i == $page ? 'class="active"' : ''; ?> 
                                onclick="changePage(<?php echo $i; ?>)"><?php echo $i; ?></button>
                    <?php endfor; ?>
                    
                    <button <?php echo $page >= $total_pages ? 'disabled' : ''; ?> 
                            onclick="changePage(<?php echo $page + 1; ?>)">Next</button>
                </div>
            </div>
        </div>
    </main>

    <div class="job-modal" id="jobModal">
        <div class="job-modal-content">
            <h2>Job Opportunity Details</h2>
            <p><strong>Company:</strong> <span id="modalCompany"></span></p>
            <p><strong>Job Title:</strong> <span id="modalTitle"></span></p>
            <p><strong>Location:</strong> <span id="modalLocation"></span></p>
            <hr>
            <div id="modalDescription"></div>
            <button class="close-job-modal">Close</button>
        </div>
    </div>

    <div class="job-modal" id="editJobModal">
        <div class="job-modal-content">
            <h2>Edit Job Post</h2>
            <form id="editJobForm" method="POST" action="admin/job_actions.php">
                <input type="hidden" id="editJobId" name="job_id">
                <input type="hidden" name="action" value="edit">

                <label for="editCompany">Company</label>
                <input type="text" id="editCompany" name="company" class="edit-input" required>

                <label for="editTitle">Job Title</label>
                <input type="text" id="editTitle" name="job_title" class="edit-input" required>

                <label for="editLocation">Location</label>
                <input type="text" id="editLocation" name="location" class="edit-input" placeholder="Enter city, address, etc." required>

                <label for="editModality">Work Setup</label>
                <div class="modality-options">
                    <label class="modality-option">
                        <input type="radio" name="modality" value="WFH" required>
                        <span>WFH</span>
                    </label>
                    <label class="modality-option">
                        <input type="radio" name="modality" value="Onsite" required>
                        <span>Onsite</span>
                    </label>
                    <label class="modality-option">
                        <input type="radio" name="modality" value="Hybrid" required>
                        <span>Hybrid</span>
                    </label>
                </div>

                <label for="editDescription">Description</label>
                <textarea id="editDescription" name="description" class="edit-textarea" required></textarea>

                <div class="modal-buttons">
                    <button type="submit">Save</button>
                    <button type="button" id="cancelEditBtn" class="cancel-btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="job-modal" id="addJobModal">
        <div class="job-modal-content">
            <h2>Add New Job Opportunity</h2>
            <form id="addJobForm" method="POST" action="admin/job_actions.php">
                <input type="hidden" name="action" value="add">

                <label for="addCompany">Company</label>
                <input type="text" id="addCompany" name="company" class="edit-input" placeholder="Enter company name" required>

                <label for="addTitle">Job Title</label>
                <input type="text" id="addTitle" name="job_title" class="edit-input" placeholder="Enter job title" required>

                <label for="addLocation">Location</label>
                <input type="text" id="addLocation" name="location" class="edit-input" placeholder="Enter city, address, etc." required>

                <label for="addModality">Work Setup</label>
                <div class="modality-options">
                    <label class="modality-option">
                        <input type="radio" name="modality" value="WFH" required checked>
                        <span>WFH</span>
                    </label>
                    <label class="modality-option">
                        <input type="radio" name="modality" value="Onsite" required>
                        <span>Onsite</span>
                    </label>
                    <label class="modality-option">
                        <input type="radio" name="modality" value="Hybrid" required>
                        <span>Hybrid</span>
                    </label>
                </div>

                <label for="addDescription">Description</label>
                <textarea id="addDescription" name="description" class="edit-textarea" placeholder="Enter job description" required></textarea>

                <div class="modal-buttons">
                    <button type="submit">Save</button>
                    <button type="button" id="cancelAddBtn" class="cancel-btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="job-modal" id="deleteJobModal">
        <div class="job-modal-content">
            <h2>Delete Job Post</h2>
            <p>Are you sure you want to delete this job posting? This action cannot be undone.</p>
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

        const modal = document.getElementById("jobModal");
        const closeModal = document.querySelector(".close-job-modal");

        function viewJob(btn) {
            const company = btn.getAttribute('data-company');
            const title = btn.getAttribute('data-title');
            const location = btn.getAttribute('data-location');
            const description = btn.getAttribute('data-description');

            document.getElementById('modalCompany').textContent = company;
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalLocation').textContent = location;
            document.getElementById('modalDescription').innerHTML = description;

            modal.style.display = "flex";
        }

        closeModal.addEventListener("click", () => {
            modal.style.display = "none";
        });

        const editModal = document.getElementById("editJobModal");
        const cancelEditBtn = document.getElementById("cancelEditBtn");

        function editJob(btn) {
            const id = btn.getAttribute('data-id');
            const company = btn.getAttribute('data-company');
            const title = btn.getAttribute('data-title');
            const location = btn.getAttribute('data-location');
            const description = btn.getAttribute('data-description');

            document.getElementById('editJobId').value = id;
            document.getElementById('editCompany').value = company;
            document.getElementById('editTitle').value = title;
            
            let locationText = location;
            let modality = 'WFH';
            
            const modalityMatch = location.match(/\s*\((WFH|Onsite|Hybrid)\)$/);
            if (modalityMatch) {
                locationText = location.replace(modalityMatch[0], '').trim();
                modality = modalityMatch[1];
            }
            
            document.getElementById('editLocation').value = locationText;
            
            const modalityRadios = document.querySelectorAll('input[name="modality"]');
            modalityRadios.forEach(radio => {
                if (radio.value === modality) {
                    radio.checked = true;
                }
            });
            
            document.getElementById('editDescription').value = description;
            
            editModal.style.display = "flex";
        }

        cancelEditBtn.addEventListener("click", () => {
            editModal.style.display = "none";
        });

        const addModal = document.getElementById("addJobModal");
        const openAddModalBtn = document.getElementById("openAddModal");
        const cancelAddBtn = document.getElementById("cancelAddBtn");

        openAddModalBtn.addEventListener("click", () => {
            addModal.style.display = "flex";
            document.getElementById('addLocation').value = '';
            const modalityRadios = document.querySelectorAll('input[name="modality"]');
            modalityRadios.forEach(radio => {
                if (radio.value === 'WFH') {
                    radio.checked = true;
                }
            });
        });

        cancelAddBtn.addEventListener("click", () => {
            addModal.style.display = "none";
        });

        const deleteModal = document.getElementById("deleteJobModal");
        const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
        const cancelDeleteBtn = document.getElementById("cancelDeleteBtn");
        let deleteId = null;

        function confirmDelete(id) {
            deleteId = id;
            deleteModal.style.display = "flex";
        }

        confirmDeleteBtn.addEventListener("click", () => {
            if(deleteId) {
                window.location.href = `admin-job.php?delete=${deleteId}`;
            }
        });

        cancelDeleteBtn.addEventListener("click", () => {
            deleteModal.style.display = "none";
        });
        
        function changePage(page) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('page', page);
            window.location.href = currentUrl.toString();
        }

        window.addEventListener("click", (e) => {
            if (e.target === modal) {
                modal.style.display = "none";
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
    </script>
</body>
</html>
