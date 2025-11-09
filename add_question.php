<?php
include 'db_connection.php';
session_start();

$quizID = isset($_GET['quizID']) ? intval($_GET['quizID']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Question</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="header">
  <div class="logo"><img src="images/logo.png" alt="Logo"></div>
  <h3>Add New Question</h3>
</div>

<div class="form-container">
  <form action="add_question_process.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="quizID" value="<?= htmlspecialchars($quizID); ?>">

    <div class="form-group">
      <label>Question:</label>
      <textarea name="question" required></textarea>
    </div>

    <div class="form-group">
      <label>Upload Figure:</label>
      <input type="file" name="figure">
    </div>

    <div class="form-group"><label>Answer A:</label><input type="text" name="answerA" required></div>
    <div class="form-group"><label>Answer B:</label><input type="text" name="answerB" required></div>
    <div class="form-group"><label>Answer C:</label><input type="text" name="answerC" required></div>
    <div class="form-group"><label>Answer D:</label><input type="text" name="answerD" required></div>

    <div class="form-group">
      <label>Correct Answer:</label>
      <select name="correctAnswer" required>
        <option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option>
      </select>
    </div>

    <button type="submit" class="btn-primary">Add Question</button>
  </form>
</div>
</body>
</html>
