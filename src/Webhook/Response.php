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

use JsonSerializable;
use RuntimeException;

/**
 * Class that represents a response.
 */
final class Response implements JsonSerializable
{
    /**
     * The success code of the response.
     *
     * @var int
     */
    private const CODE_SUCCESS = 0;

    /**
     * The default success code of the HTTP response.
     *
     * @var int
     */
    private const HTTP_CODE_SUCCESS = 200;

    /**
     * The default error code of the HTTP response.
     *
     * @var int
     */
    private const HTTP_CODE_ERROR = 400;
    /**
     * The status of the response.
     *
     * @var string
     */
    private const STATUS_SUCCESS = 'success';

    /**
     * The status of the response.
     *
     * @var string
     */
    private const STATUS_ERROR = 'error';

    /**
     * The data of the response.
     *
     * @var array
     */
    private array $response;

    /**
     * Constructs the response.
     *
     * @param string|array $response The response.
     */
    public function __construct(string|array $response)
    {
        // If the response is a string, convert it to an array where the string
        // is the value of the key 'data'.
        if (is_string($response)) {
            $response = [
                'data' => $response,
            ];
        }

        // If the response is an array, check that the key 'data' exists.
        if (!isset($response['data'])) {
            throw new RuntimeException(
                'Key "data" is required in the response when is an array.'
            );
        }

        // Set the response.
        $this->response = $response;
    }

    /**
     * Gets the data of the response.
     *
     * @return string|array The data of the response.
     */
    public function getData(): string|array
    {
        return $this->response['data'];
    }

    /**
     * Gets the code of the response.
     *
     * @return int The code of the response.
     */
    public function getCode(): int
    {
        return $this->response['code'] ?? self::CODE_SUCCESS;
    }

    /**
     * Gets the HTTP code of the response.
     *
     * @return int The HTTP code of the response.
     */
    public function getHttpCode(): int
    {
        return $this->response['http_code']
            ?? (
                $this->getCode() === self::CODE_SUCCESS
                    ? self::HTTP_CODE_SUCCESS
                    : self::HTTP_CODE_ERROR
            )
        ;
    }

    /**
     * Gets the status of the response.
     *
     * @return string The status of the response.
     */
    public function getStatus(): string
    {
        return $this->response['status']
            ?? (
                $this->getCode() === self::CODE_SUCCESS
                    ? self::STATUS_SUCCESS
                    : self::STATUS_ERROR
            )
        ;
    }

    /**
     * Gets the array representation of the response.
     *
     * @return array The array representation of the response.
     */
    public function toArray(): array
    {
        return [
            'code' => $this->getCode(),
            'http_code' => $this->getHttpCode(),
            'status' => $this->getStatus(),
            'data' => $this->getData(),
        ];
    }

    /**
     * Gets the JSON serializable representation of the response.
     *
     * @return array The JSON serializable representation of the response.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
