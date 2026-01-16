<?php
/**
 * Todo Talenta Digital - Main Router
 * 
 * This file serves as the main entry point and router for the application
 */

require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Get the requested page from URL parameter
$page = isset($_GET['page']) ? $_GET['page'] : '';

// If no page specified, redirect based on login status
if (empty($page)) {
    if (isLoggedIn()) {
        $page = 'dashboard';
    } else {
        $page = 'login';
    }
}

// Define allowed pages and their corresponding view files
$allowed_pages = [
    'login' => 'view/login.php',
    'register' => 'view/register.php',
    'dashboard' => 'view/dashboard.php',
    'users' => 'view/users.php',
    'notes' => 'view/notes.php',
    'todos' => 'view/todos.php',
    'change-password' => 'view/change-password.php',
    'logout' => 'logout.php'
];

// Check if requested page exists
if (array_key_exists($page, $allowed_pages)) {
    $view_file = $allowed_pages[$page];
    
    // Check if file exists
    if (file_exists($view_file)) {
        include $view_file;
    } else {
        // File not found, redirect to login
        header('Location: ?page=login');
        exit();
    }
} else {
    // Invalid page, redirect to appropriate page
    if (isLoggedIn()) {
        header('Location: ?page=dashboard');
    } else {
        header('Location: ?page=login');
    }
    exit();
}
