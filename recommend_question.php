<?php
session_start();
if (!isset($_SESSION['userID']) || $_SESSION['userType'] !== 'learner') {
  header('Location: login.php'); exit;
}
require 'db_connection.php';

$learnerID = (int)$_SESSION['userID'];
$success = $error = "";

// معالجة إرسال النموذج (حفظ السؤال في قاعدة البيانات)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $topicID    = (int)($_POST['topicID'] ?? 0);
  $educatorID = (int)($_POST['educatorID'] ?? 0);
  $question   = trim($_POST['question'] ?? '');
  $a          = trim($_POST['answerA'] ?? '');
  $b          = trim($_POST['answerB'] ?? '');
  $c          = trim($_POST['answerC'] ?? '');
  $d          = trim($_POST['answerD'] ?? '');
  $correctNum = (int)($_POST['correct'] ?? 0); 
  $correctMap = [1=>'A', 2=>'B', 3=>'C', 4=>'D'];
  $correct    = $correctMap[$correctNum] ?? '';

  if (!$topicID || !$educatorID || !$question || !$a || !$b || !$c || !$d || !$correct) {
    $error = "Please fill all required fields.";
  } else {
      // البحث عن كويز موجود لهذا المعلم والموضوع لربط السؤال به
      $quiz = $conn->prepare("SELECT id FROM Quiz WHERE educatorID=? AND topicID=?");
      $quiz->bind_param("ii", $educatorID, $topicID);
      $quiz->execute();
      $quizRes = $quiz->get_result()->fetch_assoc();
      $quiz->close();

      if (!$quizRes) {
        $error = "No quiz found for this educator/topic combination.";
      } else {
        $quizID = (int)$quizRes['id'];
        
        // معالجة الصورة (اختياري)
        $figureFileName = null;
        if (!empty($_FILES['figure']['name']) && $_FILES['figure']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['figure']['name'], PATHINFO_EXTENSION);
            $safe = uniqid() . "_" . preg_replace("/[^a-zA-Z0-9._-]/","", $_FILES['figure']['name']);
            if (move_uploaded_file($_FILES['figure']['tmp_name'], "uploads/" . $safe)) {
                $figureFileName = $safe; 
            }
        }

        // إدخال السؤال المقترح
        $stmt = $conn->prepare("
            INSERT INTO RecommendedQuestion
            (quizID, learnerID, question, questionFigureFileName, answerA, answerB, answerC, answerD, correctAnswer, status)
            VALUES (?,?,?,?,?,?,?,?,?, 'pending')
        ");
        $stmt->bind_param("iisssssss", $quizID, $learnerID, $question, $figureFileName, $a, $b, $c, $d, $correct);
        
        if ($stmt->execute()) {
            header("Location: learner_home.php"); exit;
        } else {
            $error = "Database error: " . $conn->error;
        }
      }
  }
}

// جلب قائمة المواضيع فقط (المعلمين سيتم جلبهم بـ AJAX)
$topicsRes = $conn->query("SELECT id, topicName FROM Topic ORDER BY topicName");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Recommend Question</title>
  <link rel="stylesheet" href="style.css" />
  
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
  <div class="header">
    <div class="logo"><img src="images/logo.png" alt="Logo"></div>
    <h3>Recommend a New Question</h3>
    <a href="learner_home.php">Back to Homepage</a>
  </div>

  <div class="form-container" style="max-width:700px;">
    <?php if ($error): ?>
      <div class="alert error" style="color:red; margin-bottom:10px; text-align:center;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      
      <div class="form-row">
        <label for="topic">Topic:</label>
        <select name="topicID" id="topic" class="input-field" required>
          <option value="">-- Select topic --</option>
          <?php while($row = $topicsRes->fetch_assoc()): ?>
            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['topicName']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="form-row">
        <label for="educator">Educator:</label>
        <select name="educatorID" id="educator" class="input-field" required>
          <option value="">-- First Select a Topic --</option>
        </select>
      </div>

      <div class="form-row">
        <label>Question Text:</label>
        <textarea name="question" class="input-field" rows="3" required></textarea>
      </div>
      <div class="form-row">
        <label>Choice A:</label> <input name="answerA" type="text" class="input-field" required>
      </div>
      <div class="form-row">
        <label>Choice B:</label> <input name="answerB" type="text" class="input-field" required>
      </div>
      <div class="form-row">
        <label>Choice C:</label> <input name="answerC" type="text" class="input-field" required>
      </div>
      <div class="form-row">
        <label>Choice D:</label> <input name="answerD" type="text" class="input-field" required>
      </div>
      <div class="form-row">
        <label>Correct Answer:</label>
        <select name="correct" class="input-field" required>
          <option value="1">Choice A</option>
          <option value="2">Choice B</option>
          <option value="3">Choice C</option>
          <option value="4">Choice D</option>
        </select>
      </div>
      <div class="form-row">
        <label>Figure (Optional):</label>
        <input name="figure" type="file" class="input-field">
      </div>

      <div class="form-row">
        <button type="submit" class="btn-primary">Submit Question</button>
      </div>
    </form>
  </div>

  <script>
  $(document).ready(function() {
      // استماع لحدث التغيير في قائمة المواضيع
      $('#topic').change(function() {
          var selectedTopicID = $(this).val();
          var educatorMenu = $('#educator');

          // تفريغ قائمة المعلمين الحالية
          educatorMenu.empty();
          educatorMenu.append('<option value="">Loading...</option>');

          if (selectedTopicID) {
              // إرسال طلب AJAX إلى ملف get_educators.php
              $.ajax({
                  url: 'get_educators.php',
                  type: 'GET',
                  data: { topicID: selectedTopicID },
                  dataType: 'json', // نتوقع استجابة بصيغة JSON
                  success: function(response) {
                      // تحديث القائمة بناءً على البيانات المسترجعة
                      educatorMenu.empty();
                      educatorMenu.append('<option value="">-- Select Educator --</option>');
                      
                      if (response.length > 0) {
                          $.each(response, function(index, educator) {
                              educatorMenu.append(
                                  $('<option></option>').val(educator.id).html(educator.firstName + ' ' + educator.lastName)
                              );
                          });
                      } else {
                          educatorMenu.append('<option value="">No educators found</option>');
                      }
                  },
                  error: function() {
                      educatorMenu.empty();
                      educatorMenu.append('<option value="">Error fetching data</option>');
                  }
              });
          } else {
              // إعادة القائمة لوضعها الافتراضي
              educatorMenu.empty();
              educatorMenu.append('<option value="">-- First Select a Topic --</option>');
          }
      });
  });
  </script>

  <footer class="site-footer">
    <div class="footer-brand">
      <img src="images/logo.png" alt="Logo" class="footer-logo">
      <h2 class="footer-name">EduLearn</h2>
    </div>
    <p class="footer-copy">&copy; 2025 EduLearn. All rights reserved.</p>
  </footer>
</body>
</html>