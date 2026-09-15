
<?php

session_start();

require_once "db.php";

header("Content-Type: application/json");


// Send a feedback result with optional data and end the request.

function response($success, $message, $extra = [])
{
    echo json_encode(
        array_merge(
            [
                "success" => $success,
                "message" => $message
            ],
            $extra
        )
    );

    exit;
}


// CHECK LOGIN

if (!isset($_SESSION["user_id"])) {

    response(false, "Please login first.");

}


// CHECK STUDENT ROLE

if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "student"
) {

    response(false, "Only students can submit feedback.");

}


$user_id =
    intval($_SESSION["user_id"]);


// FIND STUDENT ID

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

$student_result =
    $student_query->get_result();


if ($student_result->num_rows === 0) {

    response(false, "Student profile not found.");

}


$student =
    $student_result->fetch_assoc();

$student_id =
    intval($student["student_id"]);


// GET FEEDBACK

if ($_SERVER["REQUEST_METHOD"] === "GET") {


    $query = $conn->prepare(

        "SELECT
            feedback.feedback_id,
            feedback.counselor_id,
            feedback.rating,
            feedback.comment,
            users.name AS counselor_name

         FROM feedback

         INNER JOIN users
         ON feedback.counselor_id = users.user_id

         WHERE feedback.student_id = ?

         ORDER BY feedback.feedback_id DESC"

    );


    $query->bind_param(
        "i",
        $student_id
    );


    $query->execute();

    $result =
        $query->get_result();


    $feedback = [];


    while ($row = $result->fetch_assoc()) {

        $feedback[] = $row;

    }


    response(
        true,
        "Feedback loaded successfully.",
        [
            "feedback" => $feedback
        ]
    );

}


// SUBMIT FEEDBACK

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    if (
        !isset($_POST["counselor_id"]) ||
        !isset($_POST["rating"])
    ) {

        response(
            false,
            "Counselor and rating are required."
        );

    }


    $counselor_id =
        filter_var($_POST["counselor_id"], FILTER_VALIDATE_INT);


    $rating =
        filter_var($_POST["rating"], FILTER_VALIDATE_INT);


    if (!$counselor_id || $counselor_id < 1) response(false, 'Select a valid counselor.');
    if (isset($_POST['comment']) && !is_string($_POST['comment'])) response(false, 'Enter a valid feedback comment.');

    $comment =
        trim($_POST["comment"] ?? "");


    // Keep feedback within the database field limit.
    if (strlen($comment) > 500) response(false, 'Keep feedback comments within 500 bytes.');

    // Validate rating

    if ($rating === false || $rating < 1 || $rating > 5) {

        response(
            false,
            "Rating must be between 1 and 5."
        );

    }


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

    $counselor_result =
        $counselor_query->get_result();


    if ($counselor_result->num_rows === 0) {

        response(
            false,
            "Selected counselor does not exist."
        );

    }


    // CHECK COMPLETED APPOINTMENT

    $appointment_query = $conn->prepare(

        "SELECT appointment_id

         FROM appointments

         WHERE student_id = ?
         AND counselor_id = ?
         AND status = 'Completed'

         LIMIT 1"

    );


    $appointment_query->bind_param(
        "ii",
        $student_id,
        $counselor_id
    );


    $appointment_query->execute();

    $appointment_result =
        $appointment_query->get_result();


    if ($appointment_result->num_rows === 0) {

        response(
            false,
            "You can submit feedback only after a completed appointment."
        );

    }


    // INSERT FEEDBACK

    $query = $conn->prepare(

        "INSERT INTO feedback
        (
            student_id,
            counselor_id,
            rating,
            comment
        )

        VALUES (?, ?, ?, ?)"

    );


    $query->bind_param(
        "iiis",
        $student_id,
        $counselor_id,
        $rating,
        $comment
    );


    if ($query->execute()) {

        response(
            true,
            "Feedback submitted successfully."
        );

    } else {

        response(
            false,
            "Unable to submit feedback."
        );

    }

}


// INVALID REQUEST

response(false, "Invalid request method.");

?>