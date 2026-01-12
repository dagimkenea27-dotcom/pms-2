<?php
require_once "config/database.php";
require_once "models/User.php";
require_once "config/auth.php";
require_once "includes/functions.php";

Auth::startSession();

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

// If already logged in, redirect to dashboard
if (Auth::isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $error = "Your session has expired. Please log in again.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !Auth::validateCSRF($_POST['csrf_token'])) {
        $error = "Security check failed. Please refresh the page and try again.";
    } else {
        $user->username = $_POST['username'];
        $user->password = $_POST['password'];
        
        // Attempt login using the User model's login method
        if ($user->login()) {
            // Login successful
            Auth::login($user);
            
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    }
}
$csrf_token = Auth::generateCSRF();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Inventory System</title>
    <link rel="icon" type="image/jpeg" href="<?php echo BASE_URL; ?>assets/img/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #073b74 0%, #073b74 100%);
            --primary-dark: #5a67d8;
            --accent-color: #10b981;
            --light-bg: #f8fafc;
            --border-color: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.12);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.15);
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--light-bg);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .login-wrapper {
            width: 100%;
            min-height: 100vh;
            display: flex;
        }

        .login-left {
            flex: 1;
            background: var(--primary-gradient);
            position: relative;
            overflow: hidden;
            display: none;
        }

        @media (min-width: 992px) {
            .login-left {
                display: flex;
                flex-direction: column;
                justify-content: center;
                padding: 2rem;
                color: white;
            }
        }

        .login-left::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            background-position: center;
            opacity: 0.1;
        }

        .brand-showcase {
            position: relative;
            z-index: 2;
            max-width: 450px;
            margin: 0 auto;
        }

        .brand-icon-lg {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
            overflow: hidden;
        }
        
        .brand-icon-lg img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .login-right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .login-card {
            width: 100%;
            max-width: 380px;
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            border: 1px solid var(--border-color);
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-header {
            background: var(--primary-gradient);
            color: white;
            padding: 1.5rem 1.5rem 1rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .card-header::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: -20px;
            right: -20px;
            height: 30px;
            background: white;
            border-radius: 50%;
        }

        .brand-icon {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.3);
            overflow: hidden;
        }

        .brand-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .btn-login {
            background: var(--primary-gradient);
            border: none;
            border-radius: var(--radius-md);
            padding: 1rem;
            font-weight: 600;
            font-size: 1rem;
            color: white;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255,255,255,0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }

        .btn-login:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }

        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            100% {
                transform: scale(20, 20);
                opacity: 0;
            }
        }

        .alert {
            border-radius: var(--radius-md);
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            animation: slideIn 0.3s ease-out;
            font-size: 0.9rem;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert-danger {
            background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%);
            color: #9b2c2c;
            border-left: 4px solid #fc8181;
        }

        .alert-success {
            background: linear-gradient(135deg, #c6f6d5 0%, #9ae6b4 100%);
            color: #276749;
            border-left: 4px solid #68d391;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-color);
        }

        .divider span {
            padding: 0 1rem;
        }

        .form-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .form-footer a {
            color: var(--primary-dark);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .form-footer a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            z-index: 10;
        }

        .form-floating {
            position: relative;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .login-wrapper {
                padding: 1rem;
            }
            
            .login-right {
                padding: 1rem;
            }
            
            .login-card {
                max-width: 100%;
            }
            
            .card-body {
                padding: 2rem 1.5rem;
            }
        }

        /* Loading animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-wrapper" style="position: relative;">
        <!-- Language Switcher -->
        <div style="position: absolute; top: 1rem; right: 1rem; z-index: 100;">
             <a href="language_switch.php?lang=en" class="btn btn-sm btn-light <?php echo get_current_lang() == 'en' ? 'active' : ''; ?>">EN</a>
             <a href="language_switch.php?lang=ja" class="btn btn-sm btn-light <?php echo get_current_lang() == 'ja' ? 'active' : ''; ?>">JP</a>
        </div>
        <div class="login-left">
            <div class="brand-showcase">
                <h1 class="display-5 fw-bold mb-3"><?php echo __('welcome_back'); ?></h1>
                <p class="lead opacity-90 mb-4">
                    Streamline your inventory management with our powerful system.
                    Track, manage, and optimize your stock efficiently.
                </p>
                <div class="features-list mt-5">
                    <div class="feature-item d-flex align-items-center mb-3">
                        <i class="fas fa-check-circle me-3"></i>
                        <span>Real-time stock tracking</span>
                    </div>
                    <div class="feature-item d-flex align-items-center mb-3">
                        <i class="fas fa-check-circle me-3"></i>
                        <span>Advanced reporting</span>
                    </div>
                    <div class="feature-item d-flex align-items-center">
                        <i class="fas fa-check-circle me-3"></i>
                        <span>Multi-user collaboration</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="login-right">
            <div class="login-card">
                <div class="card-header">
                    <div class="brand-icon">
                         <img src="<?php echo BASE_URL; ?>assets/img/logo.jpg" alt="Logo">
                    </div>
                    <h3 class="h4 mb-2"><?php echo __('inventory_system'); ?></h3>
                    <p class="opacity-90 mb-0"><?php echo __('sign_in_to_continue'); ?></p>
                </div>
                
                <div class="card-body">
                    <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="" id="loginForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <div class="mb-4">
                            <label for="username" class="form-label">
                                <i class="fas fa-user"></i> <?php echo __('username'); ?>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" 
                                       class="form-control" 
                                       id="username" 
                                       name="username" 
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                       required 
                                       autofocus 
                                       autocomplete="username"
                                       placeholder="<?php echo __('enter_username'); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock"></i> <?php echo __('password'); ?>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password" 
                                       required 
                                       autocomplete="current-password"
                                       placeholder="<?php echo __('enter_password'); ?>">
                                <button type="button" class="password-toggle" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="mt-2 text-end">
                                <a href="forgot-password.php" class="small text-decoration-none"><?php echo __('forgot_password'); ?></a>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-3">
                            <button type="submit" class="btn btn-login" id="submitBtn">
                                <span id="btnText"><?php echo __('sign_in'); ?></span>
                                <span id="btnLoading" class="d-none">
                                    <span class="loading me-2"></span> <?php echo __('authenticating'); ?>
                                </span>
                            </button>
                            
                            <div class="divider">
                                <span>or continue with</span>
                            </div>
                            
                            <a href="register.php" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus me-2"></i> <?php echo __('create_new_account'); ?>
                            </a>
                        </div>
                    </form>
                    
                    <div class="form-footer">
                        <p class="mb-0">
                            <?php echo __('need_help'); ?> <a href="mailto:support@inventory.com"><?php echo __('contact_support'); ?></a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password visibility toggle
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });

            // Form submission loading state
            const loginForm = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnLoading = document.getElementById('btnLoading');
            
            loginForm.addEventListener('submit', function() {
                btnText.classList.add('d-none');
                btnLoading.classList.remove('d-none');
                submitBtn.disabled = true;
            });

            // Add focus effects
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('focused');
                });
            });

            // Auto-focus username if empty
            if (!document.getElementById('username').value) {
                document.getElementById('username').focus();
            }
        });
    </script>
</body>
</html>