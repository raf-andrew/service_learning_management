<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompressionMiddleware extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only compress if the response is not already compressed
        if (!$response->headers->has('Content-Encoding')) {
            // Check if client accepts gzip encoding
            if (strpos($request->header('Accept-Encoding'), 'gzip') !== false) {
                $content = $response->getContent();
                
                // Only compress if content is large enough to benefit from compression
                if (strlen($content) > 1024) {
                    $compressed = gzencode($content, 9);
                    
                    if ($compressed !== false) {
                        $response->setContent($compressed);
                        $response->headers->set('Content-Encoding', 'gzip');
                        $response->headers->set('Vary', 'Accept-Encoding');
                        $response->headers->set('Content-Length', strlen($compressed));
                    }
                }
            }
        }

        return $response;
    }
} 