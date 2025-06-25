<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_route_returns_correct_view()
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    public function test_register_route_returns_correct_view()
    {
        $response = $this->get(route('register'));
        $response->assertStatus(200);
        $response->assertViewIs('auth.register');
    }

    public function test_forgot_password_route_returns_correct_view()
    {
        $response = $this->get(route('password.request'));
        $response->assertStatus(200);
        $response->assertViewIs('auth.forgot-password');
    }

    public function test_reset_password_route_returns_correct_view()
    {
        $token = 'test-token';
        $response = $this->get(route('password.reset', ['token' => $token]));
        $response->assertStatus(200);
        $response->assertViewIs('auth.reset-password');
    }

    public function test_login_post_route_handles_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_login_post_route_handles_invalid_credentials()
    {
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_register_post_route_creates_new_user()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->post(route('register'), $userData);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com'
        ]);
        $this->assertAuthenticated();
    }

    public function test_forgot_password_post_route_sends_reset_link()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com'
        ]);

        $response = $this->post(route('password.email'), [
            'email' => 'test@example.com'
        ]);

        $response->assertSessionHas('status');
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'test@example.com'
        ]);
    }

    public function test_reset_password_post_route_updates_password()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('oldpassword')
        ]);

        $token = $this->createPasswordResetToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertRedirect(route('login'));
        $this->assertTrue(
            auth()->attempt([
                'email' => 'test@example.com',
                'password' => 'newpassword123'
            ])
        );
    }

    public function test_logout_route_ends_user_session()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('logout'));

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    private function createPasswordResetToken($user)
    {
        return app('auth.password.tokens')->create($user);
    }
} 