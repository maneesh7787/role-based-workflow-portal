<?php
/**
 * Sales - Create New Request
 */
require_once '../config/config.php';

// Check if user is logged in and is in Sales role
if (!isLoggedIn() || !hasRole('Sales')) {
    redirect('../login.php');
}

$page_title = 'Create New Request';
$db = getDB();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_name = sanitize($_POST['event_name']);
    $location = sanitize($_POST['location']);
    $event_date = sanitize($_POST['event_date']);
    $event_time = sanitize($_POST['event_time']);
    $description = sanitize($_POST['description']);
    
    if (empty($event_name) || empty($location) || empty($event_date) || empty($event_time)) {
        $error = 'Please fill in all required fields.';
    } else {
        $user_id = $_SESSION['user_id'];
        $status = 'Pending Design';
        
        $stmt = $db->prepare("INSERT INTO requests (event_name, location, event_date, event_time, description, status, created_by) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssi", $event_name, $location, $event_date, $event_time, $description, $status, $user_id);
        
        if ($stmt->execute()) {
            $request_id = $stmt->insert_id;
            $success = 'Request created successfully!';
            
            // Log activity
            logActivity($user_id, 'Request Created', "Created request: $event_name", $request_id);
            
            // Notify Design team
            $design_users = $db->query("SELECT id, email FROM users WHERE role = 'Design' AND is_active = 1");
            while ($design_user = $design_users->fetch_assoc()) {
                createNotification($design_user['id'], $request_id, 'New Request', 
                    "New design request created: $event_name");
                
                // Send email notification
                $subject = "New Design Request - $event_name";
                $message = "A new design request has been created and is waiting for your action.<br><br>
                            Event: $event_name<br>
                            Location: $location<br>
                            Date: $event_date<br>
                            Please login to the portal to view details.";
                sendEmailNotification($design_user['email'], $subject, $message);
            }
            
            // Redirect to view request
            header("Location: my_requests.php");
            exit();
        } else {
            $error = 'Error creating request. Please try again.';
        }
        $stmt->close();
    }
}

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-plus-circle"></i> Create New Request</h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="my_requests.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> My Requests
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

<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="event_name" class="form-label">Event Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="event_name" name="event_name" 
                               placeholder="Enter event name" required>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="location" name="location" 
                               placeholder="Enter location" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="event_date" class="form-label">Event Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="event_date" name="event_date" required>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="event_time" class="form-label">Event Time <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" id="event_time" name="event_time" required>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4" 
                          placeholder="Enter event description and requirements"></textarea>
            </div>
            
            <div class="text-end">
                <button type="reset" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Reset
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Request
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
