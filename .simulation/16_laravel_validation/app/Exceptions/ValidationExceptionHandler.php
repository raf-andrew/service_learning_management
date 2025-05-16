<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Throwable;

class ValidationExceptionHandler extends ExceptionHandler
{
    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->renderable(function (ValidationException $e, $request) {
            if ($request->expectsJson()) {
                $this->logValidationErrors($e);
                
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $this->formatValidationErrors($e),
                    'status' => 'error',
                    'code' => 422
                ], 422);
            }
        });
    }

    /**
     * Format the validation errors for the response.
     *
     * @param  \Illuminate\Validation\ValidationException  $e
     * @return array
     */
    protected function formatValidationErrors(ValidationException $e)
    {
        $errors = [];
        
        foreach ($e->errors() as $field => $messages) {
            $errors[$field] = [
                'messages' => $messages,
                'field' => $field,
                'value' => request()->input($field)
            ];
        }

        return $errors;
    }

    /**
     * Log validation errors with context.
     *
     * @param  \Illuminate\Validation\ValidationException  $e
     * @return void
     */
    protected function logValidationErrors(ValidationException $e)
    {
        Log::warning('Validation failed', [
            'errors' => $e->errors(),
            'input' => request()->all(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'user' => auth()->id()
        ]);
    }
} 