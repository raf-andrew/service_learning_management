<?php

namespace Tests\Feature\Settings;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoutesTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_settings_route_requires_authentication()
    {
        $response = $this->get(route('settings.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_settings_route_requires_admin_role()
    {
        $regularUser = User::factory()->create(['role' => 'user']);
        
        $response = $this->actingAs($regularUser)
                        ->get(route('settings.index'));

        $response->assertStatus(403);
    }

    public function test_settings_route_returns_correct_view()
    {
        $response = $this->actingAs($this->admin)
                        ->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertViewIs('settings.index');
    }

    public function test_settings_route_includes_required_sections()
    {
        $response = $this->actingAs($this->admin)
                        ->get(route('settings.index'));

        $response->assertViewHas('sections', function ($sections) {
            return in_array('general', $sections) &&
                   in_array('security', $sections) &&
                   in_array('notifications', $sections) &&
                   in_array('appearance', $sections);
        });
    }

    public function test_general_settings_update_route_validates_input()
    {
        $response = $this->actingAs($this->admin)
                        ->put(route('settings.general'), [
                            'site_name' => '',
                            'site_description' => '',
                        ]);

        $response->assertSessionHasErrors(['site_name', 'site_description']);
    }

    public function test_general_settings_update_route_updates_settings()
    {
        $settings = [
            'site_name' => 'Updated Site Name',
            'site_description' => 'Updated site description',
            'contact_email' => 'contact@example.com',
        ];

        $response = $this->actingAs($this->admin)
                        ->put(route('settings.general'), $settings);

        $response->assertRedirect(route('settings.index'));
        $this->assertDatabaseHas('settings', [
            'key' => 'site_name',
            'value' => 'Updated Site Name',
        ]);
    }

    public function test_security_settings_update_route_updates_settings()
    {
        $settings = [
            'password_policy' => [
                'min_length' => 12,
                'require_special_chars' => true,
                'require_numbers' => true,
            ],
            'session_timeout' => 60,
            'two_factor_required' => true,
        ];

        $response = $this->actingAs($this->admin)
                        ->put(route('settings.security'), $settings);

        $response->assertRedirect(route('settings.index'));
        $this->assertDatabaseHas('settings', [
            'key' => 'password_policy',
            'value' => json_encode($settings['password_policy']),
        ]);
    }

    public function test_notification_settings_update_route_updates_settings()
    {
        $settings = [
            'email_notifications' => [
                'enabled' => true,
                'from_address' => 'noreply@example.com',
                'from_name' => 'System Notifications',
            ],
            'push_notifications' => [
                'enabled' => true,
                'vapid_public_key' => 'test-public-key',
            ],
        ];

        $response = $this->actingAs($this->admin)
                        ->put(route('settings.notifications'), $settings);

        $response->assertRedirect(route('settings.index'));
        $this->assertDatabaseHas('settings', [
            'key' => 'email_notifications',
            'value' => json_encode($settings['email_notifications']),
        ]);
    }

    public function test_appearance_settings_update_route_updates_settings()
    {
        $settings = [
            'theme' => 'dark',
            'primary_color' => '#1a73e8',
            'secondary_color' => '#34a853',
            'font_family' => 'Inter',
        ];

        $response = $this->actingAs($this->admin)
                        ->put(route('settings.appearance'), $settings);

        $response->assertRedirect(route('settings.index'));
        $this->assertDatabaseHas('settings', [
            'key' => 'theme',
            'value' => 'dark',
        ]);
    }

    public function test_settings_backup_route_creates_backup()
    {
        $response = $this->actingAs($this->admin)
                        ->post(route('settings.backup'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'backup_path',
            'created_at',
        ]);
    }

    public function test_settings_restore_route_restores_backup()
    {
        $backupFile = 'backup-2024-01-01.json';
        
        $response = $this->actingAs($this->admin)
                        ->post(route('settings.restore'), [
                            'backup_file' => $backupFile,
                        ]);

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHas('success');
    }

    public function test_settings_clear_cache_route_clears_cache()
    {
        $response = $this->actingAs($this->admin)
                        ->post(route('settings.clear-cache'));

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHas('success');
    }
} 