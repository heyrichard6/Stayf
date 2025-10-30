<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  require_role(['admin']);
  $stmt = $pdo->prepare("SELECT * FROM settings LIMIT 1");
  $stmt->execute();
  $settings = $stmt->fetch();

  if (!$settings) {
  $settings = [
      'hotel_name' => 'StayFind Property Management',
      'contact_email' => '',
      'contact_phone' => '',
      'address' => '',
      'tax_rate' => 0.00,
      'logo_path' => ''
    ];
  }

  echo json_encode(['success' => true, 'settings' => $settings]);
} elseif ($method === 'POST') {
  require_role(['admin']);
  $d = json_decode(file_get_contents('php://input'), true) ?? $_POST;

  $hotel_name = trim($d['hotel_name'] ?? '');
  $contact_email = trim($d['contact_email'] ?? '');
  $contact_phone = trim($d['contact_phone'] ?? '');
  $address = trim($d['address'] ?? '');
  $tax_rate = (float)($d['tax_rate'] ?? 0);

  $stmt = $pdo->prepare("SELECT 1 FROM settings LIMIT 1");
  $stmt->execute();
  $exists = $stmt->fetch();

  if ($exists) {
    $stmt = $pdo->prepare("UPDATE settings SET hotel_name = ?, contact_email = ?, contact_phone = ?, address = ?, tax_rate = ?");
    $stmt->execute([$hotel_name, $contact_email, $contact_phone, $address, $tax_rate]);
  } else {
    $stmt = $pdo->prepare("INSERT INTO settings (hotel_name, contact_email, contact_phone, address, tax_rate) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$hotel_name, $contact_email, $contact_phone, $address, $tax_rate]);
  }

  echo json_encode(['success' => true, 'message' => 'Settings updated']);
}
