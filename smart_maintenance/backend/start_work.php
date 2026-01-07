<?php
session_start();
include 'db.php';
header('Content-Type: application/json; charset=utf-8');
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'error'=>'Invalid method']); exit; }
    if (!isset($_SESSION['user_id']) || $_SESSION['role']!=='technician') { echo json_encode(['success'=>false,'error'=>'Unauthorized']); exit; }
    $request_id = intval($_POST['request_id'] ?? 0);
    $tech_id = intval($_SESSION['user_id']);
    if (!$request_id) { echo json_encode(['success'=>false,'error'=>'Missing request_id']); exit; }
    // ensure assigned to this tech
    $check = $conn->prepare("SELECT status FROM maintenance_requests WHERE request_id = ? AND technician_id = ? LIMIT 1");
    $check->bind_param('ii', $request_id, $tech_id);
    $check->execute();
    $cres = $check->get_result();
    if ($cres->num_rows===0) { echo json_encode(['success'=>false,'error'=>'Not assigned to you']); exit; }
    $row = $cres->fetch_assoc();
    $status = strtolower(trim($row['status'] ?? ''));
    if (!in_array($status, ['assigned','pending','not assigned','not_assigned'])) {
        echo json_encode(['success'=>false,'error'=>'Cannot start in current status']); exit;
    }
    // set status to In Progress and record start_time
    $ustmt = $conn->prepare("UPDATE maintenance_requests SET status = 'In Progress', start_time = NOW() WHERE request_id = ? AND technician_id = ?");
    $ustmt->bind_param('ii', $request_id, $tech_id);
    if (!$ustmt->execute()) { echo json_encode(['success'=>false,'error'=>$ustmt->error]); exit; }
    // return updated row
    $rstmt = $conn->prepare("SELECT r.request_id, r.title, r.location, r.status, r.start_time, r.end_time, r.technician_id, t.name AS technician_name FROM maintenance_requests r LEFT JOIN users t ON r.technician_id = t.user_id WHERE r.request_id = ? LIMIT 1");
    $rstmt->bind_param('i', $request_id);
    $rstmt->execute();
    $res = $rstmt->get_result();
    $row = $res->fetch_assoc();
    echo json_encode(['success'=>true,'request'=>$row]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
?>