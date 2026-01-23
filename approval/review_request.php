<?php
/**
 * Approval - Review Request
 */
require_once '../config/config.php';

// Check if user is logged in and is in Approval role
if (!isLoggedIn() || !hasRole('Approval')) {
    redirect('../login.php');
}

$request_id = intval($_GET['id'] ?? 0);
if (!$request_id) {
    redirect('pending_requests.php');
}

$page_title = 'Review Request';
$db = getDB();
$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle approval submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pricing = floatval($_POST['pricing']);
    $remarks = sanitize($_POST['remarks']);
    
    if ($pricing <= 0) {
        $error = 'Please enter a valid pricing amount.';
    } else {
        // Insert approval record
        $stmt = $db->prepare("INSERT INTO approvals (request_id, pricing, remarks, approved_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("idsi", $request_id, $pricing, $remarks, $user_id);
        
        if ($stmt->execute()) {
            // Update request status
            $stmt2 = $db->prepare("UPDATE requests SET status = 'Sent to Sales' WHERE id = ?");
            $stmt2->bind_param("i", $request_id);
            $stmt2->execute();
            $stmt2->close();
            
            $success = 'Request approved and sent to sales!';
            logActivity($user_id, 'Request Approved', "Pricing: $$pricing", $request_id);
            
            // Notify sales person who created the request
            $stmt3 = $db->prepare("SELECT u.id, u.email, r.event_name FROM requests r JOIN users u ON r.created_by = u.id WHERE r.id = ?");
            $stmt3->bind_param("i", $request_id);
            $stmt3->execute();
            $sales_user = $stmt3->get_result()->fetch_assoc();
            $stmt3->close();
            
            if ($sales_user) {
                createNotification($sales_user['id'], $request_id, 'Approval Completed', 
                    "Your request #$request_id has been approved with pricing: $$pricing");
                
                // Send email notification
                $subject = "Request Approved - " . $sales_user['event_name'];
                $message = "Your design request has been approved.<br><br>
                            Request ID: #$request_id<br>
                            Pricing: $$pricing<br>
                            Remarks: $remarks<br><br>
                            Please login to the portal to review and take action.";
                sendEmailNotification($sales_user['email'], $subject, $message);
            }
            
            // Redirect to pending requests
            header("Location: pending_requests.php");
            exit();
        } else {
            $error = 'Error saving approval. Please try again.';
        }
        $stmt->close();
    }
}

// Fetch request details
$stmt = $db->prepare("SELECT r.*, u.full_name as created_by_name 
                      FROM requests r 
                      JOIN users u ON r.created_by = u.id 
                      WHERE r.id = ? AND r.status = 'Pending Approval'");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();
$stmt->close();

if (!$request) {
    redirect('pending_requests.php');
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

// Fetch sales attachments
$attachments = [];
$stmt = $db->prepare("SELECT * FROM request_attachments WHERE request_id = ? ORDER BY uploaded_at DESC");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $attachments[] = $row;
}
$stmt->close();

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-check-circle"></i> Review Request #<?php echo $request['id']; ?></h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="pending_requests.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Pending Requests
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
                <p><strong>Created By:</strong> <?php echo htmlspecialchars($request['created_by_name']); ?></p>
                <p><strong>Created At:</strong> <?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?></p>
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

<!-- Sales Attachments -->
<?php if (count($attachments) > 0): ?>
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-paperclip"></i> Sales Attachments (<?php echo count($attachments); ?>)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>File Name</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attachments as $attachment): ?>
                    <tr>
                        <td>
                            <i class="fas fa-<?php echo $attachment['file_type'] == 'pdf' ? 'file-pdf' : 'image'; ?>"></i>
                            <?php echo htmlspecialchars($attachment['file_name']); ?>
                        </td>
                        <td>
                            <span class="badge bg-secondary"><?php echo strtoupper($attachment['file_type']); ?></span>
                        </td>
                        <td>
                            <a href="../<?php echo $attachment['file_path']; ?>" target="_blank" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

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

<!-- Approval Form -->
<div class="card">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-clipboard-check"></i> Submit Approval</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="pricing" class="form-label">
                            Pricing (USD) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="pricing" name="pricing" 
                                   step="0.01" min="0.01" placeholder="0.00" required>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="remarks" class="form-label">Remarks</label>
                <textarea class="form-control" id="remarks" name="remarks" rows="4" 
                          placeholder="Enter any remarks or notes about this approval"></textarea>
            </div>
            
            <div class="text-end">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-check"></i> Approve & Send to Sales
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
