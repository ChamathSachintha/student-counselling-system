<?php
// Exchange messages between students and counselors using JSON responses.
session_start();
header('Content-Type: application/json');
// Send a messaging result with optional conversation data and end the request.
function messageResponse($success, $message, $extra = []) {
    echo json_encode(array_merge(['success'=>$success, 'message'=>$message], $extra));
    exit;
}
if (!isset($_SESSION['user_id'])) messageResponse(false, 'Please login first.');
require_once 'db.php';
$userId = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET','POST'], true)) messageResponse(false, 'Invalid request.');
$input = $method === 'POST' ? $_POST : $_GET;
$receiverId = filter_var($input['receiver_id'] ?? null, FILTER_VALIDATE_INT);
if (!$receiverId || $receiverId === $userId) messageResponse(false, 'Select another person to message.');

// Validate the recipient before reading or writing a conversation.
$query = $conn->prepare('SELECT role FROM users WHERE user_id = ?');
$query->bind_param('i', $receiverId);
$query->execute();
$recipient = $query->get_result()->fetch_assoc();
$role = $_SESSION['role'] ?? '';
if (!$recipient || !(($role === 'student' && $recipient['role'] === 'counselor') ||
    ($role === 'counselor' && $recipient['role'] === 'student'))) messageResponse(false, 'Select a valid student or counselor.');
if ($role === 'counselor') {
    $query = $conn->prepare('SELECT 1 FROM messages WHERE (sender_id = ? AND receiver_id = ?)
        OR (sender_id = ? AND receiver_id = ?) UNION SELECT 1 FROM appointments a
        JOIN students s ON a.student_id = s.student_id WHERE s.user_id = ? AND a.counselor_id = ? LIMIT 1');
    $query->bind_param('iiiiii', $userId, $receiverId, $receiverId, $userId, $receiverId, $userId);
    $query->execute();
    if (!$query->get_result()->num_rows) messageResponse(false, 'No conversation or appointment with this student.');
}
if ($method === 'POST') {
    $message = is_string($_POST['message'] ?? null) ? trim($_POST['message']) : '';
    if ($message === '' || strlen($message) > 10000) messageResponse(false, 'Enter a message of 1 to 10000 bytes.');
    $query = $conn->prepare('INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)');
    $query->bind_param('iis', $userId, $receiverId, $message);
    $query->execute();
    messageResponse(true, 'Message sent successfully.');
}

// Return only messages shared by the logged-in user and selected recipient.
$query = $conn->prepare('SELECT sender_id, receiver_id, message, sent_at FROM messages WHERE
    (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY sent_at, message_id');
$query->bind_param('iiii', $userId, $receiverId, $receiverId, $userId);
$query->execute();
messageResponse(true, 'Messages loaded.', ['messages'=>$query->get_result()->fetch_all(MYSQLI_ASSOC), 'user_id'=>$userId]);
