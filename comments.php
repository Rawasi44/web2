<?php
session_start();
require 'db_connection.php';


$quizID = isset($_GET['quizID']) ? (int)$_GET['quizID'] : 0;
if ($quizID <= 0) {
  http_response_code(400);
  die('Invalid request: missing quizID.');
}


$quizInfo = $conn->query("
  SELECT Q.id, T.topicName, CONCAT(U.firstName,' ',U.lastName) AS educatorName
  FROM Quiz Q
  JOIN Topic T ON T.id=Q.topicID
  JOIN User  U ON U.id=Q.educatorID
  WHERE Q.id=$quizID
")->fetch_assoc();


$stmt = $conn->prepare("SELECT rating, comments, date FROM QuizFeedback WHERE quizID=? ORDER BY date DESC");
$stmt->bind_param("i", $quizID);
$stmt->execute();
$feedbacks = $stmt->get_result();


$avgRow = $conn->query("SELECT ROUND(AVG(rating),2) AS avgRating, COUNT(*) AS cnt FROM QuizFeedback WHERE quizID=$quizID")->fetch_assoc();

function stars($n) {
  $n = max(0, min(5, (int)$n));
  return str_repeat("⭐️", $n);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Comments</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="header">
    <div class="logo"><img src="logo.png" alt="Logo"></div>
    <h3>
      Feedback & Comments
      <?php if ($quizInfo): ?>
        — <small><?= htmlspecialchars($quizInfo['topicName']) ?> · <?= htmlspecialchars($quizInfo['educatorName']) ?></small>
      <?php endif; ?>
    </h3>
    <a href="educator_home.php">Back</a>
  </div>

  <div class="quiz-container" style="max-width:800px;">
    <?php if (($avgRow['cnt'] ?? 0) > 0): ?>
      <p style="opacity:.9;margin-bottom:10px;">
        Average rating: <strong><?= htmlspecialchars($avgRow['avgRating']) ?>/5</strong> ·
        Total comments: <strong><?= (int)$avgRow['cnt'] ?></strong>
      </p>
    <?php else: ?>
      <p style="opacity:.8;margin-bottom:10px;">No feedback yet.</p>
    <?php endif; ?>

    <table class="styled-table">
      <thead>
        <tr>
          <th style="width:120px;">Date</th>
          <th style="width:120px;">Rating</th>
          <th>Comment</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($feedbacks->num_rows === 0): ?>
          <tr>
            <td colspan="3" style="text-align:center;opacity:.8;">No comments to display.</td>
          </tr>
        <?php else: ?>
          <?php while($fb = $feedbacks->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($fb['date']) ?></td>
              <td><?= stars($fb['rating']) ?></td>
              <td><?= nl2br(htmlspecialchars($fb['comments'] ?? '')) ?></td>
            </tr>
          <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <footer class="site-footer">
    <div class="footer-brand">
      <img src="logo.png" alt="Edulearn Logo" class="footer-logo">
      <h2 class="footer-name"><span style="color:rgb(167, 203, 251);">EduLearn</span></h2>
    </div>
    <p class="footer-copy">&copy; 2025 Edulearn. All rights reserved.</p>
  </footer>
</body>
</html>
