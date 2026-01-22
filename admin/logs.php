<?php
/**
 * Admin - Activity Logs
 */
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !hasRole('Admin')) {
    redirect('../login.php');
}

$page_title = 'Activity Logs';
$db = getDB();

// Fetch activity logs
$logs = [];
$result = $db->query("SELECT al.*, u.full_name as user_name, u.role as user_role 
                      FROM activity_logs al 
                      JOIN users u ON al.user_id = u.id 
                      ORDER BY al.created_at DESC 
                      LIMIT 100");

while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-history"></i> Activity Logs</h2>
        <p class="text-muted">Last 100 activities</p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>Request ID</th>
                        <th>IP Address</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo $log['id']; ?></td>
                        <td><?php echo htmlspecialchars($log['user_name']); ?></td>
                        <td>
                            <span class="badge role-badge role-<?php echo strtolower($log['user_role']); ?>">
                                <?php echo $log['user_role']; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                        <td><?php echo htmlspecialchars($log['details'] ?? '-'); ?></td>
                        <td>
                            <?php if ($log['request_id']): ?>
                            <a href="view_request.php?id=<?php echo $log['request_id']; ?>">
                                #<?php echo $log['request_id']; ?>
                            </a>
                            <?php else: ?>
                            -
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                        <td><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
