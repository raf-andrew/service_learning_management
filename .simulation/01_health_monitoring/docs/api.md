# Health Monitoring API Documentation

## Authentication
All API endpoints require authentication using an API key. Include the API key in the `X-API-Key` header with each request.

```http
X-API-Key: your-api-key-here
```

## Endpoints

### Health Checks

#### Perform Health Check
```http
POST /api/health/check
```

Request body:
```json
{
    "service_name": "string",
    "type": "http|tcp|command",
    "target": "string",
    "config": {
        "timeout": "integer",
        "retry_attempts": "integer",
        "retry_delay": "integer"
    }
}
```

Response (200 OK):
```json
{
    "status": "success",
    "data": {
        "check_id": "string",
        "service_name": "string",
        "status": "healthy|unhealthy",
        "response_time": "float",
        "timestamp": "datetime"
    }
}
```

#### Get Health Status
```http
GET /api/health/status
```

Response (200 OK):
```json
{
    "status": "success",
    "data": {
        "overall_status": "healthy|degraded|unhealthy",
        "services": [
            {
                "name": "string",
                "status": "healthy|unhealthy",
                "last_check": "datetime",
                "response_time": "float"
            }
        ]
    }
}
```

#### Get Health Metrics
```http
GET /api/health/metrics
```

Response (200 OK):
```json
{
    "status": "success",
    "data": {
        "uptime": "float",
        "response_times": {
            "average": "float",
            "p95": "float",
            "p99": "float"
        },
        "error_rates": {
            "total": "float",
            "by_service": {
                "service_name": "float"
            }
        }
    }
}
```

#### Get Health History
```http
GET /api/health/history
```

Query parameters:
- `service_name` (optional): Filter by service name
- `start_date` (optional): Start date for history
- `end_date` (optional): End date for history
- `status` (optional): Filter by status (healthy|unhealthy)

Response (200 OK):
```json
{
    "status": "success",
    "data": {
        "history": [
            {
                "check_id": "string",
                "service_name": "string",
                "status": "healthy|unhealthy",
                "response_time": "float",
                "timestamp": "datetime",
                "details": {
                    "error_message": "string",
                    "retry_count": "integer"
                }
            }
        ],
        "pagination": {
            "total": "integer",
            "per_page": "integer",
            "current_page": "integer",
            "last_page": "integer"
        }
    }
}
```

## Error Responses

### 400 Bad Request
```json
{
    "status": "error",
    "message": "Validation error",
    "errors": {
        "field": ["error message"]
    }
}
```

### 401 Unauthorized
```json
{
    "status": "error",
    "message": "API key is required"
}
```

### 403 Forbidden
```json
{
    "status": "error",
    "message": "Invalid API key"
}
```

### 429 Too Many Requests
```json
{
    "status": "error",
    "message": "Rate limit exceeded",
    "retry_after": "integer"
}
```

### 500 Internal Server Error
```json
{
    "status": "error",
    "message": "Internal server error"
}
```

## Rate Limiting
- 100 requests per minute per API key
- Rate limit headers included in responses:
  - `X-RateLimit-Limit`: Maximum requests per minute
  - `X-RateLimit-Remaining`: Remaining requests in current window
  - `X-RateLimit-Reset`: Time when rate limit resets (Unix timestamp)

## Best Practices
1. Always include the API key in the `X-API-Key` header
2. Handle rate limiting by checking response headers
3. Implement exponential backoff for retries
4. Monitor response times and error rates
5. Keep API keys secure and rotate them regularly 