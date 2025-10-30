<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  require_role(['admin','owner']);
  $stmt = $pdo->prepare("SELECT s.*, r.room_number FROM staff_accounts s LEFT JOIN rooms r ON s.assigned_room = r.room_id ORDER BY s.created_at DESC");
  $stmt->execute();
  $staff = $stmt->fetchAll();

  echo json_encode(['success' => true, 'staff' => $staff]);
} elseif ($method === 'POST') {
  require_role(['admin','owner']);
  $d = json_decode(file_get_contents('php://input'), true) ?? $_POST;

  $name = trim($d['name'] ?? '');
  $position = trim($d['position'] ?? '');
  $contact_number = trim($d['contact_number'] ?? '');
  $assigned_room = isset($d['assigned_room']) ? (int)$d['assigned_room'] : null;

  if ($name === '') {
    echo json_encode(['success' => false, 'message' => 'Name is required']);
    exit;
  }

  $stmt = $pdo->prepare("INSERT INTO staff_accounts (name, position, contact_number, assigned_room) VALUES (?, ?, ?, ?)");
  $stmt->execute([$name, $position, $contact_number, $assigned_room]);

  echo json_encode(['success' => true, 'message' => 'Staff member added']);
} elseif ($method === 'PUT') {
  require_role(['admin','owner']);
  parse_str(file_get_contents('php://input'), $d);

  $staff_id = (int)($d['staff_id'] ?? 0);
  $status = $d['status'] ?? '';

  if ($staff_id <= 0 || !in_array($status, ['Active','Inactive'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
  }

  $stmt = $pdo->prepare("UPDATE staff_accounts SET status = ? WHERE staff_id = ?");
  $stmt->execute([$status, $staff_id]);

  echo json_encode(['success' => true, 'message' => 'Staff status updated']);
}
