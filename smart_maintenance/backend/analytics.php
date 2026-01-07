<?php
include 'db.php'; header('Content-Type: application/json');
$sql = "SELECT u.user_id,u.name, COUNT(r.request_id) AS total_tasks, ROUND(AVG(r.time_taken),2) AS avg_time, ROUND(AVG(rv.rating),2) AS avg_rating FROM users u LEFT JOIN maintenance_requests r ON u.user_id=r.technician_id LEFT JOIN reviews rv ON r.request_id=rv.request_id WHERE u.role='technician' GROUP BY u.user_id";
$res = $conn->query($sql);
echo json_encode(['success'=>true,'rows'=>$res->fetch_all(MYSQLI_ASSOC)]);
?>