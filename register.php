<?php
session_start();
require_once 'config/database.php';

if ($_POST) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$_POST['email']]);
    
    if ($stmt->fetch()) {
        $error = "Email already exists";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, phone) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$_POST['full_name'], $_POST['email'], password_hash($_POST['password'], PASSWORD_DEFAULT), $_POST['phone']])) {
            $_SESSION['user_id'] = $conn->lastInsertId();
            $_SESSION['user_name'] = $_POST['full_name'];
            header('Location: user/dashboard.php');
            exit;
        } else {
            $error = "Registration failed";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - Luxury Hotel</title>
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
        
        /* Floating background elements */
        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .floating-element {
            position: absolute;
            background: var(--gold);
            opacity: 0.1;
            border-radius: 50%;
            animation: float 20s infinite linear;
        }
        
        .floating-element:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .floating-element:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 70%;
            left: 80%;
            animation-delay: -5s;
        }
        
        .floating-element:nth-child(3) {
            width: 60px;
            height: 60px;
            top: 80%;
            left: 20%;
            animation-delay: -10s;
        }
        
        .floating-element:nth-child(4) {
            width: 100px;
            height: 100px;
            top: 20%;
            left: 85%;
            animation-delay: -15s;
        }
        
        .register-container { 
            background: var(--white); 
            border-radius: 20px; 
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); 
            padding: 3rem 2.5rem; 
            width: 100%; 
            max-width: 480px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1);
            transform: translateY(50px);
            opacity: 0;
            transition: all 0.8s ease-out;
        }
        
        .register-container.visible {
            transform: translateY(0);
            opacity: 1;
        }
        
        .register-container::before {
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
        
        .register-container.visible::before {
            transform: scaleX(1);
        }
        
        .logo { 
            text-align: center; 
            margin-bottom: 2.5rem; 
            transform: translateY(30px);
            opacity: 0;
            transition: all 0.6s ease-out 0.4s;
        }
        
        .register-container.visible .logo {
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
            transform: scale(0);
            transition: transform 0.6s ease-out 0.8s;
        }
        
        .register-container.visible .logo i {
            transform: scale(1);
        }
        
        .logo p { 
            color: var(--gray); 
            font-size: 1.1rem;
            transform: translateY(10px);
            opacity: 0;
            transition: all 0.6s ease-out 0.6s;
        }
        
        .register-container.visible .logo p {
            transform: translateY(0);
            opacity: 1;
        }
        
        .form-group { 
            margin-bottom: 1.75rem; 
            transform: translateX(-30px);
            opacity: 0;
            transition: all 0.6s ease-out;
        }
        
        .register-container.visible .form-group {
            transform: translateX(0);
            opacity: 1;
        }
        
        .form-group:nth-child(1) { transition-delay: 0.8s; }
        .form-group:nth-child(2) { transition-delay: 1.0s; }
        .form-group:nth-child(3) { transition-delay: 1.2s; }
        .form-group:nth-child(4) { transition-delay: 1.4s; }
        
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
            transform: translateY(10px);
            opacity: 0;
            transition: all 0.5s ease-out;
        }
        
        .register-container.visible .form-input {
            transform: translateY(0);
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
            transform: translateY(30px);
            opacity: 0;
            transition: all 0.6s ease-out 1.6s;
        }
        
        .register-container.visible .btn {
            transform: translateY(0);
            opacity: 1;
        }
        
        .btn:hover { 
            background: linear-gradient(135deg, var(--gold-dark) 0%, #9c7c18 100%); 
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(212, 175, 55, 0.4);
        }
        
        .btn:active {
            transform: translateY(-1px);
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
            transform: scale(0.8);
            opacity: 0;
            transition: all 0.5s ease-out;
        }
        
        .register-container.visible .alert {
            transform: scale(1);
            opacity: 1;
        }
        
        .alert i {
            font-size: 1.25rem;
        }
        
        .links { 
            text-align: center; 
            margin-top: 2.5rem; 
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.6s ease-out 1.8s;
        }
        
        .register-container.visible .links {
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
        
        .divider {
            display: flex;
            align-items: center;
            margin: 2rem 0;
            color: var(--gray);
            transform: scaleX(0);
            opacity: 0;
            transition: all 0.6s ease-out 1.4s;
        }
        
        .register-container.visible .divider {
            transform: scaleX(1);
            opacity: 1;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }
        
        .divider span {
            padding: 0 1rem;
            font-size: 0.9rem;
        }
        
        .password-requirements {
            background: var(--gray-light);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1rem;
            border-left: 4px solid var(--gold);
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.6s ease-out 1.2s;
        }
        
        .register-container.visible .password-requirements {
            transform: translateY(0);
            opacity: 1;
        }
        
        .password-requirements h3 {
            color: var(--navy);
            margin-bottom: 1rem;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .password-requirements ul {
            color: var(--gray);
            font-size: 0.9rem;
            margin-left: 1.5rem;
        }
        
        .password-requirements li {
            margin-bottom: 0.5rem;
            transition: color 0.3s ease;
        }
        
        @media (max-width: 480px) {
            .register-container {
                padding: 2.5rem 1.5rem;
            }
            
            .logo h1 {
                font-size: 1.75rem;
            }
        }
        
        /* Animations */
        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            33% {
                transform: translateY(-20px) rotate(120deg);
            }
            66% {
                transform: translateY(10px) rotate(240deg);
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Progress indicator */
        .progress-indicator {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 4px;
            background: linear-gradient(90deg, var(--gold), var(--gold-dark));
            z-index: 1000;
            transition: width 0.3s ease;
        }
        
        /* Scroll hint */
        .scroll-hint {
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
            transform: translateY(20px);
            transition: all 0.5s ease;
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.4);
            z-index: 100;
        }
        
        .scroll-hint.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        .scroll-hint:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(212, 175, 55, 0.6);
        }
        
        /* Form validation styles */
        .form-input:invalid:not(:focus):not(:placeholder-shown) {
            border-color: #f87171;
            animation: shake 0.5s ease-in-out;
        }
        
        .form-input:valid:not(:focus):not(:placeholder-shown) {
            border-color: #4ade80;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        /* Success animation */
        @keyframes successPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .success-animation {
            animation: successPulse 0.6s ease-in-out;
        }
    </style>
</head>
<body>
    <!-- Progress indicator -->
    <div class="progress-indicator"></div>
    
    <!-- Floating background elements -->
    <div class="floating-elements">
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
    </div>
    
    <!-- Scroll to top hint -->
    <div class="scroll-hint" id="scrollTop">
        <i class="fas fa-chevron-up"></i>
    </div>
    
    <div class="register-container" id="registerContainer">
        <div class="logo">
            <h1><i class="fas fa-crown"></i> Luxury Hotel</h1>
            <p>Create your account</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert">
                <i class="fas fa-exclamation-circle"></i>
                <div><?= $error ?></div>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="registrationForm">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-input" placeholder="Enter your full name" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-input" placeholder="Enter your email" required>
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="tel" name="phone" class="form-input" placeholder="Enter your phone number">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" placeholder="Create a password" required minlength="6">
            </div>
            
            <div class="password-requirements">
                <h3><i class="fas fa-shield-alt"></i> Password Requirements</h3>
                <ul>
                    <li>At least 6 characters long</li>
                    <li>Include letters and numbers</li>
                    <li>For security, avoid common words</li>
                </ul>
            </div>
            
            <button type="submit" class="btn" id="submitBtn">
                <i class="fas fa-user-plus"></i>
                Create Account
            </button>
        </form>
        
        <div class="divider">
            <span>Already a member?</span>
        </div>
        
        <div class="links">
            <p>Already have an account? <a href="login.php">Sign in <i class="fas fa-arrow-right"></i></a></p>
            <p><a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a></p>
        </div>
    </div>

    <script>
        // Scroll trigger animations
        document.addEventListener('DOMContentLoaded', function() {
            const registerContainer = document.getElementById('registerContainer');
            const progressIndicator = document.querySelector('.progress-indicator');
            const scrollTopBtn = document.getElementById('scrollTop');
            
            // Make container visible on load
            setTimeout(() => {
                registerContainer.classList.add('visible');
            }, 100);
            
            // Scroll progress indicator
            window.addEventListener('scroll', function() {
                const scrollTop = window.pageYOffset;
                const docHeight = document.documentElement.scrollHeight - window.innerHeight;
                const scrollPercent = (scrollTop / docHeight) * 100;
                progressIndicator.style.width = scrollPercent + '%';
                
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
            
            // Password strength indicator
            const passwordInput = document.querySelector('input[name="password"]');
            const passwordRequirements = document.querySelector('.password-requirements');
            
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                
                // Update requirements styling based on password strength
                const requirements = passwordRequirements.querySelectorAll('li');
                
                // At least 6 characters
                requirements[0].style.color = password.length >= 6 ? '#16a34a' : '#64748b';
                
                // Include letters and numbers
                const hasLetters = /[a-zA-Z]/.test(password);
                const hasNumbers = /[0-9]/.test(password);
                requirements[1].style.color = (hasLetters && hasNumbers) ? '#16a34a' : '#64748b';
                
                // Avoid common words (basic check)
                const commonWords = ['password', '123456', 'qwerty'];
                const isCommon = commonWords.some(word => password.toLowerCase().includes(word));
                requirements[2].style.color = !isCommon ? '#16a34a' : '#64748b';
            });
            
            // Form submission animation
            const registrationForm = document.getElementById('registrationForm');
            const submitBtn = document.getElementById('submitBtn');
            
            registrationForm.addEventListener('submit', function(e) {
                // Only animate if form is valid
                if (this.checkValidity()) {
                    e.preventDefault();
                    
                    // Add success animation
                    submitBtn.classList.add('success-animation');
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
                    
                    // Submit form after animation
                    setTimeout(() => {
                        this.submit();
                    }, 1500);
                }
            });
            
            // Input focus animations
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
            const animatedElements = document.querySelectorAll('.form-group, .password-requirements, .btn, .links');
            animatedElements.forEach(el => observer.observe(el));
        });
    </script>
</body>
</html>