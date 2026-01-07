<?php
session_start();
include 'db.php';
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit; }
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
if (!$email || !$password) { echo json_encode(['success'=>false,'error'=>'Missing']); exit; }
$stmt = $conn->prepare("SELECT user_id,name,password,role FROM users WHERE email=?");
$stmt->bind_param('s',$email); $stmt->execute(); $res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    if (password_verify($password, $row['password'])) {
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['name'] = $row['name'];
        $_SESSION['role'] = $row['role'];
        echo json_encode(['success'=>true,'role'=>$row['role']]);
    } else echo json_encode(['success'=>false,'error'=>'Invalid credentials']);
} else echo json_encode(['success'=>false,'error'=>'User not found']);
?>