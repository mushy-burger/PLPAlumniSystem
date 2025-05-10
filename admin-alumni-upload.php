<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alumni Portal</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="csv-upload.css">
    <link rel="stylesheet" href="alumni-upload.css">
    <style>
        .error-details {
            background-color: #fff3f3;
            border: 1px solid #ffcccb;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .error-details h3 {
            color: #721c24;
            margin-top: 0;
            margin-bottom: 10px;
        }
        
        .error-list {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        
        .error-list li {
            padding: 8px 10px;
            border-bottom: 1px solid #ffdfdf;
        }
        
        .error-list li:last-child {
            border-bottom: none;
        }
        
        .toggle-errors {
            background-color: #f8d7da;
            color: #721c24;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .debug-tool-container {
            background-color: #e8f4ff;
            border: 1px solid #b3d7ff;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .debug-tool-container h3 {
            color: #004085;
            margin-top: 0;
            margin-bottom: 10px;
        }
        
        .debug-buttons {
            margin: 15px 0;
        }
        
        .debug-btn {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .debug-btn:hover {
            background-color: #0069d9;
        }
        
        .debug-note {
            font-style: italic;
            color: #004085;
            margin-top: 10px;
        }
    </style>
</head>
<body>

    <div class="interface-header">
        <img src="images/logo.png" alt="PLP Logo" class="logo-interface">
        <div class="text">
            <div class="school-name">Pamantasan Ng Lungsod Ng Pasig</div>
            <p class="p-size">Alkade Jose St. Kapasigan, Pasig City</p>
        </div>
    </div>

    <div class="alumni-upload-content">
        <div class="sidebar" id="sidebar">
            <div class="toggle-btn" onclick="toggleSidebar()">&#x25C0;</div>

            <div class="sidebar-content">
                <div class="profile-section">
                    <a href="#" class="profile-pic">
                        <img src="images/avatar.png" alt="Profile Picture">
                    </a>
                    <div class="profile-name">ADMIN</div>
                </div>

                <a href="admin-home.php"><img src="images/home.png" alt="Home"><span>Home</span></a>
                <a href="admin-gallery.php"><img src="images/gallery.png" alt="Gallery"><span>Gallery</span></a>
                <a href="admin-course-list.php"><img src="images/course-list.png" alt="Course List"><span>Course List</span></a>
                <a href="admin-alumni-list.php"><img src="images/alumni_list.png" alt="Alumni List"><span>Alumni List</span></a>
                <a href="admin-alumni-upload.php" class="active"><img src="images/upload.png" alt="Alumni Upload"><span>Alumni Upload</span></a>
                <a href="admin-job.php"><img src="images/jobs.png" alt="Jobs"><span>Jobs</span></a>
                <a href="admin-event.php"><img src="images/calendar.png" alt="Events"><span>Events</span></a>
                <a href="admin-forums.php"><img src="images/forums.png" alt="Forum"><span>Forum</span></a>
                <a href="admin-system-setting.php"><img src="images/settings.png" alt="System Settings"><span>System Settings</span></a>
                <a href="landing.php"><img src="images/log-out.png" alt="Log Out"><span>Log Out</span></a>
            </div>
        </div>

        <div class="main-upload-area">
            <header>Upload Alumni Data</header>
            <hr><br>
            
            <?php
            if(isset($_GET['success']) && $_GET['success'] == 1) {
                echo '<div class="upload-success">' . htmlspecialchars($_GET['message']) . '</div>';
            }
            if(isset($_GET['error']) && $_GET['error'] == 1) {
                echo '<div class="upload-error">' . htmlspecialchars($_GET['message']) . '</div>';
            }
            
            if((isset($_GET['show_errors']) && $_GET['show_errors'] == 1) && isset($_SESSION['upload_errors']) && !empty($_SESSION['upload_errors'])) {
                echo '<div class="error-details">';
                echo '<h3>Error Details (' . count($_SESSION['upload_errors']) . '):</h3>';
                echo '<ul class="error-list">';
                foreach($_SESSION['upload_errors'] as $error) {
                    echo '<li>' . htmlspecialchars($error) . '</li>';
                }
                echo '</ul>';
                echo '</div>';

                unset($_SESSION['upload_errors']);
            }
            
            if(isset($_GET['show_debug']) && $_GET['show_debug'] == 1) {
                echo '<div class="debug-tool-container">';
                echo '<h3>CSV Debug Tools</h3>';
                echo '<p>Use these tools to fix issues with your CSV file:</p>';
                echo '<div class="debug-buttons">';
                echo '<a href="debug_csv.php" class="debug-btn">CSV Validator</a>';
                echo '</div>';
                echo '<p class="debug-note">The CSV Validator will analyze your file and help identify and fix alumni ID issues.</p>';
                echo '</div>';
            }
            ?>
            
            <form action="upload_csv.php" method="post" enctype="multipart/form-data">
                <div class="form-content">
                    <div class="csv-upload-container">
                        <h3>Upload Alumni CSV File</h3>
                        <p class="csv-instructions">Please upload a CSV file with alumni data. The file should contain the following columns:</p>
                        <div class="csv-format">
                            <ul>
                                <li>ALUMNI ID</li>
                                <li>FIRSTNAME</li>
                                <li>MIDDLENAME</li>
                                <li>LASTNAME</li>
                                <li>GENDER</li>
                                <li>BATCH</li>
                                <li>EMAIL</li>
                                <li>COURSE</li>
                            </ul>
                        </div>
                        <p class="csv-note">Note: Make sure the CSV has a header row with these column names. 
                        <br>
                        <br>
                         - Alumni ID should follow the format 0001-0001. <strong>Each alumni ID must be unique and follow this exact format</strong>.
                        <br>
                        <br>
                         - Gender should be either "Male" or "Female". 
                        <br>
                        <br>
                         - Batch should be the graduation year (e.g., 2022).
                        <br>
                        <br>
                         - <strong>Email addresses must be valid</strong> as welcome emails will be automatically sent to alumni after upload.</p>
                        <div class="csv-template">
                            <a href="templates/alumni_template.csv" download>Download CSV Template</a>
                        </div>
                        <div class="file-upload-container">
                            <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                            <div class="file-upload-label">Choose CSV file</div>
                            <div id="file-name" class="file-name">No file chosen</div>
                        </div>
                    </div>
                    <div class="upload-button">
                        <button type="submit" name="upload">Upload</button>
                        <button type="button" onclick="window.location.href='admin-alumni-list.php'">Cancel</button>
                    </div>
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

        document.getElementById('csv_file').addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : 'No file chosen';
            document.getElementById('file-name').textContent = fileName;
        });
    </script>

</body>
</html>
