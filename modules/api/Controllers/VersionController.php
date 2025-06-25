<?php

namespace Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VersionController extends Controller
{
    /**
     * Get API version information
     */
    public function info(Request $request): JsonResponse
    {
        $currentVersion = config('modules.api.versioning.current', 'v1');
        $supportedVersions = config('modules.api.versioning.supported_versions', []);
        $requestedVersion = $request->header('X-API-Version', $currentVersion);

        return response()->json([
            'success' => true,
            'message' => 'API Version information retrieved successfully',
            'data' => [
                'current_version' => $currentVersion,
                'requested_version' => $requestedVersion,
                'supported_versions' => array_keys($supportedVersions),
                'version_details' => $supportedVersions,
                'deprecated_versions' => $this->getDeprecatedVersions($supportedVersions),
                'sunset_dates' => $this->getSunsetDates($supportedVersions),
                'migration_guide' => $this->getMigrationGuide(),
                'changelog' => $this->getChangelog(),
                'timestamp' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Get deprecated versions
     */
    protected function getDeprecatedVersions(array $supportedVersions): array
    {
        return collect($supportedVersions)
            ->filter(fn($version) => $version['deprecated'] ?? false)
            ->keys()
            ->toArray();
    }

    /**
     * Get sunset dates
     */
    protected function getSunsetDates(array $supportedVersions): array
    {
        return collect($supportedVersions)
            ->filter(fn($version) => isset($version['sunset_date']))
            ->mapWithKeys(fn($version, $key) => [$key => $version['sunset_date']])
            ->toArray();
    }

    /**
     * Get migration guide
     */
    protected function getMigrationGuide(): array
    {
        return [
            'v1_to_v2' => [
                'description' => 'Migration guide from v1 to v2',
                'breaking_changes' => [
                    'Authentication header format changed',
                    'Response format updated',
                    'New required fields in user creation',
                ],
                'steps' => [
                    'Update authentication headers',
                    'Update response handling',
                    'Update request payloads',
                ],
                'deprecation_notice' => 'v1 will be deprecated on 2024-12-31',
            ],
        ];
    }

    /**
     * Get changelog
     */
    protected function getChangelog(): array
    {
        return [
            'v2.0.0' => [
                'date' => '2024-01-01',
                'type' => 'major',
                'changes' => [
                    'Added new authentication methods',
                    'Improved rate limiting',
                    'Enhanced error handling',
                    'Added new endpoints',
                ],
                'breaking_changes' => [
                    'Changed response format',
                    'Updated authentication headers',
                ],
            ],
            'v1.1.0' => [
                'date' => '2023-12-01',
                'type' => 'minor',
                'changes' => [
                    'Added pagination support',
                    'Improved performance',
                    'Bug fixes',
                ],
                'breaking_changes' => [],
            ],
            'v1.0.0' => [
                'date' => '2023-11-01',
                'type' => 'major',
                'changes' => [
                    'Initial release',
                    'Basic CRUD operations',
                    'Authentication system',
                ],
                'breaking_changes' => [],
            ],
        ];
    }
} 