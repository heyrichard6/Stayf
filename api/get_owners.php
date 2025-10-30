<?php
header('Content-Type: application/json');
include '../includes/db.php';

try {
  $stmt = $pdo->query("SELECT owner_id as id, name, email, status FROM owners ORDER BY owner_id DESC");
  $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(["success" => true, "owners" => $owners]);
} catch (Exception $e) {
  echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
