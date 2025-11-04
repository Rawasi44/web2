<?php
// recommend_question.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['userType'] !== 'learner') {
  header('Location: login.php'); exit;
}
require 'db_connection.php';

$learnerID = (int)$_SESSION['userID'];
$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // استلام القيم
  $topicID    = (int)($_POST['topicID'] ?? 0);
  $educatorID = (int)($_POST['educatorID'] ?? 0);
  $question   = trim($_POST['question'] ?? '');
  $a          = trim($_POST['answerA'] ?? '');
  $b          = trim($_POST['answerB'] ?? '');
  $c          = trim($_POST['answerC'] ?? '');
  $d          = trim($_POST['answerD'] ?? '');
  $correctNum = (int)($_POST['correct'] ?? 0); // 1..4
  $correctMap = [1=>'A', 2=>'B', 3=>'C', 4=>'D'];
  $correct    = $correctMap[$correctNum] ?? '';

  if (!$topicID || !$educatorID || !$question || !$a || !$b || !$c || !$d || !$correct) {
    $error = "Please fill all required fields.";
  } else {
    $quiz = $conn->prepare("SELECT id FROM Quiz WHERE educatorID=? AND topicID=?");
    $quiz->bind_param("ii", $educatorID, $topicID);
    $quiz->execute();
    $quizRes = $quiz->get_result()->fetch_assoc();
    $quiz->close();

    if (!$quizRes) {
      $error = "No quiz found for the selected educator & topic.";
    } else {
      $quizID = (int)$quizRes['id'];

      $figureFileName = null;
      if (!empty($_FILES['figure']['name']) && $_FILES['figure']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['figure']['name'], PATHINFO_EXTENSION);
        $safe = bin2hex(random_bytes(8)) . "_" . preg_replace("/[^a-zA-Z0-9._-]/","", $_FILES['figure']['name']);
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
      if ($stmt->execute()) {
    
        header("Location: learner_home.php"); exit;
      } else {
        $error = "Failed to submit. Please try again.";
      }
      $stmt->close();
    }
  }
}

$topicsRes = $conn->query("SELECT id, topicName FROM Topic ORDER BY topicName");
$educRes   = $conn->query("SELECT id, firstName, lastName FROM User WHERE userType='educator' ORDER BY firstName, lastName");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Recommend Question</title>
  <link rel="stylesheet" href="style.css" />
  <script src="script.js" defer></script>
</head>
<body>
  <div class="header">
    <div class="logo"><img src="images/logo.png" alt="Logo"></div>
    <h3>Recommend a New Question</h3>
    <a href="learner_home.php">Back to Homepage</a>
  </div>

  <div class="form-container" style="max-width:700px;">

    <?php if ($error): ?>
      <div class="alert error" style="margin-bottom:12px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
  <!-- Question -->
  <div class="form-row">
    <label for="qText">Question Text:</label>
    <textarea name="question" id="qText" class="input-field" rows="3" placeholder="Enter your question here..." required></textarea>
  </div>

  <div class="form-row form-row--stack">
    <label>Choices:</label>
    <div class="stack">
      <input name="answerA" type="text" class="input-field" placeholder="Choice 1" required>
      <input name="answerB" type="text" class="input-field" placeholder="Choice 2" required>
      <input name="answerC" type="text" class="input-field" placeholder="Choice 3" required>
      <input name="answerD" type="text" class="input-field" placeholder="Choice 4" required>
    </div>
  </div>

  <!-- Correct choice -->
  <div class="form-row">
    <label for="correct">Correct Choice:</label>
    <select name="correct" id="correct" class="input-field" required>
      <option value="">-- Select correct choice --</option>
      <option value="1">Choice 1</option>
      <option value="2">Choice 2</option>
      <option value="3">Choice 3</option>
      <option value="4">Choice 4</option>
    </select>
  </div>

  <!-- Figure -->
  <div class="form-row">
    <label for="figure">Figure (optional):</label>
    <input name="figure" id="figure" type="file" class="input-field" accept="image/*">
  </div>

  <!-- Topic -->
  <div class="form-row">
    <label for="topic">Topic:</label>
    <select name="topicID" id="topic" class="input-field" required>
      <option value="">-- Select topic --</option>
    </select>
  </div>

  <!-- Educator -->
  <div class="form-row">
    <label for="educator">Educator:</label>
    <select name="educatorID" id="educator" class="input-field" required>
      <option value="">-- Select educator --</option>
    </select>
  </div>

  <!-- Submit -->
  <div class="form-row">
    <label></label>
    <button type="submit" class="btn-primary">Submit Question</button>
  </div>
</form>

  <footer class="site-footer">
    <div class="footer-brand">
      <img src="images/logo.png" alt="Edulearn Logo" class="footer-logo">
      <h2 class="footer-name"><span style="color:rgb(167, 203, 251);">EduLearn</span></h2>
    </div>
    <p class="footer-copy">&copy; 2025 Edulearn. All rights reserved.</p>
  </footer>
</body>
</html>
