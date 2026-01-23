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
        
        // Notify all involved parties using prepared statements
        $stmt2 = $db->prepare("SELECT DISTINCT created_by as user_id FROM requests WHERE id = ?
                              UNION 
                              SELECT DISTINCT uploaded_by as user_id FROM request_designs WHERE request_id = ?
                              UNION
                              SELECT DISTINCT approved_by as user_id FROM approvals WHERE request_id = ?");
        $stmt2->bind_param("iii", $request_id, $request_id, $request_id);
        $stmt2->execute();
        $result = $stmt2->get_result();
        while ($row = $result->fetch_assoc()) {
            if ($row['user_id'] != $user_id) {
                createNotification($row['user_id'], $request_id, 'Approved', $notif_msg);
            }
        }
        $stmt2->close();
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
        
        // Handle file uploads for client updates
        $upload_errors = [];
        $uploaded_files = [];
        
        if (isset($_FILES['client_update_files']) && !empty($_FILES['client_update_files']['name'][0])) {
            $upload_dir = '../uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            foreach ($_FILES['client_update_files']['name'] as $key => $filename) {
                if ($_FILES['client_update_files']['error'][$key] == 0) {
                    $file_tmp = $_FILES['client_update_files']['tmp_name'][$key];
                    $file_type = $_FILES['client_update_files']['type'][$key];
                    $file_size = $_FILES['client_update_files']['size'][$key];
                    
                    // Validate file type
                    if (!in_array($file_type, $allowed_types)) {
                        $upload_errors[] = "Invalid file type for: $filename";
                        continue;
                    }
                    
                    // Validate file size
                    if ($file_size > $max_size) {
                        $upload_errors[] = "File too large: $filename (max 5MB)";
                        continue;
                    }
                    
                    // Generate secure random filename
                    $extension = pathinfo($filename, PATHINFO_EXTENSION);
                    $secure_filename = bin2hex(random_bytes(16)) . '.' . $extension;
                    $file_path = $upload_dir . $secure_filename;
                    
                    if (move_uploaded_file($file_tmp, $file_path)) {
                        // Save to database as client update
                        $db_file_path = 'uploads/' . $secure_filename;
                        $file_type_db = (strtolower($extension) == 'pdf') ? 'pdf' : 'image';
                        
                        $stmt = $db->prepare("INSERT INTO request_attachments (request_id, file_path, file_name, file_type, uploaded_by, is_client_update, client_update_remarks) 
                                             VALUES (?, ?, ?, ?, ?, 1, ?)");
                        $stmt->bind_param("issis", $request_id, $db_file_path, $filename, $file_type_db, $user_id, $remarks);
                        $stmt->execute();
                        $stmt->close();
                        
                        $uploaded_files[] = $filename;
                    }
                }
            }
        }
        
        $stmt = $db->prepare("UPDATE requests SET status = 'Returned to Design' WHERE id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        
        $files_msg = count($uploaded_files) > 0 ? " with " . count($uploaded_files) . " client update file(s)" : "";
        $success = 'Request returned to design team' . $files_msg . '!';
        
        $log_details = "Remarks: $remarks";
        if (count($uploaded_files) > 0) {
            $log_details .= " | Files: " . implode(', ', $uploaded_files);
        }
        logActivity($user_id, 'Request Returned to Design', $log_details, $request_id);
        
        // Notify Design team
        $notif_message = "Request #$request_id returned for changes. Remarks: $remarks";
        if (count($uploaded_files) > 0) {
            $notif_message .= " | New client update files attached (" . count($uploaded_files) . ")";
        }
        
        $design_users = $db->query("SELECT id FROM users WHERE role = 'Design' AND is_active = 1");
        while ($design_user = $design_users->fetch_assoc()) {
            createNotification($design_user['id'], $request_id, 'Returned for Changes', $notif_message);
        }
        $stmt->close();
        
        if (count($upload_errors) > 0) {
            $error = implode('<br>', $upload_errors);
        }
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

// Fetch attachments - separate regular and client updates
$attachments = [];
$client_updates = [];
$stmt = $db->prepare("SELECT ra.*, u.full_name as uploaded_by_name 
                      FROM request_attachments ra 
                      JOIN users u ON ra.uploaded_by = u.id 
                      WHERE ra.request_id = ? 
                      ORDER BY ra.is_client_update ASC, ra.uploaded_at DESC");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if ($row['is_client_update']) {
        $client_updates[] = $row;
    } else {
        $attachments[] = $row;
    }
}
$stmt->close();

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-clipboard"></i> Request Details #<?php echo $request['id']; ?></h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="edit_request.php?id=<?php echo $request['id']; ?>" class="btn btn-info">
            <i class="fas fa-edit"></i> Edit Request
        </a>
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

<!-- Client Updates from Sales -->
<?php if (count($client_updates) > 0): ?>
<div class="card mb-4 border-warning">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Client Update Files (<?php echo count($client_updates); ?>)</h5>
        <small>Files added when request was returned to design with new client requirements</small>
    </div>
    <div class="card-body">
        <?php 
        // Group by remarks to show updates together
        $updates_by_remarks = [];
        foreach ($client_updates as $update) {
            $key = $update['client_update_remarks'] ?: 'No remarks';
            if (!isset($updates_by_remarks[$key])) {
                $updates_by_remarks[$key] = [
                    'remarks' => $update['client_update_remarks'],
                    'uploaded_at' => $update['uploaded_at'],
                    'uploaded_by_name' => $update['uploaded_by_name'],
                    'files' => []
                ];
            }
            $updates_by_remarks[$key]['files'][] = $update;
        }
        
        foreach ($updates_by_remarks as $update_group): ?>
        <div class="alert alert-warning mb-3">
            <div class="mb-2">
                <strong><i class="fas fa-user"></i> <?php echo htmlspecialchars($update_group['uploaded_by_name']); ?></strong>
                <small class="text-muted">- <?php echo date('M d, Y H:i', strtotime($update_group['uploaded_at'])); ?></small>
            </div>
            <?php if ($update_group['remarks']): ?>
            <div class="mb-2">
                <strong>Client Requirements:</strong><br>
                <?php echo nl2br(htmlspecialchars($update_group['remarks'])); ?>
            </div>
            <?php endif; ?>
            <div class="row">
                <?php foreach ($update_group['files'] as $file): ?>
                <div class="col-md-3 mb-2">
                    <div class="card">
                        <div class="card-body text-center p-2">
                            <?php if ($file['file_type'] == 'image'): ?>
                                <img src="../<?php echo htmlspecialchars($file['file_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($file['file_name']); ?>" 
                                     class="img-thumbnail mb-1" 
                                     style="max-height: 100px; cursor: pointer;"
                                     onclick="viewImage('../<?php echo htmlspecialchars($file['file_path']); ?>')">
                            <?php else: ?>
                                <i class="fas fa-file-pdf fa-3x text-danger mb-1"></i>
                            <?php endif; ?>
                            <p class="small mb-1"><?php echo htmlspecialchars($file['file_name']); ?></p>
                            <a href="../<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" class="btn btn-xs btn-info">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Sales Attachments -->
<?php if (count($attachments) > 0): ?>
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-paperclip"></i> Request Attachments (<?php echo count($attachments); ?>)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>File Name</th>
                        <th>Type</th>
                        <th>Uploaded</th>
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
                        <td><?php echo date('M d, Y H:i', strtotime($attachment['uploaded_at'])); ?></td>
                        <td>
                            <a href="../<?php echo $attachment['file_path']; ?>" target="_blank" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="../<?php echo $attachment['file_path']; ?>" download class="btn btn-sm btn-success">
                                <i class="fas fa-download"></i> Download
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Return to Design Team</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="return_to_design">
                    <div class="mb-3">
                        <label class="form-label">Remarks / Client Updates <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="remarks" rows="4" 
                                  placeholder="Enter reasons for returning to design team or new client requirements" required></textarea>
                        <small class="text-muted">Explain what changes are needed or provide new client requirements</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Attach Client Update Files (Optional)</label>
                        <input type="file" class="form-control" name="client_update_files[]" 
                               accept=".jpg,.jpeg,.png,.gif,.pdf" multiple>
                        <small class="text-muted">
                            Upload new client requirements, reference images, or documents (JPG, PNG, GIF, PDF - max 5MB each).
                            These will be tagged as "New Updates from Client" and shown to the design team.
                        </small>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Note:</strong> Uploaded files will be marked as client updates and displayed separately to help the design team identify new requirements.
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
