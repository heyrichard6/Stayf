<?php

declare(strict_types=1);
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../includes/db.php';

$user = $_SESSION['user'] ?? null;
$role = strtolower($user['role_name'] ?? '');
if (!$user || !in_array($role, ['admin','owner'], true)) {
  http_response_code(403);
  echo json_encode(['success'=>false,'message'=>'Not allowed']); exit;
}

$room_number = trim($_POST['title'] ?? '');
if ($room_number === '') { echo json_encode(['success'=>false,'message'=>'Room number is required']); exit; }

$location    = trim($_POST['location'] ?? '');
$capacity    = $_POST['capacity'] !== '' ? (int)$_POST['capacity'] : 1;
$price       = $_POST['price_per_night'] !== '' ? (float)$_POST['price_per_night'] : null;
$description = trim($_POST['description'] ?? '');
$room_type_id = 1; // Default room type, can be updated later
$owner_id    = (int)($user['owner_id'] ?? 0);

$image_path = '';
try {
  if (!empty($_FILES['image']['name'])) {
    $image_path = save_uploaded_image_to_assets($_FILES['image']);
  }
} catch (Throwable $e) {
  echo json_encode(['success'=>false,'message'=>'Image error: '.$e->getMessage()]); exit;
}

try {
  $pdo->beginTransaction();

  $st = $pdo->prepare("
    INSERT INTO rooms (room_number, owner_id, room_type_id, description, capacity, price, location, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
  ");
  $st->execute([$room_number, $owner_id, $room_type_id, $description, $capacity, $price, $location]);

  $room_id = $pdo->lastInsertId();

  if ($image_path) {
    $st2 = $pdo->prepare("INSERT INTO room_images (room_id, image_path, uploaded_at) VALUES (?, ?, NOW())");
    $st2->execute([$room_id, $image_path]);
  }

  $pdo->commit();
  echo json_encode(['success'=>true, 'id'=>$room_id]);
} catch (Throwable $e) {
  $pdo->rollBack();
  echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}


function save_uploaded_image_to_assets(array $file): string {
  if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed.');
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime  = $finfo->file($file['tmp_name']);
  $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
  if (!isset($allowed[$mime])) throw new RuntimeException('Invalid image type.');
  if ($file['size'] > 5 * 1024 * 1024) throw new RuntimeException('File too large (max 5MB).');

  $ext  = $allowed[$mime];
  $name = bin2hex(random_bytes(8)) . '.' . $ext;

  $projectRoot = dirname(__DIR__);
  $uploadDirFs = $projectRoot . '/assets/uploads/';
  if (!is_dir($uploadDirFs)) { @mkdir($uploadDirFs, 0777, true); }
  $dest = $uploadDirFs . $name;

  if (!move_uploaded_file($file['tmp_name'], $dest)) {
    throw new RuntimeException('Failed to move uploaded file.');
  }

  return 'assets/uploads/' . $name;
}
