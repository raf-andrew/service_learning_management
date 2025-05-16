<?php

declare(strict_types=1);

namespace MCP\Presenters;

use MCP\Core\Http\Response;
use MCP\Core\Logger\Logger;
use MCP\Core\Config\Config;

abstract class Presenter
{
    protected Response $response;
    protected Logger $logger;
    protected Config $config;
    protected array $data = [];
    protected array $hidden = [];
    protected array $casts = [];

    public function __construct(
        Response $response,
        Logger $logger,
        Config $config
    ) {
        $this->response = $response;
        $this->logger = $logger;
        $this->config = $config;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function render(string $view, array $data = []): Response
    {
        $this->data = array_merge($this->data, $data);
        
        $viewPath = $this->getViewPath($view);
        if (!file_exists($viewPath)) {
            $this->logger->error("View file not found: {$viewPath}");
            return $this->error('View not found', 500);
        }

        ob_start();
        extract($this->data);
        require $viewPath;
        $content = ob_get_clean();

        $this->response->setHeader('Content-Type', 'text/html');
        $this->response->setBody($content);
        $this->response->setStatusCode(200);

        return $this->response;
    }

    public function json(array $data, int $status = 200): Response
    {
        $this->response->setHeader('Content-Type', 'application/json');
        $this->response->setBody(json_encode($data, JSON_PRETTY_PRINT));
        $this->response->setStatusCode($status);

        return $this->response;
    }

    public function error(string $message, int $status = 400): Response
    {
        return $this->json([
            'error' => [
                'message' => $message,
                'status' => $status
            ]
        ], $status);
    }

    public function success(array $data = [], int $status = 200): Response
    {
        return $this->json([
            'success' => true,
            'data' => $data
        ], $status);
    }

    public function notFound(string $message = 'Resource not found'): Response
    {
        return $this->error($message, 404);
    }

    public function unauthorized(string $message = 'Unauthorized'): Response
    {
        return $this->error($message, 401);
    }

    public function forbidden(string $message = 'Forbidden'): Response
    {
        return $this->error($message, 403);
    }

    public function serverError(string $message = 'Internal server error'): Response
    {
        return $this->error($message, 500);
    }

    protected function getViewPath(string $view): string
    {
        $view = str_replace('.', '/', $view);
        return $this->config->get('app.views_path', 'views') . '/' . $view . '.php';
    }

    protected function log(string $level, string $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }

    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config->get($key, $default);
    }

    public function toArray(): array
    {
        $result = $this->data;
        $result = $this->castAttributes($result);
        return array_diff_key($result, array_flip($this->hidden));
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function all(): array
    {
        return $this->data;
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->toArray(), array_flip($keys));
    }

    public function except(array $keys): array
    {
        return array_diff_key($this->toArray(), array_flip($keys));
    }

    protected function castAttributes(array $data): array
    {
        foreach ($this->casts as $key => $type) {
            if (isset($data[$key])) {
                $data[$key] = match ($type) {
                    'int', 'integer' => (int) $data[$key],
                    'float', 'double' => (float) $data[$key],
                    'string' => (string) $data[$key],
                    'bool', 'boolean' => (bool) $data[$key],
                    'array' => json_decode($data[$key], true),
                    'json' => json_decode($data[$key], true),
                    'date' => new \DateTime($data[$key]),
                    default => $data[$key]
                };
            }
        }

        return $data;
    }
} 