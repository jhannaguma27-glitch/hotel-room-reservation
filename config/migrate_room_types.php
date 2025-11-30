<?php
// Migration script to add image_path column to room_types table
require_once 'database.php';

$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    die("Database connection failed.");
}

try {
    // Check if image_path column exists
    $check_column = $conn->query("SHOW COLUMNS FROM room_types LIKE 'image_path'");
    
    if ($check_column->rowCount() == 0) {
        // Add the image_path column
        $conn->exec("ALTER TABLE room_types ADD COLUMN image_path VARCHAR(255) NULL AFTER max_occupancy");
        echo "Successfully added image_path column to room_types table.\n";
    } else {
        echo "image_path column already exists in room_types table.\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
