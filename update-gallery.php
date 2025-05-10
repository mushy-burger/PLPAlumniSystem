<?php
session_start();
require_once 'config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gallery_id'])) {
    $gallery_id = intval($_POST['gallery_id']);
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    
    if ($gallery_id > 0) {
        $update_query = "UPDATE gallery SET title = '$title', description = '$description'";
        
        if (isset($_FILES['gallery_image']) && $_FILES['gallery_image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; 
            
            if (in_array($_FILES['gallery_image']['type'], $allowed_types) && $_FILES['gallery_image']['size'] <= $max_size) {
                $new_filename = uniqid() . '_' . basename($_FILES['gallery_image']['name']);
                $target_path = 'uploads/gallery/' . $new_filename;
                
                if (!is_dir('uploads/gallery')) {
                    mkdir('uploads/gallery', 0777, true);
                }
                
                $result = $conn->query("SELECT image_path FROM gallery WHERE id = $gallery_id");
                $old_image = '';
                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $old_image = $row['image_path'];
                }
                
                if (move_uploaded_file($_FILES['gallery_image']['tmp_name'], $target_path)) {
                    $update_query .= ", image_path = '$target_path'";
                    
                    if (!empty($old_image) && file_exists($old_image)) {
                        unlink($old_image);
                    }
                } else {
                    $_SESSION['error_message'] = "Failed to move uploaded file.";
                    header("Location: admin-gallery.php");
                    exit();
                }
            } else {
                $_SESSION['error_message'] = "Invalid file. Please upload JPG, PNG, or GIF files under 5MB.";
                header("Location: admin-gallery.php");
                exit();
            }
        }
        
        $update_query .= " WHERE id = $gallery_id";
        
        if ($conn->query($update_query) === TRUE) {
            $_SESSION['success_message'] = "Gallery item updated successfully!";
        } else {
            $_SESSION['error_message'] = "Error updating gallery item: " . $conn->error;
        }
    }
    
    header("Location: admin-gallery.php");
    exit();
}

header("Location: admin-gallery.php");
exit();
?>
