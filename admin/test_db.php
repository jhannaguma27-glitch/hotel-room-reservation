<?php
session_start();
require_once '../config/database.php';

// Set a test admin session if not exists
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['admin_id'] = 1;
    $_SESSION['admin_username'] = 'test_admin';
    $_SESSION['admin_role'] = 'admin';
}

$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    die("Database connection failed!");
}

echo "<h2>Database Connection Test</h2>";
echo "✅ Database connected successfully!<br><br>";

// Test 1: Check if room_types table exists
try {
    $stmt = $conn->query("DESCRIBE room_types");
    echo "<h3>Room Types Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table><br>";
} catch (PDOException $e) {
    echo "❌ Error checking table structure: " . $e->getMessage() . "<br>";
}

// Test 2: Check current room types
try {
    $stmt = $conn->query("SELECT * FROM room_types ORDER BY type_id");
    $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Current Room Types (" . count($room_types) . "):</h3>";
    if (empty($room_types)) {
        echo "No room types found.<br>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Max Occupancy</th><th>Created At</th></tr>";
        foreach ($room_types as $type) {
            echo "<tr>";
            echo "<td>" . $type['type_id'] . "</td>";
            echo "<td>" . htmlspecialchars($type['type_name']) . "</td>";
            echo "<td>$" . $type['base_price'] . "</td>";
            echo "<td>" . $type['max_occupancy'] . "</td>";
            echo "<td>" . $type['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "<br>";
} catch (PDOException $e) {
    echo "❌ Error fetching room types: " . $e->getMessage() . "<br>";
}

// Test 3: Try inserting a test room type
if (isset($_GET['test_insert'])) {
    try {
        $test_name = "Test Room Type " . date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO room_types (type_name, description, base_price, max_occupancy, created_by_admin) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$test_name, "Test description", 99.99, 2, $_SESSION['admin_id']]);
        
        if ($result) {
            echo "✅ Test insertion successful! ID: " . $conn->lastInsertId() . "<br>";
            echo "<a href='test_db.php'>Refresh to see updated list</a><br>";
        } else {
            echo "❌ Test insertion failed!<br>";
        }
    } catch (PDOException $e) {
        echo "❌ Test insertion error: " . $e->getMessage() . "<br>";
    }
    echo "<br>";
}

echo "<a href='test_db.php?test_insert=1' style='background: #007cba; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>Test Insert Room Type</a><br><br>";
echo "<a href='room_types.php'>← Back to Room Types Management</a>";
?>
