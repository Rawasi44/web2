<?php

// إعدادات الاتصال بقاعدة البيانات
$servername = "localhost";
$username = "root";
$password = "root"; // خليه فاضي لو تستخدمين MAMP أو XAMPP محليًا
$dbname = "edulearndb"; // اكتبي هنا اسم قاعدتك بالضبط مثل ما سويتيها في phpMyAdmin

// إنشاء الاتصال
$conn = new mysqli($servername,$username,$password, $dbname,8889);

// فحص الاتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// لو وصل لهنا، الاتصال ناجح
// تقدرين تختبرينه مؤقتًا بإظهار رسالة:
 //echo "Connected successfully";

?>