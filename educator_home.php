<?php
session_start();
if (!isset($_SESSION['userID']) || ($_SESSION['userType'] ?? '') !== 'educator') {
  header('Location: login.php'); exit;
}
require 'db_connection.php';

$educatorID = (int)$_SESSION['userID'];

// معلومات الهيدر
$first = htmlspecialchars($_SESSION['firstName'] ?? '');
$last  = htmlspecialchars($_SESSION['lastName'] ?? '');
$email = htmlspecialchars($_SESSION['emailAddress'] ?? '');
$photo = $_SESSION['photoFileName'] ?? 'images/default.png';
if (!preg_match('#^(uploads/|images/|/|https?://)#i', $photo)) $photo = 'uploads/' . $photo;
if (!is_file($photo)) $photo = 'images/default.png';

// كويزات المعلّم
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

// التوصيات المعلّقة
$pending = $conn->query("
  SELECT rq.*, t.topicName,
         CONCAT(u.firstName,' ',u.lastName) AS learnerName,
         u.photoFileName
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
  <style>
    /* ترتيب خلية الصورة في جدول المعلومات */
    .photo-cell{ text-align:left !important; }
    .avatar{ width:160px; height:160px; border-radius:50%; object-fit:cover; display:block; }

    /* جدول التوصيات */
    #recoTable td{ vertical-align: top; }
    .learner-avatar{
      width:42px; height:42px; border-radius:50%; object-fit:cover; vertical-align:middle;
    }
    .q-wrap{ display:flex; gap:12px; align-items:flex-start; }
    .q-image{
      width:140px; height:110px; object-fit:cover; border-radius:8px; display:block; flex:0 0 auto;
    }
    .learner-name{ margin-left:6px; vertical-align:middle; display:inline-block; }
    
    /* جدول موحّد بإطار غامق وزوايا دائرية */
.dash-table{
  width:100%;
  border-collapse:separate;
  border-spacing:0;
  background:#0c2236;              /* خلفية جسم الجدول */
  border:1px solid rgba(0,0,0,.55); /* الإطار الغامق */
  border-radius:12px;
  overflow:hidden;                  /* عشان رأس الجدول يمسك الزوايا */
}

/* رأس الجدول */
.dash-table thead th{
  background:#123a5e;               /* أزرق أغمق للرأس */
  color:#fff;
  text-align:left;
  font-weight:700;
  padding:14px 16px;
  border-bottom:1px solid rgba(0,0,0,.55);
}

/* خلايا الجسم */
.dash-table th,
.dash-table td{
  padding:14px 16px;
  border-bottom:1px solid rgba(0,0,0,.35); /* يفصل الصفوف بخط غامق */
  vertical-align:middle;
}

/* آخر صف بدون خط سفلي */
.dash-table tbody tr:last-child td{
  border-bottom:none;
}

/* تأثير خفيف عند المرور */
.dash-table tbody tr:hover{
  background:rgba(255,255,255,.035);
}

/* أعمدة مساعدة لو احتجتها */
.col-count{ width:120px; text-align:center; }

/* صورة شخصية صغيرة + اسم داخل الخلية (إن استخدمتيها) */
.cell-person{ display:flex; align-items:center; gap:10px; }
.avatar-36{ width:36px; height:36px; border-radius:50%; object-fit:cover; }

/* كتلة السؤال مع الصورة المصغّرة */
.qcell{ display:flex; gap:12px; align-items:flex-start; }
.qthumb{ width:130px; height:90px; border-radius:8px; object-fit:cover; display:block; }
.options{ margin-top:6px; padding-left:18px; }

  </style>
</head>
<body>
  <div class="header">
    <div class="logo"><img src="images/logo.png" alt="Logo"></div>
    <h3>Welcome, <?= $first ?></h3>
    <a href="logout.php">Sign out</a>
  </div>

  <!-- معلومات المعلّم -->
  <div class="form-container" style="max-width: 800px;">
    <h3>Your Info</h3>
    <table>
      <tbody>
        <tr>
          <th style="width:180px;">Profile</th>
<td style="text-align:left;">
  <img src="<?= htmlspecialchars($photo) ?>" alt="Profile" class="avatar" style="display:block; margin-left:0;">
</td>

        </tr>
        <tr><th>First name</th><td><?= $first ?></td></tr>
        <tr><th>Last name</th><td><?= $last ?></td></tr>
        <tr><th>Email</th><td><?= $email ?></td></tr>
      </tbody>
    </table>
  </div>

  <!-- كويزات المعلّم -->
  <div class="quiz-container">
<div style="display:flex; justify-content:space-between; align-items:center;">
  <h2>Your Quizzes</h2>
  <a href="create_quiz.php" class="btn-success" style="font-size:15px;">+ Create New Quiz</a>
</div>
    <table id="myQuizzes" class="dash-table">
      <thead>
        <tr>
          <th>Topic</th>
          <th>Number of Questions</th>
          <th>Stats</th>
          <th>Feedback</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$quizzes || $quizzes->num_rows === 0): ?>
        <tr><td colspan="4" style="text-align:center;opacity:.8;">No quizzes yet.</td></tr>
      <?php else: ?>
        <?php while($q = $quizzes->fetch_assoc()): ?>
          <tr>
            <td>
              <a href="quiz_page.php?quizID=<?= (int)$q['quizID'] ?>">
                <?= htmlspecialchars($q['topicName']) ?>
              </a>
            </td>
            <td><?= (int)$q['qCount'] ?></td>
            <td>
              <?php if ((int)$q['takenCount'] > 0): ?>
                <?= (int)$q['takenCount'] ?> takers • Avg <?= $q['avgScore'] ?: 0 ?>%
              <?php else: ?>
                quiz not taken yet
              <?php endif; ?>
            </td>
            <td>
              <?php if ((int)$q['fbCount'] > 0): ?>
                <?= $q['avgRating'] ?: 0 ?>/5 •
                <a href="comments.php?quizID=<?= (int)$q['quizID'] ?>">comments</a>
              <?php else: ?>
                no feedback yet
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- توصيات الأسئلة -->
  <div class="quiz-container">
    <h2>Question Recommendations</h2>
    <table id="recoTable" class="dash-table">
      <thead>
        <tr>
          <th>Topic</th>
          <th>Learner</th>
          <th>Question</th>
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
              <?php
                $lp = $r['photoFileName'] ?: 'images/default.png';
                if (!preg_match('#^(uploads/|images/|/|https?://)#i', $lp)) $lp = 'uploads/' . $lp;
                if (!is_file($lp)) $lp = 'images/default.png';
              ?>
              <img src="<?= htmlspecialchars($lp) ?>" class="learner-avatar" alt="Learner">
              <span class="learner-name"><?= htmlspecialchars($r['learnerName']) ?></span>
            </td>
            <td>
              <div class="q-wrap">
                <?php if (!empty($r['questionFigureFileName'])): ?>
                  <img src="<?= htmlspecialchars($r['questionFigureFileName']) ?>" alt="Question Image" class="q-image">
                <?php endif; ?>
                <div>
                  <?= nl2br(htmlspecialchars($r['question'])) ?>
                  <ul class="options" style="margin-top:6px;">
                    <li>A) <?= htmlspecialchars($r['answerA']) ?></li>
                    <li>B) <?= htmlspecialchars($r['answerB']) ?></li>
                    <li>C) <?= htmlspecialchars($r['answerC']) ?></li>
                    <li>D) <?= htmlspecialchars($r['answerD']) ?></li>
                  </ul>
                </div>
              </div>
            </td>
            <td>
              <form method="post" action="review_recommended.php">
                <input type="hidden" name="recID" value="<?= (int)$r['id'] ?>">
                <textarea name="comment" class="input-field" placeholder="Comment (optional)"></textarea>
                <div class="action-buttons" style="margin-top:8px;">
                  <button class="action-btn edit-btn"   name="action" value="approve"    type="submit">Approve</button>
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
