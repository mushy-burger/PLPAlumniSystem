<?php
session_start();
include 'admin/db_connect.php';

$alumni_query = "SELECT COUNT(*) as total_alumni FROM alumnus_bio";
$alumni_result = $conn->query($alumni_query);
$alumni_row = $alumni_result->fetch_assoc();
$alumni_count = $alumni_row['total_alumni'];

$forum_query = "SELECT COUNT(*) as total_topics FROM forum_topics";
$forum_result = $conn->query($forum_query);
$forum_row = $forum_result->fetch_assoc();
$forum_count = $forum_row['total_topics'];

$jobs_query = "SELECT COUNT(*) as total_jobs FROM careers";
$jobs_result = $conn->query($jobs_query);
$jobs_row = $jobs_result->fetch_assoc();
$jobs_count = $jobs_row['total_jobs'];

$events_query = "SELECT COUNT(*) as total_events FROM events";
$events_result = $conn->query($events_query);
$events_row = $events_result->fetch_assoc();
$events_count = $events_row['total_events'];

$courses_query = "SELECT COUNT(*) as total_courses FROM courses";
$courses_result = $conn->query($courses_query);
$courses_row = $courses_result->fetch_assoc();
$courses_count = $courses_row['total_courses'];

$gallery_query = "SELECT COUNT(*) as total_gallery FROM gallery";
$gallery_result = $conn->query($gallery_query);
$gallery_row = $gallery_result->fetch_assoc();
$gallery_count = $gallery_row['total_gallery'];

$recent_events_query = "SELECT * FROM events ORDER BY schedule DESC LIMIT 5";
$recent_events_result = $conn->query($recent_events_query);

$recent_topics_query = "SELECT ft.*, COUNT(fc.id) as comment_count FROM forum_topics ft 
                        LEFT JOIN forum_comments fc ON ft.id = fc.topic_id 
                        GROUP BY ft.id 
                        ORDER BY ft.date_created DESC LIMIT 5";
$recent_topics_result = $conn->query($recent_topics_query);
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Alumni Portal</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/admin-dashboard.css">
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

                <a href="admin-home.php" class="active"><img src="images/home.png" alt="Home"><span>Home</span></a>
                <a href="admin-gallery.php"><img src="images/gallery.png" alt="Gallery"><span>Gallery</span></a>
                <a href="admin-course-list.php"><img src="images/course-list.png" alt="Course List"><span>Course List</span></a>
                <a href="admin-alumni-list.php"><img src="images/alumni_list.png" alt="Alumni List"><span>Alumni List</span></a>
                <a href="admin-alumni-upload.php"><img src="images/upload.png" alt="Alumni Upload"><span>Alumni Upload</span></a>
                <a href="admin-job.php"><img src="images/jobs.png" alt="Jobs"><span>Jobs</span></a>
                <a href="admin-event.php"> <img src="images/calendar.png" alt="Events"><span>Events</span></a>
                <a href="admin-forums.php"><img src="images/forums.png" alt="Forum"><span>Forum</span></a>
                <a href="admin-officers.php"><img src="images/officer.png" alt="Officers"><span>Officers</span></a>
                <a href="admin-system-setting.php"><img src="images/settings.png" alt="System Settings"><span>System Settings</span></a>
                <a href="landing.php"><img src="images/log-out.png" alt="Log Out"><span>Log Out</span></a>
            </div>
        </div>

        <div class="main-dashboard-content">
            <header class="dashboard-header">Dashboard</header>
            <hr class="dashboard-divider">
            
            <div class="dashboard-welcome">
                <h2>Welcome to the Admin Dashboard</h2>
                <p>Here's an overview of your alumni portal statistics</p>
            </div>
            
            <div class="stats-container" style="grid-template-columns: repeat(3, 1fr);">
                <div class="stat-card alumni">
                    <div class="stat-icon">
                        <img src="images/avatar.png" alt="Alumni"/>
                    </div>
                    <div class="stat-info">
                        <h3>Alumni</h3>
                        <p class="stat-count"><?php echo $alumni_count; ?></p>
                    </div>
                </div>
                
                <div class="stat-card forum">
                    <div class="stat-icon">
                        <img src="images/forums.png" alt="Forum Topics"/>
                    </div>
                    <div class="stat-info">
                        <h3>Forum Topics</h3>
                        <p class="stat-count"><?php echo $forum_count; ?></p>
                    </div>
                </div>
                
                <div class="stat-card jobs">
                    <div class="stat-icon">
                        <img src="images/jobs.png" alt="Jobs"/>
                    </div>
                    <div class="stat-info">
                        <h3>Jobs</h3>
                        <p class="stat-count"><?php echo $jobs_count; ?></p>
                    </div>
                </div>
                
                <div class="stat-card events">
                    <div class="stat-icon">
                        <img src="images/calendar.png" alt="Events"/>
                    </div>
                    <div class="stat-info">
                        <h3>Events</h3>
                        <p class="stat-count"><?php echo $events_count; ?></p>
                    </div>
                </div>

                <div class="stat-card courses">
                    <div class="stat-icon">
                        <img src="images/course-list.png" alt="Courses"/>
                    </div>
                    <div class="stat-info">
                        <h3>Courses</h3>
                        <p class="stat-count"><?php echo $courses_count; ?></p>
                    </div>
                </div>

                <div class="stat-card gallery">
                    <div class="stat-icon">
                        <img src="images/gallery.png" alt="Gallery"/>
                    </div>
                    <div class="stat-info">
                        <h3>Gallery</h3>
                        <p class="stat-count"><?php echo $gallery_count; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-sections">
                <div class="dashboard-section">
                    <h3>Recent Events</h3>
                    <div class="dashboard-content">
                        <?php if($recent_events_result->num_rows > 0): ?>
                            <table class="dashboard-table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Schedule</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($event = $recent_events_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($event['schedule'])); ?></td>
                                        <td><button class="dashboard-btn view-event-btn" data-id="<?php echo $event['id']; ?>">View</button></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="no-data">No events found</p>
                        <?php endif; ?>
                        <div class="view-all">
                            <a href="admin-event.php">View All Events</a>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-section">
                    <h3>Recent Forum Topics</h3>
                    <div class="dashboard-content">
                        <?php if($recent_topics_result->num_rows > 0): ?>
                            <table class="dashboard-table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Comments</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($topic = $recent_topics_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($topic['title']); ?></td>
                                        <td><?php echo $topic['comment_count']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($topic['date_created'])); ?></td>
                                        <td><button class="dashboard-btn view-forum-btn" data-id="<?php echo $topic['id']; ?>">View</button></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="no-data">No forum topics found</p>
                        <?php endif; ?>
                        <div class="view-all">
                            <a href="admin-forums.php">View All Topics</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="eventModal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div class="event-modal-header">
                <h2 id="eventTitle"></h2>
                <div class="event-banner-container">
                    <img id="eventBanner" src="" alt="Event Banner">
                </div>
            </div>
            <div class="event-modal-body">
                <p><strong>Schedule:</strong> <span id="eventSchedule"></span></p>
                <hr>
                <h3>Description:</h3>
                <div id="eventContent"></div>
            </div>
        </div>
    </div>

    <div class="modal" id="forumModal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div class="forum-modal-header">
                <h2 id="forumTitle"></h2>
                <div class="forum-meta">
                    <span id="forumPostedBy"></span>
                    <span id="forumDate"></span>
                </div>
            </div>
            <hr>
            <div class="forum-modal-body">
                <div id="forumDescription"></div>
            </div>
            <hr>
            <div class="forum-comments-section">
                <h3>Comments</h3>
                <div id="forumComments"></div>
            </div>
        </div>
    </div>

    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            border-radius: 8px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 30px;
            position: relative;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
            color: #adb5bd;
            cursor: pointer;
            transition: all 0.2s;
        }

        .close-modal:hover {
            color: #212529;
        }

        .event-banner-container {
            margin-top: 15px;
            margin-bottom: 15px;
            text-align: center;
        }

        .event-banner-container img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 6px;
        }

        .forum-meta {
            display: flex;
            justify-content: space-between;
            color: #6c757d;
            font-size: 14px;
            margin-top: 8px;
        }

        .forum-comments-section {
            margin-top: 20px;
        }

        .comment-item {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .comment-meta {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 8px;
        }

        .comment-text {
            margin-top: 5px;
            color: #212529;
        }

        .view-event-btn, .view-forum-btn {
            cursor: pointer;
        }
    </style>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            const toggleBtn = document.querySelector('.toggle-btn');
            toggleBtn.innerHTML = sidebar.classList.contains('collapsed') ? '&#x25B6;' : '&#x25C0;';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const eventModal = document.getElementById('eventModal');
            const forumModal = document.getElementById('forumModal');
            const eventButtons = document.querySelectorAll('.view-event-btn');
            const forumButtons = document.querySelectorAll('.view-forum-btn');
            const closeButtons = document.querySelectorAll('.close-modal');
            
            eventButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const eventId = this.getAttribute('data-id');
                    console.log('Event button clicked, ID:', eventId);
                    
                    fetch(`get_event_details.php?id=${eventId}`)
                        .then(response => {
                            console.log('Response status:', response.status);
                            return response.json();
                        })
                        .then(data => {
                            console.log('Event data received:', data);
                            document.getElementById('eventTitle').innerText = data.title;
                            
                            const eventDate = new Date(data.schedule);
                            document.getElementById('eventSchedule').innerText = eventDate.toLocaleString('en-US', {
                                weekday: 'long',
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                                hour: 'numeric',
                                minute: 'numeric'
                            });
                            
                            document.getElementById('eventContent').innerHTML = data.content;
                            
                            const bannerImg = document.getElementById('eventBanner');
                            if (data.banner && data.banner !== '') {
                                bannerImg.src = data.banner;
                                bannerImg.style.display = 'block';
                            } else {
                                bannerImg.src = 'images/no-image-available.png';
                                bannerImg.style.display = 'block';
                            }
                            
                            eventModal.style.display = 'flex';
                        })
                        .catch(error => {
                            console.error('Error fetching event details:', error);
                        });
                });
            });
            
            forumButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const topicId = this.getAttribute('data-id');
                    console.log('Forum button clicked, ID:', topicId);
                    
                    fetch(`admin/get_topic.php?id=${topicId}`)
                        .then(response => {
                            console.log('Response status:', response.status);
                            return response.json();
                        })
                        .then(data => {
                            document.getElementById('forumTitle').innerText = data.topic.title;
                            document.getElementById('forumPostedBy').innerText = `Posted by: ${data.topic.posted_by}`;
                            
                            const topicDate = new Date(data.topic.date_created);
                            document.getElementById('forumDate').innerText = `Date: ${topicDate.toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric'
                            })}`;
                            
                            document.getElementById('forumDescription').innerHTML = data.topic.description;
                            
                            const commentsContainer = document.getElementById('forumComments');
                            commentsContainer.innerHTML = '';
                            
                            if (data.comments && data.comments.length > 0) {
                                data.comments.forEach(comment => {
                                    const commentDiv = document.createElement('div');
                                    commentDiv.className = 'comment-item';
                                    
                                    const commentMeta = document.createElement('div');
                                    commentMeta.className = 'comment-meta';
                                    commentMeta.innerHTML = `<span>${comment.name || 'Anonymous'}</span><span>${comment.formatted_date}</span>`;
                                    
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
                            
                            forumModal.style.display = 'flex';
                        })
                        .catch(error => {
                            console.error('Error fetching topic details:', error);
                        });
                });
            });
            
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    eventModal.style.display = 'none';
                    forumModal.style.display = 'none';
                });
            });
            
            window.addEventListener('click', function(event) {
                if (event.target === eventModal) {
                    eventModal.style.display = 'none';
                }
                if (event.target === forumModal) {
                    forumModal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>