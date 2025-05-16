<?php

namespace MCP\Controllers;

abstract class BaseController
{
    protected $model;
    protected $presenter;
    protected $logger;

    public function __construct($model, $presenter, \Monolog\Logger $logger)
    {
        $this->model = $model;
        $this->presenter = $presenter;
        $this->logger = $logger;
    }

    protected function handleRequest($method, $params = [])
    {
        try {
            if (!method_exists($this, $method)) {
                throw new \Exception("Method {$method} not found");
            }

            $result = $this->$method($params);
            return $this->presenter->formatResponse($result);
        } catch (\Exception $e) {
            $this->logger->error("Error in {$method}: " . $e->getMessage());
            return $this->presenter->formatError($e->getMessage());
        }
    }

    protected function validateInput($data, $rules)
    {
        $errors = [];
        foreach ($rules as $field => $rule) {
            if (!isset($data[$field]) || !$this->validateRule($data[$field], $rule)) {
                $errors[$field] = "Invalid {$field}";
            }
        }
        return empty($errors) ? true : $errors;
    }

    private function validateRule($value, $rule)
    {
        switch ($rule) {
            case 'required':
                return !empty($value);
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'numeric':
                return is_numeric($value);
            default:
                return true;
        }
    }
} 