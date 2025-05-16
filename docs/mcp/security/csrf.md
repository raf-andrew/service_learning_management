# MCP CSRF Protection System

## Overview

The MCP CSRF (Cross-Site Request Forgery) Protection System provides robust protection against CSRF attacks by implementing token-based validation for all non-safe HTTP methods. The system is designed to be secure, configurable, and easy to integrate with existing applications.

## Components

### CSRF Middleware

The `CsrfMiddleware` class is the core component of the CSRF protection system. It implements the following features:

- Token generation and validation
- Cookie-based token storage
- Header-based token transmission
- Safe method detection
- Route exclusion support
- Configurable token parameters

### CSRF Exception

The `CsrfException` class handles all CSRF-related errors, including:

- Missing tokens
- Token mismatches
- Missing cookies
- Invalid token formats

## Usage

### Basic Implementation

```php
use MCP\Security\Middleware\CsrfMiddleware;

// Create middleware with default configuration
$middleware = new CsrfMiddleware();

// Add middleware to your application
$app->add($middleware);
```

### Configuration

The middleware can be configured with custom settings:

```php
$config = [
    'token_length' => 32,
    'token_name' => 'csrf_token',
    'cookie' => [
        'name' => 'csrf_token',
        'expire' => 7200,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true
    ]
];

$excludedRoutes = ['api.*', 'webhook.*'];

$middleware = new CsrfMiddleware($config, $excludedRoutes);
```

### Client-Side Integration

Include the CSRF token in your forms:

```html
<form method="POST" action="/submit">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
    <!-- Form fields -->
</form>
```

Or include it in AJAX requests:

```javascript
fetch('/api/endpoint', {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': document.cookie.match(/csrf_token=([^;]+)/)[1]
    },
    body: JSON.stringify(data)
});
```

## Security Features

### Token Generation

- Cryptographically secure random tokens
- Configurable token length
- Unique tokens per session

### Token Validation

- Double-submit cookie pattern
- Token comparison in constant time
- Automatic token rotation

### Cookie Security

- Secure flag
- HTTP-only flag
- Configurable domain and path
- Automatic expiration

### Request Protection

- Protection for all non-safe methods
- Configurable route exclusions
- Header and form field support

## Testing

The CSRF protection system includes comprehensive test coverage:

- Successful validation
- Missing token scenarios
- Token mismatch scenarios
- Safe method handling
- Excluded route handling

Run the tests using:

```bash
vendor/bin/phpunit tests/MCP/Security/Middleware/CsrfMiddlewareTest.php
```

## Error Handling

The system provides clear error messages for various scenarios:

- "CSRF token missing."
- "CSRF cookie missing."
- "CSRF token mismatch."

All errors are thrown as `CsrfException` with appropriate HTTP status codes.

## Best Practices

1. Always include CSRF protection for state-changing operations
2. Use secure and HTTP-only cookies
3. Implement proper error handling
4. Rotate tokens regularly
5. Exclude only necessary routes
6. Use HTTPS in production
7. Keep token length sufficient (32+ bytes recommended)

## Related Documentation

- [Authentication System](authentication.md)
- [Authorization System](authorization.md)
- [Security Best Practices](../security/best-practices.md) 