<?php
require_once 'includes/db.php';
$st = $pdo->prepare('SELECT r.room_id as id, r.room_number as title, r.capacity, r.description, r.price, ri.image_path as image, rt.type_name as room_type, u.full_name as owner_name, r.status FROM rooms r LEFT JOIN room_images ri ON ri.room_id = r.room_id AND ri.image_id = (SELECT MIN(image_id) FROM room_images WHERE room_id = r.room_id) LEFT JOIN room_types rt ON rt.room_type_id = r.room_type_id LEFT JOIN users u ON u.user_id = r.owner_id ORDER BY r.created_at DESC');
$st->execute();
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
echo 'Rows count: ' . count($rows) . PHP_EOL;
print_r($rows);
echo json_encode(['success'=>true, 'rooms'=>$rows]);
