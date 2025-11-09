<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connection.php';
session_start();

$quizID = isset($_GET['quizID']) ? intval($_GET['quizID']) : 0;

$query = "SELECT * FROM quizquestion WHERE quizID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $quizID);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Quiz Page</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .q-image {
      max-width: 120px;
      display: block;
      margin-bottom: 8px;
      border-radius: 6px;
    }
    .options {
      list-style-type: none;
      padding-left: 10px;
      margin: 5px 0;
    }
    .option.correct {
      font-weight: bold;
      color: green;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }
    th, td {
      border: 1px solid #ddd;
      padding: 10px;
      vertical-align: top;
    }
  
  </style>
</head>
<body>
  <div class="header">
    <div class="logo">
      <img src="images/logo.png" alt="Logo">
    </div>
    <div class="user-info">
      <h3>Quiz Questions</h3>
      <a href="educator_home.php">Back to Home</a>
    </div>
  </div>

  <div class="quiz-container">
    <div class="quiz-header">
      <h2>Questions List</h2>
      <a href="add_question.php?quizID=<?= $quizID ?>" class="add-question-btn">Add New Question</a>
    </div>

    <?php if ($result->num_rows > 0): ?>
      <table>
        <thead>
          <tr>
            <th>Question</th>
            <th>Edit</th>
            <th>Delete</th>
          </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td>
              <!-- الصورة (تظهر فقط إذا كانت موجودة) -->
              <?php if (!empty($row['questionFigureFileName'])): ?>
                <img src="uploads/<?php echo htmlspecialchars($row['questionFigureFileName']); ?>" 
                     alt="Question Figure" class="q-image">
              <?php endif; ?>

              <!-- نص السؤال -->
              <strong><?= htmlspecialchars($row['question']) ?></strong>

              <!-- الإجابات -->
              <ul class="options">
                <li class="option <?= $row['correctAnswer']=='A'?'correct':'' ?>">A. <?= htmlspecialchars($row['answerA']) ?></li>
                <li class="option <?= $row['correctAnswer']=='B'?'correct':'' ?>">B. <?= htmlspecialchars($row['answerB']) ?></li>
                <li class="option <?= $row['correctAnswer']=='C'?'correct':'' ?>">C. <?= htmlspecialchars($row['answerC']) ?></li>
                <li class="option <?= $row['correctAnswer']=='D'?'correct':'' ?>">D. <?= htmlspecialchars($row['answerD']) ?></li>
              </ul>

              <!-- الإجابة الصحيحة -->
              <p><strong>Correct:</strong> <?= htmlspecialchars($row['correctAnswer']) ?></p>
            </td>

            <!-- رابط التعديل -->
            <td>
              <a href="edit_question.php?id=<?= $row['id'] ?>" class="action-btn edit-btn">Edit</a>
            </td>

            <!-- رابط الحذف -->
            <td>
              <a href="delete_question.php?id=<?= $row['id'] ?>&quizID=<?= $quizID ?>" 
                 class="action-btn delete-btn" 
                 onclick="return confirm('Delete this question?');">Delete</a>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No questions found for this quiz.</p>
    <?php endif; ?>
  </div>
</body>

<footer class="site-footer">
  <div class="footer-brand">
    <img src="images/logo.png" alt="Logo" class="footer-logo">
    <h2 class="footer-name"><span style="color:rgb(167, 203, 251);">EduLearn</span></h2>
  </div>
  <p class="footer-copy">&copy; 2025 EduLearn. All rights reserved.</p>
</footer>
</html>
