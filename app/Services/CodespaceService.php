<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\DeveloperCredential;
use App\Models\User;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CodespaceService
{
    protected $baseUrl = 'https://api.github.com';
    protected $token;
    private $repository;
    private $branch;
    private $credentialService;

    public function __construct(DeveloperCredentialService $credentialService)
    {
        $this->token = config('services.github.token');
        $this->repository = config('codespaces.repository');
        $this->branch = config('codespaces.branch', 'main');
        $this->credentialService = $credentialService;
    }

    private function getActiveCredential(): ?DeveloperCredential
    {
        $user = Auth::user();
        if (!$user) {
            throw new \RuntimeException('No authenticated user found');
        }

        $credential = $this->credentialService->getActiveCredential($user);
        if (!$credential) {
            throw new \RuntimeException('No active GitHub credentials found');
        }

        return $credential;
    }

    private function executeCommand(string $command): Process
    {
        $credential = $this->getActiveCredential();
        $token = decrypt($credential->github_token);
        
        $process = Process::fromShellCommandline($command);
        $process->setEnv(['GITHUB_TOKEN' => $token]);
        $process->run();
        
        return $process;
    }

    public function create($name, $branch = 'main', $region = null, $machine = null)
    {
        $response = Http::withToken($this->token)
            ->post("{$this->baseUrl}/user/codespaces", [
                'repository_id' => config('services.github.repository_id'),
                'name' => $name,
                'ref' => $branch,
                'location' => $region,
                'machine' => $machine,
            ]);

        if (!$response->successful()) {
            throw new \Exception("Failed to create codespace: {$response->body()}");
        }

        return $response->json();
    }

    public function list()
    {
        $response = Http::withToken($this->token)
            ->get("{$this->baseUrl}/user/codespaces");

        if (!$response->successful()) {
            throw new \Exception("Failed to list codespaces: {$response->body()}");
        }

        return collect($response->json()['codespaces'])->map(function ($codespace) {
            return [
                'name' => $codespace['name'],
                'branch' => $codespace['git_status']['ref'],
                'status' => $codespace['state'],
                'region' => $codespace['location'],
            ];
        })->toArray();
    }

    public function delete($name)
    {
        $response = Http::withToken($this->token)
            ->delete("{$this->baseUrl}/user/codespaces/{$name}");

        if (!$response->successful()) {
            throw new \Exception("Failed to delete codespace: {$response->body()}");
        }

        return true;
    }

    public function rebuild($name)
    {
        $response = Http::withToken($this->token)
            ->post("{$this->baseUrl}/user/codespaces/{$name}/rebuild");

        if (!$response->successful()) {
            throw new \Exception("Failed to rebuild codespace: {$response->body()}");
        }

        return $response->json();
    }

    public function getStatus($name)
    {
        $response = Http::withToken($this->token)
            ->get("{$this->baseUrl}/user/codespaces/{$name}");

        if (!$response->successful()) {
            throw new \Exception("Failed to get codespace status: {$response->body()}");
        }

        return $response->json();
    }

    public function connect($name)
    {
        $response = Http::withToken($this->token)
            ->post("{$this->baseUrl}/user/codespaces/{$name}/start");

        if (!$response->successful()) {
            throw new \Exception("Failed to connect to codespace: {$response->body()}");
        }

        return $response->json();
    }

    public function validateDeveloperAccess(DeveloperCredential $credential)
    {
        // Check if the developer has access to the repository
        $response = Http::withToken($credential->github_token)
            ->get("{$this->baseUrl}/repos/" . config('services.github.repository'));

        return $response->successful();
    }

    public function getAvailableRegions()
    {
        return Cache::remember('github_codespace_regions', 3600, function () {
            $response = Http::withToken($this->token)
                ->get("{$this->baseUrl}/codespaces/regions");

            if (!$response->successful()) {
                throw new \Exception("Failed to get available regions: {$response->body()}");
            }

            return $response->json()['regions'];
        });
    }

    public function getAvailableMachines()
    {
        return Cache::remember('github_codespace_machines', 3600, function () {
            $response = Http::withToken($this->token)
                ->get("{$this->baseUrl}/codespaces/machines");

            if (!$response->successful()) {
                throw new \Exception("Failed to get available machines: {$response->body()}");
            }

            return $response->json()['machines'];
        });
    }
} 