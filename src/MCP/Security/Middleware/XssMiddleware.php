<?php

namespace MCP\Security\Middleware;

use MCP\Core\Middleware;
use MCP\Exceptions\XssException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * XSS Middleware
 * 
 * Protects against cross-site scripting attacks by sanitizing input and encoding output.
 * 
 * @package MCP\Security\Middleware
 */
class XssMiddleware extends Middleware
{
    protected array $config;
    protected array $excludedRoutes;

    /**
     * Initialize the middleware
     */
    public function __construct(array $config = [], array $excludedRoutes = [])
    {
        $this->config = array_merge([
            'content_security_policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';",
            'x_xss_protection' => '1; mode=block',
            'x_content_type_options' => 'nosniff',
            'sanitize_input' => true,
            'encode_output' => true,
            'allowed_tags' => '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><table><tr><td><th><thead><tbody>',
        ], $config);

        $this->excludedRoutes = $excludedRoutes;
    }

    /**
     * Process the request
     * 
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws XssException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Skip XSS protection for excluded routes
        if ($this->isExcludedRoute($request)) {
            return $handler->handle($request);
        }

        // Sanitize input if enabled
        if ($this->config['sanitize_input']) {
            $request = $this->sanitizeRequest($request);
        }

        // Process the request
        $response = $handler->handle($request);

        // Add security headers
        $response = $this->addSecurityHeaders($response);

        // Encode output if enabled
        if ($this->config['encode_output']) {
            $response = $this->encodeResponse($response);
        }

        return $response;
    }

    /**
     * Check if the route is excluded from XSS protection
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
     * Sanitize the request data
     * 
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    protected function sanitizeRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        // Sanitize query parameters
        $queryParams = $request->getQueryParams();
        $sanitizedQueryParams = $this->sanitizeArray($queryParams);
        $request = $request->withQueryParams($sanitizedQueryParams);

        // Sanitize parsed body
        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody)) {
            $sanitizedBody = $this->sanitizeArray($parsedBody);
            $request = $request->withParsedBody($sanitizedBody);
        }

        // Sanitize cookies
        $cookies = $request->getCookieParams();
        $sanitizedCookies = $this->sanitizeArray($cookies);
        $request = $request->withCookieParams($sanitizedCookies);

        return $request;
    }

    /**
     * Sanitize an array of data
     * 
     * @param array $data
     * @return array
     */
    protected function sanitizeArray(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
            } else {
                $sanitized[$key] = $this->sanitizeValue($value);
            }
        }
        return $sanitized;
    }

    /**
     * Sanitize a single value
     * 
     * @param mixed $value
     * @return mixed
     */
    protected function sanitizeValue($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        // Strip all HTML tags except allowed ones
        $value = strip_tags($value, $this->config['allowed_tags']);

        // Convert special characters to HTML entities
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Add security headers to the response
     * 
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function addSecurityHeaders(ResponseInterface $response): ResponseInterface
    {
        $response = $response->withHeader('Content-Security-Policy', $this->config['content_security_policy']);
        $response = $response->withHeader('X-XSS-Protection', $this->config['x_xss_protection']);
        $response = $response->withHeader('X-Content-Type-Options', $this->config['x_content_type_options']);

        return $response;
    }

    /**
     * Encode the response body
     * 
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function encodeResponse(ResponseInterface $response): ResponseInterface
    {
        $contentType = $response->getHeaderLine('Content-Type');
        
        // Only encode HTML responses
        if (strpos($contentType, 'text/html') !== false) {
            $body = (string) $response->getBody();
            $encodedBody = $this->encodeHtml($body);
            
            $stream = fopen('php://temp', 'r+');
            fwrite($stream, $encodedBody);
            rewind($stream);
            
            return $response->withBody(new \GuzzleHttp\Psr7\Stream($stream));
        }

        return $response;
    }

    /**
     * Encode HTML content
     * 
     * @param string $html
     * @return string
     */
    protected function encodeHtml(string $html): string
    {
        // Preserve allowed HTML tags
        $allowedTags = explode('><', trim($this->config['allowed_tags'], '<>'));
        $allowedTags = array_map(function($tag) {
            return '<' . $tag . '>';
        }, $allowedTags);

        // Temporarily replace allowed tags
        $placeholders = [];
        foreach ($allowedTags as $i => $tag) {
            $placeholder = "{{ALLOWED_TAG_{$i}}}";
            $html = str_replace($tag, $placeholder, $html);
            $placeholders[$placeholder] = $tag;
        }

        // Encode the rest of the HTML
        $encoded = htmlspecialchars($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Restore allowed tags
        foreach ($placeholders as $placeholder => $tag) {
            $encoded = str_replace($placeholder, $tag, $encoded);
        }

        return $encoded;
    }
} 