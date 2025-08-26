<?php
require_once 'db_config.php';

// Check if table exists, create if not
$table_check = $conn->query("SHOW TABLES LIKE 'admins'");
if ($table_check->num_rows == 0) {
    $create_table = "CREATE TABLE `admins` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(50) NOT NULL,
        `password` varchar(255) NOT NULL,
        `email` varchar(100) NOT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($create_table) {
        echo "Admins table created successfully.<br>";
    } else {
        die("Error creating table: " . $conn->error);
    }
}

// Check if admin exists
$username = 'admin';
$check = $conn->prepare("SELECT id FROM admins WHERE username = ?");
$check->bind_param("s", $username);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo "Admin user already exists!";
} else {
    // Create new admin
    $password = 'admin123'; // Change this to a strong password!
 
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO admins (username, password, email) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashed_password, $email);

    if ($stmt->execute()) {
        echo "Admin user created successfully!<br>";
        echo "Username: admin<br>";
        echo "Password: admin123 (change this immediately!)";
    } else {
        echo "Error creating admin user: " . $conn->error;
    }
    
    $stmt->close();
}

$check->close();
$conn->close();

// Security recommendation
echo "<br><br>IMPORTANT: Delete or rename this file after creating the admin account!";
?>