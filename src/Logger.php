<?php

declare(strict_types=1);

/**
 * Derafu: GitHub - Webhook handling and other sysadmin tasks.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\GitHub;

use Psr\Log\AbstractLogger;
use Stringable;

/**
 * Memory logger.
 */
class Logger extends AbstractLogger
    {
    /**
     * The logs.
     *
     * @var array
     */
    private array $logs = [];

    /**
     * Logs with an arbitrary level.
     *
     * @param string $level The level of the log.
     * @param string|Stringable $message The message to log.
     * @param mixed[] $context The context of the log.
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->logs[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];
    }

    /**
     * Get the logs.
     *
     * @return array
     */
    public function getLogs(): array
    {
        return $this->logs;
    }
}
