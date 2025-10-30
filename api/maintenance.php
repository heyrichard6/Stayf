<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
  require_role(['admin','owner']);
  $d = json_decode(file_get_contents('php://input'), true) ?? $_POST;

  $room_id = (int)($d['room_id'] ?? 0);
  $issue_description = trim($d['issue'] ?? '');
  $staff_id = isset($d['staff_id']) ? (int)$d['staff_id'] : null;
  $user_id = (int)$_SESSION['user']['id'];

  if ($room_id <= 0 || $issue_description === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid room or issue description']);
    exit;
  }

  $stmt = $pdo->prepare("INSERT INTO maintenance_records (room_id, staff_id, verified_by, issue_description, date_reported) VALUES (?, ?, ?, ?, CURDATE())");
  $stmt->execute([$room_id, $staff_id, $user_id, $issue_description]);

  echo json_encode(['success' => true, 'message' => 'Maintenance record created']);
} elseif ($method === 'GET') {
  require_role(['admin','owner','staff']);
  $room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : null;

  if ($room_id) {
    $stmt = $pdo->prepare("SELECT m.*, r.room_number, s.name as staff_name FROM maintenance_records m JOIN rooms r ON m.room_id = r.room_id LEFT JOIN staff_accounts s ON m.staff_id = s.staff_id WHERE m.room_id = ? ORDER BY m.date_reported DESC");
    $stmt->execute([$room_id]);
  } else {
    $stmt = $pdo->prepare("SELECT m.*, r.room_number, s.name as staff_name FROM maintenance_records m JOIN rooms r ON m.room_id = r.room_id LEFT JOIN staff_accounts s ON m.staff_id = s.staff_id ORDER BY m.date_reported DESC");
    $stmt->execute();
  }

  $records = $stmt->fetchAll();
  echo json_encode(['success' => true, 'maintenance' => $records]);
} elseif ($method === 'PUT') {
  require_role(['admin','staff']);
  parse_str(file_get_contents('php://input'), $d);

  $maintenance_id = (int)($d['maintenance_id'] ?? 0);
  $status = $d['status'] ?? '';
  $date_fixed = $d['date_fixed'] ?? null;

  if ($maintenance_id <= 0 || !in_array($status, ['Pending','In Progress','Completed'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
  }

  $stmt = $pdo->prepare("UPDATE maintenance_records SET status = ?, date_fixed = ? WHERE maintenance_id = ?");
  $stmt->execute([$status, $date_fixed, $maintenance_id]);

  echo json_encode(['success' => true, 'message' => 'Maintenance record updated']);
}
