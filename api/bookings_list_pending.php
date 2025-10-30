<?php
// stay/api/bookings_list_pending.php
declare(strict_types=1);
header('Content-Type: application/json');
ini_set('display_errors','0'); ini_set('log_errors','1');

require_once __DIR__ . '/../includes/auth.php';

try {
  $user = require_auth();
  $role = strtolower($user['role_name'] ?? '');
  if (!in_array($role, ['superadmin','admin','owner','manager','staff','encoder'], true)) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']); exit;
  }

  $pdo = get_db();

  // Show bookings that are pending/awaiting approval. Accept common pending status values.
  // Select only columns that exist in the canonical schema to avoid SQL errors
  $sql = "
    SELECT
      b.booking_id AS id,
      b.room_id,
      b.check_in AS start_date,
      b.check_out AS end_date,
      b.total_amount,
      b.status,
      b.created_at,
      p.payment_method,
      p.reference_number AS payment_reference,
      p.proof_image AS payment_receipt,
      p.paid_at AS payment_uploaded_at,
      r.room_number AS room_title,
      r.location AS room_location,
      ri.image_path AS room_image,
      u.full_name AS user_name,
      u.email AS user_email
    FROM bookings b
    LEFT JOIN payments p ON p.booking_id = b.booking_id
    JOIN rooms r ON r.room_id = b.room_id
    JOIN users u ON u.user_id = b.user_id
    LEFT JOIN room_images ri ON ri.room_id = r.room_id AND ri.image_id = (SELECT MIN(image_id) FROM room_images WHERE room_id = r.room_id)
    WHERE b.status IN ('Pending','pending','waiting_approval','Waiting_Approval')
    ORDER BY b.created_at ASC
  ";
  $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(['success' => true, 'bookings' => $rows]);
} catch (Throwable $e) {
  echo json_encode(['success' => false, 'message' => 'Server error']);
}
