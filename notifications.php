<?php
/**
 * Notifications Page
 */
require_once 'config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Mark notification as read if ID is provided
if (isset($_GET['id'])) {
    $notif_id = intval($_GET['id']);
    $db = getDB();
    
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notif_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    
    // Get the request ID to redirect
    $stmt = $db->prepare("SELECT request_id FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notif_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $notif = $result->fetch_assoc();
        $request_id = $notif['request_id'];
        
        // Redirect based on role
        if (hasRole('Admin')) {
            redirect("admin/view_request.php?id=$request_id");
        } elseif (hasRole('Sales')) {
            redirect("sales/view_request.php?id=$request_id");
        } elseif (hasRole('Design')) {
            redirect("design/upload_design.php?id=$request_id");
        } elseif (hasRole('Approval')) {
            redirect("approval/review_request.php?id=$request_id");
        }
    }
    $stmt->close();
}

$page_title = 'Notifications';
$db = getDB();
$user_id = $_SESSION['user_id'];

// Fetch all notifications
$notifications = [];
$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

require_once 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-bell"></i> Notifications</h2>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (count($notifications) > 0): ?>
        <div class="list-group">
            <?php foreach ($notifications as $notif): ?>
            <a href="?id=<?php echo $notif['id']; ?>" 
               class="list-group-item list-group-item-action <?php echo $notif['is_read'] ? '' : 'list-group-item-primary'; ?>">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">
                        <?php if (!$notif['is_read']): ?>
                        <i class="fas fa-circle text-primary" style="font-size: 8px;"></i>
                        <?php endif; ?>
                        Request #<?php echo $notif['request_id']; ?>
                        <span class="badge bg-<?php 
                            echo match($notif['type']) {
                                'New Request' => 'warning',
                                'Design Uploaded' => 'info',
                                'Approval Completed' => 'success',
                                'Returned for Changes' => 'danger',
                                'Approved' => 'success',
                                'Closed' => 'secondary',
                                default => 'primary'
                            };
                        ?>">
                            <?php echo $notif['type']; ?>
                        </span>
                    </h6>
                    <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($notif['created_at'])); ?></small>
                </div>
                <p class="mb-1"><?php echo htmlspecialchars($notif['message']); ?></p>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-bell-slash fa-4x text-muted mb-3"></i>
            <h4>No Notifications</h4>
            <p class="text-muted">You don't have any notifications yet.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
