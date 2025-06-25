<?php

namespace Tests\Feature\Dashboard;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoutesTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_dashboard_route_requires_authentication()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_route_returns_correct_view()
    {
        $response = $this->actingAs($this->user)
                        ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.index');
    }

    public function test_dashboard_route_includes_user_data()
    {
        $response = $this->actingAs($this->user)
                        ->get(route('dashboard'));

        $response->assertViewHas('user');
        $response->assertViewHas('stats');
    }

    public function test_dashboard_route_includes_required_components()
    {
        $response = $this->actingAs($this->user)
                        ->get(route('dashboard'));

        $response->assertViewHas('components', function ($components) {
            return in_array('dashboard.stats', $components) &&
                   in_array('dashboard.recent-activity', $components) &&
                   in_array('dashboard.quick-actions', $components);
        });
    }

    public function test_dashboard_route_handles_user_permissions()
    {
        $adminUser = User::factory()->create(['role' => 'admin']);
        $regularUser = User::factory()->create(['role' => 'user']);

        // Test admin dashboard
        $adminResponse = $this->actingAs($adminUser)
                            ->get(route('dashboard'));
        $adminResponse->assertViewHas('isAdmin', true);

        // Test regular user dashboard
        $userResponse = $this->actingAs($regularUser)
                           ->get(route('dashboard'));
        $userResponse->assertViewHas('isAdmin', false);
    }

    public function test_dashboard_route_includes_notifications()
    {
        $response = $this->actingAs($this->user)
                        ->get(route('dashboard'));

        $response->assertViewHas('notifications');
    }

    public function test_dashboard_route_includes_quick_actions()
    {
        $response = $this->actingAs($this->user)
                        ->get(route('dashboard'));

        $response->assertViewHas('quickActions', function ($actions) {
            return is_array($actions) && !empty($actions);
        });
    }

    public function test_dashboard_route_includes_recent_activity()
    {
        $response = $this->actingAs($this->user)
                        ->get(route('dashboard'));

        $response->assertViewHas('recentActivity', function ($activities) {
            return is_array($activities);
        });
    }

    public function test_dashboard_route_includes_user_preferences()
    {
        $response = $this->actingAs($this->user)
                        ->get(route('dashboard'));

        $response->assertViewHas('preferences', function ($preferences) {
            return is_array($preferences) && 
                   isset($preferences['theme']) && 
                   isset($preferences['notifications']);
        });
    }

    public function test_dashboard_route_handles_missing_data_gracefully()
    {
        $newUser = User::factory()->create();
        
        $response = $this->actingAs($newUser)
                        ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('recentActivity', []);
        $response->assertViewHas('notifications', []);
    }
} 