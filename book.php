<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

if ($_POST) {
    $checkin = new DateTime($_POST['checkin']);
    $checkout = new DateTime($_POST['checkout']);
    $nights = $checkout->diff($checkin)->days;
    
    $stmt = $conn->prepare("SELECT base_price FROM room_types WHERE type_id = ?");
    $stmt->execute([$_POST['type_id']]);
    $room_type = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_price = $nights * $room_type['base_price'];
    
    $stmt = $conn->prepare("
        SELECT r.room_id FROM rooms r 
        WHERE r.type_id = ? AND r.status = 'available'
        AND r.room_id NOT IN (
            SELECT room_id FROM reservations 
            WHERE status IN ('confirmed', 'checked_in')
            AND ((check_in_date <= ? AND check_out_date > ?) 
                 OR (check_in_date < ? AND check_out_date >= ?))
        )
        LIMIT 1
    ");
    $stmt->execute([$_POST['type_id'], $_POST['checkin'], $_POST['checkin'], $_POST['checkout'], $_POST['checkout']]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($room) {
        $stmt = $conn->prepare("INSERT INTO reservations (user_id, room_id, check_in_date, check_out_date, total_price, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        if ($stmt->execute([$_SESSION['user_id'], $room['room_id'], $_POST['checkin'], $_POST['checkout'], $total_price])) {
            header('Location: index.php?booked=1');
            exit;
        }
    }
    $error = "Booking failed. Room may no longer be available.";
}

$stmt = $conn->prepare("SELECT * FROM room_types WHERE type_id = ?");
$stmt->execute([$_GET['type_id']]);
$room_type = $stmt->fetch(PDO::FETCH_ASSOC);

// Set default dates if not provided
$checkin = $_GET['checkin'] ?? date('Y-m-d');
$checkout = $_GET['checkout'] ?? date('Y-m-d', strtotime('+1 day'));
$nights = (new DateTime($checkout))->diff(new DateTime($checkin))->days;
$total_price = $nights * $room_type['base_price'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Book Room - Hotel Reservation</title>
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
        
        .container { max-width: 800px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
        .card-header { padding: 2rem; border-bottom: 1px solid #e2e8f0; }
        .card-title { font-size: 1.75rem; font-weight: 600; color: #1e293b; }
        .card-body { padding: 2rem; }
        
        .booking-summary { background: #f8fafc; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; }
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; }
        .summary-item { }
        .summary-label { font-size: 0.875rem; color: #64748b; margin-bottom: 0.25rem; }
        .summary-value { font-weight: 600; color: #1e293b; }
        .total-price { font-size: 1.5rem; color: #3b82f6; }
        
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #374151; }
        .form-input, .form-select { width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem; transition: border-color 0.2s; }
        .form-input:focus, .form-select:focus { outline: none; border-color: #3b82f6; }
        
        .btn { display: inline-flex; align-items: center; padding: 0.875rem 1.5rem; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.2s; text-decoration: none; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-secondary:hover { background: #4b5563; }
        
        .alert { padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 2rem; background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        
        .actions { display: flex; gap: 1rem; margin-top: 2rem; }
        
        @media (max-width: 768px) {
            .summary-grid { grid-template-columns: 1fr; }
            .actions { flex-direction: column; }
            .nav { flex-direction: column; gap: 1rem; }
            .header-content { flex-direction: column; gap: 1rem; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">🏨 Luxury Hotel</div>
            <nav class="nav">
                <a href="index.php">Home</a>
                <a href="user/profile.php">Profile</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </div>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Complete Your Booking</h1>
            </div>
            <div class="card-body">
                <div class="booking-summary">
                    <h3 style="margin-bottom: 1.5rem; color: #1e293b;">Booking Summary</h3>
                    <div class="summary-grid">
                        <div class="summary-item">
                            <div class="summary-label">Room Type</div>
                            <div class="summary-value"><?= htmlspecialchars($room_type['type_name']) ?></div>
                        </div>
                    </div>
                </div>
                
                <p style="color: #64748b; margin-bottom: 1.5rem;"><?= htmlspecialchars($room_type['description']) ?></p>
                
                <form method="POST">
                    <input type="hidden" name="type_id" value="<?= $_GET['type_id'] ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Check-in Date</label>
                        <input type="date" name="checkin" class="form-input" value="<?= $checkin ?>" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Check-out Date</label>
                        <input type="date" name="checkout" class="form-input" value="<?= $checkout ?>" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="">Select payment method</option>
                            <option value="credit_card">💳 Credit Card</option>
                            <option value="paypal">🅿️ PayPal</option>
                            <option value="bank_transfer">🏦 Bank Transfer</option>
                        </select>
                    </div>
                    
                    <div class="actions">
                        <button type="submit" class="btn btn-primary">Confirm Booking</button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
