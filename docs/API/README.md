# API Documentation

## Overview

The Service Learning Management System provides a comprehensive API for managing service learning programs, user authentication, encryption, compliance, and more. This documentation covers all available endpoints, authentication methods, and data formats.

---

## Authentication

### API Key Authentication

Most endpoints require API key authentication. Include your API key in the request headers:

```
X-API-Key: your-api-key-here
```

### Rate Limiting

- **Web endpoints**: 120 requests per minute
- **API endpoints**: 60 requests per minute
- **Rate limit headers**: `X-RateLimit-Limit`, `X-RateLimit-Remaining`

---

## Base URL

```
https://your-domain.com/api/v1
```

---

## E2EE (End-to-End Encryption) Module

### Endpoints

#### Encrypt Data
```http
POST /e2ee/encrypt
```

**Request Body:**
```json
{
    "data": "sensitive data to encrypt",
    "user_id": 123,
    "metadata": {
        "source": "api",
        "timestamp": "2024-06-23T10:00:00Z"
    }
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "transaction_id": "e2ee_abc123_1234567890",
        "encrypted_data": "base64-encoded-encrypted-data",
        "iv": "base64-encoded-iv",
        "algorithm": "AES-256-GCM",
        "key_id": "key_123",
        "timestamp": "2024-06-23T10:00:00Z"
    }
}
```

#### Decrypt Data
```http
POST /e2ee/decrypt
```

**Request Body:**
```json
{
    "encrypted_data": "base64-encoded-encrypted-data",
    "iv": "base64-encoded-iv",
    "user_id": 123,
    "transaction_id": "e2ee_abc123_1234567890"
}
```

**Response:**
```json
{
    "success": true,
    "data": "decrypted sensitive data"
}
```

#### Get Encryption Statistics
```http
GET /e2ee/statistics
```

**Response:**
```json
{
    "success": true,
    "data": {
        "total_transactions": 1500,
        "encrypt_operations": 800,
        "decrypt_operations": 700,
        "algorithm": "AES-256-GCM",
        "key_length": 32,
        "audit_enabled": true
    }
}
```

#### Generate Key
```http
POST /e2ee/generate-key
```

**Request Body:**
```json
{
    "length": 32
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "key": "base64-encoded-key",
        "length": 32
    }
}
```

---

## SOC2 (Compliance) Module

### Endpoints

#### Validate System
```http
POST /soc2/validate
```

**Response:**
```json
{
    "success": true,
    "data": {
        "overall_status": "pass",
        "score": 95.5,
        "checks": [
            {
                "name": "Database",
                "status": "pass",
                "details": ["Database connection successful"]
            },
            {
                "name": "Security",
                "status": "pass",
                "details": ["All security checks passed"]
            }
        ],
        "issues": [],
        "recommendations": []
    }
}
```

#### Get Compliance Report
```http
GET /soc2/report/{certification_id}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "certification_id": "soc2_001",
        "certification_type": "Type II",
        "status": "active",
        "valid_from": "2024-01-01",
        "valid_until": "2024-12-31",
        "compliance_score": 95.5,
        "controls": [
            {
                "id": "CC1.1",
                "name": "Control Environment",
                "status": "compliant",
                "score": 98.0
            }
        ]
    }
}
```

#### Create Risk Assessment
```http
POST /soc2/risk-assessment
```

**Request Body:**
```json
{
    "title": "Data Breach Risk",
    "description": "Risk of unauthorized access to sensitive data",
    "risk_level": "medium",
    "impact": "high",
    "probability": "low",
    "mitigation_plan": "Implement additional access controls"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": "risk_001",
        "title": "Data Breach Risk",
        "status": "open",
        "created_at": "2024-06-23T10:00:00Z"
    }
}
```

---

## Security Endpoints

### Health Check
```http
GET /health
```

**Response:**
```json
{
    "status": "healthy",
    "timestamp": "2024-06-23T10:00:00Z",
    "version": "1.0.0",
    "checks": {
        "database": "healthy",
        "cache": "healthy",
        "queue": "healthy"
    }
}
```

### Security Headers Check
```http
GET /security/headers
```

**Response:**
```json
{
    "success": true,
    "data": {
        "headers": {
            "X-Content-Type-Options": "nosniff",
            "X-Frame-Options": "DENY",
            "X-XSS-Protection": "1; mode=block",
            "Content-Security-Policy": "default-src 'self'",
            "Strict-Transport-Security": "max-age=31536000"
        }
    }
}
```

---

## Performance Endpoints

### Get Performance Metrics
```http
GET /performance/metrics
```

**Response:**
```json
{
    "success": true,
    "data": {
        "response_times": {
            "average": 150,
            "p95": 300,
            "p99": 500
        },
        "memory_usage": {
            "current": 256,
            "peak": 512
        },
        "query_count": {
            "total": 1250,
            "slow_queries": 5
        },
        "cache_hit_rate": 85.5
    }
}
```

### Clear Performance Caches
```http
POST /performance/clear-cache
```

**Response:**
```json
{
    "success": true,
    "message": "Performance caches cleared successfully"
}
```

---

## Error Responses

### Standard Error Format
```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Invalid input data",
        "details": [
            "Field 'email' is required",
            "Field 'user_id' must be a positive integer"
        ]
    }
}
```

### Common Error Codes

| Code | Description | HTTP Status |
|------|-------------|-------------|
| `AUTHENTICATION_FAILED` | Invalid or missing API key | 401 |
| `RATE_LIMIT_EXCEEDED` | Too many requests | 429 |
| `VALIDATION_ERROR` | Invalid request data | 400 |
| `NOT_FOUND` | Resource not found | 404 |
| `ENCRYPTION_ERROR` | Encryption/decryption failed | 500 |
| `COMPLIANCE_ERROR` | Compliance check failed | 400 |
| `PERFORMANCE_ERROR` | Performance threshold exceeded | 500 |

---

## Request/Response Headers

### Required Headers
```
Content-Type: application/json
Accept: application/json
X-API-Key: your-api-key
```

### Optional Headers
```
X-Request-ID: unique-request-id
X-User-Agent: your-app-name/version
```

### Response Headers
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-Request-ID: unique-request-id
```

---

## Rate Limiting

### Limits
- **Web endpoints**: 120 requests per minute
- **API endpoints**: 60 requests per minute
- **E2EE operations**: 30 requests per minute
- **SOC2 operations**: 20 requests per minute

### Rate Limit Headers
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1234567890
```

### Rate Limit Exceeded Response
```json
{
    "success": false,
    "error": {
        "code": "RATE_LIMIT_EXCEEDED",
        "message": "Too many requests",
        "retry_after": 30
    }
}
```

---

## Data Formats

### Timestamps
All timestamps are in ISO 8601 format: `YYYY-MM-DDTHH:mm:ssZ`

### IDs
- **User IDs**: Positive integers
- **Transaction IDs**: String format `e2ee_<unique>_<timestamp>`
- **Certification IDs**: String format `soc2_<number>`
- **Risk IDs**: String format `risk_<number>`

### Encryption Data
- **Encrypted data**: Base64 encoded
- **IV (Initialization Vector)**: Base64 encoded
- **Keys**: Base64 encoded
- **Tags**: Base64 encoded (for GCM mode)

---

## SDKs and Libraries

### PHP SDK
```bash
composer require service-learning/api-sdk
```

### JavaScript SDK
```bash
npm install @service-learning/api-sdk
```

### Python SDK
```bash
pip install service-learning-api-sdk
```

---

## Support

For API support and questions:
- **Email**: api-support@service-learning.com
- **Documentation**: https://docs.service-learning.com/api
- **Status Page**: https://status.service-learning.com

---

## Changelog

### Version 1.0.0 (2024-06-23)
- Initial API release
- E2EE encryption/decryption endpoints
- SOC2 compliance endpoints
- Security and performance monitoring
- Rate limiting and authentication 