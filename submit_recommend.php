<?php

session_start();
if (!isset($_SESSION['userID']) || ($_SESSION['userType'] ?? '') !== 'learner') {
  header('Location: login.php'); exit;
}
require 'db_connection.php';

$learnerID = (int)$_SESSION['userID'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: recommend_question.php'); exit;
}


$topicID    = (int)($_POST['topicID'] ?? 0);
$educatorID = (int)($_POST['educatorID'] ?? 0);
$question   = trim($_POST['question'] ?? '');
$a = trim($_POST['answerA'] ?? '');
$b = trim($_POST['answerB'] ?? '');
$c = trim($_POST['answerC'] ?? '');
$d = trim($_POST['answerD'] ?? '');
$correctNum = (int)($_POST['correct'] ?? 0);
$correctMap = [1=>'A', 2=>'B', 3=>'C', 4=>'D'];
$correct    = $correctMap[$correctNum] ?? '';

if (!$topicID || !$educatorID || !$question || !$a || !$b || !$c || !$d || !$correct) {
  $_SESSION['flash_error'] = 'Please fill all required fields.';
  header('Location: recommend_question.php'); exit;
}


$q = $conn->prepare("SELECT id FROM Quiz WHERE educatorID=? AND topicID=?");
$q->bind_param("ii", $educatorID, $topicID);
$q->execute();
$row = $q->get_result()->fetch_assoc();
$q->close();

if (!$row) {
  $_SESSION['flash_error'] = 'No quiz found for the selected educator & topic.';
  header('Location: recommend_question.php'); exit;
}
$quizID = (int)$row['id'];


$figureFileName = null;
if (!empty($_FILES['figure']['name']) && $_FILES['figure']['error'] === UPLOAD_ERR_OK) {
  $orig = preg_replace("/[^a-zA-Z0-9._-]/", "", $_FILES['figure']['name']);
  $safe = bin2hex(random_bytes(8)) . "_" . $orig;
  $dest = __DIR__ . "/uploads/" . $safe;
  if (move_uploaded_file($_FILES['figure']['tmp_name'], $dest)) {

    $figureFileName = "uploads/" . $safe;
  }
}


$stmt = $conn->prepare("
  INSERT INTO RecommendedQuestion
    (quizID, learnerID, question, questionFigureFileName,
     answerA, answerB, answerC, answerD, correctAnswer, status)
  VALUES (?,?,?,?,?,?,?,?,?,'pending')
");
$stmt->bind_param(
  "iisssssss",
  $quizID, $learnerID, $question, $figureFileName,
  $a, $b, $c, $d, $correct
);
$stmt->execute();
$stmt->close();


header('Location: learner_home.php'); exit;
