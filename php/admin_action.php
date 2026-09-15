<?php

session_start();

require_once "db.php";

header("Content-Type: application/json");


// Send a standard admin JSON response and end the request.

function response($success, $message = "", $data = [])
{
    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);

    exit;
}


// CHECK LOGIN

if (!isset($_SESSION["user_id"])) {

    response(
        false,
        "You are not logged in."
    );
}


// CHECK ADMIN ROLE

if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {

    response(
        false,
        "Admin access required."
    );
}


// GET ACTION

$action = $_GET["action"] ?? $_POST["action"] ?? "";


// CHECK ADMIN

if ($action === "check") {

    response(
        true,
        "Admin authenticated.",
        [
            "user_id" => $_SESSION["user_id"],
            "name" => $_SESSION["name"],
            "email" => $_SESSION["email"],
            "role" => $_SESSION["role"]
        ]
    );
}


// DASHBOARD

if ($action === "dashboard") {


    // Total students
    $student_result = $conn->query(
        "SELECT COUNT(*) AS total
         FROM users
         WHERE role = 'student'"
    );

    $total_students =
        $student_result->fetch_assoc()["total"];


    // Total counselors
    $counselor_result = $conn->query(
        "SELECT COUNT(*) AS total
         FROM users
         WHERE role = 'counselor'"
    );

    $total_counselors =
        $counselor_result->fetch_assoc()["total"];


    // Total appointments
    $appointment_result = $conn->query(
        "SELECT COUNT(*) AS total
         FROM appointments"
    );

    $total_appointments =
        $appointment_result->fetch_assoc()["total"];


    // Pending appointments
    $pending_result = $conn->query(
        "SELECT COUNT(*) AS total
         FROM appointments
         WHERE status = 'Pending'"
    );

    $pending_appointments =
        $pending_result->fetch_assoc()["total"];


    response(
        true,
        "Dashboard data loaded.",
        [
            "total_students" => $total_students,
            "total_counselors" => $total_counselors,
            "total_appointments" => $total_appointments,
            "pending_appointments" => $pending_appointments
        ]
    );
}


// APPOINTMENTS

if ($action === "appointments") {


    $query = $conn->query(
        "SELECT
            a.appointment_id,
            a.student_id,
            a.counselor_id,
            a.appointment_date,
            a.appointment_time,
            a.reason,
            a.status,

            s.student_number,

            student_user.name AS student_name,

            counselor_user.name AS counselor_name

         FROM appointments a

         LEFT JOIN students s
         ON a.student_id = s.student_id

         LEFT JOIN users student_user
         ON s.user_id = student_user.user_id

         LEFT JOIN users counselor_user
         ON a.counselor_id = counselor_user.user_id

         ORDER BY a.appointment_id DESC"
    );


    $appointments = [];


    while ($row = $query->fetch_assoc()) {

        $appointments[] = $row;
    }


    response(
        true,
        "Appointments loaded.",
        [
            "appointments" => $appointments
        ]
    );
}


// USERS

if ($action === "users") {


    $query = $conn->query(
        "SELECT
            user_id,
            name,
            email,
            role
         FROM users
         ORDER BY user_id ASC"
    );


    $users = [];


    while ($row = $query->fetch_assoc()) {

        $users[] = $row;
    }


    response(
        true,
        "Users loaded.",
        [
            "users" => $users
        ]
    );
}


// COUNSELORS

if ($action === "counselors") {


    $query = $conn->query(
        "SELECT
            u.user_id,
            u.name,
            u.email
         FROM users u
         WHERE u.role = 'counselor'
         ORDER BY u.user_id ASC"
    );


    $counselors = [];


    while ($row = $query->fetch_assoc()) {

        $counselors[] = $row;
    }


    response(
        true,
        "Counselors loaded.",
        [
            "counselors" => $counselors
        ]
    );
}


// UPDATE APPOINTMENT STATUS

if ($action === "update_appointment") {


    if (
        !isset($_POST["appointment_id"]) ||
        !isset($_POST["status"])
    ) {

        response(
            false,
            "Appointment ID and status are required."
        );
    }


    $appointment_id =
        intval($_POST["appointment_id"]);

    $status =
        trim($_POST["status"]);


    $allowed_statuses = [
        "Pending",
        "Approved",
        "Rejected",
        "Completed",
        "Cancelled"
    ];


    if (!in_array($status, $allowed_statuses, true)) {

        response(
            false,
            "Invalid appointment status."
        );
    }


    // Reject IDs that do not identify an existing appointment.
    $check = $conn->prepare("SELECT appointment_id FROM appointments WHERE appointment_id = ?");
    $check->bind_param('i', $appointment_id);
    $check->execute();
    if ($check->get_result()->num_rows !== 1) response(false, "Appointment not found.");

    $query = $conn->prepare(
        "UPDATE appointments
         SET status = ?
         WHERE appointment_id = ?"
    );


    $query->bind_param(
        "si",
        $status,
        $appointment_id
    );


    if (!$query->execute()) {

        response(
            false,
            "Unable to update appointment."
        );
    }


    response(
        true,
        "Appointment status updated successfully."
    );
}


// Validate and save a new account with its optional student profile.
if ($action === "add_user") {
    foreach (['name', 'email', 'password', 'role'] as $field) {
        if (!isset($_POST[$field]) || !is_string($_POST[$field]) || trim($_POST[$field]) === '') {
            response(false, 'Name, email, password, and role are required.');
        }
    }
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = trim($_POST['role']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) response(false, 'Enter a valid email address.');
    if (!in_array($role, ['student', 'counselor', 'admin'], true)) response(false, 'Invalid user role.');
    if (strlen($name) > 100 || strlen($email) > 100 || strlen($password) > 255) {
        response(false, 'Name and email must be at most 100 characters; password must be at most 255.');
    }
    $check = $conn->prepare('SELECT user_id FROM users WHERE email = ?');
    $check->bind_param('s', $email);
    $check->execute();
    if ($check->get_result()->num_rows) response(false, 'Email already exists.');

    // Validate student details before inserting any account records.
    if ($role === 'student') {
        foreach (['student_number', 'course', 'year', 'phone'] as $field) {
            if (isset($_POST[$field]) && !is_string($_POST[$field])) response(false, 'Invalid student details.');
        }
        $student_number = trim($_POST['student_number'] ?? '');
        $course = trim($_POST['course'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $year_input = trim($_POST['year'] ?? '');
        $year = $year_input === '' ? null : filter_var($year_input, FILTER_VALIDATE_INT);
        if ($year !== null && ($year === false || $year < 1 || $year > 6)) {
            response(false, 'Choose a study year between 1 and 6.');
        }
        if (strlen($student_number) > 30 || strlen($course) > 100 || strlen($phone) > 20) {
            response(false, 'Student number, course, or phone number is too long.');
        }
        if ($student_number !== '') {
            $check = $conn->prepare('SELECT student_id FROM students WHERE student_number = ?');
            $check->bind_param('s', $student_number);
            $check->execute();
            if ($check->get_result()->num_rows) response(false, 'Student number already exists.');
        }
    }

    // Commit the account and profile together or roll back both on failure.
    $conn->begin_transaction();
    try {
        $query = $conn->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
        $query->bind_param('ssss', $name, $email, $password, $role);
        $query->execute();
        $user_id = $conn->insert_id;
        if ($role === 'student') {
            $query = $conn->prepare('INSERT INTO students (user_id, student_number, course, year, phone) VALUES (?, ?, ?, ?, ?)');
            $query->bind_param('issis', $user_id, $student_number, $course, $year, $phone);
            $query->execute();
        }
        $conn->commit();
    } catch (mysqli_sql_exception $error) {
        $conn->rollback();
        response(false, 'Unable to create the account. Check the details and try again.');
    }
    response(true, 'User added successfully.');
}


// CHANGE USER PASSWORD

if ($action === "change_password") {


    if (
        !isset($_POST["user_id"]) ||
        !isset($_POST["new_password"])
    ) {

        response(
            false,
            "User ID and new password are required."
        );
    }


    $user_id =
        intval($_POST["user_id"]);

    $new_password =
        $_POST["new_password"];


    // Check password is not empty
    if (
        trim($new_password) === ""
    ) {

        response(
            false,
            "Password cannot be empty."
        );
    }


    // Optional minimum password length
    if (
        strlen($new_password) < 4
    ) {

        response(
            false,
            "Password must contain at least 4 characters."
        );
    }


    // Check user exists
    $check =
        $conn->prepare(
            "SELECT user_id
             FROM users
             WHERE user_id = ?"
        );


    $check->bind_param(
        "i",
        $user_id
    );


    $check->execute();


    $result =
        $check->get_result();


    if ($result->num_rows !== 1) {

        response(
            false,
            "User not found."
        );
    }


    // Store the demo password directly, within the database field limit.
    if (strlen($new_password) > 255) response(false, "Password must be at most 255 bytes.");


    // Update password
    $query =
        $conn->prepare(
            "UPDATE users
             SET password = ?
             WHERE user_id = ?"
        );


    $query->bind_param(
        "si",
        $new_password,
        $user_id
    );


    if (!$query->execute()) {

        response(
            false,
            "Unable to change password."
        );
    }


    response(
        true,
        "Password changed successfully."
    );
}


// DELETE USER

if ($action === "delete_user") {


    if (!isset($_POST["user_id"])) {

        response(
            false,
            "User ID is required."
        );
    }


    $user_id =
        intval($_POST["user_id"]);


    // Prevent admin from deleting their own account
    if (
        $user_id == $_SESSION["user_id"]
    ) {

        response(
            false,
            "You cannot delete your own admin account."
        );
    }


    // Check user exists
    $check =
        $conn->prepare(
            "SELECT user_id, role
             FROM users
             WHERE user_id = ?"
        );


    $check->bind_param(
        "i",
        $user_id
    );


    $check->execute();


    $result =
        $check->get_result();


    if ($result->num_rows !== 1) {

        response(
            false,
            "User not found."
        );
    }


    $user =
        $result->fetch_assoc();


    // Delete student profile first
    if ($user["role"] === "student") {


        $student_delete =
            $conn->prepare(
                "DELETE FROM students
                 WHERE user_id = ?"
            );


        $student_delete->bind_param(
            "i",
            $user_id
        );


        $student_delete->execute();
    }


    // Delete user
    $delete =
        $conn->prepare(
            "DELETE FROM users
             WHERE user_id = ?"
        );


    $delete->bind_param(
        "i",
        $user_id
    );


    if (!$delete->execute()) {

        response(
            false,
            "Unable to delete user."
        );
    }


    response(
        true,
        "User deleted successfully."
    );
}


// INVALID ACTION

response(
    false,
    "Invalid action."
);

?>