<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db_connection.php';
session_start();

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id === 0) die("⚠️ Invalid question ID.");

$sql = "SELECT * FROM quizquestion WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$q = $stmt->get_result()->fetch_assoc();

if (!$q) die("⚠️ Question not found.");

$quizID = $q['quizID'];
$question = $_POST['question'];
$a = $_POST['answerA'];
$b = $_POST['answerB'];
$c = $_POST['answerC'];
$d = $_POST['answerD'];
$correct = $_POST['correctAnswer'];
$fileName = $q['questionFigureFileName'];

if (!empty($_FILES['figure']['name'])) {
  $newName = time() . '_' . basename($_FILES['figure']['name']);
  move_uploaded_file($_FILES['figure']['tmp_name'], "uploads/" . $newName);
  if (!empty($fileName) && file_exists("uploads/" . $fileName)) unlink("uploads/" . $fileName);
  $fileName = $newName;
}

$sql = "UPDATE quizquestion SET question=?, questionFigureFileName=?, answerA=?, answerB=?, answerC=?, answerD=?, correctAnswer=? WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssssi", $question, $fileName, $a, $b, $c, $d, $correct, $id);
$stmt->execute();

header("Location: quiz_page.php?quizID=$quizID");
exit;
?>
