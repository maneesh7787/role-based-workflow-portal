<?php
/**
 * Sales - Edit Request and Add More Attachments
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

$page_title = 'Edit Request';
$db = getDB();
$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_name = sanitize($_POST['event_name']);
    $location = sanitize($_POST['location']);
    $event_date = sanitize($_POST['event_date']);
    $event_time = sanitize($_POST['event_time']);
    $description = sanitize($_POST['description']);
    
    if (empty($event_name) || empty($location) || empty($event_date) || empty($event_time)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Update request details
        $stmt = $db->prepare("UPDATE requests SET event_name = ?, location = ?, event_date = ?, event_time = ?, description = ? WHERE id = ? AND created_by = ?");
        $stmt->bind_param("sssssii", $event_name, $location, $event_date, $event_time, $description, $request_id, $user_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Handle new file uploads if any
            $uploaded_count = 0;
            if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
                $files = $_FILES['attachments'];
                
                // Check if upload directory exists
                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0755, true);
                }
                
                // Process each uploaded file
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $file_name = $files['name'][$i];
                        $file_tmp = $files['tmp_name'][$i];
                        $file_size = $files['size'][$i];
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        
                        // Validate file - allow images and PDFs
                        $allowed_exts = array_merge(ALLOWED_EXTENSIONS, ['pdf']);
                        if (!in_array($file_ext, $allowed_exts)) {
                            $error = "Invalid file type for $file_name. Only JPG, JPEG, PNG, GIF, and PDF are allowed.";
                            continue;
                        }
                        
                        if ($file_size > MAX_FILE_SIZE) {
                            $error = "File $file_name is too large. Maximum size is 5MB.";
                            continue;
                        }
                        
                        // Generate unique filename
                        $random_string = bin2hex(random_bytes(16));
                        $new_file_name = 'attachment_' . $request_id . '_' . $random_string . '.' . $file_ext;
                        $destination = UPLOAD_DIR . $new_file_name;
                        
                        // Move uploaded file
                        if (move_uploaded_file($file_tmp, $destination)) {
                            $file_path = 'uploads/' . $new_file_name;
                            
                            // Save to database
                            $stmt2 = $db->prepare("INSERT INTO request_attachments (request_id, file_path, file_name, file_type, uploaded_by) VALUES (?, ?, ?, ?, ?)");
                            $stmt2->bind_param("isssi", $request_id, $file_path, $file_name, $file_ext, $user_id);
                            $stmt2->execute();
                            $stmt2->close();
                            
                            $uploaded_count++;
                        }
                    }
                }
            }
            
            $success = 'Request updated successfully!' . ($uploaded_count > 0 ? " $uploaded_count new file(s) uploaded." : '');
            
            // Log activity
            logActivity($user_id, 'Request Updated', "Updated request: $event_name" . ($uploaded_count > 0 ? " and added $uploaded_count attachment(s)" : ''), $request_id);
            
            // Notify relevant teams based on current status
            $stmt3 = $db->prepare("SELECT status FROM requests WHERE id = ?");
            $stmt3->bind_param("i", $request_id);
            $stmt3->execute();
            $result3 = $stmt3->get_result();
            $request_data = $result3->fetch_assoc();
            $current_status = $request_data['status'];
            $stmt3->close();
            
            // Notify design team if status is pending design or returned to design
            if (in_array($current_status, ['Pending Design', 'Returned to Design'])) {
                $design_users = $db->query("SELECT id FROM users WHERE role = 'Design' AND is_active = 1");
                while ($design_user = $design_users->fetch_assoc()) {
                    createNotification($design_user['id'], $request_id, 'New Request', 
                        "Request #$request_id has been updated: $event_name");
                }
            }
            
        } else {
            $error = 'Error updating request. Please try again.';
            $stmt->close();
        }
    }
}

// Handle attachment deletion
if (isset($_GET['delete_attachment'])) {
    $attachment_id = intval($_GET['delete_attachment']);
    
    // Get file path before deleting
    $stmt = $db->prepare("SELECT file_path FROM request_attachments WHERE id = ? AND request_id = ? AND uploaded_by = ?");
    $stmt->bind_param("iii", $attachment_id, $request_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $file_path = '../' . $row['file_path'];
        
        // Delete from database
        $stmt2 = $db->prepare("DELETE FROM request_attachments WHERE id = ?");
        $stmt2->bind_param("i", $attachment_id);
        $stmt2->execute();
        $stmt2->close();
        
        // Delete physical file
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        $success = 'Attachment deleted successfully!';
        logActivity($user_id, 'Attachment Deleted', "Deleted attachment from request", $request_id);
    }
    $stmt->close();
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

// Fetch attachments
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
        <h2><i class="fas fa-edit"></i> Edit Request #<?php echo $request_id; ?></h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="view_request.php?id=<?php echo $request_id; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Request
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

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title mb-3">Request Details</h5>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="event_name" class="form-label">Event Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="event_name" name="event_name" 
                               value="<?php echo htmlspecialchars($request['event_name']); ?>" required>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="location" name="location" 
                               value="<?php echo htmlspecialchars($request['location']); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="event_date" class="form-label">Event Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="event_date" name="event_date" 
                               value="<?php echo $request['event_date']; ?>" required>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="event_time" class="form-label">Event Time <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" id="event_time" name="event_time" 
                               value="<?php echo $request['event_time']; ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($request['description']); ?></textarea>
            </div>
            
            <div class="mb-3">
                <label for="attachments" class="form-label">
                    Add More Attachments (Optional)
                    <small class="text-muted">- Images (JPG, PNG, GIF) or PDF files, max 5MB each</small>
                </label>
                <input type="file" class="form-control" id="attachments" name="attachments[]" 
                       multiple accept=".jpg,.jpeg,.png,.gif,.pdf">
                <small class="text-muted">You can select multiple files</small>
            </div>
            
            <div class="text-end">
                <a href="view_request.php?id=<?php echo $request_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Request
                </button>
            </div>
        </form>
    </div>
</div>

<?php if (count($attachments) > 0): ?>
<div class="card">
    <div class="card-body">
        <h5 class="card-title mb-3">Current Attachments (<?php echo count($attachments); ?>)</h5>
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
                            <a href="?id=<?php echo $request_id; ?>&delete_attachment=<?php echo $attachment['id']; ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('Are you sure you want to delete this attachment?')">
                                <i class="fas fa-trash"></i> Delete
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

<?php require_once '../includes/footer.php'; ?>
