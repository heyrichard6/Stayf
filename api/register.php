<?php
// api/register.php
declare(strict_types=1);
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../includes/db.php';

$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = (string)($_POST['password'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$role     = trim($_POST['role'] ?? 'user'); // user, admin, owner

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
  echo json_encode(['success'=>false,'message'=>'Please enter a name, valid email, and a password.']);
  exit;
}

if (!in_array($role, ['user', 'admin', 'owner'])) {
  echo json_encode(['success'=>false,'message'=>'Invalid role.']);
  exit;
}

try {
  $pdo->beginTransaction();

  $hash = password_hash($password, PASSWORD_DEFAULT);

  if ($role === 'user') {
    // Check duplicate email in users
    $dup = $pdo->prepare("SELECT 1 FROM users WHERE email=? LIMIT 1");
    $dup->execute([$email]);
    if ($dup->fetch()) {
      $pdo->rollBack();
      echo json_encode(['success'=>false,'message'=>'Email already registered.']);
      exit;
    }
    $st = $pdo->prepare("INSERT INTO users (full_name, email, password, contact_number, created_at) VALUES (?, ?, ?, ?, NOW())");
    $st->execute([$name, $email, $hash, $phone]);
  } elseif ($role === 'admin') {
    // Check duplicate email/username in admins
    $dup = $pdo->prepare("SELECT 1 FROM admins WHERE email=? OR username=? LIMIT 1");
    $dup->execute([$email, $name]);
    if ($dup->fetch()) {
      $pdo->rollBack();
      echo json_encode(['success'=>false,'message'=>'Email or username already registered.']);
      exit;
    }
    $st = $pdo->prepare("INSERT INTO admins (name, username, password, email, contact_number, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $st->execute([$name, $name, $hash, $email, $phone]);
  } elseif ($role === 'owner') {
    // Check duplicate email in owners
    $dup = $pdo->prepare("SELECT 1 FROM owners WHERE email=? LIMIT 1");
    $dup->execute([$email]);
    if ($dup->fetch()) {
      $pdo->rollBack();
      echo json_encode(['success'=>false,'message'=>'Email already registered.']);
      exit;
    }
    $st = $pdo->prepare("INSERT INTO owners (name, email, password, contact_number, created_at) VALUES (?, ?, ?, ?, NOW())");
    $st->execute([$name, $email, $hash, $phone]);
  }

  $pdo->commit();
  echo json_encode(['success'=>true,'message'=>'Registration successful. Please log in.']);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
