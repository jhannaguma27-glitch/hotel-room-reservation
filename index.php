<?php
session_start();
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT * FROM room_types ORDER BY base_price");
$stmt->execute();
$room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user reservations if logged in
$reservations = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("
        SELECT r.*, ro.room_number, rt.type_name, rt.base_price 
        FROM reservations r
        JOIN rooms ro ON r.room_id = ro.room_id
        JOIN room_types rt ON ro.type_id = rt.type_id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Caliview Hotel - Luxury Accommodations</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Experience unparalleled luxury at Caliview Hotel. Premium accommodations with exceptional service in the heart of the city.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 8px 25px rgba(0,0,0,0.1);
            --shadow-lg: 0 20px 40px rgba(0,0,0,0.15);
            --border-radius: 16px;
        }
        
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, var(--gray-light) 0%, #f1f5f9 100%);
            color: var(--navy); 
            line-height: 1.7;
            overflow-x: hidden;
        }
        
        /* Skip to main content for accessibility */
        .skip-link {
            position: absolute;
            top: -40px;
            left: 6px;
            background: var(--gold);
            color: var(--navy);
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            z-index: 10000;
            transition: var(--transition);
        }
        
        .skip-link:focus {
            top: 0;
        }
        
        /* Header Styles */
        .header { 
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 1rem 2rem;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            transition: var(--transition);
        }
        
        .header.scrolled {
            background: rgba(15, 23, 42, 0.98);
            box-shadow: var(--shadow-lg);
            padding: 0.75rem 2rem;
        }
        
        .header-content { 
            max-width: 1400px; 
            margin: 0 auto; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        
        /* Enhanced Logo Styles */
        .logo { 
            display: flex; 
            align-items: center; 
            gap: 1rem; 
            text-decoration: none;
            color: var(--white);
            position: relative;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.15) 0%, rgba(212, 175, 55, 0.05) 100%);
            border: 1px solid rgba(212, 175, 55, 0.2);
        }
        
        .logo:hover {
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.25) 0%, rgba(212, 175, 55, 0.15) 100%);
            border-color: var(--gold);
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(212, 175, 55, 0.25);
        }
        
        .logo-image {
            height: 45px;
            width: auto;
            filter: brightness(0) invert(1) drop-shadow(0 0 12px rgba(212, 175, 55, 0.6));
            transition: var(--transition);
        }
        
        .logo:hover .logo-image {
            filter: brightness(0) invert(1) drop-shadow(0 0 20px var(--gold));
            transform: scale(1.1);
        }
        
        .logo-text {
            font-size: 1.8rem; 
            font-weight: 800; 
            background: linear-gradient(135deg, var(--white) 0%, var(--gold-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
            transition: var(--transition);
        }
        
        .logo:hover .logo-text {
            background: linear-gradient(135deg, var(--gold-light) 0%, var(--gold) 100%);
        }
        
        /* Navigation */
        .nav { 
            display: flex; 
            gap: 1.5rem; 
            align-items: center; 
        }
        
        .nav a { 
            color: var(--white); 
            text-decoration: none; 
            font-weight: 500; 
            transition: var(--transition); 
            padding: 0.75rem 1.5rem;
            position: relative;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
        }
        
        .nav a:hover { 
            color: var(--gold); 
            background: rgba(212, 175, 55, 0.15);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .nav a i {
            font-size: 1.1rem;
            transition: var(--transition);
        }
        
        .nav a:hover i {
            transform: scale(1.1);
        }
        
        /* Hero Section */
        .hero {
            background: 
                linear-gradient(135deg, rgba(15, 23, 42, 0.85) 0%, rgba(15, 23, 42, 0.75) 100%),
                url('https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: var(--white);
            padding: 12rem 2rem 8rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 50% 50%, rgba(212, 175, 55, 0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 0.8; }
        }
        
        .hero-content { 
            max-width: 800px; 
            margin: 0 auto; 
            position: relative;
            z-index: 2;
        }
        
        .hero h1 { 
            font-size: 4rem; 
            font-weight: 800; 
            margin-bottom: 1.5rem;
            text-shadow: 0 4px 20px rgba(0,0,0,0.3);
            line-height: 1.1;
            background: linear-gradient(135deg, var(--white) 0%, var(--gold-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero p { 
            font-size: 1.4rem; 
            opacity: 0.9; 
            margin-bottom: 3rem;
            line-height: 1.8;
            font-weight: 300;
        }
        
        /* Container */
        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
            padding: 0 2rem; 
        }
        
        /* Button Styles */
        .btn { 
            display: inline-flex; 
            align-items: center; 
            gap: 0.75rem;
            padding: 1rem 2.5rem; 
            border: none; 
            border-radius: 12px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: var(--transition); 
            text-decoration: none; 
            font-size: 1.1rem;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: var(--transition);
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary { 
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%); 
            color: var(--navy); 
            box-shadow: var(--shadow-md);
        }
        
        .btn-primary:hover { 
            background: linear-gradient(135deg, var(--gold-dark) 0%, #9c7c18 100%); 
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(212, 175, 55, 0.4);
        }
        
        .btn-outline { 
            background: transparent; 
            color: var(--gold); 
            border: 2px solid var(--gold);
            backdrop-filter: blur(10px);
        }
        
        .btn-outline:hover { 
            background: var(--gold); 
            color: var(--navy);
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(212, 175, 55, 0.3);
        }
        
        .btn-danger { 
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); 
            color: var(--white);
            box-shadow: var(--shadow-md);
        }
        
        .btn-danger:hover { 
            background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%); 
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(220, 38, 38, 0.3);
        }
        
        /* Section Titles */
        .section-title { 
            text-align: center; 
            font-size: 3rem; 
            font-weight: 800; 
            margin: 6rem 0 4rem; 
            color: var(--navy);
            position: relative;
        }
        
        .section-title::after {
            content: '';
            display: block;
            width: 100px;
            height: 6px;
            background: linear-gradient(90deg, var(--gold), var(--gold-dark));
            margin: 1.5rem auto;
            border-radius: 3px;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
        }
        
        /* Welcome Section */
        .welcome { 
            background: linear-gradient(135deg, var(--white) 0%, #f8fafc 100%);
            padding: 4rem; 
            border-radius: var(--border-radius); 
            box-shadow: var(--shadow-lg); 
            margin: 4rem 0; 
            border-left: 6px solid var(--gold);
            position: relative;
            overflow: hidden;
            text-align: center;
        }
        
        .welcome::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, var(--gold-light) 0%, transparent 70%);
            opacity: 0.1;
            border-radius: 50%;
        }
        
        .welcome h1 { 
            color: var(--navy); 
            margin-bottom: 1.5rem; 
            font-size: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }
        
        .welcome h1 i { 
            color: var(--gold);
            font-size: 2.2rem;
        }
        
        .welcome p { 
            color: var(--gray); 
            font-size: 1.3rem;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.8;
        }
        
        /* Alert */
        .alert { 
            padding: 1.5rem 2rem; 
            border-radius: var(--border-radius); 
            margin-bottom: 3rem; 
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #166534; 
            border: 1px solid #22c55e; 
            display: flex;
            align-items: center;
            gap: 1.5rem;
            box-shadow: var(--shadow-md);
            border-left: 6px solid #22c55e;
        }
        
        .alert i { 
            font-size: 2rem; 
            color: #22c55e;
        }
        
        /* Room Cards Grid */
        .rooms-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); 
            gap: 3rem; 
            margin: 4rem 0; 
        }
        
        .room-card { 
            background: var(--white); 
            border-radius: var(--border-radius); 
            overflow: hidden; 
            box-shadow: var(--shadow-md); 
            transition: var(--transition);
            border: 1px solid rgba(226, 232, 240, 0.8);
            position: relative;
        }
        
        .room-card:hover { 
            transform: translateY(-10px); 
            box-shadow: var(--shadow-lg); 
        }
        
        .room-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--gold), var(--gold-dark));
            z-index: 2;
        }
        
        /* Enhanced Carousel */
        .room-carousel {
            height: 280px;
            position: relative;
            overflow: hidden;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }
        
        .carousel-inner {
            display: flex;
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
        }
        
        .carousel-item {
            min-width: 100%;
            height: 100%;
            position: relative;
        }
        
        .carousel-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .room-card:hover .carousel-item img {
            transform: scale(1.08);
        }
        
        .carousel-controls {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 12px;
            z-index: 3;
        }
        
        .carousel-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            cursor: pointer;
            transition: var(--transition);
            border: 2px solid transparent;
        }
        
        .carousel-dot.active {
            background: var(--gold);
            transform: scale(1.3);
            border-color: rgba(255, 255, 255, 0.8);
        }
        
        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(15, 23, 42, 0.8);
            color: var(--white);
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            opacity: 0;
            z-index: 3;
            backdrop-filter: blur(10px);
        }
        
        .room-carousel:hover .carousel-nav {
            opacity: 1;
        }
        
        .carousel-nav:hover {
            background: rgba(15, 23, 42, 0.95);
            transform: translateY(-50%) scale(1.1);
        }
        
        .carousel-prev {
            left: 20px;
        }
        
        .carousel-next {
            right: 20px;
        }
        
        /* Room Content */
        .room-content { 
            padding: 2.5rem; 
        }
        
        .room-title { 
            font-size: 1.5rem; 
            font-weight: 700; 
            margin-bottom: 1rem; 
            color: var(--navy); 
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .room-description { 
            color: var(--gray); 
            margin-bottom: 1.5rem;
            line-height: 1.7;
            font-size: 1.05rem;
        }
        
        .room-price { 
            font-size: 2rem; 
            font-weight: 800; 
            color: var(--gold); 
            margin-bottom: 2rem; 
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .room-price span { 
            font-size: 1rem; 
            font-weight: 500; 
            color: var(--gray); 
        }
        
        /* Room Features */
        .room-features {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin: 1.5rem 0;
        }
        
        .feature {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
            color: var(--gray);
            padding: 0.5rem;
            border-radius: 8px;
            transition: var(--transition);
        }
        
        .feature:hover {
            background: rgba(212, 175, 55, 0.05);
            transform: translateX(5px);
        }
        
        .feature i {
            color: var(--gold);
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }
        
        /* Reservations Grid */
        .reservations-grid { 
            display: grid; 
            gap: 2rem; 
            margin: 3rem 0; 
        }
        
        .reservation-card { 
            background: linear-gradient(135deg, var(--white) 0%, #f8fafc 100%);
            border-radius: var(--border-radius); 
            padding: 2.5rem; 
            box-shadow: var(--shadow-md); 
            transition: var(--transition);
            border-left: 6px solid var(--gold);
            position: relative;
            overflow: hidden;
        }
        
        .reservation-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, var(--gold-light) 0%, transparent 70%);
            opacity: 0.05;
            border-radius: 50%;
        }
        
        .reservation-card:hover { 
            transform: translateY(-5px); 
            box-shadow: var(--shadow-lg);
        }
        
        .reservation-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: start; 
            margin-bottom: 2rem; 
        }
        
        .reservation-title { 
            font-size: 1.5rem; 
            font-weight: 700; 
            color: var(--navy); 
        }
        
        .reservation-details { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 2rem; 
            margin-bottom: 2rem; 
        }
        
        .detail-item { }
        
        .detail-label { 
            font-size: 0.9rem; 
            color: var(--gray); 
            margin-bottom: 0.5rem; 
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .detail-value { 
            font-weight: 700; 
            color: var(--navy); 
            font-size: 1.2rem;
        }
        
        /* Status Badges */
        .status { 
            display: inline-flex; 
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem; 
            border-radius: 12px; 
            font-size: 0.9rem; 
            font-weight: 700; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: var(--shadow-sm);
        }
        
        .status.confirmed { 
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #166534; 
            border: 1px solid #22c55e;
        }
        
        .status.pending { 
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e; 
            border: 1px solid #f59e0b;
        }
        
        .status.cancelled { 
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #dc2626; 
            border: 1px solid #ef4444;
        }
        
        .status.completed { 
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1d4ed8; 
            border: 1px solid #3b82f6;
        }
        
        .status.checked_in { 
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            color: #6366f1; 
            border: 1px solid #6366f1;
        }
        
        /* Footer */
        .footer {
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
            color: var(--white);
            padding: 5rem 2rem 2rem;
            margin-top: 8rem;
            position: relative;
        }
        
        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--gold), var(--gold-dark));
        }
        
        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 4rem;
        }
        
        .footer-section h3 {
            color: var(--gold);
            margin-bottom: 2rem;
            font-size: 1.4rem;
            font-weight: 700;
        }
        
        .footer-section p, .footer-section a {
            color: #cbd5e1;
            margin-bottom: 1rem;
            display: block;
            text-decoration: none;
            transition: var(--transition);
            line-height: 1.8;
        }
        
        .footer-section a:hover {
            color: var(--gold);
            transform: translateX(5px);
        }
        
        .footer-bottom {
            max-width: 1400px;
            margin: 0 auto;
            text-align: center;
            padding-top: 3rem;
            margin-top: 4rem;
            border-top: 1px solid var(--navy-lighter);
            color: #94a3b8;
            font-size: 0.9rem;
        }
        
        /* Loading Animation */
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
        
        .room-card, .reservation-card, .welcome {
            animation: fadeInUp 0.8s cubic-bezier(0.4, 0, 0.2, 1) both;
        }
        
        .room-card:nth-child(2) { animation-delay: 0.1s; }
        .room-card:nth-child(3) { animation-delay: 0.2s; }
        .room-card:nth-child(4) { animation-delay: 0.3s; }
        .room-card:nth-child(5) { animation-delay: 0.4s; }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .hero h1 { font-size: 3.5rem; }
            .section-title { font-size: 2.5rem; }
        }
        
        @media (max-width: 768px) {
            .header { padding: 1rem; }
            .header.scrolled { padding: 0.75rem 1rem; }
            
            .header-content { 
                flex-direction: column; 
                gap: 1.5rem; 
            }
            
            .logo { 
                padding: 0.5rem 1rem;
                gap: 0.75rem;
            }
            
            .logo-text { font-size: 1.5rem; }
            .logo-image { height: 35px; }
            
            .nav { 
                flex-direction: column; 
                gap: 0.75rem; 
                width: 100%;
            }
            
            .nav a { 
                justify-content: center;
                width: 100%;
            }
            
            .hero { 
                padding: 8rem 1rem 4rem; 
                background-attachment: scroll;
            }
            
            .hero h1 { font-size: 2.5rem; }
            .hero p { font-size: 1.1rem; }
            
            .container { padding: 0 1rem; }
            
            .section-title { 
                font-size: 2rem; 
                margin: 4rem 0 2.5rem;
            }
            
            .rooms-grid { 
                grid-template-columns: 1fr; 
                gap: 2rem;
            }
            
            .room-content { padding: 2rem; }
            
            .welcome { 
                padding: 2.5rem 1.5rem; 
                margin: 2.5rem 0;
            }
            
            .welcome h1 { font-size: 2rem; }
            
            .reservation-details { grid-template-columns: 1fr; }
            .reservation-header { flex-direction: column; gap: 1rem; }
            
            .footer-content { gap: 2.5rem; }
        }
        
        @media (max-width: 480px) {
            .room-features {
                grid-template-columns: 1fr;
            }
            
            .btn {
                padding: 0.875rem 2rem;
                font-size: 1rem;
            }
            
            .hero h1 { font-size: 2rem; }
            .section-title { font-size: 1.75rem; }
        }
    </style>
</head>
<body>
    <!-- Skip to main content -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <div class="header" id="header">
        <div class="header-content">
            <a href="index.php" class="logo">
                <img src="cali.png" alt="Caliview Hotel" class="logo-image">
                <span class="logo-text">Caliview Hotel</span>
            </a>
            <nav class="nav" aria-label="Main navigation">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="index.php"><i class="fas fa-home"></i> Home</a>
                    <a href="user/profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </div>

    <main id="main-content">
        <?php if (!isset($_SESSION['user_id'])): ?>
            <section class="hero">
                <div class="hero-content">
                    <h1>Experience Unparalleled Luxury</h1>
                    <p>Discover our premium accommodations in the heart of the city, where elegance meets comfort for an unforgettable stay.</p>
                    <a href="register.php" class="btn btn-primary">
                        <i class="fas fa-gem"></i> Book Your Stay
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </section>
        <?php endif; ?>

        <div class="container">
            <?php if (isset($_SESSION['user_id'])): ?>
                <section class="welcome">
                    <h1><i class="fas fa-gem"></i> Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h1>
                    <p>We're delighted to have you with us again. Manage your reservations and explore our exclusive room offerings.</p>
                </section>

                <?php if (isset($_GET['booked'])): ?>
                    <div class="alert">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong>Booking Confirmed!</strong> Your reservation has been successfully created. 
                            Details are shown below.
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($reservations): ?>
                    <section>
                        <h2 class="section-title">My Recent Reservations</h2>
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
                                    
                                    <?php if ($reservation['status'] == 'confirmed'): ?>
                                        <form method="POST" action="user/cancel_reservation.php">
                                            <input type="hidden" name="reservation_id" value="<?= $reservation['reservation_id'] ?>">
                                            <button type="submit" onclick="return confirm('Are you sure you want to cancel this reservation?')" class="btn btn-danger">
                                                <i class="fas fa-times"></i> Cancel Reservation
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            <?php endif; ?>

            <section>
                <h2 class="section-title"><?= isset($_SESSION['user_id']) ? 'Book Another Room' : 'Our Premium Accommodations' ?></h2>
                
                <div class="rooms-grid">
                    <?php foreach ($room_types as $type): ?>
                        <div class="room-card">
                            <div class="room-carousel" id="carousel-<?= $type['type_id'] ?>">
                                <div class="carousel-inner">
                                    <?php
                                    $roomImages = [
                                        'standard_room' => [
                                            'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
                                            'https://images.unsplash.com/photo-1586105251261-72a756497a11?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
                                            'https://images.unsplash.com/photo-1566665797739-1674de7a421a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80'
                                        ],
                                        'deluxe_room' => [
                                            'https://images.unsplash.com/photo-1590490360182-c33d57733427?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
                                            'https://images.unsplash.com/photo-1611892440504-42a792e24d32?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
                                            'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80'
                                        ],
                                        'suite' => [
                                            'https://images.unsplash.com/photo-1564078516393-cf04bd966897?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
                                            'https://images.unsplash.com/photo-1618773928121-c32242e63f39?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
                                            'https://images.unsplash.com/photo-1591088398332-8a7791972843?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80'
                                        ],
                                        'executive_suite' => [
                                            'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
                                            'https://images.unsplash.com/photo-1631049552057-403cdb8f0658?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
                                            'https://images.unsplash.com/photo-1590490360182-c33d57733427?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80'
                                        ],
                                        'presidential_suite' => [
                                            'https://images.unsplash.com/photo-1590490360182-c33d57733427?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
                                            'https://images.unsplash.com/photo-1611892440504-42a792e24d32?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
                                            'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80'
                                        ]
                                    ];
                                    
                                    $roomType = strtolower(str_replace(' ', '_', $type['type_name']));
                                    $images = $roomImages[$roomType] ?? $roomImages['standard_room'];
                                    
                                    foreach ($images as $index => $imageUrl): ?>
                                        <div class="carousel-item">
                                            <img src="<?= $imageUrl ?>" 
                                                 alt="<?= htmlspecialchars($type['type_name']) ?> - Image <?= $index + 1 ?>"
                                                 loading="lazy">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <button class="carousel-nav carousel-prev" onclick="prevSlide(<?= $type['type_id'] ?>)">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button class="carousel-nav carousel-next" onclick="nextSlide(<?= $type['type_id'] ?>)">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                                
                                <div class="carousel-controls">
                                    <?php for ($i = 0; $i < count($images); $i++): ?>
                                        <div class="carousel-dot <?= $i === 0 ? 'active' : '' ?>" 
                                             onclick="goToSlide(<?= $type['type_id'] ?>, <?= $i ?>)"></div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <div class="room-content">
                                <h3 class="room-title">
                                    <?= htmlspecialchars($type['type_name']) ?>
                                    <span style="font-size: 1rem; color: var(--gray); font-weight: 500;">
                                        <i class="fas fa-user-friends"></i> Up to <?= $type['max_occupancy'] ?? 2 ?> guests
                                    </span>
                                </h3>
                                <p class="room-description"><?= htmlspecialchars($type['description']) ?></p>
                                
                                <div class="room-features">
                                    <div class="feature">
                                        <i class="fas fa-wifi"></i> Free WiFi
                                    </div>
                                    <div class="feature">
                                        <i class="fas fa-tv"></i> Smart TV
                                    </div>
                                    <div class="feature">
                                        <i class="fas fa-coffee"></i> Coffee Maker
                                    </div>
                                    <div class="feature">
                                        <i class="fas fa-snowflake"></i> Air Conditioning
                                    </div>
                                    <div class="feature">
                                        <i class="fas fa-shower"></i> Luxury Bathroom
                                    </div>
                                    <div class="feature">
                                        <i class="fas fa-utensils"></i> Room Service
                                    </div>
                                </div>
                                
                                <div class="room-price">
                                    <i class="fas fa-tag"></i> $<?= number_format($type['base_price'], 0) ?> 
                                    <span>per night</span>
                                </div>
                                
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <a href="book.php?type_id=<?= $type['type_id'] ?>" class="btn btn-primary">
                                        <i class="fas fa-calendar-check"></i> Book Now
                                    </a>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-outline">
                                        <i class="fas fa-sign-in-alt"></i> Login to Book
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>Caliview Hotel</h3>
                <p>Experience unparalleled luxury and comfort in the heart of the city. Our premium accommodations and exceptional service ensure an unforgettable stay.</p>
            </div>
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p><i class="fas fa-map-marker-alt"></i> 123 Luxury Avenue, City Center</p>
                <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                <p><i class="fas fa-envelope"></i> info@caliviewhotel.com</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <a href="index.php">Home</a>
                <a href="about.php">About Us</a>
                <a href="rooms.php">Rooms & Suites</a>
                <a href="contact.php">Contact</a>
            </div>
            <div class="footer-section">
                <h3>Connect With Us</h3>
                <a href="#"><i class="fab fa-facebook"></i> Facebook</a>
                <a href="#"><i class="fab fa-instagram"></i> Instagram</a>
                <a href="#"><i class="fab fa-twitter"></i> Twitter</a>
                <a href="#"><i class="fab fa-linkedin"></i> LinkedIn</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> Caliview Hotel. All rights reserved. | Luxury Redefined</p>
        </div>
    </footer>

    <script>
        // Header scroll effect
        let lastScrollY = window.scrollY;
        const header = document.getElementById('header');

        window.addEventListener('scroll', () => {
            if (window.scrollY > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
            
            lastScrollY = window.scrollY;
        });

        // Carousel functionality
        const carousels = {};
        
        function initCarousel(typeId, imageCount) {
            carousels[typeId] = {
                currentIndex: 0,
                totalSlides: imageCount
            };
        }
        
        function nextSlide(typeId) {
            const carousel = carousels[typeId];
            carousel.currentIndex = (carousel.currentIndex + 1) % carousel.totalSlides;
            updateCarousel(typeId);
        }
        
        function prevSlide(typeId) {
            const carousel = carousels[typeId];
            carousel.currentIndex = (carousel.currentIndex - 1 + carousel.totalSlides) % carousel.totalSlides;
            updateCarousel(typeId);
        }
        
        function goToSlide(typeId, index) {
            const carousel = carousels[typeId];
            carousel.currentIndex = index;
            updateCarousel(typeId);
        }
        
        function updateCarousel(typeId) {
            const carousel = carousels[typeId];
            const carouselElement = document.getElementById(`carousel-${typeId}`);
            const inner = carouselElement.querySelector('.carousel-inner');
            const dots = carouselElement.querySelectorAll('.carousel-dot');
            
            inner.style.transform = `translateX(-${carousel.currentIndex * 100}%)`;
            
            dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === carousel.currentIndex);
            });
        }
        
        // Initialize carousels
        document.addEventListener('DOMContentLoaded', function() {
            <?php foreach ($room_types as $type): ?>
                <?php
                $roomType = strtolower(str_replace(' ', '_', $type['type_name']));
                $roomImages = [
                    'standard_room' => 3,
                    'deluxe_room' => 3,
                    'suite' => 3,
                    'executive_suite' => 3,
                    'presidential_suite' => 3
                ];
                $imageCount = $roomImages[$roomType] ?? 3;
                ?>
                initCarousel(<?= $type['type_id'] ?>, <?= $imageCount ?>);
            <?php endforeach; ?>
            
            // Auto-advance carousels
            setInterval(() => {
                for (const typeId in carousels) {
                    nextSlide(parseInt(typeId));
                }
            }, 6000);
        });

        // Add loading state for better UX
        document.addEventListener('DOMContentLoaded', function() {
            document.body.style.opacity = '0';
            document.body.style.transition = 'opacity 0.3s ease-in';
            
            setTimeout(() => {
                document.body.style.opacity = '1';
            }, 100);
        });
    </script>
</body>
</html>