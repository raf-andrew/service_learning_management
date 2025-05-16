<?php

namespace App\Services;

use App\Models\ApiKey;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthenticationService
{
    protected $jwtSecret;
    protected $jwtAlgorithm;
    protected $oauthProvider;

    public function __construct()
    {
        $this->jwtSecret = config('auth.jwt.secret');
        $this->jwtAlgorithm = config('auth.jwt.algorithm', 'HS256');
        $this->oauthProvider = config('auth.oauth.provider');
    }

    public function validateRequest(Request $request, array $route)
    {
        if (!isset($route['auth_required']) || !$route['auth_required']) {
            return true;
        }

        $authHeader = $request->header('Authorization');
        if (!$authHeader) {
            throw new UnauthorizedHttpException('Bearer', 'No authorization header');
        }

        $authType = $this->getAuthType($authHeader);
        switch ($authType) {
            case 'api_key':
                return $this->validateApiKey($request);
            case 'jwt':
                return $this->validateJwt($request);
            case 'oauth':
                return $this->validateOAuth($request);
            default:
                throw new UnauthorizedHttpException('Bearer', 'Invalid authorization type');
        }
    }

    protected function getAuthType(string $authHeader): string
    {
        if (str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
            if (strlen($token) === 32) {
                return 'api_key';
            }
            return 'jwt';
        }
        if (str_starts_with($authHeader, 'OAuth ')) {
            return 'oauth';
        }
        throw new UnauthorizedHttpException('Bearer', 'Invalid authorization header format');
    }

    protected function validateApiKey(Request $request): bool
    {
        $apiKey = substr($request->header('Authorization'), 7);
        
        // Check cache first
        $cachedKey = Cache::get('api_key:' . $apiKey);
        if ($cachedKey) {
            return $cachedKey['is_valid'];
        }

        // Validate against database
        $key = ApiKey::where('key', $apiKey)
            ->where('is_active', true)
            ->first();

        if (!$key) {
            Cache::put('api_key:' . $apiKey, ['is_valid' => false], 300);
            throw new UnauthorizedHttpException('Bearer', 'Invalid API key');
        }

        // Cache valid key
        Cache::put('api_key:' . $apiKey, [
            'is_valid' => true,
            'permissions' => $key->permissions
        ], 300);

        return true;
    }

    protected function validateJwt(Request $request): bool
    {
        $token = substr($request->header('Authorization'), 7);
        
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, $this->jwtAlgorithm));
            
            // Validate token expiration
            if (isset($decoded->exp) && $decoded->exp < time()) {
                throw new UnauthorizedHttpException('Bearer', 'Token has expired');
            }

            // Add decoded token to request for later use
            $request->attributes->set('jwt_payload', $decoded);
            
            return true;
        } catch (\Exception $e) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid JWT token: ' . $e->getMessage());
        }
    }

    protected function validateOAuth(Request $request): bool
    {
        $token = substr($request->header('Authorization'), 6);
        
        // Validate OAuth token with provider
        try {
            $response = $this->oauthProvider->validateToken($token);
            if (!$response->isValid()) {
                throw new UnauthorizedHttpException('Bearer', 'Invalid OAuth token');
            }

            // Add OAuth user info to request for later use
            $request->attributes->set('oauth_user', $response->getUser());
            
            return true;
        } catch (\Exception $e) {
            throw new UnauthorizedHttpException('Bearer', 'OAuth validation failed: ' . $e->getMessage());
        }
    }

    public function generateJwt(array $payload, int $ttl = 3600): string
    {
        $payload = array_merge([
            'iat' => time(),
            'exp' => time() + $ttl,
            'nbf' => time()
        ], $payload);

        return JWT::encode($payload, $this->jwtSecret, $this->jwtAlgorithm);
    }

    public function generateApiKey(): string
    {
        return bin2hex(random_bytes(16));
    }
} 