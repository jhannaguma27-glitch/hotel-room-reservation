<?php
session_start();
require_once 'config/database.php';

if ($_POST) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$_POST['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($_POST['password'], $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['full_name'];
        header('Location: user/dashboard.php');
        exit;
    } else {
        $error = "Invalid email or password";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Luxury Hotel</title>
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
            overflow: hidden;
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
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--gold), var(--gold-dark));
        }
        
        .logo { 
            text-align: center; 
            margin-bottom: 2.5rem; 
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
        }
        
        .logo p { 
            color: var(--gray); 
            font-size: 1.1rem;
        }
        
        .form-group { 
            margin-bottom: 1.75rem; 
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
        }
        
        .alert i {
            font-size: 1.25rem;
        }
        
        .links { 
            text-align: center; 
            margin-top: 2.5rem; 
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
        
        .demo-accounts {
            background: var(--gray-light);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
            border-left: 4px solid var(--gold);
        }
        
        .demo-accounts h3 {
            color: var(--navy);
            margin-bottom: 1rem;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .demo-accounts p {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 2.5rem 1.5rem;
            }
            
            .logo h1 {
                font-size: 1.75rem;
            }
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-container {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1><i class="fas fa-crown"></i> Luxury Hotel</h1>
            <p>Sign in to your account</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert">
                <i class="fas fa-exclamation-circle"></i>
                <div><?= $error ?></div>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-input" placeholder="Enter your email" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn">
                <i class="fas fa-sign-in-alt"></i>
                Sign In
            </button>
        </form>
        
        <div class="divider">
            <span>New to Luxury Hotel?</span>
        </div>
        
        <div class="links">
            <p>Don't have an account? <a href="register.php">Create one <i class="fas fa-arrow-right"></i></a></p>
            <p><a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a></p>
        </div>
        
    </div>
</body>
</html>