<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Get user reservations
$stmt = $conn->prepare("
    SELECT r.*, ro.room_number, rt.type_name, rt.base_price 
    FROM reservations r
    JOIN rooms ro ON r.room_id = ro.room_id
    JOIN room_types rt ON ro.type_id = rt.type_id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Bookings - Hotel Reservation</title>
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
            --white: #ffffff;
            --gray-light: #f8fafc;
            --gray: #64748b;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, var(--gray-light) 0%, #f1f5f9 100%);
            color: var(--navy); 
            line-height: 1.6;
        }
        
        .header { 
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%); 
            color: var(--white); 
            padding: 1.5rem 2rem; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
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
        
        .logo i { color: var(--gold); }
        
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
        }
        
        .nav a:hover { color: var(--gold); }
        
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
        
        .reservations-grid { 
            display: grid; 
            gap: 2rem; 
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        }
        
        .reservation-card { 
            background: linear-gradient(135deg, var(--white) 0%, #f8fafc 100%);
            border-radius: 16px; 
            padding: 2rem; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .reservation-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--gold), var(--gold-dark));
        }
        
        .reservation-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 12px 35px rgba(0,0,0,0.15);
        }
        
        .reservation-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start; 
            margin-bottom: 1.5rem;
        }
        
        .reservation-title { 
            font-size: 1.5rem; 
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .status.confirmed { background: #dcfce7; color: #166534; }
        .status.pending { background: #fef3c7; color: #92400e; }
        .status.cancelled { background: #fee2e2; color: #dc2626; }
        .status.completed { background: #dbeafe; color: #1e40af; }
        .status.checked_in { background: #f3e8ff; color: #7c3aed; }
        
        .reservation-details { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 1.5rem; 
            margin-bottom: 2rem;
        }
        
        .detail-item {
            text-align: center;
            padding: 1rem;
            background: rgba(212, 175, 55, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(212, 175, 55, 0.1);
        }
        
        .detail-label {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .detail-value {
            font-weight: 700;
            color: var(--navy);
            font-size: 1.1rem;
        }
        
        .btn {
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
            font-size: 0.9rem;
        }
        
        .btn:hover {
            background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%);
            transform: translateY(-2px);
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
        
        .empty-state a {
            color: var(--gold);
            text-decoration: none;
            font-weight: 600;
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
            
            .page-title {
                font-size: 2rem;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .reservations-grid {
                grid-template-columns: 1fr;
            }
            
            .reservation-details {
                grid-template-columns: 1fr;
            }
            
            .reservation-header {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo"><i class="fas fa-crown"></i> Caliview Hotel</div>
            <nav class="nav">
                <a href="../index.php"><i class="fas fa-home"></i> Home</a>
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="reviews.php"><i class="fas fa-star"></i> Reviews</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </div>

    <div class="container">
        <h1 class="page-title">
            <i class="fas fa-calendar-check"></i>
            My Bookings
        </h1>
        
        <?php if (empty($reservations)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>No Bookings Found</h3>
                <p>You haven't made any reservations yet. <a href="../index.php">Book a room</a> to get started!</p>
            </div>
        <?php else: ?>
            <div class="reservations-grid">
                <?php foreach ($reservations as $reservation): ?>
                    <div class="reservation-card">
                        <div class="reservation-header">
                            <h3 class="reservation-title">
                                <?= htmlspecialchars($reservation['type_name']) ?> - Room <?= htmlspecialchars($reservation['room_number']) ?>
                            </h3>
                            <span class="status <?= $reservation['status'] ?>">
                                <i class="fas fa-<?= 
                                    $reservation['status'] == 'confirmed' ? 'check-circle' : 
                                    ($reservation['status'] == 'pending' ? 'clock' : 
                                    ($reservation['status'] == 'cancelled' ? 'times-circle' : 
                                    ($reservation['status'] == 'completed' ? 'flag-checkered' : 'door-open'))) 
                                ?>"></i>
                                <?= ucfirst(str_replace('_', ' ', $reservation['status'])) ?>
                            </span>
                        </div>
                        
                        <div class="reservation-details">
                            <div class="detail-item">
                                <div class="detail-label">Check-in Date</div>
                                <div class="detail-value"><?= date('F j, Y', strtotime($reservation['check_in_date'])) ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Check-out Date</div>
                                <div class="detail-value"><?= date('F j, Y', strtotime($reservation['check_out_date'])) ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Total Price</div>
                                <div class="detail-value">$<?= number_format($reservation['total_price'], 2) ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Room Number</div>
                                <div class="detail-value"><?= htmlspecialchars($reservation['room_number']) ?></div>
                            </div>
                        </div>
                        
                        <?php if ($reservation['status'] == 'confirmed' || $reservation['status'] == 'pending'): ?>
                            <form method="POST" action="cancel_reservation.php">
                                <input type="hidden" name="reservation_id" value="<?= $reservation['reservation_id'] ?>">
                                <button type="submit" onclick="return confirm('Are you sure you want to cancel this reservation?')" class="btn">
                                    <i class="fas fa-times"></i> Cancel Reservation
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
