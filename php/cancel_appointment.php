<?php

session_start();
header("Content-Type: application/json");

require_once "db.php";


// Check login
if (!isset($_SESSION["user_id"])) {

    echo json_encode([
        "success" => false,
        "message" => "Please login first."
    ]);

    exit;
}


// Only students can cancel their appointments
if ($_SESSION["role"] != "student") {

    echo json_encode([
        "success" => false,
        "message" => "Only students can cancel appointments."
    ]);

    exit;
}


// Check request
if ($_SERVER["REQUEST_METHOD"] != "POST") {

    echo json_encode([
        "success" => false,
        "message" => "Invalid request."
    ]);

    exit;
}


// Get appointment ID
$appointment_id = filter_input(INPUT_POST, "appointment_id", FILTER_VALIDATE_INT) ?: 0;


// Get logged-in user
$user_id = $_SESSION["user_id"];


// Find student
$student_query = $conn->prepare(
    "SELECT student_id
     FROM students
     WHERE user_id = ?"
);

$student_query->bind_param(
    "i",
    $user_id
);

$student_query->execute();

$student_result = $student_query->get_result();


if ($student_result->num_rows == 0) {

    echo json_encode([
        "success" => false,
        "message" => "Student profile not found."
    ]);

    exit;
}


$student = $student_result->fetch_assoc();

$student_id = $student["student_id"];


// Check appointment belongs to this student
$check_query = $conn->prepare(
    "SELECT appointment_id
     FROM appointments
     WHERE appointment_id = ?
     AND student_id = ?
     AND status IN ('Pending', 'Approved')"
);

$check_query->bind_param(
    "ii",
    $appointment_id,
    $student_id
);

$check_query->execute();

$check_result = $check_query->get_result();


if ($check_result->num_rows == 0) {

    echo json_encode([
        "success" => false,
        "message" => "Appointment cannot be cancelled."
    ]);

    exit;
}


// Cancel appointment
$cancel_query = $conn->prepare(
    "UPDATE appointments
     SET status = 'Cancelled'
     WHERE appointment_id = ?
     AND student_id = ?"
);

$cancel_query->bind_param(
    "ii",
    $appointment_id,
    $student_id
);


if ($cancel_query->execute()) {

    echo json_encode([
        "success" => true,
        "message" => "Appointment cancelled successfully."
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Unable to cancel appointment."
    ]);
}

?>