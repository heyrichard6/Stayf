<?php
header('Content-Type: application/json');
include '../includes/db.php';

try {
  $stmt = $pdo->query("SELECT user_id as id, full_name as name, email, role, status FROM users ORDER BY user_id DESC");
  $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(["success" => true, "users" => $users]);
} catch (Exception $e) {
  echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
