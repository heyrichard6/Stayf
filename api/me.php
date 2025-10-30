<?php
declare(strict_types=1);
header('Content-Type: application/json');
session_start();
$user = $_SESSION['user'] ?? null;
if ($user) {
  // Ensure session is still valid
  if (empty($user['id']) || empty($user['role_name'])) {
    session_destroy();
    echo json_encode(['user' => null]);
    exit;
  }
}
echo json_encode(['user' => $user]);
