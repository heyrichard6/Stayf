<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  $stmt = $pdo->prepare("SELECT * FROM discounts WHERE status = 'Active' AND (end_date IS NULL OR end_date >= CURDATE()) ORDER BY created_at DESC");
  $stmt->execute();
  $discounts = $stmt->fetchAll();

  echo json_encode(['success' => true, 'discounts' => $discounts]);
} elseif ($method === 'POST' && isset($_GET['apply'])) {
  require_role(['user']);
  $d = json_decode(file_get_contents('php://input'), true) ?? $_POST;

  $code = trim($d['code'] ?? '');
  $booking_id = (int)($d['booking_id'] ?? 0);

  if ($code === '' || $booking_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid code or booking']);
    exit;
  }

  $stmt = $pdo->prepare("SELECT * FROM discounts WHERE code = ? AND status = 'Active'");
  $stmt->execute([$code]);
  $discount = $stmt->fetch();

  if (!$discount) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired discount code']);
    exit;
  }

  // Apply discount to booking
  $discount_amount = ($discount['discount_rate'] / 100) * 100; // Assuming fixed discount for simplicity
  $stmt = $pdo->prepare("UPDATE bookings SET total_amount = total_amount - ? WHERE booking_id = ?");
  $stmt->execute([$discount_amount, $booking_id]);

  echo json_encode(['success' => true, 'message' => 'Discount applied', 'discount_amount' => $discount_amount]);
}
