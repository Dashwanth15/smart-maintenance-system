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
    $check = $conn->prepare("SELECT status, start_time FROM maintenance_requests WHERE request_id = ? AND technician_id = ? LIMIT 1");
    $check->bind_param('ii', $request_id, $tech_id);
    $check->execute();
    $cres = $check->get_result();
    if ($cres->num_rows===0) { echo json_encode(['success'=>false,'error'=>'Not assigned to you']); exit; }
    $r = $cres->fetch_assoc();
    $status = strtolower(trim($r['status'] ?? ''));
    if ($status !== 'in progress' && $status !== 'inprogress') { echo json_encode(['success'=>false,'error'=>'Not in progress']); exit; }
    // compute total time if start_time exists
    $start = $r['start_time'] ?? null;
    // set status to Completed and record end_time
    $ustmt = $conn->prepare("UPDATE maintenance_requests SET status = 'Completed', end_time = NOW() WHERE request_id = ? AND technician_id = ?");
    $ustmt->bind_param('ii', $request_id, $tech_id);
    if (!$ustmt->execute()) { echo json_encode(['success'=>false,'error'=>$ustmt->error]); exit; }
    // compute total_time (H:i:s) and update field if exists
    if ($start) {
        $r2 = $conn->prepare("SELECT start_time, end_time FROM maintenance_requests WHERE request_id = ? LIMIT 1");
        $r2->bind_param('i', $request_id);
        $r2->execute();
        $res2 = $r2->get_result();
        $rr = $res2->fetch_assoc();
        if ($rr['start_time'] && $rr['end_time']) {
            $s = new DateTime($rr['start_time']);
            $e = new DateTime($rr['end_time']);
            $diff = $e->getTimestamp() - $s->getTimestamp();
            $total = gmdate('H:i:s', $diff);
            // attempt to update total_time column if exists
            $u2 = $conn->prepare("UPDATE maintenance_requests SET total_time = ? WHERE request_id = ?");
            $u2->bind_param('si', $total, $request_id);
            @$u2->execute();
        } else {
            $total = null;
        }
    } else {
        $total = null;
    }
    // return updated row
    $rstmt = $conn->prepare("SELECT r.request_id, r.title, r.location, r.status, r.start_time, r.end_time, r.total_time, r.technician_id, t.name AS technician_name FROM maintenance_requests r LEFT JOIN users t ON r.technician_id = t.user_id WHERE r.request_id = ? LIMIT 1");
    $rstmt->bind_param('i', $request_id);
    $rstmt->execute();
    $res = $rstmt->get_result();
    $row = $res->fetch_assoc();
    echo json_encode(['success'=>true,'request'=>$row,'total_time'=>$total]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
?>