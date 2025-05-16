# JWT Implementation Documentation

## Overview

The LMS platform implements JSON Web Tokens (JWT) for API authentication using a custom TokenHandler class. The implementation provides a simple yet effective way to manage stateless authentication for API requests.

## Components

### 1. TokenHandler Class
Located in `application/libraries/TokenHandler.php`, this class provides the core JWT functionality:

```php
class TokenHandler {
    PRIVATE $key = "academy-lms-api-token-handler";
    
    public function GenerateToken($data) {
        $jwt = JWT::encode($data, $this->key);
        return $jwt;
    }
    
    public function DecodeToken($token) {
        $decoded = JWT::decode($token, $this->key, array('HS256'));
        $decodedData = (array) $decoded;
        return $decodedData;
    }
}
```

### 2. JWT Library
The system uses a third-party JWT library for token encoding and decoding operations.

## Token Structure

### 1. Token Format
The JWT follows the standard format:
```
header.payload.signature
```

### 2. Token Components

#### Header
```json
{
    "typ": "JWT",
    "alg": "HS256"
}
```

#### Payload
Typically contains:
```json
{
    "user_id": "123",
    "email": "user@example.com",
    "role": "instructor",
    "iat": 1516239022,
    "exp": 1516242622
}
```

#### Signature
Generated using HMAC-SHA256:
```
HMACSHA256(
    base64UrlEncode(header) + "." +
    base64UrlEncode(payload),
    secret_key
)
```

## Implementation Details

### 1. Token Generation
```php
// Example usage in API controller
public function login_post() {
    // Validate credentials
    if ($valid_credentials) {
        $token_data = [
            'user_id' => $user->id,
            'role' => $user->role,
            'iat' => time(),
            'exp' => time() + (60 * 60) // 1 hour expiration
        ];
        
        $token = $this->tokenHandler->GenerateToken($token_data);
        
        return $this->response([
            'status' => true,
            'token' => $token,
            'user_id' => $user->id
        ], 200);
    }
}
```

### 2. Token Validation
```php
// Example middleware implementation
public function validate_token($token) {
    try {
        $decoded = $this->tokenHandler->DecodeToken($token);
        
        // Check expiration
        if ($decoded['exp'] < time()) {
            return false;
        }
        
        // Additional validation as needed
        return true;
    } catch (Exception $e) {
        return false;
    }
}
```

## Security Considerations

### 1. Token Security
- Uses HS256 algorithm for signing
- Implements token expiration
- Validates token integrity
- Protects against tampering

### 2. Key Management
- Secret key stored in TokenHandler class
- Should be moved to environment configuration
- Should be unique per environment
- Minimum 256-bit length recommended

### 3. Token Storage
- Stored client-side
- Transmitted in Authorization header
- Never stored in cookies
- Protected against XSS

### 4. Token Validation
- Signature verification
- Expiration checking
- Role validation
- Scope validation

## Migration Recommendations

When migrating to Laravel, consider the following improvements:

### 1. Token Management
- Use Laravel Sanctum for API tokens
- Implement refresh tokens
- Add token revocation
- Add token scoping

### 2. Security Enhancements
- Move secret key to .env
- Implement key rotation
- Add blacklisting capability
- Add rate limiting

### 3. Authentication Flow
- Add OAuth 2.0 support
- Implement social authentication
- Add 2FA support
- Add device tracking

### 4. Implementation
```php
// Example Laravel Sanctum implementation
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    
    public function tokens()
    {
        return $this->morphMany('Laravel\Sanctum\PersonalAccessToken', 'tokenable');
    }
}
```

## Best Practices

### 1. Token Handling
- Use short expiration times
- Implement token refresh
- Validate all token claims
- Handle errors gracefully

### 2. Security
- Use HTTPS only
- Implement CORS properly
- Validate all inputs
- Log security events

### 3. Error Handling
```php
try {
    $decoded = $this->tokenHandler->DecodeToken($token);
} catch (ExpiredException $e) {
    return response()->json([
        'error' => 'Token has expired'
    ], 401);
} catch (Exception $e) {
    return response()->json([
        'error' => 'Invalid token'
    ], 401);
}
```

## Testing Guidelines

### 1. Unit Tests
- Token generation
- Token validation
- Error handling
- Expiration handling

### 2. Integration Tests
- Authentication flow
- Token refresh
- Error scenarios
- Rate limiting

### 3. Security Tests
- Token tampering
- Expiration bypass
- Role spoofing
- Injection attacks 