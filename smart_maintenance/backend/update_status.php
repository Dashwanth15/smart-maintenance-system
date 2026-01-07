<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') {
    echo json_encode(['success'=>false,'error'=>'Unauthorized']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'error'=>'Invalid method']);
    exit;
}
if (empty($_POST['request_id']) || empty($_POST['action'])) {
    echo json_encode(['success'=>false,'error'=>'Missing parameters']);
    exit;
}
$tech_id = $_SESSION['user_id'];
$request_id = intval($_POST['request_id']);
$action = $_POST['action'];

include __DIR__ . '/db.php';

if ($action === 'start') {
    // set status to In Progress and set start_time if not set
    $stmt = $conn->prepare("UPDATE maintenance_requests SET status='In Progress', start_time = IFNULL(start_time, NOW()) WHERE request_id = ? AND technician_id = ?");
    $stmt->bind_param('ii',$request_id,$tech_id);
    if (!$stmt->execute()) { echo json_encode(['success'=>false,'error'=>$stmt->error]); exit; }
    // fetch start_time
    $stmt2 = $conn->prepare("SELECT start_time FROM maintenance_requests WHERE request_id = ? AND technician_id = ?");
    $stmt2->bind_param('ii',$request_id,$tech_id);
    $stmt2->execute();
    $res = $stmt2->get_result()->fetch_assoc();
    echo json_encode(['success'=>true,'start_time'=>$res['start_time']]);
    exit;
} elseif ($action === 'complete') {
    // set end_time and status completed, compute total time
    $stmt = $conn->prepare("UPDATE maintenance_requests SET status='Completed', end_time = IFNULL(end_time, NOW()) WHERE request_id = ? AND technician_id = ?");
    $stmt->bind_param('ii',$request_id,$tech_id);
    if (!$stmt->execute()) { echo json_encode(['success'=>false,'error'=>$stmt->error]); exit; }
    // update time_taken in minutes if start_time is set
    $stmtT = $conn->prepare("UPDATE maintenance_requests SET time_taken = TIMESTAMPDIFF(MINUTE, start_time, end_time) WHERE request_id = ? AND technician_id = ?");
    $stmtT->bind_param('ii', $request_id, $tech_id);
    $stmtT->execute();
    // fetch start_time and end_time to compute duration
    $stmt2 = $conn->prepare("SELECT start_time, end_time FROM maintenance_requests WHERE request_id = ? AND technician_id = ?");
    $stmt2->bind_param('ii',$request_id,$tech_id);
    $stmt2->execute();
    $row = $stmt2->get_result()->fetch_assoc();
    $start = $row['start_time'];
    $end = $row['end_time'];
    $total = null;
    if ($start && $end) {
        $s = new DateTime($start);
        $e = new DateTime($end);
        $diff = $e->getTimestamp() - $s->getTimestamp();
        $total = gmdate('H:i:s', $diff);
    }
    echo json_encode(['success'=>true,'end_time'=>$end,'total_time'=>$total]);
    exit;
} else {
    echo json_encode(['success'=>false,'error'=>'Unknown action']);
    exit;
}
?>