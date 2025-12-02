<?php
// create_users.php - Create users with hashed passwords
require_once "config/database.php";

$database = new Database();
$db = $database->getConnection();

// Users to create
$users = [
    [
        'username' => 'admin',
        'password' => 'admin123',
        'email' => 'admin@inventory.com',
        'first_name' => 'System',
        'last_name' => 'Administrator',
        'role' => 'admin'
    ],
    [
        'username' => 'manager',
        'password' => 'manager123',
        'email' => 'manager@inventory.com',
        'first_name' => 'Inventory',
        'last_name' => 'Manager',
        'role' => 'manager'
    ],
    [
        'username' => 'staff',
        'password' => 'staff123',
        'email' => 'staff@inventory.com',
        'first_name' => 'Regular',
        'last_name' => 'Staff',
        'role' => 'staff'
    ]
];

echo "<h2>Creating Users with Hashed Passwords</h2>";

foreach ($users as $user_data) {
    // Hash the password
    $password_hash = password_hash($user_data['password'], PASSWORD_DEFAULT);
    
    // Check if user already exists
    $check_query = "SELECT id FROM users WHERE username = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(1, $user_data['username']);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        echo "<div class='alert alert-warning'>User '{$user_data['username']}' already exists - skipping</div>";
        continue;
    }
    
    // Insert user
    $query = "INSERT INTO users (username, email, password_hash, first_name, last_name, role, is_active) 
              VALUES (:username, :email, :password_hash, :first_name, :last_name, :role, 1)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":username", $user_data['username']);
    $stmt->bindParam(":email", $user_data['email']);
    $stmt->bindParam(":password_hash", $password_hash);
    $stmt->bindParam(":first_name", $user_data['first_name']);
    $stmt->bindParam(":last_name", $user_data['last_name']);
    $stmt->bindParam(":role", $user_data['role']);
    
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>";
        echo "✓ User '{$user_data['username']}' created successfully!<br>";
        echo "<small>Password: {$user_data['password']} | Role: {$user_data['role']}</small>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-danger'>";
        echo "✗ Failed to create user '{$user_data['username']}'";
        echo "</div>";
    }
}

echo "<hr><h3>Verification</h3>";

// Verify users can login
foreach ($users as $user_data) {
    $query = "SELECT username, password_hash FROM users WHERE username = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user_data['username']);
    $stmt->execute();
    
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (password_verify($user_data['password'], $row['password_hash'])) {
            echo "<div class='alert alert-info'>✓ Password verification successful for '{$user_data['username']}'</div>";
        } else {
            echo "<div class='alert alert-danger'>✗ Password verification FAILED for '{$user_data['username']}'</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4"></body>
</html>