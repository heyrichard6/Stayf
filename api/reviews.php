<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
  require_role(['user']);
  $d = json_decode(file_get_contents('php://input'), true) ?? $_POST;

  $room_id = (int)($d['room_id'] ?? 0);
  $rating = (int)($d['rating'] ?? 0);
  $comment = trim($d['comment'] ?? '');
  $user_id = (int)$_SESSION['user']['id'];

  if ($room_id <= 0 || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid room or rating']);
    exit;
  }

  // Check if user has booked this room
  $stmt = $pdo->prepare("SELECT 1 FROM bookings WHERE user_id = ? AND room_id = ? AND status = 'CheckedOut'");
  $stmt->execute([$user_id, $room_id]);
  if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'You can only review rooms you have stayed in']);
    exit;
  }

  // Check if already reviewed
  $stmt = $pdo->prepare("SELECT 1 FROM reviews WHERE user_id = ? AND room_id = ?");
  $stmt->execute([$user_id, $room_id]);
  if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'You have already reviewed this room']);
    exit;
  }

  $stmt = $pdo->prepare("INSERT INTO reviews (user_id, room_id, rating, comment) VALUES (?, ?, ?, ?)");
  $stmt->execute([$user_id, $room_id, $rating, $comment]);

  echo json_encode(['success' => true, 'message' => 'Review submitted']);
} elseif ($method === 'GET') {
  $room_id = (int)($_GET['room_id'] ?? 0);

  if ($room_id > 0) {
    $stmt = $pdo->prepare("SELECT r.*, u.full_name FROM reviews r JOIN users u ON r.user_id = u.user_id WHERE r.room_id = ? ORDER BY r.created_at DESC");
    $stmt->execute([$room_id]);
    $reviews = $stmt->fetchAll();
    echo json_encode(['success' => true, 'reviews' => $reviews]);
  } else {
    echo json_encode(['success' => false, 'message' => 'Room ID required']);
  }
}
