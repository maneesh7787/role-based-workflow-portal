<?php
/**
 * Dashboard - Main landing page after login
 */
require_once 'config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$page_title = 'Dashboard';
$db = getDB();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch statistics based on role
$stats = [];

if ($role == 'Admin') {
    // Admin statistics
    $result = $db->query("SELECT COUNT(*) as total FROM requests");
    $stats['total_requests'] = $result->fetch_assoc()['total'];
    
    $result = $db->query("SELECT COUNT(*) as total FROM requests WHERE status = 'Pending Design'");
    $stats['pending_design'] = $result->fetch_assoc()['total'];
    
    $result = $db->query("SELECT COUNT(*) as total FROM requests WHERE status = 'Pending Approval'");
    $stats['pending_approval'] = $result->fetch_assoc()['total'];
    
    $result = $db->query("SELECT COUNT(*) as total FROM requests WHERE status IN ('Approved', 'Closed')");
    $stats['completed'] = $result->fetch_assoc()['total'];
    
    $result = $db->query("SELECT COUNT(*) as total FROM users WHERE is_active = 1");
    $stats['active_users'] = $result->fetch_assoc()['total'];
    
} elseif ($role == 'Sales') {
    // Sales statistics
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM requests WHERE created_by = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['my_requests'] = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM requests WHERE created_by = ? AND status = 'Pending Design'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['pending_design'] = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM requests WHERE created_by = ? AND status = 'Sent to Sales'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['pending_review'] = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM requests WHERE created_by = ? AND status IN ('Approved', 'Closed')");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['completed'] = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
} elseif ($role == 'Design') {
    // Design statistics
    $result = $db->query("SELECT COUNT(*) as total FROM requests WHERE status = 'Pending Design'");
    $stats['pending_design'] = $result->fetch_assoc()['total'];
    
    $result = $db->query("SELECT COUNT(*) as total FROM requests WHERE status = 'Returned to Design'");
    $stats['returned'] = $result->fetch_assoc()['total'];
    
    $stmt = $db->prepare("SELECT COUNT(DISTINCT request_id) as total FROM request_designs WHERE uploaded_by = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['completed'] = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
} elseif ($role == 'Approval') {
    // Approval statistics
    $result = $db->query("SELECT COUNT(*) as total FROM requests WHERE status = 'Pending Approval'");
    $stats['pending_approval'] = $result->fetch_assoc()['total'];
    
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM approvals WHERE approved_by = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['approved'] = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
}

// Fetch recent requests
$recent_requests = [];
if ($role == 'Admin') {
    $result = $db->query("SELECT r.*, u.full_name as created_by_name 
                          FROM requests r 
                          JOIN users u ON r.created_by = u.id 
                          ORDER BY r.created_at DESC LIMIT 10");
} elseif ($role == 'Sales') {
    $stmt = $db->prepare("SELECT r.*, u.full_name as created_by_name 
                          FROM requests r 
                          JOIN users u ON r.created_by = u.id 
                          WHERE r.created_by = ? 
                          ORDER BY r.created_at DESC LIMIT 10");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
} elseif ($role == 'Design') {
    $result = $db->query("SELECT r.*, u.full_name as created_by_name 
                          FROM requests r 
                          JOIN users u ON r.created_by = u.id 
                          WHERE r.status IN ('Pending Design', 'Returned to Design') 
                          ORDER BY r.created_at DESC LIMIT 10");
} elseif ($role == 'Approval') {
    $result = $db->query("SELECT r.*, u.full_name as created_by_name 
                          FROM requests r 
                          JOIN users u ON r.created_by = u.id 
                          WHERE r.status = 'Pending Approval' 
                          ORDER BY r.created_at DESC LIMIT 10");
}

if (isset($result)) {
    while ($row = $result->fetch_assoc()) {
        $recent_requests[] = $row;
    }
    if (isset($stmt)) $stmt->close();
}

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">
            <i class="fas fa-tachometer-alt"></i> Dashboard
            <small class="text-muted">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></small>
        </h1>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <?php if ($role == 'Admin'): ?>
    <div class="col-md-3 mb-3">
        <div class="card dashboard-card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase mb-2">Total Requests</h6>
                        <h2 class="mb-0"><?php echo $stats['total_requests']; ?></h2>
                    </div>
                    <i class="fas fa-clipboard-list card-icon"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card dashboard-card bg-warning text-dark">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase mb-2">Pending Design</h6>
                        <h2 class="mb-0"><?php echo $stats['pending_design']; ?></h2>
                    </div>
                    <i class="fas fa-palette card-icon"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card dashboard-card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase mb-2">Pending Approval</h6>
                        <h2 class="mb-0"><?php echo $stats['pending_approval']; ?></h2>
                    </div>
                    <i class="fas fa-check-circle card-icon"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card dashboard-card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase mb-2">Completed</h6>
                        <h2 class="mb-0"><?php echo $stats['completed']; ?></h2>
                    </div>
                    <i class="fas fa-check-double card-icon"></i>
                </div>
            </div>
        </div>
    </div>
    
    <?php elseif ($role == 'Sales'): ?>
    <div class="col-md-3 mb-3">
        <div class="card dashboard-card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase mb-2">My Requests</h6>
                        <h2 class="mb-0"><?php echo $stats['my_requests']; ?></h2>
                    </div>
                    <i class="fas fa-clipboard-list card-icon"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card dashboard-card bg-warning text-dark">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase mb-2">Pending Design</h6>
                        <h2 class="mb-0"><?php echo $stats['pending_design']; ?></h2>
                    </div>
                    <i class="fas fa-hourglass-half card-icon"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card dashboard-card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase mb-2">Pending Review</h6>
                        <h2 class="mb-0"><?php echo $stats['pending_review']; ?></h2>
                    </div>
                    <i class="fas fa-eye card-icon"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card dashboard-card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase mb-2">Completed</h6>
                        <h2 class="mb-0"><?php echo $stats['completed']; ?></h2>
                    </div>
                    <i class="fas fa-check-circle card-icon"></i>
                </div>
            </div>
        </div>
    </div>
    
    <?php elseif ($role == 'Design'): ?>
    <div class="col-md-4 mb-3">
        <div class="card dashboard-card bg-warning text-dark">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase mb-2">Pending Design</h6>
                        <h2 class="mb-0"><?php echo $stats['pending_design']; ?></h2>
                    </div>
                    <i class="fas fa-palette card-icon"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card dashboard-card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase mb-2">Returned</h6>
                        <h2 class="mb-0"><?php echo $stats['returned']; ?></h2>
                    </div>
                    <i class="fas fa-undo card-icon"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card dashboard-card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase mb-2">Completed</h6>
                        <h2 class="mb-0"><?php echo $stats['completed']; ?></h2>
                    </div>
                    <i class="fas fa-check-circle card-icon"></i>
                </div>
            </div>
        </div>
    </div>
    
    <?php elseif ($role == 'Approval'): ?>
    <div class="col-md-6 mb-3">
        <div class="card dashboard-card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase mb-2">Pending Approval</h6>
                        <h2 class="mb-0"><?php echo $stats['pending_approval']; ?></h2>
                    </div>
                    <i class="fas fa-check-circle card-icon"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="card dashboard-card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase mb-2">Approved</h6>
                        <h2 class="mb-0"><?php echo $stats['approved']; ?></h2>
                    </div>
                    <i class="fas fa-check-double card-icon"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Recent Requests Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-list"></i> Recent Requests</h5>
            </div>
            <div class="card-body">
                <?php if (count($recent_requests) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Event Name</th>
                                <th>Location</th>
                                <th>Event Date</th>
                                <th>Status</th>
                                <?php if ($role == 'Admin'): ?>
                                <th>Created By</th>
                                <?php endif; ?>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_requests as $request): ?>
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
                                <?php if ($role == 'Admin'): ?>
                                <td><?php echo htmlspecialchars($request['created_by_name']); ?></td>
                                <?php endif; ?>
                                <td><?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?></td>
                                <td>
                                    <?php if ($role == 'Sales'): ?>
                                    <a href="sales/view_request.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php elseif ($role == 'Design' && in_array($request['status'], ['Pending Design', 'Returned to Design'])): ?>
                                    <a href="design/upload_design.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-upload"></i> Upload
                                    </a>
                                    <?php elseif ($role == 'Approval' && $request['status'] == 'Pending Approval'): ?>
                                    <a href="approval/review_request.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-success">
                                        <i class="fas fa-check"></i> Review
                                    </a>
                                    <?php elseif ($role == 'Admin'): ?>
                                    <a href="admin/view_request.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center text-muted">No requests found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
