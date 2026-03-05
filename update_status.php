<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Planner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require 'db_connect.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['request_id']) || !isset($data['new_status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit();
}

$requestId = $data['request_id'];
$newStatus = $data['new_status'];

$stmt = $conn->prepare("UPDATE Requests SET status = ? WHERE request_id = ?");
$stmt->bind_param("si", $newStatus, $requestId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
}

$stmt->close();
$conn->close();
?>