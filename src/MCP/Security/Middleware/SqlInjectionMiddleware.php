<?php

namespace MCP\Security\Middleware;

use MCP\Core\Middleware;
use MCP\Exceptions\SqlInjectionException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * SQL Injection Middleware
 * 
 * Protects against SQL injection attacks by validating and sanitizing SQL queries.
 * 
 * @package MCP\Security\Middleware
 */
class SqlInjectionMiddleware extends Middleware
{
    protected array $config;
    protected array $excludedRoutes;
    protected array $sqlKeywords = [
        'SELECT', 'FROM', 'WHERE', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER',
        'CREATE', 'TRUNCATE', 'UNION', 'JOIN', 'HAVING', 'GROUP BY', 'ORDER BY'
    ];

    /**
     * Initialize the middleware
     */
    public function __construct(array $config = [], array $excludedRoutes = [])
    {
        $this->config = array_merge([
            'validate_queries' => true,
            'enforce_prepared_statements' => true,
            'validate_input' => true,
            'log_violations' => true,
            'block_violations' => true,
            'allowed_operators' => ['=', '>', '<', '>=', '<=', '!=', 'LIKE', 'IN', 'BETWEEN'],
            'max_query_length' => 10000,
            'max_parameters' => 100,
        ], $config);

        $this->excludedRoutes = $excludedRoutes;
    }

    /**
     * Process the request
     * 
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws SqlInjectionException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Skip SQL injection protection for excluded routes
        if ($this->isExcludedRoute($request)) {
            return $handler->handle($request);
        }

        // Validate input if enabled
        if ($this->config['validate_input']) {
            $request = $this->validateInput($request);
        }

        // Process the request
        $response = $handler->handle($request);

        return $response;
    }

    /**
     * Check if the route is excluded from SQL injection protection
     * 
     * @param ServerRequestInterface $request
     * @return bool
     */
    protected function isExcludedRoute(ServerRequestInterface $request): bool
    {
        $route = $request->getAttribute('route');
        if (!$route) {
            return false;
        }

        return in_array($route->getName(), $this->excludedRoutes);
    }

    /**
     * Validate input data for potential SQL injection
     * 
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     * @throws SqlInjectionException
     */
    protected function validateInput(ServerRequestInterface $request): ServerRequestInterface
    {
        // Validate query parameters
        $queryParams = $request->getQueryParams();
        $validatedQueryParams = $this->validateArray($queryParams);
        $request = $request->withQueryParams($validatedQueryParams);

        // Validate parsed body
        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody)) {
            $validatedBody = $this->validateArray($parsedBody);
            $request = $request->withParsedBody($validatedBody);
        }

        // Validate cookies
        $cookies = $request->getCookieParams();
        $validatedCookies = $this->validateArray($cookies);
        $request = $request->withCookieParams($validatedCookies);

        return $request;
    }

    /**
     * Validate an array of data for potential SQL injection
     * 
     * @param array $data
     * @return array
     * @throws SqlInjectionException
     */
    protected function validateArray(array $data): array
    {
        $validated = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $validated[$key] = $this->validateArray($value);
            } else {
                $validated[$key] = $this->validateValue($value);
            }
        }
        return $validated;
    }

    /**
     * Validate a single value for potential SQL injection
     * 
     * @param mixed $value
     * @return mixed
     * @throws SqlInjectionException
     */
    protected function validateValue($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        // Check for SQL keywords
        $upperValue = strtoupper($value);
        foreach ($this->sqlKeywords as $keyword) {
            if (strpos($upperValue, $keyword) !== false) {
                if ($this->config['log_violations']) {
                    // Log the violation
                    error_log("SQL Injection attempt detected: {$value}");
                }
                if ($this->config['block_violations']) {
                    throw new SqlInjectionException("Potential SQL injection detected");
                }
            }
        }

        // Check for common SQL injection patterns
        $patterns = [
            '/\b(OR|AND)\s+\d+\s*=\s*\d+/i',
            '/\b(OR|AND)\s+\d+\s*=\s*\d+\s*--/i',
            '/\b(OR|AND)\s+\d+\s*=\s*\d+\s*#/i',
            '/\b(OR|AND)\s+\d+\s*=\s*\d+\s*\/\*/i',
            '/\b(OR|AND)\s+\d+\s*=\s*\d+\s*\*\//i',
            '/\b(OR|AND)\s+\d+\s*=\s*\d+\s*;/i',
            '/\b(OR|AND)\s+\d+\s*=\s*\d+\s*$/i',
            '/\b(OR|AND)\s+\d+\s*=\s*\d+\s*$/i',
            '/\b(OR|AND)\s+\d+\s*=\s*\d+\s*$/i',
            '/\b(OR|AND)\s+\d+\s*=\s*\d+\s*$/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                if ($this->config['log_violations']) {
                    // Log the violation
                    error_log("SQL Injection pattern detected: {$value}");
                }
                if ($this->config['block_violations']) {
                    throw new SqlInjectionException("Potential SQL injection pattern detected");
                }
            }
        }

        return $value;
    }

    /**
     * Validate a SQL query
     * 
     * @param string $query
     * @param array $parameters
     * @return bool
     * @throws SqlInjectionException
     */
    public function validateQuery(string $query, array $parameters = []): bool
    {
        // Check query length
        if (strlen($query) > $this->config['max_query_length']) {
            throw new SqlInjectionException("Query exceeds maximum length");
        }

        // Check parameter count
        if (count($parameters) > $this->config['max_parameters']) {
            throw new SqlInjectionException("Too many parameters in query");
        }

        // Check for prepared statement usage
        if ($this->config['enforce_prepared_statements']) {
            $placeholders = substr_count($query, '?');
            if ($placeholders !== count($parameters)) {
                throw new SqlInjectionException("Parameter count mismatch");
            }
        }

        // Check for SQL keywords
        $upperQuery = strtoupper($query);
        foreach ($this->sqlKeywords as $keyword) {
            if (strpos($upperQuery, $keyword) !== false) {
                // Validate the context of the keyword
                if (!$this->isValidKeywordContext($upperQuery, $keyword)) {
                    if ($this->config['log_violations']) {
                        error_log("Invalid SQL keyword context: {$query}");
                    }
                    if ($this->config['block_violations']) {
                        throw new SqlInjectionException("Invalid SQL keyword context");
                    }
                }
            }
        }

        return true;
    }

    /**
     * Check if a SQL keyword is used in a valid context
     * 
     * @param string $query
     * @param string $keyword
     * @return bool
     */
    protected function isValidKeywordContext(string $query, string $keyword): bool
    {
        // Add context validation logic here
        // This is a simplified example - you would want more sophisticated validation
        $validContexts = [
            'SELECT' => '/^SELECT\s+.+FROM/i',
            'FROM' => '/FROM\s+[a-zA-Z0-9_]+/i',
            'WHERE' => '/WHERE\s+[a-zA-Z0-9_]+\s*(=|>|<|>=|<=|!=|LIKE|IN|BETWEEN)/i',
            'INSERT' => '/^INSERT\s+INTO/i',
            'UPDATE' => '/^UPDATE\s+[a-zA-Z0-9_]+\s+SET/i',
            'DELETE' => '/^DELETE\s+FROM/i',
        ];

        if (isset($validContexts[$keyword])) {
            return preg_match($validContexts[$keyword], $query) === 1;
        }

        return true;
    }
} 