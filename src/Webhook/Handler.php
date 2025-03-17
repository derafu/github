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

use Closure;
use RuntimeException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class for handling GitHub webhooks.
 *
 * A simple class to handle GitHub webhooks without external dependencies.
 */
final class Handler
{
    /**
     * The configuration of the webhook notification.
     *
     * Keys:
     *
     *   - secret: The secret configured in GitHub (required).
     *   - hash_id: The Hash ID for additional check in $_GET['hash_id'].
     *
     * @var array
     */
    private array $config;

    /**
     * The handlers for the events.
     *
     * @var array<string,Closure>
     */
    private array $handlers = [];

    /**
     * The logger.
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Constructor.
     *
     * @param string|array $config The configuration of the webhook notification.
     * @param LoggerInterface|null $logger The logger.
     */
    public function __construct(
        string|array $config,
        ?LoggerInterface $logger = null
    ) {
        if (is_string($config)) {
            $config = [
                'secret' => $config,
            ];
        }

        $config = array_merge([
            'secret' => getenv('GITHUB_WEBHOOK_SECRET') ?: null,
            'hash_id' => getenv('GITHUB_WEBHOOK_HASH_ID') ?: null,
            'handlers' => [],
        ], $config);

        if ($config['secret'] === null) {
            throw new RuntimeException(
                'Environment variable GITHUB_WEBHOOK_SECRET is not set. Set it or provide the secret in the config.'
            );
        }

        $this->config = $config;

        foreach ($config['handlers'] ?? [] as $event => $closure) {
            $this->addHandler($event, $closure);
        }

        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Adds a handler for an event.
     *
     * @param string $event The event to handle.
     * @param Closure $handler The handler for the event.
     * @return static The handler.
     */
    public function addHandler(string $event, Closure $handler): static
    {
        $this->handlers[$event] = $handler;

        return $this;
    }

    /**
     * Handles the incoming request.
     *
     * @param string|null $data The raw data of the webhook notification.
     * @param array|null $config The configuration of the webhook notification.
     *
     * @throws RuntimeException If the webhook notification is invalid.
     */
    public function handle(?string $data = null, ?array $config = null): Notification
    {
        $notification = new Notification($data, $config, $this->logger);

        $this->validate($notification);

        $this->process($notification);

        return $notification;
    }

    /**
     * Validates the notification.
     *
     * @param Notification $notification The notification to validate.
     * @param string $algorithm The algorithm of the webhook notification.
     */
    public function validate(Notification $notification): void
    {
        // Validate hash ID, must match if provided for validation.
        $hashId = $this->config['hash_id'];
        if ($hashId !== null && $notification->getHashId() !== $hashId) {
            throw new RuntimeException('Invalid hash ID (hash_id).');
        }

        // Validate request method, must be POST.
        if ($notification->getRequestMethod() !== 'POST') {
            throw new RuntimeException(sprintf(
                'Invalid request method %s, only POST is allowed.',
                $notification->getRequestMethod()
            ));
        }

        // Validate event, must be set.
        if (empty($notification->getEvent())) {
            throw new RuntimeException(
                'Missing GitHub event (HTTP_X_GITHUB_EVENT).'
            );
        }

        // Validate signature, must match.
        // $secret = $this->config['secret'];
        // $algorithm = $notification->getSignatureAlgorithm();
        // $notificationSignature = $notification->getSignatureValue();
        // $data = json_encode($notification->getPayload());
        // $calculatedSignature = hash_hmac($algorithm, $data, $secret);
        // $this->logger->debug(sprintf(
        //     'Calculated signature: %s',
        //     $calculatedSignature
        // ));
        // $this->logger->debug(sprintf(
        //     'Notification signature: %s',
        //     $notificationSignature
        // ));
        // $this->logger->debug(sprintf(
        //     'Notification payload: %s',
        //     $data
        // ));
        // $this->logger->debug(sprintf(
        //     'Notification algorithm: %s',
        //     $algorithm
        // ));
        // $this->logger->debug(sprintf(
        //     'Notification secret: %s',
        //     $secret
        // ));
        // if (!hash_equals($notificationSignature, $calculatedSignature)) {
        //     throw new RuntimeException(
        //         'Invalid GitHub signature (HTTP_X_HUB_SIGNATURE_256).'
        //     );
        // }
    }

    /**
     * Processes the notification.
     *
     * @param Notification $notification The notification to process.
     */
    public function process(Notification $notification): void
    {
        $event = $notification->getEvent();

        match ($event) {
            'dependabot_alert' => $this->handleDependabotAlert($notification),
            'fork' => $this->handleFork($notification),
            'marketplace_purchase' => $this->handleMarketplacePurchase($notification),
            'page_build' => $this->handlePageBuild($notification),
            'ping' => $this->handlePing($notification),
            'pull_request' => $this->handlePullRequest($notification),
            'push' => $this->handlePush($notification),
            'release' => $this->handleRelease($notification),
            'star' => $this->handleStar($notification),
            'status' => $this->handleStatus($notification),
            'watch' => $this->handleWatch($notification),
            'workflow_run' => $this->handleWorkflowRun($notification),
            default => $this->handleDefault($notification),
        };
    }

    /**
     * Handles the dependabot alert event.
     *
     * @param Notification $notification The notification to handle.
     */
    private function handleDependabotAlert(Notification $notification): void
    {
        if (isset($this->handlers['dependabot_alert'])) {
            $this->handlers['dependabot_alert']($notification);
        } else {
            $this->handleDefault($notification);
        }
    }

    /**
     * Handles the fork event.
     *
     * @param Notification $notification The notification to handle.
     */
    private function handleFork(Notification $notification): void
    {
        if (isset($this->handlers['fork'])) {
            $this->handlers['fork']($notification);
        } else {
            $this->handleDefault($notification);
        }
    }

    /**
     * Handles the marketplace purchase event.
     *
     * @param Notification $notification The notification to handle.
     */
    private function handleMarketplacePurchase(Notification $notification): void
    {
        if (isset($this->handlers['marketplace_purchase'])) {
            $this->handlers['marketplace_purchase']($notification);
        } else {
            $this->handleDefault($notification);
        }
    }

    /**
     * Handles the page build event.
     *
     * @param Notification $notification The notification to handle.
     */
    private function handlePageBuild(Notification $notification): void
    {
        if (isset($this->handlers['page_build'])) {
            $this->handlers['page_build']($notification);
        } else {
            $this->handleDefault($notification);
        }
    }

    /**
     * Handles the ping event.
     *
     * @param Notification $notification The notification to handle.
     */
    private function handlePing(Notification $notification): void
    {
        if (isset($this->handlers['ping'])) {
            $this->handlers['ping']($notification);
        } else {
            $notification->setResponse(sprintf(
                'Ping from %s received.',
                $notification->getPayload()->repository->full_name
            ));
        }
    }

    /**
     * Handles the pull request event.
     *
     * @param Notification $notification The notification to handle.
     */
    private function handlePullRequest(Notification $notification): void
    {
        if (isset($this->handlers['pull_request'])) {
            $this->handlers['pull_request']($notification);
        } else {
            $this->handleDefault($notification);
        }
    }

    /**
     * Handles the push event.
     *
     * @param Notification $notification The notification to handle.
     */
    private function handlePush(Notification $notification): void
    {
        if (isset($this->handlers['push'])) {
            $this->handlers['push']($notification);
        } else {
            $this->handleDefault($notification);
        }
    }

    /**
     * Handles the release event.
     *
     * @param Notification $notification The notification to handle.
     */
    private function handleRelease(Notification $notification): void
    {
        if (isset($this->handlers['release'])) {
            $this->handlers['release']($notification);
        } else {
            $this->handleDefault($notification);
        }
    }

    /**
     * Handles the star event.
     *
     * @param Notification $notification The notification to handle.
     */
    private function handleStar(Notification $notification): void
    {
        if (isset($this->handlers['star'])) {
            $this->handlers['star']($notification);
        } else {
            $this->handleDefault($notification);
        }
    }

    /**
     * Handles the status event.
     *
     * @param Notification $notification The notification to handle.
     */
    private function handleStatus(Notification $notification): void
    {
        if (isset($this->handlers['status'])) {
            $this->handlers['status']($notification);
        } else {
            $this->handleDefault($notification);
        }
    }

    /**
     * Handles the watch event.
     *
     * @param Notification $notification The notification to handle.
     */
    private function handleWatch(Notification $notification): void
    {
        if (isset($this->handlers['watch'])) {
            $this->handlers['watch']($notification);
        } else {
            $this->handleDefault($notification);
        }
    }

    /**
     * Handles the workflow run event.
     *
     * @param Notification $notification The notification to handle.
     */
    private function handleWorkflowRun(Notification $notification): void
    {
        if (isset($this->handlers['workflow_run'])) {
            $this->handlers['workflow_run']($notification);
        } else {
            $this->handleDefault($notification);
        }
    }

    /**
     * Handles the default event.
     *
     * @param Notification $notification The notification to handle.
     */
    private function handleDefault(Notification $notification): void
    {
        if (isset($this->handlers['default'])) {
            $this->handlers['default']($notification);
        } else {
            $notification->setResponse(sprintf(
                'Unhandled event "%s" received, no handler is set.',
                $notification->getEvent()
            ));
        }
    }
}
