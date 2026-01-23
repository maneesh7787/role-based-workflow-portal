<?php
/**
 * Design - View Completed Design
 */
require_once '../config/config.php';

// Check if user is logged in and is in Design role
if (!isLoggedIn() || !hasRole('Design')) {
    redirect('../login.php');
}

$request_id = intval($_GET['id'] ?? 0);
if (!$request_id) {
    redirect('completed_designs.php');
}

$page_title = 'View Design';
$db = getDB();

// Fetch request details
$stmt = $db->prepare("SELECT r.*, u.full_name as created_by_name, u.email as created_by_email 
                      FROM requests r 
                      JOIN users u ON r.created_by = u.id 
                      WHERE r.id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();
$stmt->close();

if (!$request) {
    redirect('completed_designs.php');
}

// Fetch sales attachments
$attachments = [];
$stmt = $db->prepare("SELECT ra.*, u.full_name as uploaded_by_name 
                      FROM request_attachments ra 
                      JOIN users u ON ra.uploaded_by = u.id 
                      WHERE ra.request_id = ? 
                      ORDER BY ra.uploaded_at ASC");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $attachments[] = $row;
}
$stmt->close();

// Fetch designs
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

// Fetch approval info if exists
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
        <h2><i class="fas fa-eye"></i> Design Details #<?php echo $request['id']; ?></h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="completed_designs.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Completed Designs
        </a>
    </div>
</div>

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

<!-- Sales Attachments -->
<?php if (count($attachments) > 0): ?>
<div class="card mb-4">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0"><i class="fas fa-paperclip"></i> Sales Attachments (<?php echo count($attachments); ?>)</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <?php foreach ($attachments as $attachment): ?>
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <?php if (in_array(strtolower($attachment['file_type']), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                            <img src="../<?php echo htmlspecialchars($attachment['file_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($attachment['file_name']); ?>" 
                                 class="img-thumbnail mb-2" 
                                 style="max-height: 150px; cursor: pointer;"
                                 onclick="viewImage('../<?php echo htmlspecialchars($attachment['file_path']); ?>')">
                        <?php else: ?>
                            <i class="fas fa-file-pdf fa-5x text-danger mb-2"></i>
                        <?php endif; ?>
                        <p class="small mb-1"><strong><?php echo htmlspecialchars($attachment['file_name']); ?></strong></p>
                        <p class="small text-muted mb-2">
                            Uploaded <?php echo date('M d, Y', strtotime($attachment['uploaded_at'])); ?>
                        </p>
                        <a href="../<?php echo htmlspecialchars($attachment['file_path']); ?>" 
                           class="btn btn-sm btn-primary" download>
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
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-images"></i> Design Images (<?php echo count($designs); ?>)</h5>
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

<?php require_once '../includes/footer.php'; ?>
