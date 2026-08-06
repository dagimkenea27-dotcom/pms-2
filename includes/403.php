<?php
// includes/403.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden - Access Denied</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        }
        .error-card {
            max-width: 500px;
            width: 100%;
            text-align: center;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            background: white;
        }
        .error-icon {
            font-size: 5rem;
            color: #dc3545;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon">
            <i class="fas fa-lock"></i>
        </div>
        <h1 class="display-4 fw-bold">403</h1>
        <h2 class="h4 text-danger mb-3">Access Forbidden</h2>
        <p class="text-muted mb-4">
            You do not have permission to access this page. 
            Please contact your administrator if you believe this is an error.
        </p>
        <a href="../index.php" class="btn btn-primary px-4">
            <i class="fas fa-home me-2"></i> Return to Dashboard
        </a>
    </div>
</body>
</html>
