<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>/index.php">
                <i class="fas fa-tasks"></i> <?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/index.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    
                    <?php if (hasRole('Admin')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-shield"></i> Admin
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/users.php">Manage Users</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/requests.php">All Requests</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/logs.php">Activity Logs</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasRole('Sales')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/sales/create_request.php">
                            <i class="fas fa-plus-circle"></i> New Request
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/sales/my_requests.php">
                            <i class="fas fa-list"></i> My Requests
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasRole('Design')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/design/pending_requests.php">
                            <i class="fas fa-palette"></i> Pending Designs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/design/completed_designs.php">
                            <i class="fas fa-check-circle"></i> Completed Designs
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasRole('Approval')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/approval/pending_requests.php">
                            <i class="fas fa-check-circle"></i> Pending Approvals
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <?php
                            $db = getDB();
                            $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
                            $stmt->bind_param("i", $_SESSION['user_id']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $unread = $result->fetch_assoc()['count'];
                            $stmt->close();
                            if ($unread > 0):
                            ?>
                            <span class="badge bg-danger"><?php echo $unread; ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end notification-dropdown">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <?php
                            $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
                            $stmt->bind_param("i", $_SESSION['user_id']);
                            $stmt->execute();
                            $notifications = $stmt->get_result();
                            
                            if ($notifications->num_rows > 0):
                                while ($notif = $notifications->fetch_assoc()):
                            ?>
                            <li>
                                <a class="dropdown-item <?php echo $notif['is_read'] ? '' : 'fw-bold'; ?>" href="<?php echo BASE_URL; ?>/notifications.php?id=<?php echo $notif['id']; ?>">
                                    <small class="text-muted"><?php echo date('M d, H:i', strtotime($notif['created_at'])); ?></small><br>
                                    <?php echo htmlspecialchars(substr($notif['message'], 0, 50)); ?>...
                                </a>
                            </li>
                            <?php 
                                endwhile;
                            else:
                            ?>
                            <li><a class="dropdown-item text-muted">No notifications</a></li>
                            <?php endif; $stmt->close(); ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" href="<?php echo BASE_URL; ?>/notifications.php">View All</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/profile.php"><i class="fas fa-user-circle"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <div class="<?php echo isLoggedIn() ? 'container-fluid mt-4' : ''; ?>">
