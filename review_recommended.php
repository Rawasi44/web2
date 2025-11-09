<?php
session_start();
if (!isset($_SESSION['userID']) || ($_SESSION['userType'] ?? '') !== 'educator') {
  header('Location: login.php'); exit;
}
require 'db_connection.php';

$educatorID = (int)$_SESSION['userID'];
$recID  = (int)($_POST['recID'] ?? 0);
$action = $_POST['action'] ?? '';
$comment= trim($_POST['comment'] ?? '');

if (!$recID || !in_array($action, ['approve','disapprove'], true)) {
  header('Location: educator_home.php'); exit;
}


$q = $conn->prepare("
  SELECT RQ.*
  FROM RecommendedQuestion RQ
  JOIN Quiz Q ON Q.id = RQ.quizID
  WHERE RQ.id=? AND Q.educatorID=?
");
$q->bind_param("ii", $recID, $educatorID);
$q->execute();
$rec = $q->get_result()->fetch_assoc();
$q->close();

if (!$rec) { header('Location: educator_home.php'); exit; }

if ($action === 'approve') {

  $stmt = $conn->prepare("
    INSERT INTO QuizQuestion
      (quizID, question, questionFigureFileName, answerA, answerB, answerC, answerD, correctAnswer)
    VALUES (?,?,?,?,?,?,?,?)
  ");
  $stmt->bind_param(
    "isssssss",
    $rec['quizID'], $rec['question'], $rec['questionFigureFileName'],
    $rec['answerA'], $rec['answerB'], $rec['answerC'], $rec['answerD'], $rec['correctAnswer']
  );
  $stmt->execute();
  $stmt->close();

 
  $c = $conn->real_escape_string($comment);
  $conn->query("UPDATE RecommendedQuestion SET status='approved', comments='$c' WHERE id=$recID");

} else {
  $c = $conn->real_escape_string($comment);
  $conn->query("UPDATE RecommendedQuestion SET status='disapproved', comments='$c' WHERE id=$recID");
}

header('Location: educator_home.php'); exit;


