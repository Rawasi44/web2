<?php
// quiz_score_feedback.php (النقطة 13)
session_start();
include 'db_connection.php'; // للاتصال بقاعدة البيانات

// --- 1. التأكد أن المستخدم "متعلم" ---
if (!isset($_SESSION['userID']) || $_SESSION['userType'] !== 'learner') {
    header('Location: login.php');
    exit;
}

$learnerID = (int)$_SESSION['userID'];
$showScorePage = false; 

// --- 2. الجزء الأول: حفظ الملاحظات ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'submit_feedback') {
    
    $quizID_feedback = intval($_POST['quizID_feedback']);
    $rating = intval($_POST['rating']);
    $comments = trim($_POST['comments']);

    if ($quizID_feedback > 0 && $rating > 0) {
        $stmt = $conn->prepare("
            INSERT INTO quizfeedback (quizID, learnerID, rating, comments, date) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("iiss", $quizID_feedback, $learnerID, $rating, $comments);
        $stmt->execute();
        $stmt->close();
    }
    
    header('Location: learner_home.php');
    exit;
}

// --- 3. الجزء الثاني: حساب النتيجة ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['quizID'])) {
    
    $quizID = intval($_POST['quizID']);
    $submittedQuestionIDs = $_POST['questionIDs'] ?? []; 
    $submittedAnswers = $_POST['answers'] ?? [];     
    
    $totalQuestions = count($submittedQuestionIDs);
    $correctCount = 0;
    
    if ($totalQuestions > 0) {
        $placeholders = implode(',', array_fill(0, $totalQuestions, '?'));
        $types = str_repeat('i', $totalQuestions);
        
        $stmt = $conn->prepare("SELECT id, correctAnswer FROM quizquestion WHERE id IN ($placeholders)");
        $stmt->bind_param($types, ...$submittedQuestionIDs);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $correctAnswersMap = [];
        while ($row = $result->fetch_assoc()) {
            $correctAnswersMap[$row['id']] = $row['correctAnswer'];
        }
        $stmt->close();

        foreach ($submittedQuestionIDs as $id) {
            $correct = $correctAnswersMap[$id] ?? null;
            $submitted = $submittedAnswers[$id] ?? null; 
            
            if ($correct !== null && $correct === $submitted) {
                $correctCount++;
            }
        }
    }

    $scorePercent = ($totalQuestions > 0) ? round(($correctCount / $totalQuestions) * 100) : 0;
    
    $stmt = $conn->prepare("INSERT INTO takenquiz (quizID, learnerID, score) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $quizID, $learnerID, $scorePercent);
    $stmt->execute();
    $stmt->close();

    // (النقطة 13c): تحديد الأنيميشن
    $animationwidth = 300;
    if ($scorePercent >= 80) {
        $animationToShow = 'animations/trophy.json'; // (ضعي اسم ملفك)
        $animationwidth = 800;
    } else if ($scorePercent >= 50) {
        $animationToShow = 'animations/good_job.json'; // (ضعي اسم ملفك)
    } else {
        $animationToShow = 'animations/try_again.json'; // (ضعي اسم ملفك)
        $animationwidth=450;
    }

    // (النقطة 13a): جلب بيانات الكويز للعرض
    $stmt = $conn->prepare("
        SELECT T.topicName, U.firstName, U.lastName, U.photoFileName 
        FROM quiz Q
        JOIN user U ON Q.educatorID = U.id
        JOIN topic T ON Q.topicID = T.id
        WHERE Q.id = ?
    ");
    $stmt->bind_param("i", $quizID);
    $stmt->execute();
    $quizInfo = $stmt->get_result()->fetch_assoc();
    $stmt->close();

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

    $showScorePage = true; 
}

if (!$showScorePage) {
    header('Location: learner_home.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Quiz Score & Feedback</title>
  <link rel="stylesheet" href="style.css">
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</head>
<body>
  <div class="quiz-box">
    <h2>
      Quiz in <?php echo htmlspecialchars($quizInfo['topicName']); ?>
      <a href="learner_home.php" class="back-link">Back to Homepage</a>
    </h2>

    <div id="educator-info">
      <img src="<?php echo htmlspecialchars($photoPath); ?>" alt="Educator Photo" class="teacher">
      <span><?php echo htmlspecialchars($quizInfo['firstName'] . ' ' . $quizInfo['lastName']); ?></span>
    </div>

    <div class="score-box">
      <p><strong>Quiz Score:</strong> <?php echo $scorePercent; ?>%</p>
      
      <lottie-player
        src="<?php echo $animationToShow; ?>"
        background="transparent" 
        speed="1" 

        preserveAspectRatio="xMidYMid slice"

        style="width: <?php echo $animationwidth; ?>px; height:400; margin:auto;"
        loop
        autoplay
    ></lottie-player>
    </div>

    <h3>Feedback about Quiz:</h3>
    <form action="quiz_score_feedback.php" method="POST">
      <input type="hidden" name="action" value="submit_feedback">
      <input type="hidden" name="quizID_feedback" value="<?php echo $quizID; ?>">
      
      <label>Rating (out of 5):</label>
      <select name="rating" class="input-field rating" required>
        <option value="">--Rate--</option>
        <option value="1">1 (Poor)</option>
        <option value="2">2 (Fair)</option>
        <option value="3">3 (Good)</option>
        <option value="4">4 (Very Good)</option>
        <option value="5">5 (Excellent)</option>
      </select>
      <br><br>
      <label>Comments (optional):</label><br>
      <textarea name="comments" class="input-field" rows="4"></textarea>
      <br>
      <button type="submit" class="btn-success">Submit Feedback</button>
    </form>
  </div>
</body>
</html>
