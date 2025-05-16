<?php

declare(strict_types=1);

namespace MCP\Controllers;

use MCP\Core\Config\Config;
use MCP\Core\Logger\Logger;
use MCP\Core\Validation\Validator;

abstract class Controller
{
    protected array $validationErrors = [];

    public function __construct(
        protected Config $config,
        protected Logger $logger,
        protected Validator $validator
    ) {}

    protected function validate(array $rules): bool
    {
        $this->validationErrors = $this->validator->validate($_REQUEST, $rules);
        return empty($this->validationErrors);
    }

    protected function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function error(string $message, int $status = 400): void
    {
        $this->json([
            'error' => true,
            'message' => $message
        ], $status);
    }

    protected function success(array $data = [], int $status = 200): void
    {
        $this->json([
            'error' => false,
            'data' => $data
        ], $status);
    }

    protected function notFound(string $message = 'Resource not found'): void
    {
        $this->error($message, 404);
    }

    protected function unauthorized(string $message = 'Unauthorized'): void
    {
        $this->error($message, 401);
    }

    protected function forbidden(string $message = 'Forbidden'): void
    {
        $this->error($message, 403);
    }

    protected function serverError(string $message = 'Internal server error'): void
    {
        $this->error($message, 500);
    }

    protected function log(string $level, string $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }

    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config->get($key, $default);
    }
} 