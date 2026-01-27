<?php
/**
 * Exhibitor Sales Request Form
 * This page allows sales representatives to create and submit exhibitor sales requests
 */

// Initialize variables
$errors = [];
$success = false;
$formData = [
    'requirement_type' => '',
    'client_name' => '',
    'client_email' => '',
    'client_phone' => '',
    'event_name' => '',
    'event_date' => '',
    'event_location' => '',
    'booth_size' => '',
    'booth_number' => '',
    'booth_type' => '',
    'design_requirements' => [],
    'delivery_priority' => '',
    'special_requests' => '',
    'budget_range' => ''
];

// Server-side validation and processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $formData['requirement_type'] = filter_input(INPUT_POST, 'requirement_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $formData['client_name'] = filter_input(INPUT_POST, 'client_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $formData['client_email'] = filter_input(INPUT_POST, 'client_email', FILTER_SANITIZE_EMAIL);
    $formData['client_phone'] = filter_input(INPUT_POST, 'client_phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $formData['event_name'] = filter_input(INPUT_POST, 'event_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $formData['event_date'] = filter_input(INPUT_POST, 'event_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $formData['event_location'] = filter_input(INPUT_POST, 'event_location', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $formData['booth_size'] = filter_input(INPUT_POST, 'booth_size', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $formData['booth_number'] = filter_input(INPUT_POST, 'booth_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $formData['booth_type'] = filter_input(INPUT_POST, 'booth_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $formData['design_requirements'] = filter_input(INPUT_POST, 'design_requirements', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY) ?? [];
    $formData['delivery_priority'] = filter_input(INPUT_POST, 'delivery_priority', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $formData['special_requests'] = filter_input(INPUT_POST, 'special_requests', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $formData['budget_range'] = filter_input(INPUT_POST, 'budget_range', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    // Validation
    if (empty($formData['requirement_type'])) {
        $errors[] = 'Requirement type is required.';
    }
    
    if (empty($formData['client_name'])) {
        $errors[] = 'Client name is required.';
    }
    
    if (empty($formData['client_email'])) {
        $errors[] = 'Client email is required.';
    } elseif (!filter_var($formData['client_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }
    
    if (empty($formData['client_phone'])) {
        $errors[] = 'Client phone number is required.';
    }
    
    if (empty($formData['event_name'])) {
        $errors[] = 'Event name is required.';
    }
    
    if (empty($formData['event_date'])) {
        $errors[] = 'Event date is required.';
    }
    
    if (empty($formData['event_location'])) {
        $errors[] = 'Event location is required.';
    }
    
    if (empty($formData['booth_size'])) {
        $errors[] = 'Booth size is required.';
    }
    
    if (empty($formData['delivery_priority'])) {
        $errors[] = 'Delivery priority is required.';
    }
    
    // If no errors, process the form
    if (empty($errors)) {
        // Here you would typically save to database or send email
        // For now, we'll just set success flag
        $success = true;
        
        // Reset form data after successful submission
        foreach ($formData as $key => $value) {
            $formData[$key] = is_array($value) ? [] : '';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exhibitor Sales Request Form</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    
    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .form-container {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-top: 30px;
            margin-bottom: 30px;
        }
        
        .form-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            margin: -30px -30px 30px -30px;
        }
        
        .form-header h2 {
            margin: 0;
            font-weight: 600;
        }
        
        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .form-section h4 {
            color: #667eea;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
        }
        
        .required::after {
            content: " *";
            color: #dc3545;
        }
        
        .form-control:focus,
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 40px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            border-radius: 8px;
        }
        
        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }
        
        @media (max-width: 768px) {
            .form-container {
                padding: 20px;
            }
            
            .form-header {
                margin: -20px -20px 20px -20px;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-9">
                <div class="form-container">
                    <div class="form-header">
                        <h2><i class="fas fa-clipboard-list me-2"></i>Exhibitor Sales Request Form</h2>
                        <p class="mb-0 mt-2">Please fill out all required fields to submit your sales request</p>
                    </div>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Success!</strong> Your sales request has been submitted successfully.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Error!</strong> Please fix the following issues:
                            <ul class="mb-0 mt-2">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="salesRequestForm" novalidate>
                        
                        <!-- Requirement Type Section -->
                        <div class="form-section">
                            <h4><i class="fas fa-tasks me-2"></i>Requirement Type</h4>
                            <div class="row">
                                <div class="col-12">
                                    <label class="form-label required">Select Request Type</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="requirement_type" id="req_new" value="new_booth" required <?php echo ($formData['requirement_type'] === 'new_booth') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="req_new">
                                                New Booth Design
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="requirement_type" id="req_renovation" value="renovation" <?php echo ($formData['requirement_type'] === 'renovation') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="req_renovation">
                                                Booth Renovation
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="requirement_type" id="req_rental" value="rental" <?php echo ($formData['requirement_type'] === 'rental') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="req_rental">
                                                Booth Rental
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="requirement_type" id="req_graphics" value="graphics" <?php echo ($formData['requirement_type'] === 'graphics') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="req_graphics">
                                                Graphics Only
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Client Details Section -->
                        <div class="form-section">
                            <h4><i class="fas fa-user me-2"></i>Client Details</h4>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="client_name" class="form-label required">Client Name</label>
                                    <input type="text" class="form-control" id="client_name" name="client_name" 
                                           value="<?php echo htmlspecialchars($formData['client_name']); ?>" 
                                           placeholder="Enter client's full name" required>
                                    <div class="invalid-feedback">
                                        Please provide the client's name.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="client_email" class="form-label required">Client Email</label>
                                    <input type="email" class="form-control" id="client_email" name="client_email" 
                                           value="<?php echo htmlspecialchars($formData['client_email']); ?>" 
                                           placeholder="client@example.com" required>
                                    <div class="invalid-feedback">
                                        Please provide a valid email address.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="client_phone" class="form-label required">Client Phone</label>
                                    <input type="tel" class="form-control" id="client_phone" name="client_phone" 
                                           value="<?php echo htmlspecialchars($formData['client_phone']); ?>" 
                                           placeholder="+1 (555) 123-4567" required>
                                    <div class="invalid-feedback">
                                        Please provide a contact phone number.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="budget_range" class="form-label">Budget Range</label>
                                    <select class="form-select" id="budget_range" name="budget_range">
                                        <option value="">Select budget range</option>
                                        <option value="under_5k" <?php echo ($formData['budget_range'] === 'under_5k') ? 'selected' : ''; ?>>Under $5,000</option>
                                        <option value="5k_10k" <?php echo ($formData['budget_range'] === '5k_10k') ? 'selected' : ''; ?>>$5,000 - $10,000</option>
                                        <option value="10k_25k" <?php echo ($formData['budget_range'] === '10k_25k') ? 'selected' : ''; ?>>$10,000 - $25,000</option>
                                        <option value="25k_50k" <?php echo ($formData['budget_range'] === '25k_50k') ? 'selected' : ''; ?>>$25,000 - $50,000</option>
                                        <option value="over_50k" <?php echo ($formData['budget_range'] === 'over_50k') ? 'selected' : ''; ?>>Over $50,000</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Event Details Section -->
                        <div class="form-section">
                            <h4><i class="fas fa-calendar-alt me-2"></i>Event Details</h4>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="event_name" class="form-label required">Event Name</label>
                                    <input type="text" class="form-control" id="event_name" name="event_name" 
                                           value="<?php echo htmlspecialchars($formData['event_name']); ?>" 
                                           placeholder="Enter event name" required>
                                    <div class="invalid-feedback">
                                        Please provide the event name.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="event_date" class="form-label required">Event Date</label>
                                    <input type="date" class="form-control" id="event_date" name="event_date" 
                                           value="<?php echo htmlspecialchars($formData['event_date']); ?>" required>
                                    <div class="invalid-feedback">
                                        Please provide the event date.
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="event_location" class="form-label required">Event Location</label>
                                    <input type="text" class="form-control" id="event_location" name="event_location" 
                                           value="<?php echo htmlspecialchars($formData['event_location']); ?>" 
                                           placeholder="Enter event venue/location" required>
                                    <div class="invalid-feedback">
                                        Please provide the event location.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Booth Details Section -->
                        <div class="form-section">
                            <h4><i class="fas fa-store me-2"></i>Booth Details</h4>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="booth_size" class="form-label required">Booth Size</label>
                                    <select class="form-select" id="booth_size" name="booth_size" required>
                                        <option value="">Select size</option>
                                        <option value="10x10" <?php echo ($formData['booth_size'] === '10x10') ? 'selected' : ''; ?>>10x10 ft</option>
                                        <option value="10x20" <?php echo ($formData['booth_size'] === '10x20') ? 'selected' : ''; ?>>10x20 ft</option>
                                        <option value="20x20" <?php echo ($formData['booth_size'] === '20x20') ? 'selected' : ''; ?>>20x20 ft</option>
                                        <option value="20x30" <?php echo ($formData['booth_size'] === '20x30') ? 'selected' : ''; ?>>20x30 ft</option>
                                        <option value="custom" <?php echo ($formData['booth_size'] === 'custom') ? 'selected' : ''; ?>>Custom Size</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Please select a booth size.
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="booth_number" class="form-label">Booth Number</label>
                                    <input type="text" class="form-control" id="booth_number" name="booth_number" 
                                           value="<?php echo htmlspecialchars($formData['booth_number']); ?>" 
                                           placeholder="Enter booth number (if assigned)">
                                </div>
                                <div class="col-md-4">
                                    <label for="booth_type" class="form-label">Booth Type</label>
                                    <select class="form-select" id="booth_type" name="booth_type">
                                        <option value="">Select type</option>
                                        <option value="inline" <?php echo ($formData['booth_type'] === 'inline') ? 'selected' : ''; ?>>Inline</option>
                                        <option value="corner" <?php echo ($formData['booth_type'] === 'corner') ? 'selected' : ''; ?>>Corner</option>
                                        <option value="peninsula" <?php echo ($formData['booth_type'] === 'peninsula') ? 'selected' : ''; ?>>Peninsula</option>
                                        <option value="island" <?php echo ($formData['booth_type'] === 'island') ? 'selected' : ''; ?>>Island</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Design Requirements Section -->
                        <div class="form-section">
                            <h4><i class="fas fa-palette me-2"></i>Additional Design Requirements</h4>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Select Design Elements (Check all that apply)</label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="design_requirements[]" value="custom_graphics" id="design_graphics">
                                                <label class="form-check-label" for="design_graphics">
                                                    Custom Graphics
                                                </label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="design_requirements[]" value="lighting" id="design_lighting">
                                                <label class="form-check-label" for="design_lighting">
                                                    Special Lighting
                                                </label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="design_requirements[]" value="flooring" id="design_flooring">
                                                <label class="form-check-label" for="design_flooring">
                                                    Custom Flooring
                                                </label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="design_requirements[]" value="furniture" id="design_furniture">
                                                <label class="form-check-label" for="design_furniture">
                                                    Furniture & Seating
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="design_requirements[]" value="av_equipment" id="design_av">
                                                <label class="form-check-label" for="design_av">
                                                    A/V Equipment
                                                </label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="design_requirements[]" value="storage" id="design_storage">
                                                <label class="form-check-label" for="design_storage">
                                                    Storage Solutions
                                                </label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="design_requirements[]" value="displays" id="design_displays">
                                                <label class="form-check-label" for="design_displays">
                                                    Product Displays
                                                </label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="design_requirements[]" value="signage" id="design_signage">
                                                <label class="form-check-label" for="design_signage">
                                                    Digital Signage
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Delivery Priority Section -->
                        <div class="form-section">
                            <h4><i class="fas fa-shipping-fast me-2"></i>Delivery Priority</h4>
                            <div class="row">
                                <div class="col-12">
                                    <label class="form-label required">Select Delivery Priority</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="delivery_priority" id="priority_standard" value="standard" required <?php echo ($formData['delivery_priority'] === 'standard') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="priority_standard">
                                                Standard (4-6 weeks)
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="delivery_priority" id="priority_expedited" value="expedited" <?php echo ($formData['delivery_priority'] === 'expedited') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="priority_expedited">
                                                Expedited (2-3 weeks)
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="delivery_priority" id="priority_rush" value="rush" <?php echo ($formData['delivery_priority'] === 'rush') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="priority_rush">
                                                Rush (1 week)
                                            </label>
                                        </div>
                                    </div>
                                    <div class="invalid-feedback">
                                        Please select a delivery priority.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Special Requests Section -->
                        <div class="form-section">
                            <h4><i class="fas fa-comment-alt me-2"></i>Special Requests & Notes</h4>
                            <div class="row">
                                <div class="col-12">
                                    <label for="special_requests" class="form-label">Additional Comments or Special Requirements</label>
                                    <textarea class="form-control" id="special_requests" name="special_requests" rows="4" 
                                              placeholder="Please provide any additional information, special requirements, or notes that would help us serve you better..."><?php echo htmlspecialchars($formData['special_requests']); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="reset" class="btn btn-outline-secondary px-4">
                                <i class="fas fa-redo me-2"></i>Reset Form
                            </button>
                            <button type="submit" class="btn btn-primary btn-submit px-5">
                                <i class="fas fa-paper-plane me-2"></i>Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    
    <!-- Custom JavaScript for form validation -->
    <script>
        // Client-side form validation
        (function() {
            'use strict';
            
            // Fetch the form element
            const form = document.getElementById('salesRequestForm');
            
            // Add submit event listener
            form.addEventListener('submit', function(event) {
                // Check if form is valid
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                // Add Bootstrap validation classes
                form.classList.add('was-validated');
            }, false);
            
            // Phone number formatting with proper display format
            const phoneInput = document.getElementById('client_phone');
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                
                // Limit to 10 digits
                if (value.length > 10) {
                    value = value.substring(0, 10);
                }
                
                // Format as (XXX) XXX-XXXX
                let formatted = '';
                if (value.length > 0) {
                    formatted = value.substring(0, 3);
                    if (value.length >= 4) {
                        formatted = '(' + formatted + ') ' + value.substring(3, 6);
                        if (value.length >= 7) {
                            formatted += '-' + value.substring(6, 10);
                        }
                    }
                }
                
                e.target.value = formatted || value;
            });
            
            // Event date validation (prevent past dates)
            const eventDateInput = document.getElementById('event_date');
            const today = new Date().toISOString().split('T')[0];
            eventDateInput.setAttribute('min', today);
            
        })();
    </script>
</body>
</html>
