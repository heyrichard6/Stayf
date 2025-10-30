<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  require_role(['admin']);
  $report_type = $_GET['type'] ?? '';
  $start_date = $_GET['start_date'] ?? date('Y-m-01');
  $end_date = $_GET['end_date'] ?? date('Y-m-t');

  if (!in_array($report_type, ['Sales','Occupancy','Revenue','UserActivity'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid report type']);
    exit;
  }

  $user_id = (int)$_SESSION['user']['id'];

  if ($report_type === 'Sales') {
    $stmt = $pdo->prepare("
      SELECT COUNT(*) as total_bookings, SUM(total_amount) as total_revenue
      FROM bookings
      WHERE created_at BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    $data = $stmt->fetch();
  } elseif ($report_type === 'Revenue') {
    $stmt = $pdo->prepare("
      SELECT SUM(amount_paid) as total_payments, SUM(refund_amount) as total_refunds
      FROM payments p
      LEFT JOIN refunds r ON p.payment_id = r.payment_id
      WHERE p.paid_at BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    $data = $stmt->fetch();
  } elseif ($report_type === 'Occupancy') {
    $stmt = $pdo->prepare("
      SELECT COUNT(DISTINCT room_id) as occupied_rooms,
             (SELECT COUNT(*) FROM rooms) as total_rooms
      FROM bookings
      WHERE check_in <= ? AND check_out >= ? AND status IN ('Approved','CheckedIn')
    ");
    $stmt->execute([$end_date, $start_date]);
    $data = $stmt->fetch();
  } elseif ($report_type === 'UserActivity') {
    $stmt = $pdo->prepare("
      SELECT COUNT(DISTINCT user_id) as active_users,
             COUNT(*) as total_logins
      FROM login_logs
      WHERE login_time BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    $data = $stmt->fetch();
  }

  // Save report
  $stmt = $pdo->prepare("INSERT INTO reports (report_type, generated_by, report_period_start, report_period_end, total_bookings, total_revenue) VALUES (?, ?, ?, ?, ?, ?)");
  $stmt->execute([$report_type, $user_id, $start_date, $end_date, $data['total_bookings'] ?? 0, $data['total_revenue'] ?? 0]);

  echo json_encode(['success' => true, 'report' => $data]);
}
