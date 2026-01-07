<?php
session_start();
include 'db.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'error'=>'Unauthorized']); exit; }
$role = $_SESSION['role']; $uid = $_SESSION['user_id'];
if ($role==='admin') {
    $sql = "SELECT r.*, s.name AS student_name, t.name AS technician_name, r.tech_type FROM maintenance_requests r LEFT JOIN users s ON r.student_id=s.user_id LEFT JOIN users t ON r.technician_id=t.user_id ORDER BY r.created_at DESC";
    $res = $conn->query($sql); echo json_encode(['success'=>true,'requests'=>$res->fetch_all(MYSQLI_ASSOC)]); exit;
}
if ($role==='student') {
    $stmt = $conn->prepare("SELECT r.*, t.name AS technician_name FROM maintenance_requests r LEFT JOIN users t ON r.technician_id=t.user_id WHERE r.student_id=? ORDER BY r.created_at DESC");
    $stmt->bind_param('i',$uid); $stmt->execute(); echo json_encode(['success'=>true,'requests'=>$stmt->get_result()->fetch_all(MYSQLI_ASSOC)]); exit;
}
if ($role==='technician') {
    $stmt = $conn->prepare("SELECT r.*, s.name AS student_name FROM maintenance_requests r LEFT JOIN users s ON r.student_id=s.user_id WHERE r.technician_id=? ORDER BY r.created_at DESC");
    $stmt->bind_param('i',$uid); $stmt->execute(); echo json_encode(['success'=>true,'requests'=>$stmt->get_result()->fetch_all(MYSQLI_ASSOC)]); exit;
}
echo json_encode(['success'=>false,'error'=>'Unknown role']);
?>