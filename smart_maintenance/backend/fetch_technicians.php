<?php
include 'db.php';
header('Content-Type: application/json; charset=utf-8');
try {
    $tech_type = trim($_GET['tech_type'] ?? '');
    if (!$tech_type) {
        echo json_encode(['success' => false, 'error' => 'Missing tech_type']);
        exit;
    }

    // Normalize common labels to canonical values
    $map = [
      'electrician' => 'Electrical',
      'electrical' => 'Electrical',
      'plumber' => 'Plumbing',
      'plumbing' => 'Plumbing',
      'carpenter' => 'Carpentry',
      'carpentry' => 'Carpentry',
      'cleaner' => 'Cleaning',
      'cleaning' => 'Cleaning'
    ];
    $key = strtolower($tech_type);
    $tech_type = $map[$key] ?? $tech_type;

    // Use case-insensitive comparison
    $stmt = $conn->prepare("SELECT user_id, name FROM users WHERE role = 'technician' AND LOWER(COALESCE(tech_type,'')) = LOWER(?)");
    if (!$stmt) {
        echo json_encode(['success'=>false,'error'=>'Prepare failed: '.$conn->error]);
        exit;
    }
    $stmt->bind_param('s', $tech_type);
    $stmt->execute();
    $res = $stmt->get_result();
    $techs = $res->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'techs' => $techs]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: '.$e->getMessage()]);
}
?>