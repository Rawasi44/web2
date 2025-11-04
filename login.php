<?php
include 'db_connection.php'; // الاتصال بقاعدة البيانات
session_start(); // لتمكين الجلسات



if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // استعلام للبحث عن المستخدم في قاعدة البيانات
    $sql = "SELECT * FROM User WHERE emailAddress = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        // التحقق من كلمة المرور
        if (password_verify($password, $row['password'])) {
            // تسجيل الدخول ناجح
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['userType'] = $row['userType'];
            $_SESSION['firstName'] = $row['firstName'];
            $_SESSION['lastName']     = $row['lastName'];
            $_SESSION['emailAddress'] = $row['emailAddress'];   
            $_SESSION['photoFileName']= $row['photoFileName'] ?: null;

            // توجيه المستخدم حسب نوعه
            if ($row['userType'] === 'learner') {
                header("Location: learner_home.php");
                exit;
            } else {
                header("Location: educator_home.php");
                exit;
            }
        } else {
           header("Location: login.php?error=wrongPassword");
           exit;
        }
    } else {
        header("Location: login.php?error=emailNotFound");
           exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login Page</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h1>Welcome to <span style="color: rgb(167, 203, 251);">EduLearn</span></h1>
  <div class="logo">
    <img src="images/logo.png" alt="Logo">
  </div>
  <h2>Login</h2>
  <a href="home.html">Back to home</a>

  <form method="POST" action="login.php" class="login-box">
    <input class="input-field" type="email" name="email" placeholder="Enter your Email" required><br>
    <input class="input-field" type="password" name="password" placeholder="Enter your Password" required><br>
    <button class="btn-primary" type="submit">Login</button>
  </form>

  <?php 
  if (isset($_GET['error'])){
      if($_GET['error'] == 'wrongPassword'){
          echo "<p style='color:red; text-align:center;'> Wrong password<?p>";
      }elseif ($_GET['error'] == 'emailNotFound'){
          echo "<p style='color:red; text-align:center;'>Email not found<?p>";
      }
  }
  ?>

  <footer class="site-footer">
  <div class="footer-brand">
    <img src="images/logo.png" alt="Edulearn Logo" class="footer-logo">
    <h2 class="footer-name"><span style="color:rgb(167, 203, 251);">EduLearn</span></h2>
  </div>
  <p class="footer-copy">&copy; 2025 Edulearn. All rights reserved.</p>
</footer>
</body>
</html>

