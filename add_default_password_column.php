<?php
include 'admin/db_connect.php';

$sql = "SHOW COLUMNS FROM `users` LIKE 'is_default_password'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    $alter_sql = "ALTER TABLE `users` 
                  ADD `is_default_password` tinyint(1) NOT NULL DEFAULT 1 
                  COMMENT '1=Using default password, 0=Password changed' 
                  AFTER `auto_generated_pass`";
    
    if ($conn->query($alter_sql) === TRUE) {
        echo "Default password column added successfully!";
    } else {
        echo "Error adding default password column: " . $conn->error;
    }
} else {
    echo "Default password column already exists!";
}

$conn->close();
?> 