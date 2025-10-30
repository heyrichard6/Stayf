<?php
// includes/auth.php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function current_user() {
  return $_SESSION['user'] ?? null;
}
function is_manager_like(?array $u): bool {
  $r = strtolower($u['role_name'] ?? '');
  return in_array($r, ['admin','owner','manager'], true);
}
function require_login_json() {
  if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit;
  }
}
function require_role(array $roles) {
  $u = current_user();
  $role = strtolower($u['role_name'] ?? '');
  // normalize allowed roles to lowercase for case-insensitive comparison
  $allowed = array_map('strtolower', $roles);
  if (!$u || !in_array($role, $allowed, true)) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Forbidden']);
    exit;
  }
}
function require_auth() {
  $u = current_user();
  if (!$u) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
  }
  return $u;
}
function get_db() {
  static $pdo = null;
  if ($pdo === null) {
    $host = 'localhost';
    $dbname = 'stayfind';
    $user = 'root';
    $pass = '';
    try {
      $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
      ]);
    } catch (PDOException $e) {
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => 'Database connection failed']);
      exit;
    }
  }
  return $pdo;
}
