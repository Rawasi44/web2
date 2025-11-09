<?php
session_start();
if (!isset($_SESSION['userID']) || $_SESSION['userType'] !== 'learner') {
  header('Location: login.php'); exit;
}
require 'db_connection.php';

$learnerID = (int)$_SESSION['userID'];


$topics = $conn->query("SELECT id, topicName FROM Topic ORDER BY topicName");


$topicID = isset($_POST['topicID']) ? (int)$_POST['topicID'] : 0;


$sql = "
  SELECT 
    Q.id AS quizID,
    T.topicName,
    U.firstName, U.lastName,
    (SELECT COUNT(*) FROM QuizQuestion QQ WHERE QQ.quizID = Q.id) AS qCount
  FROM Quiz Q
  JOIN Topic T ON T.id = Q.topicID
  JOIN User  U ON U.id = Q.educatorID
";
if ($topicID) { $sql .= " WHERE Q.topicID = $topicID "; }
$sql .= " ORDER BY T.topicName, U.firstName ";
$quizzes = $conn->query($sql);


$myReco = $conn->query("
  SELECT 
    RQ.*,
    T.topicName,
    CONCAT(E.firstName,' ',E.lastName) AS educatorName
  FROM RecommendedQuestion RQ
  JOIN Quiz Q   ON Q.id = RQ.quizID
  JOIN Topic T  ON T.id = Q.topicID
  JOIN User  E  ON E.id = Q.educatorID
  WHERE RQ.learnerID = $learnerID
  ORDER BY RQ.id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Learner Homepage</title>
  <link rel="stylesheet" href="style.css" />
  <script src="script.js" defer></script>
</head>
<body>
  <div class="header">
    <div class="logo"><img src="images/logo.png" alt="Logo"></div>
    <h3>Welcome, <span id="learnerName"><?php echo htmlspecialchars($_SESSION['firstName'] ?? 'Learner'); ?></span></h3>
    <a href="logout.php">Sign out</a>
  </div>

  <div class="form-container" style="max-width: 800px;">
    <h3>Your Info</h3>
    <table>
      <tbody>
        <tr>
          <th style="width:180px;">Profile</th>
          <td><?php

$raw = $_SESSION['photoFileName'] ?? '';

$photoPath = 'images/default.png';

if ($raw) {
 
  if (preg_match('#^(uploads/|images/|/|https?://)#i', $raw)) {
    $candidate = $raw;
  } else {
   
    $candidate = 'uploads/' . $raw;
  }

  if (is_file($candidate)) {
    $photoPath = $candidate;
  }
}
?>
<img src="<?= htmlspecialchars($photoPath) ?>" alt="Profile" class="avatar" style="margin:0;">
</td>
  </tr>     
        <tr><th>First name</th><td><?php echo htmlspecialchars($_SESSION['firstName'] ?? ''); ?></td></tr>
        <tr><th>Last name</th><td><?php echo htmlspecialchars($_SESSION['lastName'] ?? ''); ?></td></tr>
        <tr><th>Email</th><td><?php echo htmlspecialchars($_SESSION['emailAddress'] ?? ''); ?></td></tr>
      </tbody>
    </table>
  </div>

  <div class="quiz-container">
    <div class="quiz-header">
      <h2>Available Quizzes</h2>
      <div>
        <form method="post" style="display:flex;gap:8px;align-items:center;">
          <select name="topicID" class="input-field" style="max-width:240px;">
            <option value="0">All topics</option>
            <?php while($t = $topics->fetch_assoc()): ?>
              <option value="<?= $t['id']; ?>" <?= $topicID==$t['id']?'selected':''; ?>>
                <?= htmlspecialchars($t['topicName']); ?>
              </option>
            <?php endwhile; ?>
          </select>
          <button class="btn-primary" type="submit">Filter</button>
        </form>
      </div>
    </div>

    <table id="quizzesTable">
      <thead>
        <tr>
          <th>Topic</th>
          <th>Educator</th>
          <th># Questions</th>
          <th>Take</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($quizzes->num_rows === 0): ?>
          <tr><td colspan="4" style="text-align:center;opacity:.8;">No quizzes found.</td></tr>
        <?php else: ?>
          <?php while($q = $quizzes->fetch_assoc()): ?>
            <tr data-topic="<?= htmlspecialchars($q['topicName']); ?>">
              <td><?= htmlspecialchars($q['topicName']); ?></td>
              <td>
                <img src="default.png" class="avatar" style="width:40px;height:40px;display:inline-block;vertical-align:middle;">
                <span style="margin-left:6px;"><?= htmlspecialchars($q['firstName'].' '.$q['lastName']); ?></span>
              </td>
              <td><?= (int)$q['qCount']; ?></td>
              <td>
                <?php if ((int)$q['qCount'] > 0): ?>
                  <a class="btn-success" href="take_quiz.php?quizID=<?= $q['quizID']; ?>">Take Quiz</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="quiz-container">
    <h2>Your Recommended Questions</h2>
    <table>
      <thead>
        <tr>
          <th>Topic</th>
          <th>Educator</th>
          <th>Question (with options)</th>
          <th>Status</th>
          <th>Educator Comment</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($myReco->num_rows === 0): ?>
          <tr><td colspan="5" style="text-align:center;opacity:.8;">You haven't recommended any questions yet.</td></tr>
        <?php else: ?>
          <?php while($r = $myReco->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($r['topicName']); ?></td>
              <td>
                <img src="default.png" class="avatar" style="width:40px;height:40px;display:inline-block;vertical-align:middle;">
                <span style="margin-left:6px;"><?= htmlspecialchars($r['educatorName']); ?></span>
              </td>
              <td>
                <?php if (!empty($r['questionFigureFileName'])): ?>
                  <img src="<?= htmlspecialchars($r['questionFigureFileName']); ?>" alt="Question figure" class="q-image">
                <?php endif; ?>
                <?= nl2br(htmlspecialchars($r['question'])); ?>
                <ul class="options">
                  <li class="option"><?= htmlspecialchars($r['answerA']); ?></li>
                  <li class="option"><?= htmlspecialchars($r['answerB']); ?></li>
                  <li class="option"><?= htmlspecialchars($r['answerC']); ?></li>
                  <li class="option"><?= htmlspecialchars($r['answerD']); ?></li>
                </ul>
              </td>
              <td><strong><?= htmlspecialchars(ucfirst($r['status'])); ?></strong></td>
              <td><?= !empty($r['comments']) ? nl2br(htmlspecialchars($r['comments'])) : '—'; ?></td>
            </tr>
          <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div style="margin:20px;">
    <a class="btn-primary" href="recommend_question.php">Recommend a New Question</a>
  </div>

</body>
<footer class="site-footer">
  <div class="footer-brand">
    <img src="images/logo.png" alt="Edulearn Logo" class="footer-logo">
    <h2 class="footer-name"><span style="color:rgb(167, 203, 251);">EduLearn</span></h2>
  </div>
  <p class="footer-copy">&copy; 2025 Edulearn. All rights reserved.</p>
</footer>
</html>
