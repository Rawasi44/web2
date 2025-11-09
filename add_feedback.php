<?php

session_start();
include 'db_connection.php'; // للاتصال بقاعدة البيانات

// 1. التأكد أن المستخدم "متعلم"
if (!isset($_SESSION['userID']) || $_SESSION['userType'] !== 'learner') {
    // إذا حاول أحد فتح الملف مباشرة، أعده للصفحة الرئيسية
    header('Location: learner_home.php');
    exit;
}

// 2. التأكد أن الطلب هو POST (من فورم الفيدباك)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $learnerID = (int)$_SESSION['userID'];
    $quizID_feedback = intval($_POST['quizID_feedback']);
    $rating = intval($_POST['rating']);
    $comments = trim($_POST['comments']);

    if ($quizID_feedback > 0 && $rating > 0) {
        // 3. حفظ الفيدباك في قاعدة البيانات
        $stmt = $conn->prepare("
            INSERT INTO quizfeedback (quizID, learnerID, rating, comments, date) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("iiss", $quizID_feedback, $learnerID, $rating, $comments);
        $stmt->execute();
        $stmt->close();
    }
    
    // 4. إعادة التوجيه إلى صفحة المتعلم
    header('Location: learner_home.php');
    exit;
    
} else {
    // إذا لم يكن الطلب POST، أعده للصفحة الرئيسية
    header('Location: learner_home.php');
    exit;
}
?>