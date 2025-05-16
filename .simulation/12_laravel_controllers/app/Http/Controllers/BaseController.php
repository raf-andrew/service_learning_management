<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class BaseController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Success response method.
     *
     * @param  mixed  $data
     * @param  string  $message
     * @param  int  $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse($data, string $message = null, int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $code);
    }

    /**
     * Error response method.
     *
     * @param  string  $message
     * @param  int  $code
     * @param  mixed  $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse(string $message, int $code = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Log error and return error response.
     *
     * @param  \Exception  $e
     * @param  string  $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleException(\Exception $e, string $message = 'An error occurred'): JsonResponse
    {
        Log::error($e->getMessage(), [
            'exception' => $e,
            'trace' => $e->getTraceAsString()
        ]);

        return $this->errorResponse($message, 500);
    }

    /**
     * Validate request data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $rules
     * @return bool
     */
    protected function validateRequest($request, array $rules): bool
    {
        try {
            $this->validate($request, $rules);
            return true;
        } catch (\Illuminate\Validation\ValidationException $e) {
            return false;
        }
    }
} 