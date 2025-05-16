<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RateLimiter;
use App\Services\CacheManager;
use App\Services\RouteManager;
use App\Services\AuthenticationService;
use App\Services\LoggingService;
use GuzzleHttp\Client;
use Illuminate\Http\Response;

class GatewayController extends Controller
{
    protected $rateLimiter;
    protected $cacheManager;
    protected $routeManager;
    protected $authService;
    protected $logger;
    protected $httpClient;

    public function __construct(
        RateLimiter $rateLimiter,
        CacheManager $cacheManager,
        RouteManager $routeManager,
        AuthenticationService $authService,
        LoggingService $logger
    ) {
        $this->rateLimiter = $rateLimiter;
        $this->cacheManager = $cacheManager;
        $this->routeManager = $routeManager;
        $this->authService = $authService;
        $this->logger = $logger;
        $this->httpClient = new Client();
    }

    public function handle(Request $request)
    {
        try {
            // Log the incoming request
            $this->logger->logRequest($request);

            // Check rate limit
            if (!$this->rateLimiter->check($request)) {
                return response()->json([
                    'error' => 'Rate limit exceeded',
                    'code' => Response::HTTP_TOO_MANY_REQUESTS
                ], Response::HTTP_TOO_MANY_REQUESTS);
            }

            // Authenticate request
            if (!$this->authService->authenticate($request)) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'code' => Response::HTTP_UNAUTHORIZED
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Get route configuration
            $route = $this->routeManager->getRoute($request->path());
            if (!$route) {
                return response()->json([
                    'error' => 'Route not found',
                    'code' => Response::HTTP_NOT_FOUND
                ], Response::HTTP_NOT_FOUND);
            }

            // Check cache
            if ($route['cache_ttl'] > 0) {
                $cachedResponse = $this->cacheManager->get($request);
                if ($cachedResponse) {
                    return $cachedResponse;
                }
            }

            // Forward request to target service
            $response = $this->forwardRequest($request, $route);

            // Cache response if needed
            if ($route['cache_ttl'] > 0) {
                $this->cacheManager->put($request, $response, $route['cache_ttl']);
            }

            return $response;

        } catch (\Exception $e) {
            $this->logger->logError($e);
            return response()->json([
                'error' => 'Internal server error',
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function forwardRequest(Request $request, array $route)
    {
        $options = [
            'headers' => $request->headers->all(),
            'query' => $request->query(),
            'json' => $request->json()->all(),
            'timeout' => $route['timeout'] ?? 30
        ];

        $response = $this->httpClient->request(
            $request->method(),
            $route['target_url'] . $request->path(),
            $options
        );

        return response(
            $response->getBody()->getContents(),
            $response->getStatusCode(),
            $response->getHeaders()
        );
    }
} 