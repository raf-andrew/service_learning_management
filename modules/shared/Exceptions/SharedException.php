<?php

namespace Modules\Shared\Exceptions;

use Exception;

class SharedException extends Exception
{
    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'timestamp' => now()->toISOString(),
        ], $this->getCode() ?: 500);
    }
} 