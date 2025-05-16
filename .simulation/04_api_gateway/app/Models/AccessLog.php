<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessLog extends Model
{
    protected $fillable = [
        'route_id',
        'api_key_id',
        'ip_address',
        'user_agent',
        'request_method',
        'request_path',
        'request_headers',
        'request_body',
        'response_status',
        'response_headers',
        'response_body',
        'response_time',
        'error_message',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'request_body' => 'array',
        'response_headers' => 'array',
        'response_body' => 'array',
        'response_time' => 'float',
    ];

    /**
     * Get the route that owns this access log.
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    /**
     * Get the API key that owns this access log.
     */
    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    /**
     * Check if the request was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->response_status >= 200 && $this->response_status < 300;
    }

    /**
     * Get the formatted response time.
     */
    public function getFormattedResponseTime(): string
    {
        return number_format($this->response_time, 2) . 'ms';
    }

    /**
     * Get the error message if any.
     */
    public function getErrorMessage(): ?string
    {
        return $this->error_message;
    }

    /**
     * Get the request headers as a string.
     */
    public function getRequestHeadersString(): string
    {
        return json_encode($this->request_headers, JSON_PRETTY_PRINT);
    }

    /**
     * Get the response headers as a string.
     */
    public function getResponseHeadersString(): string
    {
        return json_encode($this->response_headers, JSON_PRETTY_PRINT);
    }
} 