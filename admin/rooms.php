<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Handle room creation/updates
if ($_POST) {
    if (isset($_POST['add_room'])) {
        $stmt = $conn->prepare("INSERT INTO rooms (room_number, type_id, max_guests, amenities, created_by_admin) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['room_number'], $_POST['type_id'], $_POST['max_guests'], $_POST['amenities'], $_SESSION['admin_id']]);
        $success = "Room added successfully";
    } elseif (isset($_POST['update_status'])) {
        $stmt = $conn->prepare("UPDATE rooms SET status = ? WHERE room_id = ?");
        $stmt->execute([$_POST['status'], $_POST['room_id']]);
        $success = "Room status updated";
    }
}

// Get room types for dropdown
$stmt = $conn->query("SELECT * FROM room_types ORDER BY type_name");
$room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all rooms
$stmt = $conn->prepare("
    SELECT r.*, rt.type_name, rt.base_price 
    FROM rooms r
    JOIN room_types rt ON r.type_id = rt.type_id
    ORDER BY r.room_number
");
$stmt->execute();
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Rooms - Luxury Hotel Admin</title>
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
            transform: translateY(30px);
            opacity: 0;
            transition: all 0.8s ease-out;
        }
        
        .page-title.visible {
            transform: translateY(0);
            opacity: 1;
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
            transform: translateY(30px);
            opacity: 0;
            transition: all 0.8s ease-out 0.2s;
        }
        
        .form-container.visible {
            transform: translateY(0);
            opacity: 1;
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
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
        
        .form-input, .form-select, .form-textarea { 
            width: 100%; 
            padding: 1rem 1.25rem; 
            border: 2px solid #e2e8f0; 
            border-radius: 10px; 
            transition: all 0.3s;
            background: var(--white);
            color: var(--navy);
            font-size: 1rem;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus { 
            outline: none; 
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
            transform: translateY(-2px);
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
        }
        
        .btn:hover { 
            background: linear-gradient(135deg, var(--gold-dark) 0%, #9c7c18 100%); 
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(212, 175, 55, 0.4);
        }
        
        /* Room Cards */
        .rooms-section {
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
            transform: translateX(-30px);
            opacity: 0;
            transition: all 0.6s ease-out 0.4s;
        }
        
        .section-title.visible {
            transform: translateX(0);
            opacity: 1;
        }
        
        .section-title i {
            color: var(--gold);
        }
        
        .room { 
            background: var(--white); 
            border-radius: 16px; 
            padding: 0;
            margin-bottom: 2rem; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
            transform: translateY(30px);
            opacity: 0;
            transition: all 0.6s ease-out;
        }
        
        .room.visible {
            transform: translateY(0);
            opacity: 1;
        }
        
        .room::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--gold), var(--gold-dark));
        }
        
        .room-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 2rem 2rem 1rem;
        }
        
        .room-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--navy);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .room-image {
            width: 100%;
            height: 200px;
            background: var(--gray-light);
            position: relative;
            overflow: hidden;
        }
        
        .room-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .room:hover .room-image img {
            transform: scale(1.05);
        }
        
        .room-overlay {
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
        
        .room-body {
            padding: 1.5rem 2rem;
        }
        
        .room-details { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); 
            gap: 1.5rem; 
            margin-bottom: 1.5rem; 
        }
        
        .detail-item { }
        .detail-label { 
            font-size: 0.875rem; 
            color: var(--gray); 
            margin-bottom: 0.5rem; 
            font-weight: 500;
        }
        .detail-value { 
            font-weight: 600; 
            color: var(--navy); 
            font-size: 1.1rem;
        }
        
        .form-controls { 
            display: flex; 
            gap: 1.5rem; 
            align-items: center; 
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }
        
        .status { 
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem; 
            border-radius: 8px; 
            font-size: 0.875rem; 
            font-weight: 600;
        }
        
        .status.available { 
            background: #dcfce7; 
            color: #166534; 
        }
        .status.occupied { 
            background: #fee2e2; 
            color: #dc2626; 
        }
        .status.maintenance { 
            background: #fef3c7; 
            color: #92400e; 
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
            transform: scale(0.9);
            opacity: 0;
            transition: all 0.5s ease-out;
        }
        
        .success.visible {
            transform: scale(1);
            opacity: 1;
        }
        
        .success i {
            font-size: 1.25rem;
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
            
            .form-container {
                padding: 2rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-controls {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .room-header {
                flex-direction: column;
                align-items: flex-start;
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
        
        /* Stagger delays for rooms */
        .room:nth-child(1) { transition-delay: 0.1s; }
        .room:nth-child(2) { transition-delay: 0.2s; }
        .room:nth-child(3) { transition-delay: 0.3s; }
        .room:nth-child(4) { transition-delay: 0.4s; }
        .room:nth-child(5) { transition-delay: 0.5s; }
        .room:nth-child(6) { transition-delay: 0.6s; }
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
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </div>

    <div class="container">
        <h1 class="page-title" id="pageTitle">
            <i class="fas fa-door-open"></i>
            Room Management
        </h1>
        
        <?php if (isset($success)): ?>
            <div class="success" id="successMessage">
                <i class="fas fa-check-circle"></i>
                <div><?= $success ?></div>
            </div>
        <?php endif; ?>

        <div class="form-container" id="addRoomForm">
            <h3 class="form-title">
                <i class="fas fa-plus-circle"></i>
                Add New Room
            </h3>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Room Number</label>
                        <input type="text" name="room_number" class="form-input" placeholder="e.g., 101, 202" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Room Type</label>
                        <select name="type_id" class="form-select" required>
                            <?php foreach ($room_types as $type): ?>
                                <option value="<?= $type['type_id'] ?>"><?= htmlspecialchars($type['type_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Max Guests</label>
                        <input type="number" name="max_guests" class="form-input" min="1" max="10" placeholder="1-10" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Amenities</label>
                    <textarea name="amenities" class="form-textarea" rows="3" placeholder="List room amenities (comma separated)"></textarea>
                </div>
                <button type="submit" name="add_room" class="btn">
                    <i class="fas fa-plus"></i>
                    Add Room
                </button>
            </form>
        </div>

        <div class="rooms-section">
            <h3 class="section-title" id="sectionTitle">
                <i class="fas fa-list"></i>
                All Rooms
            </h3>
            
            <?php 
            // Room images based on type
            $roomImages = [
                'standard_room' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80',
                'deluxe_room' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80',
                'suite' => 'https://images.unsplash.com/photo-1564078516393-cf04bd966897?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80',
                'executive_suite' => 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80',
                'presidential_suite' => 'https://images.unsplash.com/photo-1618773928121-c32242e63f39?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80'
            ];
            
            foreach ($rooms as $index => $room): 
                $roomType = strtolower(str_replace(' ', '_', $room['type_name']));
                $imageUrl = $roomImages[$roomType] ?? $roomImages['standard_room'];
            ?>
                <div class="room" id="room<?= $index ?>">
                    <div class="room-header">
                        <h4 class="room-title">
                            <i class="fas fa-door-closed"></i>
                            Room <?= htmlspecialchars($room['room_number']) ?> - <?= htmlspecialchars($room['type_name']) ?>
                        </h4>
                    </div>
                    
                    <div class="room-image">
                        <img src="<?= $imageUrl ?>" alt="<?= htmlspecialchars($room['type_name']) ?>">
                        <div class="room-overlay">
                            <div class="detail-value" style="color: var(--white);">
                                $<?= number_format($room['base_price'], 2) ?>/night
                            </div>
                        </div>
                    </div>
                    
                    <div class="room-body">
                        <div class="room-details">
                            <div class="detail-item">
                                <div class="detail-label">Max Guests</div>
                                <div class="detail-value"><?= $room['max_guests'] ?> <i class="fas fa-user-friends" style="color: var(--gold);"></i></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Base Price</div>
                                <div class="detail-value">$<?= number_format($room['base_price'], 2) ?>/night</div>
                            </div>
                            <?php if ($room['amenities']): ?>
                                <div class="detail-item">
                                    <div class="detail-label">Amenities</div>
                                    <div class="detail-value"><?= htmlspecialchars($room['amenities']) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-controls">
                            <form method="POST" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                                <input type="hidden" name="room_id" value="<?= $room['room_id'] ?>">
                                <select name="status" class="form-select" style="width: auto; min-width: 150px;">
                                    <option value="available" <?= $room['status'] == 'available' ? 'selected' : '' ?>>Available</option>
                                    <option value="occupied" <?= $room['status'] == 'occupied' ? 'selected' : '' ?>>Occupied</option>
                                    <option value="maintenance" <?= $room['status'] == 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                </select>
                                <button type="submit" name="update_status" class="btn" style="padding: 0.75rem 1.5rem;">
                                    <i class="fas fa-sync-alt"></i>
                                    Update Status
                                </button>
                            </form>
                            
                            <span class="status <?= $room['status'] ?>">
                                <i class="fas fa-<?= 
                                    $room['status'] == 'available' ? 'check-circle' : 
                                    ($room['status'] == 'occupied' ? 'bed' : 'tools')
                                ?>"></i>
                                <?= ucfirst($room['status']) ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const pageTitle = document.getElementById('pageTitle');
            const addRoomForm = document.getElementById('addRoomForm');
            const sectionTitle = document.getElementById('sectionTitle');
            const rooms = document.querySelectorAll('.room');
            const successMessage = document.getElementById('successMessage');
            const scrollProgress = document.querySelector('.scroll-progress');
            const scrollTopBtn = document.getElementById('scrollTop');
            
            // Make elements visible on load with staggered delays
            setTimeout(() => {
                pageTitle.classList.add('visible');
            }, 100);
            
            setTimeout(() => {
                addRoomForm.classList.add('visible');
            }, 300);
            
            setTimeout(() => {
                sectionTitle.classList.add('visible');
            }, 500);
            
            setTimeout(() => {
                rooms.forEach(room => room.classList.add('visible'));
            }, 700);
            
            if (successMessage) {
                setTimeout(() => {
                    successMessage.classList.add('visible');
                }, 900);
            }
            
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
                
                // Parallax effect for room images
                rooms.forEach((room, index) => {
                    const roomOffset = room.offsetTop;
                    if (scrollY > roomOffset - windowHeight * 0.8) {
                        const progress = Math.min(1, (scrollY - roomOffset + windowHeight * 0.8) / 200);
                        const image = room.querySelector('.room-image img');
                        if (image) {
                            image.style.transform = `scale(${1 + progress * 0.05})`;
                        }
                    }
                });
            }
            
            // Initialize scroll animations
            triggerScrollAnimations();
            
            // Add hover effects to room cards
            rooms.forEach(room => {
                room.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 12px 30px rgba(0,0,0,0.15)';
                });
                
                room.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 8px 25px rgba(0,0,0,0.08)';
                });
            });
            
            // Add form submission animation
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                        submitBtn.disabled = true;
                    }
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
                }
            });
        }, observerOptions);
        
        // Observe all animated elements
        document.addEventListener('DOMContentLoaded', () => {
            const animatedElements = document.querySelectorAll('.room');
            animatedElements.forEach(el => observer.observe(el));
        });
    </script>
</body>
</html>