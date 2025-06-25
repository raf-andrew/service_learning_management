<?php

namespace App\Modules\E2ee\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Modules\E2ee\Services\TransactionService;
use App\Modules\E2ee\Services\EncryptionService;
use App\Modules\E2ee\Exceptions\E2eeException;
use Illuminate\Support\Facades\Log;

class E2eeMiddleware
{
    /**
     * @var TransactionService
     */
    protected TransactionService $transactionService;

    /**
     * @var EncryptionService
     */
    protected EncryptionService $encryptionService;

    public function __construct(
        TransactionService $transactionService,
        EncryptionService $encryptionService
    ) {
        $this->transactionService = $transactionService;
        $this->encryptionService = $encryptionService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Check if E2EE is enabled
            if (!config('e2ee.enabled', true)) {
                return $next($request);
            }

            // Validate user authentication
            if (!$request->user()) {
                return response()->json([
                    'error' => 'Authentication required for E2EE operations',
                    'code' => 'AUTHENTICATION_REQUIRED'
                ], 401);
            }

            // Start transaction for E2EE operations
            $transaction = $this->transactionService->startTransaction(
                $request->user()->id,
                [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'endpoint' => $request->path(),
                    'method' => $request->method(),
                ]
            );

            // Add transaction to request for downstream use
            $request->attributes->set('e2ee_transaction', $transaction);

            // Process the request
            $response = $next($request);

            // Complete the transaction
            $this->transactionService->completeTransaction($transaction->transaction_id);

            return $response;

        } catch (E2eeException $e) {
            Log::error('E2EE Middleware Error', [
                'message' => $e->getMessage(),
                'code' => $e->getErrorCode(),
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'error' => 'E2EE Error',
                'message' => $e->getMessage(),
                'code' => $e->getErrorCode()
            ], 500);

        } catch (\Exception $e) {
            Log::error('E2EE Middleware Unexpected Error', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'An unexpected error occurred during E2EE processing'
            ], 500);
        }
    }

    /**
     * Handle response encryption if needed
     */
    public function encryptResponse(Response $response, Request $request): Response
    {
        try {
            // Check if response should be encrypted
            if (!$this->shouldEncryptResponse($request)) {
                return $response;
            }

            $transaction = $request->attributes->get('e2ee_transaction');
            if (!$transaction) {
                return $response;
            }

            // Get response content
            $content = $response->getContent();
            if (empty($content)) {
                return $response;
            }

            // Encrypt the response content
            $encryptedContent = $this->encryptionService->encrypt(
                $content,
                $transaction->transaction_id
            );

            // Update response with encrypted content
            $response->setContent(json_encode($encryptedContent));
            $response->header('X-E2EE-Encrypted', 'true');

            return $response;

        } catch (\Exception $e) {
            Log::error('E2EE Response Encryption Error', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            // Return original response if encryption fails
            return $response;
        }
    }

    /**
     * Handle request decryption if needed
     */
    public function decryptRequest(Request $request): Request
    {
        try {
            // Check if request should be decrypted
            if (!$this->shouldDecryptRequest($request)) {
                return $request;
            }

            $content = $request->getContent();
            if (empty($content)) {
                return $request;
            }

            // Parse encrypted content
            $encryptedData = json_decode($content, true);
            if (!$encryptedData || !isset($encryptedData['encrypted_data'])) {
                return $request;
            }

            // Decrypt the content
            $decryptedContent = $this->encryptionService->decrypt($encryptedData);

            // Update request with decrypted content
            $request->merge(json_decode($decryptedContent, true));

            return $request;

        } catch (\Exception $e) {
            Log::error('E2EE Request Decryption Error', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            // Return original request if decryption fails
            return $request;
        }
    }

    /**
     * Determine if response should be encrypted
     */
    protected function shouldEncryptResponse(Request $request): bool
    {
        // Check for E2EE encryption header
        if ($request->header('X-E2EE-Encrypt-Response') === 'true') {
            return true;
        }

        // Check if endpoint requires encryption
        $encryptEndpoints = config('e2ee.encrypt_endpoints', []);
        return in_array($request->path(), $encryptEndpoints);
    }

    /**
     * Determine if request should be decrypted
     */
    protected function shouldDecryptRequest(Request $request): bool
    {
        // Check for E2EE encryption header
        if ($request->header('X-E2EE-Encrypted') === 'true') {
            return true;
        }

        // Check if endpoint requires decryption
        $decryptEndpoints = config('e2ee.decrypt_endpoints', []);
        return in_array($request->path(), $decryptEndpoints);
    }
} 