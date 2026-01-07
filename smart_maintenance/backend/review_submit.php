<?php
session_start(); include 'db.php';
if ($_SERVER['REQUEST_METHOD']!=='POST'){ http_response_code(405); exit; }
if (!isset($_SESSION['user_id']) || $_SESSION['role']!=='student'){ echo json_encode(['success'=>false,'error'=>'Unauthorized']); exit; }
$student_id = $_SESSION['user_id']; $request_id = intval($_POST['request_id'] ?? 0); $technician_id = intval($_POST['technician_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 0); $feedback = trim($_POST['feedback'] ?? '');
if (!$request_id || !$technician_id || $rating<1 || $rating>5) { echo json_encode(['success'=>false,'error'=>'Invalid input']); exit; }
$stmt = $conn->prepare("INSERT INTO reviews (request_id, student_id, technician_id, rating, feedback) VALUES (?,?,?,?,?)");
$stmt->bind_param('iiiis',$request_id,$student_id,$technician_id,$rating,$feedback);
if ($stmt->execute()) echo json_encode(['success'=>true]); else echo json_encode(['success'=>false,'error'=>$stmt->error]);
?>