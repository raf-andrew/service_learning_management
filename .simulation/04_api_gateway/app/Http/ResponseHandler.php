<?php

namespace App\Http;

use Illuminate\Http\Response;
use App\Services\TransformationService;
use App\Services\CacheManager;
use App\Services\LoggingService;

class ResponseHandler
{
    protected $transformer;
    protected $cacheManager;
    protected $logger;

    public function __construct(
        TransformationService $transformer,
        CacheManager $cacheManager,
        LoggingService $logger
    ) {
        $this->transformer = $transformer;
        $this->cacheManager = $cacheManager;
        $this->logger = $logger;
    }

    public function handle(Response $response, array $route, array $requestData)
    {
        // Log the response
        $this->logger->logResponse($response);

        // Transform the response if needed
        $transformedResponse = $this->transformer->transformResponse($response, $route);

        // Add cache headers if caching is enabled
        if (isset($route['cache_ttl']) && $route['cache_ttl'] > 0) {
            $this->addCacheHeaders($transformedResponse, $route['cache_ttl']);
        }

        // Add rate limit headers
        $this->addRateLimitHeaders($transformedResponse, $route);

        // Add security headers
        $this->addSecurityHeaders($transformedResponse);

        // Cache the response if needed
        if (isset($route['cache_ttl']) && $route['cache_ttl'] > 0) {
            $this->cacheManager->put($requestData, $transformedResponse, $route['cache_ttl']);
        }

        return $transformedResponse;
    }

    protected function addCacheHeaders(Response $response, int $ttl)
    {
        $response->headers->set('Cache-Control', "public, max-age={$ttl}");
        $response->headers->set('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + $ttl));
    }

    protected function addRateLimitHeaders(Response $response, array $route)
    {
        if (isset($route['rate_limit'])) {
            $response->headers->set('X-RateLimit-Limit', $route['rate_limit']);
            $response->headers->set('X-RateLimit-Remaining', $route['rate_limit'] - 1);
            $response->headers->set('X-RateLimit-Reset', time() + 3600);
        }
    }

    protected function addSecurityHeaders(Response $response)
    {
        $securityHeaders = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'Content-Security-Policy' => "default-src 'self'",
            'Referrer-Policy' => 'strict-origin-when-cross-origin'
        ];

        foreach ($securityHeaders as $header => $value) {
            $response->headers->set($header, $value);
        }
    }

    public function handleError(\Throwable $error, array $route = [])
    {
        $statusCode = $this->getErrorStatusCode($error);
        $message = $this->getErrorMessage($error);

        $response = new Response([
            'error' => $message,
            'code' => $statusCode
        ], $statusCode);

        // Add security headers even for error responses
        $this->addSecurityHeaders($response);

        // Log the error
        $this->logger->logError($error);

        return $response;
    }

    protected function getErrorStatusCode(\Throwable $error): int
    {
        if ($error instanceof \InvalidArgumentException) {
            return Response::HTTP_BAD_REQUEST;
        }

        if ($error instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return Response::HTTP_NOT_FOUND;
        }

        if ($error instanceof \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException) {
            return Response::HTTP_UNAUTHORIZED;
        }

        if ($error instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
            return Response::HTTP_FORBIDDEN;
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    protected function getErrorMessage(\Throwable $error): string
    {
        if ($error instanceof \InvalidArgumentException) {
            return $error->getMessage();
        }

        if ($error instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
            return $error->getMessage() ?: Response::$statusTexts[$error->getStatusCode()];
        }

        return 'An unexpected error occurred';
    }
} 