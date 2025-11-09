<?php
session_start();
if (!isset($_SESSION['userID']) || ($_SESSION['userType'] ?? '') !== 'educator') {
  header('Location: login.php'); exit;
}
require 'db_connection.php';

$educatorID = (int)$_SESSION['userID'];

$first  = htmlspecialchars($_SESSION['firstName'] ?? '');
$last   = htmlspecialchars($_SESSION['lastName'] ?? '');
$email  = htmlspecialchars($_SESSION['emailAddress'] ?? '');
$photo  = $_SESSION['photoFileName'] ?? 'images/default.png';


if (!preg_match('#^(uploads/|images/|/|https?://)#i', $photo)) {
  $photo = 'uploads/' . $photo;
}
if (!is_file($photo)) {
  $photo = 'images/default.png';
}

$quizzes = $conn->query("
  SELECT 
    q.id AS quizID,
    t.topicName,
    (SELECT COUNT(*) FROM quizquestion qq WHERE qq.quizID = q.id) AS qCount,
    (SELECT COUNT(*) FROM takenquiz tk WHERE tk.quizID = q.id) AS takenCount,
    (SELECT ROUND(AVG(tk.score), 1) FROM takenquiz tk WHERE tk.quizID = q.id) AS avgScore,
    (SELECT ROUND(AVG(qf.rating), 1) FROM quizfeedback qf WHERE qf.quizID = q.id) AS avgRating,
    (SELECT COUNT(*) FROM quizfeedback qf WHERE qf.quizID = q.id) AS fbCount
  FROM quiz q 
  JOIN topic t ON t.id = q.topicID
  WHERE q.educatorID = $educatorID
  ORDER BY t.topicName
");

$pending = $conn->query("
  SELECT rq.*, t.topicName,
         CONCAT(u.firstName,' ',u.lastName) AS learnerName
  FROM recommendedquestion rq
  JOIN quiz  q ON q.id = rq.quizID
  JOIN topic t ON t.id = q.topicID
  JOIN user  u ON u.id = rq.learnerID
  WHERE q.educatorID = $educatorID AND rq.status = 'pending'
  ORDER BY rq.createdAt DESC
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
    <h3>Welcome, <?= $first ?></h3>
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
      <?php if (!$quizzes || $quizzes->num_rows === 0): ?>
        <tr>
          <td colspan="5" style="text-align:center;opacity:.8;">
            No quizzes yet.
          </td>
        </tr>
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
              <a class="btn-info" href="quiz_page.php?quizID=<?= (int)$q['quizID'] ?>">View</a>
              <a class="btn-success" href="add_question.php?quizID=<?= (int)$q['quizID'] ?>">Add Question</a>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="quiz-container">
    <h2>Recommended Questions (Review)</h2>
    <table id="recoTable">
      <thead>
        <tr>
          <th>Topic</th>
          <th>Learner</th>
          <th>Question (with options)</th>
          <th>Review</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$pending || $pending->num_rows === 0): ?>
        <tr><td colspan="4" style="text-align:center;opacity:.8;">No pending recommendations.</td></tr>
      <?php else: ?>
        <?php while($r = $pending->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($r['topicName']) ?></td>
            <td>
              <img src="images/default.png" class="avatar" style="width:40px;height:40px;vertical-align:middle;">
              <span style="margin-left:6px;"><?= htmlspecialchars($r['learnerName']) ?></span>
            </td>
            <td>
              <?php if (!empty($r['questionFigureFileName'])): ?>
                <img src="uploads/<?= htmlspecialchars($r['questionFigureFileName']) ?>" alt="Question figure" class="q-image">
              <?php endif; ?>
              <?= nl2br(htmlspecialchars($r['question'])) ?>
              <ul class="options" style="margin-top:6px;">
                <li class="option"><?= htmlspecialchars($r['answerA']) ?></li>
                <li class="option"><?= htmlspecialchars($r['answerB']) ?></li>
                <li class="option"><?= htmlspecialchars($r['answerC']) ?></li>
                <li class="option"><?= htmlspecialchars($r['answerD']) ?></li>
              </ul>
            </td>
            <td>
              <form class="reviewForm" method="post" action="review_recommended.php">
                <input type="hidden" name="recID" value="<?= (int)$r['id'] ?>">
                <textarea name="comment" class="input-field" placeholder="Comment to learner (optional)"></textarea>
                <div class="action-buttons" style="margin-top:8px;">
                  <button class="action-btn edit-btn" name="action" value="approve" type="submit">Approve</button>
                  <button class="action-btn delete-btn" name="action" value="disapprove" type="submit">Disapprove</button>
                </div>
              </form>
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

