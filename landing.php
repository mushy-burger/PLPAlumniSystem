<?php
include 'admin/db_connect.php';

$system_query = "SELECT * FROM system_settings WHERE id = 1";
$system_result = $conn->query($system_query);
$system = $system_result->fetch_assoc();

$events_query = "SELECT * FROM events ORDER BY id DESC LIMIT 4";
$events_result = $conn->query($events_query);

$gallery_query = "SELECT * FROM gallery ORDER BY RAND() LIMIT 6";
$gallery_result = $conn->query($gallery_query);

$current_year = date('Y');
$selected_year = isset($_GET['year']) ? $_GET['year'] : $current_year;

$years_query = "SELECT DISTINCT class_year FROM alumni_officers ORDER BY class_year DESC";
$years_result = $conn->query($years_query);
$available_years = [];
while($row = $years_result->fetch_assoc()) {
    $available_years[] = $row['class_year'];
}

$officers_query = "SELECT * FROM alumni_officers WHERE class_year = '$selected_year' ORDER BY display_order, position LIMIT 4";
$officers_result = $conn->query($officers_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo isset($system['name']) ? $system['name'] : 'PLP Alumni Portal'; ?></title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <script src="https://unpkg.com/scrollreveal@4.0.9/dist/scrollreveal.min.js"></script>
  <style>
    .header-left {
      display: flex;
      align-items: center;
    }
    
    .logo {
      height: 70px !important;
      margin-right: 10px !important;
    }
    
    .text {
      display: flex;
      flex-direction: column;
    }
    
    .school-name {
      font-size: 18px !important;
      color: #003366 !important;
      font-weight: bold !important;
      margin-bottom: 0 !important;
      line-height: normal !important;
      letter-spacing: normal !important;
      text-shadow: none !important;
    }
    
    .alumni-title {
      font-size: 24px !important;
      color: #003366 !important;
      letter-spacing: 8px !important;
      font-weight: bold !important;
      text-transform: uppercase !important;
      background: none !important;
      -webkit-background-clip: initial !important;
      -webkit-text-fill-color: initial !important;
      background-clip: initial !important;
      text-fill-color: initial !important;
      position: static !important;
      padding: 0 !important;
      margin: 0 !important;
      text-shadow: none !important;
    }
    
    .alumni-title:after {
      display: none !important;
    }
    
    .hero-section {
      position: relative;
      height: 600px;
      background: url('<?php echo !empty($system['cover_img']) ? $system['cover_img'] : 'images/plpasigg.jpg'; ?>') center/cover no-repeat;
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
    
    .events-section {
      padding: 100px 20px;
      background-color: white;
    }
    
    .events-container {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 30px;
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .event-card {
      display: flex;
      flex-direction: column;
      background-color: #f9f9f9;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .event-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
    }
    
    .event-image {
      width: 100%;
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
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .gallery-item {
      position: relative;
      height: 250px;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .gallery-item img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }
    
    .gallery-item:hover img {
      transform: translateY(-5px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
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
      padding: 20px;
    }
    
    .about-image-main {
      width: 100%;
      height: 400px;
      border-radius: 15px;
      object-fit: cover;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
      transition: transform 0.5s ease, box-shadow 0.5s ease;
      position: relative;
      z-index: 2;
    }
    
    .about-image:hover .about-image-main {
      transform: translateY(-10px);
      box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
    }
    
    .about-image::before {
      display: none;
    }
    
    .about-content {
      flex: 1;
    }
    
    .about-content h2 {
      color: #003366;
      font-size: 36px;
      margin-bottom: 25px;
      text-align: center;
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
      background: url('<?php echo !empty($system['cover_img']) ? $system['cover_img'] : 'images/plpasigg.jpg'; ?>') center/cover no-repeat;
      padding: 30px 20px 20px;
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
      gap: 20px;
    }
    
    .footer-column h3 {
      font-size: 16px;
      margin-bottom: 12px;
      position: relative;
      padding-bottom: 6px;
    }
    
    .footer-column h3::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 30px;
      height: 2px;
      background-color: white;
    }
    
    .footer-column p, .footer-column a {
      margin-bottom: 6px;
      display: block;
      color: rgba(255, 255, 255, 0.8);
      text-decoration: none;
      transition: color 0.3s ease;
      font-size: 13px;
    }
    
    .footer-column a:hover {
      color: white;
    }
    
    .contact-info {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      color: rgba(255, 255, 255, 0.8);
      font-size: 13px;
    }
    
    .contact-info i {
      margin-right: 5px;
    }
    
    .footer-bottom {
      position: relative;
      z-index: 2;
      text-align: center;
      padding-top: 15px;
      margin-top: 15px;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      font-size: 12px;
      color: rgba(255, 255, 255, 0.6);
    }
  
    .social-icons {
      display: flex;
      gap: 10px;
      margin-top: 10px;
    }
    
    .social-icon {
      display: flex;
      justify-content: center;
      align-items: center;
      width: 32px;
      height: 32px;
      background-color: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
      color: white;
      font-size: 14px;
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    
    .social-icon i {
      display: flex;
      justify-content: center;
      align-items: center;
      width: 100%;
      height: 100%;
      transition: transform 0.3s ease;
      position: relative;
      z-index: 3;
    }
    
    .social-icon:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }
    
    .social-icon:hover i {
      color: #0047AB;
    }
    
    .social-icon:after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: white;
      border-radius: 50%;
      z-index: 1;
      transform: scale(0);
      transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    
    .social-icon:hover:after {
      transform: scale(1);
    }
    
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
    
    .icon-placeholder {
      display: flex;
      justify-content: center;
      align-items: center;
      width: 100%;
      height: 100%;
      font-size: 30px;
      color: #0047AB;
    }
    
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
        height: 350px;
      }
      
      .gallery-grid {
        grid-template-columns: repeat(2, 1fr);
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
      
      .events-container {
        grid-template-columns: 1fr;
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
      
      .gallery-grid {
        grid-template-columns: 1fr;
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
    
    .officers-section {
      padding: 100px 20px;
      background-color: #f0f5ff;
    }
    
    .officers-container {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 30px;
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .officer-card {
      background-color: white;
      border-radius: 10px;
      overflow: hidden;
      width: 250px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .officer-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
    }
    
    .officer-image {
      height: 200px;
      overflow: hidden;
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
      text-align: center;
    }
    
    .officer-info h3 {
      color: #003366;
      font-size: 18px;
      margin-bottom: 5px;
    }
    
    .officer-position {
      display: block;
      color: #0047AB;
      font-weight: bold;
      font-size: 14px;
      margin-bottom: 10px;
      text-transform: uppercase;
    }
    
    .officer-info p {
      color: #666;
      font-size: 14px;
      margin-bottom: 15px;
    }
    
    .text .alumni-title {
      color: #0047AB;
      font-size: 14px;
      letter-spacing: 2px;
      font-weight: bold;
      text-transform: uppercase;
    }
    
    .header-right {
      display: flex;
      align-items: center;
    }
    
    .header-login-btn {
      background-color: #0047AB;
      color: white;
      padding: 8px 20px;
      border-radius: 50px;
      text-decoration: none;
      font-size: 14px;
      font-weight: bold;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .header-login-btn:hover {
      background-color: #003366;
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
    
    .header-login-btn i {
      font-size: 14px;
    }
    
    .header-left {
      display: flex;
      align-items: center;
    }
    
    .logo {
      height: 70px !important;
      margin-right: 10px !important;
    }
    
    .text {
      display: flex;
      flex-direction: column;
    }
    
    .school-name {
      font-size: 18px !important;
      color: #003366 !important;
      font-weight: bold !important;
      margin-bottom: 0 !important;
      line-height: normal !important;
      letter-spacing: normal !important;
      text-shadow: none !important;
    }
    
    .alumni-title {
      font-size: 24px !important;
      color: #003366 !important;
      letter-spacing: 8px !important;
      font-weight: bold !important;
      text-transform: uppercase !important;
      background: none !important;
      -webkit-background-clip: initial !important;
      -webkit-text-fill-color: initial !important;
      background-clip: initial !important;
      text-fill-color: initial !important;
      position: static !important;
      padding: 0 !important;
      margin: 0 !important;
      text-shadow: none !important;
    }
    
    .alumni-title:after {
      display: none !important;
    }
    
    .menu {
      cursor: pointer;
    }
    
    .menu img {
      width: 24px;
      height: auto;
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
    
    .year-selector-container {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .year-dropdown {
      padding: 10px 20px;
      border-radius: 50px;
      border: 2px solid #0047AB;
      background-color: white;
      color: #0047AB;
      font-weight: bold;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.3s ease;
      width: auto;
      min-width: 150px;
      text-align: center;
      -webkit-appearance: none;
      -moz-appearance: none;
      appearance: none;
      background-image: url('data:image/svg+xml;utf8,<svg fill="%230047AB" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/><path d="M0 0h24v24H0z" fill="none"/></svg>');
      background-repeat: no-repeat;
      background-position: right 10px center;
      padding-right: 30px;
    }
    
    .year-dropdown:hover, .year-dropdown:focus {
      background-color: #f0f5ff;
      border-color: #003366;
      outline: none;
      box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
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
</header>

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

<section class="events-section">
  <h2 class="section-title">Latest Events</h2>
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
      <img src="<?php echo !empty($system['cover_img']) ? $system['cover_img'] : 'images/plpasigg.jpg'; ?>" alt="PLP Campus" class="about-image-main">
    </div>
    <div class="about-content">
      <h2>About</h2>
      <?php if(isset($system['about_content']) && !empty($system['about_content'])): ?>
        <div class="about-text"><?php echo html_entity_decode($system['about_content']); ?></div>
      <?php else: ?>
        <p>The Pamantasan ng Lungsod ng Pasig Alumni Portal is designed to strengthen the bond between the university and its graduates. We believe in fostering a vibrant community where alumni can connect, collaborate, and contribute to the growth of each other and the institution.</p>
        <p>Through this platform, we aim to create a supportive network that spans across generations, industries, and geographical boundaries, uniting all PLP graduates under one virtual roof.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="officers-section">
  <h2 class="section-title">Alumni Officers</h2>
  
  <div class="year-selector-container">
    <form action="" method="get" id="yearSelectorForm">
      <select name="year" id="yearSelector" class="year-dropdown">
        <?php if(!empty($available_years)): ?>
          <?php foreach($available_years as $year): ?>
            <option value="<?php echo $year; ?>" <?php echo $year == $selected_year ? 'selected' : ''; ?>>
              Class of <?php echo $year; ?>
            </option>
          <?php endforeach; ?>
        <?php else: ?>
          <option value="<?php echo $current_year; ?>"><?php echo $current_year; ?></option>
        <?php endif; ?>
      </select>
    </form>
  </div>
  
  <div class="officers-container">
    <?php if($officers_result && $officers_result->num_rows > 0): ?>
      <?php while($officer = $officers_result->fetch_assoc()): ?>
        <div class="officer-card">
          <div class="officer-image">
            <img src="<?php echo htmlspecialchars($officer['image_path']); ?>" alt="<?php echo htmlspecialchars($officer['name']); ?>" onerror="this.src='images/user.jpg'">
          </div>
          <div class="officer-info">
            <h3><?php echo htmlspecialchars($officer['name']); ?></h3>
            <span class="officer-position"><?php echo htmlspecialchars($officer['position']); ?></span>
            <p>Class of <?php echo htmlspecialchars($officer['class_year']); ?>, <?php echo htmlspecialchars($officer['course']); ?></p>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="no-gallery-items">
        <div class="no-content-message">
          <i class="fas fa-users"></i>
          <p>No alumni officers available for the selected year. Check other years or check back later!</p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<footer class="footer">
  <div class="overlay"></div>
  <div class="footer-content">
    <div class="footer-column">
      <h3>CONTACT US</h3>
      <p class="contact-info"><i class="fas fa-envelope"></i> <?php echo $system['email']; ?> &nbsp;&nbsp;|&nbsp;&nbsp; <i class="fas fa-phone"></i> <?php echo $system['contact']; ?></p>
    </div>
    <div class="footer-column">
      <h3>CONNECT WITH US</h3>
      <div class="social-icons">
        <?php if (!empty($system['facebook'])): ?>
          <a href="<?php echo htmlspecialchars($system['facebook']); ?>" target="_blank" class="social-icon"><i class="fab fa-facebook-f"></i></a>
        <?php endif; ?>
        <?php if (!empty($system['twitter'])): ?>
          <a href="<?php echo htmlspecialchars($system['twitter']); ?>" target="_blank" class="social-icon"><i class="fab fa-twitter"></i></a>
        <?php endif; ?>
        <?php if (!empty($system['instagram'])): ?>
          <a href="<?php echo htmlspecialchars($system['instagram']); ?>" target="_blank" class="social-icon"><i class="fab fa-instagram"></i></a>
        <?php endif; ?>
        <?php if (!empty($system['linkedin'])): ?>
          <a href="<?php echo htmlspecialchars($system['linkedin']); ?>" target="_blank" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
        <?php endif; ?>
        <?php if (!empty($system['youtube'])): ?>
        <a href="<?php echo htmlspecialchars($system['youtube']); ?>" target="_blank" class="social-icon"><i class="fab fa-youtube"></i></a>
        <?php endif; ?>
      </div>
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

<div id="modal" class="modal">
  <a href="register.php" class="modal-link"><i class="fas fa-user-plus"></i> Register</a>
  <a href="login.php" class="modal-link"><i class="fas fa-sign-in-alt"></i> Sign In</a>
</div>

<script>
  const eventLinks = document.querySelectorAll('.event-link');
  const eventModal = document.getElementById('eventModal');
  const closeEventModal = document.getElementById('closeEventModal');
  
  document.getElementById('yearSelector').addEventListener('change', function() {
    document.getElementById('yearSelectorForm').submit();
  });
  
  eventLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      const eventId = this.getAttribute('data-id');
      
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
  
  document.addEventListener('DOMContentLoaded', function() {
    ScrollReveal().reveal('.hero-content', {
      delay: 200,
      distance: '50px',
      origin: 'bottom',
      duration: 1000
    });
    
    ScrollReveal().reveal('.event-card', {
      delay: 200,
      distance: '50px',
      origin: 'bottom',
      duration: 800,
      interval: 200
    });
    
    ScrollReveal().reveal('.gallery-item', {
      delay: 200,
      distance: '50px',
      origin: 'left',
      duration: 800,
      interval: 150
    });
    
    ScrollReveal().reveal('.about-image', {
      delay: 200,
      distance: '100px',
      origin: 'left',
      duration: 1000,
      easing: 'cubic-bezier(0.5, 0, 0, 1)',
      scale: 0.9
    });
    
    ScrollReveal().reveal('.about-content', {
      delay: 400,
      distance: '100px',
      origin: 'right',
      duration: 1000
    });
    
    ScrollReveal().reveal('.officer-card', {
      delay: 200,
      distance: '30px',
      origin: 'bottom',
      duration: 800,
      interval: 150
    });
    
    ScrollReveal().reveal('.section-title', {
      delay: 100,
      distance: '20px',
      origin: 'top',
      duration: 800
    });
  });
</script>

</body>
</html>
