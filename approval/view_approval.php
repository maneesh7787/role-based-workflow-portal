<?php
/**
 * Approval - View Approval Details
 */
require_once '../config/config.php';

// Check if user is logged in and is in Approval role
if (!isLoggedIn() || !hasRole('Approval')) {
    redirect('../login.php');
}

$request_id = intval($_GET['id'] ?? 0);
if (!$request_id) {
    redirect('completed_approvals.php');
}

$page_title = 'View Approval Details';
$db = getDB();

// Fetch request details
$stmt = $db->prepare("SELECT r.*, u.full_name as created_by_name, u.email as created_by_email 
                      FROM requests r 
                      JOIN users u ON r.created_by = u.id 
                      WHERE r.id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$request) {
    redirect('completed_approvals.php');
}

// Fetch sales attachments
$sales_attachments = [];
$stmt = $db->prepare("SELECT ra.*, u.full_name as uploaded_by_name 
                      FROM request_attachments ra 
                      JOIN users u ON ra.uploaded_by = u.id 
                      WHERE ra.request_id = ? 
                      ORDER BY ra.uploaded_at ASC");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $sales_attachments[] = $row;
}
$stmt->close();

// Fetch design images
$designs = [];
$stmt = $db->prepare("SELECT rd.*, u.full_name as uploaded_by_name 
                      FROM request_designs rd 
                      JOIN users u ON rd.uploaded_by = u.id 
                      WHERE rd.request_id = ? 
                      ORDER BY rd.uploaded_at ASC");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $designs[] = $row;
}
$stmt->close();

// Fetch approval data if exists
$approval = null;
$stmt = $db->prepare("SELECT a.*, u.full_name as approved_by_name 
                      FROM approvals a 
                      JOIN users u ON a.approved_by = u.id 
                      WHERE a.request_id = ?");
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
        <h2><i class="fas fa-eye"></i> Approval Details - Request #<?php echo $request['id']; ?></h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="completed_approvals.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<!-- Request Information -->
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fas fa-info-circle"></i> Request Information</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr>
                        <th width="150">Request ID:</th>
                        <td>#<?php echo $request['id']; ?></td>
                    </tr>
                    <tr>
                        <th>Event Name:</th>
                        <td><?php echo htmlspecialchars($request['event_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Location:</th>
                        <td><?php echo htmlspecialchars($request['location']); ?></td>
                    </tr>
                    <tr>
                        <th>Event Date:</th>
                        <td><?php echo date('F d, Y', strtotime($request['event_date'])); ?></td>
                    </tr>
                    <tr>
                        <th>Event Time:</th>
                        <td><?php echo date('h:i A', strtotime($request['event_time'])); ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr>
                        <th width="150">Status:</th>
                        <td>
                            <span class="badge status-badge status-<?php echo strtolower(str_replace(' ', '-', $request['status'])); ?>">
                                <?php echo $request['status']; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Created By:</th>
                        <td><?php echo htmlspecialchars($request['created_by_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Created At:</th>
                        <td><?php echo date('F d, Y h:i A', strtotime($request['created_at'])); ?></td>
                    </tr>
                    <tr>
                        <th>Last Updated:</th>
                        <td><?php echo date('F d, Y h:i A', strtotime($request['updated_at'])); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-12">
                <h6>Description:</h6>
                <p class="text-muted"><?php echo nl2br(htmlspecialchars($request['description'])); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Sales Attachments -->
<?php if (count($sales_attachments) > 0): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fas fa-paperclip"></i> Sales Attachments</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <?php foreach ($sales_attachments as $attachment): ?>
            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <?php if (in_array(strtolower(pathinfo($attachment['file_name'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                            <img src="<?php echo BASE_URL; ?>/<?php echo $attachment['file_path']; ?>" 
                                 class="img-fluid mb-2" 
                                 style="max-height: 150px; cursor: pointer;"
                                 onclick="window.open('<?php echo BASE_URL; ?>/<?php echo $attachment['file_path']; ?>', '_blank')">
                        <?php else: ?>
                            <i class="fas fa-file-pdf fa-4x text-danger mb-2"></i>
                        <?php endif; ?>
                        <p class="small mb-1"><strong><?php echo htmlspecialchars($attachment['file_name']); ?></strong></p>
                        <p class="small text-muted mb-2">
                            By: <?php echo htmlspecialchars($attachment['uploaded_by_name']); ?><br>
                            <?php echo date('M d, Y H:i', strtotime($attachment['uploaded_at'])); ?>
                        </p>
                        <a href="<?php echo BASE_URL; ?>/<?php echo $attachment['file_path']; ?>" 
                           target="_blank" 
                           class="btn btn-sm btn-primary" 
                           download>
                            <i class="fas fa-download"></i> Download
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Design Images -->
<?php if (count($designs) > 0): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fas fa-images"></i> Design Images</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <?php foreach ($designs as $design): ?>
            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <img src="<?php echo BASE_URL; ?>/<?php echo $design['image_path']; ?>" 
                         class="card-img-top" 
                         style="height: 200px; object-fit: cover; cursor: pointer;"
                         onclick="window.open('<?php echo BASE_URL; ?>/<?php echo $design['image_path']; ?>', '_blank')">
                    <div class="card-body text-center p-2">
                        <small class="text-muted">
                            Uploaded by: <?php echo htmlspecialchars($design['uploaded_by_name']); ?><br>
                            <?php echo date('M d, Y H:i', strtotime($design['uploaded_at'])); ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Approval Information -->
<?php if ($approval): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fas fa-check-circle"></i> Approval Information</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr>
                        <th width="150">Pricing:</th>
                        <td><strong class="text-success">$<?php echo number_format($approval['pricing'], 2); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Approved By:</th>
                        <td><?php echo htmlspecialchars($approval['approved_by_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Approved At:</th>
                        <td><?php echo date('F d, Y h:i A', strtotime($approval['approved_at'])); ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Remarks:</h6>
                <p class="text-muted"><?php echo nl2br(htmlspecialchars($approval['remarks'])); ?></p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
