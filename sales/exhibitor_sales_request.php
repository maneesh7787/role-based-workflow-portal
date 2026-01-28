<?php
/**
 * Sales - Exhibitor Sales Request Form
 * 
 * This page allows sales users to create exhibitor booth requests
 * with comprehensive details including booth specifications, design requirements,
 * and delivery priorities.
 */
require_once '../config/config.php';

// Check if user is logged in and is in Sales role
if (!isLoggedIn() || !hasRole('Sales')) {
    redirect('../login.php');
}

$page_title = 'Exhibitor Sales Request';
$db = getDB();
$success = '';
$error = '';
$form_data = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
    $requirement_type = isset($_POST['requirement_type']) ? sanitize($_POST['requirement_type']) : '';
    $client_name = sanitize($_POST['client_name']);
    $client_company = sanitize($_POST['client_company']);
    $client_email = sanitize($_POST['client_email']);
    $client_phone = sanitize($_POST['client_phone']);
    $event_name = sanitize($_POST['event_name']);
    $event_date = sanitize($_POST['event_date']);
    $event_location = sanitize($_POST['event_location']);
    $booth_size = sanitize($_POST['booth_size']);
    $booth_type = isset($_POST['booth_type']) ? sanitize($_POST['booth_type']) : '';
    $booth_number = sanitize($_POST['booth_number']);
    $delivery_priority = isset($_POST['delivery_priority']) ? sanitize($_POST['delivery_priority']) : '';
    $special_requests = sanitize($_POST['special_requests']);
    
    // Get design requirements checkboxes
    $design_requirements = isset($_POST['design_requirements']) ? $_POST['design_requirements'] : [];
    $design_requirements_json = json_encode(array_map('sanitize', $design_requirements));
    
    // Store form data for repopulation on error
    $form_data = $_POST;
    
    // Validation
    $validation_errors = [];
    
    if (empty($requirement_type)) {
        $validation_errors[] = 'Please select a requirement type.';
    }
    
    if (empty($client_name)) {
        $validation_errors[] = 'Client name is required.';
    }
    
    if (empty($client_email)) {
        $validation_errors[] = 'Client email is required.';
    } elseif (!filter_var($client_email, FILTER_VALIDATE_EMAIL)) {
        $validation_errors[] = 'Please enter a valid email address.';
    }
    
    if (empty($client_phone)) {
        $validation_errors[] = 'Client phone is required.';
    } elseif (!preg_match('/^\(?([0-9]{3})\)?[-.\s]?([0-9]{3})[-.\s]?([0-9]{4})$/', $client_phone)) {
        $validation_errors[] = 'Please enter a valid phone number (format: 123-456-7890).';
    }
    
    if (empty($event_name)) {
        $validation_errors[] = 'Event name is required.';
    }
    
    if (empty($event_date)) {
        $validation_errors[] = 'Event date is required.';
    } else {
        $event_date_obj = DateTime::createFromFormat('Y-m-d', $event_date);
        if (!$event_date_obj || $event_date_obj->format('Y-m-d') !== $event_date) {
            $validation_errors[] = 'Please enter a valid event date.';
        }
    }
    
    if (empty($event_location)) {
        $validation_errors[] = 'Event location is required.';
    }
    
    if (empty($booth_type)) {
        $validation_errors[] = 'Please select a booth type.';
    }
    
    if (empty($delivery_priority)) {
        $validation_errors[] = 'Please select a delivery priority.';
    }
    
    // If validation passes, insert into database
    if (empty($validation_errors)) {
        $user_id = $_SESSION['user_id'];
        $status = 'Pending';
        
        $stmt = $db->prepare("INSERT INTO exhibitor_requests 
            (requirement_type, client_name, client_company, client_email, client_phone, 
             event_name, event_date, event_location, booth_size, booth_type, booth_number, 
             design_requirements, delivery_priority, special_requests, status, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("sssssssssssssssi", 
            $requirement_type, $client_name, $client_company, $client_email, $client_phone,
            $event_name, $event_date, $event_location, $booth_size, $booth_type, $booth_number,
            $design_requirements_json, $delivery_priority, $special_requests, $status, $user_id
        );
        
        if ($stmt->execute()) {
            $request_id = $stmt->insert_id;
            $stmt->close();
            
            $success = 'Exhibitor sales request created successfully! Request ID: ' . $request_id;
            
            // Log activity
            logActivity($user_id, 'Exhibitor Request Created', 
                "Created exhibitor request for: $client_name - $event_name", null);
            
            // Clear form data on success
            $form_data = [];
            
            // Optionally redirect after a short delay
            // header("Refresh: 3; URL=my_requests.php");
        } else {
            $error = 'Error creating request: ' . $db->error;
            $stmt->close();
        }
    } else {
        $error = implode('<br>', $validation_errors);
    }
}

require_once '../includes/header.php';
?>

<style>
/* Additional form styling */
.form-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.form-section-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #0d6efd;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #0d6efd;
}

.checkbox-group {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px;
}

.form-check {
    padding-left: 1.5em;
}

.required-field::after {
    content: ' *';
    color: #dc3545;
}

.phone-format-hint {
    font-size: 0.875rem;
    color: #6c757d;
}
</style>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-clipboard-list"></i> Exhibitor Sales Request Form</h2>
        <p class="text-muted">Create a new exhibitor booth request with detailed specifications</p>
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
    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="" id="exhibitorRequestForm" novalidate>
            
            <!-- Requirement Type Section -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-tasks"></i> Requirement Type
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label class="form-label required-field">Select Requirement Type</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="requirement_type" 
                                           id="req_new_booth" value="new_booth" required
                                           <?php echo (isset($form_data['requirement_type']) && $form_data['requirement_type'] === 'new_booth') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="req_new_booth">
                                        <i class="fas fa-plus-square"></i> New Booth
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="requirement_type" 
                                           id="req_renovation" value="booth_renovation"
                                           <?php echo (isset($form_data['requirement_type']) && $form_data['requirement_type'] === 'booth_renovation') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="req_renovation">
                                        <i class="fas fa-tools"></i> Booth Renovation
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="requirement_type" 
                                           id="req_rental" value="booth_rental"
                                           <?php echo (isset($form_data['requirement_type']) && $form_data['requirement_type'] === 'booth_rental') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="req_rental">
                                        <i class="fas fa-handshake"></i> Booth Rental
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="requirement_type" 
                                           id="req_graphics" value="graphics_only"
                                           <?php echo (isset($form_data['requirement_type']) && $form_data['requirement_type'] === 'graphics_only') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="req_graphics">
                                        <i class="fas fa-image"></i> Graphics Only
                                    </label>
                                </div>
                            </div>
                            <div class="invalid-feedback d-block" id="requirement_type_error"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Client Details Section -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-user"></i> Client Details
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="client_name" class="form-label required-field">Client Name</label>
                            <input type="text" class="form-control" id="client_name" name="client_name" 
                                   placeholder="Enter client's full name" required
                                   value="<?php echo htmlspecialchars($form_data['client_name'] ?? ''); ?>">
                            <div class="invalid-feedback">Please enter client name.</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="client_company" class="form-label">Company Name</label>
                            <input type="text" class="form-control" id="client_company" name="client_company" 
                                   placeholder="Enter company name (optional)"
                                   value="<?php echo htmlspecialchars($form_data['client_company'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="client_email" class="form-label required-field">Email Address</label>
                            <input type="email" class="form-control" id="client_email" name="client_email" 
                                   placeholder="client@example.com" required
                                   value="<?php echo htmlspecialchars($form_data['client_email'] ?? ''); ?>">
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="client_phone" class="form-label required-field">Phone Number</label>
                            <input type="tel" class="form-control" id="client_phone" name="client_phone" 
                                   placeholder="123-456-7890" required pattern="^\(?([0-9]{3})\)?[-.\s]?([0-9]{3})[-.\s]?([0-9]{4})$"
                                   value="<?php echo htmlspecialchars($form_data['client_phone'] ?? ''); ?>">
                            <div class="phone-format-hint">Format: 123-456-7890</div>
                            <div class="invalid-feedback">Please enter a valid phone number.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Event Details Section -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-calendar-alt"></i> Event Details
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="event_name" class="form-label required-field">Event Name</label>
                            <input type="text" class="form-control" id="event_name" name="event_name" 
                                   placeholder="Enter event name" required
                                   value="<?php echo htmlspecialchars($form_data['event_name'] ?? ''); ?>">
                            <div class="invalid-feedback">Please enter event name.</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="event_date" class="form-label required-field">Event Date</label>
                            <input type="date" class="form-control" id="event_date" name="event_date" required
                                   value="<?php echo htmlspecialchars($form_data['event_date'] ?? ''); ?>">
                            <div class="invalid-feedback">Please select event date.</div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="event_location" class="form-label required-field">Event Location</label>
                            <input type="text" class="form-control" id="event_location" name="event_location" 
                                   placeholder="Enter event location/venue" required
                                   value="<?php echo htmlspecialchars($form_data['event_location'] ?? ''); ?>">
                            <div class="invalid-feedback">Please enter event location.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booth Details Section -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-store"></i> Booth Specifications
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="booth_size" class="form-label">Booth Size</label>
                            <input type="text" class="form-control" id="booth_size" name="booth_size" 
                                   placeholder="e.g., 10x10, 20x20"
                                   value="<?php echo htmlspecialchars($form_data['booth_size'] ?? ''); ?>">
                            <small class="text-muted">Dimensions in feet</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="booth_type" class="form-label required-field">Booth Type</label>
                            <select class="form-select" id="booth_type" name="booth_type" required>
                                <option value="">Select booth type</option>
                                <option value="standard" <?php echo (isset($form_data['booth_type']) && $form_data['booth_type'] === 'standard') ? 'selected' : ''; ?>>Standard</option>
                                <option value="custom" <?php echo (isset($form_data['booth_type']) && $form_data['booth_type'] === 'custom') ? 'selected' : ''; ?>>Custom</option>
                                <option value="island" <?php echo (isset($form_data['booth_type']) && $form_data['booth_type'] === 'island') ? 'selected' : ''; ?>>Island</option>
                                <option value="peninsula" <?php echo (isset($form_data['booth_type']) && $form_data['booth_type'] === 'peninsula') ? 'selected' : ''; ?>>Peninsula</option>
                                <option value="inline" <?php echo (isset($form_data['booth_type']) && $form_data['booth_type'] === 'inline') ? 'selected' : ''; ?>>Inline</option>
                            </select>
                            <div class="invalid-feedback">Please select a booth type.</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="booth_number" class="form-label">Booth Number</label>
                            <input type="text" class="form-control" id="booth_number" name="booth_number" 
                                   placeholder="Enter booth number (if assigned)"
                                   value="<?php echo htmlspecialchars($form_data['booth_number'] ?? ''); ?>">
                            <small class="text-muted">Optional</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Design Requirements Section -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-palette"></i> Additional Design Requirements
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label class="form-label">Select Design Elements (Check all that apply)</label>
                            <div class="checkbox-group">
                                <?php
                                $design_options = [
                                    'graphics' => 'Graphics & Banners',
                                    'lighting' => 'Lighting',
                                    'flooring' => 'Flooring',
                                    'furniture' => 'Furniture',
                                    'av_equipment' => 'A/V Equipment',
                                    'storage' => 'Storage',
                                    'displays' => 'Product Displays',
                                    'signage' => 'Signage'
                                ];
                                
                                foreach ($design_options as $key => $label) {
                                    $checked = isset($form_data['design_requirements']) && 
                                              in_array($key, $form_data['design_requirements']) ? 'checked' : '';
                                    echo "<div class='form-check'>";
                                    echo "<input class='form-check-input' type='checkbox' name='design_requirements[]' value='$key' id='design_$key' $checked>";
                                    echo "<label class='form-check-label' for='design_$key'>";
                                    echo "<i class='fas fa-check-square'></i> $label";
                                    echo "</label>";
                                    echo "</div>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delivery Priority Section -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-shipping-fast"></i> Delivery Priority
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label class="form-label required-field">Select Delivery Priority</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="delivery_priority" 
                                           id="priority_standard" value="standard" required
                                           <?php echo (isset($form_data['delivery_priority']) && $form_data['delivery_priority'] === 'standard') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="priority_standard">
                                        <i class="fas fa-clock"></i> Standard (4-6 weeks)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="delivery_priority" 
                                           id="priority_expedited" value="expedited"
                                           <?php echo (isset($form_data['delivery_priority']) && $form_data['delivery_priority'] === 'expedited') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="priority_expedited">
                                        <i class="fas fa-bolt"></i> Expedited (2-3 weeks)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="delivery_priority" 
                                           id="priority_rush" value="rush"
                                           <?php echo (isset($form_data['delivery_priority']) && $form_data['delivery_priority'] === 'rush') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="priority_rush">
                                        <i class="fas fa-exclamation-triangle"></i> Rush (1 week)
                                    </label>
                                </div>
                            </div>
                            <div class="invalid-feedback d-block" id="delivery_priority_error"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Special Requests Section -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-comment-dots"></i> Special Requests & Notes
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="special_requests" class="form-label">Additional Notes or Special Requests</label>
                            <textarea class="form-control" id="special_requests" name="special_requests" rows="4" 
                                      placeholder="Enter any additional requirements, special instructions, or notes..."><?php echo htmlspecialchars($form_data['special_requests'] ?? ''); ?></textarea>
                            <small class="text-muted">Optional - Provide any additional details that might help us serve you better</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="text-end mt-4">
                <button type="reset" class="btn btn-secondary btn-lg" id="resetBtn">
                    <i class="fas fa-undo"></i> Reset Form
                </button>
                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                    <i class="fas fa-paper-plane"></i> Submit Request
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Form validation and progressive phone formatting
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('exhibitorRequestForm');
    const phoneInput = document.getElementById('client_phone');
    
    // Progressive phone number formatting
    phoneInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 10) value = value.substr(0, 10);
        
        let formatted = '';
        if (value.length > 0) {
            formatted = value.substr(0, 3);
            if (value.length >= 4) {
                formatted += '-' + value.substr(3, 3);
            }
            if (value.length >= 7) {
                formatted += '-' + value.substr(6, 4);
            }
        }
        e.target.value = formatted;
    });
    
    // Form validation
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        // Custom validation for requirement type radio buttons
        const requirementType = document.querySelector('input[name="requirement_type"]:checked');
        const reqTypeError = document.getElementById('requirement_type_error');
        if (!requirementType) {
            event.preventDefault();
            reqTypeError.textContent = 'Please select a requirement type.';
            reqTypeError.style.display = 'block';
        } else {
            reqTypeError.style.display = 'none';
        }
        
        // Custom validation for delivery priority radio buttons
        const deliveryPriority = document.querySelector('input[name="delivery_priority"]:checked');
        const priorityError = document.getElementById('delivery_priority_error');
        if (!deliveryPriority) {
            event.preventDefault();
            priorityError.textContent = 'Please select a delivery priority.';
            priorityError.style.display = 'block';
        } else {
            priorityError.style.display = 'none';
        }
        
        form.classList.add('was-validated');
    }, false);
    
    // Reset button handler
    document.getElementById('resetBtn').addEventListener('click', function() {
        form.classList.remove('was-validated');
        document.getElementById('requirement_type_error').style.display = 'none';
        document.getElementById('delivery_priority_error').style.display = 'none';
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
