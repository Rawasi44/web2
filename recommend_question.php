<?php
session_start();
if (!isset($_SESSION['userID']) || ($_SESSION['userType'] ?? '') !== 'learner') {
  header('Location: login.php'); exit;
}
require 'db_connection.php';


$topicsRes = $conn->query("SELECT id, topicName 
FROM topic
ORDER BY FIELD(topicName, 'HTML & CSS', 'JavaScript', 'Databases');
");
$educRes   = $conn->query("SELECT id, firstName, lastName FROM User WHERE userType='educator' ORDER BY firstName, lastName");


$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Recommend Question</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="header">
    <div class="logo"><img src="images/logo.png" alt="Logo"></div>
    <h3>Recommend a New Question</h3>
    <a href="learner_home.php">Back to Homepage</a>
  </div>

  <div class="form-container" style="max-width:700px;">
    <?php if ($flash_error): ?>
      <div class="alert error" style="margin-bottom:12px;"><?= htmlspecialchars($flash_error) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" action="submit_recommend.php">
      <!-- Question -->
      <div class="form-row">
        <label for="qText">Question Text:</label>
        <textarea name="question" id="qText" class="input-field" rows="3" required placeholder="Enter your question here..."></textarea>
      </div>

      <!-- Choices -->
      <div class="form-row form-row--stack">
        <label>Choices:</label>
        <div class="stack">
          <input name="answerA" type="text" class="input-field" placeholder="Choice 1" required>
          <input name="answerB" type="text" class="input-field" placeholder="Choice 2" required>
          <input name="answerC" type="text" class="input-field" placeholder="Choice 3" required>
          <input name="answerD" type="text" class="input-field" placeholder="Choice 4" required>
        </div>
      </div>

      <!-- Correct -->
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
          <?php while($t = $topicsRes->fetch_assoc()): ?>
            <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['topicName']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>

      <!-- Educator -->
      <div class="form-row">
        <label for="educator">Educator:</label>
        <select name="educatorID" id="educator" class="input-field" required>
          <option value="">-- Select educator --</option>
          <?php while($e = $educRes->fetch_assoc()): ?>
            <option value="<?= (int)$e['id'] ?>"><?= htmlspecialchars($e['firstName'].' '.$e['lastName']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>

      <!-- Submit -->
      <div class="form-row">
        <label></label>
        <button type="submit" class="btn-primary">Submit Question</button>
      </div>
    </form>
  </div>

  <footer class="site-footer">
    <div class="footer-brand">
      <img src="images/logo.png" alt="Edulearn Logo" class="footer-logo">
      <h2 class="footer-name"><span style="color:rgb(167, 203, 251);">EduLearn</span></h2>
    </div>
    <p class="footer-copy">&copy; 2025 Edulearn. All rights reserved.</p>
  </footer>
</body>
</html>




