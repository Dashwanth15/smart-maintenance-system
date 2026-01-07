<?php
session_start();
include 'db.php';
if ($_SERVER['REQUEST_METHOD']!=='POST'){ http_response_code(405); exit; }
if (!isset($_SESSION['user_id']) || $_SESSION['role']!=='student'){ echo json_encode(['success'=>false,'error'=>'Unauthorized']); exit; }
$student_id = $_SESSION['user_id'];
$title = trim($_POST['title'] ?? '');
$tech_type = trim($_POST['tech_type'] ?? '');
$location = trim($_POST['location'] ?? '');
$contact = trim($_POST['contact'] ?? '');
$description = trim($_POST['description'] ?? '');
if (!$title || !$tech_type || !$location || !$description) { echo json_encode(['success'=>false,'error'=>'Missing fields']); exit; }
$stmt = $conn->prepare("INSERT INTO maintenance_requests (student_id,title,description,tech_type,location,contact,status,created_at) VALUES (?,?,?,?,?,?, 'Pending', NOW())");
$stmt->bind_param('isssss', $student_id, $title, $description, $tech_type, $location, $contact);
if ($stmt->execute()) echo json_encode(['success'=>true,'request_id'=>$conn->insert_id]); else echo json_encode(['success'=>false,'error'=>$stmt->error]);
?>