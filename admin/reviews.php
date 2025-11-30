<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Handle review deletion
if (isset($_GET['action']) && isset($_GET['review_id']) && $_GET['action'] === 'delete') {
    $review_id = intval($_GET['review_id']);
    $stmt = $conn->prepare("DELETE FROM reviews WHERE review_id = ?");
    if ($stmt->execute([$review_id])) {
        $success = "Review deleted successfully!";
    }
}

// Get all reviews
$reviews_stmt = $conn->query("
    SELECT r.*, u.full_name, rm.room_number, rt.type_name
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    JOIN rooms rm ON r.room_id = rm.room_id
    JOIN room_types rt ON rm.type_id = rt.type_id
    ORDER BY r.created_at DESC
");
$reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_stmt = $conn->query("
    SELECT 
        COUNT(*) as total_reviews,
        AVG(rating) as avg_rating
    FROM reviews
");
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Reviews - Luxury Hotel Admin</title>
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
        
        .stats { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 1.5rem; 
            margin-bottom: 2rem; 
        }
        
        .stat-card { 
            background: var(--white); 
            padding: 1.5rem; 
            border-radius: 12px; 
            text-align: center; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
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
        
        .reviews-container { 
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
        
        .review-item { 
            padding: 2rem; 
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s;
        }
        
        .review-item:last-child { 
            border-bottom: none; 
        }
        
        .review-item:hover {
            background: var(--gray-light);
        }
        
        .review-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 1rem; 
        }
        
        .review-meta { 
            display: flex; 
            gap: 1.5rem; 
            align-items: center; 
            font-size: 0.9rem; 
            color: var(--gray);
            margin-bottom: 1rem;
        }
        
        .stars { 
            color: #ffc107; 
            font-size: 1.2rem; 
        }
        
        .actions { 
            display: flex; 
            gap: 0.5rem; 
        }
        
        .btn { 
            padding: 0.75rem 1.5rem; 
            border: none; 
            border-radius: 10px; 
            cursor: pointer; 
            text-decoration: none; 
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }
        
        .btn-delete { 
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); 
            color: var(--white);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }
        
        .btn-delete:hover { 
            background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(220, 38, 38, 0.4);
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
        
        .empty { 
            text-align: center; 
            padding: 4rem 2rem; 
            color: var(--gray); 
        }
        
        .empty i {
            font-size: 4rem;
            color: var(--gold-light);
            margin-bottom: 1rem;
        }
        
        .empty h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--navy);
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
                <a href="reservations.php"><i class="fas fa-calendar-check"></i> Reservations</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </div>

    <div class="container">
        <h1 class="page-title">
            <i class="fas fa-star"></i>
            Review Management
        </h1>
        
        <?php if (isset($success)): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i>
                <div><?= $success ?></div>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_reviews'] ?></div>
                <div class="stat-label">Total Reviews</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= number_format($stats['avg_rating'], 1) ?></div>
                <div class="stat-label">Average Rating</div>
            </div>
        </div>

        <!-- Reviews List -->
        <div class="reviews-container">
            <div class="section-header">
                <i class="fas fa-list"></i>
                All Reviews (<?= count($reviews) ?>)
            </div>
            
            <?php if (empty($reviews)): ?>
                <div class="empty">
                    <i class="fas fa-star"></i>
                    <h3>No Reviews Found</h3>
                    <p>Reviews will appear here once users start submitting them.</p>
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div>
                                <strong><?= htmlspecialchars($review['type_name']) ?> - Room <?= $review['room_number'] ?></strong>
                            </div>
                            <div class="actions">
                                <a href="?action=delete&review_id=<?= $review['review_id'] ?>" 
                                   class="btn btn-delete" 
                                   onclick="return confirm('Delete this review? This cannot be undone.')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                        
                        <div class="review-meta">
                            <span><i class="fas fa-user"></i> <?= htmlspecialchars($review['full_name']) ?></span>
                            <span class="stars"><?= str_repeat('⭐', $review['rating']) ?></span>
                            <span><i class="fas fa-calendar"></i> <?= date('M j, Y g:i A', strtotime($review['created_at'])) ?></span>
                        </div>
                        
                        <div style="margin-top: 1rem; line-height: 1.6; color: var(--navy);">
                            <?= htmlspecialchars($review['comment']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
