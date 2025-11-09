<?php
// ===== DEBUG =====
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// =================

session_start();
if (!isset($_SESSION['userID']) || ($_SESSION['userType'] ?? '') !== 'educator') {
  header('Location: login.php'); exit;
}

require 'db_connection.php'; // يفترض يعرّف $conn كـ mysqli

$educatorID = (int)($_SESSION['userID'] ?? 0);
if ($educatorID <= 0) { die('Invalid educator session.'); }

// نجلب المواضيع للفورم
$topics = $conn->query("SELECT id, topicName 
FROM topic
ORDER BY FIELD(topicName, 'HTML & CSS', 'JavaScript', 'Databases');
");
if (!$topics) { die("Topics query error: " . $conn->error); }

// رسالة خطأ/نجاح للعرض داخل الصفحة
$msg = '';

// داخل create_quiz.php مكان معالجة POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $topicID = (int)($_POST['topicID'] ?? 0);

  if ($topicID <= 0) {
    $msg = "Please choose a topic.";
  } else {
    // هل يوجد كويز مسبقاً لهذا المعلّم وهذا الموضوع؟
    $chk = $conn->prepare("SELECT id FROM quiz WHERE educatorID=? AND topicID=? LIMIT 1");
    $chk->bind_param("ii", $educatorID, $topicID);
    $chk->execute();
    $exists = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($exists) {
      // موجود: وجّهيه لإدارة/إضافة أسئلة لهذا الكويز
      header("Location: add_question.php?quizID=".(int)$exists['id']);
      exit;
    }

    // غير موجود: أنشيء الكويز
    $stmt = $conn->prepare("INSERT INTO quiz (topicID, educatorID) VALUES (?, ?)");
    if (!$stmt) {
      $msg = "Prepare failed: " . $conn->error;
    } else {
      $stmt->bind_param("ii", $topicID, $educatorID);
      if ($stmt->execute()) {
        header("Location: educator_home.php");
        exit;
      } else {
        $msg = "Insert failed: " . $stmt->error . " | SQLSTATE: " . $stmt->sqlstate;
      }
      $stmt->close();
    }
  }
}?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Quiz</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .notice {margin-bottom:12px; padding:10px; border-radius:8px; background:#1b2a41; color:#fff;}
    .notice.error {background:#5b1f1f;}
  </style>
</head>
<body>
  <div class="header">
    <div class="logo"><img src="images/logo.png" alt="Logo"></div>
    <h3>Create a New Quiz</h3>
    <a href="educator_home.php">Back</a>
  </div>

  <div class="form-container" style="max-width:480px;">
    <?php if ($msg): ?>
      <div class="notice error"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="post">
      <label for="topicID">Choose Topic</label>
      <select class="input-field" id="topicID" name="topicID" required>
        <option value="">-- Select Topic --</option>
        <?php
          // أعد تحميل المؤشر لأننا قرأناه فوق محتمل
          if ($topics->num_rows === 0) {
            echo '<option value="">(No topics)</option>';
          } else {
            // لو المؤشر استُهلك قبل، أعيدي تنفيذ الاستعلام
            $topics->data_seek(0);
            while ($t = $topics->fetch_assoc()):
        ?>
          <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['topicName']) ?></option>
        <?php endwhile; } ?>
      </select>

      <button type="submit" class="btn-success" style="width:100%; margin-top:12px;">Create Quiz</button>
    </form>
  </div>
</body>
</html>
