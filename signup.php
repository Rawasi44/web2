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
    header("Location: signup.html?error=emailExists");
    exit();
  } else {
    // إدخال المستخدم
    $insert = $conn->prepare("
      INSERT INTO user (firstName, lastName, emailAddress, password, userType, photoFileName)
      VALUES (?, ?, ?, ?, ?, ?)
    ");
    $insert->bind_param("ssssss", $fname, $lname, $email, $password, $userType, $uniqueName);
    $insert->execute();

    // حفظ بيانات المستخدم في السيشن
    $_SESSION['userID']        = $conn->insert_id;   // 👈 المهم
    $_SESSION['userType']      = $userType;
    $_SESSION['firstName']     = $fname;
    $_SESSION['lastName']      = $lname;
    $_SESSION['emailAddress']  = $email;
    $_SESSION['photoFileName'] = $uniqueName;

    //  إنشاء الكويزات التلقائية إذا المستخدم "معلم"
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
