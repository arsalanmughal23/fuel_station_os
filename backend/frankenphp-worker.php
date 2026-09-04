<?php

/**
 * FrankenPHP Worker Entry Point for Tauri Sidecar
 * 
 * Runs as a long-running PHP worker handling requests via stdin/stdout JSON-RPC.
 * Communicates with Tauri sidecar process manager.
 * 
 * Commands from Tauri:
 * - {"method": "health"} -> Returns health status
 * - {"method": "GET", "uri": "/api/...", "headers": {}, "query": {}, "body": null} -> HTTP request
 * - {"method": "shutdown"} -> Graceful shutdown
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->boot();

// Handle graceful shutdown
$shutdown = false;
pcntl_signal(SIGTERM, function () use (&$shutdown) {
    $shutdown = true;
});
pcntl_signal(SIGINT, function () use (&$shutdown) {
    $shutdown = true;
});

// Send ready signal to Tauri
echo json_encode(['event' => 'ready', 'pid' => getmypid()]) . "\n";
@ob_flush();
flush();

// Handle worker lifecycle
while (true) {
    if ($shutdown) {
        break;
    }

    // Read from stdin (Tauri sends requests via stdin)
    $input = fgets(STDIN);
    if ($input === false) {
        // EOF - parent process closed stdin
        break;
    }

    $input = trim($input);
    if ($input === '') {
        continue;
    }

    // Handle special commands
    if ($input === 'shutdown') {
        break;
    }

    if ($input === 'health') {
        echo json_encode([
            'status' => 'ok',
            'pid' => getmypid(),
            'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'uptime_seconds' => time() - $_SERVER['REQUEST_TIME_FLOAT'] ?? 0,
        ]) . "\n";
        @ob_flush();
        flush();
        continue;
    }

    $request = json_decode($input, true);
    if (!$request) {
        echo json_encode(['error' => 'Invalid JSON', 'input' => $input]) . "\n";
        @ob_flush();
        flush();
        continue;
    }

    // Handle special command in JSON
    if (isset($request['command'])) {
        if ($request['command'] === 'health') {
            echo json_encode([
                'status' => 'ok',
                'pid' => getmypid(),
                'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            ]) . "\n";
            @ob_flush();
            flush();
            continue;
        }
        if ($request['command'] === 'shutdown') {
            $shutdown = true;
            echo json_encode(['status' => 'shutting_down']) . "\n";
            @ob_flush();
            flush();
            continue;
        }
    }

    try {
        // Create Laravel request from JSON-RPC request
        $laravelRequest = createLaravelRequest($request);
        
        // Handle request via Laravel
        $response = $app->handle($laravelRequest);
        
        // Output response as JSON
        $responseData = [
            'status' => $response->getStatusCode(),
            'headers' => $response->headers->all(),
            'content' => $response->getContent(),
        ];
        
        echo json_encode($responseData) . "\n";
        @ob_flush();
        flush();
    } catch (Throwable $e) {
        $error = [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ];
        echo json_encode(['error' => $error]) . "\n";
        @ob_flush();
        flush();
    }
}

/**
 * Create Laravel request from JSON-RPC request
 */
function createLaravelRequest(array $request): Illuminate\Http\Request
{
    $method = $request['method'] ?? 'GET';
    $uri = $request['uri'] ?? '/';
    $headers = $request['headers'] ?? [];
    $body = $request['body'] ?? null;
    $query = $request['query'] ?? [];

    // Create Symfony request
    $request = Illuminate\Http\Request::create(
        $uri,
        $method,
        $query,
        [], // cookies
        [], // files
        array_change_key_case($headers, CASE_UPPER), // server params
        $body // content
    );

    // Set headers properly
    foreach ($headers as $key => $value) {
        $request->headers->set($key, $value);
    }

    return $request;
}