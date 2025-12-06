<?php
// notifications.php
require_once "config/auth_check.php";
require_once "config/database.php";
require_once "models/Notification.php";

$database = new Database();
$db = $database->getConnection();
$notification = new Notification($db);
$user_id = Auth::getCurrentUser()['id'];

// Handle Mark All as Read
if (isset($_POST['mark_all_read'])) {
    if ($notification->markAllAsRead($user_id)) {
        $success_msg = "All notifications marked as read.";
    }
}

// Handle Mark Single as Read
if (isset($_POST['mark_read_id'])) {
    if ($notification->markAsRead($_POST['mark_read_id'], $user_id)) {
        // Redirect if link provided
        if (!empty($_POST['redirect_link']) && $_POST['redirect_link'] != '#') {
            header("Location: " . $_POST['redirect_link']);
            exit;
        }
    }
}

// Fetch all notifications (with pagination logic if needed, for now get recent 50)
$stmt = $notification->getRecent($user_id, 50);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once "includes/header.php";
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Notifications</h1>
    <?php if (count($notifications) > 0): ?>
    <form method="POST" action="">
        <button type="submit" name="mark_all_read" class="btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-check-double fa-sm text-white-50"></i> Mark All as Read
        </button>
    </form>
    <?php endif; ?>
</div>

<?php if (isset($success_msg)): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?php echo $success_msg; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">All Alerts</h6>
            </div>
            <div class="card-body">
                <?php if (count($notifications) > 0): ?>
                    <div class="list-group list-group-flush">
                    <?php foreach ($notifications as $notif): ?>
                        <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $notif['is_read'] ? '' : 'bg-light'; ?>">
                            <div class="d-flex align-items-center">
                                <div class="icon-circle bg-<?php echo $notif['type'] == 'info' ? 'primary' : ($notif['type'] == 'success' ? 'success' : 'warning'); ?> text-white p-3 rounded-circle mr-3">
                                    <i class="fas fa-<?php echo $notif['type'] == 'info' ? 'file-alt' : 'exclamation-triangle'; ?>"></i>
                                </div>
                                <div class="ms-3">
                                    <div class="small text-gray-500"><?php echo date('F j, Y, g:i a', strtotime($notif['created_at'])); ?></div>
                                    <span class="font-weight-<?php echo $notif['is_read'] ? 'normal' : 'bold'; ?>"><?php echo htmlspecialchars($notif['message']); ?></span>
                                </div>
                            </div>
                            <div class="action-buttons">
                                <?php if (!$notif['is_read']): ?>
                                <form method="POST" action="" class="d-inline">
                                    <input type="hidden" name="mark_read_id" value="<?php echo $notif['id']; ?>">
                                    <input type="hidden" name="redirect_link" value="<?php echo $notif['link'] ? BASE_URL . $notif['link'] : '#'; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-primary" title="Mark as Read">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <?php if ($notif['link'] && $notif['link'] != '#'): ?>
                                <a href="<?php echo BASE_URL . $notif['link']; ?>" class="btn btn-sm btn-circle btn-light ml-2" title="View">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted my-5">You have no notifications.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>
