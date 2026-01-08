<?php
require_once "config/database.php";
require_once "models/User.php";
require_once "models/Notification.php"; // Added notification model
require_once "config/auth.php";
require_once "includes/functions.php";

Auth::startSession();

// If already logged in, redirect to dashboard
if (Auth::isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// Initialize database and models
$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$notification = new Notification($db);

$message = '';
$error = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !Auth::validateCSRF($_POST['csrf_token'])) {
        $error = __('security_check_failed');
    } else {
        $user->username = $_POST['username'] ?? '';
        $user->password = $_POST['password'] ?? '';
        $user->email = $_POST['email'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $user->first_name = $_POST['first_name'] ?? '';
        $user->last_name = $_POST['last_name'] ?? '';
        $user->role = 'staff'; // Default role
        
        // Validation checks
        if (empty($user->username) || empty($user->password) || empty($user->email)) {
            $error = __('please_fill_required');
        } elseif ($user->password !== $confirm_password) {
            $error = __('passwords_do_not_match');
        } elseif ($user->usernameExists()) {
            $error = __('username_exists');
        } elseif ($user->emailExists()) {
            $error = __('email_exists');
        } elseif (strlen($user->password) < 8 || 
                 !preg_match('/[A-Z]/', $user->password) || 
                 !preg_match('/[a-z]/', $user->password) || 
                 !preg_match('/[0-9]/', $user->password) || 
                 !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $user->password)) {
            $error = __('password_requirements_error');
        } else {
            if ($user->create()) {
                // Notify admins
                $notifMsg = "New user registration: " . $user->username . " (" . $user->email . ")";
                $notification->notifyAdmins($notifMsg, "users/view_users.php", "info");
                
                $message = __('registration_successful');
            } else {
                $error = __('unable_to_register');
            }
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
    <title>Register - Inventory System</title>
    <link rel="icon" type="image/jpeg" href="<?php echo BASE_URL; ?>assets/img/logo.jpg">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --primary-color: #4e73df;
            --primary-dark: #224abe;
            --secondary-color: #6e707e;
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
            --light-bg: #f8f9fc;
            --border-color: #e3e6f0;
            --text-primary: #3a3b45;
            --text-secondary: #858796;
            --shadow-sm: 0 0.15rem 0.35rem rgba(0,0,0,0.075);
            --shadow-md: 0 0.35rem 0.75rem rgba(0,0,0,0.1);
            --shadow-lg: 0 1rem 3rem rgba(0,0,0,0.175);
            --radius-sm: 0.35rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
        }

        /* ... (rest of styles, unchanged mainly, but I need to make sure I don't cut them off incorrectly) */
        /* To minimize large replacement, I'll target just the head and the specific body part separately if I can, but the tool requires contiguous blocks for single call. 
           Refining to just the HEAD and later the BODY part is better.
        */

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
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            position: relative;
            overflow: hidden;
        }

        .register-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .background-pattern {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            opacity: 0.05;
            z-index: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%234e73df' fill-opacity='0.4'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .register-card {
            width: 100%;
            max-width: 800px;
            background: white;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            border: 1px solid var(--border-color);
            animation: slideUp 0.6s ease-out;
            position: relative;
            z-index: 1;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-header {
            background: var(--primary-gradient);
            color: white;
            padding: 1.5rem 1rem 1rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        }

        .brand-icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem;
            position: relative;
            z-index: 2;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.3);
            overflow: hidden;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .brand-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .card-body {
            padding: 1.5rem;
        }

        .form-section {
            margin-bottom: 1.5rem; /* Tighter sections */
        }

        .section-title {
            font-size: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
        }

        .mb-4 {
            margin-bottom: 0.75rem !important; /* Tighter field spacing */
        }

        .form-control {
            padding: 0.5rem 0.75rem; /* Compact inputs */
            font-size: 0.9rem;
        }
        
        .progress-steps {
            margin-bottom: 1.5rem;
        }
        
        .step-icon {
            width: 28px; /* Smaller steps */
            height: 28px;
            font-size: 0.8rem;
        }

        .input-group .form-control {
            border-right: 0;
        }

        .input-group .input-group-text {
            background: white;
            border: 2px solid var(--border-color);
            border-right: 0;
            color: var(--text-secondary);
            transition: all 0.3s ease;
        }

        .form-control:focus + .input-group-text,
        .form-control:focus ~ .input-group-text {
            border-color: var(--primary-color);
        }

        .form-label {
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label .required {
            color: var(--danger-color);
        }

        .password-strength {
            margin-top: 0.5rem;
        }

        .strength-meter {
            height: 4px;
            background: var(--border-color);
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .strength-fill {
            height: 100%;
            width: 0;
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        .strength-text {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .requirements-list {
            list-style: none;
            padding-left: 0;
            margin-top: 0.75rem;
        }

        .requirements-list li {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 0.375rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.3s ease;
        }

        .requirements-list li.valid {
            color: var(--success-color);
        }

        .requirements-list li i {
            font-size: 0.8rem;
        }

        .btn-register {
            background: var(--primary-gradient);
            border: none;
            border-radius: var(--radius-md);
            padding: 1rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            color: white;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            width: 100%;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .alert {
            border-radius: var(--radius-md);
            border: none;
            padding: 1.25rem 1.5rem;
            margin-bottom: 2rem;
            animation: slideIn 0.3s ease-out;
            position: relative;
            overflow: hidden;
        }

        .alert::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }

        .alert-success::before {
            background: var(--success-color);
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }

        .alert-danger::before {
            background: var(--danger-color);
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 16px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--border-color);
            z-index: 1;
        }

        .step {
            position: relative;
            z-index: 2;
            text-align: center;
            flex: 1;
        }

        .step-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: white;
            border: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .step.active .step-icon {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .step.completed .step-icon {
            background: var(--success-color);
            border-color: var(--success-color);
            color: white;
        }

        .step-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .step.active .step-label {
            color: var(--primary-color);
            font-weight: 600;
        }

        .terms-check {
            margin-top: 1.5rem;
            padding: 1rem;
            background: rgba(78, 115, 223, 0.05);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
        }

        .form-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .form-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .form-footer a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .input-with-icon {
            position: relative;
        }

        .input-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            pointer-events: none;
        }

        .valid-feedback,
        .invalid-feedback {
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }

        @media (max-width: 768px) {
            .register-container {
                padding: 1rem;
            }
            
            .card-body {
                padding: 2rem 1.5rem;
            }
            
            .progress-steps {
                flex-wrap: wrap;
                gap: 1rem;
            }
            
            .progress-steps::before {
                display: none;
            }
            
            .step {
                flex: none;
                width: calc(33.333% - 1rem);
            }
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: 0.5rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .form-row > div {
            flex: 1;
        }

        .character-count {
            font-size: 0.8rem;
            color: var(--text-secondary);
            text-align: right;
            margin-top: 0.25rem;
        }

        .character-count.warning {
            color: var(--warning-color);
        }

        .character-count.danger {
            color: var(--danger-color);
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            z-index: 10;
            padding: 0;
        }

        .form-control[type="password"] {
            padding-right: 40px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div style="position: absolute; top: 1rem; right: 1rem; z-index: 10;">
             <a href="language_switch.php?lang=en" class="btn btn-sm btn-light <?php echo get_current_lang() == 'en' ? 'active' : ''; ?>">EN</a>
             <a href="language_switch.php?lang=ja" class="btn btn-sm btn-light <?php echo get_current_lang() == 'ja' ? 'active' : ''; ?>">JP</a>
        </div>
        <div class="background-pattern"></div>
        
        <div class="register-card">
            <div class="card-header">
                <div class="brand-icon">
                    <img src="<?php echo BASE_URL; ?>assets/img/logo.jpg" alt="Logo">
                </div>
                <h1 class="h3 mb-2"><?php echo __('join_inventory_system'); ?></h1>
                <p class="opacity-90 mb-0"><?php echo __('create_account_minutes'); ?></p>
            </div>
            
            <div class="card-body">
                <div class="progress-steps">
                    <div class="step active" id="step1">
                        <div class="step-icon">1</div>
                        <div class="step-label"><?php echo __('personal_info'); ?></div>
                    </div>
                    <div class="step" id="step2">
                        <div class="step-icon">2</div>
                        <div class="step-label"><?php echo __('account_details'); ?></div>
                    </div>
                    <div class="step" id="step3">
                        <div class="step-icon">3</div>
                        <div class="step-label"><?php echo __('complete'); ?></div>
                    </div>
                </div>
                
                <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-check-circle me-3 fa-lg"></i>
                    <div><?php echo htmlspecialchars($message); ?></div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-circle me-3 fa-lg"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="POST" action="" id="registerForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-user-circle"></i> <?php echo __('personal_info'); ?>
                        </h3>
                        
                        <div class="form-row">
                            <div>
                                <label for="first_name" class="form-label">
                                    <i class="fas fa-user"></i> <?php echo __('first_name'); ?> <span class="required">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="first_name" 
                                           name="first_name" 
                                           value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" 
                                           required
                                           placeholder="John"
                                           maxlength="50">
                                </div>
                                <div class="character-count" id="firstNameCount">0/50</div>
                            </div>
                            
                            <div>
                                <label for="last_name" class="form-label">
                                    <i class="fas fa-user"></i> <?php echo __('last_name'); ?> <span class="required">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="last_name" 
                                           name="last_name" 
                                           value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" 
                                           required
                                           placeholder="Doe"
                                           maxlength="50">
                                </div>
                                <div class="character-count" id="lastNameCount">0/50</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-id-card"></i> <?php echo __('account_details'); ?>
                        </h3>
                        
                        <div class="mb-4">
                            <label for="username" class="form-label">
                                <i class="fas fa-at"></i> <?php echo __('username'); ?> <span class="required">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-at"></i></span>
                                <input type="text" 
                                       class="form-control" 
                                       id="username" 
                                       name="username" 
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                       required
                                       placeholder="johndoe"
                                       maxlength="30"
                                       pattern="[a-zA-Z0-9_]+">
                                <span class="input-icon">
                                    <i class="fas fa-check-circle d-none text-success" id="usernameValid"></i>
                                </span>
                            </div>
                            <div class="character-count" id="usernameCount">0/30</div>
                            <div class="form-text">Only letters, numbers, and underscores allowed</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i> <?php echo __('email_address'); ?> <span class="required">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                       required
                                       placeholder="john@example.com"
                                       maxlength="100">
                                <span class="input-icon">
                                    <i class="fas fa-check-circle d-none text-success" id="emailValid"></i>
                                </span>
                            </div>
                            <div class="character-count" id="emailCount">0/100</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock"></i> <?php echo __('password'); ?> <span class="required">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password" 
                                       required
                                       placeholder="Create a strong password"
                                       minlength="8">
                                <button type="button" class="password-toggle" onclick="togglePasswordVisibility('password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            
                            <div class="password-strength">
                                <div class="strength-meter">
                                    <div class="strength-fill" id="strengthFill"></div>
                                </div>
                                <div class="strength-text" id="strengthText"><?php echo __('password_strength'); ?></div>
                            </div>
                            
                            <ul class="requirements-list" id="passwordRequirements">
                                <li id="reqLength">
                                    <i class="fas fa-circle"></i>
                                    <span><?php echo __('at_least_8_chars'); ?></span>
                                </li>
                                <li id="reqUpper">
                                    <i class="fas fa-circle"></i>
                                    <span><?php echo __('one_uppercase'); ?></span>
                                </li>
                                <li id="reqLower">
                                    <i class="fas fa-circle"></i>
                                    <span><?php echo __('one_lowercase'); ?></span>
                                </li>
                                <li id="reqNumber">
                                    <i class="fas fa-circle"></i>
                                    <span><?php echo __('one_number'); ?></span>
                                </li>
                                <li id="reqSpecial">
                                    <i class="fas fa-circle"></i>
                                    <span><?php echo __('one_special_char'); ?></span>
                                </li>
                            </ul>
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock"></i> <?php echo __('confirm_password'); ?> <span class="required">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" 
                                       class="form-control" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       required
                                       placeholder="Repeat your password"
                                       minlength="8">
                                <button type="button" class="password-toggle" onclick="togglePasswordVisibility('confirm_password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div id="passwordMatch" class="form-text mt-1"></div>
                        </div>
                        
                        <div class="terms-check">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="terms" required>
                                <label class="form-check-label small" for="terms">
                                    <?php echo __('i_agree_to'); ?> <a href="terms.php" class="text-decoration-none"><?php echo __('terms_of_service'); ?></a> 
                                    <?php echo __('and'); ?> <a href="privacy.php" class="text-decoration-none"><?php echo __('privacy_policy'); ?></a>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-3">
                        <button type="submit" class="btn btn-register" id="submitBtn">
                            <span id="btnText">
                                <i class="fas fa-user-plus me-2"></i> <?php echo __('create_account'); ?>
                            </span>
                            <span id="btnLoading" class="d-none">
                                <span class="loading"></span> <?php echo __('creating_account'); ?>
                            </span>
                        </button>
                    </div>
                </form>
                
                <div class="form-footer">
                    <p class="mb-0">
                        <?php echo __('already_have_account'); ?> 
                        <a href="login.php" class="fw-bold"><?php echo __('login_here'); ?></a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle Password Visibility Function
        function togglePasswordVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            btn.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Password match validation
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('confirm_password');
            const matchText = document.getElementById('passwordMatch');

            function checkMatch() {
                if (confirmInput.value === '') {
                    matchText.textContent = '';
                    matchText.className = 'form-text mt-1';
                    return;
                }
                
                if (passwordInput.value === confirmInput.value) {
                    matchText.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
                    matchText.className = 'form-text mt-1 text-success';
                } else {
                    matchText.innerHTML = '<i class="fas fa-times-circle"></i> Passwords do not match';
                    matchText.className = 'form-text mt-1 text-danger';
                }
            }

            passwordInput.addEventListener('input', checkMatch);
            confirmInput.addEventListener('input', checkMatch);

            // Character counters
            const fields = ['first_name', 'last_name', 'username', 'email'];
            fields.forEach(field => {
                const input = document.getElementById(field);
                const counter = document.getElementById(field + 'Count');
                
                if (input && counter) {
                    input.addEventListener('input', function() {
                        const maxLength = this.getAttribute('maxlength') || 50;
                        const currentLength = this.value.length;
                        counter.textContent = `${currentLength}/${maxLength}`;
                        
                        // Add warning class when approaching limit
                        const percentage = (currentLength / maxLength) * 100;
                        counter.className = 'character-count';
                        if (percentage >= 90) {
                            counter.classList.add('danger');
                        } else if (percentage >= 75) {
                            counter.classList.add('warning');
                        }
                    });
                    
                    // Trigger initial count
                    input.dispatchEvent(new Event('input'));
                }
            });

            // Password strength meter
            const passwordInput = document.getElementById('password');
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            const requirements = {
                length: document.getElementById('reqLength'),
                upper: document.getElementById('reqUpper'),
                lower: document.getElementById('reqLower'),
                number: document.getElementById('reqNumber'),
                special: document.getElementById('reqSpecial')
            };
            
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                
                // Check requirements
                const hasLength = password.length >= 8;
                const hasUpper = /[A-Z]/.test(password);
                const hasLower = /[a-z]/.test(password);
                const hasNumber = /\d/.test(password);
                const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
                
                // Update requirement icons
                updateRequirement(requirements.length, hasLength);
                updateRequirement(requirements.upper, hasUpper);
                updateRequirement(requirements.lower, hasLower);
                updateRequirement(requirements.number, hasNumber);
                updateRequirement(requirements.special, hasSpecial);
                
                // Calculate strength
                let strength = 0;
                if (hasLength) strength += 20;
                if (hasUpper) strength += 20;
                if (hasLower) strength += 20;
                if (hasNumber) strength += 20;
                if (hasSpecial) strength += 20;
                
                // Update strength meter
                strengthFill.style.width = strength + '%';
                
                // Update strength text and color
                if (strength === 0) {
                    strengthText.textContent = 'No password';
                    strengthFill.style.background = '#e74a3b';
                } else if (strength <= 40) {
                    strengthText.textContent = 'Weak';
                    strengthFill.style.background = '#e74a3b';
                } else if (strength <= 60) {
                    strengthText.textContent = 'Fair';
                    strengthFill.style.background = '#f6c23e';
                } else if (strength <= 80) {
                    strengthText.textContent = 'Good';
                    strengthFill.style.background = '#4e73df';
                } else {
                    strengthText.textContent = 'Strong';
                    strengthFill.style.background = '#1cc88a';
                }
            });
            
            function updateRequirement(element, isValid) {
                if (isValid) {
                    element.classList.add('valid');
                    element.querySelector('i').className = 'fas fa-check-circle';
                    element.querySelector('i').style.color = '#1cc88a';
                } else {
                    element.classList.remove('valid');
                    element.querySelector('i').className = 'fas fa-circle';
                    element.querySelector('i').style.color = '';
                }
            }
            
            // Form validation
            const registerForm = document.getElementById('registerForm');
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnLoading = document.getElementById('btnLoading');
            
            registerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Validate password
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;

                if (password !== confirmPassword) {
                    alert('Passwords do not match.');
                    return;
                }

                const hasLength = password.length >= 8;
                const hasUpper = /[A-Z]/.test(password);
                const hasLower = /[a-z]/.test(password);
                const hasNumber = /\d/.test(password);
                const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
                
                if (!hasLength || !hasUpper || !hasLower || !hasNumber || !hasSpecial) {
                    alert('Please ensure your password meets all requirements.');
                    return;
                }
                
                // Validate terms
                if (!document.getElementById('terms').checked) {
                    alert('Please agree to the Terms of Service and Privacy Policy.');
                    return;
                }
                
                // Show loading state
                btnText.classList.add('d-none');
                btnLoading.classList.remove('d-none');
                submitBtn.disabled = true;
                
                // Submit form
                this.submit();
            });
            
            // Update progress steps based on form completion
            const steps = document.querySelectorAll('.step');
            const inputs = registerForm.querySelectorAll('input[required]');
            
            function updateProgress() {
                let filledCount = 0;
                inputs.forEach(input => {
                    if (input.value.trim() !== '') {
                        filledCount++;
                    }
                });
                
                const percentage = (filledCount / inputs.length) * 100;
                
                if (percentage >= 66) {
                    steps[0].classList.add('completed');
                    steps[1].classList.add('completed');
                    steps[2].classList.add('active');
                } else if (percentage >= 33) {
                    steps[0].classList.add('completed');
                    steps[1].classList.add('active');
                    steps[2].classList.remove('active');
                } else {
                    steps[0].classList.add('active');
                    steps[1].classList.remove('active', 'completed');
                    steps[2].classList.remove('active', 'completed');
                }
            }
            
            inputs.forEach(input => {
                input.addEventListener('input', updateProgress);
                input.addEventListener('change', updateProgress);
            });
            
            // Initial progress update
            updateProgress();
            
            // Add focus effects
            const formControls = document.querySelectorAll('.form-control');
            formControls.forEach(control => {
                control.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                control.addEventListener('blur', function() {
                    this.parentElement.classList.remove('focused');
                });
            });
            
            // Auto-focus first empty field
            const firstEmpty = Array.from(inputs).find(input => !input.value.trim());
            if (firstEmpty) {
                firstEmpty.focus();
            }
        });
    </script>
</body>
</html>