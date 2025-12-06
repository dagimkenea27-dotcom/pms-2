<?php
// simple_debug.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli('localhost', 'root', '', 'inventory_system');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h3>Users</h3>";
$result = $conn->query("SELECT id, username, role, is_active FROM users");
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row["id"]. " - Name: " . $row["username"]. " - Role: " . $row["role"]. "<br>";
    }
} else {
    echo "0 results in users";
}

echo "<h3>Notifications</h3>";
$result = $conn->query("SELECT * FROM notifications ORDER BY id DESC LIMIT 5");
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row["id"]. " - UserID: " . $row["user_id"]. " - Msg: " . $row["message"]. "<br>";
    }
} else {
    echo "0 results in notifications";
}
$conn->close();
?>
