<?php

declare(strict_types=1);
header('Content-Type: application/json');

session_start();
require_once __DIR__ . '/../includes/db.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = $_POST;

$login = trim((string)($input['email'] ?? ''));
$pass  = (string)($input['password'] ?? '');

if ($login === '' || $pass === '') {
  http_response_code(400);
  echo json_encode(['success'=>false, 'message'=>'Please enter your email/username and password.']);
  exit;
}

try {
  $loginLower = mb_strtolower($login);

  // Check admins table first
  $sql = "SELECT admin_id as id, name, email, username, password, role as role_name FROM admins WHERE LOWER(email) = ? OR LOWER(username) = ? LIMIT 1";
  $st = $pdo->prepare($sql);
  $st->execute([$loginLower, $loginLower]);
  $u = $st->fetch();

  if (!$u) {
  // Check users table
  $sql = "SELECT user_id as id, full_name as name, email, password, 'User' as role_name FROM users WHERE LOWER(email) = ? LIMIT 1";
  $st = $pdo->prepare($sql);
  $st->execute([$loginLower]);
  $u = $st->fetch();
  }

  if (!$u) {
    // Check owners table
    $sql = "SELECT owner_id as id, name, email, password, 'Owner' as role_name FROM owners WHERE LOWER(email) = ? LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([$loginLower]);
    $u = $st->fetch();
  }

  if (!$u) {
    http_response_code(401);
    echo json_encode(['success'=>false, 'message'=>"Account not found for '{$login}'."]);
    exit;
  }

  if (in_array($u['role_name'], ['SuperAdmin', 'Admin', 'Manager', 'Encoder', 'Staff'])) {
    $ok = $pass === $u['password'];
  } else {
    $ok = password_verify($pass, $u['password']);
  }

  if (!$ok) {
    http_response_code(401);
    echo json_encode(['success'=>false, 'message'=>'Incorrect password.']);
    exit;
  }

  $_SESSION['user'] = [
    'id'        => (int)$u['id'],
    'name'      => (string)($u['name'] ?: $u['email']),
    'email'     => (string)$u['email'],
    'role_name' => (string)$u['role_name'],
    'admin_id'  => in_array($u['role_name'], ['SuperAdmin', 'Admin', 'Manager', 'Encoder', 'Staff']) ? (int)$u['id'] : null,
    'owner_id'  => $u['role_name'] === 'Owner' ? (int)$u['id'] : null,
    'user_id'   => $u['role_name'] === 'User' ? (int)$u['id'] : null,
  ];
  session_regenerate_id(true);
  session_write_close(); // Ensure session is written

  // Log login attempt
  $user_type = in_array($u['role_name'], ['SuperAdmin', 'Admin', 'Manager', 'Encoder', 'Staff']) ? 'Admin' : ($u['role_name'] === 'Owner' ? 'Owner' : 'User');
  $stmt = $pdo->prepare("INSERT INTO login_logs (user_type, user_id, status) VALUES (?, ?, 'Success')");
  $stmt->execute([$user_type, $u['id']]);

  echo json_encode(['success'=>true, 'user'=>$_SESSION['user']]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false, 'message'=>'Error: '.$e->getMessage()]);
}
