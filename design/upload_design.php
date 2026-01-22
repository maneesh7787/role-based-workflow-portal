<?php
/**
 * Design - Upload Design Images
 */
require_once '../config/config.php';

// Check if user is logged in and is in Design role
if (!isLoggedIn() || !hasRole('Design')) {
    redirect('../login.php');
}

$request_id = intval($_GET['id'] ?? 0);
if (!$request_id) {
    redirect('pending_requests.php');
}

$page_title = 'Upload Design';
$db = getDB();
$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['design_images'])) {
    $files = $_FILES['design_images'];
    $uploaded_count = 0;
    
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
            
            // Validate file
            if (!in_array($file_ext, ALLOWED_EXTENSIONS)) {
                $error = "Invalid file type for $file_name. Only JPG, JPEG, PNG, and GIF are allowed.";
                continue;
            }
            
            if ($file_size > MAX_FILE_SIZE) {
                $error = "File $file_name is too large. Maximum size is 5MB.";
                continue;
            }
            
            // Generate unique filename with secure random component
            $random_string = bin2hex(random_bytes(16));
            $new_file_name = 'design_' . $request_id . '_' . $random_string . '.' . $file_ext;
            $destination = UPLOAD_DIR . $new_file_name;
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp, $destination)) {
                $image_path = 'uploads/' . $new_file_name;
                
                // Save to database
                $stmt = $db->prepare("INSERT INTO request_designs (request_id, image_path, uploaded_by) VALUES (?, ?, ?)");
                $stmt->bind_param("isi", $request_id, $image_path, $user_id);
                $stmt->execute();
                $stmt->close();
                
                $uploaded_count++;
            }
        }
    }
    
    if ($uploaded_count > 0) {
        // Update request status to Pending Approval
        $stmt = $db->prepare("UPDATE requests SET status = 'Pending Approval' WHERE id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $stmt->close();
        
        $success = "$uploaded_count design image(s) uploaded successfully!";
        logActivity($user_id, 'Design Uploaded', "Uploaded $uploaded_count images for request", $request_id);
        
        // Notify Approval team
        $approval_users = $db->query("SELECT id, email FROM users WHERE role = 'Approval' AND is_active = 1");
        while ($approval_user = $approval_users->fetch_assoc()) {
            createNotification($approval_user['id'], $request_id, 'Design Uploaded', 
                "Design images uploaded for request #$request_id. Ready for approval.");
            
            // Send email notification
            $subject = "Design Ready for Approval - Request #$request_id";
            $message = "Design images have been uploaded and are ready for your approval.<br><br>
                        Request ID: #$request_id<br>
                        Please login to the portal to review and approve.";
            sendEmailNotification($approval_user['email'], $subject, $message);
        }
        
        // Also notify the sales person who created the request
        $stmt = $db->prepare("SELECT u.id, u.email FROM requests r JOIN users u ON r.created_by = u.id WHERE r.id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $sales_user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($sales_user) {
            createNotification($sales_user['id'], $request_id, 'Design Uploaded', 
                "Design images have been uploaded for your request #$request_id");
        }
    }
}

// Fetch request details
$stmt = $db->prepare("SELECT r.*, u.full_name as created_by_name 
                      FROM requests r 
                      JOIN users u ON r.created_by = u.id 
                      WHERE r.id = ? AND r.status IN ('Pending Design', 'Returned to Design')");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();
$stmt->close();

if (!$request) {
    redirect('pending_requests.php');
}

// Fetch existing designs
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

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-upload"></i> Upload Design for Request #<?php echo $request['id']; ?></h2>
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

<!-- Existing Designs -->
<?php if (count($designs) > 0): ?>
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-images"></i> Previously Uploaded Designs</h5>
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

<!-- Upload Form -->
<div class="card">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-cloud-upload-alt"></i> Upload Design Images</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="design_images" class="form-label">
                    Select Design Images <span class="text-danger">*</span>
                </label>
                <input type="file" class="form-control" id="design_images" name="design_images[]" 
                       accept="image/*" multiple required data-preview="image-preview">
                <small class="text-muted">
                    Allowed formats: JPG, JPEG, PNG, GIF. Maximum size: 5MB per file. You can select multiple files.
                </small>
            </div>
            
            <div id="image-preview" class="row mb-3"></div>
            
            <div class="text-end">
                <button type="reset" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Reset
                </button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-upload"></i> Upload Designs
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
