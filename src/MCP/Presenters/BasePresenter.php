<?php

namespace MCP\Presenters;

abstract class BasePresenter
{
    protected $logger;

    public function __construct(\Monolog\Logger $logger)
    {
        $this->logger = $logger;
    }

    public function formatResponse($data)
    {
        return [
            'success' => true,
            'data' => $data,
            'timestamp' => time()
        ];
    }

    public function formatError($message)
    {
        return [
            'success' => false,
            'error' => $message,
            'timestamp' => time()
        ];
    }

    public function formatValidationError($errors)
    {
        return [
            'success' => false,
            'error' => 'Validation failed',
            'errors' => $errors,
            'timestamp' => time()
        ];
    }

    protected function formatData($data)
    {
        if (is_array($data)) {
            return array_map(function($item) {
                return $this->formatData($item);
            }, $data);
        }
        return $data;
    }
} 