<?php
// api/admin_list.php
declare(strict_types=1);
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['SuperAdmin', 'Manager']); // Only SuperAdmins and Managers can view admin list

try {
  $stmt = $pdo->prepare("SELECT admin_id, name, username, email, contact_number, role, status, created_at FROM admins ORDER BY created_at DESC");
  $stmt->execute();
  $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(['success'=>true, 'admins'=>$admins]);
} catch (Throwable $e) {
  echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
