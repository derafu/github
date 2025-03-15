<?php

declare(strict_types=1);

/**
 * Derafu: GitHub - Webhook handling and other sysadmin tasks.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

use Derafu\GitHub\Webhook\EventHandler\WorkflowRunHandler;
use Derafu\GitHub\Webhook\Notification;

// Load the sites configuration.
$DEPLOYER_DIR = realpath('/home/admin/deployer');
$dep = $DEPLOYER_DIR . '/vendor/bin/dep';
$sites = require $DEPLOYER_DIR . '/sites.php';

// Return the configuration.
return [
    'secret' => getenv('GITHUB_WEBHOOK_SECRET') ?: throw new RuntimeException(
        'Environment variable GITHUB_WEBHOOK_SECRET is not set.'
    ),
    'hash_id' => getenv('GITHUB_WEBHOOK_HASH_ID') ?: null,
    'handlers' => [
        // Add handling for workflow_run (GitHub Actions) events to deploy sites.
        'workflow_run' => fn (Notification $notification) => WorkflowRunHandler::deploy(
            $notification,
            $dep,
            $sites
        ),
    ],
];
