<?php
include 'db.php'; header('Content-Type: application/json');
$sql = "SELECT rv.*, u.name AS student_name, t.name AS technician_name FROM reviews rv LEFT JOIN users u ON rv.student_id=u.user_id LEFT JOIN users t ON rv.technician_id=t.user_id ORDER BY rv.created_at DESC";
$res = $conn->query($sql);
echo json_encode(['success'=>true,'reviews'=>$res->fetch_all(MYSQLI_ASSOC)]);
?>