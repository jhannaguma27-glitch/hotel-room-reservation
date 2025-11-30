<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Handle user status updates
if ($_POST && isset($_POST['toggle_status'])) {
    $stmt = $conn->prepare("UPDATE users SET is_active = NOT is_active WHERE user_id = ?");
    $stmt->execute([$_POST['user_id']]);
    $success = "User status updated";
}

// Get all users
$stmt = $conn->prepare("
    SELECT u.*, COUNT(r.reservation_id) as total_reservations 
    FROM users u
    LEFT JOIN reservations r ON u.user_id = r.user_id
    GROUP BY u.user_id
    ORDER BY u.created_at DESC
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Users - Admin</title>
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
        
        .container { max-width: 1000px; margin: 2rem auto; padding: 0 1rem; }
        .page-title { font-size: 2rem; font-weight: 600; color: #1e293b; margin-bottom: 2rem; text-align: center; }
        
        .user { background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .user h4 { color: #1e293b; margin-bottom: 1rem; }
        .user-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
        .detail-item { }
        .detail-label { font-size: 0.875rem; color: #64748b; margin-bottom: 0.25rem; }
        .detail-value { font-weight: 500; color: #1e293b; }
        
        .status { display: inline-block; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500; margin-left: 1rem; }
        .status.active { background: #dcfce7; color: #166534; }
        .status.inactive { background: #fee2e2; color: #dc2626; }
        
        .btn { padding: 0.5rem 1rem; border: none; border-radius: 6px; cursor: pointer; transition: background 0.2s; font-size: 0.875rem; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-danger:hover { background: #b91c1c; }
        
        .form-controls { display: flex; gap: 1rem; align-items: center; margin-top: 1rem; }
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
        <h1 class="page-title">User Management</h1>
        
        <?php if (isset($success)): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <?php foreach ($users as $user): ?>
            <div class="user">
                <h4><?= htmlspecialchars($user['full_name']) ?></h4>
                
                <div class="user-details">
                    <div class="detail-item">
                        <div class="detail-label">Email</div>
                        <div class="detail-value"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                    <?php if ($user['phone']): ?>
                        <div class="detail-item">
                            <div class="detail-label">Phone</div>
                            <div class="detail-value"><?= htmlspecialchars($user['phone']) ?></div>
                        </div>
                    <?php endif; ?>
                    <div class="detail-item">
                        <div class="detail-label">Joined</div>
                        <div class="detail-value"><?= date('M j, Y', strtotime($user['created_at'])) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Total Reservations</div>
                        <div class="detail-value"><?= $user['total_reservations'] ?></div>
                    </div>
                </div>
                
                <div class="form-controls">
                    <form method="POST" style="display: inline-block;">
                        <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                        <button type="submit" name="toggle_status" class="btn <?= $user['is_active'] ? 'btn-danger' : 'btn-primary' ?>">
                            <?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>
                        </button>
                    </form>
                    
                    <span class="status <?= $user['is_active'] ? 'active' : 'inactive' ?>">
                        <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
