<?php
/**
 * Sales - My Requests
 */
require_once '../config/config.php';

// Check if user is logged in and is in Sales role
if (!isLoggedIn() || !hasRole('Sales')) {
    redirect('../login.php');
}

$page_title = 'My Requests';
$db = getDB();
$user_id = $_SESSION['user_id'];

// Fetch user's requests
$requests = [];
$stmt = $db->prepare("SELECT * FROM requests WHERE created_by = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}
$stmt->close();

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-list"></i> My Requests</h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="create_request.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Request
        </a>
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
                        <td><?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?></td>
                        <td>
                            <a href="view_request.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
            <h4>No Requests Found</h4>
            <p class="text-muted">You haven't created any requests yet.</p>
            <a href="create_request.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Your First Request
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
