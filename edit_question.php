<?php
include 'db_connection.php';
session_start();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sql = "SELECT * FROM quizquestion WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$q = $stmt->get_result()->fetch_assoc();

if (!$q) { echo "<p>⚠️ Question not found.</p>"; exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Question</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .image-row { display:flex; align-items:flex-start; gap:30px; flex-wrap:wrap; }
    .current-figure { background:rgba(255,255,255,0.05); padding:10px; border-radius:12px; text-align:center; }
    .current-figure img { max-width:180px; height:auto; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.3); }
  </style>
</head>
<body>
<div class="header">
  <div class="logo"><img src="images/logo.png" alt="Logo"></div>
  <h3>Edit Question</h3>
</div>

<div class="form-container">
<form action="edit_question_process.php" method="POST" enctype="multipart/form-data">
  <input type="hidden" name="id" value="<?= $id; ?>">

  <div class="form-group">
    <label>Question:</label>
    <textarea name="question" required><?= htmlspecialchars($q['question']); ?></textarea>
  </div>

  <div class="form-group image-row">
    <div><label>Upload New Figure (optional):</label><input type="file" name="figure"></div>
    <?php if (!empty($q['questionFigureFileName'])): ?>
      <div class="current-figure">
        <p>Current Figure:</p>
        <img src="uploads/<?= htmlspecialchars($q['questionFigureFileName']); ?>" alt="Current Figure">
      </div>
    <?php endif; ?>
  </div>

  <div class="form-group"><label>Answer A:</label><input type="text" name="answerA" value="<?= htmlspecialchars($q['answerA']); ?>" required></div>
  <div class="form-group"><label>Answer B:</label><input type="text" name="answerB" value="<?= htmlspecialchars($q['answerB']); ?>" required></div>
  <div class="form-group"><label>Answer C:</label><input type="text" name="answerC" value="<?= htmlspecialchars($q['answerC']); ?>" required></div>
  <div class="form-group"><label>Answer D:</label><input type="text" name="answerD" value="<?= htmlspecialchars($q['answerD']); ?>" required></div>

  <div class="form-group">
    <label>Correct Answer:</label>
    <select name="correctAnswer" required>
      <option value="A" <?= $q['correctAnswer']=='A'?'selected':''; ?>>A</option>
      <option value="B" <?= $q['correctAnswer']=='B'?'selected':''; ?>>B</option>
      <option value="C" <?= $q['correctAnswer']=='C'?'selected':''; ?>>C</option>
      <option value="D" <?= $q['correctAnswer']=='D'?'selected':''; ?>>D</option>
    </select>
  </div>

  <button type="submit" class="btn-primary">Save Changes</button>
</form>
</div>
</body>
</html>
