<?php
// populate_db.php - Populate the stayfind database with sample data
declare(strict_types=1);
require_once __DIR__ . '/includes/db.php';

try {
    // Check if data already exists
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch()['count'];

    if ($count > 0) {
        echo "Database already has data. Skipping population.";
        exit;
    }

    // Insert sample users
    $pdo->exec("INSERT INTO users (full_name, email, password, contact_number, role, status) VALUES
        ('Admin User', 'admin@stayfind.com', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', '09123456789', 'Admin', 'Active'),
        ('John Owner', 'john@owner.com', '" . password_hash('owner123', PASSWORD_DEFAULT) . "', '09123456780', 'Owner', 'Active'),
        ('Jane Owner', 'jane@owner.com', '" . password_hash('owner123', PASSWORD_DEFAULT) . "', '09123456781', 'Owner', 'Active'),
        ('Regular User', 'user@example.com', '" . password_hash('user123', PASSWORD_DEFAULT) . "', '09123456782', 'User', 'Active')");

    // Insert room types
    $pdo->exec("INSERT INTO room_types (type_name, description, base_price) VALUES
        ('Standard Room', 'Basic room with essential amenities', 1500.00),
        ('Deluxe Room', 'Comfortable room with additional amenities', 2500.00),
        ('Suite', 'Luxury suite with premium amenities', 4000.00)");

    // Insert rooms
    $pdo->exec("INSERT INTO rooms (room_number, owner_id, room_type_id, description, capacity, price, status) VALUES
        ('Room 101', 2, 1, 'Cozy standard room in the heart of CDO', 2, 1500.00, 'Available'),
        ('Room 102', 2, 1, 'Comfortable standard room with city view', 2, 1600.00, 'Available'),
        ('Deluxe 201', 3, 2, 'Spacious deluxe room with balcony', 4, 2500.00, 'Available'),
        ('Suite 301', 3, 3, 'Luxury suite with kitchenette', 6, 4000.00, 'Available'),
        ('Room 103', 2, 1, 'Budget-friendly standard room', 2, 1400.00, 'Available')");

    // Insert room images
    $pdo->exec("INSERT INTO room_images (room_id, image_path) VALUES
        (1, 'uploads/room101.jpg'),
        (2, 'uploads/room102.jpg'),
        (3, 'uploads/deluxe201.jpg'),
        (4, 'uploads/suite301.jpg'),
        (5, 'uploads/room103.jpg')");

    // Insert amenities
    $pdo->exec("INSERT INTO amenities (amenity_name, description) VALUES
        ('WiFi', 'High-speed internet access'),
        ('Air Conditioning', 'Climate control system'),
        ('TV', 'Cable television'),
        ('Kitchen', 'Basic kitchen facilities'),
        ('Parking', 'Free parking space')");

    // Insert room amenities
    $pdo->exec("INSERT INTO room_amenities (room_id, amenity_id) VALUES
        (1, 1), (1, 2), (1, 3),
        (2, 1), (2, 2), (2, 3),
        (3, 1), (3, 2), (3, 3), (3, 4),
        (4, 1), (4, 2), (4, 3), (4, 4), (4, 5),
        (5, 1), (5, 2)");

    echo "Sample data inserted successfully!";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage();
}
