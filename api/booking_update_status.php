<?php
// stay/api/booking_update_status.php
declare(strict_types=1);
header('Content-Type: application/json');
ini_set('display_errors','0'); ini_set('log_errors','1');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

try {
  $user = require_auth();
  $role = strtolower($user['role_name'] ?? '');
  if (!in_array($role, ['admin','owner','manager'], true)) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']); exit;
  }

  $pdo = get_db();
  $booking_id = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
  $action = strtolower(trim($_POST['action'] ?? ''));
  $reason = trim($_POST['reason'] ?? '');

  if ($booking_id <= 0 || !in_array($action, ['approve','reject'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']); exit;
  }

  if ($action === 'approve') {
    // Accept common pending values when approving
    $stmt = $pdo->prepare("UPDATE bookings SET status='Approved', approved_by=:uid WHERE booking_id=:id AND (status='Pending' OR status='pending' OR status='waiting_approval' OR status='Waiting_Approval')");
    $stmt->execute([':uid' => $user['admin_id'] ?? null, ':id' => $booking_id]);
  } else {
    $stmt = $pdo->prepare("UPDATE bookings SET status='Cancelled', approved_by=:uid WHERE booking_id=:id AND (status='Pending' OR status='pending' OR status='waiting_approval' OR status='Waiting_Approval')");
    $stmt->execute([':uid' => $user['admin_id'] ?? null, ':id' => $booking_id]);
  }

  if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => 'Booking not found or already processed']); exit;
  }
  // Fetch booking details to notify the user
  $bk = $pdo->prepare("SELECT booking_id, user_id, room_id, check_in, check_out FROM bookings WHERE booking_id = ?");
  $bk->execute([$booking_id]);
  $booking = $bk->fetch(PDO::FETCH_ASSOC);

  if ($booking) {
    // Fetch room title for nicer message
    $rstmt = $pdo->prepare("SELECT room_number FROM rooms WHERE room_id = ?");
    $rstmt->execute([$booking['room_id']]);
    $room = $rstmt->fetch(PDO::FETCH_ASSOC);
    $roomTitle = $room['room_number'] ?? 'your room';

    $userId = (int)$booking['user_id'];
    $adminId = $user['admin_id'] ?? null;
    $start = $booking['check_in'];
    $end = $booking['check_out'];

    if ($action === 'approve') {
      $message = "Your booking for {$roomTitle} ({$start} → {$end}) has been approved.";
    } else {
      $message = "Your booking for {$roomTitle} ({$start} → {$end}) has been cancelled.";
    }

    $ins = $pdo->prepare("INSERT INTO notifications (user_id, admin_id, message, status, created_at) VALUES (?, ?, ?, 'Unread', NOW())");
    $ins->execute([$userId, $adminId, $message]);
  }

  echo json_encode(['success' => true]);
} catch (Throwable $e) {
  echo json_encode(['success' => false, 'message' => 'Server error']);
}
