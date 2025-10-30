<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

try {
    $sql = "SELECT
              r.room_id as id,
              r.room_number as title,
              r.location,
              r.capacity,
              r.description,
              r.price,
              ri.image_path as image,
              rt.type_name as room_type,
              u.full_name as owner_name
            FROM rooms r
            LEFT JOIN room_images ri ON ri.room_id = r.room_id AND ri.image_id = (SELECT MIN(image_id) FROM room_images WHERE room_id = r.room_id)
            LEFT JOIN room_types rt ON rt.room_type_id = r.room_type_id
            LEFT JOIN users u ON u.user_id = r.owner_id
            WHERE r.status = 'Available'
            ORDER BY r.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rooms = $stmt->fetchAll();

    echo json_encode(['success' => true, 'rooms' => $rooms]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
