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
     * The code of the response.
     *
     * @var int
     */
    private const CODE_SUCCESS = 200;

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
     * Constructs the response.
     *
     * @param string|array $data The data of the response.
     */
    public function __construct(private readonly string|array $data)
    {
    }

    /**
     * Gets the data of the response.
     *
     * @return string|array The data of the response.
     */
    public function getData(): string|array
    {
        return $this->data;
    }

    /**
     * Gets the code of the response.
     *
     * @return int The code of the response.
     */
    public function getCode(): int
    {
        if (is_array($this->data)) {
            return $this->data['code'] ?? self::CODE_SUCCESS;
        }

        return self::CODE_SUCCESS;
    }

    /**
     * Gets the status of the response.
     *
     * @return string The status of the response.
     */
    public function getStatus(): string
    {
        if (is_array($this->data)) {
            return $this->data['status']
                ?? (
                    $this->getCode() === self::CODE_SUCCESS
                        ? self::STATUS_SUCCESS
                        : self::STATUS_ERROR
                )
            ;
        }

        return self::STATUS_SUCCESS;
    }

    /**
     * Gets the array representation of the response.
     *
     * @return array The array representation of the response.
     */
    public function toArray(): array
    {
        $data = is_string($this->data)
            ? ['message' => $this->data]
            : (is_array($this->data) && isset($this->data['data'])
                ? $this->data['data']
                : throw new RuntimeException('Key "data" is required.')
            )
        ;

        return [
            'code' => $this->getCode(),
            'status' => $this->getStatus(),
            'data' => $data,
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
