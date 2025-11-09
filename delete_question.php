<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db_connection.php';
session_start();

if (isset($_GET['id']) && isset($_GET['quizID'])) {
  $id = intval($_GET['id']);
  $quizID = intval($_GET['quizID']);

  $sql = "SELECT questionFigureFileName FROM quizquestion WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $res = $stmt->get_result();

  if ($row = $res->fetch_assoc()) {
    if (!empty($row['questionFigureFileName'])) {
      $path = "uploads/" . $row['questionFigureFileName'];
      if (file_exists($path)) unlink($path);
    }
  }

  $sql = "DELETE FROM quizquestion WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id);
  $stmt->execute();
}

header("Location: quiz_page.php?quizID=$quizID");
exit;
?>
