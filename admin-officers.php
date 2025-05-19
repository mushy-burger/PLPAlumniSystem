<?php
session_start();
include 'admin/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_officer']) || isset($_POST['update_officer'])) {
        $name = $conn->real_escape_string($_POST['name']);
        $position = $conn->real_escape_string($_POST['position']);
        $class_year = $conn->real_escape_string($_POST['class_year']);
        $course = $conn->real_escape_string($_POST['course']);
        $image_path = '';
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "uploads/officers/";
            
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if ($check !== false) {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $image_path = $target_file;
                }
            }
        }
        
        if (isset($_POST['add_officer'])) {
            $sql = "INSERT INTO alumni_officers (name, position, class_year, course, image_path) 
                    VALUES ('$name', '$position', '$class_year', '$course', '$image_path')";
            
            if ($conn->query($sql)) {
                $success_msg = "New officer added successfully!";
            } else {
                $error_msg = "Error: " . $conn->error;
            }
        } else if (isset($_POST['update_officer'])) {
            $id = intval($_POST['officer_id']);
            
            if (empty($image_path)) {
                $sql = "UPDATE alumni_officers SET 
                        name = '$name', 
                        position = '$position', 
                        class_year = '$class_year', 
                        course = '$course'
                        WHERE id = $id";
            } else {
                $old_image_query = "SELECT image_path FROM alumni_officers WHERE id = $id";
                $old_image_result = $conn->query($old_image_query);
                if ($old_image_result && $old_image_result->num_rows > 0) {
                    $old_image = $old_image_result->fetch_assoc()['image_path'];
                    if (!empty($old_image) && file_exists($old_image)) {
                        unlink($old_image);
                    }
                }
                
                $sql = "UPDATE alumni_officers SET 
                        name = '$name', 
                        position = '$position', 
                        class_year = '$class_year', 
                        course = '$course', 
                        image_path = '$image_path'
                        WHERE id = $id";
            }
            
            if ($conn->query($sql)) {
                $success_msg = "Officer updated successfully!";
            } else {
                $error_msg = "Error: " . $conn->error;
            }
        }
    } else if (isset($_POST['delete_officer'])) {
        $id = intval($_POST['officer_id']);
        
        $image_query = "SELECT image_path FROM alumni_officers WHERE id = $id";
        $image_result = $conn->query($image_query);
        if ($image_result && $image_result->num_rows > 0) {
            $image_path = $image_result->fetch_assoc()['image_path'];
            if (!empty($image_path) && file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        $sql = "DELETE FROM alumni_officers WHERE id = $id";
        if ($conn->query($sql)) {
            $success_msg = "Officer deleted successfully!";
        } else {
            $error_msg = "Error: " . $conn->error;
        }
    }
}

$current_year = date('Y');
$selected_year = isset($_GET['year']) ? $_GET['year'] : $current_year;

$any_officers_query = "SELECT COUNT(*) as count FROM alumni_officers";
$any_officers_result = $conn->query($any_officers_query);
$has_any_officers = ($any_officers_result && $any_officers_result->fetch_assoc()['count'] > 0);

$years_query = "SELECT DISTINCT class_year FROM alumni_officers ORDER BY class_year DESC";
$years_result = $conn->query($years_query);
$available_years = [];

if ($years_result && $years_result->num_rows > 0) {
    while($row = $years_result->fetch_assoc()) {
        $available_years[] = $row['class_year'];
    }
    
    if (!in_array($selected_year, $available_years) && !empty($available_years)) {
        $selected_year = $available_years[0]; 
    }
} else if (!$has_any_officers) {
    $available_years[] = $current_year;
}

$officers_query = "SELECT * FROM alumni_officers 
                  WHERE class_year = '$selected_year' 
                  ORDER BY 
                  CASE position 
                      WHEN 'President' THEN 1
                      WHEN 'Vice President' THEN 2
                      WHEN 'Secretary' THEN 3
                      WHEN 'Treasurer' THEN 4
                      WHEN 'Auditor' THEN 5
                      WHEN 'Public Relations Officer' THEN 6
                      WHEN 'Project Coordinator' THEN 7
                      WHEN 'Board Member' THEN 8
                      ELSE 9
                  END, name";
$officers_result = $conn->query($officers_query);

$count_query = "SELECT COUNT(*) as count FROM alumni_officers WHERE class_year = '$selected_year'";
$count_result = $conn->query($count_query);
$officers_count = $count_result->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Alumni Officers | Alumni Portal</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .officers-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .officer-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #eaeaea;
        }
        
        .officer-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.15);
        }
        
        .officer-image {
            height: 200px;
            overflow: hidden;
            position: relative;
        }
        
        .officer-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .officer-card:hover .officer-image img {
            transform: scale(1.05);
        }
        
        .officer-info {
            padding: 20px;
        }
        
        .officer-info h3 {
            margin: 0 0 5px;
            color: #003366;
            font-size: 18px;
        }
        
        .officer-position {
            display: block;
            color: #0047AB;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 14px;
            text-transform: uppercase;
        }
        
        .officer-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eaeaea;
        }
        
        .form-container {
            background-color: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            border: 1px solid #eaeaea;
        }
        
        .form-title {
            color: #003366;
            margin-top: 0;
            border-bottom: 2px solid #0047AB;
            padding-bottom: 10px;
            margin-bottom: 25px;
            font-size: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #0047AB;
            box-shadow: 0 0 0 3px rgba(0, 71, 171, 0.1);
            outline: none;
        }
        
        .btn-container {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 25px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .btn-primary {
            background-color: #0047AB;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #003366;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 25px;
            font-size: 14px;
            display: flex;
            align-items: center;
            opacity: 1;
            transition: opacity 0.5s ease;
        }
        
        .alert:before {
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            margin-right: 10px;
            font-size: 16px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-success:before {
            content: '\f058'; 
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-danger:before {
            content: '\f057';
        }
        
        .edit-form {
            display: none;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 0;
        }
        
        .form-col {
            flex: 1;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 20px;
            color: #003366;
            margin: 0;
        }
        
        .officer-count {
            background-color: #0047AB;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .no-officers {
            background-color: #f8f9fa;
            padding: 40px;
            text-align: center;
            border-radius: 8px;
            border: 1px dashed #ddd;
        }
        
        .no-officers i {
            font-size: 48px;
            color: #0047AB;
            opacity: 0.5;
            margin-bottom: 15px;
        }
        
        .no-officers p {
            color: #666;
            font-size: 16px;
            margin: 0;
        }
        
        .dashboard-content-header {
            margin-bottom: 30px;
        }
        
        .dashboard-content-header h2 {
            font-size: 24px;
            color: #003366;
            margin-bottom: 10px;
        }
        
        .dashboard-content-header p {
            color: #666;
            font-size: 14px;
            margin: 0;
        }
        
        .image-preview {
            margin-top: 10px;
            max-width: 100%;
            height: 150px;
            border-radius: 4px;
            overflow: hidden;
            display: none;
        }
        
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .officer-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(0, 71, 171, 0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .year-selector {
            margin: 0;
        }
        
        .year-selector select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            color: #333;
            background-color: white;
            cursor: pointer;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        .year-selector select:hover {
            border-color: #0047AB;
        }
        
        .year-selector select:focus {
            border-color: #0047AB;
            box-shadow: 0 0 0 3px rgba(0, 71, 171, 0.1);
            outline: none;
        }
    </style>
</head>

<body class="dashboard-body">

    <div class="interface-header">
        <img src="images/logo.png" alt="PLP Logo" class="logo-interface">
        <div class="text">
            <div class="school-name">Pamantasan Ng Lungsod Ng Pasig</div>
            <p class="p-size">Alkade Jose St. Kapasigan, Pasig City</p>
        </div>
    </div>

    <div class="dashboard-container">
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
                <a href="admin-event.php"> <img src="images/calendar.png" alt="Events"><span>Events</span></a>
                <a href="admin-forums.php"><img src="images/forums.png" alt="Forum"><span>Forum</span></a>
                <a href="admin-officers.php" class="active"><img src="images/users.png" alt="Officers"><span>Officers</span></a>
                <a href="admin-system-setting.php"><img src="images/settings.png" alt="System Settings"><span>System Settings</span></a>
                <a href="landing.php"><img src="images/log-out.png" alt="Log Out"><span>Log Out</span></a>
            </div>
        </div>

        <div class="main-dashboard-content">
            <header class="dashboard-header">Manage Alumni Officers</header>
            <hr class="dashboard-divider">
            
            <div class="dashboard-content-header">
                <h2>Alumni Officers Management</h2>
                <p>Add, edit, or remove officers displayed on the landing page</p>
            </div>
            
            <?php if(isset($success_msg)): ?>
                <div class="alert alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error_msg)): ?>
                <div class="alert alert-danger"><?php echo $error_msg; ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <h3 class="form-title">Add New Officer</h3>
                <form action="" method="post" enctype="multipart/form-data" id="addOfficerForm">
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" required placeholder="Enter officer's full name">
                            </div>
                            
                            <div class="form-group">
                                <label for="position">Position</label>
                                <select class="form-control" id="position" name="position" required>
                                    <option value="">Select Position</option>
                                    <option value="President">President</option>
                                    <option value="Vice President">Vice President</option>
                                    <option value="Secretary">Secretary</option>
                                    <option value="Treasurer">Treasurer</option>
                                    <option value="Auditor">Auditor</option>
                                    <option value="Public Relations Officer">Public Relations Officer</option>
                                    <option value="Project Coordinator">Project Coordinator</option>
                                    <option value="Board Member">Board Member</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="class_year">Class Year</label>
                                <input type="text" class="form-control" id="class_year" name="class_year" placeholder="e.g., 2010">
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label for="course">Course</label>
                                <input type="text" class="form-control" id="course" name="course" placeholder="e.g., BS Computer Science">
                            </div>
                            
                            <div class="form-group">
                                <label for="image">Profile Image</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*" onchange="previewImage(this, 'imagePreview')">
                                <div class="image-preview" id="imagePreview">
                                    <img src="#" alt="Image Preview">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="btn-container">
                        <button type="submit" name="add_officer" class="btn btn-primary"><i class="fas fa-plus"></i> Add Officer</button>
                    </div>
                </form>
            </div>
            
            <div class="section-header">
                <h3 class="section-title">Officers (<?php echo $selected_year; ?>)</h3>
                <div class="header-actions">
                    <form method="get" class="year-selector">
                        <select name="year" onchange="this.form.submit()" class="form-control">
                            <?php
                            if(!empty($available_years)): ?>
                                <?php foreach($available_years as $year): ?>
                                    <option value="<?php echo $year; ?>" <?php echo $year == $selected_year ? 'selected' : ''; ?>>
                                        <?php echo $year; ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="<?php echo $current_year; ?>"><?php echo $current_year; ?></option>
                            <?php endif; ?>
                        </select>
                    </form>
                    <?php if($officers_count > 0): ?>
                        <span class="officer-count"><?php echo $officers_count; ?> Officers</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if($officers_result && $officers_result->num_rows > 0): ?>
                <div class="officers-container">
                    <?php while($officer = $officers_result->fetch_assoc()): ?>
                        <div class="officer-card">
                            <div class="officer-image">
                                <img src="<?php echo !empty($officer['image_path']) ? $officer['image_path'] : 'images/user.jpg'; ?>" alt="<?php echo htmlspecialchars($officer['position']); ?>">
                            </div>
                            <div class="officer-info">
                                <h3><?php echo htmlspecialchars($officer['name']); ?></h3>
                                <span class="officer-position"><?php echo htmlspecialchars($officer['position']); ?></span>
                                <p>Class of <?php echo htmlspecialchars($officer['class_year']); ?>, <?php echo htmlspecialchars($officer['course']); ?></p>
                                <div class="officer-actions">
                                    <button class="btn btn-primary edit-btn" data-id="<?php echo $officer['id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($officer['name']); ?>"
                                            data-position="<?php echo htmlspecialchars($officer['position']); ?>"
                                            data-class-year="<?php echo htmlspecialchars($officer['class_year']); ?>"
                                            data-course="<?php echo htmlspecialchars($officer['course']); ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form action="" method="post" style="display:inline;">
                                        <input type="hidden" name="officer_id" value="<?php echo $officer['id']; ?>">
                                        <button type="submit" name="delete_officer" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this officer?');">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-officers">
                    <i class="fas fa-users"></i>
                    <p>No officers found. Add your first officer using the form above.</p>
                </div>
            <?php endif; ?>
            
            <div class="form-container edit-form" id="editForm">
                <h3 class="form-title">Edit Officer</h3>
                <form action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" id="edit_officer_id" name="officer_id">
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="edit_name">Full Name</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_position">Position</label>
                                <select class="form-control" id="edit_position" name="position" required>
                                    <option value="">Select Position</option>
                                    <option value="President">President</option>
                                    <option value="Vice President">Vice President</option>
                                    <option value="Secretary">Secretary</option>
                                    <option value="Treasurer">Treasurer</option>
                                    <option value="Auditor">Auditor</option>
                                    <option value="Public Relations Officer">Public Relations Officer</option>
                                    <option value="Project Coordinator">Project Coordinator</option>
                                    <option value="Board Member">Board Member</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_class_year">Class Year</label>
                                <input type="text" class="form-control" id="edit_class_year" name="class_year">
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label for="edit_course">Course</label>
                                <input type="text" class="form-control" id="edit_course" name="course">
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_image">Profile Image (Leave empty to keep current image)</label>
                                <input type="file" class="form-control" id="edit_image" name="image" accept="image/*" onchange="previewImage(this, 'editImagePreview')">
                                <div class="image-preview" id="editImagePreview">
                                    <img src="#" alt="Image Preview">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="btn-container">
                        <button type="button" class="btn btn-danger" id="cancelEditBtn"><i class="fas fa-times"></i> Cancel</button>
                        <button type="submit" name="update_officer" class="btn btn-primary"><i class="fas fa-save"></i> Update Officer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            
            const toggleBtn = document.querySelector('.toggle-btn');
            if (sidebar.classList.contains('collapsed')) {
                toggleBtn.innerHTML = '&#x25B6;'; 
            } else {
                toggleBtn.innerHTML = '&#x25C0;'; 
            }
        }
        
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const previewImg = preview.querySelector('img');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(input.files[0]);
            } else {
                previewImg.src = '#';
                preview.style.display = 'none';
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const editButtons = document.querySelectorAll('.edit-btn');
            const editForm = document.getElementById('editForm');
            const addForm = document.getElementById('addOfficerForm');
            const cancelEditBtn = document.getElementById('cancelEditBtn');
            
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const position = this.getAttribute('data-position');
                    const classYear = this.getAttribute('data-class-year');
                    const course = this.getAttribute('data-course');
                    
                    document.getElementById('edit_officer_id').value = id;
                    document.getElementById('edit_name').value = name;
                    document.getElementById('edit_position').value = position;
                    document.getElementById('edit_class_year').value = classYear;
                    document.getElementById('edit_course').value = course;
                    
                    editForm.style.display = 'block';
                    addForm.closest('.form-container').style.display = 'none';
                    
                    editForm.scrollIntoView({behavior: 'smooth'});
                });
            });
            
            cancelEditBtn.addEventListener('click', function() {
                editForm.style.display = 'none';
                addForm.closest('.form-container').style.display = 'block';
            });
            
            const alerts = document.querySelectorAll('.alert');
            if (alerts.length > 0) {
                setTimeout(function() {
                    alerts.forEach(alert => {
                        alert.style.opacity = '0';
                        alert.style.transition = 'opacity 0.5s ease';
                        setTimeout(() => alert.style.display = 'none', 500);
                    });
                }, 5000);
            }
        });
    </script>

</body>
</html> 