# SQL Injection Protection

## Overview

The SQL Injection Protection system is designed to prevent SQL injection attacks by validating and sanitizing SQL queries, enforcing prepared statements, and implementing input validation. This system provides multiple layers of protection to ensure database security.

## Components

### SqlInjectionMiddleware

The core component that provides SQL injection protection through:

- Input validation and sanitization
- Query validation
- Prepared statement enforcement
- Route exclusion support
- Configurable security settings

### SqlInjectionException

A custom exception class that handles SQL injection related errors with:

- Custom error messages
- HTTP status code 400 (Bad Request)
- Detailed error information

## Usage

### Basic Implementation

```php
use MCP\Security\Middleware\SqlInjectionMiddleware;

// Create middleware with default configuration
$middleware = new SqlInjectionMiddleware();

// Add to middleware stack
$app->add($middleware);
```

### Custom Configuration

```php
$config = [
    'validate_queries' => true,
    'enforce_prepared_statements' => true,
    'validate_input' => true,
    'log_violations' => true,
    'block_violations' => true,
    'allowed_operators' => ['=', '>', '<', '>=', '<=', '!=', 'LIKE', 'IN', 'BETWEEN'],
    'max_query_length' => 10000,
    'max_parameters' => 100,
];

$excludedRoutes = ['api_docs', 'health_check'];

$middleware = new SqlInjectionMiddleware($config, $excludedRoutes);
```

### Query Validation

```php
// Validate a query
$query = "SELECT * FROM users WHERE id = ?";
$parameters = [1];

try {
    $middleware->validateQuery($query, $parameters);
    // Query is safe to execute
} catch (SqlInjectionException $e) {
    // Handle SQL injection attempt
}
```

## Security Features

1. **Input Validation**
   - Validates query parameters
   - Validates request body
   - Validates cookies
   - Checks for SQL keywords
   - Detects common SQL injection patterns

2. **Query Validation**
   - Enforces maximum query length
   - Limits parameter count
   - Validates SQL keyword context
   - Ensures proper query structure

3. **Prepared Statements**
   - Enforces parameterized queries
   - Validates parameter count
   - Prevents direct value injection

4. **Route Exclusion**
   - Allows specific routes to bypass protection
   - Useful for API endpoints or health checks

5. **Configurable Settings**
   - Adjustable security levels
   - Customizable validation rules
   - Configurable logging and blocking

## Testing

The SQL injection protection system includes comprehensive test coverage:

```bash
# Run tests
vendor/bin/phpunit tests/MCP/Security/Middleware/SqlInjectionMiddlewareTest.php
```

Test cases cover:
- Successful protection scenarios
- Malicious query detection
- Pattern matching
- Query validation
- Configuration options
- Route exclusion
- Error handling

## Error Handling

The system throws `SqlInjectionException` with specific error messages:

- "Potential SQL injection detected"
- "Potential SQL injection pattern detected"
- "Query exceeds maximum length"
- "Too many parameters in query"
- "Parameter count mismatch"
- "Invalid SQL keyword context"

## Best Practices

1. **Configuration**
   - Enable all security features in production
   - Configure appropriate query length limits
   - Set reasonable parameter limits
   - Enable violation logging
   - Block violations in production

2. **Implementation**
   - Always use prepared statements
   - Validate all user input
   - Sanitize query parameters
   - Use parameterized queries
   - Implement proper error handling

3. **Maintenance**
   - Regularly update SQL keyword list
   - Monitor violation logs
   - Review excluded routes
   - Update security patterns
   - Test with new attack vectors

## Related Documentation

- [Authentication System](authentication.md)
- [Authorization System](authorization.md)
- [CSRF Protection](csrf.md)
- [XSS Protection](xss.md) 