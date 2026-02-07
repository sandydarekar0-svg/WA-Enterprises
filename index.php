<?php
// index.php - Routes all requests

require 'config.php';

$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Remove base path if needed
$request = str_replace('/whatsapp-bulk-sender', '', $request);

// ROUTING
if (strpos($request, '/api/') === 0) {
    // API calls
    $endpoint = str_replace('/api/', '', $request);
    
    if (file_exists("api/$endpoint.php")) {
        require "api/$endpoint.php";
    } else {
        json_response(false, 'Endpoint not found');
    }
} else {
    // Web pages
    if ($request === '/') {
        // Check if logged in
        if (isset($_SESSION['user_id'])) {
            $user = get_user();
            if ($user['role'] === 'admin') {
                header('Location: /admin/dashboard.html');
            } else {
                header('Location: /user/dashboard.html');
            }
        } else {
            header('Location: /admin/index.html');
        }
    } else if ($request === '/logout') {
        session_destroy();
        header('Location: /admin/index.html');
    } else if (file_exists("." . $request)) {
        // Serve static files
        return false;
    } else {
        // 404
        header('HTTP/1.0 404 Not Found');
        echo '404 - Page not found';
    }
}
?>
