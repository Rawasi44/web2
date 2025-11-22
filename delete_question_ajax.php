<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db_connection.php';
session_start();

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // حذف الصورة إن وُجدت
    $stmt = $conn->prepare("SELECT questionFigureFileName FROM quizquestion WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        if (!empty($row['questionFigureFileName'])) {
            $path = "uploads/" . $row['questionFigureFileName'];
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    // حذف السؤال من قاعدة البيانات
    $stmt = $conn->prepare("DELETE FROM quizquestion WHERE id = ?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();

    echo json_encode($success);
    exit;
}

echo json_encode(false);
exit;
