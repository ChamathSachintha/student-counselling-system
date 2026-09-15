<?php

// Connect to the counselling database and use UTF-8 text.

$host = "localhost";
$username = "root";
$password = "";
$database = "student_counselling";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>