<?php
// List assigned appointments and apply valid counselor status changes.
session_start();
header('Content-Type: application/json');
// Send the counselor action result and stop processing the request.
function counselorResponse($success, $message, $extra = []) {
    echo json_encode(array_merge(['success'=>$success, 'message'=>$message], $extra));
    exit;
}
if (!isset($_SESSION['user_id'])) counselorResponse(false, 'Please login first.');
if (($_SESSION['role'] ?? '') !== 'counselor') counselorResponse(false, 'Only counselors can access this page.');
require_once 'db.php';
$counselorId = (int)$_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = $conn->prepare('SELECT a.appointment_id, a.appointment_date, a.appointment_time,
        a.reason, a.status, u.name AS student_name FROM appointments a
        JOIN students s ON a.student_id = s.student_id JOIN users u ON s.user_id = u.user_id
        WHERE a.counselor_id = ? ORDER BY a.appointment_date, a.appointment_time');
    $query->bind_param('i', $counselorId);
    $query->execute();
    counselorResponse(true, 'Appointments loaded.', ['appointments'=>$query->get_result()->fetch_all(MYSQLI_ASSOC)]);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') counselorResponse(false, 'Invalid request.');
$appointmentId = filter_var($_POST['appointment_id'] ?? null, FILTER_VALIDATE_INT);
$status = $_POST['status'] ?? '';
if (!$appointmentId || !in_array($status, ['Approved','Rejected','Completed'], true)) {
    counselorResponse(false, 'Invalid appointment or status.');
}

// Change an owned appointment only from its expected previous status.
$previousStatus = $status === 'Completed' ? 'Approved' : 'Pending';
$query = $conn->prepare('UPDATE appointments SET status = ? WHERE appointment_id = ? AND counselor_id = ? AND status = ?');
$query->bind_param('siis', $status, $appointmentId, $counselorId, $previousStatus);
$query->execute();
if ($query->affected_rows !== 1) counselorResponse(false, 'Appointment not found or already processed.');
counselorResponse(true, 'Appointment '.strtolower($status).' successfully.');
