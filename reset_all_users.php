<?php
// reset_all_users.php - Completely reset all users with proper hashes
require_once "config/database.php";

$database = new Database();
$db = $database->getConnection();

echo "<h2>Resetting All Users</h2>";

// Delete all existing users (be careful!)
$delete_query = "DELETE FROM users";
$delete_stmt = $db->prepare($delete_query);
$delete_stmt->execute();

echo "<div class='alert alert-info'>Cleared existing users</div>";

// Create fresh users with proper password hashes
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

foreach ($users as $user_data) {
    // Hash the password properly
    $password_hash = password_hash($user_data['password'], PASSWORD_DEFAULT);
    
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
        echo "✓ User '{$user_data['username']}' created!";
        echo "</div>";
    } else {
        echo "<div class='alert alert-danger'>";
        echo "✗ Failed to create user '{$user_data['username']}'";
        echo "</div>";
    }
}

// Final verification
echo "<hr><h3>Verification</h3>";

foreach ($users as $user_data) {
    $query = "SELECT username, password_hash FROM users WHERE username = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user_data['username']);
    $stmt->execute();
    
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (password_verify($user_data['password'], $row['password_hash'])) {
            echo "<div class='alert alert-success'>✓ '{$user_data['username']}' login works!</div>";
        } else {
            echo "<div class='alert alert-danger'>✗ '{$user_data['username']}' login FAILED</div>";
        }
    }
}

echo "<hr>";
echo "<div class='alert alert-info'>";
echo "<h4>Login Credentials:</h4>";
echo "<strong>Admin:</strong> admin / admin123<br>";
echo "<strong>Manager:</strong> manager / manager123<br>";
echo "<strong>Staff:</strong> staff / staff123<br>";
echo "</div>";
?>
<!DOCTYPE html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4"></body>
</html>