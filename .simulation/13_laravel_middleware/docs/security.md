# Security Documentation

## Overview

This document outlines the security features implemented in the middleware layer of our application. The security implementation includes:

1. Security Headers
2. Permission-based Access Control
3. Security Logging
4. Configuration Management

## Security Headers

The application implements a comprehensive set of security headers to protect against various web vulnerabilities:

### Available Headers

- `X-Content-Type-Options`: Prevents MIME type sniffing
- `X-Frame-Options`: Prevents clickjacking attacks
- `X-XSS-Protection`: Enables browser's XSS filtering
- `Referrer-Policy`: Controls referrer information
- `Content-Security-Policy`: Controls resource loading
- `X-Download-Options`: Prevents automatic file downloads
- `X-UA-Compatible`: Disables IE compatibility mode
- `Strict-Transport-Security`: Enforces HTTPS
- `Cache-Control` & `Pragma`: Controls caching behavior
- `Permissions-Policy`: Controls browser features
- `Cross-Origin-Resource-Policy`: Controls cross-origin resource loading
- `Cross-Origin-Embedder-Policy`: Controls cross-origin embedding
- `Cross-Origin-Opener-Policy`: Controls cross-origin window interactions

### Configuration

Security headers can be configured in `config/security.php`:

```php
'headers' => [
    'X-Frame-Options' => 'DENY',
    'Content-Security-Policy' => env('CSP_POLICY', "default-src 'self'"),
    // ...
]
```

Environment variables can override default values:
- `CSP_POLICY`: Content Security Policy
- `HSTS_MAX_AGE`: HSTS max-age value
- `PERMISSIONS_POLICY`: Permissions Policy

## Permission-based Access Control

The application implements a flexible permission-based access control system:

### Features

- Role-based permissions
- Direct user permissions
- Permission inheritance
- Multiple permission requirements
- Permission caching

### Usage

```php
// In routes/web.php
Route::middleware('permission:manage_users,view_reports')->group(function () {
    // Protected routes
});

// In controllers
public function __construct()
{
    $this->middleware('permission:manage_users');
}
```

### Default Roles and Permissions

- Super Admin: Full access
- Admin: User management, reports, content
- Manager: Reports, content
- User: Basic access

## Security Logging

The application includes a comprehensive security logging system:

### Features

- Configurable log levels
- Custom log channels
- Contextual information
- Request details
- User information

### Configuration

```php
'logging' => [
    'enabled' => env('SECURITY_LOGGING_ENABLED', true),
    'channel' => env('SECURITY_LOG_CHANNEL', 'security'),
    'level' => env('SECURITY_LOG_LEVEL', 'warning'),
]
```

### Usage

```php
use App\Services\SecurityLogService;

$securityLog = new SecurityLogService();
$securityLog->warning('Suspicious activity detected', [
    'user_id' => $user->id,
    'action' => 'failed_login'
]);
```

## Best Practices

1. Always use HTTPS in production
2. Regularly review and update security headers
3. Follow the principle of least privilege
4. Monitor security logs
5. Keep dependencies updated
6. Use environment variables for sensitive configuration
7. Implement rate limiting for sensitive endpoints
8. Regular security audits

## Security Checklist

- [ ] Enable HTTPS
- [ ] Configure security headers
- [ ] Set up permission system
- [ ] Configure security logging
- [ ] Implement rate limiting
- [ ] Set up monitoring
- [ ] Regular security updates
- [ ] Security testing
- [ ] Documentation updates

## Troubleshooting

### Common Issues

1. **CSP Violations**
   - Check browser console for violations
   - Adjust CSP policy in configuration
   - Use report-only mode for testing

2. **Permission Issues**
   - Verify user roles
   - Check permission inheritance
   - Review middleware configuration

3. **Logging Issues**
   - Verify log channel configuration
   - Check log level settings
   - Ensure proper permissions

### Support

For security-related issues, contact:
- Security Team: security@example.com
- Emergency: security-emergency@example.com 