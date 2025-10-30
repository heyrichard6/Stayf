<?php
// api/rooms_list.php
declare(strict_types=1);
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../includes/db.php';

$loc = trim($_GET['location'] ?? '');
$min = $_GET['min'] ?? '';
$max = $_GET['max'] ?? '';
$show_all = isset($_GET['show_all']) && $_GET['show_all'] == '1';

$where = [];
$vals  = [];

if ($loc !== '') { $where[] = 'r.location = ?'; $vals[] = $loc; }
if ($min !== '' && is_numeric($min)) { $where[] = 'r.price >= ?'; $vals[] = (float)$min; }
if ($max !== '' && is_numeric($max)) { $where[] = 'r.price <= ?'; $vals[] = (float)$max; }

$sql = "SELECT
          r.room_id as id,
          r.room_number as title,
          r.location,
          r.capacity,
          r.description,
          r.price,
          ri.image_path as image,
          rt.type_name as room_type,
          u.full_name as owner_name,
          r.status,
          CASE WHEN EXISTS (
            SELECT 1 FROM bookings b
            WHERE b.room_id = r.room_id
            AND b.status = 'Approved'
            AND b.check_out >= CURDATE()
          ) THEN 1 ELSE 0 END as is_booked
        FROM rooms r
        LEFT JOIN room_images ri ON ri.room_id = r.room_id AND ri.image_id = (SELECT MIN(image_id) FROM room_images WHERE room_id = r.room_id)
        LEFT JOIN room_types rt ON rt.room_type_id = r.room_type_id
        LEFT JOIN users u ON u.user_id = r.owner_id";
if (!$show_all) {
  $sql .= " WHERE r.room_id NOT IN (
    SELECT DISTINCT b.room_id
    FROM bookings b
    WHERE b.status = 'Approved'
    AND b.check_out >= CURDATE()
  )";
}
if ($where) $sql .= ($show_all ? " WHERE " : " AND ") . implode(' AND ', $where);
$sql .= " ORDER BY r.created_at DESC";

try {
  $st = $pdo->prepare($sql);
  $st->execute($vals);
  $rows = $st->fetchAll();
  echo json_encode(['success'=>true, 'rooms'=>$rows]);
} catch (Throwable $e) {
  echo json_encode(['success'=>false, 'message'=>'Error: '.$e->getMessage()]);
}
