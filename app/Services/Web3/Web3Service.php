<?php

namespace App\Services\Web3;

use Illuminate\Support\Facades\Log;
use Web3\Web3;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Contract;

class Web3Service
{
    private $web3;
    private $contract;
    private $contractAddress;
    private $provider;

    public function __construct()
    {
        $this->provider = new HttpProvider(new HttpRequestManager(env('WEB3_PROVIDER_URL')));
        $this->web3 = new Web3($this->provider);
        $this->contractAddress = env('SERVICE_VERIFICATION_CONTRACT_ADDRESS');
        $this->initializeContract();
    }

    private function initializeContract()
    {
        try {
            $contractABI = json_decode(file_get_contents(base_path('.web3/contracts/ServiceVerification.json')), true);
            $this->contract = new Contract($this->provider, $contractABI);
            $this->contract->at($this->contractAddress);
        } catch (\Exception $e) {
            Log::error('Failed to initialize Web3 contract: ' . $e->getMessage());
            throw new \RuntimeException('Web3 contract initialization failed');
        }
    }

    public function verifyService(int $serviceId, string $verifierAddress): bool
    {
        try {
            $result = $this->contract->call('verifyService', [$serviceId], [
                'from' => $verifierAddress,
                'gas' => 3000000
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Service verification failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getServiceDetails(int $serviceId): ?array
    {
        try {
            $result = $this->contract->call('getService', [$serviceId]);
            return [
                'title' => $result[0],
                'description' => $result[1],
                'ipfsHash' => $result[2],
                'reward' => $result[3],
                'verified' => $result[4],
                'verifier' => $result[5]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get service details: ' . $e->getMessage());
            return null;
        }
    }

    public function isVerifier(string $address): bool
    {
        try {
            return $this->contract->call('isVerifier', [$address]);
        } catch (\Exception $e) {
            Log::error('Failed to check verifier status: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get Web3 service status for all networks
     */
    public function getStatus(): array
    {
        $networks = ['ethereum', 'polygon', 'bsc'];
        $status = [];

        foreach ($networks as $network) {
            $status[] = [
                'network' => $network,
                'connected' => $this->isNetworkConnected($network),
                'address' => $this->getConnectedAddress($network),
                'last_check' => now()->format('Y-m-d H:i:s')
            ];
        }

        return $status;
    }

    /**
     * Connect to a specific network
     */
    public function connectToNetwork(string $network): bool
    {
        try {
            Log::info("Connecting to {$network} network");
            // Implementation would connect to the specific network
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to connect to {$network}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Connect to all available networks
     */
    public function connectToAllNetworks(): bool
    {
        try {
            $networks = ['ethereum', 'polygon', 'bsc'];
            foreach ($networks as $network) {
                $this->connectToNetwork($network);
            }
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to connect to all networks: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Disconnect from a specific network
     */
    public function disconnectFromNetwork(string $network): void
    {
        try {
            Log::info("Disconnecting from {$network} network");
            // Implementation would disconnect from the specific network
        } catch (\Exception $e) {
            Log::error("Failed to disconnect from {$network}: " . $e->getMessage());
        }
    }

    /**
     * Disconnect from all networks
     */
    public function disconnectFromAllNetworks(): void
    {
        try {
            $networks = ['ethereum', 'polygon', 'bsc'];
            foreach ($networks as $network) {
                $this->disconnectFromNetwork($network);
            }
        } catch (\Exception $e) {
            Log::error("Failed to disconnect from all networks: " . $e->getMessage());
        }
    }

    /**
     * Verify an address across all networks
     */
    public function verifyAddress(string $address): array
    {
        try {
            return [
                'valid' => $this->isValidAddress($address),
                'checksum' => $this->isValidChecksum($address),
                'network' => 'ethereum', // Default network
                'balance' => '0 ETH' // Placeholder
            ];
        } catch (\Exception $e) {
            Log::error("Failed to verify address {$address}: " . $e->getMessage());
            return [
                'valid' => false,
                'checksum' => false,
                'network' => 'unknown',
                'balance' => '0 ETH'
            ];
        }
    }

    /**
     * Verify an address on a specific network
     */
    public function verifyAddressOnNetwork(string $address, string $network): array
    {
        try {
            return [
                'valid' => $this->isValidAddress($address),
                'checksum' => $this->isValidChecksum($address),
                'network' => $network,
                'balance' => $this->getBalance($address, $network)
            ];
        } catch (\Exception $e) {
            Log::error("Failed to verify address {$address} on {$network}: " . $e->getMessage());
            return [
                'valid' => false,
                'checksum' => false,
                'network' => $network,
                'balance' => '0 ETH'
            ];
        }
    }

    /**
     * Check if network is connected
     */
    private function isNetworkConnected(string $network): bool
    {
        // Placeholder implementation
        return in_array($network, ['ethereum', 'polygon']);
    }

    /**
     * Get connected address for network
     */
    private function getConnectedAddress(string $network): ?string
    {
        // Placeholder implementation
        return $this->isNetworkConnected($network) ? '0x1234567890abcdef' : null;
    }

    /**
     * Validate address format
     */
    private function isValidAddress(string $address): bool
    {
        return preg_match('/^0x[a-fA-F0-9]{40}$/', $address);
    }

    /**
     * Validate address checksum
     */
    private function isValidChecksum(string $address): bool
    {
        // Placeholder implementation
        return $this->isValidAddress($address);
    }

    /**
     * Get balance for address on network
     */
    private function getBalance(string $address, string $network): string
    {
        // Placeholder implementation
        return '1.5 ETH';
    }
} 