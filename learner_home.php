<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['userType'] ?? '') !== 'educator') {
  header('Location: login.php'); exit;
}
require 'db_connection.php';

$educatorID = (int)$_SESSION['userID'];

$first  = htmlspecialchars($_SESSION['firstName'] ?? '');
$last   = htmlspecialchars($_SESSION['lastName'] ?? '');
$email  = htmlspecialchars($_SESSION['emailAddress'] ?? '');
$photo  = $_SESSION['photoFileName'] ?? 'images/default.png';
if (!preg_match('#^(uploads/|images/|/|https?://)#i', $photo)) $photo = 'uploads/'.$photo;
if (!is_file($photo)) $photo = 'images/default.png';

$quizzes = $conn->query("
  SELECT 
    Q.id AS quizID,
    T.topicName,
    (SELECT COUNT(*) FROM QuizQuestion QQ WHERE QQ.quizID=Q.id) AS qCount,
    (SELECT COUNT(*) FROM TakenQuiz TK WHERE TK.quizID=Q.id) AS takenCount,
    (SELECT ROUND(AVG(TK.score),1) FROM TakenQuiz TK WHERE TK.quizID=Q.id) AS avgScore,
    (SELECT ROUND(AVG(QF.rating),1) FROM QuizFeedback QF WHERE QF.quizID=Q.id) AS avgRating,
    (SELECT COUNT(*) FROM QuizFeedback QF WHERE QF.quizID=Q.id) AS fbCount
  FROM Quiz Q 
  JOIN Topic T ON T.id=Q.topicID
  WHERE Q.educatorID=$educatorID
  ORDER BY T.topicName
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Educator Homepage</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="header">
    <div class="logo"><img src="images/logo.png" alt="Logo"></div>
    <h3>Welcome, <span id="educatorName"><?= $first ?></span></h3>
    <a href="logout.php">Sign out</a>
  </div>

  <div class="form-container" style="max-width: 800px;">
    <h3>Your Info</h3>
    <table>
      <tbody>
        <tr>
          <th style="width:180px;">Profile</th>
          <td><img src="<?= htmlspecialchars($photo) ?>" alt="Profile" class="avatar" style="margin:0;"></td>
        </tr>
        <tr><th>First name</th><td><?= $first ?></td></tr>
        <tr><th>Last name</th><td><?= $last ?></td></tr>
        <tr><th>Email</th><td><?= $email ?></td></tr>
      </tbody>
    </table>
  </div>

  <!-- ✅ نفس تصميم جدول Your Quizzes -->
  <div class="quiz-container">
    <h2>Your Quizzes</h2>
    <table id="myQuizzes">
      <thead>
        <tr>
          <th>Topic</th>
          <th># Questions</th>
          <th>Stats</th>
          <th>Feedback</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($quizzes->num_rows === 0): ?>
        <tr><td colspan="5" style="text-align:center;opacity:.8;">No quizzes yet.</td></tr>
      <?php else: ?>
        <?php while($q = $quizzes->fetch_assoc()): ?>
          <tr>
            <td>
              <a href="quiz_page.php?quizID=<?= (int)$q['quizID'] ?>">
                <?= htmlspecialchars($q['topicName']) ?>
              </a>
            </td>
            <td class="qcount"><?= (int)$q['qCount'] ?></td>
            <td class="stat">
              <?php if ((int)$q['takenCount'] > 0): ?>
                <?= (int)$q['takenCount'] ?> takers • Avg <?= $q['avgScore'] ?: 0 ?>%
              <?php else: ?>
                quiz not taken yet
              <?php endif; ?>
            </td>
            <td class="fb">
              <?php if ((int)$q['fbCount'] > 0): ?>
                <?= $q['avgRating'] ?: 0 ?>/5 • 
                <a href="comments.php?quizID=<?= (int)$q['quizID'] ?>">comments</a>
              <?php else: ?>
                no feedback yet
              <?php endif; ?>
            </td>
            <td>
              <a class="action-btn edit-btn" href="quiz_page.php?quizID=<?= (int)$q['quizID'] ?>">View</a>
              <a class="action-btn add-btn" href="add_question.php?quizID=<?= (int)$q['quizID'] ?>">Add</a>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php endif; ?>
      </tbody>
    </table>
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
