<?php

namespace MCP\Security\Middleware;

use MCP\Core\Middleware;
use MCP\Security\RBAC;
use MCP\Security\Authentication;
use MCP\Exceptions\AuthorizationException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Authorization Middleware
 * 
 * Handles authorization checks for requests using the RBAC system.
 * 
 * @package MCP\Security\Middleware
 */
class AuthorizationMiddleware extends Middleware
{
    protected RBAC $rbac;
    protected Authentication $auth;

    /**
     * Initialize the middleware
     */
    public function __construct(RBAC $rbac, Authentication $auth)
    {
        $this->rbac = $rbac;
        $this->auth = $auth;
    }

    /**
     * Process the request
     * 
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws AuthorizationException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Get the current user
        $user = $this->auth->getCurrentUser();
        if (!$user) {
            throw new AuthorizationException('Authentication required.', 401);
        }

        // Get the required permission from the request
        $permission = $this->getRequiredPermission($request);
        if (!$permission) {
            // No permission required, proceed
            return $handler->handle($request);
        }

        // Check if the user has the required permission
        if (!$this->rbac->hasPermission($user, $permission)) {
            throw new AuthorizationException('Permission denied.', 403);
        }

        // User has permission, proceed
        return $handler->handle($request);
    }

    /**
     * Get the required permission from the request
     * 
     * @param ServerRequestInterface $request
     * @return string|null
     */
    protected function getRequiredPermission(ServerRequestInterface $request): ?string
    {
        // Get the route from the request
        $route = $request->getAttribute('route');
        if (!$route) {
            return null;
        }

        // Get the permission from the route
        return $route->getPermission();
    }
} 