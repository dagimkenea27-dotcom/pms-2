<?php
require_once "config/database.php";
$db = (new Database())->getConnection();

$user_id = 7;
$stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

echo "Unread count for User 7: " . $count . "<br>";

$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 3");
$stmt->execute([$user_id]);
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row); echo "<br>";
}
?>
