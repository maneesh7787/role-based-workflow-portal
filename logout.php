<?php
/**
 * Logout Page
 */
require_once 'config/config.php';

// Log activity before destroying session
if (isLoggedIn()) {
    logActivity($_SESSION['user_id'], 'User Logout', 'User logged out successfully');
}

// Destroy session
session_destroy();

// Redirect to login page
redirect('login.php');
?>
