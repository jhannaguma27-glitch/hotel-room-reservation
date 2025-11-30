<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Handle reservation deletion
if (isset($_GET['delete_id'])) {
    $reservation_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM reservations WHERE reservation_id = ?");
    if ($stmt->execute([$reservation_id])) {
        $success = "Reservation deleted successfully!";
    } else {
        $error = "Failed to delete reservation";
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $reservation_id = intval($_POST['reservation_id']);
    $new_status = $_POST['status'];
    
    $valid_statuses = ['pending', 'confirmed', 'checked_in', 'completed', 'cancelled'];
    if (in_array($new_status, $valid_statuses)) {
        $stmt = $conn->prepare("UPDATE reservations SET status = ? WHERE reservation_id = ?");
        if ($stmt->execute([$new_status, $reservation_id])) {
            $success = "Reservation status updated successfully!";
        } else {
            $error = "Failed to update reservation status";
        }
    }
}

// Get all reservations
$reservations_stmt = $conn->query("
    SELECT r.*, u.full_name, u.email, rm.room_number, rt.type_name
    FROM reservations r
    JOIN users u ON r.user_id = u.user_id
    JOIN rooms rm ON r.room_id = rm.room_id
    JOIN room_types rt ON rm.type_id = rt.type_id
    ORDER BY r.created_at DESC
");
$reservations = $reservations_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_stmt = $conn->query("
    SELECT 
        COUNT(*) as total_reservations,
        COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled,
        SUM(total_price) as total_revenue
    FROM reservations
");
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Reservations - Luxury Hotel Admin</title>
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
        
        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .revenue-section {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .revenue-card {
            border-left: 4px solid #27ae60 !important;
        }
        
        .revenue-card .stat-number {
            color: #27ae60 !important;
            font-size: 2.2rem;
        }
        
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .stat-card {
            background: var(--white);
            padding: 1.2rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            text-align: center;
            border-left: 4px solid var(--gold);
            min-width: 0;
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--gold);
            margin-bottom: 0.5rem;
            word-break: break-word;
        }
        
        .stat-label {
            color: var(--gray);
            font-size: 0.85rem;
            font-weight: 500;
            line-height: 1.2;
        }
        
        /* Reservations Section */
        .reservations-section {
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        
        .section-header {
            background: linear-gradient(90deg, var(--gold), var(--gold-dark));
            color: var(--navy);
            padding: 2rem;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .reservation-item {
            padding: 2rem;
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s;
        }
        
        .reservation-item:last-child {
            border-bottom: none;
        }
        
        .reservation-item:hover {
            background: var(--gray-light);
        }
        
        .reservation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .reservation-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--navy);
        }
        
        .status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-completed { background: #cce5ff; color: #004085; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .reservation-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--gray);
            font-size: 0.95rem;
        }
        
        .detail-item i {
            color: var(--gold);
            font-size: 1.1rem;
            width: 20px;
        }
        
        .price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gold-dark);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: var(--white);
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(220, 38, 38, 0.4);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: var(--navy);
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--gold-dark) 0%, #9c7c18 100%);
            transform: translateY(-2px);
        }
        
        .status-form {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .status-select {
            padding: 0.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            background: white;
            color: var(--navy);
            font-size: 0.9rem;
        }
        
        .status-select:focus {
            outline: none;
            border-color: var(--gold);
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
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .container {
                padding: 0 1rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 1rem;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .nav {
                flex-wrap: wrap;
                gap: 1rem;
                justify-content: center;
            }
            
            .admin-info {
                order: -1;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-number {
                font-size: 1.5rem;
            }
            
            .stat-label {
                font-size: 0.8rem;
            }
            
            .page-title {
                font-size: 1.75rem;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .reservation-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .reservation-details {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            
            .status-form {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }
            
            .status-select {
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .header {
                padding: 0.75rem;
            }
            
            .logo {
                font-size: 1.25rem;
            }
            
            .nav {
                gap: 0.75rem;
            }
            
            .nav a {
                font-size: 0.9rem;
                padding: 0.5rem;
            }
            
            .container {
                margin: 1rem auto;
                padding: 0 0.75rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            
            .stat-card {
                padding: 0.75rem;
            }
            
            .stat-number {
                font-size: 1.25rem;
            }
            
            .page-title {
                font-size: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .section-header {
                padding: 1.5rem 1rem;
                font-size: 1.25rem;
            }
            
            .reservation-item {
                padding: 1.5rem 1rem;
            }
            
            .reservation-title {
                font-size: 1.1rem;
            }
            
            .price {
                font-size: 1.25rem;
            }
            
            .btn-danger {
                padding: 0.5rem 1rem;
                font-size: 0.85rem;
            }
            
            .btn-primary {
                padding: 0.5rem 1rem;
                font-size: 0.85rem;
            }
            
            .detail-item {
                font-size: 0.9rem;
            }
            
            .status-form label {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 320px) {
            .header {
                padding: 0.5rem;
            }
            
            .logo {
                font-size: 1rem;
            }
            
            .nav a {
                font-size: 0.8rem;
                padding: 0.25rem;
            }
            
            .admin-badge {
                font-size: 0.7rem;
                padding: 0.2rem 0.5rem;
            }
            
            .container {
                padding: 0 0.5rem;
            }
            
            .page-title {
                font-size: 1.25rem;
            }
            
            .stat-number {
                font-size: 1.1rem;
            }
            
            .stat-label {
                font-size: 0.75rem;
            }
            
            .reservation-title {
                font-size: 1rem;
            }
            
            .price {
                font-size: 1.1rem;
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
                <a href="room_types.php"><i class="fas fa-layer-group"></i> Room Types</a>
                <a href="rooms.php"><i class="fas fa-door-open"></i> Rooms</a>
                <a href="reviews.php"><i class="fas fa-star"></i> Reviews</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </div>

    <div class="container">
        <h1 class="page-title">
            <i class="fas fa-calendar-check"></i>
            Reservation Management
        </h1>
        
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

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_reservations'] ?></div>
                <div class="stat-label">Total Reservations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['confirmed'] ?></div>
                <div class="stat-label">Confirmed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['pending'] ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['completed'] ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['cancelled'] ?></div>
                <div class="stat-label">Cancelled</div>
            </div>
        </div>
        
        <!-- Revenue Card (Second Row) -->
        <div class="revenue-section">
            <div class="stat-card revenue-card">
                <div class="stat-number">$<?= number_format($stats['total_revenue'], 2) ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>

        <!-- Reservations List -->
        <div class="reservations-section">
            <div class="section-header">
                <i class="fas fa-list"></i>
                All Reservations (<?= count($reservations) ?>)
            </div>
            
            <?php if (empty($reservations)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Reservations Found</h3>
                    <p>Reservations will appear here once users start booking rooms.</p>
                </div>
            <?php else: ?>
                <?php foreach ($reservations as $reservation): ?>
                    <div class="reservation-item">
                        <div class="reservation-header">
                            <div>
                                <div class="reservation-title"><?= htmlspecialchars($reservation['type_name']) ?> - Room <?= $reservation['room_number'] ?></div>
                                <span class="status status-<?= $reservation['status'] ?>"><?= ucfirst($reservation['status']) ?></span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 1.5rem;">
                                <span class="price">$<?= number_format($reservation['total_price'], 2) ?></span>
                                <a href="?delete_id=<?= $reservation['reservation_id'] ?>" 
                                   class="btn-danger" 
                                   onclick="return confirm('Delete this reservation? This cannot be undone.')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                        
                        <div class="reservation-details">
                            <div class="detail-item">
                                <i class="fas fa-user"></i>
                                <span><strong>Guest:</strong> <?= htmlspecialchars($reservation['full_name']) ?></span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-envelope"></i>
                                <span><?= htmlspecialchars($reservation['email']) ?></span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-calendar-check"></i>
                                <span><strong>Check-in:</strong> <?= date('M j, Y', strtotime($reservation['check_in_date'])) ?></span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-calendar-times"></i>
                                <span><strong>Check-out:</strong> <?= date('M j, Y', strtotime($reservation['check_out_date'])) ?></span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-clock"></i>
                                <span><strong>Booked:</strong> <?= date('M j, Y g:i A', strtotime($reservation['created_at'])) ?></span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-hashtag"></i>
                                <span><strong>ID:</strong> #<?= $reservation['reservation_id'] ?></span>
                            </div>
                        </div>
                        
                        <!-- Status Update Form -->
                        <form method="POST" class="status-form">
                            <input type="hidden" name="reservation_id" value="<?= $reservation['reservation_id'] ?>">
                            <label><strong>Update Status:</strong></label>
                            <select name="status" class="status-select" required>
                                <option value="pending" <?= $reservation['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="confirmed" <?= $reservation['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                <option value="checked_in" <?= $reservation['status'] === 'checked_in' ? 'selected' : '' ?>>Checked In</option>
                                <option value="completed" <?= $reservation['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= $reservation['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                            <button type="submit" name="update_status" class="btn-primary">
                                <i class="fas fa-save"></i> Update
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
