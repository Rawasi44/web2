<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'db_connection.php'; // الاتصال بقاعدة البيانات
session_start(); // لتمكين الجلسات



if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // استعلام للبحث عن المستخدم في قاعدة البيانات
    $sql = "SELECT * FROM user WHERE emailAddress = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        // التحقق من كلمة المرور
        if (password_verify($password, $row['password'])) {
            // تسجيل الدخول ناجح
            $_SESSION['userID'] = $row['id'];
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
           header("Location: login.html?error=wrongPassword");
           exit;
        }
    } else {
        header("Location: login.html?error=emailNotFound");
           exit;
    }
}
?>

