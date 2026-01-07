<?php
session_start();
include 'db.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'student';
$tech_type = trim($_POST['tech_type'] ?? '');

if (!$name || !$email || !$password) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Normalize technician type if role is technician
if ($role !== 'technician') {
    $tech_type = null;
} else {
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

    $allowed = ['Electrical','Plumbing','Carpentry','Cleaning'];
    if (!in_array($tech_type, $allowed, true)) {
        echo json_encode(['success' => false, 'error' => 'Invalid technician type']);
        exit;
    }
}

$hash = password_hash($password, PASSWORD_BCRYPT);

if ($tech_type === null) {
    $stmt = $conn->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)");
    if (!$stmt) { echo json_encode(['success'=>false,'error'=>'Prepare failed: '.$conn->error]); exit; }
    $stmt->bind_param('ssss', $name, $email, $hash, $role);
} else {
    $stmt = $conn->prepare("INSERT INTO users (name,email,password,role,tech_type) VALUES (?,?,?,?,?)");
    if (!$stmt) { echo json_encode(['success'=>false,'error'=>'Prepare failed: '.$conn->error]); exit; }
    $stmt->bind_param('sssss', $name, $email, $hash, $role, $tech_type);
}

$exec = $stmt->execute();
if ($exec) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error ?: $conn->error]);
}
?>