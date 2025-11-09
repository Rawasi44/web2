<?php
// learner_home.php
session_start();
if (!isset($_SESSION['userID']) || ($_SESSION['userType'] ?? '') !== 'learner') {
  header('Location: login.php'); exit;
}
require 'db_connection.php';

$learnerID = (int)$_SESSION['userID'];
// -------- بيانات المتعلّم لجزء "Your Info" --------
$first = $_SESSION['firstName']    ?? '';
$last  = $_SESSION['lastName']     ?? '';
$email = $_SESSION['emailAddress'] ?? '';   // انتبهي للاسم: emailAddress
$photo = $_SESSION['photoFileName'] ?? '';

// لو فيه قيم ناقصة، كمّلها من قاعدة البيانات
if ($first === '' || $last === '' || $email === '' || $photo === '') {
    $stmt = $conn->prepare("
        SELECT firstName, lastName, emailAddress, photoFileName
        FROM user
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $learnerID);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($u) {
        $first = $first ?: $u['firstName'];
        $last  = $last  ?: $u['lastName'];
        $email = $email ?: $u['emailAddress'];
        $photo = $photo ?: ($u['photoFileName'] ?? '');
    }
}

// جهّزي مسار الصورة (uploads/images) وافتراضي عند عدم وجود الملف
function safe_photo_local($p) {
    $p = $p ?: 'images/default.png';
    if (!preg_match('#^(uploads/|images/|/|https?://)#i', $p)) $p = 'uploads/'.$p;
    if (!is_file($p)) $p = 'images/default.png';
    return $p;
}
$photo = safe_photo_local($photo);

// ترميز آمن للعرض
$first = htmlspecialchars($first, ENT_QUOTES, 'UTF-8');
$last  = htmlspecialchars($last,  ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$photo = htmlspecialchars($photo, ENT_QUOTES, 'UTF-8');

// ---------- جلب المواضيع لقائمة الفلترة ----------
$topicsRes = $conn->query("SELECT id, topicName FROM topic ORDER BY topicName");

// قيمة الفلتر (GET أو POST لنفس الصفحة)
$selectedTopicID = (int)($_REQUEST['topicID'] ?? 0);

// ---------- جلب الكويزات المعروضة للمتعلّم ----------
$sql = "
  SELECT
    q.id            AS quizID,
    t.topicName     AS topic,
    u.firstName,
    u.lastName,
    u.photoFileName AS educatorPhoto,
    (SELECT COUNT(*) FROM quizquestion qq WHERE qq.quizID = q.id)  AS qCount,
    (SELECT COUNT(*) FROM takenquiz tk WHERE tk.quizID = q.id)     AS takenCount,
    (SELECT ROUND(AVG(qf.rating),1) FROM quizfeedback qf WHERE qf.quizID = q.id) AS avgRating
  FROM quiz q
  JOIN topic t ON t.id = q.topicID
  JOIN user  u ON u.id = q.educatorID
";
if ($selectedTopicID > 0) {
  $sql .= " WHERE q.topicID = $selectedTopicID ";
}
$sql .= " ORDER BY t.topicName, u.firstName, u.lastName";

$quizzes = $conn->query($sql);

// ---------- جلب الأسئلة المُقترحة من هذا المتعلّم ----------
$reco = $conn->query("
  SELECT
    rq.*,
    t.topicName,
    CONCAT(ue.firstName,' ',ue.lastName) AS educatorName,
    ue.photoFileName AS educatorPhoto
  FROM recommendedquestion rq
  JOIN quiz  q  ON q.id = rq.quizID
  JOIN topic t  ON t.id = q.topicID
  JOIN user  ue ON ue.id = q.educatorID
  WHERE rq.learnerID = $learnerID
  ORDER BY rq.createdAt DESC
");

// دالة تحضير مسار صورة (uploads / images / default)
function safe_photo($path) {
  $p = $path ?: 'images/default.png';
  if (!preg_match('#^(uploads/|images/|/|https?://)#i', $p)) $p = 'uploads/'.$p;
  if (!is_file($p)) $p = 'images/default.png';
  return $p;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Learner Homepage</title>
  <link rel="stylesheet" href="style.css" />
  <style>
    /* تنسيق عام للجدولين */
    table.styled{ width:100%; border-collapse:separate; border-spacing:0; }
    table.styled th, table.styled td{ padding:14px 16px; border-bottom:1px solid rgba(255,255,255,.08); vertical-align:middle; }
    table.styled thead th{ background:#112945; color:#fff; text-align:left; }
    table.styled tbody tr:hover{ background:rgba(255,255,255,.03); }

    /* أعمدة ثابتة العرض قليلاً */
    .col-topic{ width:220px; }
    .col-count{ width:140px; text-align:center; }
    .col-stats{ width:280px; }
    .col-feedback{ width:220px; }

    /* صورة المعلّم بجانب اسمه */
    .edu-wrap{ display:flex; align-items:center; gap:10px; }
    .edu-avatar{ width:36px; height:36px; border-radius:50%; object-fit:cover; display:inline-block; flex:0 0 36px; }

    /* زرار */
    .btn-primary, .btn-success{ padding:8px 14px; border-radius:8px; text-decoration:none; display:inline-block; font-weight:600; }
    .btn-success{ background:#10d49c; color:#032; }
    .btn-success:empty{ display:none; }

    /* جدول التوصيات */
    .qthumb{ width:130px; height:90px; border-radius:8px; object-fit:cover; display:block; }
    .qbox{ display:flex; gap:12px; align-items:flex-start; }
    .muted{ opacity:.8; }

    /* فورم الفلتر */
    .filter-bar{ display:flex; gap:10px; align-items:center; margin-bottom:14px; }
    .filter-bar select, .filter-bar button{ height:40px; padding:0 10px; }
  </style>
</head>
<body>
  <div class="header">
    <div class="logo"><img src="images/logo.png" alt="Logo"></div>
    <h3>Welcome, <?= htmlspecialchars($_SESSION['firstName'] ?? '') ?></h3>
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
          <img src="<?= $photo ?>" alt="Profile" class="avatar"
               style="display:block; margin-left:0; width:160px; height:160px; border-radius:50%; object-fit:cover;">
        </td>
      </tr>
      <tr><th>First name</th><td><?= $first ?></td></tr>
      <tr><th>Last name</th><td><?= $last ?></td></tr>
      <tr><th>Email</th><td><?= $email ?></td></tr>
    </tbody>
  </table>
</div>

  <!-- الكويزات المتاحة -->
  <div class="quiz-container">
  <h2>Your Quizzes</h2>

  <form class="filter-bar" method="get" action="learner_home.php">
    <label for="topicID" class="muted">Filter by topic:</label>
    <select name="topicID" id="topicID" class="input-field" style="max-width:260px;">
      <option value="0">All topics</option>
      <?php while($t = $topicsRes->fetch_assoc()): ?>
        <option value="<?= (int)$t['id'] ?>" <?= $selectedTopicID===(int)$t['id']?'selected':'' ?>>
          <?= htmlspecialchars($t['topicName']) ?>
        </option>
      <?php endwhile; ?>
    </select>
    <button class="btn-primary" type="submit">Apply</button>
  </form>

  <table class="styled" id="quizzesTable">
    <thead>
      <tr>
        <th class="col-topic">Topic</th>
        <th>Educator</th>
        <th class="col-count"># Questions</th>
        <th class="col-feedback">Take</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$quizzes || $quizzes->num_rows===0): ?>
        <tr><td colspan="4" style="text-align:center;opacity:.8;">No quizzes found.</td></tr>
      <?php else: ?>
        <?php while($q = $quizzes->fetch_assoc()): ?>
          <?php
            $eduPhoto = safe_photo($q['educatorPhoto']);
            $qCount   = (int)$q['qCount'];
          ?>
          <tr>
            <td><?= htmlspecialchars($q['topic']) ?></td>
            <td>
              <div class="edu-wrap">
                <img src="<?= htmlspecialchars($eduPhoto) ?>" class="edu-avatar" alt="Educator">
                <span><?= htmlspecialchars($q['firstName'].' '.$q['lastName']) ?></span>
              </div>
            </td>
            <td style="text-align:center;"><?= $qCount ?></td>
            <td>
              <?php if ($qCount > 0): ?>
                <a class="btn-success" href="take_quiz.php?quizID=<?= (int)$q['quizID'] ?>">Take Quiz</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

  <!-- الأسئلة المقترحة من المتعلّم -->
  <div class="quiz-container">
    <h2>Recommended Questions</h2>
    <table class="styled">
      <thead>
        <tr>
          <th class="col-topic">Topic</th>
          <th>Educator</th>
          <th>Question</th>
          <th class="col-feedback">Status</th>
          <th>Comments</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$reco || $reco->num_rows===0): ?>
          <tr><td colspan="5" style="text-align:center;opacity:.8;">No recommendations.</td></tr>
        <?php else: ?>
          <?php while($r = $reco->fetch_assoc()): ?>
            <?php $eduP = safe_photo($r['educatorPhoto']); ?>
            <tr>
              <td><?= htmlspecialchars($r['topicName']) ?></td>
              <td>
                <div class="edu-wrap">
                  <img src="<?= htmlspecialchars($eduP) ?>" class="edu-avatar" alt="Educator">
                  <span><?= htmlspecialchars($r['educatorName']) ?></span>
                </div>
              </td>
              <td>
                <div class="qbox">
                  <?php if (!empty($r['questionFigureFileName'])): ?>
                    <img class="qthumb" src="<?= htmlspecialchars($r['questionFigureFileName']) ?>" alt="Figure">
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
              <td><?= ucfirst(htmlspecialchars($r['status'])) ?></td>
              <td><?= $r['comments'] ? nl2br(htmlspecialchars($r['comments'])) : '—' ?></td>
            </tr>
          <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <div style="margin-top:14px;">
      <a class="btn-primary" href="recommend_question.php">Recommend a New Question</a>
    </div>
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
