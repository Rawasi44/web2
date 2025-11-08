<?php
// take_quiz.php (النقطة 12)
session_start();
include 'db_connection.php'; // للاتصال بقاعدة البيانات

// --- 1. التأكد أن المستخدم "متعلم" ---
if (!isset($_SESSION['userID']) || $_SESSION['userType'] !== 'learner') {
    header('Location: login.php');
    exit;
}

// --- 2. استقبال معرّف الكويز ---
$quizID = isset($_GET['quizID']) ? intval($_GET['quizID']) : 0;
if ($quizID == 0) {
    header('Location: learner_home.php');
    exit;
}

// --- 3. جلب بيانات الكويز (الموضوع + المعلم) ---
$stmt = $conn->prepare("
    SELECT 
        T.topicName, 
        U.firstName, 
        U.lastName, 
        U.photoFileName 
    FROM quiz Q
    JOIN user U ON Q.educatorID = U.id
    JOIN topic T ON Q.topicID = T.id
    WHERE Q.id = ?
");
$stmt->bind_param("i", $quizID);
$stmt->execute();
$quizInfo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$quizInfo) {
    header('Location: learner_home.php');
    exit;
}

$raw = $quizInfo['photoFileName'] ?? '';

// المسار الافتراضي
$photoPath = 'images/default.png';

if ($raw) {
  // لو القيمة أصلاً فيها مسار (uploads/ أو images/ أو رابط http)
  if (preg_match('#^(uploads/|images/|/|https?://)#i', $raw)) {
    $candidate = $raw;
  } else {
    // اسم ملف فقط → نضيف مجلد الرفع
    $candidate = 'uploads/' . $raw;
  }

  // تأكد أن الملف موجود فعلاً قبل العرض
  if (is_file($candidate)) {
    $photoPath = $candidate;
  }
}


// --- 4. جلب واختيار الأسئلة ---
$stmt = $conn->prepare("SELECT * FROM quizquestion WHERE quizID = ?");
$stmt->bind_param("i", $quizID);
$stmt->execute();
$allQuestions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$selectedQuestions = [];
$questionCount = count($allQuestions);

if ($questionCount > 0) {
    if ($questionCount <= 5) {
        $selectedQuestions = $allQuestions;
    } else {
        shuffle($allQuestions);
        $selectedQuestions = array_slice($allQuestions, 0, 5);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Take Quiz - <?php echo htmlspecialchars($quizInfo['topicName']); ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="quiz-box">
    <h2>Quiz in <?php echo htmlspecialchars($quizInfo['topicName']); ?></h2>

    <div id="educator-info">
      <img src="<?php echo htmlspecialchars($photoPath); ?>" alt="Educator Photo" class="teacher">
      <span><?php echo htmlspecialchars($quizInfo['firstName'] . ' ' . $quizInfo['lastName']); ?></span>
    </div>

    <form action="quiz_score_feedback.php" method="POST">
      <input type="hidden" name="quizID" value="<?php echo $quizID; ?>">
      <h3>Quiz Questions:</h3>
      <?php if (empty($selectedQuestions)): ?>
        <p>This quiz has no questions yet. Please check back later.</p>
      <?php else: ?>
        <?php foreach ($selectedQuestions as $index => $q): ?>
          <input type="hidden" name="questionIDs[]" value="<?php echo $q['id']; ?>">
          <fieldset class="question">
            <legend>Question <?php echo $index + 1; ?></legend>
            <?php if (!empty($q['questionFigureFileName'])): ?>
              <img src="uploads/<?php echo htmlspecialchars($q['questionFigureFileName']); ?>" alt="Question Figure" class="q-image">
            <?php endif; ?>
            <p><?php echo htmlspecialchars($q['question']); ?></p>
            <label><input type="radio" name="answers[<?php echo $q['id']; ?>]" value="A" required> <?php echo htmlspecialchars($q['answerA']); ?></label>
            <label><input type="radio" name="answers[<?php echo $q['id']; ?>]" value="B"> <?php echo htmlspecialchars($q['answerB']); ?></label>
            <label><input type="radio" name="answers[<?php echo $q['id']; ?>]" value="C"> <?php echo htmlspecialchars($q['answerC']); ?></label>
            <label><input type="radio" name="answers[<?php echo $q['id']; ?>]" value="D"> <?php echo htmlspecialchars($q['answerD']); ?></label>
          </fieldset>
        <?php endforeach; ?>
        <button type="submit" class="btn-primary done-btn">Done</button>
      <?php endif; ?>
    </form>
  </div>
</body>
</html>
