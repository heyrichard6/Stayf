<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  require_role(['user','admin','owner']);
  $user_id = (int)$_SESSION['user']['id'];
  $role = $_SESSION['user']['role_name'];

  $where = [];
  $params = [];

  if ($role === 'User') {
    $where[] = 'user_id = ?';
    $params[] = $user_id;
  } elseif ($role === 'Admin') {
    $where[] = 'admin_id = ?';
    $params[] = $user_id;
  }

  $sql = "SELECT * FROM notifications WHERE " . implode(' OR ', $where) . " ORDER BY created_at DESC";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $notifications = $stmt->fetchAll();

  echo json_encode(['success' => true, 'notifications' => $notifications]);
} elseif ($method === 'POST' && isset($_GET['mark_read'])) {
  require_role(['user','admin','owner']);
  $notification_id = (int)($_POST['notification_id'] ?? 0);

  if ($notification_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit;
  }

  $stmt = $pdo->prepare("UPDATE notifications SET status = 'Read' WHERE notification_id = ?");
  $stmt->execute([$notification_id]);

  echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
}
