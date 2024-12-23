<?php
// Define the routes
$routes = [
    '/' => 'formu.html',
    '/store' => 'store.php',
    '/save' => '/assets/save.php'
];

// Get the current path
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Check if the path exists in the routes
if (array_key_exists($path, $routes)) {
    // Serve the corresponding HTML file
    include 'src/' . $routes[$path];
} else {
    // Serve a 404 error page
    http_response_code(404);
    echo '404 Not Found';
}
?>