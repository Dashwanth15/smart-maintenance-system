<?php
include 'db.php';
$name = 'admin';
$email = 'admin@local';
$pass = 'admin123';
$hash = password_hash($pass, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?, 'admin')");
$stmt->bind_param('sss',$name,$email,$hash);
if ($stmt->execute()) echo "Admin created: $email / $pass"; else echo "Error: " . $stmt->error;
?>