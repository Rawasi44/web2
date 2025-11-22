<?php
header("Content-Type: application/json");
session_start();

if (!isset($_SESSION['userID']) || ($_SESSION['userType'] ?? '') !== 'learner') {
    echo json_encode([]);
    exit;
}

require 'db_connection.php';

// خذي التوبيك المختار من AJAX
$topicID = (int)($_POST['topicID'] ?? 0);

// نفس الاستعلام الموجود عندك فوق → لكن بدون HTML
$sql = "
  SELECT
    q.id            AS quizID,
    t.topicName     AS topic,
    u.firstName,
    u.lastName,
    u.photoFileName AS educatorPhoto,
    (SELECT COUNT(*) FROM quizquestion qq WHERE qq.quizID = q.id)  AS qCount
  FROM quiz q
  JOIN topic t ON t.id = q.topicID
  JOIN user  u ON u.id = q.educatorID
";

if($topicID > 0){
    $sql .= " WHERE q.topicID = $topicID ";
}

$sql .= " ORDER BY t.topicName, u.firstName, u.lastName";

$res = $conn->query($sql);

$data = [];

function safe_photo_ajax($p){
   if (!$p) return "images/default.png";

    if (preg_match('#^(uploads/|images/)#i', $p))
        return $p;

    if (is_file("uploads/".$p))
        return "uploads/".$p;

    if (is_file("images/".$p))
        return "images/".$p;

    if (is_file($p))
        return $p;

    return "images/default.png";
}

while($r = $res->fetch_assoc()){
    $r['educatorPhoto'] = safe_photo_ajax($r['educatorPhoto']);
    $data[] = $r;
}

echo json_encode($data);
exit;
?>
