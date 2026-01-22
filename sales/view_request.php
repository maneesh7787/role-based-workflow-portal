<?php
/**
 * Sales - View Request and Take Action
 */
require_once '../config/config.php';

// Check if user is logged in and is in Sales role
if (!isLoggedIn() || !hasRole('Sales')) {
    redirect('../login.php');
}

$request_id = intval($_GET['id'] ?? 0);
if (!$request_id) {
    redirect('my_requests.php');
}

$page_title = 'View Request';
$db = getDB();
$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle actions (approve, close, return to design)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == 'approve') {
        $stmt = $db->prepare("UPDATE requests SET status = 'Approved' WHERE id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $success = 'Request approved successfully!';
        logActivity($user_id, 'Request Approved', "Request ID: $request_id", $request_id);
        
        // Notify all involved parties
        $notif_msg = "Request #$request_id has been approved";
        $result = $db->query("SELECT DISTINCT user_id FROM 
                             (SELECT created_by as user_id FROM requests WHERE id = $request_id
                              UNION 
                              SELECT uploaded_by as user_id FROM request_designs WHERE request_id = $request_id
                              UNION
                              SELECT approved_by as user_id FROM approvals WHERE request_id = $request_id) as users");
        while ($row = $result->fetch_assoc()) {
            if ($row['user_id'] != $user_id) {
                createNotification($row['user_id'], $request_id, 'Approved', $notif_msg);
            }
        }
        $stmt->close();
        
    } elseif ($action == 'close') {
        $stmt = $db->prepare("UPDATE requests SET status = 'Closed' WHERE id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $success = 'Request closed successfully!';
        logActivity($user_id, 'Request Closed', "Request ID: $request_id", $request_id);
        $stmt->close();
        
    } elseif ($action == 'return_to_design') {
        $remarks = sanitize($_POST['remarks']);
        $stmt = $db->prepare("UPDATE requests SET status = 'Returned to Design' WHERE id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $success = 'Request returned to design team!';
        logActivity($user_id, 'Request Returned to Design', "Remarks: $remarks", $request_id);
        
        // Notify Design team
        $design_users = $db->query("SELECT id FROM users WHERE role = 'Design' AND is_active = 1");
        while ($design_user = $design_users->fetch_assoc()) {
            createNotification($design_user['id'], $request_id, 'Returned for Changes', 
                "Request #$request_id returned for changes. Remarks: $remarks");
        }
        $stmt->close();
    }
}

// Fetch request details
$stmt = $db->prepare("SELECT r.* FROM requests r WHERE r.id = ? AND r.created_by = ?");
$stmt->bind_param("ii", $request_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();
$stmt->close();

if (!$request) {
    redirect('my_requests.php');
}

// Fetch designs
$designs = [];
$stmt = $db->prepare("SELECT rd.*, u.full_name as uploaded_by_name 
                      FROM request_designs rd 
                      JOIN users u ON rd.uploaded_by = u.id 
                      WHERE rd.request_id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $designs[] = $row;
}
$stmt->close();

// Fetch approval info
$approval = null;
$stmt = $db->prepare("SELECT a.*, u.full_name as approved_by_name 
                      FROM approvals a 
                      JOIN users u ON a.approved_by = u.id 
                      WHERE a.request_id = ? 
                      ORDER BY a.approved_at DESC LIMIT 1");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $approval = $result->fetch_assoc();
}
$stmt->close();

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-clipboard"></i> Request Details #<?php echo $request['id']; ?></h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="my_requests.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to My Requests
        </a>
    </div>
</div>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Request Information -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Request Information</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Event Name:</strong> <?php echo htmlspecialchars($request['event_name']); ?></p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($request['location']); ?></p>
                <p><strong>Event Date:</strong> <?php echo date('M d, Y', strtotime($request['event_date'])); ?></p>
                <p><strong>Event Time:</strong> <?php echo date('H:i', strtotime($request['event_time'])); ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Status:</strong> 
                    <span class="badge status-badge status-<?php echo strtolower(str_replace(' ', '-', $request['status'])); ?>">
                        <?php echo $request['status']; ?>
                    </span>
                </p>
                <p><strong>Created At:</strong> <?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?></p>
                <p><strong>Last Updated:</strong> <?php echo date('M d, Y H:i', strtotime($request['updated_at'])); ?></p>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <p><strong>Description:</strong></p>
                <p><?php echo nl2br(htmlspecialchars($request['description'])); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Design Images -->
<?php if (count($designs) > 0): ?>
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-images"></i> Design Images</h5>
    </div>
    <div class="card-body">
        <div class="image-gallery">
            <?php foreach ($designs as $design): ?>
            <div>
                <img src="../<?php echo htmlspecialchars($design['image_path']); ?>" 
                     alt="Design" 
                     onclick="viewImage('../<?php echo htmlspecialchars($design['image_path']); ?>')">
                <small class="d-block mt-1 text-muted">
                    By <?php echo htmlspecialchars($design['uploaded_by_name']); ?> 
                    on <?php echo date('M d, Y H:i', strtotime($design['uploaded_at'])); ?>
                </small>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Approval Information -->
<?php if ($approval): ?>
<div class="card mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-check-circle"></i> Approval Information</h5>
    </div>
    <div class="card-body">
        <p><strong>Pricing:</strong> $<?php echo number_format($approval['pricing'], 2); ?></p>
        <p><strong>Remarks:</strong> <?php echo nl2br(htmlspecialchars($approval['remarks'])); ?></p>
        <p><strong>Approved By:</strong> <?php echo htmlspecialchars($approval['approved_by_name']); ?></p>
        <p><strong>Approved At:</strong> <?php echo date('M d, Y H:i', strtotime($approval['approved_at'])); ?></p>
    </div>
</div>
<?php endif; ?>

<!-- Actions -->
<?php if ($request['status'] == 'Sent to Sales'): ?>
<div class="card">
    <div class="card-header bg-warning">
        <h5 class="mb-0"><i class="fas fa-tasks"></i> Take Action</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to approve this request?')">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-check"></i> Approve Request
                    </button>
                </form>
            </div>
            <div class="col-md-4">
                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to close this request?')">
                    <input type="hidden" name="action" value="close">
                    <button type="submit" class="btn btn-secondary w-100">
                        <i class="fas fa-times-circle"></i> Close Request
                    </button>
                </form>
            </div>
            <div class="col-md-4">
                <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#returnModal">
                    <i class="fas fa-undo"></i> Return to Design
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Return to Design Modal -->
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Return to Design Team</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="return_to_design">
                    <div class="mb-3">
                        <label class="form-label">Remarks <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="remarks" rows="4" 
                                  placeholder="Enter reasons for returning to design team" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Return to Design</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
