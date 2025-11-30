<?php
session_start();
require_once '../config/database.php';

if ($_POST) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$_POST['username']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin && password_verify($_POST['password'], $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];
        
        // Update last login
        $stmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE admin_id = ?");
        $stmt->execute([$admin['admin_id']]);
        
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login - Luxury Hotel</title>
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
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            padding: 1rem;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1200&q=80') no-repeat center center;
            background-size: cover;
            opacity: 0.1;
            z-index: -1;
        }
        
        /* Floating security elements */
        .floating-security {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .security-element {
            position: absolute;
            color: var(--gold);
            opacity: 0.1;
            font-size: 2rem;
            animation: floatSecurity 20s infinite linear;
        }
        
        .security-element:nth-child(1) {
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .security-element:nth-child(2) {
            top: 70%;
            left: 80%;
            animation-delay: -5s;
        }
        
        .security-element:nth-child(3) {
            top: 80%;
            left: 20%;
            animation-delay: -10s;
        }
        
        .security-element:nth-child(4) {
            top: 20%;
            left: 85%;
            animation-delay: -15s;
        }
        
        /* Scroll progress indicator */
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
        
        .login-container { 
            background: var(--white); 
            border-radius: 20px; 
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); 
            padding: 3rem 2.5rem; 
            width: 100%; 
            max-width: 440px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1);
            transform: translateY(50px) rotateX(5deg);
            opacity: 0;
            transition: all 0.8s ease-out;
        }
        
        .login-container.visible {
            transform: translateY(0) rotateX(0);
            opacity: 1;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--gold), var(--gold-dark));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 1s ease-out 0.3s;
        }
        
        .login-container.visible::before {
            transform: scaleX(1);
        }
        
        .logo { 
            text-align: center; 
            margin-bottom: 2.5rem; 
            transform: translateY(30px);
            opacity: 0;
            transition: all 0.6s ease-out 0.4s;
        }
        
        .login-container.visible .logo {
            transform: translateY(0);
            opacity: 1;
        }
        
        .logo h1 { 
            font-size: 2rem; 
            font-weight: 700; 
            color: var(--navy); 
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .logo i {
            color: var(--gold);
            transform: scale(0) rotate(-180deg);
            transition: transform 0.6s ease-out 0.8s;
        }
        
        .login-container.visible .logo i {
            transform: scale(1) rotate(0);
        }
        
        .logo p { 
            color: var(--gray); 
            font-size: 1.1rem;
            transform: translateY(10px);
            opacity: 0;
            transition: all 0.6s ease-out 0.6s;
        }
        
        .login-container.visible .logo p {
            transform: translateY(0);
            opacity: 1;
        }
        
        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: var(--navy);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 0.5rem;
            transform: scale(0);
            transition: transform 0.6s ease-out 1s;
        }
        
        .login-container.visible .admin-badge {
            transform: scale(1);
        }
        
        .form-group { 
            margin-bottom: 1.75rem; 
            transform: translateX(-50px);
            opacity: 0;
            transition: all 0.6s ease-out;
        }
        
        .login-container.visible .form-group {
            transform: translateX(0);
            opacity: 1;
        }
        
        .form-group:nth-child(1) { transition-delay: 1.2s; }
        .form-group:nth-child(2) { transition-delay: 1.4s; }
        
        .form-label { 
            display: block; 
            margin-bottom: 0.75rem; 
            font-weight: 600; 
            color: var(--navy);
            font-size: 0.95rem;
            transform: translateX(-20px);
            opacity: 0;
            transition: all 0.4s ease-out;
        }
        
        .login-container.visible .form-label {
            transform: translateX(0);
            opacity: 1;
        }
        
        .form-label:nth-child(1) { transition-delay: 1.3s; }
        .form-label:nth-child(2) { transition-delay: 1.5s; }
        
        .form-input { 
            width: 100%; 
            padding: 1rem 1.25rem; 
            border: 2px solid #e2e8f0; 
            border-radius: 10px; 
            font-size: 1rem; 
            transition: all 0.3s;
            background: var(--white);
            color: var(--navy);
            transform: translateY(20px) scale(0.95);
            opacity: 0;
            transition: all 0.5s ease-out;
        }
        
        .login-container.visible .form-input {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
        
        .form-input:focus { 
            outline: none; 
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
            transform: translateY(-2px) scale(1.02);
        }
        
        .form-input::placeholder {
            color: #94a3b8;
        }
        
        .btn { 
            width: 100%; 
            padding: 1.1rem; 
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%); 
            color: var(--navy); 
            border: none; 
            border-radius: 10px; 
            font-weight: 600; 
            font-size: 1.05rem; 
            cursor: pointer; 
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transform: translateY(40px) scale(0.9);
            opacity: 0;
            transition: all 0.6s ease-out 1.6s;
        }
        
        .login-container.visible .btn {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
        
        .btn:hover { 
            background: linear-gradient(135deg, var(--gold-dark) 0%, #9c7c18 100%); 
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 20px rgba(212, 175, 55, 0.4);
        }
        
        .btn:active {
            transform: translateY(-1px) scale(1);
        }
        
        .alert { 
            padding: 1.25rem 1.5rem; 
            border-radius: 12px; 
            margin-bottom: 2rem; 
            background: #fee2e2; 
            color: #dc2626; 
            border: 1px solid #fecaca;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transform: scale(0.8) rotate(-5deg);
            opacity: 0;
            transition: all 0.5s ease-out;
        }
        
        .login-container.visible .alert {
            transform: scale(1) rotate(0);
            opacity: 1;
        }
        
        .alert i {
            font-size: 1.25rem;
        }
        
        .security-notice {
            background: var(--gray-light);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
            border-left: 4px solid var(--gold);
            transform: translateY(30px) scale(0.95);
            opacity: 0;
            transition: all 0.6s ease-out 1.8s;
        }
        
        .login-container.visible .security-notice {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
        
        .security-notice h3 {
            color: var(--navy);
            margin-bottom: 1rem;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .security-notice ul {
            color: var(--gray);
            font-size: 0.9rem;
            margin-left: 1.5rem;
        }
        
        .security-notice li {
            margin-bottom: 0.5rem;
            transform: translateX(-20px);
            opacity: 0;
            transition: all 0.4s ease-out;
        }
        
        .login-container.visible .security-notice li {
            transform: translateX(0);
            opacity: 1;
        }
        
        .security-notice li:nth-child(1) { transition-delay: 2.0s; }
        .security-notice li:nth-child(2) { transition-delay: 2.2s; }
        .security-notice li:nth-child(3) { transition-delay: 2.4s; }
        
        .links { 
            text-align: center; 
            margin-top: 2rem; 
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.6s ease-out 2.2s;
        }
        
        .login-container.visible .links {
            transform: translateY(0);
            opacity: 1;
        }
        
        .links a { 
            color: var(--gold-dark); 
            text-decoration: none; 
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .links a:hover { 
            color: var(--navy);
            transform: translateX(5px);
        }
        
        .links p { 
            margin: 1rem 0; 
            color: var(--gray);
        }
        
        /* Admin access level indicator */
        .access-level {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin: 1.5rem 0;
            transform: scale(0);
            opacity: 0;
            transition: all 0.6s ease-out 1.4s;
        }
        
        .login-container.visible .access-level {
            transform: scale(1);
            opacity: 1;
        }
        
        .level-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--gray);
            animation: pulse 2s infinite;
        }
        
        .level-indicator.active {
            background: var(--gold);
        }
        
        .level-indicator:nth-child(1) { animation-delay: 0s; }
        .level-indicator:nth-child(2) { animation-delay: 0.5s; }
        .level-indicator:nth-child(3) { animation-delay: 1s; }
        
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
        
        @keyframes floatSecurity {
            0%, 100% {
                transform: translateY(0) rotate(0deg) scale(1);
            }
            25% {
                transform: translateY(-20px) rotate(90deg) scale(1.2);
            }
            50% {
                transform: translateY(0) rotate(180deg) scale(1);
            }
            75% {
                transform: translateY(20px) rotate(270deg) scale(0.8);
            }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.2); }
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 2.5rem 1.5rem;
            }
            
            .logo h1 {
                font-size: 1.75rem;
            }
            
            .scroll-top {
                bottom: 1rem;
                right: 1rem;
            }
        }
        
        /* Form validation styles */
        .form-input:invalid:not(:focus):not(:placeholder-shown) {
            border-color: #f87171;
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        /* Loading animation */
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
        
        /* Scroll-triggered parallax effect */
        .parallax-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 200%;
            background: linear-gradient(45deg, 
                rgba(212, 175, 55, 0.03) 0%, 
                transparent 50%,
                rgba(15, 23, 42, 0.05) 100%);
            z-index: -2;
            transform: translateY(0);
            transition: transform 0.1s linear;
        }
    </style>
</head>
<body>
    <!-- Scroll progress indicator -->
    <div class="scroll-progress"></div>
    
    <!-- Parallax background -->
    <div class="parallax-bg" id="parallaxBg"></div>
    
    <!-- Floating security elements -->
    <div class="floating-security">
        <div class="security-element"><i class="fas fa-shield-alt"></i></div>
        <div class="security-element"><i class="fas fa-lock"></i></div>
        <div class="security-element"><i class="fas fa-key"></i></div>
        <div class="security-element"><i class="fas fa-user-shield"></i></div>
    </div>
    
    <!-- Scroll to top button -->
    <div class="scroll-top" id="scrollTop">
        <i class="fas fa-chevron-up"></i>
    </div>
    
    <div class="login-container" id="loginContainer">
        <div class="logo">
            <h1><i class="fas fa-crown"></i> Luxury Hotel</h1>
            <p>Administrator Access</p>
            <div class="admin-badge">
                <i class="fas fa-shield-alt"></i>
                Secure Admin Portal
            </div>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <div><?= $error ?></div>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="adminLoginForm">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-input" placeholder="Enter admin username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" placeholder="Enter admin password" required autocomplete="current-password">
            </div>
            
            <!-- Access level indicator -->
            <div class="access-level">
                <div class="level-indicator active"></div>
                <div class="level-indicator active"></div>
                <div class="level-indicator active"></div>
            </div>
            
            <button type="submit" class="btn" id="submitBtn">
                <i class="fas fa-lock"></i>
                Secure Login
            </button>
        </form>
        
        <div class="security-notice">
            <h3><i class="fas fa-info-circle"></i> Security Notice</h3>
            <ul>
                <li>This area is restricted to authorized personnel only</li>
                <li>All activities are logged and monitored</li>
                <li>Ensure you log out after completing your tasks</li>
            </ul>
        </div>
        
        <div class="links">
            <p><a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Main Site</a></p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginContainer = document.getElementById('loginContainer');
            const adminLoginForm = document.getElementById('adminLoginForm');
            const submitBtn = document.getElementById('submitBtn');
            const scrollProgress = document.querySelector('.scroll-progress');
            const scrollTopBtn = document.getElementById('scrollTop');
            const parallaxBg = document.getElementById('parallaxBg');
            
            // Make container visible on load
            setTimeout(() => {
                loginContainer.classList.add('visible');
            }, 100);
            
            // Scroll progress indicator
            window.addEventListener('scroll', function() {
                const scrollTop = window.pageYOffset;
                const docHeight = document.documentElement.scrollHeight - window.innerHeight;
                const scrollPercent = (scrollTop / docHeight) * 100;
                scrollProgress.style.width = scrollPercent + '%';
                
                // Parallax effect
                parallaxBg.style.transform = `translateY(${scrollTop * 0.5}px)`;
                
                // Show/hide scroll to top button
                if (scrollTop > 300) {
                    scrollTopBtn.classList.add('visible');
                } else {
                    scrollTopBtn.classList.remove('visible');
                }
            });
            
            // Scroll to top functionality
            scrollTopBtn.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
            
            // Form submission animation
            adminLoginForm.addEventListener('submit', function(e) {
                // Only animate if form is valid
                if (this.checkValidity()) {
                    e.preventDefault();
                    
                    // Add loading state
                    submitBtn.classList.add('loading');
                    submitBtn.innerHTML = '<i class="fas fa-spinner"></i> Authenticating...';
                    
                    // Add security delay with scroll effect
                    setTimeout(() => {
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    }, 500);
                    
                    // Submit form after animation
                    setTimeout(() => {
                        this.submit();
                    }, 2000);
                }
            });
            
            // Add scroll-triggered focus effects
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach((input, index) => {
                input.addEventListener('focus', function() {
                    this.style.background = 'var(--gray-light)';
                    // Add subtle scroll to ensure input is fully visible
                    if (index === 1) { // Password field
                        setTimeout(() => {
                            this.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }, 300);
                    }
                });
                
                input.addEventListener('blur', function() {
                    this.style.background = 'var(--white)';
                });
            });
            
            // Auto-focus on username field with scroll
            setTimeout(() => {
                const usernameInput = document.querySelector('input[name="username"]');
                usernameInput.focus();
                usernameInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 1200);
            
            // Add scroll-triggered security element animation
            window.addEventListener('scroll', function() {
                const securityElements = document.querySelectorAll('.security-element');
                const scrollY = window.pageYOffset;
                
                securityElements.forEach((element, index) => {
                    const speed = 0.5 + (index * 0.1);
                    element.style.transform = `translateY(${scrollY * speed}px) rotate(${scrollY * 0.1}deg)`;
                });
            });
        });
        
        // Intersection Observer for additional scroll effects
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
        
        // Observe elements when added to DOM
        document.addEventListener('DOMContentLoaded', () => {
            const animatedElements = document.querySelectorAll('.form-group, .security-notice, .btn, .links');
            animatedElements.forEach(el => observer.observe(el));
        });
    </script>
</body>
</html>