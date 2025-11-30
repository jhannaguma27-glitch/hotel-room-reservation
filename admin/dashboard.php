<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Get statistics
$stats = [];
$stmt = $conn->query("SELECT COUNT(*) as total_reservations FROM reservations");
$stats['reservations'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_reservations'];

$stmt = $conn->query("SELECT COUNT(*) as total_users FROM users WHERE is_active = 1");
$stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

$stmt = $conn->query("SELECT COUNT(*) as total_rooms FROM rooms");
$stats['rooms'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_rooms'];

$stmt = $conn->query("SELECT SUM(total_price) as total_revenue FROM reservations WHERE status IN ('confirmed', 'completed')");
$stats['revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;

// Get recent reservations
$stmt = $conn->prepare("
    SELECT r.*, u.full_name, ro.room_number, rt.type_name 
    FROM reservations r
    JOIN users u ON r.user_id = u.user_id
    JOIN rooms ro ON r.room_id = ro.room_id
    JOIN room_types rt ON ro.type_id = rt.type_id
    ORDER BY r.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recent_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Luxury Hotel</title>
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
        
        /* Welcome Section */
        .welcome { 
            background: var(--white); 
            padding: 2.5rem; 
            border-radius: 16px; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.08); 
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            border-left: 5px solid var(--gold);
            transform: translateY(30px);
            opacity: 0;
            transition: all 0.8s ease-out;
        }
        
        .welcome.visible {
            transform: translateY(0);
            opacity: 1;
        }
        
        .welcome::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, var(--gold-light) 0%, transparent 70%);
            border-radius: 0 0 0 100%;
            opacity: 0.3;
        }
        
        .welcome h1 { 
            color: var(--navy); 
            margin-bottom: 0.5rem; 
            font-size: 2rem;
        }
        
        .welcome p { 
            color: var(--gray); 
            font-size: 1.1rem;
        }
        
        /* Navigation Cards */
        .nav-cards { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
            gap: 1.5rem; 
            margin-bottom: 3rem; 
        }
        
        .nav-card { 
            background: var(--white); 
            padding: 2rem; 
            border-radius: 16px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.08); 
            text-align: center; 
            transition: all 0.3s;
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
            transform: translateY(30px);
            opacity: 0;
        }
        
        .nav-card.visible {
            transform: translateY(0);
            opacity: 1;
        }
        
        .nav-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--gold), var(--gold-dark));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease-out;
        }
        
        .nav-card:hover::before {
            transform: scaleX(1);
        }
        
        .nav-card:hover { 
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.15);
        }
        
        .nav-card a { 
            text-decoration: none; 
            color: var(--navy); 
            font-weight: 600;
            display: block;
        }
        
        .nav-card-icon { 
            font-size: 2.5rem; 
            margin-bottom: 1rem;
            color: var(--gold);
            transition: transform 0.3s;
        }
        
        .nav-card:hover .nav-card-icon {
            transform: scale(1.2);
        }
        
        /* Stats Grid */
        .stats { 
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
            position: relative;
            overflow: hidden;
            transform: scale(0.9);
            opacity: 0;
            transition: all 0.6s ease-out;
        }
        
        .stat-card.visible {
            transform: scale(1);
            opacity: 1;
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
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
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
        
        /* Section Title */
        .section-title { 
            font-size: 1.75rem; 
            font-weight: 700; 
            color: var(--navy); 
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transform: translateX(-30px);
            opacity: 0;
            transition: all 0.6s ease-out;
        }
        
        .section-title.visible {
            transform: translateX(0);
            opacity: 1;
        }
        
        .section-title i {
            color: var(--gold);
        }
        
        /* Reservations */
        .reservation { 
            background: var(--white); 
            border-radius: 12px; 
            padding: 1.5rem; 
            margin-bottom: 1rem; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border-left: 4px solid var(--gold);
            transition: all 0.3s;
            transform: translateX(-20px);
            opacity: 0;
        }
        
        .reservation.visible {
            transform: translateX(0);
            opacity: 1;
        }
        
        .reservation:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .status { 
            padding: 0.5rem 1rem; 
            border-radius: 8px; 
            font-size: 0.875rem; 
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .status.confirmed { 
            background: #dcfce7; 
            color: #166534; 
        }
        
        .status.pending { 
            background: #fef3c7; 
            color: #92400e; 
        }
        
        .status.cancelled { 
            background: #fee2e2; 
            color: #dc2626; 
        }
        
        .status.completed { 
            background: #dbeafe; 
            color: #1d4ed8; 
        }
        
        .status.checked_in { 
            background: #e0e7ff; 
            color: #6366f1; 
        }
        
        /* Scroll Progress */
        .scroll-progress {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 3px;
            background: linear-gradient(90deg, var(--gold), var(--gold-dark));
            z-index: 1000;
            transition: width 0.3s ease;
        }
        
        /* Scroll to top button */
        .scroll-top {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--gold);
            color: var(--navy);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            transform: translateY(20px) scale(0);
            transition: all 0.5s ease;
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.4);
            z-index: 100;
        }
        
        .scroll-top.visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        
        .scroll-top:hover {
            transform: translateY(-5px) scale(1.1);
            box-shadow: 0 6px 16px rgba(212, 175, 55, 0.6);
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
            
            .welcome {
                padding: 2rem;
            }
            
            .welcome h1 {
                font-size: 1.75rem;
            }
            
            .nav-cards {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 1rem;
            }
            
            .stats {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 1rem;
            }
            
            .scroll-top {
                bottom: 1rem;
                right: 1rem;
            }
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        /* Stagger delays for cards */
        .nav-card:nth-child(1) { transition-delay: 0.1s; }
        .nav-card:nth-child(2) { transition-delay: 0.2s; }
        .nav-card:nth-child(3) { transition-delay: 0.3s; }
        .nav-card:nth-child(4) { transition-delay: 0.4s; }
        
        .stat-card:nth-child(1) { transition-delay: 0.2s; }
        .stat-card:nth-child(2) { transition-delay: 0.3s; }
        .stat-card:nth-child(3) { transition-delay: 0.4s; }
        .stat-card:nth-child(4) { transition-delay: 0.5s; }
        
        .reservation:nth-child(1) { transition-delay: 0.1s; }
        .reservation:nth-child(2) { transition-delay: 0.2s; }
        .reservation:nth-child(3) { transition-delay: 0.3s; }
        .reservation:nth-child(4) { transition-delay: 0.4s; }
        .reservation:nth-child(5) { transition-delay: 0.5s; }
    </style>
</head>
<body>
    <!-- Scroll Progress Indicator -->
    <div class="scroll-progress"></div>
    
    <!-- Scroll to Top Button -->
    <div class="scroll-top" id="scrollTop">
        <i class="fas fa-chevron-up"></i>
    </div>
    
    <div class="header">
        <div class="header-content">
            <div class="logo"><i class="fas fa-crown"></i> Luxury Hotel Admin</div>
            <nav class="nav">
                <div class="admin-info">
                    <span>Welcome, <?= htmlspecialchars($_SESSION['admin_username']) ?></span>
                    <span class="admin-badge"><?= ucfirst($_SESSION['admin_role']) ?></span>
                </div>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </div>

    <div class="container">
        <div class="welcome" id="welcomeSection">
            <h1>Admin Dashboard</h1>
            <p>Manage your hotel operations from this central hub with real-time insights and analytics.</p>
        </div>

        <div class="nav-cards">
            <div class="nav-card" id="navCard1">
                <div class="nav-card-icon"><i class="fas fa-calendar-check"></i></div>
                <a href="reservations.php">Manage Reservations</a>
            </div>
            <div class="nav-card" id="navCard2">
                <div class="nav-card-icon"><i class="fas fa-bed"></i></div>
                <a href="rooms.php">Manage Rooms</a>
            </div>
            <div class="nav-card" id="navCard3">
                <div class="nav-card-icon"><i class="fas fa-users"></i></div>
                <a href="users.php">Manage Users</a>
            </div>
            <div class="nav-card" id="navCard4">
                <div class="nav-card-icon"><i class="fas fa-door-open"></i></div>
                <a href="room_types.php">Room Types</a>
            </div>
        </div>
        
        <div class="stats">
            <div class="stat-card" id="statCard1">
                <div class="stat-number"><?= $stats['reservations'] ?></div>
                <div class="stat-label">Total Reservations</div>
            </div>
            <div class="stat-card" id="statCard2">
                <div class="stat-number"><?= $stats['users'] ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card" id="statCard3">
                <div class="stat-number"><?= $stats['rooms'] ?></div>
                <div class="stat-label">Total Rooms</div>
            </div>
            <div class="stat-card" id="statCard4">
                <div class="stat-number">$<?= number_format($stats['revenue'], 0) ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>

        <h2 class="section-title" id="sectionTitle">
            <i class="fas fa-clock"></i>
            Recent Reservations
        </h2>
        
        <?php foreach ($recent_reservations as $index => $reservation): ?>
            <div class="reservation" id="reservation<?= $index ?>">
                <strong><?= htmlspecialchars($reservation['full_name']) ?></strong> - 
                Room <?= htmlspecialchars($reservation['room_number']) ?> (<?= htmlspecialchars($reservation['type_name']) ?>)
                <br>
                <small>
                    <?= date('M j, Y', strtotime($reservation['check_in_date'])) ?> to 
                    <?= date('M j, Y', strtotime($reservation['check_out_date'])) ?> - 
                    $<?= number_format($reservation['total_price'], 2) ?>
                </small>
                <span class="status <?= $reservation['status'] ?>">
                    <i class="fas fa-<?= 
                        $reservation['status'] == 'confirmed' ? 'check-circle' : 
                        ($reservation['status'] == 'pending' ? 'clock' : 
                        ($reservation['status'] == 'cancelled' ? 'times-circle' : 
                        ($reservation['status'] == 'completed' ? 'flag-checkered' : 'door-open'))) 
                    ?>"></i>
                    <?= ucfirst($reservation['status']) ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const welcomeSection = document.getElementById('welcomeSection');
            const navCards = document.querySelectorAll('.nav-card');
            const statCards = document.querySelectorAll('.stat-card');
            const sectionTitle = document.getElementById('sectionTitle');
            const reservations = document.querySelectorAll('.reservation');
            const scrollProgress = document.querySelector('.scroll-progress');
            const scrollTopBtn = document.getElementById('scrollTop');
            
            // Make elements visible on load with staggered delays
            setTimeout(() => {
                welcomeSection.classList.add('visible');
            }, 100);
            
            setTimeout(() => {
                navCards.forEach(card => card.classList.add('visible'));
            }, 300);
            
            setTimeout(() => {
                statCards.forEach(card => card.classList.add('visible'));
            }, 600);
            
            setTimeout(() => {
                sectionTitle.classList.add('visible');
            }, 800);
            
            setTimeout(() => {
                reservations.forEach(reservation => reservation.classList.add('visible'));
            }, 1000);
            
            // Scroll progress indicator
            window.addEventListener('scroll', function() {
                const scrollTop = window.pageYOffset;
                const docHeight = document.documentElement.scrollHeight - window.innerHeight;
                const scrollPercent = (scrollTop / docHeight) * 100;
                scrollProgress.style.width = scrollPercent + '%';
                
                // Show/hide scroll to top button
                if (scrollTop > 300) {
                    scrollTopBtn.classList.add('visible');
                } else {
                    scrollTopBtn.classList.remove('visible');
                }
                
                // Additional scroll-triggered animations
                triggerScrollAnimations();
            });
            
            // Scroll to top functionality
            scrollTopBtn.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
            
            // Scroll-triggered animations
            function triggerScrollAnimations() {
                const scrollY = window.pageYOffset;
                const windowHeight = window.innerHeight;
                
                // Parallax effect for welcome section
                if (welcomeSection) {
                    const welcomeOffset = welcomeSection.offsetTop;
                    if (scrollY > welcomeOffset - windowHeight * 0.8) {
                        welcomeSection.style.transform = `translateY(${scrollY * 0.05}px)`;
                    }
                }
                
                // Scale effect for stat cards on scroll
                statCards.forEach((card, index) => {
                    const cardOffset = card.offsetTop;
                    if (scrollY > cardOffset - windowHeight * 0.8) {
                        const progress = Math.min(1, (scrollY - cardOffset + windowHeight * 0.8) / 200);
                        card.style.transform = `scale(${0.9 + progress * 0.1})`;
                    }
                });
            }
            
            // Initialize scroll animations
            triggerScrollAnimations();
            
            // Add hover effects to navigation cards
            navCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
            
            // Add click effects to stat cards
            statCards.forEach(card => {
                card.addEventListener('click', function() {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                    }, 150);
                });
            });
        });
        
        // Intersection Observer for scroll-triggered animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                    // Add pulse animation to stat cards when they come into view
                    if (entry.target.classList.contains('stat-card')) {
                        entry.target.style.animation = 'pulse 2s ease-in-out';
                    }
                }
            });
        }, observerOptions);
        
        // Observe all animated elements
        document.addEventListener('DOMContentLoaded', () => {
            const animatedElements = document.querySelectorAll('.nav-card, .stat-card, .reservation');
            animatedElements.forEach(el => observer.observe(el));
        });
    </script>
</body>
</html>