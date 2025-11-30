<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $room_id = intval($_POST['room_id']);
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    
    if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
        try {
            $stmt = $conn->prepare("INSERT INTO reviews (user_id, room_id, rating, comment) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$_SESSION['user_id'], $room_id, $rating, $comment])) {
                header("Location: reviews.php?success=1");
                exit;
            }
        } catch (PDOException $e) {
            $error = "Error submitting review: " . $e->getMessage();
        }
    } else {
        $error = "Please provide a valid rating and comment";
    }
}

// Get user reservations with review status
$reservations_stmt = $conn->prepare("
    SELECT r.*, rm.room_number, rt.type_name,
           CASE WHEN rev.review_id IS NOT NULL THEN 1 ELSE 0 END as has_review
    FROM reservations r
    JOIN rooms rm ON r.room_id = rm.room_id
    JOIN room_types rt ON rm.type_id = rt.type_id
    LEFT JOIN reviews rev ON r.room_id = rev.room_id AND rev.user_id = r.user_id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$reservations_stmt->execute([$_SESSION['user_id']]);
$reservations = $reservations_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's reviews
$user_reviews_stmt = $conn->prepare("
    SELECT r.*, rm.room_number, rt.type_name
    FROM reviews r
    JOIN rooms rm ON r.room_id = rm.room_id
    JOIN room_types rt ON rm.type_id = rt.type_id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$user_reviews_stmt->execute([$_SESSION['user_id']]);
$user_reviews = $user_reviews_stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['success'])) {
    $success = "Review submitted successfully!";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Reviews - Hotel Reservation</title>
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
        
        .card { 
            background: var(--white); 
            padding: 2rem; 
            margin: 1rem 0; 
            border-radius: 16px; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--gold), var(--gold-dark));
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
        
        .reservation-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .reservation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .reservation-header h4 {
            margin: 0;
            color: #2c3e50;
        }
        
        .status {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-left: 1rem;
        }
        
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-completed { background: #cce5ff; color: #004085; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .reservation-details {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
            color: #666;
            font-size: 0.9rem;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .detail-item i {
            color: #3498db;
        }
        
        .btn {
            background: #3498db;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover { background: #2980b9; }
        
        .btn-review {
            background: #ffc107;
            color: #000;
        }
        
        .btn-review:hover {
            background: #e0a800;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .review-form {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            margin-top: 1rem;
            display: none;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        
        select, textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .review-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #28a745;
            margin-top: 1rem;
        }
        
        .review-item {
            border-bottom: 1px solid #eee;
            padding: 1rem 0;
        }
        
        .review-item:last-child {
            border-bottom: none;
        }
        
        .stars {
            font-size: 1.2rem;
            color: #ffc107;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo"><i class="fas fa-crown"></i> Caliview Hotel</div>
            <nav class="nav">
                <a href="../index.php"><i class="fas fa-home"></i> Home</a>
                <a href="bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a>
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </div>

    <div class="container">
        <h1 class="page-title">
            <i class="fas fa-star"></i>
            My Reviews & Reservations
        </h1>
        
        <?php if (isset($success)): ?>
            <div class="success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
        <?php endif; ?>

        <!-- Reservation History -->
        <div class="card">
            <h2>My Reservations</h2>
            <?php if (empty($reservations)): ?>
                <div class="empty-state">
                    <p>You haven't made any reservations yet. <a href="../index.php">Book a room</a> to get started!</p>
                </div>
            <?php else: ?>
                <?php foreach ($reservations as $reservation): ?>
                    <div class="reservation-card">
                        <div class="reservation-header">
                            <div>
                                <h4><?= htmlspecialchars($reservation['type_name']) ?> - Room <?= $reservation['room_number'] ?></h4>
                                <span class="status status-<?= $reservation['status'] ?>"><?= ucfirst($reservation['status']) ?></span>
                            </div>
                            <div style="font-size: 1.2rem; font-weight: bold; color: #27ae60;">
                                $<?= number_format($reservation['total_price'], 2) ?>
                            </div>
                        </div>
                        
                        <div class="reservation-details">
                            <div class="detail-item">
                                <i class="fas fa-calendar-check"></i>
                                <span><?= date('M j, Y', strtotime($reservation['check_in_date'])) ?> - <?= date('M j, Y', strtotime($reservation['check_out_date'])) ?></span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-clock"></i>
                                <span>Booked on <?= date('M j, Y', strtotime($reservation['created_at'])) ?></span>
                            </div>
                        </div>
                        
                        <?php if ($reservation['status'] === 'completed' && !$reservation['has_review']): ?>
                            <button class="btn btn-review" onclick="toggleReviewForm(<?= $reservation['reservation_id'] ?>)">
                                <i class="fas fa-star"></i> Write Review
                            </button>
                            
                            <div id="reviewForm<?= $reservation['reservation_id'] ?>" class="review-form">
                                <form method="POST">
                                    <input type="hidden" name="room_id" value="<?= $reservation['room_id'] ?>">
                                    
                                    <div class="form-group">
                                        <label>Rating:</label>
                                        <select name="rating" required>
                                            <option value="">Select rating...</option>
                                            <option value="5">⭐⭐⭐⭐⭐ (5 - Excellent)</option>
                                            <option value="4">⭐⭐⭐⭐ (4 - Very Good)</option>
                                            <option value="3">⭐⭐⭐ (3 - Good)</option>
                                            <option value="2">⭐⭐ (2 - Fair)</option>
                                            <option value="1">⭐ (1 - Poor)</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Comment:</label>
                                        <textarea name="comment" rows="3" placeholder="Share your experience..." required></textarea>
                                    </div>
                                    
                                    <div style="display: flex; gap: 0.5rem;">
                                        <button type="submit" name="submit_review" class="btn">Submit Review</button>
                                        <button type="button" onclick="toggleReviewForm(<?= $reservation['reservation_id'] ?>)" class="btn btn-secondary">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        <?php elseif ($reservation['has_review']): ?>
                            <div class="review-status">
                                <i class="fas fa-check-circle"></i>
                                <span>Review submitted</span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- My Reviews -->
        <div class="card">
            <h2>My Reviews (<?= count($user_reviews) ?>)</h2>
            <?php if (empty($user_reviews)): ?>
                <p>You haven't written any reviews yet.</p>
            <?php else: ?>
                <?php foreach ($user_reviews as $review): ?>
                    <div class="review-item">
                        <div style="margin-bottom: 0.5rem;">
                            <strong><?= htmlspecialchars($review['type_name']) ?> - Room <?= $review['room_number'] ?></strong>
                        </div>
                        <div class="stars">
                            <?= str_repeat('⭐', $review['rating']) ?>
                        </div>
                        <p style="margin: 0.5rem 0;"><?= htmlspecialchars($review['comment']) ?></p>
                        <small style="color: #666;">Submitted on <?= date('M j, Y', strtotime($review['created_at'])) ?></small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleReviewForm(reservationId) {
            const form = document.getElementById('reviewForm' + reservationId);
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
            }
        }
    </script>
</body>
</html>
