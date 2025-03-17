<?php

declare(strict_types=1);

/**
 * Derafu: GitHub - Webhook handling and other sysadmin tasks.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\GitHub\Webhook;

use Derafu\GitHub\Logger;
use Derafu\GitHub\Webhook\Handler;
use Derafu\GitHub\Webhook\Response;
use Exception;

// Load the autoloader.
require 'vendor/autoload.php';

// Load configuration.
$config = require 'config.php';

// Create the handler.
$logger = new Logger();
$handler = new Handler($config, $logger);

// Handle the webhook.
try {
    $notification = $handler->handle();
    $response = $notification->getResponse();
} catch (Exception $e) {
    $response = new Response([
        'code' => $e->getCode() ?: 1,
        'data' => [
            'message' => $e->getMessage(),
            'logs' => $logger->getLogs(),
        ],
    ]);
}

// Send the response.
http_response_code($response->getHttpCode());
header('Content-Type: application/json');
echo json_encode($response->toArray(), JSON_PRETTY_PRINT);
