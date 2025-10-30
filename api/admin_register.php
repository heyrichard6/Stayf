<?php
// api/admin_register.php
declare(strict_types=1);
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['SuperAdmin', 'Manager']); // Only SuperAdmins and Managers can create admin accounts

$name     = trim($_POST['name'] ?? '');
$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = (string)($_POST['password'] ?? '');
$role     = trim($_POST['role'] ?? 'Staff');
$contact  = trim($_POST['contact_number'] ?? '');

if ($name === '' || $username === '' || $password === '') {
  echo json_encode(['success'=>false,'message'=>'Please enter name, username, and password.']);
  exit;
}

if (!in_array($role, ['Staff', 'Encoder', 'Manager', 'SuperAdmin'])) {
  echo json_encode(['success'=>false,'message'=>'Invalid role selected.']);
  exit;
}

try {
  $pdo->beginTransaction();

  // Check for duplicate username
  $dup = $pdo->prepare("SELECT 1 FROM admins WHERE username=? LIMIT 1");
  $dup->execute([$username]);
  if ($dup->fetch()) {
    $pdo->rollBack();
    echo json_encode(['success'=>false,'message'=>'Username already exists.']);
    exit;
  }

  // Check for duplicate email if provided
  if ($email !== '') {
    $dup = $pdo->prepare("SELECT 1 FROM admins WHERE email=? LIMIT 1");
    $dup->execute([$email]);
    if ($dup->fetch()) {
      $pdo->rollBack();
      echo json_encode(['success'=>false,'message'=>'Email already exists.']);
      exit;
    }
  }

  // Insert new admin (password stored as plain text like existing admins)
  $st = $pdo->prepare("INSERT INTO admins (name, username, password, email, contact_number, role, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
  $st->execute([$name, $username, $password, $email, $contact, $role]);

  $pdo->commit();
  echo json_encode(['success'=>true,'message'=>'Admin account created successfully.']);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
