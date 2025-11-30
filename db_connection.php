<?php
$servername = "sql312.infinityfree.com"; 
$username   = "if0_40559314"; 
$password   = "9Qj4EqrVRA8D69"; 
$dbname     = "if0_40559314_edulearnweb_db";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>
