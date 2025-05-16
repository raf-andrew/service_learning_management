<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class XssProtectionMiddleware extends BaseMiddleware
{
    /**
     * The URIs that should be excluded from XSS protection.
     *
     * @var array
     */
    protected $except = [];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($this->shouldPassThrough($request)) {
            return $next($request);
        }

        // Sanitize request input
        $this->sanitizeRequest($request);

        // Get the response
        $response = $next($request);

        // Add security headers
        $this->addSecurityHeaders($response);

        return $response;
    }

    /**
     * Sanitize the request input.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function sanitizeRequest(Request $request): void
    {
        // Sanitize GET parameters
        $this->sanitizeArray($request->query->all());

        // Sanitize POST parameters
        $this->sanitizeArray($request->request->all());

        // Sanitize JSON input
        if ($request->isJson()) {
            $content = $request->getContent();
            if ($content) {
                $json = json_decode($content, true);
                if (is_array($json)) {
                    $this->sanitizeArray($json);
                    $request->json = $json;
                }
            }
        }
    }

    /**
     * Sanitize an array of input data.
     *
     * @param  array  $data
     * @return void
     */
    protected function sanitizeArray(array &$data): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->sanitizeArray($value);
            } else {
                $data[$key] = $this->sanitizeValue($value);
            }
        }
    }

    /**
     * Sanitize a single value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function sanitizeValue($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        // Remove common XSS vectors
        $value = str_replace(
            ['<script', '</script>', 'javascript:', 'onerror=', 'onload='],
            ['&lt;script', '&lt;/script&gt;', 'javascript&#58;', 'onerror&#61;', 'onload&#61;'],
            $value
        );

        // Remove HTML tags if configured to do so
        if ($this->config('xss.strip_tags', false)) {
            $value = strip_tags($value);
        }

        // Escape HTML entities
        if ($this->config('xss.escape_html', true)) {
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }

        return $value;
    }

    /**
     * Add security headers to the response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    protected function addSecurityHeaders(Response $response): void
    {
        // Add X-XSS-Protection header
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Add Content-Security-Policy header if configured
        if ($csp = $this->config('xss.content_security_policy')) {
            $response->headers->set('Content-Security-Policy', $csp);
        }

        // Add X-Content-Type-Options header
        $response->headers->set('X-Content-Type-Options', 'nosniff');
    }

    /**
     * Determine if the request should pass through the middleware.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldPassThrough(Request $request): bool
    {
        $except = array_merge($this->except, $this->config('xss.except', []));

        foreach ($except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->is($except)) {
                return true;
            }
        }

        return false;
    }
} 