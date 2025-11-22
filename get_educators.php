<?php
require 'db_connection.php';

// استقبال topicID من طلب الـ AJAX
$topicID = isset($_GET['topicID']) ? intval($_GET['topicID']) : 0;

$response = [];

if ($topicID > 0) {
    // تنفيذ استعلام SQL لجلب المعلمين المرتبطين بهذا الموضوع فقط
    $stmt = $conn->prepare("
        SELECT DISTINCT u.id, u.firstName, u.lastName 
        FROM user u
        JOIN quiz q ON u.id = q.educatorID
        WHERE q.topicID = ? AND u.userType = 'educator'
        ORDER BY u.firstName
    ");
    $stmt->bind_param("i", $topicID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // تخزين النتائج في مصفوفة
    while ($row = $result->fetch_assoc()) {
        $response[] = $row;
    }
    $stmt->close();
}

// إرجاع البيانات كـ JSON (حسب النقطة 20)
header('Content-Type: application/json');
echo json_encode($response);
?>