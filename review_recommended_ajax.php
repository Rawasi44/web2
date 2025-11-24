<?php
// review_recommended_ajax.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['userID']) || ($_SESSION['userType'] ?? '') !== 'educator') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require 'db_connection.php';

$educatorID = (int)$_SESSION['userID'];

// استلام البيانات من AJAX
$recID   = isset($_POST['recID']) ? (int)$_POST['recID'] : 0;
$action  = $_POST['action']  ?? '';
$comment = trim($_POST['comment'] ?? '');

if ($recID <= 0 || !in_array($action, ['approve','disapprove'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    $conn->begin_transaction();

    // 1) جلب بيانات السؤال الموصى به
    $stmt = $conn->prepare("
        SELECT *
        FROM recommendedquestion
        WHERE id = ? 
          AND status = 'pending'
    ");
    $stmt->bind_param("i", $recID);
    $stmt->execute();
    $res = $stmt->get_result();
    $rec = $res->fetch_assoc();
    $stmt->close();

    if (!$rec) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Recommended question not found or already processed.']);
        exit;
    }

    // 2) تحديث الحالة والتعليق في جدول recommendedquestion
    $newStatus = ($action === 'approve') ? 'approved' : 'disapproved';

    $stmt = $conn->prepare("
        UPDATE recommendedquestion
        SET status = ?, comments = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssi", $newStatus, $comment, $recID);
    $stmt->execute();
    $stmt->close();

    // 3) إذا Approved → نضيف السؤال لجدول أسئلة الكويز
    if ($action === 'approve') {
       
        $stmt = $conn->prepare("
            INSERT INTO quizquestion
              (quizID, question, answerA, answerB, answerC, answerD, correctAnswer, questionFigureFileName)
            VALUES
              (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "isssssss",
            $rec['quizID'],
            $rec['question'],
            $rec['answerA'],
            $rec['answerB'],
            $rec['answerC'],
            $rec['answerD'],
            $rec['correctAnswer'],
            $rec['questionFigureFileName']
        );
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();

    echo json_encode(['success' => true]);
    exit;

} catch (Throwable $e) {
    $conn->rollback();
    
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}
