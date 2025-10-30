<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
  require_role(['admin']);
  $d = json_decode(file_get_contents('php://input'), true) ?? $_POST;

  $payment_id = (int)($d['payment_id'] ?? 0);
  $booking_id = (int)($d['booking_id'] ?? 0);
  $refund_amount = (float)($d['refund_amount'] ?? 0);
  $reason = trim($d['reason'] ?? '');
  $user_id = (int)$_SESSION['user']['id'];

  if ($payment_id <= 0 || $booking_id <= 0 || $refund_amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment, booking, or amount']);
    exit;
  }

  $pdo->beginTransaction();

  $stmt = $pdo->prepare("INSERT INTO refunds (payment_id, booking_id, refund_amount, reason, refunded_by) VALUES (?, ?, ?, ?, ?)");
  $stmt->execute([$payment_id, $booking_id, $refund_amount, $reason, $user_id]);

  $stmt = $pdo->prepare("UPDATE payments SET payment_status = 'Refunded' WHERE payment_id = ?");
  $stmt->execute([$payment_id]);

  $pdo->commit();

  echo json_encode(['success' => true, 'message' => 'Refund processed']);
} elseif ($method === 'GET') {
  require_role(['admin']);
  $stmt = $pdo->prepare("SELECT r.*, b.room_id, rm.room_number FROM refunds r JOIN bookings b ON r.booking_id = b.booking_id JOIN rooms rm ON b.room_id = rm.room_id ORDER BY r.refund_date DESC");
  $stmt->execute();
  $refunds = $stmt->fetchAll();

  echo json_encode(['success' => true, 'refunds' => $refunds]);
}
