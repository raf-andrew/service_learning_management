<?php

namespace App\Http;

use Illuminate\Http\Request;
use App\Config\RouteConfig;
use App\Services\ValidationService;
use App\Services\TransformationService;

class RequestHandler
{
    protected $routeConfig;
    protected $validator;
    protected $transformer;

    public function __construct(
        RouteConfig $routeConfig,
        ValidationService $validator,
        TransformationService $transformer
    ) {
        $this->routeConfig = $routeConfig;
        $this->validator = $validator;
        $this->transformer = $transformer;
    }

    public function handle(Request $request)
    {
        // Parse and validate the request path
        $path = $this->parsePath($request);
        if (!$path) {
            throw new \InvalidArgumentException('Invalid request path');
        }

        // Get route configuration
        $route = $this->routeConfig->getRoute($path, $request->method());
        if (!$route) {
            throw new \InvalidArgumentException('Route not found');
        }

        // Validate headers
        $this->validateHeaders($request);

        // Parse and validate query parameters
        $queryParams = $this->parseQueryParameters($request);
        $this->validator->validateQueryParameters($queryParams, $route);

        // Parse and validate request body
        $body = $this->parseBody($request);
        $this->validator->validateBody($body, $route);

        // Transform request if needed
        $transformedRequest = $this->transformer->transformRequest($request, $route);

        return [
            'route' => $route,
            'query_params' => $queryParams,
            'body' => $body,
            'transformed_request' => $transformedRequest
        ];
    }

    protected function parsePath(Request $request)
    {
        $path = $request->path();
        
        // Remove trailing slash
        $path = rtrim($path, '/');
        
        // Ensure path starts with /
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        return $path;
    }

    protected function validateHeaders(Request $request)
    {
        $requiredHeaders = [
            'Content-Type',
            'Accept',
            'X-Request-ID'
        ];

        foreach ($requiredHeaders as $header) {
            if (!$request->hasHeader($header)) {
                throw new \InvalidArgumentException("Missing required header: {$header}");
            }
        }

        // Validate Content-Type
        $contentType = $request->header('Content-Type');
        if (!in_array($contentType, ['application/json', 'application/x-www-form-urlencoded'])) {
            throw new \InvalidArgumentException('Invalid Content-Type header');
        }

        // Validate Accept
        $accept = $request->header('Accept');
        if (!in_array($accept, ['application/json', '*/*'])) {
            throw new \InvalidArgumentException('Invalid Accept header');
        }
    }

    protected function parseQueryParameters(Request $request)
    {
        $params = $request->query();
        
        // Remove empty parameters
        $params = array_filter($params, function ($value) {
            return $value !== null && $value !== '';
        });

        return $params;
    }

    protected function parseBody(Request $request)
    {
        if ($request->isMethod('GET')) {
            return null;
        }

        $contentType = $request->header('Content-Type');
        
        if ($contentType === 'application/json') {
            return $request->json()->all();
        }

        if ($contentType === 'application/x-www-form-urlencoded') {
            return $request->all();
        }

        throw new \InvalidArgumentException('Unsupported Content-Type');
    }
} 