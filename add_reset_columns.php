<?php
include 'admin/db_connect.php';

$sql = "SHOW COLUMNS FROM `users` LIKE 'reset_token'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    $alter_sql = "ALTER TABLE `users` ADD `reset_token` VARCHAR(64) NULL DEFAULT NULL AFTER `auto_generated_pass`, ADD `token_expiry` DATETIME NULL DEFAULT NULL AFTER `reset_token`";
    
    if ($conn->query($alter_sql) === TRUE) {
        echo "Reset token columns added successfully!";
    } else {
        echo "Error adding reset token columns: " . $conn->error;
    }
} else {
    echo "Reset token columns already exist!";
}

$conn->close();
?> 