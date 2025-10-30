<?php
// stay/api/bookings_create.php
declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$user = require_auth();
$pdo = get_db();

// Inputs
$room_id = (int)($_POST['room_id'] ?? 0);
$start_date = trim($_POST['start_date'] ?? '');
$end_date   = trim($_POST['end_date'] ?? '');
$guests     = (int)($_POST['guests'] ?? 1);
$extras     = $_POST['extras'] ?? [];          // may be array or JSON (UI sends array)
$notes      = trim($_POST['notes'] ?? '');

if ($room_id <= 0 || $start_date === '' || $end_date === '') {
  echo json_encode(['success' => false, 'message' => 'Missing required fields']); exit;
}

if (!is_array($extras)) {
  // If UI sent JSON string
  $decoded = json_decode((string)$extras, true);
  if (is_array($decoded)) $extras = $decoded; else $extras = [];
}

try {
  // Get room price for total calculation
  $roomStmt = $pdo->prepare("SELECT price FROM rooms WHERE room_id = ?");
  $roomStmt->execute([$room_id]);
  $room = $roomStmt->fetch(PDO::FETCH_ASSOC);
  if (!$room) {
    echo json_encode(['success' => false, 'message' => 'Room not found']); exit;
  }

  $start = new DateTime($start_date);
  $end = new DateTime($end_date);
  $nights = $start->diff($end)->days;
  $total_amount = $room['price'] * $nights;

  $stmt = $pdo->prepare("
    INSERT INTO bookings (user_id, room_id, check_in, check_out, guests, total_amount, status, created_at)
    VALUES (:user_id, :room_id, :check_in, :check_out, :guests, :total_amount, 'Pending', NOW())
  ");
  $stmt->execute([
    ':user_id'     => $user['user_id'],
    ':room_id'     => $room_id,
    ':check_in'    => $start_date,
    ':check_out'   => $end_date,
    ':guests'      => $guests,
    ':total_amount' => $total_amount,
  ]);

  $booking_id = (int)$pdo->lastInsertId();

  // Create payment record
  $paymentStmt = $pdo->prepare("
    INSERT INTO payments (booking_id, payment_status)
    VALUES (:booking_id, 'Pending')
  ");
  $paymentStmt->execute([':booking_id' => $booking_id]);

  echo json_encode(['success' => true, 'booking_id' => $booking_id]);
} catch (Throwable $e) {
  echo json_encode(['success' => false, 'message' => 'DB error: '.$e->getMessage()]);
}
