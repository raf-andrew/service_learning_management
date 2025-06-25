<?php

namespace App\Models\GitHub;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class Repository extends Model
{
    protected $fillable = [
        'name',
        'full_name',
        'default_branch',
        'settings',
        'permissions'
    ];

    protected $casts = [
        'settings' => 'array',
        'permissions' => 'array'
    ];

    public function syncFromGitHub()
    {
        $token = Config::getToken();
        if (!$token) {
            throw new \Exception('GitHub token not configured');
        }

        $response = Http::withToken($token)
            ->get("https://api.github.com/repos/{$this->full_name}");

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch repository data from GitHub');
        }

        $data = $response->json();
        
        $this->update([
            'name' => $data['name'],
            'full_name' => $data['full_name'],
            'default_branch' => $data['default_branch'],
            'settings' => [
                'private' => $data['private'],
                'description' => $data['description'],
                'homepage' => $data['homepage'],
                'has_issues' => $data['has_issues'],
                'has_wiki' => $data['has_wiki'],
                'has_pages' => $data['has_pages'],
            ],
            'permissions' => $data['permissions'] ?? []
        ]);

        return $this;
    }

    public function updateGitHubSettings()
    {
        $token = Config::getToken();
        if (!$token) {
            throw new \Exception('GitHub token not configured');
        }

        $response = Http::withToken($token)
            ->patch("https://api.github.com/repos/{$this->full_name}", [
                'name' => $this->name,
                'description' => $this->settings['description'] ?? null,
                'homepage' => $this->settings['homepage'] ?? null,
                'private' => $this->settings['private'] ?? false,
                'has_issues' => $this->settings['has_issues'] ?? true,
                'has_wiki' => $this->settings['has_wiki'] ?? true,
                'has_pages' => $this->settings['has_pages'] ?? false,
            ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to update repository settings on GitHub');
        }

        return $this;
    }
} 