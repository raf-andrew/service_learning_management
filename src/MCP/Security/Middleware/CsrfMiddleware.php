<?php

namespace MCP\Security\Middleware;

use MCP\Core\Middleware;
use MCP\Exceptions\CsrfException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * CSRF Middleware
 * 
 * Protects against cross-site request forgery attacks by validating tokens.
 * 
 * @package MCP\Security\Middleware
 */
class CsrfMiddleware extends Middleware
{
    protected array $config;
    protected array $excludedRoutes;

    /**
     * Initialize the middleware
     */
    public function __construct(array $config = [], array $excludedRoutes = [])
    {
        $this->config = array_merge([
            'token_length' => 32,
            'token_name' => 'csrf_token',
            'header_name' => 'X-CSRF-TOKEN',
            'cookie_name' => 'csrf_token',
            'cookie_lifetime' => 7200, // 2 hours
            'cookie_path' => '/',
            'cookie_domain' => '',
            'cookie_secure' => true,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ], $config);

        $this->excludedRoutes = $excludedRoutes;
    }

    /**
     * Process the request
     * 
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws CsrfException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Skip CSRF check for excluded routes
        if ($this->isExcludedRoute($request)) {
            return $handler->handle($request);
        }

        // Skip CSRF check for safe methods
        if ($this->isSafeMethod($request->getMethod())) {
            return $handler->handle($request);
        }

        // Get the token from the request
        $token = $this->getTokenFromRequest($request);
        if (!$token) {
            throw new CsrfException('CSRF token missing.');
        }

        // Get the token from the cookie
        $cookieToken = $this->getTokenFromCookie($request);
        if (!$cookieToken) {
            throw new CsrfException('CSRF cookie missing.');
        }

        // Validate the token
        if (!$this->validateToken($token, $cookieToken)) {
            throw new CsrfException('CSRF token mismatch.');
        }

        // Process the request
        $response = $handler->handle($request);

        // Generate a new token
        $newToken = $this->generateToken();

        // Add the token to the response
        return $this->addTokenToResponse($response, $newToken);
    }

    /**
     * Check if the route is excluded from CSRF protection
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
     * Check if the request method is safe
     * 
     * @param string $method
     * @return bool
     */
    protected function isSafeMethod(string $method): bool
    {
        return in_array(strtoupper($method), ['GET', 'HEAD', 'OPTIONS']);
    }

    /**
     * Get the CSRF token from the request
     * 
     * @param ServerRequestInterface $request
     * @return string|null
     */
    protected function getTokenFromRequest(ServerRequestInterface $request): ?string
    {
        // Check header
        $token = $request->getHeaderLine($this->config['header_name']);
        if ($token) {
            return $token;
        }

        // Check POST data
        $data = $request->getParsedBody();
        if (is_array($data) && isset($data[$this->config['token_name']])) {
            return $data[$this->config['token_name']];
        }

        return null;
    }

    /**
     * Get the CSRF token from the cookie
     * 
     * @param ServerRequestInterface $request
     * @return string|null
     */
    protected function getTokenFromCookie(ServerRequestInterface $request): ?string
    {
        $cookies = $request->getCookieParams();
        return $cookies[$this->config['cookie_name']] ?? null;
    }

    /**
     * Validate the CSRF token
     * 
     * @param string $token
     * @param string $cookieToken
     * @return bool
     */
    protected function validateToken(string $token, string $cookieToken): bool
    {
        return hash_equals($cookieToken, $token);
    }

    /**
     * Generate a new CSRF token
     * 
     * @return string
     */
    protected function generateToken(): string
    {
        return bin2hex(random_bytes($this->config['token_length']));
    }

    /**
     * Add the CSRF token to the response
     * 
     * @param ResponseInterface $response
     * @param string $token
     * @return ResponseInterface
     */
    protected function addTokenToResponse(ResponseInterface $response, string $token): ResponseInterface
    {
        // Add cookie
        $response = $response->withHeader(
            'Set-Cookie',
            sprintf(
                '%s=%s; Path=%s; Max-Age=%d; HttpOnly; SameSite=%s%s',
                $this->config['cookie_name'],
                $token,
                $this->config['cookie_path'],
                $this->config['cookie_lifetime'],
                $this->config['cookie_samesite'],
                $this->config['cookie_secure'] ? '; Secure' : ''
            )
        );

        // Add header for JavaScript access
        return $response->withHeader($this->config['header_name'], $token);
    }
} 