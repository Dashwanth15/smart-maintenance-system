<?php
// Database connection - modified to avoid mysqli exceptions thrown as HTML fatal errors
mysqli_report(MYSQLI_REPORT_OFF);
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'smart_maintenance';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) { 
    // return json-friendly error when included in AJAX scripts
    header('Content-Type: application/json');
    echo json_encode(['success'=>false,'error'=>'DB connection failed: '.$conn->connect_error]);
    exit;
}
$conn->set_charset('utf8mb4');
?>