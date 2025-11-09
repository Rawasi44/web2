<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db_connection.php';
session_start();

$quizID = isset($_POST['quizID']) ? intval($_POST['quizID']) : 0;
if ($quizID === 0) die("⚠️ No quiz ID provided.");

$question = $_POST['question'];
$a = $_POST['answerA'];
$b = $_POST['answerB'];
$c = $_POST['answerC'];
$d = $_POST['answerD'];
$correct = $_POST['correctAnswer'];

$fileName = null;
if (!empty($_FILES['figure']['name'])) {
  $fileName = time() . '_' . basename($_FILES['figure']['name']);
  move_uploaded_file($_FILES['figure']['tmp_name'], "uploads/" . $fileName);
}

$sql = "INSERT INTO quizquestion (quizID, question, questionFigureFileName, answerA, answerB, answerC, answerD, correctAnswer)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isssssss", $quizID, $question, $fileName, $a, $b, $c, $d, $correct);
$stmt->execute();

header("Location: quiz_page.php?quizID=$quizID");
exit;
?>
