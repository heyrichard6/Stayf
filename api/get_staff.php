<?php
header('Content-Type: application/json');
include '../includes/db.php';

try {
  $stmt = $pdo->query("SELECT staff_id as id, name, position, contact_number, status FROM staff_accounts ORDER BY staff_id DESC");
  $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(["success" => true, "staff" => $staff]);
} catch (Exception $e) {
  echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
