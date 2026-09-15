<?php

// Login user and redirect according to role.

session_start();

require_once "db.php";


// Check login request.
if ($_SERVER["REQUEST_METHOD"] != "POST") {

    header("Location: ../login.html?error=request");
    exit;

}


$email = trim(is_string($_POST["email"] ?? null) ? $_POST["email"] : "");

$password = is_string($_POST["password"] ?? null) ? $_POST["password"] : "";


// Check empty fields.
if ($email == "" || $password == "") {

    header("Location: ../login.html?error=empty");
    exit;

}


// Find user account.
$query = $conn->prepare(
    "SELECT user_id, name, email, password, role
     FROM users
     WHERE email = ?"
);


$query->bind_param(
    "s",
    $email
);


$query->execute();


$result = $query->get_result();


// Check email exists.
if ($result->num_rows == 0) {

    header("Location: ../login.html?error=email");
    exit;

}


$user = $result->fetch_assoc();


// Compare the submitted password with the stored plain-text demo password.
if ($password !== $user["password"]) {

    header("Location: ../login.html?error=password");
    exit;

}


// Create login session.
session_regenerate_id(true);

$_SESSION["user_id"] = $user["user_id"];

$_SESSION["name"] = $user["name"];

$_SESSION["email"] = $user["email"];

$_SESSION["role"] = $user["role"];


// Redirect by user role.
if ($user["role"] == "student") {

    header("Location: ../student.html");

}

elseif ($user["role"] == "counselor") {

    header("Location: ../counselor.html");

}

elseif ($user["role"] == "admin") {

    header("Location: ../admin.html");

}

else {

    header("Location: ../login.html?error=role");

}


exit;

?>