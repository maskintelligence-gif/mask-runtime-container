<?php
/**
 * Main PHP entry point
 */

// Enable error reporting for development
if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

$request = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Handle PHP API routes
if (strpos($request, '/api/php/') === 0) {
    header('Content-Type: application/json');
    
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
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size')
            ]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'PHP endpoint not found']);
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
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                line-height: 1.6;
                color: #333;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                padding: 20px;
            }
            
            .container {
                max-width: 1200px;
                margin: 0 auto;
            }
            
            .header {
                text-align: center;
                color: white;
                margin-bottom: 40px;
            }
            
            .header h1 {
                font-size: 3em;
                margin-bottom: 10px;
                text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
            }
            
            .header p {
                font-size: 1.2em;
                opacity: 0.9;
            }
            
            .status-card {
                background: white;
                border-radius: 15px;
                padding: 25px;
                margin-bottom: 30px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                border-left: 5px solid #28a745;
            }
            
            .status-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-top: 20px;
            }
            
            .status-item {
                text-align: center;
                padding: 15px;
                background: #f8f9fa;
                border-radius: 10px;
            }
            
            .status-label {
                font-size: 0.9em;
                color: #666;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            
            .status-value {
                font-size: 1.2em;
                font-weight: bold;
                color: #28a745;
                margin-top: 5px;
            }
            
            .services-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 25px;
                margin-bottom: 40px;
            }
            
            .service-card {
                background: white;
                border-radius: 15px;
                padding: 25px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                transition: transform 0.3s ease;
            }
            
            .service-card:hover {
                transform: translateY(-5px);
            }
            
            .service-header {
                display: flex;
                align-items: center;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 2px solid #f0f0f0;
            }
            
            .service-icon {
                width: 40px;
                height: 40px;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-right: 15px;
                font-weight: bold;
                color: white;
            }
            
            .php-icon { background: #4F5B93; }
            .python-icon { background: #306998; }
            .node-icon { background: #68A063; }
            
            .service-title {
                font-size: 1.3em;
                font-weight: bold;
            }
            
            .service-subtitle {
                font-size: 0.9em;
                color: #666;
            }
            
            .endpoint-list {
                list-style: none;
            }
            
            .endpoint-item {
                padding: 12px;
                background: #f8f9fa;
                border-radius: 8px;
                margin-bottom: 10px;
                font-family: 'Courier New', monospace;
                font-size: 0.9em;
                border-left: 3px solid;
            }
            
            .endpoint-item.php { border-left-color: #4F5B93; }
            .endpoint-item.python { border-left-color: #306998; }
            .endpoint-item.node { border-left-color: #68A063; }
            
            .endpoint-method {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 5px;
                color: white;
                font-size: 0.8em;
                margin-right: 10px;
                font-weight: bold;
            }
            
            .method-get { background: #28a745; }
            .method-post { background: #007bff; }
            .method-ws { background: #dc3545; }
            
            .endpoint-path {
                font-weight: bold;
            }
            
            .endpoint-desc {
                display: block;
                color: #666;
                font-size: 0.9em;
                margin-top: 5px;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            
            .env-section {
                background: white;
                border-radius: 15px;
                padding: 25px;
                margin-top: 30px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            }
            
            .env-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 15px;
                margin-top: 15px;
            }
            
            .env-item {
                padding: 10px;
                background: #f8f9fa;
                border-radius: 8px;
            }
            
            .env-label {
                font-weight: bold;
                color: #666;
            }
            
            .footer {
                text-align: center;
                margin-top: 40px;
                color: white;
                opacity: 0.8;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🚀 Multi-Runtime Container</h1>
                <p>Your all-in-one PHP + Python + Node.js + Nginx solution</p>
            </div>
            
            <div class="status-card">
                <h2>System Status</h2>
                <div class="status-grid">
                    <div class="status-item">
                        <div class="status-label">Status</div>
                        <div class="status-value">✅ Running</div>
                    </div>
                    <div class="status-item">
                        <div class="status-label">Host</div>
                        <div class="status-value"><?php echo gethostname(); ?></div>
                    </div>
                    <div class="status-item">
                        <div class="status-label">Time</div>
                        <div class="status-value"><?php echo date('Y-m-d H:i:s'); ?></div>
                    </div>
                </div>
            </div>
            
            <h2 style="color: white; margin-bottom: 20px;">Available Services</h2>
            
            <div class="services-grid">
                <!-- PHP Card -->
                <div class="service-card">
                    <div class="service-header">
                        <div class="service-icon php-icon">PHP</div>
                        <div>
                            <div class="service-title">PHP 8.3</div>
                            <div class="service-subtitle">FastCGI Process Manager</div>
                        </div>
                    </div>
                    <ul class="endpoint-list">
                        <li class="endpoint-item php">
                            <span class="endpoint-method method-get">GET</span>
                            <span class="endpoint-path">/</span>
                            <span class="endpoint-desc">This status page</span>
                        </li>
                        <li class="endpoint-item php">
                            <span class="endpoint-method method-get">GET</span>
                            <span class="endpoint-path">/api/php/health</span>
                            <span class="endpoint-desc">PHP health check</span>
                        </li>
                        <li class="endpoint-item php">
                            <span class="endpoint-method method-get">GET</span>
                            <span class="endpoint-path">/api/php/info</span>
                            <span class="endpoint-desc">PHP configuration info</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Python Card -->
                <div class="service-card">
                    <div class="service-header">
                        <div class="service-icon python-icon">Py</div>
                        <div>
                            <div class="service-title">Python FastAPI</div>
                            <div class="service-subtitle">High-performance async framework</div>
                        </div>
                    </div>
                    <ul class="endpoint-list">
                        <li class="endpoint-item python">
                            <span class="endpoint-method method-get">GET</span>
                            <span class="endpoint-path">/py/health</span>
                            <span class="endpoint-desc">Python health check</span>
                        </li>
                        <li class="endpoint-item python">
                            <span class="endpoint-method method-get">GET</span>
                            <span class="endpoint-path">/py/items</span>
                            <span class="endpoint-desc">List all items</span>
                        </li>
                        <li class="endpoint-item python">
                            <span class="endpoint-method method-post">POST</span>
                            <span class="endpoint-path">/py/items</span>
                            <span class="endpoint-desc">Create new item</span>
                        </li>
                        <li class="endpoint-item python">
                            <span class="endpoint-method method-get">GET</span>
                            <span class="endpoint-path">/py/items/{id}</span>
                            <span class="endpoint-desc">Get item by ID</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Node.js Card -->
                <div class="service-card">
                    <div class="service-header">
                        <div class="service-icon node-icon">Node</div>
                        <div>
                            <div class="service-title">Node.js Express</div>
                            <div class="service-subtitle">Fast, unopinionated framework</div>
                        </div>
                    </div>
                    <ul class="endpoint-list">
                        <li class="endpoint-item node">
                            <span class="endpoint-method method-get">GET</span>
                            <span class="endpoint-path">/node/health</span>
                            <span class="endpoint-desc">Node.js health check</span>
                        </li>
                        <li class="endpoint-item node">
                            <span class="endpoint-method method-get">GET</span>
                            <span class="endpoint-path">/node/users</span>
                            <span class="endpoint-desc">List all users</span>
                        </li>
                        <li class="endpoint-item node">
                            <span class="endpoint-method method-post">POST</span>
                            <span class="endpoint-path">/node/users</span>
                            <span class="endpoint-desc">Create new user</span>
                        </li>
                        <li class="endpoint-item node">
                            <span class="endpoint-method method-ws">WS</span>
                            <span class="endpoint-path">/ws</span>
                            <span class="endpoint-desc">WebSocket connection</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Environment Section -->
            <div class="env-section">
                <h2>Environment Details</h2>
                <div class="env-grid">
                    <div class="env-item">
                        <span class="env-label">PHP Version:</span> <?php echo phpversion(); ?>
                    </div>
                    <div class="env-item">
                        <span class="env-label">Server:</span> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Nginx'; ?>
                    </div>
                    <div class="env-item">
                        <span class="env-label">Document Root:</span> <?php echo $_SERVER['DOCUMENT_ROOT']; ?>
                    </div>
                    <div class="env-item">
                        <span class="env-label">Container ID:</span> <?php echo gethostname(); ?>
                    </div>
                </div>
            </div>
            
            <div class="footer">
                <p>Built by MASK INTELLIGENCE • Powered by GHCR • Multi-Runtime Container v1.0</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Default 404 response
http_response_code(404);
header('Content-Type: application/json');
echo json_encode([
    'error' => 'Endpoint not found',
    'path' => $request,
    'message' => 'Check / for available endpoints'
]);
