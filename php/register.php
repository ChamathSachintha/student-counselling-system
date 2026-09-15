<?php

// Use the shared layout for the existing form result messages.
require_once __DIR__ . "/result_page.php";
// Validate student details and create the account and profile together.
require_once 'db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit('Invalid request.');
$values = [];
foreach (['name','email','password','student_number','course','year','phone'] as $field) {
    if (!isset($_POST[$field]) || !is_string($_POST[$field]) || trim($_POST[$field]) === '') {
        http_response_code(422);
        exit('Complete all required registration details.');
    }
    $values[$field] = $field === 'password' ? $_POST[$field] : trim($_POST[$field]);
}
if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    exit('Enter a valid email address.');
}
if (!ctype_digit($values['year']) || (int)$values['year'] < 1 || (int)$values['year'] > 6) {
    http_response_code(422);
    exit('Choose a study year between 1 and 6.');
}
foreach (['name'=>100,'email'=>100,'password'=>255,'student_number'=>30,'course'=>100,'phone'=>20] as $field=>$limit) {
    if (strlen($values[$field]) > $limit) { http_response_code(422); exit('Registration detail is too long: '.$field); }
}
$query = $conn->prepare('SELECT user_id FROM users WHERE email = ?');
$query->bind_param('s', $values['email']);
$query->execute();
if ($query->get_result()->num_rows) exit('Email already exists.');
$query = $conn->prepare('SELECT student_id FROM students WHERE student_number = ?');
$query->bind_param('s', $values['student_number']);
$query->execute();
if ($query->get_result()->num_rows) exit('Student number already exists.');

// Roll back the account if its student profile cannot be saved.
$conn->begin_transaction();
try {
    // Store the supplied password as plain text for the demonstration account.
    $query = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')");
    $query->bind_param('sss', $values['name'], $values['email'], $values['password']);
    $query->execute();
    $userId = $conn->insert_id;
    $query = $conn->prepare('INSERT INTO students (user_id, student_number, course, year, phone) VALUES (?, ?, ?, ?, ?)');
    $query->bind_param('issis', $userId, $values['student_number'], $values['course'], $values['year'], $values['phone']);
    $query->execute();
    $conn->commit();
    echo '<h2>Registration Successful</h2><p>Your student account has been created.</p><a href="../login.html">Go to Login</a>';
} catch (mysqli_sql_exception $error) {
    $conn->rollback();
    http_response_code(422);
    echo 'Unable to register. Check your details and try again.';
}
