<?php
/**
 * Approval - Pending Requests
 */
require_once '../config/config.php';

// Check if user is logged in and is in Approval role
if (!isLoggedIn() || !hasRole('Approval')) {
    redirect('../login.php');
}

$page_title = 'Pending Approval Requests';
$db = getDB();

// Fetch pending approval requests
$requests = [];
$result = $db->query("SELECT r.*, u.full_name as created_by_name 
                      FROM requests r 
                      JOIN users u ON r.created_by = u.id 
                      WHERE r.status = 'Pending Approval' 
                      ORDER BY r.created_at DESC");

while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-check-circle"></i> Pending Approval Requests</h2>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (count($requests) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Event Name</th>
                        <th>Location</th>
                        <th>Event Date</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?php echo $request['id']; ?></td>
                        <td><?php echo htmlspecialchars($request['event_name']); ?></td>
                        <td><?php echo htmlspecialchars($request['location']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($request['event_date'])); ?></td>
                        <td>
                            <span class="badge status-badge status-<?php echo strtolower(str_replace(' ', '-', $request['status'])); ?>">
                                <?php echo $request['status']; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($request['created_by_name']); ?></td>
                        <td><?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?></td>
                        <td>
                            <a href="review_request.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-success">
                                <i class="fas fa-check"></i> Review
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-check-circle fa-4x text-muted mb-3"></i>
            <h4>No Pending Approvals</h4>
            <p class="text-muted">All approval requests have been processed.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
