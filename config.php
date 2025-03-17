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

return [
    'handlers' => [
        'workflow_run' => fn (Notification $notification) => WorkflowRunHandler::deploy(
            $notification
        ),
    ],
];
