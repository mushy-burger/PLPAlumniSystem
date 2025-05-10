<?php
// Include database connection
include 'admin/db_connect.php';

// Get system settings
$system_query = "SELECT * FROM system_settings WHERE id = 1";
$system_result = $conn->query($system_query);
$system = $system_result->fetch_assoc();

// Get total statistics
$alumni_count_query = "SELECT COUNT(*) as count FROM alumnus_bio";
$alumni_count_result = $conn->query($alumni_count_query);
$alumni_count = $alumni_count_result->fetch_assoc()['count'];

$courses_count_query = "SELECT COUNT(*) as count FROM courses";
$courses_count_result = $conn->query($courses_count_query);
$courses_count = $courses_count_result->fetch_assoc()['count'];

$events_count_query = "SELECT COUNT(*) as count FROM events";
$events_count_result = $conn->query($events_count_query);
$events_count = $events_count_result->fetch_assoc()['count'];

// Get latest events (limit to 3)
$events_query = "SELECT * FROM events WHERE schedule >= NOW() ORDER BY schedule ASC LIMIT 3";
$events_result = $conn->query($events_query);

// If no upcoming events, get recent past events
if ($events_result->num_rows == 0) {
    $events_query = "SELECT * FROM events ORDER BY schedule DESC LIMIT 3";
    $events_result = $conn->query($events_query);
}

// Get random gallery images (limit to 4)
$gallery_query = "SELECT * FROM gallery ORDER BY RAND() LIMIT 4";
$gallery_result = $conn->query($gallery_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo isset($system['name']) ? $system['name'] : 'PLP Alumni Portal'; ?></title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    /* Additional styles for landing page */
    .hero-section {
      position: relative;
      height: 600px;
      background: url('images/plpasigg.jpg') center/cover no-repeat;
      display: flex;
      justify-content: center;
      align-items: center;
      text-align: center;
      overflow: hidden;
    }
    
    .hero-section .overlay {
      position: absolute;
      top: 0;
      left: 0;
      height: 100%;
      width: 100%;
      background-color: rgba(0, 47, 108, 0.7);
      z-index: 1;
    }
    
    .hero-content {
      position: relative;
      z-index: 2;
      color: white;
      max-width: 800px;
      padding: 0 20px;
      animation: fadeInUp 1s ease-out;
    }
    
    .hero-content h1 {
      font-size: 54px;
      margin-bottom: 20px;
      letter-spacing: 3px;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    }
    
    .hero-content p {
      font-size: 20px;
      margin-bottom: 30px;
      line-height: 1.6;
    }
    
    .cta-buttons {
      display: flex;
      justify-content: center;
      gap: 20px;
    }
    
    .cta-button {
      padding: 15px 45px;
      background-color: #0047AB;
      color: white;
      border: 2px solid white;
      border-radius: 50px;
      font-size: 16px;
      font-weight: bold;
      text-decoration: none;
      transition: all 0.3s ease;
      transform: scale(1);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }
    
    .cta-button:hover {
      background-color: white;
      color: #0047AB;
      transform: scale(1.05);
    }
    
    .stats-section {
      background-color: #0047AB;
      padding: 40px 0;
      position: relative;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }
    
    .stats-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, rgba(0, 47, 108, 0.9) 0%, rgba(0, 71, 171, 0.9) 100%);
      z-index: 1;
    }
    
    .stats-container {
      display: flex;
      justify-content: space-around;
      max-width: 1200px;
      margin: 0 auto;
      flex-wrap: wrap;
      position: relative;
      z-index: 2;
    }
    
    .stat-item {
      text-align: center;
      padding: 20px;
      flex: 1;
      min-width: 200px;
      position: relative;
      transition: transform 0.3s ease;
    }
    
    .stat-item:hover {
      transform: translateY(-5px);
    }
    
    .stat-item::after {
      content: '';
      position: absolute;
      right: 0;
      top: 50%;
      transform: translateY(-50%);
      height: 60%;
      width: 1px;
      background-color: rgba(255, 255, 255, 0.2);
    }
    
    .stat-item:last-child::after {
      display: none;
    }
    
    .stat-icon-bg {
      display: flex;
      justify-content: center;
      align-items: center;
      margin: 0 auto 15px;
      width: 60px;
      height: 60px;
      background-color: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
    }
    
    .stat-icon-bg i {
      font-size: 24px;
      color: white;
    }
    
    .stat-number {
      font-size: 42px;
      font-weight: bold;
      margin-bottom: 5px;
      display: block;
      color: white;
      text-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
      font-family: 'Arial', sans-serif;
    }
    
    .stat-label {
      font-size: 16px;
      text-transform: uppercase;
      letter-spacing: 1px;
      font-weight: 600;
      color: rgba(255, 255, 255, 0.9);
      position: relative;
      padding-bottom: 10px;
    }
    
    .stat-label::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 30px;
      height: 2px;
      background-color: rgba(255, 255, 255, 0.4);
    }
    
    .features-section {
      padding: 100px 20px;
      background-color: #f8f9fa;
    }
    
    .section-title {
      text-align: center;
      color: #003366;
      font-size: 36px;
      margin-bottom: 50px;
      position: relative;
    }
    
    .section-title:after {
      content: '';
      display: block;
      width: 80px;
      height: 4px;
      background-color: #0047AB;
      position: absolute;
      bottom: -15px;
      left: 50%;
      transform: translateX(-50%);
    }
    
    .features-container {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 30px;
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .feature-card {
      background-color: white;
      border-radius: 10px;
      padding: 40px 30px;
      width: 300px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      text-align: center;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    
    .feature-card:hover {
      transform: translateY(-15px);
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
    }
    
    .feature-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background-color: #0047AB;
    }
    
    .feature-icon {
      width: 80px;
      height: 80px;
      background-color: #e6f0fa;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      margin: 0 auto 25px;
      transition: all 0.3s ease;
    }
    
    .feature-card:hover .feature-icon {
      background-color: #0047AB;
      color: white;
    }
    
    .feature-icon i {
      font-size: 36px;
      color: #0047AB;
      transition: all 0.3s ease;
    }
    
    .feature-card:hover .feature-icon i {
      color: white;
    }
    
    .feature-card h3 {
      color: #003366;
      font-size: 22px;
      margin-bottom: 15px;
    }
    
    .feature-card p {
      color: #555;
      font-size: 16px;
      line-height: 1.6;
    }
    
    .events-section {
      padding: 100px 20px;
      background-color: white;
    }
    
    .events-container {
      display: flex;
      flex-direction: column;
      gap: 40px;
      max-width: 1000px;
      margin: 0 auto;
    }
    
    .event-card {
      display: flex;
      background-color: #f9f9f9;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease;
    }
    
    .event-card:hover {
      transform: translateY(-10px);
    }
    
    .event-image {
      width: 350px;
      height: 250px;
      object-fit: cover;
    }
    
    .event-info {
      padding: 30px;
      flex: 1;
    }
    
    .event-date {
      background-color: #0047AB;
      color: white;
      display: inline-block;
      padding: 8px 15px;
      border-radius: 50px;
      font-size: 14px;
      margin-bottom: 20px;
      font-weight: bold;
    }
    
    .event-info h3 {
      color: #003366;
      font-size: 24px;
      margin-bottom: 15px;
    }
    
    .event-info p {
      color: #555;
      font-size: 16px;
      line-height: 1.6;
      margin-bottom: 20px;
    }
    
    .event-link {
      color: #0047AB;
      text-decoration: none;
      font-weight: bold;
      display: inline-flex;
      align-items: center;
      font-size: 16px;
      transition: color 0.3s ease;
    }
    
    .event-link:hover {
      color: #003366;
    }
    
    .event-link i {
      margin-left: 8px;
      transition: transform 0.3s ease;
    }
    
    .event-link:hover i {
      transform: translateX(5px);
    }
    
    .gallery-section {
      padding: 100px 20px;
      background-color: #f0f5ff;
    }
    
    .gallery-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .gallery-item {
      position: relative;
      height: 300px;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .gallery-item img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }
    
    .gallery-item:hover img {
      transform: scale(1.1);
    }
    
    .gallery-title {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
      color: white;
      padding: 20px;
      font-size: 18px;
      transform: translateY(100%);
      transition: transform 0.3s ease;
    }
    
    .gallery-item:hover .gallery-title {
      transform: translateY(0);
    }
    
    .gallery-cta {
      text-align: center;
      margin-top: 40px;
    }
    
    .gallery-button {
      background-color: #0047AB;
      color: white;
      border: none;
      padding: 12px 25px;
      font-size: 16px;
      border-radius: 50px;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
      transition: all 0.3s ease;
    }
    
    .gallery-button:hover {
      background-color: #003366;
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
    }
    
    .about-section {
      padding: 100px 20px;
      background-color: white;
    }
    
    .about-container {
      display: flex;
      align-items: center;
      max-width: 1200px;
      margin: 0 auto;
      gap: 60px;
    }
    
    .about-image {
      flex: 1;
      position: relative;
    }
    
    .about-image-main {
      width: 450px;
      height: 350px;
      border-radius: 10px;
      object-fit: cover;
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
      position: relative;
      z-index: 2;
    }
    
    .about-image::before {
      content: '';
      position: absolute;
      width: 100%;
      height: 100%;
      background-color: #0047AB;
      top: 20px;
      left: 20px;
      border-radius: 10px;
      z-index: 1;
    }
    
    .about-content {
      flex: 1;
    }
    
    .about-content h2 {
      color: #003366;
      font-size: 36px;
      margin-bottom: 25px;
    }
    
    .about-content p {
      color: #333;
      font-size: 16px;
      line-height: 1.8;
      margin-bottom: 25px;
    }
    
    .about-button {
      display: inline-block;
      background-color: #0047AB;
      color: white;
      padding: 12px 25px;
      border-radius: 50px;
      text-decoration: none;
      font-weight: bold;
      transition: all 0.3s ease;
    }
    
    .about-button:hover {
      background-color: #003366;
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
    }
    
    .testimonials-section {
      background-color: #f0f5ff;
      padding: 100px 20px;
    }
    
    .testimonial-container {
      max-width: 800px;
      margin: 0 auto;
      text-align: center;
    }
    
    .testimonial-quote {
      font-size: 20px;
      line-height: 1.8;
      color: #333;
      font-style: italic;
      margin-bottom: 30px;
      position: relative;
      padding: 0 40px;
    }
    
    .testimonial-quote::before, .testimonial-quote::after {
      content: '"';
      font-size: 60px;
      color: #0047AB;
      position: absolute;
      opacity: 0.3;
    }
    
    .testimonial-quote::before {
      top: -20px;
      left: 0;
    }
    
    .testimonial-quote::after {
      bottom: -50px;
      right: 0;
    }
    
    .testimonial-author {
      font-weight: bold;
      color: #003366;
      font-size: 18px;
    }
    
    .testimonial-role {
      color: #555;
      font-size: 14px;
    }
    
    .footer {
      position: relative;
      background: url('images/plpasigg.jpg') center/cover no-repeat;
      padding: 50px 20px 30px;
      color: white;
    }
    
    .footer .overlay {
      position: absolute;
      top: 0;
      left: 0;
      height: 100%;
      width: 100%;
      background-color: rgba(0, 47, 108, 0.9);
      z-index: 1;
    }
    
    .footer-content {
      position: relative;
      z-index: 2;
      max-width: 1200px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 30px;
    }
    
    .footer-column h3 {
      font-size: 18px;
      margin-bottom: 15px;
      position: relative;
      padding-bottom: 8px;
    }
    
    .footer-column h3::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 40px;
      height: 2px;
      background-color: white;
    }
    
    .footer-column p, .footer-column a {
      margin-bottom: 8px;
      display: block;
      color: rgba(255, 255, 255, 0.8);
      text-decoration: none;
      transition: color 0.3s ease;
      font-size: 14px;
    }
    
    .footer-column a:hover {
      color: white;
    }
    
    .footer-bottom {
      position: relative;
      z-index: 2;
      text-align: center;
      padding-top: 25px;
      margin-top: 25px;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      font-size: 13px;
      color: rgba(255, 255, 255, 0.6);
    }
    
    /* Animations */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    /* Icon placeholders */
    .icon-placeholder {
      display: flex;
      justify-content: center;
      align-items: center;
      width: 100%;
      height: 100%;
      font-size: 30px;
      color: #0047AB;
    }
    
    /* Responsive adjustments */
    @media (max-width: 992px) {
      .hero-content h1 {
        font-size: 42px;
      }
      
      .about-container {
        flex-direction: column;
      }
      
      .about-image {
        margin-bottom: 40px;
      }
      
      .about-image-main {
        width: 100%;
        max-width: 450px;
      }
    }
    
    @media (max-width: 768px) {
      .hero-content h1 {
        font-size: 36px;
      }
      
      .hero-content p {
        font-size: 16px;
      }
      
      .cta-buttons {
        flex-direction: column;
        gap: 15px;
      }
      
      .cta-button {
        padding: 15px 35px;
      }
      
      .event-card {
        flex-direction: column;
      }
      
      .event-image {
        width: 100%;
        height: 200px;
      }
      
      .stat-item {
        flex-basis: 50%;
        padding: 15px 10px;
      }
      
      .stat-item::after {
        display: none;
      }
      
      .stat-number {
        font-size: 36px;
      }
      
      .stat-label {
        font-size: 14px;
      }
    }
    
    @media (max-width: 576px) {
      .hero-section {
        height: 500px;
      }
      
      .hero-content h1 {
        font-size: 28px;
      }
      
      .section-title {
        font-size: 28px;
      }
      
      .about-content h2 {
        font-size: 28px;
      }
      
      .stat-icon-bg {
        width: 50px;
        height: 50px;
        margin-bottom: 10px;
      }
      
      .stat-icon-bg i {
        font-size: 20px;
      }
      
      .stat-number {
        font-size: 32px;
      }
      
      .stat-item {
        flex-basis: 100%;
        margin-bottom: 20px;
      }
      
      .stat-item:last-child {
        margin-bottom: 0;
      }
    }
    
    .about-text {
      color: #333;
      line-height: 1.8;
    }
    
    .about-text p {
      color: #333;
      font-size: 16px;
      line-height: 1.8;
      margin-bottom: 20px;
    }
    
    .about-text h1, .about-text h2, .about-text h3, 
    .about-text h4, .about-text h5, .about-text h6 {
      color: #003366;
      margin-top: 25px;
      margin-bottom: 15px;
    }
    
    .about-text ul, .about-text ol {
      margin-bottom: 20px;
      margin-left: 20px;
    }
    
    .about-text li {
      margin-bottom: 8px;
    }
    
    .about-text a {
      color: #0047AB;
      text-decoration: none;
      transition: all 0.3s ease;
    }
    
    .about-text a:hover {
      color: #003366;
      text-decoration: underline;
    }
    
    .about-text img {
      max-width: 100%;
      height: auto;
      border-radius: 8px;
      margin: 15px 0;
    }
    
    .about-text blockquote {
      border-left: 4px solid #0047AB;
      padding-left: 20px;
      margin: 20px 0;
      font-style: italic;
      color: #555;
    }
    
    .no-gallery-items {
      text-align: center;
      padding: 40px 20px;
    }
    
    .no-content-message {
      background-color: white;
      padding: 40px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      max-width: 600px;
      margin: 0 auto 30px;
    }
    
    .no-content-message i {
      font-size: 50px;
      color: #0047AB;
      margin-bottom: 20px;
      opacity: 0.8;
    }
    
    .no-content-message p {
      font-size: 18px;
      color: #555;
      line-height: 1.6;
    }
    
    /* Improved hamburger menu */
    .hamburger-icon {
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      width: 25px;
      height: 18px;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .hamburger-icon span {
      display: block;
      height: 2px;
      width: 100%;
      background-color: #003366;
      border-radius: 2px;
      transition: all 0.3s ease;
    }
    
    .modal {
      display: none;
      position: fixed; 
      top: 70px;
      right: 20px;
      background-color: #fff;
      border-radius: 8px;
      flex-direction: column;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
      width: 180px;
      z-index: 1000;
      padding: 8px 0;
      animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .modal-link {
      display: flex;
      align-items: center;
      padding: 12px 20px;
      text-decoration: none;
      color: #003366;
      font-size: 16px;
      transition: background-color 0.3s ease;
    }
    
    .modal-link:hover {
      background-color: #f0f5ff;
    }
    
    .modal-link i {
      margin-right: 10px;
      width: 20px;
      text-align: center;
    }
    
    .show {
      display: flex;
    }
  </style>
</head>
<body>

<header class="header">
  <div class="header-left">
    <img src="images/logo.png" alt="PLP Logo" class="logo">
    <div class="text">
      <div class="school-name">Pamantasan Ng Lungsod Ng Pasig</div>
      <div class="alumni-title">ALUMNI</div>
    </div>
  </div>
  <div class="menu" id="menuButton">
    <div class="hamburger-icon">
      <span></span>
      <span></span>
      <span></span>
    </div>
  </div>
</header>

<div id="modal" class="modal">
  <a href="login.php" class="modal-link"><i class="fas fa-sign-in-alt"></i> Sign In</a>
</div>

<section class="hero-section">
  <div class="overlay"></div>
  <div class="hero-content">
    <h1>WELCOME TO PLP ALUMNI PORTAL</h1>
    <p>Connect with fellow alumni, stay updated with university events, and be part of our growing community of excellence.</p>
    <div class="cta-buttons">
      <a href="login.php" class="cta-button">SIGN IN</a>
    </div>
  </div>
</section>

<section class="stats-section">
  <div class="stats-container">
    <div class="stat-item">
      <div class="stat-icon-bg">
        <i class="fas fa-user-graduate"></i>
      </div>
      <span class="stat-number"><?php echo number_format($alumni_count); ?></span>
      <span class="stat-label">Alumni</span>
    </div>
    <div class="stat-item">
      <div class="stat-icon-bg">
        <i class="fas fa-book"></i>
      </div>
      <span class="stat-number"><?php echo number_format($courses_count); ?></span>
      <span class="stat-label">Courses</span>
    </div>
    <div class="stat-item">
      <div class="stat-icon-bg">
        <i class="fas fa-calendar-check"></i>
      </div>
      <span class="stat-number"><?php echo number_format($events_count); ?></span>
      <span class="stat-label">Events</span>
    </div>
  </div>
</section>

<section class="features-section">
  <h2 class="section-title">Why Join Our Community</h2>
  <div class="features-container">
    <div class="feature-card">
      <div class="feature-icon">
        <i class="fas fa-network-wired"></i>
      </div>
      <h3>Network</h3>
      <p>Connect with thousands of alumni across different industries to expand your professional network and build meaningful relationships.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">
        <i class="fas fa-briefcase"></i>
      </div>
      <h3>Job Opportunities</h3>
      <p>Access exclusive job postings and career opportunities shared by fellow alumni and partner companies in various industries.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon">
        <i class="fas fa-calendar-alt"></i>
      </div>
      <h3>Events</h3>
      <p>Stay updated with university events, reunions, and activities specially organized for alumni to keep your connection strong.</p>
    </div>
  </div>
</section>

<section class="events-section">
  <h2 class="section-title"><?php echo $events_result->num_rows > 0 ? 'Upcoming Events' : 'Recent Events'; ?></h2>
  <div class="events-container">
    <?php if($events_result->num_rows > 0): ?>
      <?php while($event = $events_result->fetch_assoc()): 
        $banner = !empty($event['banner']) ? $event['banner'] : 'images/plpasigg.jpg';
        if(!file_exists($banner)) {
          $banner = 'images/plpasigg.jpg';
        }
        
        $content_preview = strip_tags(html_entity_decode($event['content']));
        $content_preview = substr($content_preview, 0, 150) . (strlen($content_preview) > 150 ? '...' : '');
      ?>
      <div class="event-card">
        <img src="<?php echo $banner; ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" class="event-image">
        <div class="event-info">
          <span class="event-date"><?php echo date('F d, Y', strtotime($event['schedule'])); ?></span>
          <h3><?php echo htmlspecialchars($event['title']); ?></h3>
          <p><?php echo $content_preview; ?></p>
          <a href="#" class="event-link" data-id="<?php echo $event['id']; ?>">Learn More <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="event-card">
        <img src="images/plpasigg.jpg" alt="No Events" class="event-image">
        <div class="event-info">
          <h3>No Upcoming Events</h3>
          <p>Stay tuned for our upcoming events and activities. We're constantly planning new ways to engage with our alumni community.</p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="gallery-section">
  <h2 class="section-title">Gallery Highlights</h2>
  
  <?php if($gallery_result && $gallery_result->num_rows > 0): ?>
    <div class="gallery-grid">
      <?php while($gallery = $gallery_result->fetch_assoc()): ?>
        <div class="gallery-item">
          <img src="<?php echo htmlspecialchars($gallery['image_path']); ?>" alt="<?php echo htmlspecialchars($gallery['title']); ?>">
          <div class="gallery-title"><?php echo htmlspecialchars($gallery['title']); ?></div>
        </div>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <div class="no-gallery-items">
      <div class="no-content-message">
        <i class="fas fa-images"></i>
        <p>No gallery items available yet. Check back soon for updates!</p>
      </div>
    </div>
  <?php endif; ?>
</section>

<section class="about-section">
  <div class="about-container">
    <div class="about-image">
      <img src="images/plpasigg.jpg" alt="PLP Campus" class="about-image-main">
    </div>
    <div class="about-content">
      <h2>About Our Alumni Portal</h2>
      <?php if(isset($system['about_content']) && !empty($system['about_content'])): ?>
        <div class="about-text"><?php echo html_entity_decode($system['about_content']); ?></div>
      <?php else: ?>
        <p>The Pamantasan ng Lungsod ng Pasig Alumni Portal is designed to strengthen the bond between the university and its graduates. We believe in fostering a vibrant community where alumni can connect, collaborate, and contribute to the growth of each other and the institution.</p>
        <p>Through this platform, we aim to create a supportive network that spans across generations, industries, and geographical boundaries, uniting all PLP graduates under one virtual roof.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<footer class="footer">
  <div class="overlay"></div>
  <div class="footer-content">
    <div class="footer-column">
      <h3>CONTACT US</h3>
      <p><i class="fas fa-envelope"></i> <?php echo $system['email']; ?></p>
      <p><i class="fas fa-phone"></i> <?php echo $system['contact']; ?></p>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; <?php echo date('Y'); ?> Pamantasan ng Lungsod ng Pasig. All Rights Reserved.</p>
  </div>
</footer>

<div id="eventModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.7); padding-top:60px;">
  <div style="background-color:white; margin:auto; padding:20px; border-radius:10px; width:80%; max-width:800px; position:relative;">
    <span id="closeEventModal" style="position:absolute; right:20px; top:10px; font-size:28px; font-weight:bold; cursor:pointer;">&times;</span>
    <h2 id="eventTitle" style="color:#003366; margin-bottom:15px;"></h2>
    <div id="eventDate" style="color:#0047AB; margin-bottom:20px; font-weight:bold;"></div>
    <img id="eventImage" style="max-width:100%; margin-bottom:20px; border-radius:5px; display:none;" alt="Event Image">
    <div id="eventContent" style="line-height:1.6;"></div>
  </div>
</div>

<script>
  // Menu Toggle
  const menuButton = document.getElementById('menuButton');
  const modal = document.getElementById('modal');

  menuButton.onclick = function() {
    modal.classList.toggle('show');
  }

  window.onclick = function(event) {
    if (!event.target.closest('.menu') && !event.target.closest('#modal')) {
      modal.classList.remove('show');
    }
  }
  
  // Event Modal
  const eventLinks = document.querySelectorAll('.event-link');
  const eventModal = document.getElementById('eventModal');
  const closeEventModal = document.getElementById('closeEventModal');
  
  eventLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      const eventId = this.getAttribute('data-id');
      
      // Fetch event details
      fetch('get_event_details.php?id=' + eventId)
        .then(response => response.json())
        .then(data => {
          document.getElementById('eventTitle').textContent = data.title;
          document.getElementById('eventDate').textContent = data.schedule;
          document.getElementById('eventContent').innerHTML = data.content;
          
          const eventImage = document.getElementById('eventImage');
          if (data.banner && data.banner !== '') {
            eventImage.src = data.banner;
            eventImage.style.display = 'block';
          } else {
            eventImage.style.display = 'none';
          }
          
          eventModal.style.display = 'block';
        })
        .catch(error => {
          console.error('Error fetching event details:', error);
        });
    });
  });
  
  closeEventModal.addEventListener('click', function() {
    eventModal.style.display = 'none';
  });
  
  window.addEventListener('click', function(event) {
    if (event.target === eventModal) {
      eventModal.style.display = 'none';
    }
  });
</script>

</body>
</html>
