<?php
/**
 * Main PHP entry point
 * Supports Laravel, Symfony, or custom PHP apps
 */

// Enable error reporting for development (disable in production)
if ($_ENV['APP_ENV'] ?? 'production' === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Simple router for demo
$request = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

header('Content-Type: application/json');

// API routes
if (strpos($request, '/api/php') === 0) {
    switch ($request) {
        case '/api/php/health':
            echo json_encode([
                'status' => 'healthy',
                'service' => 'php',
                'timestamp' => date('c'),
                'php_version' => phpversion()
            ]);
            break;
            
        case '/api/php/info':
            echo json_encode([
                'extensions' => get_loaded_extensions(),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize')
            ]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
    }
    exit;
}

// Simple HTML page for root
if ($request === '/') {
    header('Content-Type: text/html');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Multi-Runtime Container</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
            }
            h1 { color: #2c3e50; }
            .status {
                background: #f8f9fa;
                border-left: 4px solid #28a745;
                padding: 15px;
                margin: 20px 0;
            }
            .endpoint {
                background: #e9ecef;
                padding: 10px;
                margin: 10px 0;
                border-radius: 4px;
            }
            code {
                background: #f4f4f4;
                padding: 2px 5px;
                border-radius: 3px;
            }
        </style>
    </head>
    <body>
        <h1>🚀 Multi-Runtime Container</h1>
        
        <div class="status">
            <strong>Status:</strong> Running<br>
            <strong>Host:</strong> <?php echo gethostname(); ?><br>
            <strong>Time:</strong> <?php echo date('Y-m-d H:i:s'); ?>
        </div>
        
        <h2>Available Services</h2>
        
        <div class="endpoint">
            <strong>PHP (this page)</strong><br>
            <code>GET /</code> - This status page<br>
            <code>GET /api/php/health</code> - PHP health check<br>
            <code>GET /api/php/info</code> - PHP info
        </div>
        
        <div class="endpoint">
    <strong>Python FastAPI</strong><br>
    <code>GET /py/health</code> - Python health<br>
    <code>GET /py/items</code> - List items<br>
    <code>POST /py/items</code> - Create item
</div>

<div class="endpoint">
    <strong>Node.js</strong><br>
    <code>GET /node/health</code> - Node health<br>
    <code>GET /node/users</code> - List users<br>
    <code>WS /ws/</code> - WebSocket connection
</div>
        
        <div class="endpoint">
            <strong>Frontend Apps</strong><br>
            <code>/app/</code> - Main React app<br>
            <code>/dashboard/</code> - Dashboard app<br>
            <code>/admin/</code> - Admin panel
        </div>
        
        <h2>Environment</h2>
        <pre>
PHP Version: <?php echo phpversion(); ?>
Server: <?php echo $_SERVER['SERVER_SOFTWARE']; ?>
Document Root: <?php echo $_SERVER['DOCUMENT_ROOT']; ?>
        </pre>
    </body>
    </html>
    <?php
    exit;
}

// Default response for unhandled routes
http_response_code(404);
header('Content-Type: application/json');
echo json_encode(['error' => 'Not found', 'path' => $request]);
