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

use RuntimeException;
use stdClass;

/**
 * Class that represents a GitHub webhook notification.
 */
final class Notification
{
    /**
     * The raw data of the webhook notification.
     *
     * @var string
     */
    private string $data;

    /**
     * The configuration of the webhook notification.
     *
     * @var array
     */
    private array $config;

    /**
     * The payload of the webhook notification.
     *
     * @var stdClass
     */
    private stdClass $payload;

    /**
     * The response of the webhook notification.
     *
     * @var Response
     */
    private Response $response;

    /**
     * Constructor.
     *
     * @param string|null $data The raw data of the webhook notification.
     * @param array|null $config The configuration of the webhook notification.
     */
    public function __construct(?string $data = null, ?array $config = null)
    {
        $this->data = $data ?? file_get_contents('php://input');

        $this->config = array_merge([
            'hash_id' => null,
            'REQUEST_METHOD' => null,
            'HTTP_X_GITHUB_EVENT' => null,
            'HTTP_X_GITHUB_DELIVERY' => null,
            'HTTP_X_HUB_SIGNATURE_256' => null,
        ], $config ?? array_merge($_GET, $_SERVER));
    }

    /**
     * Validates the webhook notification.
     *
     * @param string $secret The secret of the webhook notification.
     * @param string|null $hashId The hash ID of the webhook notification.
     * @param string $algorithm The algorithm of the webhook notification.
     *
     * @throws RuntimeException If the webhook notification is invalid.
     */
    public function validate(
        string $secret,
        ?string $hashId = null,
        string $algorithm = 'sha256'
    ): void {
        // Validate hash ID, must match if provided for validation.
        if ($hashId !== null && $this->config['hash_id'] !== $hashId) {
            throw new RuntimeException('Invalid hash ID (hash_id).');
        }

        // Validate request method, must be POST.
        if ($this->config['REQUEST_METHOD'] !== 'POST') {
            throw new RuntimeException(sprintf(
                'Invalid request method %s, only POST is allowed.',
                $this->config['REQUEST_METHOD']
            ));
        }

        // Validate event, must be set.
        if (empty($this->config['HTTP_X_GITHUB_EVENT'])) {
            throw new RuntimeException(
                'Missing GitHub event (HTTP_X_GITHUB_EVENT).'
            );
        }

        // Validate signature, must be set.
        $signature = $this->config['HTTP_X_HUB_SIGNATURE_256'] ?? null;
        if (empty($signature)) {
            throw new RuntimeException(
                'Missing GitHub signature (HTTP_X_HUB_SIGNATURE_256).'
            );
        }

        // Validate signature, must match.
        $calculatedSignature = $algorithm . '='
            . hash_hmac($algorithm, $this->data, $secret)
        ;
        if (!hash_equals($signature, $calculatedSignature)) {
            throw new RuntimeException(
                'Invalid GitHub signature (HTTP_X_HUB_SIGNATURE_256).'
            );
        }
    }

    /**
     * Gets the event of the webhook notification.
     *
     * @return string The event of the webhook notification.
     *
     * @throws RuntimeException If the event is missing.
     */
    public function getEvent(): string
    {
        return $this->config['HTTP_X_GITHUB_EVENT']
            ?? throw new RuntimeException('Missing GitHub event (HTTP_X_GITHUB_EVENT).')
        ;
    }

    /**
     * Gets the delivery ID of the webhook notification.
     *
     * @return string The delivery ID of the webhook notification.
     *
     * @throws RuntimeException If the delivery ID is missing.
     */
    public function getDeliveryId(): string
    {
        return $this->config['HTTP_X_GITHUB_DELIVERY']
            ?? throw new RuntimeException('Missing GitHub delivery (HTTP_X_GITHUB_DELIVERY).')
        ;
    }

    /**
     * Gets the payload of the webhook notification.
     *
     * @return stdClass The payload of the webhook notification.
     *
     * @throws RuntimeException If the payload is missing.
     */
    public function getPayload(): stdClass
    {
        if (!isset($this->payload)) {
            $this->payload = json_decode(
                $this->data,
                false,
                512,
                JSON_THROW_ON_ERROR
            );
        }

        return $this->payload;
    }

    /**
     * Gets the response of the webhook notification.
     *
     * @return Response The response of the webhook notification.
     *
     * @throws RuntimeException If the response is missing.
     */
    public function getResponse(): Response
    {
        if (!isset($this->response)) {
            throw new RuntimeException('Notification has no response.');
        }

        return $this->response;
    }

    /**
     * Sets the response of the webhook notification.
     *
     * @param Response|string|array $response The response of the webhook notification.
     *
     * @return static The notification.
     */
    public function setResponse(Response|string|array $response): static
    {
        $this->response = $response instanceof Response
            ? $response
            : new Response($response)
        ;

        return $this;
    }
}
