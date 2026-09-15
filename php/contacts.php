<?php
// Return available contacts and the current user's message count.
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success'=>false, 'message'=>'Please login first.']));
}
require_once 'db.php';
$id = (int)$_SESSION['user_id'];
if ($_SESSION['role'] === 'student') {
    $result = $conn->query("SELECT user_id, name FROM users WHERE role = 'counselor' ORDER BY name");
} elseif ($_SESSION['role'] === 'counselor') {
    $query = $conn->prepare("SELECT u.user_id, u.name FROM users u WHERE u.role = 'student' AND
        (EXISTS (SELECT 1 FROM messages m WHERE (m.sender_id = u.user_id AND m.receiver_id = ?)
        OR (m.receiver_id = u.user_id AND m.sender_id = ?)) OR EXISTS
        (SELECT 1 FROM students s JOIN appointments a ON a.student_id = s.student_id
        WHERE s.user_id = u.user_id AND a.counselor_id = ?)) ORDER BY u.name");
    $query->bind_param('iii', $id, $id, $id);
    $query->execute();
    $result = $query->get_result();
} else {
    http_response_code(403);
    exit(json_encode(['success'=>false, 'message'=>'Student or counselor access required.']));
}
$query = $conn->prepare('SELECT COUNT(*) AS total FROM messages WHERE sender_id = ? OR receiver_id = ?');
$query->bind_param('ii', $id, $id);
$query->execute();
echo json_encode(['success'=>true, 'contacts'=>$result->fetch_all(MYSQLI_ASSOC),
    'message_count'=>(int)$query->get_result()->fetch_assoc()['total']]);
