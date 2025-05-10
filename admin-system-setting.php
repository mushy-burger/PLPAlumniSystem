<?php
session_start();

include 'admin/db_connect.php';

$systemName = "";
$email = "";
$contact = "";
$aboutContent = "";
$coverImg = "";
$msg = "";
$msgClass = "";

$query = "SELECT * FROM system_settings LIMIT 1";
$result = $conn->query($query);
if($result->num_rows > 0) {
    $settings = $result->fetch_assoc();
    $systemName = $settings['name'];
    $email = $settings['email'];
    $contact = $settings['contact'];
    $aboutContent = $settings['about_content'];
    $coverImg = $settings['cover_img'];
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $systemName = $conn->real_escape_string($_POST['systemName']);
    $email = $conn->real_escape_string($_POST['email']);
    $contact = $conn->real_escape_string($_POST['contact']);
    $aboutContent = $conn->real_escape_string($_POST['aboutContent']);
    
    $upload_path = "";
    if(isset($_FILES['imageUpload']) && $_FILES['imageUpload']['error'] == 0) {
        $target_dir = "uploads/";
        
        if(!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $filename = time().'_'.basename($_FILES["imageUpload"]["name"]);
        $target_file = $target_dir . $filename;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        $check = getimagesize($_FILES["imageUpload"]["tmp_name"]);
        if($check !== false) {
            if($_FILES["imageUpload"]["size"] <= 5000000) {
                if($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg" || $imageFileType == "gif") {
                    if(move_uploaded_file($_FILES["imageUpload"]["tmp_name"], $target_file)) {
                        $upload_path = $target_file;
                    } else {
                        $msg = "Sorry, there was an error uploading your file.";
                        $msgClass = "alert-error";
                    }
                } else {
                    $msg = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                    $msgClass = "alert-error";
                }
            } else {
                $msg = "Sorry, your file is too large. Maximum 5MB allowed.";
                $msgClass = "alert-error";
            }
        } else {
            $msg = "File is not an image.";
            $msgClass = "alert-error";
        }
    }
    
    if(empty($msg)) {
        $sql = "";
        if($result->num_rows > 0) {
            $sql = "UPDATE system_settings SET name = '$systemName', email = '$email', contact = '$contact', about_content = '$aboutContent'";
            if(!empty($upload_path)) {
                $sql .= ", cover_img = '$upload_path'";
            }
            $sql .= " WHERE id = " . $settings['id'];
        } else {
            $cover_img_val = !empty($upload_path) ? "'$upload_path'" : "''";
            $sql = "INSERT INTO system_settings (name, email, contact, about_content, cover_img) VALUES 
                    ('$systemName', '$email', '$contact', '$aboutContent', $cover_img_val)";
        }
        
        if($conn->query($sql)) {
            $msg = "System settings updated successfully.";
            $msgClass = "alert-success";
        } else {
            $msg = "Error updating system settings: " . $conn->error;
            $msgClass = "alert-error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings | Alumni Portal</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/system-settings.css">
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
            <a href="admin-event.php"><img src="images/calendar.png" alt="Events"><span>Events</span></a>
            <a href="admin-forums.php"><img src="images/forums.png" alt="Forum"><span>Forum</span></a>
            <a href="admin-system-setting.php" class="active"><img src="images/settings.png" alt="System Settings"><span>System Settings</span></a>
            <a href="landing.php"><img src="images/log-out.png" alt="Log Out"><span>Log Out</span></a>
        </div>
    </div>

    <div class="setting-content">
        <h2>System Settings</h2>
        
        <?php if(!empty($msg)): ?>
            <div class="<?php echo $msgClass; ?>">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>
        
        <form class="settings-form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data">
            <div class="settings-card">
                <div class="form-row">
                    <div class="form-group">
                        <label for="systemName">System Name</label>
                        <input type="text" id="systemName" name="systemName" value="<?php echo htmlspecialchars($systemName); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="contact">Contact</label>
                    <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars($contact); ?>">
                </div>

                <div class="form-group">
                    <label for="aboutContent">About Content</label>
                    <textarea id="aboutContent" name="aboutContent" rows="8"><?php echo htmlspecialchars($aboutContent); ?></textarea>
                </div>

                <div class="form-row media-upload">
                    <div class="form-group upload-group">
                        <label for="imageUpload">Logo/Cover Image</label>
                        <input type="file" id="imageUpload" name="imageUpload" accept="image/jpeg, image/png">
                        <p class="file-hint">Recommended size: 1200x400px. Max size: 5MB</p>
                    </div>
                    
                    <div class="preview-image">
                        <?php if(!empty($coverImg)): ?>
                            <img src="<?php echo $coverImg; ?>" alt="Current Cover Image">
                        <?php else: ?>
                            <div class="no-image">No image uploaded</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="save-button">Save Changes</button>
                    <button type="reset" class="reset-button">Reset</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            const toggleBtn = document.querySelector('.toggle-btn');
            toggleBtn.innerHTML = sidebar.classList.contains('collapsed') ? '&#x25B6;' : '&#x25C0;';
        }
        
        document.getElementById('imageUpload').addEventListener('change', function(e) {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const previewDiv = document.querySelector('.preview-image');
                    
                    previewDiv.innerHTML = '';
                    
                    const img = document.createElement('img');
                    img.src = event.target.result;
                    img.alt = 'Image Preview';
                    
                    previewDiv.appendChild(img);
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
