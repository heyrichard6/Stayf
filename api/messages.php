<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
  require_role(['user','admin','owner']);
  $d = json_decode(file_get_contents('php://input'), true) ?? $_POST;

  $receiver_id = (int)($d['receiver_id'] ?? 0);
  $message_text = trim($d['message'] ?? '');
  $sender_id = (int)$_SESSION['user']['id'];

  if ($receiver_id <= 0 || $message_text === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid receiver or message']);
    exit;
  }

  $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)");
  $stmt->execute([$sender_id, $receiver_id, $message_text]);

  echo json_encode(['success' => true, 'message' => 'Message sent']);
} elseif ($method === 'GET') {
  require_role(['user','admin','owner']);
  $user_id = (int)$_SESSION['user']['id'];

  $stmt = $pdo->prepare("
    SELECT m.*, u.full_name as sender_name, u2.full_name as receiver_name
    FROM messages m
    LEFT JOIN users u ON m.sender_id = u.user_id
    LEFT JOIN users u2 ON m.receiver_id = u2.user_id
    WHERE m.sender_id = ? OR m.receiver_id = ?
    ORDER BY m.sent_at DESC
  ");
  $stmt->execute([$user_id, $user_id]);
  $messages = $stmt->fetchAll();

  echo json_encode(['success' => true, 'messages' => $messages]);
}
