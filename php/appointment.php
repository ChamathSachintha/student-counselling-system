<?php

// Use the shared layout for the existing form result messages.
require_once __DIR__ . "/result_page.php";

session_start();

require_once "db.php";


// CHECK LOGIN

if (!isset($_SESSION["user_id"])) {

    // For GET request, return JSON
    if ($_SERVER["REQUEST_METHOD"] == "GET") {

        header("Content-Type: application/json");

        echo json_encode([
            "success" => false,
            "message" => "Please login first."
        ]);

        exit;
    }

    echo "Please login first.";

    exit;
}


// CHECK STUDENT ROLE

if ($_SESSION["role"] != "student") {

    if ($_SERVER["REQUEST_METHOD"] == "GET") {

        header("Content-Type: application/json");

        echo json_encode([
            "success" => false,
            "message" => "Only students can access appointments."
        ]);

        exit;
    }

    echo "Only students can book appointments.";

    exit;
}


// GET APPOINTMENTS

if ($_SERVER["REQUEST_METHOD"] == "GET") {

    header("Content-Type: application/json");


    $user_id = $_SESSION["user_id"];


    // Find student ID
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


    // Get appointments
    $appointment_query = $conn->prepare(
        "SELECT
            appointments.appointment_id,
            appointments.appointment_date,
            appointments.appointment_time,
            appointments.reason,
            appointments.status,
            users.name AS counselor_name
         FROM appointments
         INNER JOIN users
         ON appointments.counselor_id = users.user_id
         WHERE appointments.student_id = ?
         ORDER BY appointments.appointment_date DESC,
                  appointments.appointment_time DESC"
    );

    $appointment_query->bind_param(
        "i",
        $student_id
    );

    $appointment_query->execute();

    $result = $appointment_query->get_result();


    $appointments = [];


    while ($row = $result->fetch_assoc()) {

        $appointments[] = $row;
    }


    echo json_encode([
        "success" => true,
        "appointments" => $appointments
    ]);

    exit;
}


// CREATE APPOINTMENT

if ($_SERVER["REQUEST_METHOD"] == "POST") {


    // Validate booking details before looking up availability.
    foreach (['counselor_id', 'appointment_date', 'appointment_time', 'reason'] as $field) {
        if (!isset($_POST[$field]) || !is_string($_POST[$field]) || trim($_POST[$field]) === '') {
            http_response_code(422);
            exit('Complete all appointment details.');
        }
    }
    date_default_timezone_set('Asia/Colombo');
    $requested = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $_POST['appointment_date'].' '.$_POST['appointment_time']);
    if (!$requested || $requested->format('Y-m-d H:i') !== $_POST['appointment_date'].' '.$_POST['appointment_time'] || $requested <= new DateTimeImmutable()) {
        http_response_code(422);
        exit('Choose a valid future appointment date and time.');
    }
    if (!ctype_digit($_POST['counselor_id']) || (int)$_POST['counselor_id'] < 1 || strlen($_POST['reason']) > 255) {
        http_response_code(422);
        exit('Choose a counselor and enter a reason of at most 255 bytes.');
    }
    $counselor_id = $_POST["counselor_id"];

    $appointment_date = $_POST["appointment_date"];

    $appointment_time = $_POST["appointment_time"];

    $reason = trim($_POST["reason"]);


    // Get logged-in student's user ID
    $user_id = $_SESSION["user_id"];


    // Find student ID
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

        echo "Student profile not found.";

        exit;
    }


    $student = $student_result->fetch_assoc();

    $student_id = $student["student_id"];


    // Check counselor
    $counselor_query = $conn->prepare(
        "SELECT user_id
         FROM users
         WHERE user_id = ?
         AND role = 'counselor'"
    );

    $counselor_query->bind_param(
        "i",
        $counselor_id
    );

    $counselor_query->execute();

    $counselor_result = $counselor_query->get_result();


    if ($counselor_result->num_rows == 0) {

        echo "Selected counselor does not exist.";

        exit;
    }


    // Check time availability
    $availability_query = $conn->prepare(
        "SELECT appointment_id
         FROM appointments
         WHERE counselor_id = ?
         AND appointment_date = ?
         AND appointment_time = ?
         AND status IN ('Pending', 'Approved')"
    );

    $availability_query->bind_param(
        "iss",
        $counselor_id,
        $appointment_date,
        $appointment_time
    );

    $availability_query->execute();

    $availability_result = $availability_query->get_result();


    if ($availability_result->num_rows > 0) {

        echo "
            <h2>Time Slot Unavailable</h2>

            <p>
                The counselor already has an appointment
                at this time.
            </p>

            <a href='../student.html'>
                Back to Dashboard
            </a>
        ";

        exit;
    }


    // Insert appointment
    $appointment_query = $conn->prepare(
        "INSERT INTO appointments
        (
            student_id,
            counselor_id,
            appointment_date,
            appointment_time,
            reason,
            status
        )
        VALUES (?, ?, ?, ?, ?, 'Pending')"
    );

    $appointment_query->bind_param(
        "iisss",
        $student_id,
        $counselor_id,
        $appointment_date,
        $appointment_time,
        $reason
    );


    if ($appointment_query->execute()) {

        echo "
            <h2>Appointment Request Sent</h2>

            <p>
                Your counselling appointment request
                has been submitted successfully.
            </p>

            <p>
                Status: Pending
            </p>

            <a href='../student.html'>
                Back to Dashboard
            </a>
        ";

    } else {

        echo "
            <h2>Error</h2>

            <p>
                Unable to book the appointment.
            </p>

            <a href='../student.html'>
                Back to Dashboard
            </a>
        ";
    }

}

?>