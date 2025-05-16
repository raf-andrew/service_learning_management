# XSS Protection System

## Overview

The XSS (Cross-Site Scripting) Protection System is a comprehensive security solution designed to prevent XSS attacks by sanitizing input and encoding output. It provides multiple layers of protection through middleware, security headers, and content sanitization.

## Components

### XSS Middleware

The `XssMiddleware` class provides the core XSS protection functionality:

- Input sanitization for query parameters, request body, and cookies
- Output encoding for HTML responses
- Security headers management
- Route exclusion support
- Configurable allowed HTML tags

### XSS Exception

The `XssException` class handles XSS-related errors:

- Custom error messages
- HTTP status code 400 (Bad Request)
- Exception chaining support

## Usage

### Basic Implementation

```php
use MCP\Security\Middleware\XssMiddleware;

// Create middleware with default configuration
$middleware = new XssMiddleware();

// Create middleware with custom configuration
$middleware = new XssMiddleware([
    'csp' => [
        'default-src' => ["'self'"],
        'script-src' => ["'self'", "'unsafe-inline'"],
        'style-src' => ["'self'", "'unsafe-inline'"]
    ],
    'xss_protection' => true,
    'allowed_tags' => ['p', 'br', 'strong', 'em']
], ['api.*', 'webhook.*']);
```

### Security Headers

The middleware automatically adds the following security headers:

- Content-Security-Policy (CSP)
- X-XSS-Protection
- X-Content-Type-Options
- X-Frame-Options

### Content Sanitization

The middleware sanitizes:

1. Query parameters
2. Request body
3. Cookies
4. HTML response content

## Security Features

1. **Input Sanitization**
   - Removes potentially dangerous HTML tags
   - Preserves allowed HTML tags
   - Handles nested HTML structures

2. **Output Encoding**
   - HTML entity encoding
   - Preserves allowed HTML tags
   - Handles special characters

3. **Security Headers**
   - Content Security Policy (CSP)
   - XSS Protection
   - Content Type Options
   - Frame Options

4. **Route Exclusion**
   - Configurable excluded routes
   - Pattern-based matching
   - API and webhook support

## Testing

The XSS protection system includes comprehensive test coverage:

```bash
# Run XSS middleware tests
vendor/bin/phpunit tests/MCP/Security/Middleware/XssMiddlewareTest.php
```

Test cases cover:
- Successful XSS protection
- Excluded routes
- Non-HTML responses
- Allowed HTML tags
- Security headers

## Error Handling

The system throws `XssException` with:
- HTTP status code 400
- Custom error messages
- Exception chaining

## Best Practices

1. **Configuration**
   - Define strict CSP rules
   - Limit allowed HTML tags
   - Configure route exclusions carefully

2. **Implementation**
   - Apply middleware early in the stack
   - Test with various input types
   - Monitor security headers

3. **Maintenance**
   - Regular security audits
   - Update CSP rules as needed
   - Review excluded routes

## Related Documentation

- [Authentication System](authentication.md)
- [Authorization System](authorization.md)
- [CSRF Protection](csrf.md)
- [Security Best Practices](../security/best-practices.md) 