<?php
include 'db_connection.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $userType = $_POST['userType'] ?? '';
  $fname = $_POST['firstName'] ?? '';
  $lname = $_POST['lastName'] ?? '';
  $email = $_POST['emailAddress'] ?? '';
  $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
  $topicsArr = isset($_POST['topic']) ? $_POST['topic'] : [];
  $topics = !empty($topicsArr) ? implode(", ", $topicsArr) : NULL;

  // رفع الصورة
  if (!empty($_FILES["photo"]["name"])) {
      $fileName = basename($_FILES["photo"]["name"]);
      $targetDir = "uploads/";
      if (!is_dir($targetDir)) mkdir($targetDir);
      $uniqueName = uniqid() . "_" . $fileName;
      $targetFile = $targetDir . $uniqueName;
      move_uploaded_file($_FILES["photo"]["tmp_name"], $targetFile);
  } else {
      $uniqueName = "images/default.png";
  }

  // تحقق من تكرار الإيميل
  $check = $conn->prepare("SELECT id FROM user WHERE emailAddress = ?");
  $check->bind_param("s", $email);
  $check->execute();
  $result = $check->get_result();

  if ($result->num_rows > 0) {
    header("Location: signup.php?error=emailExists");
    exit();
  } else {
    // إدخال المستخدم
    $insert = $conn->prepare("
      INSERT INTO user (firstName, lastName, emailAddress, password, userType, photoFileName, topics)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $insert->bind_param("sssssss", $fname, $lname, $email, $password, $userType, $uniqueName, $topics);
    $insert->execute();

    // حفظ بيانات المستخدم في السيشن
    $_SESSION['userID']        = $conn->insert_id;   // 👈 المهم
    $_SESSION['userType']      = $userType;
    $_SESSION['firstName']     = $fname;
    $_SESSION['lastName']      = $lname;
    $_SESSION['emailAddress']  = $email;
    $_SESSION['photoFileName'] = $uniqueName;

    // ✅ إنشاء الكويزات التلقائية إذا المستخدم "معلم"
    if ($userType === "educator" && !empty($topicsArr)) {
        $educatorID = (int)$_SESSION['userID'];

        foreach ($topicsArr as $topicName) {
            // نحصل على رقم الموضوع من جدول topic
            $stmt = $conn->prepare("SELECT id FROM topic WHERE topicName = ?");
            $stmt->bind_param("s", $topicName);
            $stmt->execute();
            $topicRow = $stmt->get_result()->fetch_assoc();

            if ($topicRow) {
                $topicID = (int)$topicRow['id'];

                // إضافة كويز جديد لهذا المعلم/الموضوع (مع قيود UNIQUE على (educatorID, topicID))
                $addQuiz = $conn->prepare("INSERT IGNORE INTO quiz (educatorID, topicID) VALUES (?, ?)");
                $addQuiz->bind_param("ii", $educatorID, $topicID);
                $addQuiz->execute();
            }
        }
    }

    // توجيه المستخدم
    if ($userType === "educator") {
        header("Location: educator_home.php");
    } else {
        header("Location: learner_home.php");
    }
    exit();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Sign Up</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <h1>Welcome to <span style="color:rgb(167, 203, 251);">EduLearn</span></h1>
  <div class="logo">
    <img src="images/logo.png" alt="Logo">
  </div>
  <h2>Sign Up</h2>
  <a href="home.html">Back to home</a>

  <?php
  if (isset($_GET['error']) && $_GET['error'] == 'emailExists'){
      echo "<p style='color:red; text-align:center;'>This email already exists.</p>";
  }
  ?>

  <div class="radios">
    <label><input type="radio" name="uType" value="learner"> Learner</label>
    <label><input type="radio" name="uType" value="educator"> Educator</label>
  </div>

  <!-- Learner form -->
  <form id="learnerForm" class="form-box" method="POST" action="signup.php" enctype="multipart/form-data">
    <input type="hidden" name="userType" value="learner">
    <h3>Learner Registration</h3>
    <div class="info">
      <input class="input-field" type="text" name="firstName" placeholder="First Name" required>
      <input class="input-field" type="text" name="lastName" placeholder="Last Name" required>
    </div>
    <div class="info">
      <input class="input-field" type="email" name="emailAddress" placeholder="Email" required>
      <input class="input-field" type="password" name="password" placeholder="Password" required>
    </div>
    <img id="l-avatar" class="avatar" src="images/default.png" alt="Profile" />
    <input type="file" name="photo" accept="image/*" />
    <div>
      <button class="btn-success" type="submit">Complete Registration</button>
    </div>
  </form>

  <!-- Educator form -->
  <form id="educatorForm" class="form-box" method="POST" action="signup.php" enctype="multipart/form-data" style="display:none;">
    <input type="hidden" name="userType" value="educator">
    <h3>Educator Registration</h3>
    <div class="info">
      <input class="input-field" type="text" name="firstName" placeholder="First Name" required>
      <input class="input-field" type="text" name="lastName" placeholder="Last Name" required>
    </div>
    <div class="info">
      <input class="input-field" type="email" name="emailAddress" placeholder="Email" required>
      <input class="input-field" type="password" name="password" placeholder="Password" required>
    </div>

    <img id="e-avatar" class="avatar" src="images/default.png" alt="Profile" />
    <input type="file" name="photo" accept="image/*" />

    <div class="topics">
      <p class="topics-title">Select your topics (choose one or more):</p>
      <label><input type="checkbox" name="topic[]" value="HTML & CSS"> HTML & CSS</label>
      <label><input type="checkbox" name="topic[]" value="JavaScript"> JavaScript</label>
      <label><input type="checkbox" name="topic[]" value="Databases"> Databases</label>
    </div>

    <div>
      <button class="btn-success" type="submit">Complete Registration</button>
    </div>
  </form>

  <footer class="site-footer">
    <div class="footer-brand">
      <img src="images/logo.png" alt="Edulearn Logo" class="footer-logo">
      <h2 class="footer-name"><span style="color:rgb(167, 203, 251);">EduLearn</span></h2>
    </div>
    <p class="footer-copy">&copy; 2025 Edulearn. All rights reserved.</p>
  </footer>

  <script src="script.js"></script>
</body>
</html>
