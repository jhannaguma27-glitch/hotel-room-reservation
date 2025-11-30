<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    die("Database connection failed.");
}

echo "Database connection successful!\n\n";

// Check room_types table structure
try {
    $stmt = $conn->query("DESCRIBE room_types");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current room_types table structure:\n";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // Check if image_path column exists
    $has_image_path = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'image_path') {
            $has_image_path = true;
            break;
        }
    }
    
    echo "\nimage_path column exists: " . ($has_image_path ? "YES" : "NO") . "\n";
    
    if (!$has_image_path) {
        echo "\nAdding image_path column...\n";
        $conn->exec("ALTER TABLE room_types ADD COLUMN image_path VARCHAR(255) NULL AFTER max_occupancy");
        echo "image_path column added successfully!\n";
    }
    
    // Test inserting a room type
    echo "\nTesting room type insertion...\n";
    $test_stmt = $conn->prepare("INSERT INTO room_types (type_name, description, base_price, max_occupancy, created_by_admin) VALUES (?, ?, ?, ?, ?)");
    $test_result = $test_stmt->execute(['Test Room Type', 'Test description', 100.00, 2, 1]);
    
    if ($test_result) {
        echo "Test insertion successful!\n";
        // Clean up test data
        $conn->exec("DELETE FROM room_types WHERE type_name = 'Test Room Type'");
        echo "Test data cleaned up.\n";
    } else {
        echo "Test insertion failed.\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
