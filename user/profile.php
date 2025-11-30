<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if ($_POST && isset($_POST['full_name'])) {
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?");
    if ($stmt->execute([$_POST['full_name'], $_POST['phone'], $_SESSION['user_id']])) {
        $_SESSION['user_name'] = $_POST['full_name'];
        $success = "Profile updated successfully";
        // Refresh user data
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Profile - Luxury Hotel</title>
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
        
        .container { 
            max-width: 1000px; 
            margin: 3rem auto; 
            padding: 0 1.5rem; 
        }
        
        .card { 
            background: var(--white); 
            border-radius: 16px; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.08); 
            overflow: hidden;
            border: 1px solid #e2e8f0;
            margin-bottom: 2rem;
            position: relative;
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
        
        .card-header { 
            padding: 2.5rem 2.5rem 1.5rem; 
            border-bottom: 1px solid #e2e8f0; 
        }
        
        .card-title { 
            font-size: 1.75rem; 
            font-weight: 700; 
            color: var(--navy); 
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .card-title i {
            color: var(--gold);
        }
        
        .card-body { 
            padding: 2.5rem; 
        }
        
        .form-group { 
            margin-bottom: 2rem; 
        }
        
        .form-label { 
            display: block; 
            margin-bottom: 0.75rem; 
            font-weight: 600; 
            color: var(--navy);
            font-size: 0.95rem;
        }
        
        .form-input { 
            width: 100%; 
            padding: 1rem 1.25rem; 
            border: 2px solid #e2e8f0; 
            border-radius: 10px; 
            font-size: 1rem; 
            transition: all 0.3s;
            background: var(--white);
            color: var(--navy);
        }
        
        .form-input:focus { 
            outline: none; 
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
            transform: translateY(-2px);
        }
        
        .form-input:disabled { 
            background: var(--gray-light); 
            color: var(--gray);
            cursor: not-allowed;
        }
        
        .btn { 
            display: inline-flex; 
            align-items: center; 
            gap: 0.5rem;
            padding: 1rem 2rem; 
            border: none; 
            border-radius: 10px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.3s; 
            text-decoration: none; 
            font-size: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .btn-primary { 
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%); 
            color: var(--navy); 
        }
        
        .btn-primary:hover { 
            background: linear-gradient(135deg, var(--gold-dark) 0%, #9c7c18 100%); 
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(212, 175, 55, 0.4);
        }
        
        .btn-secondary { 
            background: var(--navy-lighter); 
            color: var(--white); 
        }
        
        .btn-secondary:hover { 
            background: var(--navy-light);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.3);
        }
        
        .alert { 
            padding: 1.25rem 1.5rem; 
            border-radius: 12px; 
            margin-bottom: 2rem; 
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            animation: slideIn 0.5s ease-out;
        }
        
        .alert-success { 
            background: #dcfce7; 
            color: #166534; 
            border: 1px solid #bbf7d0;
            border-left: 4px solid #22c55e;
        }
        
        .alert i {
            font-size: 1.25rem;
        }
        
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
            gap: 1.5rem; 
            margin-bottom: 3rem; 
        }
        
        .stat-card { 
            background: var(--white); 
            padding: 2rem; 
            border-radius: 16px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.08); 
            text-align: center;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--gold), var(--gold-dark));
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.15);
        }
        
        .stat-number { 
            font-size: 2.5rem; 
            font-weight: 700; 
            color: var(--gold-dark);
            margin-bottom: 0.5rem;
        }
        
        .stat-label { 
            color: var(--gray); 
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
            padding: 2rem;
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
            border-radius: 16px;
            color: var(--white);
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--gold);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--navy);
            font-weight: 700;
        }
        
        .profile-info h1 {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }
        
        .profile-info p {
            color: var(--gold-light);
            opacity: 0.9;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 2rem;
        }
        
        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .card, .stats-grid, .profile-header {
            animation: fadeIn 0.6s ease-out;
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
                margin: 2rem auto;
                padding: 0 1rem;
            }
            
            .card-header,
            .card-body {
                padding: 1.5rem;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* Loading state for form */
        .loading {
            position: relative;
            pointer-events: none;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo"><i class="fas fa-crown"></i> Luxury Hotel</div>
            <nav class="nav">
                <a href="../index.php"><i class="fas fa-home"></i> Home</a>
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </div>

    <div class="container">
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div><?= $success ?></div>
            </div>
        <?php endif; ?>

        <?php
        // Get user stats
        $stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(total_price) as spent FROM reservations WHERE user_id = ? AND status != 'cancelled'");
        $stmt->execute([$_SESSION['user_id']]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        ?>

        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
            </div>
            <div class="profile-info">
                <h1><?= htmlspecialchars($user['full_name']) ?></h1>
                <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?></p>
                <p><i class="fas fa-calendar"></i> Member since <?= date('F Y', strtotime($user['created_at'])) ?></p>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total'] ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">$<?= number_format($stats['spent'] ?? 0, 0) ?></div>
                <div class="stat-label">Total Spent</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <i class="fas fa-star" style="color: var(--gold);"></i>
                    <?= $stats['total'] >= 10 ? 'Gold' : ($stats['total'] >= 5 ? 'Silver' : 'Bronze') ?>
                </div>
                <div class="stat-label">Member Tier</div>
            </div>
        </div>

        <!-- Profile Form -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-user-edit"></i>
                    Profile Information
                </h2>
            </div>
            <div class="card-body">
                <form method="POST" id="profileForm">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-input" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-input" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                        <small style="color: var(--gray); margin-top: 0.5rem; display: block;">
                            <i class="fas fa-info-circle"></i> Email cannot be changed
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone" class="form-input" value="<?= htmlspecialchars($user['phone']) ?>" placeholder="Enter your phone number">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Member Since</label>
                        <input type="text" class="form-input" value="<?= date('F j, Y', strtotime($user['created_at'])) ?>" disabled>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save"></i>
                            Update Profile
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profileForm = document.getElementById('profileForm');
            const submitBtn = document.getElementById('submitBtn');
            
            profileForm.addEventListener('submit', function(e) {
                // Add loading state
                submitBtn.classList.add('loading');
                submitBtn.innerHTML = '<i class="fas fa-spinner"></i> Updating...';
                
                // Form will submit normally, this is just for visual feedback
            });
            
            // Add focus effects to form inputs
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateX(10px)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateX(0)';
                });
            });
        });
    </script>
</body>
</html>