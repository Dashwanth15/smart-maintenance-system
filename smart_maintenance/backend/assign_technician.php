<?php
include 'db.php';
header('Content-Type: application/json; charset=utf-8');
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success'=>false,'error'=>'Invalid request method']);
        exit;
    }
    $request_id = intval($_POST['request_id'] ?? 0);
    $tech_id = intval($_POST['technician_id'] ?? 0);
    $tech_type = trim($_POST['technician_type'] ?? '');
    if (!$request_id || !$tech_id) {
        echo json_encode(['success'=>false,'error'=>'Missing parameters']);
        exit;
    }
    // verify technician
    $tstmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND role = 'technician' LIMIT 1");
    $tstmt->bind_param('i', $tech_id);
    $tstmt->execute();
    $tres = $tstmt->get_result();
    if ($tres->num_rows === 0) {
        echo json_encode(['success'=>false,'error'=>'Technician not found']);
        exit;
    }
    // update row
    $ustmt = $conn->prepare("UPDATE maintenance_requests SET technician_id = ?, tech_type = ?, status = 'Assigned' WHERE request_id = ?");
    $ustmt->bind_param('isi', $tech_id, $tech_type, $request_id);
    if (!$ustmt->execute()) {
        echo json_encode(['success'=>false,'error'=>$ustmt->error]);
        exit;
    }
    // return updated row
    $rstmt = $conn->prepare("SELECT r.request_id, r.title, r.location, r.tech_type, r.technician_id, r.start_time, r.end_time, r.status, s.name AS student_name, t.name AS technician_name FROM maintenance_requests r LEFT JOIN users s ON r.student_id=s.user_id LEFT JOIN users t ON r.technician_id=t.user_id WHERE r.request_id = ? LIMIT 1");
    $rstmt->bind_param('i', $request_id);
    $rstmt->execute();
    $res = $rstmt->get_result();
    $row = $res->fetch_assoc();
    echo json_encode(['success'=>true,'request'=>$row]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
?>