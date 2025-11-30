<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    die("Database connection failed. Please check your database configuration.");
}

// Add image_path column if it doesn't exist
try {
    $check_column = $conn->query("SHOW COLUMNS FROM room_types LIKE 'image_path'");
    if ($check_column->rowCount() == 0) {
        $conn->exec("ALTER TABLE room_types ADD COLUMN image_path VARCHAR(255) NULL AFTER max_occupancy");
    }
} catch (PDOException $e) {
    // Column might already exist, continue
}

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_type'])) {
    $type_name = trim($_POST['type_name']);
    $description = trim($_POST['description']);
    $base_price = floatval($_POST['base_price']);
    $max_occupancy = intval($_POST['max_occupancy']);
    
    if (!empty($type_name) && $base_price > 0 && $max_occupancy > 0) {
        try {
            $stmt = $conn->prepare("INSERT INTO room_types (type_name, description, base_price, max_occupancy, created_by_admin) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([$type_name, $description, $base_price, $max_occupancy, $_SESSION['admin_id']]);
            
            if ($result) {
                $success = "Room type added successfully!";
                // Clear form data
                $_POST = array();
            } else {
                $error = "Failed to add room type.";
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "A room type with this name already exists.";
            } else {
                $error = "Database error: " . $e->getMessage();
            }
        }
    } else {
        $error = "Please fill in all required fields with valid values.";
    }
}

// Get all room types
$stmt = $conn->query("SELECT * FROM room_types ORDER BY type_name");
$room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Room Types - Hotel Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #005a87; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .room-type { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .price { font-size: 18px; font-weight: bold; color: #007cba; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Room Type Management</h1>
        
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <h2>Add New Room Type</h2>
        <form method="POST">
            <div class="form-group">
                <label>Type Name *</label>
                <input type="text" name="type_name" required value="<?= isset($error) ? htmlspecialchars($_POST['type_name'] ?? '') : '' ?>">
            </div>
            
            <div class="form-group">
                <label>Base Price (per night) *</label>
                <input type="number" name="base_price" step="0.01" min="0" required value="<?= isset($error) ? htmlspecialchars($_POST['base_price'] ?? '') : '' ?>">
            </div>
            
            <div class="form-group">
                <label>Maximum Occupancy *</label>
                <input type="number" name="max_occupancy" min="1" required value="<?= isset($error) ? htmlspecialchars($_POST['max_occupancy'] ?? '2') : '2' ?>">
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"><?= isset($error) ? htmlspecialchars($_POST['description'] ?? '') : '' ?></textarea>
            </div>
            
            <button type="submit" name="add_type">Add Room Type</button>
        </form>
        
        <h2>Existing Room Types</h2>
        <?php if (empty($room_types)): ?>
            <p>No room types found. Add your first room type above.</p>
        <?php else: ?>
            <?php foreach ($room_types as $type): ?>
                <div class="room-type">
                    <h3><?= htmlspecialchars($type['type_name']) ?></h3>
                    <div class="price">$<?= number_format($type['base_price'], 2) ?> per night</div>
                    <p><strong>Max Occupancy:</strong> <?= $type['max_occupancy'] ?> guests</p>
                    <?php if ($type['description']): ?>
                        <p><?= htmlspecialchars($type['description']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <p><a href="dashboard.php">← Back to Dashboard</a></p>
    </div>
</body>
</html>
