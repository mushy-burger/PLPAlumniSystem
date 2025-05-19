<?php
session_start();
include 'admin/db_connect.php';

if (isset($_POST['submit']) && (!isset($_POST['gallery_id']) || empty($_POST['gallery_id']))) {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $upload_success = false;
    $error_message = '';
    
    if (isset($_FILES['gallery_image']) && $_FILES['gallery_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; 
        
        if (in_array($_FILES['gallery_image']['type'], $allowed_types) && $_FILES['gallery_image']['size'] <= $max_size) {
            $new_filename = uniqid() . '_' . basename($_FILES['gallery_image']['name']);
            $target_path = 'uploads/gallery/' . $new_filename;
            
            if (!is_dir('uploads/gallery')) {
                mkdir('uploads/gallery', 0777, true);
            }
            
            if (move_uploaded_file($_FILES['gallery_image']['tmp_name'], $target_path)) {
                $sql = "INSERT INTO gallery (title, description, image_path) VALUES ('$title', '$description', '$target_path')";
                
                if ($conn->query($sql) === TRUE) {
                    $upload_success = true;
                    $_SESSION['success_message'] = "Image uploaded successfully!";
                } else {
                    $error_message = "Database error: " . $conn->error;
                }
            } else {
                $error_message = "Failed to move uploaded file.";
            }
        } else {
            $error_message = "Invalid file. Please upload JPG, PNG, or GIF files under 5MB.";
        }
    } else {
        $error_message = "Please select an image to upload.";
    }
    
    if (!$upload_success) {
        $_SESSION['error_message'] = $error_message;
    }
    
    header("Location: admin-gallery.php");
    exit();
}

if(isset($_POST['update']) || (isset($_POST['submit']) && isset($_POST['gallery_id']) && !empty($_POST['gallery_id']))) {
    $id = intval($_POST['gallery_id']);
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $update_success = false;
    $error_message = '';
    
    $sql = "UPDATE gallery SET title = '$title', description = '$description'";
    
    if(isset($_FILES['gallery_image']) && $_FILES['gallery_image']['error'] == 0 && !empty($_FILES['gallery_image']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; 
        
        if (in_array($_FILES['gallery_image']['type'], $allowed_types) && $_FILES['gallery_image']['size'] <= $max_size) {
            $new_filename = uniqid() . '_' . basename($_FILES['gallery_image']['name']);
            $target_path = 'uploads/gallery/' . $new_filename;
            
            if (!is_dir('uploads/gallery')) {
                mkdir('uploads/gallery', 0777, true);
            }
            
            if (move_uploaded_file($_FILES['gallery_image']['tmp_name'], $target_path)) {
                $result = $conn->query("SELECT image_path FROM gallery WHERE id = $id");
                if($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $old_image = $row['image_path'];
                    
                    $sql .= ", image_path = '$target_path'";
                    
                    $sql .= " WHERE id = $id";
                    
                    if($conn->query($sql) === TRUE) {
                        if(file_exists($old_image)) {
                            unlink($old_image);
                        }
                        $update_success = true;
                        $_SESSION['success_message'] = "Gallery item updated successfully!";
                    } else {
                        $error_message = "Database error: " . $conn->error;
                    }
                }
            } else {
                $error_message = "Failed to move uploaded file.";
            }
        } else {
            $error_message = "Invalid file. Please upload JPG, PNG, or GIF files under 5MB.";
        }
    } else {
        $sql .= " WHERE id = $id";
        
        if($conn->query($sql) === TRUE) {
            $update_success = true;
            $_SESSION['success_message'] = "Gallery item updated successfully!";
        } else {
            $error_message = "Database error: " . $conn->error;
        }
    }
    
    if(!$update_success) {
        $_SESSION['error_message'] = $error_message;
    }
    
    header("Location: admin-gallery.php");
    exit();
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    $result = $conn->query("SELECT image_path FROM gallery WHERE id = $id");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $image_path = $row['image_path'];
        
        if ($conn->query("DELETE FROM gallery WHERE id = $id") === TRUE) {
            if (file_exists($image_path)) {
                unlink($image_path);
            }
            $_SESSION['success_message'] = "Image deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Error deleting image: " . $conn->error;
        }
    }
    
    header("Location: admin-gallery.php");
    exit();
}

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$search_condition = '';
if(!empty($search)) {
    $search_condition = " WHERE title LIKE '%$search%' OR description LIKE '%$search%' ";
}

$query = "SELECT * FROM gallery $search_condition ORDER BY id DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

$total_query = "SELECT COUNT(*) as total FROM gallery $search_condition";
$total_result = $conn->query($total_query);
$total_row = $total_result->fetch_assoc();
$total_items = $total_row['total'];
$total_pages = ceil($total_items / $limit);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Gallery | Alumni Portal</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/admin-gallery.css">
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
            <a href="admin-gallery.php" class="active"><img src="images/gallery.png" alt="Gallery"><span>Gallery</span></a>
            <a href="admin-course-list.php"><img src="images/course-list.png" alt="Course List"><span>Course List</span></a>
            <a href="admin-alumni-list.php"><img src="images/alumni_list.png" alt="Alumni List"><span>Alumni List</span></a>
            <a href="admin-alumni-upload.php"><img src="images/upload.png" alt="Alumni Upload"><span>Alumni Upload</span></a>
            <a href="admin-job.php"><img src="images/jobs.png" alt="Jobs"><span>Jobs</span></a>
            <a href="admin-event.php"> <img src="images/calendar.png" alt="Events"><span>Events</span></a>
            <a href="admin-forums.php"><img src="images/forums.png" alt="Forum"><span>Forum</span></a>
            <a href="admin-officers.php"><img src="images/users.png" alt="Officers"><span>Officers</span></a>
            <a href="admin-system-setting.php"><img src="images/settings.png" alt="System Settings"><span>System Settings</span></a>
            <a href="landing.php"><img src="images/log-out.png" alt="Log Out"><span>Log Out</span></a>
        </div>
    </div>

    <div class="content gallery-container">
        <div class="gallery-header">
            <h2>Manage Gallery Images</h2>
            <div class="gallery-controls">
                <div class="entries-control">
                    Show
                    <select id="entries-select" onchange="changeEntriesPerPage(this.value)">
                        <option value="5" <?php echo $limit == 5 ? 'selected' : ''; ?>>5</option>
                        <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                        <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                    </select>
                    Entries
                </div>
                <div class="search-control">
                    <label for="gallery-search">Search:</label>
                    <input type="text" id="gallery-search" placeholder="Enter search term..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button class="add-new-btn" id="add-gallery-btn">Add New Gallery Image</button>
            </div>
        </div>
        
        <?php if(isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success_message']; 
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error_message']; 
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <table class="gallery-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Upload Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($result && $result->num_rows > 0) {
                    $count = $offset + 1;
                    while ($row = $result->fetch_assoc()) {
                ?>
                <tr>
                    <td><?php echo $count++; ?></td>
                    <td><img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="Gallery Image" class="thumbnail"></td>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo substr(htmlspecialchars($row['description']), 0, 100) . (strlen($row['description']) > 100 ? '...' : ''); ?></td>
                    <td><?php echo date('M d, Y', strtotime($row['upload_date'])); ?></td>
                    <td class="action-buttons">
                        <button class="btn btn-primary edit-btn" data-id="<?php echo $row['id']; ?>" 
                                data-title="<?php echo htmlspecialchars($row['title']); ?>"
                                data-description="<?php echo htmlspecialchars($row['description']); ?>"
                                data-image="<?php echo htmlspecialchars($row['image_path']); ?>">Edit</button>
                        <button class="btn btn-danger delete-btn" 
                                onclick="confirmDelete(<?php echo $row['id']; ?>)">Delete</button>
                    </td>
                </tr>
                <?php
                    }
                } else {
                ?>
                <tr>
                    <td colspan="6" style="text-align: center;">No gallery images found</td>
                </tr>
                <?php
                }
                ?>
            </tbody>
        </table>

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

    <div id="gallery-modal" class="gallery-modal">
        <div class="gallery-modal-content">
            <div class="gallery-modal-header">
                <h3 id="modal-title">Add New Gallery Image</h3>
                <span class="close-modal">&times;</span>
            </div>
            <form id="gallery-form" method="post" enctype="multipart/form-data" action="admin-gallery.php">
                <input type="hidden" id="gallery-id" name="gallery_id">
                <input type="hidden" id="edit-action" name="form_action" value="submit">
                
                <div class="form-group">
                    <label for="title">Title:</label>
                    <input type="text" id="title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" required></textarea>
                </div>
                
                <div class="form-group" id="image-upload-group">
                    <label for="gallery_image">Image:</label>
                    <input type="file" id="gallery_image" name="gallery_image" accept="image/jpeg, image/png">
                    <div class="image-preview">
                        <img id="image-preview-element" src="#" alt="Preview">
                    </div>
                    <div id="keep-existing-notice" style="display: none;" class="keep-existing">
                        <input type="checkbox" id="keep-existing-check" checked disabled>
                        <label for="keep-existing-check">Keep existing image if no new file is selected</label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="cancel-btn">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submit-btn" name="submit">Add New Gallery Item</button>
                    <button type="submit" class="btn btn-primary" id="update-btn" name="update" style="display: none;">Update Gallery Item</button>
                </div>
            </form>
        </div>
    </div>

    <div id="delete-modal" class="gallery-modal">
        <div class="gallery-modal-content">
            <div class="gallery-modal-header">
                <h3>Confirm Deletion</h3>
                <span class="close-modal">&times;</span>
            </div>
            <p>Are you sure you want to delete this gallery image? This action cannot be undone.</p>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" id="cancel-delete-btn">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-btn">Delete</button>
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
        
        const galleryModal = document.getElementById('gallery-modal');
        const deleteModal = document.getElementById('delete-modal');
        const addGalleryBtn = document.getElementById('add-gallery-btn');
        const cancelBtn = document.getElementById('cancel-btn');
        const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
        const closeModalBtns = document.querySelectorAll('.close-modal');
        const imageInput = document.getElementById('gallery_image');
        const imagePreview = document.getElementById('image-preview-element');
        let deleteId = null;
        
        addGalleryBtn.addEventListener('click', function() {
            document.getElementById('modal-title').textContent = 'Add New Gallery Image';
            document.getElementById('gallery-form').reset();
            document.getElementById('gallery-id').value = '';
            document.getElementById('edit-action').value = 'submit';
            document.getElementById('submit-btn').style.display = 'inline-block';
            document.getElementById('update-btn').style.display = 'none';
            document.getElementById('keep-existing-notice').style.display = 'none';
            document.getElementById('gallery_image').required = true;
            imagePreview.style.display = 'none';
            galleryModal.style.display = 'block';
        });
        
        cancelBtn.addEventListener('click', function() {
            galleryModal.style.display = 'none';
        });
        
        cancelDeleteBtn.addEventListener('click', function() {
            deleteModal.style.display = 'none';
        });
        
        closeModalBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                galleryModal.style.display = 'none';
                deleteModal.style.display = 'none';
            });
        });
        
        imageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
        
        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('edit-btn')) {
                document.getElementById('modal-title').textContent = 'Edit Gallery Image';
                document.getElementById('gallery-form').reset();
                document.getElementById('gallery-id').value = e.target.dataset.id;
                document.getElementById('title').value = e.target.dataset.title;
                document.getElementById('description').value = e.target.dataset.description;
                document.getElementById('edit-action').value = 'update';
                document.getElementById('submit-btn').style.display = 'none';
                document.getElementById('update-btn').style.display = 'inline-block';
                document.getElementById('keep-existing-notice').style.display = 'block';
                document.getElementById('gallery_image').required = false;
                
                if (e.target.dataset.image) {
                    imagePreview.src = e.target.dataset.image;
                    imagePreview.style.display = 'block';
                } else {
                    imagePreview.style.display = 'none';
                }
                
                galleryModal.style.display = 'block';
            }
        });
        
        document.getElementById('gallery-form').addEventListener('submit', function(e) {
            const isUpdate = document.getElementById('gallery-id').value !== '';
            const submitBtn = document.getElementById('submit-btn');
            const updateBtn = document.getElementById('update-btn');
            
            if (isUpdate) {
                submitBtn.style.display = 'none';
                updateBtn.style.display = 'inline-block';
            } else {
                submitBtn.style.display = 'inline-block';
                updateBtn.style.display = 'none';
            }
        });
        
        function confirmDelete(id) {
            deleteId = id;
            deleteModal.style.display = 'block';
        }
        
        document.getElementById('confirm-delete-btn').addEventListener('click', function() {
            if (deleteId) {
                window.location.href = 'admin-gallery.php?delete=' + deleteId;
            }
        });
        
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
        
        document.getElementById('gallery-search').addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const rows = document.querySelectorAll('.gallery-table tbody tr');
            
            rows.forEach(function(row) {
                const title = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                const description = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
                
                if (title.includes(searchText) || description.includes(searchText)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>

</body>

</html>
