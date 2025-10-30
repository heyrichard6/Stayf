<?php
header('Content-Type: application/json');
include '../includes/db.php';

$conn = $pdo; // Use $pdo from includes/db.php

$role = $_GET['role'] ?? '';
$user_id = $_GET['user_id'] ?? 0;

$total_rooms = 0;
$total_bookings = 0;
$pending_approvals = 0;
$recent_bookings = [];

try {
    if ($role === 'owner') {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM rooms WHERE owner_id = ?");
        $stmt->execute([$user_id]);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM rooms");
        $stmt->execute();
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_rooms = $result['total'];

    if ($role === 'guest') {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE guest_id = ?");
        $stmt->execute([$user_id]);
    } elseif ($role === 'owner') {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings b JOIN rooms r ON b.room_id = r.id WHERE r.owner_id = ?");
        $stmt->execute([$user_id]);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings");
        $stmt->execute();
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_bookings = $result['total'];

    if ($role === 'manager') {
        $stmt = $conn->prepare("SELECT COUNT(*) as pending FROM bookings WHERE status = 'waiting_approval'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $pending_approvals = $result['pending'];
    } elseif ($role === 'owner') {
        $stmt = $conn->prepare("SELECT COUNT(*) as pending FROM bookings b JOIN rooms r ON b.room_id = r.id WHERE b.status = 'waiting_approval' AND r.owner_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $pending_approvals = $result['pending'];
    }

    // Recent bookings
    if ($role === 'owner') {
        $stmt = $conn->prepare("SELECT b.*, r.room_number as room_name, u.full_name as guest_name FROM bookings b JOIN rooms r ON b.room_id = r.room_id JOIN users u ON b.user_id = u.user_id WHERE r.owner_id = ? ORDER BY b.created_at DESC LIMIT 5");
        $stmt->execute([$user_id]);
    } else {
        $stmt = $conn->prepare("SELECT b.*, r.room_number as room_name, u.full_name as guest_name FROM bookings b JOIN rooms r ON b.room_id = r.room_id JOIN users u ON b.user_id = u.user_id ORDER BY b.created_at DESC LIMIT 5");
        $stmt->execute();
    }
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($res as $row) {
        $recent_bookings[] = [
            "guest_name" => $row['guest_name'],
            "room_name" => $row['room_name'],
            "booking_date" => $row['created_at'],
            "status" => $row['status']
        ];
    }

    echo json_encode([
        "success" => true,
        "total_rooms" => $total_rooms,
        "total_bookings" => $total_bookings,
        "pending_approvals" => $pending_approvals,
        "recent_bookings" => $recent_bookings
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>
