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

// Debug: Show what's being submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px; border: 1px solid #ccc;'>";
    echo "<strong>DEBUG - Form submitted:</strong><br>";
    echo "POST data: " . print_r($_POST, true) . "<br>";
    echo "FILES data: " . print_r($_FILES, true) . "<br>";
    echo "</div>";
}

// Handle success message from redirect
if (isset($_GET['success'])) {
    $success = "Room type added successfully!";
}

// First, check if image_path column exists and add it if it doesn't
try {
    $check_column = $conn->query("SHOW COLUMNS FROM room_types LIKE 'image_path'");
    if ($check_column->rowCount() == 0) {
        $conn->exec("ALTER TABLE room_types ADD COLUMN image_path VARCHAR(255) NULL AFTER max_occupancy");
    }
} catch (PDOException $e) {
    error_log("Error checking/adding image_path column: " . $e->getMessage());
}

// Handle room type creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['type_name'])) {
    $type_name = trim($_POST['type_name']);
    $description = trim($_POST['description']);
    $base_price = floatval($_POST['base_price']);
    $max_occupancy = intval($_POST['max_occupancy']) ?: 2;
    
    // Handle image upload
    $image_path = null;
    if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/room_types/';
        
        // Create upload directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['room_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $type_name) . '.' . $file_extension;
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['room_image']['tmp_name'], $target_path)) {
                $image_path = 'uploads/room_types/' . $filename;
            }
        }
    }
    
    if (!empty($type_name) && $base_price > 0) {
        try {
            // Check if image_path column exists
            $check_column = $conn->query("SHOW COLUMNS FROM room_types LIKE 'image_path'");
            if ($check_column->rowCount() > 0) {
                $stmt = $conn->prepare("INSERT INTO room_types (type_name, description, base_price, max_occupancy, image_path, created_by_admin) VALUES (?, ?, ?, ?, ?, ?)");
                $result = $stmt->execute([$type_name, $description, $base_price, $max_occupancy, $image_path, $_SESSION['admin_id']]);
            } else {
                $stmt = $conn->prepare("INSERT INTO room_types (type_name, description, base_price, max_occupancy, created_by_admin) VALUES (?, ?, ?, ?, ?)");
                $result = $stmt->execute([$type_name, $description, $base_price, $max_occupancy, $_SESSION['admin_id']]);
            }
            
            if ($result) {
                header("Location: room_types.php?success=1");
                exit;
            } else {
                $error = "Failed to add room type";
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields";
    }
}

// Handle room type deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // First, check if there are any rooms using this type
    $check_stmt = $conn->prepare("SELECT COUNT(*) as room_count FROM rooms WHERE type_id = ?");
    $check_stmt->execute([$delete_id]);
    $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['room_count'] > 0) {
        $error = "Cannot delete room type. There are " . $result['room_count'] . " room(s) assigned to this type.";
    } else {
        // Get image path before deletion to remove the file
        $img_stmt = $conn->prepare("SELECT image_path FROM room_types WHERE type_id = ?");
        $img_stmt->execute([$delete_id]);
        $room_type = $img_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete from database
        $delete_stmt = $conn->prepare("DELETE FROM room_types WHERE type_id = ?");
        if ($delete_stmt->execute([$delete_id])) {
            // Delete associated image file if exists
            if ($room_type && $room_type['image_path'] && file_exists('../' . $room_type['image_path'])) {
                unlink('../' . $room_type['image_path']);
            }
            $success = "Room type deleted successfully!";
        } else {
            $error = "Failed to delete room type";
        }
    }
    
    // Redirect to avoid form resubmission
    header("Location: room_types.php");
    exit;
}

// Get all room types with additional information
$stmt = $conn->query("
    SELECT rt.*, 
           a.username as created_by,
           (SELECT COUNT(*) FROM rooms r WHERE r.type_id = rt.type_id) as room_count
    FROM room_types rt 
    LEFT JOIN admins a ON rt.created_by_admin = a.admin_id 
    ORDER BY rt.type_name
");
$room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get room count statistics
$stats_stmt = $conn->query("
    SELECT 
        COUNT(*) as total_types,
        SUM((SELECT COUNT(*) FROM rooms r WHERE r.type_id = rt.type_id)) as total_rooms,
        AVG(base_price) as avg_price
    FROM room_types rt
");
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Room Types - Luxury Hotel Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --gold: #d4af37;
            --gold-light: #f4e4a6;
            --gold-dark: #b8941f;
            --navy: #0f172a;
            --navy-light: #1e293b;
            --navy-lighter: #334155;
            --white: #ffffff;
            --gray-light: #f8fafc;
            --gray: #64748b;
        }
        
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: var(--gray-light); 
            color: var(--navy); 
            line-height: 1.6;
            min-height: 100vh;
        }
        
        /* Header Styles */
        .header { 
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%); 
            color: var(--white); 
            padding: 1.5rem 2rem; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content { 
            max-width: 1200px; 
            margin: 0 auto; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        
        .logo { 
            font-size: 1.5rem; 
            font-weight: 700; 
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .logo i {
            color: var(--gold);
        }
        
        .nav { 
            display: flex; 
            gap: 2rem; 
            align-items: center;
        }
        
        .nav a { 
            color: var(--white); 
            text-decoration: none; 
            font-weight: 500; 
            transition: all 0.3s; 
            padding: 0.5rem 0;
            position: relative;
        }
        
        .nav a:hover { 
            color: var(--gold); 
        }
        
        .nav a:after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 0;
            background-color: var(--gold);
            transition: width 0.3s;
        }
        
        .nav a:hover:after { 
            width: 100%; 
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--gold-light);
        }
        
        .admin-badge {
            background: var(--gold);
            color: var(--navy);
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .container { 
            max-width: 1200px; 
            margin: 2rem auto; 
            padding: 0 1.5rem; 
        }
        
        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            text-align: center;
            border-left: 4px solid var(--gold);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gold);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .page-title { 
            font-size: 2.5rem; 
            font-weight: 700; 
            color: var(--navy); 
            margin-bottom: 2rem; 
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }
        
        /* Form Container */
        .form-container { 
            background: var(--white); 
            padding: 2.5rem; 
            margin-bottom: 3rem; 
            border-radius: 20px; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        
        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--gold), var(--gold-dark));
        }
        
        .form-title { 
            font-size: 1.5rem; 
            font-weight: 700; 
            color: var(--navy); 
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .form-title i {
            color: var(--gold);
        }
        
        .form-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 1.5rem; 
            margin-bottom: 1.5rem;
        }
        
        .form-group { 
            margin-bottom: 1.5rem; 
        }
        
        .form-label { 
            display: block; 
            margin-bottom: 0.75rem; 
            font-weight: 600; 
            color: var(--navy);
            font-size: 0.95rem;
        }
        
        .form-input, .form-textarea, .form-file { 
            width: 100%; 
            padding: 1rem 1.25rem; 
            border: 2px solid #e2e8f0; 
            border-radius: 10px; 
            transition: all 0.3s;
            background: var(--white);
            color: var(--navy);
            font-size: 1rem;
        }
        
        .form-file {
            padding: 0.8rem 1.25rem;
        }
        
        .form-input:focus, .form-textarea:focus, .form-file:focus { 
            outline: none; 
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
        }
        
        .file-preview {
            margin-top: 1rem;
            display: none;
        }
        
        .file-preview img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .file-info {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: var(--gray);
        }
        
        .btn { 
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%); 
            color: var(--navy); 
            padding: 1rem 2rem; 
            border: none; 
            border-radius: 10px; 
            cursor: pointer; 
            transition: all 0.3s;
            font-weight: 600;
            font-size: 1rem;
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn:hover { 
            background: linear-gradient(135deg, var(--gold-dark) 0%, #9c7c18 100%); 
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(212, 175, 55, 0.4);
        }
        
        .btn-secondary {
            background: var(--navy-lighter);
            color: var(--white);
        }
        
        .btn-secondary:hover {
            background: var(--navy-light);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: var(--white);
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%);
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
        /* Room Types Section */
        .types-section {
            margin-top: 3rem;
        }
        
        .section-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .section-title i {
            color: var(--gold);
        }
        
        .types-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }
        
        .type-card { 
            background: var(--white); 
            border-radius: 16px; 
            padding: 0;
            margin-bottom: 0;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .type-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.15);
        }
        
        .type-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--gold), var(--gold-dark));
        }
        
        .type-image {
            width: 100%;
            height: 200px;
            background: var(--gray-light);
            position: relative;
            overflow: hidden;
        }
        
        .type-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .type-card:hover .type-image img {
            transform: scale(1.05);
        }
        
        .type-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, transparent 0%, rgba(15, 23, 42, 0.7) 100%);
            display: flex;
            align-items: flex-end;
            padding: 1.5rem;
            color: var(--white);
        }
        
        .type-body {
            padding: 2rem;
        }
        
        .type-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .type-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--navy);
        }
        
        .price { 
            font-size: 1.5rem; 
            font-weight: 700; 
            color: var(--gold-dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .type-description {
            color: var(--gray);
            line-height: 1.7;
            margin-bottom: 1.5rem;
        }
        
        .type-meta {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: var(--gray);
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .meta-item i {
            color: var(--gold);
        }
        
        .type-features {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .feature {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: var(--gray);
        }
        
        .feature i {
            color: var(--gold);
        }
        
        .type-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .success { 
            background: #dcfce7; 
            color: #166534; 
            padding: 1.25rem 1.5rem; 
            border-radius: 12px; 
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-left: 4px solid #22c55e;
        }
        
        .error { 
            background: #fee2e2; 
            color: #dc2626; 
            padding: 1.25rem 1.5rem; 
            border-radius: 12px; 
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-left: 4px solid #ef4444;
        }
        
        .success i, .error i {
            font-size: 1.25rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: var(--gold-light);
            margin-bottom: 1rem;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--navy);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav {
                gap: 1.5rem;
            }
            
            .container {
                margin: 1.5rem auto;
                padding: 0 1rem;
            }
            
            .form-container {
                padding: 2rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .types-grid {
                grid-template-columns: 1fr;
            }
            
            .type-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .type-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo"><i class="fas fa-crown"></i> Luxury Hotel Admin</div>
            <nav class="nav">
                <div class="admin-info">
                    <span>Welcome, <?= htmlspecialchars($_SESSION['admin_username']) ?></span>
                    <span class="admin-badge"><?= ucfirst($_SESSION['admin_role']) ?></span>
                </div>
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="rooms.php"><i class="fas fa-door-open"></i> Manage Rooms</a>
                <a href="reservations.php"><i class="fas fa-calendar-check"></i> Reservations</a>
                <a href="reviews.php"><i class="fas fa-star"></i> Reviews</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </div>

    <div class="container">
        <h1 class="page-title">
            <i class="fas fa-layer-group"></i>
            Room Type Management
        </h1>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_types'] ?? 0 ?></div>
                <div class="stat-label">Room Types</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_rooms'] ?? 0 ?></div>
                <div class="stat-label">Total Rooms</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">$<?= number_format($stats['avg_price'] ?? 0, 2) ?></div>
                <div class="stat-label">Average Price</div>
            </div>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i>
                <div><?= $success ?></div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <div><?= $error ?></div>
            </div>
        <?php endif; ?>

        <!-- Add Room Type Form -->
        <div class="form-container">
            <h3 class="form-title">
                <i class="fas fa-plus-circle"></i>
                Add New Room Type
            </h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Type Name *</label>
                        <input type="text" name="type_name" class="form-input" 
                               placeholder="e.g., Deluxe Suite, Executive Room" 
                               value="<?= isset($error) ? htmlspecialchars($_POST['type_name'] ?? '') : '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Base Price (per night) *</label>
                        <input type="number" name="base_price" class="form-input" 
                               step="0.01" min="0" placeholder="0.00" 
                               value="<?= isset($error) ? htmlspecialchars($_POST['base_price'] ?? '') : '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Maximum Occupancy *</label>
                        <input type="number" name="max_occupancy" class="form-input" 
                               min="1" max="10" placeholder="2" 
                               value="<?= isset($error) ? htmlspecialchars($_POST['max_occupancy'] ?? '2') : '2' ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" rows="4" 
                              placeholder="Describe the room type features and amenities"><?= isset($error) ? htmlspecialchars($_POST['description'] ?? '') : '' ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Room Image</label>
                    <input type="file" name="room_image" class="form-file" id="roomImage" accept="image/*">
                    <small style="color: var(--gray); margin-top: 0.5rem; display: block;">
                        <i class="fas fa-info-circle"></i> Supported formats: JPG, JPEG, PNG, WebP. Max file size: 5MB
                    </small>
                    
                    <div class="file-preview" id="filePreview">
                        <img id="previewImage" src="" alt="Preview">
                        <div class="file-info" id="fileInfo"></div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button type="submit" name="add_type" class="btn" id="submitBtn">
                        <i class="fas fa-plus"></i>
                        Add Room Type
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-redo"></i>
                        Reset Form
                    </button>
                </div>
            </form>
        </div>

        <!-- Display Room Types -->
        <div class="types-section">
            <h3 class="section-title">
                <i class="fas fa-list"></i>
                All Room Types (<?= count($room_types) ?>)
            </h3>
            
            <?php if (empty($room_types)): ?>
                <div class="empty-state">
                    <i class="fas fa-bed"></i>
                    <h3>No Room Types Found</h3>
                    <p>Get started by adding your first room type using the form above.</p>
                </div>
            <?php else: ?>
                <div class="types-grid">
                    <?php 
                    // Default images for room types without uploaded images
                    $defaultImages = [
                        'standard_room' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
                        'deluxe_room' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
                        'suite' => 'https://images.unsplash.com/photo-1564078516393-cf04bd966897?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
                        'executive_suite' => 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
                        'presidential_suite' => 'https://images.unsplash.com/photo-1618773928121-c32242e63f39?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80'
                    ];
                    
                    foreach ($room_types as $type): 
                        // Use uploaded image if available, otherwise use default based on type name
                        if (!empty($type['image_path']) && file_exists('../' . $type['image_path'])) {
                            $imageUrl = '../' . $type['image_path'];
                        } else {
                            $typeKey = strtolower(str_replace(' ', '_', $type['type_name']));
                            $imageUrl = $defaultImages[$typeKey] ?? $defaultImages['standard_room'];
                        }
                    ?>
                        <div class="type-card">
                            <div class="type-image">
                                <img src="<?= $imageUrl ?>" alt="<?= htmlspecialchars($type['type_name']) ?>" 
                                     onerror="this.src='<?= $defaultImages['standard_room'] ?>'">
                                <div class="type-overlay">
                                    <div class="price">
                                        <i class="fas fa-tag"></i>
                                        $<?= number_format($type['base_price'], 2) ?>/night
                                    </div>
                                </div>
                            </div>
                            
                            <div class="type-body">
                                <div class="type-header">
                                    <h4 class="type-name"><?= htmlspecialchars($type['type_name']) ?></h4>
                                </div>
                                
                                <div class="type-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-user-friends"></i>
                                        <span>Max <?= $type['max_occupancy'] ?> guests</span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-door-open"></i>
                                        <span><?= $type['room_count'] ?> room(s)</span>
                                    </div>
                                </div>
                                
                                <p class="type-description"><?= htmlspecialchars($type['description'] ?: 'No description provided.') ?></p>
                                
                                <div class="type-features">
                                    <div class="feature">
                                        <i class="fas fa-wifi"></i>
                                        <span>Free WiFi</span>
                                    </div>
                                    <div class="feature">
                                        <i class="fas fa-tv"></i>
                                        <span>Smart TV</span>
                                    </div>
                                    <div class="feature">
                                        <i class="fas fa-snowflake"></i>
                                        <span>Air Conditioning</span>
                                    </div>
                                    <div class="feature">
                                        <i class="fas fa-coffee"></i>
                                        <span>Coffee Maker</span>
                                    </div>
                                </div>
                                
                                <div class="type-actions">
                                    <a href="edit_room_type.php?id=<?= $type['type_id'] ?>" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="room_types.php?delete_id=<?= $type['type_id'] ?>" class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Are you sure you want to delete <?= htmlspecialchars($type['type_name']) ?>? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                                
                                <?php if ($type['created_by']): ?>
                                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; font-size: 0.8rem; color: var(--gray);">
                                        <i class="fas fa-user"></i> Created by: <?= htmlspecialchars($type['created_by']) ?>
                                        on <?= date('M j, Y', strtotime($type['created_at'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const roomImageInput = document.getElementById('roomImage');
            const filePreview = document.getElementById('filePreview');
            const previewImage = document.getElementById('previewImage');
            const fileInfo = document.getElementById('fileInfo');
            const submitBtn = document.getElementById('submitBtn');
            
            // File upload preview
            roomImageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Validate file type
                    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                    if (!validTypes.includes(file.type)) {
                        alert('Please select a valid image file (JPG, JPEG, PNG, or WebP)');
                        this.value = '';
                        return;
                    }
                    
                    // Validate file size (5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('File size must be less than 5MB');
                        this.value = '';
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                        fileInfo.innerHTML = `
                            <strong>File:</strong> ${file.name}<br>
                            <strong>Size:</strong> ${(file.size / 1024 / 1024).toFixed(2)} MB<br>
                            <strong>Type:</strong> ${file.type}
                        `;
                        filePreview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    filePreview.style.display = 'none';
                }
            });
            
            // Add form submission animation
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding Room Type...';
                        submitBtn.disabled = true;
                    }
                });
            }
            
            // Auto-hide success/error messages after 5 seconds
            setTimeout(() => {
                const successMsg = document.querySelector('.success');
                const errorMsg = document.querySelector('.error');
                
                if (successMsg) {
                    successMsg.style.opacity = '0';
                    setTimeout(() => successMsg.remove(), 500);
                }
                
                if (errorMsg) {
                    errorMsg.style.opacity = '0';
                    setTimeout(() => errorMsg.remove(), 500);
                }
            }, 5000);
        });
    </script>
</body>
</html>