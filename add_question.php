<?php
ini_set('display_errors', 1);

include 'db_connection.php';
session_start();

// نقرأ quizID من POST أولاً إذا تم الإرسال، وإلا من GET
$quizID = isset($_POST['quizID']) ? intval($_POST['quizID']) : (isset($_GET['quizID']) ? intval($_GET['quizID']) : 0);

// للتأكد من أن quizID صحيح
if ($quizID === 0) {
  die("⚠️ No quiz ID provided. Please open this page using a valid quiz link.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $question = $_POST['question'];
  $a = $_POST['answerA'];
  $b = $_POST['answerB'];
  $c = $_POST['answerC'];
  $d = $_POST['answerD'];
  $correct = $_POST['correctAnswer'];

  $fileName = null;
  if (!empty($_FILES['figure']['name'])) {
    $fileName = time() . '_' . basename($_FILES['figure']['name']);
    move_uploaded_file($_FILES['figure']['tmp_name'], "uploads/" . $fileName);
  }

  // تأكيد أن الكويز موجود فعلاً في جدول quiz
  $check = $conn->prepare("SELECT id FROM quiz WHERE id = ?");
  $check->bind_param("i", $quizID);
  $check->execute();
  $res = $check->get_result();

  if ($res->num_rows === 0) {
    die("⚠️ Invalid quiz ID ($quizID): no matching quiz found.");
  }

  $sql = "INSERT INTO quizquestion (quizID, question, questionFigureFileName, answerA, answerB, answerC, answerD, correctAnswer)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("isssssss", $quizID, $question, $fileName, $a, $b, $c, $d, $correct);
  $stmt->execute();

  header("Location: quiz_page.php?quizID=$quizID");
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Question</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="header">
  <div class="logo">
    <img src="images/logo.png" alt="Logo">
  </div>
  <h3>Add New Question</h3>
</div>

<div class="form-container">
  <form action="add_question.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="quizID" value="<?php echo htmlspecialchars($quizID); ?>">

    <div class="form-group">
      <label>Question:</label>
      <textarea name="question" required></textarea>
    </div>

    <div class="form-group">
      <label>Upload Figure:</label>
      <input type="file" name="figure">
    </div>

    <div class="form-group">
      <label>Answer A:</label>
      <input type="text" name="answerA" required>
    </div>

    <div class="form-group">
      <label>Answer B:</label>
      <input type="text" name="answerB" required>
    </div>

    <div class="form-group">
      <label>Answer C:</label>
      <input type="text" name="answerC" required>
    </div>

    <div class="form-group">
      <label>Answer D:</label>
      <input type="text" name="answerD" required>
    </div>

    <div class="form-group">
      <label>Correct Answer:</label>
      <select name="correctAnswer" required>
        <option value="A">A</option>
        <option value="B">B</option>
        <option value="C">C</option>
        <option value="D">D</option>
      </select>
    </div>

    <button type="submit" class="btn-primary">Add Question</button>
  </form>
</div>
</body>
</html>
