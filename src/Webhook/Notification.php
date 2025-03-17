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
     * Gets the raw data of the webhook notification.
     *
     * @return string The raw data of the webhook notification.
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * Gets the hash ID of the webhook notification.
     *
     * @return string|null The hash ID of the webhook notification.
     */
    public function getHashId(): ?string
    {
        return $this->config['hash_id'];
    }

    /**
     * Gets the request method of the webhook notification.
     *
     * @return string The request method of the webhook notification.
     *
     * @throws RuntimeException If the request method is missing.
     */
    public function getRequestMethod(): string
    {
        return $this->config['REQUEST_METHOD']
            ?? throw new RuntimeException(
                'Missing request method (REQUEST_METHOD).'
            )
        ;
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
            ?? throw new RuntimeException(
                'Missing GitHub event (HTTP_X_GITHUB_EVENT).'
            )
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
            ?? throw new RuntimeException(
                'Missing GitHub delivery (HTTP_X_GITHUB_DELIVERY).'
            )
        ;
    }

    /**
     * Gets the signature header of the webhook notification.
     *
     * @return string The signature header of the webhook notification.
     *
     * @throws RuntimeException If the signature header is missing.
     */
    public function getSignatureHeader(): string
    {
        return $this->config['HTTP_X_HUB_SIGNATURE_256']
            ?? throw new RuntimeException(
                'Missing GitHub signature (HTTP_X_HUB_SIGNATURE_256).'
            )
        ;
    }

    /**
     * Gets the signature algorithm of the webhook notification.
     *
     * @return string The signature algorithm of the webhook notification.
     *
     * @throws RuntimeException If the signature algorithm is missing.
     */
    public function getSignatureAlgorithm(): string
    {
        [$algorithm, $signature] = explode('=', $this->getSignatureHeader());

        return $algorithm;
    }

    /**
     * Gets the signature value of the webhook notification.
     *
     * @return string The signature value of the webhook notification.
     *
     * @throws RuntimeException If the signature is missing.
     */
    public function getSignatureValue(): string
    {
        [$algorithm, $signature] = explode('=', $this->getSignatureHeader());

        return $signature;
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
