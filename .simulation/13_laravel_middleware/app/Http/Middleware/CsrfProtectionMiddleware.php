<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CsrfProtectionMiddleware extends BaseMiddleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
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
        if ($this->isReading($request) || $this->shouldPassThrough($request)) {
            return $next($request);
        }

        if (!$this->tokensMatch($request)) {
            return $this->handleInvalidToken($request);
        }

        return $next($request);
    }

    /**
     * Determine if the request has a valid CSRF token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function tokensMatch(Request $request): bool
    {
        $token = $this->getTokenFromRequest($request);
        $sessionToken = Session::token();

        return is_string($token) && is_string($sessionToken) &&
               hash_equals($sessionToken, $token);
    }

    /**
     * Get the CSRF token from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function getTokenFromRequest(Request $request): ?string
    {
        $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');

        if (!$token && $header = $request->header('X-XSRF-TOKEN')) {
            $token = urldecode($header);
        }

        return $token;
    }

    /**
     * Handle an invalid CSRF token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleInvalidToken(Request $request): Response
    {
        return response()->json([
            'error' => 'CSRF token mismatch',
            'message' => 'The CSRF token is invalid or has expired. Please refresh the page and try again.'
        ], 419);
    }

    /**
     * Determine if the request is a "read" request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function isReading(Request $request): bool
    {
        return in_array($request->method(), ['HEAD', 'GET', 'OPTIONS']);
    }

    /**
     * Determine if the request should pass through the middleware.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldPassThrough(Request $request): bool
    {
        $except = array_merge($this->except, $this->config('csrf.except', []));

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

    /**
     * Generate a new CSRF token.
     *
     * @return string
     */
    public function generateToken(): string
    {
        $token = Str::random(40);
        Session::put('_token', $token);
        return $token;
    }
} 