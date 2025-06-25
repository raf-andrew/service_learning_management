<?php

namespace App\Services\Web3;

use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;

class ServiceVerification
{
    private $web3Service;

    public function __construct(Web3Service $web3Service)
    {
        $this->web3Service = $web3Service;
    }

    public function verify(int $serviceId, int $userId): bool
    {
        try {
            $service = Service::findOrFail($serviceId);
            $user = User::findOrFail($userId);

            if (!$this->web3Service->isVerifier($user->wallet_address)) {
                Log::warning("User {$userId} is not authorized to verify services");
                return false;
            }

            $result = $this->web3Service->verifyService($serviceId, $user->wallet_address);
            
            if ($result) {
                $service->update(['verified' => true]);
                Event::dispatch('service.verified', [$service, $user]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("Service verification failed: {$e->getMessage()}");
            return false;
        }
    }

    public function getServiceDetails(int $serviceId): ?array
    {
        try {
            $service = Service::findOrFail($serviceId);
            $web3Details = $this->web3Service->getServiceDetails($serviceId);

            if (!$web3Details) {
                return null;
            }

            return array_merge($service->toArray(), [
                'web3_details' => $web3Details
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to get service details: {$e->getMessage()}");
            return null;
        }
    }
} 