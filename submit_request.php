<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized user.']);
    exit();
}

require 'db_connect.php';

$userId = $_SESSION['user_id'];
$lessonName = $_POST['lesson_name'] ?? '';
$room = $_POST['room'] ?? '';
$requiredDate = $_POST['required_date'] ?? '';
$otherItems = $_POST['other_items'] ?? '';
$items = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];

if (empty($lessonName) || empty($room) || empty($requiredDate)) {
    echo json_encode(['success' => false, 'message' => 'Please fill out all required fields.']);
    exit();
}

$uploadedFileName = NULL;
if (isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/';
    $safeFileName = time() . '_' . basename($_FILES['upload_file']['name']);
    $uploadFilePath = $uploadDir . $safeFileName;
    
    if (move_uploaded_file($_FILES['upload_file']['tmp_name'], $uploadFilePath)) {
        $uploadedFileName = $safeFileName;
    }
}

$conn->begin_transaction();

try {
    $stmt1 = $conn->prepare("INSERT INTO Requests (user_id, lesson_name, room, required_date, other_items, attached_file) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt1->bind_param("isssss", $userId, $lessonName, $room, $requiredDate, $otherItems, $uploadedFileName);
    $stmt1->execute();
    $newRequestId = $conn->insert_id;
    $stmt1->close();

    if (!empty($items)) {
        $stmt2 = $conn->prepare("INSERT INTO Request_Items (request_id, equipment_id, quantity) VALUES (?, ?, ?)");
        foreach ($items as $item) {
            $stmt2->bind_param("iii", $newRequestId, $item['id'], $item['quantity']);
            $stmt2->execute();
        }
        $stmt2->close();
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>