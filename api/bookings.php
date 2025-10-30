<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
  require_role(['user','owner','admin']);
  $d = json_decode(file_get_contents('php://input'), true) ?? $_POST;

  $check_in = $d['checkin'] ?? null;
  $check_out = $d['checkout'] ?? null;
  $room_id = (int)($d['room_id'] ?? 0);
  $user_id = (int)$_SESSION['user']['id'];
  $payment_method = $d['paymentMethod'] ?? null;

  if (!$check_in || !$check_out || !$room_id || !$user_id || !$payment_method) {
    echo json_encode(['success' => false, 'message' => 'Please provide valid check-in, check-out dates, and room.']);
    exit;
  }

  // Check room availability in room_availability table
  $stmt = $pdo->prepare("SELECT 1 FROM room_availability WHERE room_id = ? AND available_date BETWEEN ? AND ? AND is_available = 0");
  $stmt->execute([$room_id, $check_in, $check_out]);
  if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Room not available for selected dates']);
    exit;
  }

  // Check room availability
  $stmt = $pdo->prepare("SELECT price, capacity FROM rooms WHERE room_id = ? AND status = 'Available'");
  $stmt->execute([$room_id]);
  $room = $stmt->fetch();

  if (!$room) {
    echo json_encode(['success' => false, 'message' => 'Room not available.']);
    exit;
  }

  // Calculate total amount (simple: price per night * nights)
  $checkin_date = new DateTime($check_in);
  $checkout_date = new DateTime($check_out);
  $nights = $checkout_date->diff($checkin_date)->days;
  $total_amount = $room['price'] * $nights;

  try {
    $pdo->beginTransaction();

  // Insert booking (mark as Pending)
  $sql = "INSERT INTO bookings (user_id, room_id, check_in, check_out, total_amount, status, created_at) VALUES (?, ?, ?, ?, ?, 'Pending', NOW())";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$user_id, $room_id, $check_in, $check_out, $total_amount]);

    $booking_id = $pdo->lastInsertId();

    // Normalize payment method to match payments.payment_method enum values
    $pm = strtolower((string)$payment_method);
    switch ($pm) {
      case 'gcash': $pm_db = 'GCash'; break;
      case 'paymaya':
      case 'paymaya': $pm_db = 'Maya'; break;
      case 'bank_transfer':
      case 'bank': $pm_db = 'Bank'; break;
      case 'cod':
      case 'cash':
      default: $pm_db = 'Cash'; break;
    }

    // Insert initial payment record
    $payment_sql = "INSERT INTO payments (booking_id, amount_paid, payment_method, payment_status, paid_at) VALUES (?, ?, ?, 'Pending', NOW())";
    $payment_stmt = $pdo->prepare($payment_sql);
    $payment_stmt->execute([$booking_id, $total_amount, $pm_db]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Booking created!', 'booking_id' => $booking_id]);
  } catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error processing booking: ' . $e->getMessage()]);
  }
} elseif ($method === 'GET') {
  require_role(['user','owner','admin']);
  $user_id = (int)$_SESSION['user']['id'];
  $role = $_SESSION['user']['role_name'];

  if ($role === 'User') {
    $sql = "SELECT b.*, r.room_number, r.location FROM bookings b JOIN rooms r ON b.room_id = r.room_id WHERE b.user_id = ?";
  } elseif ($role === 'Owner') {
    $sql = "SELECT b.*, r.room_number, r.location, u.full_name as user_name FROM bookings b JOIN rooms r ON b.room_id = r.room_id JOIN users u ON b.user_id = u.user_id WHERE r.owner_id = ?";
  } elseif ($role === 'Admin') {
    $sql = "SELECT b.*, r.room_number, r.location, u.full_name as user_name, u2.full_name as owner_name FROM bookings b JOIN rooms r ON b.room_id = r.room_id JOIN users u ON b.user_id = u.user_id JOIN users u2 ON r.owner_id = u2.user_id";
  }

  $stmt = $pdo->prepare($sql);
  if ($role === 'User' || $role === 'Owner') {
    $stmt->execute([$user_id]);
  } else {
    $stmt->execute();
  }
  $bookings = $stmt->fetchAll();

  echo json_encode(['success' => true, 'bookings' => $bookings]);
}
