# MCP Authorization System

## Overview

The MCP Authorization System provides a flexible and powerful authorization framework that integrates with the RBAC (Role-Based Access Control) system. It ensures that users can only access resources and perform actions they are authorized to use.

## Components

### Authorization Middleware

The core authorization middleware (`MCP\Security\Middleware\AuthorizationMiddleware`) handles:

- Permission checks for requests
- Integration with authentication
- Integration with RBAC
- Error handling

### Authorization Exception

The `MCP\Exceptions\AuthorizationException` handles authorization-related errors:

- Authentication required
- Permission denied
- Invalid permissions
- Access control violations

## Usage

### Basic Authorization

```php
use MCP\Security\Middleware\AuthorizationMiddleware;
use MCP\Security\RBAC;
use MCP\Security\Authentication;

// Initialize the middleware
$rbac = new RBAC();
$auth = new Authentication($rbac);
$middleware = new AuthorizationMiddleware($rbac, $auth);

// The middleware will automatically:
// 1. Check if the user is authenticated
// 2. Get the required permission from the route
// 3. Check if the user has the required permission
// 4. Allow or deny the request
```

### Route Configuration

Routes can specify required permissions:

```php
$router->get('/admin/users', 'UserController@index')
    ->permission('users.view');

$router->post('/admin/users', 'UserController@store')
    ->permission('users.create');

$router->put('/admin/users/{id}', 'UserController@update')
    ->permission('users.update');

$router->delete('/admin/users/{id}', 'UserController@delete')
    ->permission('users.delete');
```

### Error Handling

Authorization errors are handled through the `AuthorizationException`:

```php
try {
    $response = $middleware->process($request, $handler);
} catch (AuthorizationException $e) {
    switch ($e->getCode()) {
        case 401:
            // Authentication required
            return $response->withStatus(401)
                ->withJson(['error' => 'Authentication required']);
        case 403:
            // Permission denied
            return $response->withStatus(403)
                ->withJson(['error' => 'Permission denied']);
    }
}
```

## Security Features

### Role-Based Access Control

The authorization system integrates with the RBAC system to provide:

- Role-based permissions
- Permission inheritance
- Permission caching
- Permission validation

### Permission Management

Permissions are managed through:

- Permission definitions
- Role assignments
- Permission checks
- Permission auditing

### Access Control

The system implements access control through:

- Route-level permissions
- Resource-level permissions
- Action-level permissions
- Tenant-level permissions

## Testing

The authorization system includes comprehensive test coverage:

- Unit tests for all components
- Integration tests for authorization flows
- Security testing for vulnerabilities
- Performance testing for load handling

## Best Practices

1. Always use the authorization middleware
2. Define clear permission names
3. Use permission inheritance
4. Implement proper error handling
5. Log authorization events
6. Regular security audits
7. Monitor permission usage
8. Review permission assignments
9. Document permission requirements
10. Test authorization flows

## Related Documentation

- [Authentication System](authentication.md)
- [RBAC System](rbac.md)
- [Security Best Practices](security-best-practices.md)
- [Middleware System](middleware.md) 