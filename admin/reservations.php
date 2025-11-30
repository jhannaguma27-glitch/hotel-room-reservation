<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Handle status updates
if ($_POST && isset($_POST['update_status'])) {
    $stmt = $conn->prepare("UPDATE reservations SET status = ? WHERE reservation_id = ?");
    $stmt->execute([$_POST['status'], $_POST['reservation_id']]);
    $success = "Reservation status updated";
}

// Get all reservations
$stmt = $conn->prepare("
    SELECT r.*, u.full_name, u.email, ro.room_number, rt.type_name 
    FROM reservations r
    JOIN users u ON r.user_id = u.user_id
    JOIN rooms ro ON r.room_id = ro.room_id
    JOIN room_types rt ON ro.type_id = rt.type_id
    ORDER BY r.created_at DESC
");
$stmt->execute();
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Reservations - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #334155; line-height: 1.6; }
        
        .header { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color: white; padding: 1.5rem 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .header-content { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: 600; }
        .nav { display: flex; gap: 2rem; }
        .nav a { color: white; text-decoration: none; font-weight: 500; transition: opacity 0.2s; }
        .nav a:hover { opacity: 0.8; }
        
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        .page-title { font-size: 2rem; font-weight: 600; color: #1e293b; margin-bottom: 2rem; text-align: center; }
        
        .reservation { background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .reservation h3 { color: #1e293b; margin-bottom: 1rem; }
        .reservation-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
        .detail-item { }
        .detail-label { font-size: 0.875rem; color: #64748b; margin-bottom: 0.25rem; }
        .detail-value { font-weight: 500; color: #1e293b; }
        
        .status { display: inline-block; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500; margin-left: 1rem; }
        .status.confirmed { background: #dcfce7; color: #166534; }
        .status.pending { background: #fef3c7; color: #92400e; }
        .status.cancelled { background: #fee2e2; color: #dc2626; }
        .status.completed { background: #dbeafe; color: #1d4ed8; }
        .status.checked_in { background: #e0e7ff; color: #6366f1; }
        
        .form-controls { display: flex; gap: 1rem; align-items: center; margin-top: 1rem; }
        select, button { padding: 0.5rem 1rem; border: 2px solid #e2e8f0; border-radius: 6px; }
        .btn { background: #3b82f6; color: white; border: none; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background: #2563eb; }
        
        .success { background: #dcfce7; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">🏨 Admin Panel</div>
            <nav class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </div>

    <div class="container">
        <h1 class="page-title">Manage Reservations</h1>
        
        <?php if (isset($success)): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <?php foreach ($reservations as $reservation): ?>
            <div class="reservation">
                <h3>Reservation #<?= $reservation['reservation_id'] ?></h3>
                
                <div class="reservation-details">
                    <div class="detail-item">
                        <div class="detail-label">Guest</div>
                        <div class="detail-value"><?= htmlspecialchars($reservation['full_name']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Email</div>
                        <div class="detail-value"><?= htmlspecialchars($reservation['email']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Room</div>
                        <div class="detail-value"><?= htmlspecialchars($reservation['room_number']) ?> - <?= htmlspecialchars($reservation['type_name']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Check-in</div>
                        <div class="detail-value"><?= date('M j, Y', strtotime($reservation['check_in_date'])) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Check-out</div>
                        <div class="detail-value"><?= date('M j, Y', strtotime($reservation['check_out_date'])) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Total</div>
                        <div class="detail-value">$<?= number_format($reservation['total_price'], 2) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Booked</div>
                        <div class="detail-value"><?= date('M j, Y g:i A', strtotime($reservation['created_at'])) ?></div>
                    </div>
                </div>
                
                <div class="form-controls">
                    <form method="POST" style="display: flex; gap: 1rem; align-items: center;">
                        <input type="hidden" name="reservation_id" value="<?= $reservation['reservation_id'] ?>">
                        <select name="status">
                            <option value="pending" <?= $reservation['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="confirmed" <?= $reservation['status'] == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="checked_in" <?= $reservation['status'] == 'checked_in' ? 'selected' : '' ?>>Checked In</option>
                            <option value="completed" <?= $reservation['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $reservation['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                        <button type="submit" name="update_status" class="btn">Update Status</button>
                    </form>
                    
                    <span class="status <?= $reservation['status'] ?>"><?= ucfirst(str_replace('_', ' ', $reservation['status'])) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
