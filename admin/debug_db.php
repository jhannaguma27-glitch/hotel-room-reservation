<?php
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

if ($conn) {
    echo "Database connection: SUCCESS<br>";
    
    // Check if room_types table exists
    try {
        $stmt = $conn->query("SHOW TABLES LIKE 'room_types'");
        $table_exists = $stmt->rowCount() > 0;
        
        if ($table_exists) {
            echo "room_types table: EXISTS<br>";
            
            // Show table structure
            $stmt = $conn->query("DESCRIBE room_types");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "Table structure:<br>";
            foreach ($columns as $column) {
                echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
            }
            
            // Count existing records
            $stmt = $conn->query("SELECT COUNT(*) as count FROM room_types");
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "Existing records: " . $count['count'] . "<br>";
            
        } else {
            echo "room_types table: DOES NOT EXIST<br>";
            echo "Creating table...<br>";
            
            $create_sql = "CREATE TABLE room_types (
                type_id INT AUTO_INCREMENT PRIMARY KEY,
                type_name VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                base_price DECIMAL(10,2) NOT NULL,
                max_occupancy INT DEFAULT 2,
                image_path VARCHAR(255),
                created_by_admin INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            if ($conn->exec($create_sql)) {
                echo "Table created successfully!<br>";
            } else {
                echo "Failed to create table<br>";
            }
        }
        
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . "<br>";
    }
    
} else {
    echo "Database connection: FAILED<br>";
}
?>
