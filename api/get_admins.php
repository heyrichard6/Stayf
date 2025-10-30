<?php
header('Content-Type: application/json');
include '../includes/db.php';

try {
  $stmt = $pdo->query("SELECT admin_id as id, name, email, role, status FROM admins ORDER BY admin_id DESC");
  $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(["success" => true, "admins" => $admins]);
} catch (Exception $e) {
  echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
