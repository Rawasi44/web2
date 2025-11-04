<?php
include 'db_connection.php';
session_start();

// 🔹 جلب رقم السؤال من الرابط
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 🔹 جلب بيانات السؤال من قاعدة البيانات
$sql = "SELECT * FROM quizquestion WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$q = $stmt->get_result()->fetch_assoc();

if (!$q) {
  echo "<p>⚠️ Question not found.</p>";
  exit;
}

$quizID = $q['quizID'];

// 🔹 إذا تم إرسال النموذج (تحديث البيانات)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $question = $_POST['question'];
  $a = $_POST['answerA'];
  $b = $_POST['answerB'];
  $c = $_POST['answerC'];
  $d = $_POST['answerD'];
  $correct = $_POST['correctAnswer'];
  $fileName = $q['questionFigureFileName'];

  // 🔹 لو رفع المستخدم صورة جديدة
  if (!empty($_FILES['figure']['name'])) {
    $newName = time() . '_' . basename($_FILES['figure']['name']);
    move_uploaded_file($_FILES['figure']['tmp_name'], "uploads/" . $newName);
    $fileName = $newName;
  }

  // 🔹 تحديث البيانات في قاعدة البيانات
  $sql = "UPDATE quizquestion 
          SET question=?, questionFigureFileName=?, answerA=?, answerB=?, answerC=?, answerD=?, correctAnswer=?
          WHERE id=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("sssssssi", $question, $fileName, $a, $b, $c, $d, $correct, $id);
  $stmt->execute();

  header("Location: quiz_page.php?quizID=$quizID");
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Question</title>
  <link rel="stylesheet" href="style.css">
  <style>
    /* ✨ تنسيق أفقي لزر رفع الصورة والصورة الحالية */
    .image-row {
      display: flex;
      align-items: flex-start;
      gap: 30px;
      flex-wrap: wrap;
    }

    .current-figure {
      background: rgba(255, 255, 255, 0.05);
      padding: 10px;
      border-radius: 12px;
      text-align: center;
    }

    .current-figure img {
      max-width: 180px;
      height: auto;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.3);
    }
  </style>
</head>
<body>
<div class="header">
  <div class="logo">
    <img src="images/logo.png" alt="Logo">
  </div>
  <h3>Edit Question</h3>
</div>

<div class="form-container">
<form action="edit_question.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">

  <div class="form-group">
    <label>Question:</label>
    <textarea name="question" required><?php echo htmlspecialchars($q['question']); ?></textarea>
  </div>

  <div class="form-group image-row">
    <div>
      <label>Upload New Figure (optional):</label>
      <input type="file" name="figure">
    </div>

    <?php if (!empty($q['questionFigureFileName'])): ?>
      <div class="current-figure">
        <p>Current Figure:</p>
        <img src="uploads/<?php echo htmlspecialchars($q['questionFigureFileName']); ?>" 
             alt="Current Figure">
      </div>
    <?php endif; ?>
  </div>

  <div class="form-group">
    <label>Answer A:</label>
    <input type="text" name="answerA" value="<?php echo htmlspecialchars($q['answerA']); ?>" required>
  </div>

  <div class="form-group">
    <label>Answer B:</label>
    <input type="text" name="answerB" value="<?php echo htmlspecialchars($q['answerB']); ?>" required>
  </div>

  <div class="form-group">
    <label>Answer C:</label>
    <input type="text" name="answerC" value="<?php echo htmlspecialchars($q['answerC']); ?>" required>
  </div>

  <div class="form-group">
    <label>Answer D:</label>
    <input type="text" name="answerD" value="<?php echo htmlspecialchars($q['answerD']); ?>" required>
  </div>

  <div class="form-group">
    <label>Correct Answer:</label>
    <select name="correctAnswer" required>
      <option value="A" <?php if($q['correctAnswer']=='A') echo 'selected'; ?>>A</option>
      <option value="B" <?php if($q['correctAnswer']=='B') echo 'selected'; ?>>B</option>
      <option value="C" <?php if($q['correctAnswer']=='C') echo 'selected'; ?>>C</option>
      <option value="D" <?php if($q['correctAnswer']=='D') echo 'selected'; ?>>D</option>
    </select>
  </div>

  <button type="submit" class="btn-primary">Save Changes</button>
</form>
</div>
</body>
</html>
