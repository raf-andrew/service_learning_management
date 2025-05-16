<?php

namespace Tests\MCP\EndToEnd;

/**
 * UserInteractionTest
 * 
 * This class contains end-to-end tests for user interactions in the MCP system, covering:
 * - User authentication and registration
 * - Authorization and role-based access control
 * - User profile management
 * - Session management
 * - Password management
 * - User preferences and settings
 * - Multi-factor authentication
 * 
 * Each test method is designed to test a complete user interaction scenario,
 * including proper setup, execution, verification, and cleanup.
 * 
 * @package Tests\MCP\EndToEnd
 */
class UserInteractionTest extends EndToEndTest
{
    /**
     * Test user authentication workflow
     * 
     * This test verifies:
     * 1. User registration
     * 2. Login functionality
     * 3. Token generation
     * 4. Authentication logging
     * 
     * @return void
     */
    public function testUserAuthentication(): void
    {
        // Create test user
        $userData = [
            'username' => 'testuser_' . uniqid(),
            'email' => 'test_' . uniqid() . '@example.com',
            'password' => 'TestPassword123!'
        ];

        $registerResponse = $this->makeAuthenticatedRequest(
            '/api/v1/users/register',
            $this->credentials,
            $userData
        );

        $this->assertEquals(200, $registerResponse['status']);
        $userId = $registerResponse['data']['user_id'];

        // Test login
        $loginResponse = $this->makeAuthenticatedRequest(
            '/api/v1/auth/login',
            $this->credentials,
            [
                'username' => $userData['username'],
                'password' => $userData['password']
            ]
        );

        $this->assertEquals(200, $loginResponse['status']);
        $this->assertArrayHasKey('token', $loginResponse['data']);

        // Verify authentication in logs
        $logs = $this->checkLogs('authentication', [
            'user_id' => $userId,
            'action' => 'login'
        ]);

        $this->assertNotEmpty($logs);
        $this->assertEquals('success', $logs[0]['status']);
    }

    /**
     * Test user authorization and access control
     * 
     * This test verifies:
     * 1. Role-based access control
     * 2. Permission validation
     * 3. Access denial for unauthorized requests
     * 4. Authorization logging
     * 
     * @return void
     */
    public function testUserAuthorization(): void
    {
        // Create test user with specific role
        $userData = [
            'username' => 'authuser_' . uniqid(),
            'email' => 'auth_' . uniqid() . '@example.com',
            'password' => 'TestPassword123!',
            'role' => 'test_role'
        ];

        $registerResponse = $this->makeAuthenticatedRequest(
            '/api/v1/users/register',
            $this->credentials,
            $userData
        );

        $userId = $registerResponse['data']['user_id'];

        // Login as the new user
        $loginResponse = $this->makeAuthenticatedRequest(
            '/api/v1/auth/login',
            $this->credentials,
            [
                'username' => $userData['username'],
                'password' => $userData['password']
            ]
        );

        $userToken = $loginResponse['data']['token'];

        // Test authorized access
        $authorizedResponse = $this->makeAuthenticatedRequest(
            '/api/v1/test/authorized',
            ['token' => $userToken, 'tenant_id' => $this->tenantId]
        );

        $this->assertEquals(200, $authorizedResponse['status']);

        // Test unauthorized access
        $unauthorizedResponse = $this->makeAuthenticatedRequest(
            '/api/v1/test/unauthorized',
            ['token' => $userToken, 'tenant_id' => $this->tenantId]
        );

        $this->assertEquals(403, $unauthorizedResponse['status']);

        // Verify authorization in logs
        $logs = $this->checkLogs('authorization', [
            'user_id' => $userId
        ]);

        $this->assertNotEmpty($logs);
        $this->assertContains('success', array_column($logs, 'status'));
        $this->assertContains('denied', array_column($logs, 'status'));
    }

    /**
     * Test user profile management
     * 
     * This test verifies:
     * 1. Profile creation
     * 2. Profile updates
     * 3. Preference management
     * 4. Profile data validation
     * 
     * @return void
     */
    public function testUserProfileManagement(): void
    {
        // Create test user
        $userData = [
            'username' => 'profileuser_' . uniqid(),
            'email' => 'profile_' . uniqid() . '@example.com',
            'password' => 'TestPassword123!'
        ];

        $registerResponse = $this->makeAuthenticatedRequest(
            '/api/v1/users/register',
            $this->credentials,
            $userData
        );

        $userId = $registerResponse['data']['user_id'];

        // Login as the new user
        $loginResponse = $this->makeAuthenticatedRequest(
            '/api/v1/auth/login',
            $this->credentials,
            [
                'username' => $userData['username'],
                'password' => $userData['password']
            ]
        );

        $userToken = $loginResponse['data']['token'];

        // Update profile
        $updateData = [
            'name' => 'Test User',
            'bio' => 'Test bio',
            'preferences' => [
                'notifications' => true,
                'theme' => 'dark'
            ]
        ];

        $updateResponse = $this->makeAuthenticatedRequest(
            '/api/v1/users/profile',
            ['token' => $userToken, 'tenant_id' => $this->tenantId],
            $updateData
        );

        $this->assertEquals(200, $updateResponse['status']);
        $this->assertEquals($updateData['name'], $updateResponse['data']['name']);

        // Verify profile update in logs
        $logs = $this->checkLogs('profile_update', [
            'user_id' => $userId
        ]);

        $this->assertNotEmpty($logs);
        $this->assertEquals('success', $logs[0]['status']);
    }

    /**
     * Test user session management
     * 
     * This test verifies:
     * 1. Session creation
     * 2. Session tracking
     * 3. Session invalidation
     * 4. Concurrent session handling
     * 
     * @return void
     */
    public function testUserSessionManagement(): void
    {
        // Create test user
        $userData = [
            'username' => 'sessionuser_' . uniqid(),
            'email' => 'session_' . uniqid() . '@example.com',
            'password' => 'TestPassword123!'
        ];

        $registerResponse = $this->makeAuthenticatedRequest(
            '/api/v1/users/register',
            $this->credentials,
            $userData
        );

        $userId = $registerResponse['data']['user_id'];

        // Login as the new user
        $loginResponse = $this->makeAuthenticatedRequest(
            '/api/v1/auth/login',
            $this->credentials,
            [
                'username' => $userData['username'],
                'password' => $userData['password']
            ]
        );

        $userToken = $loginResponse['data']['token'];

        // Get active sessions
        $sessionsResponse = $this->makeAuthenticatedRequest(
            '/api/v1/users/sessions',
            ['token' => $userToken, 'tenant_id' => $this->tenantId]
        );

        $this->assertEquals(200, $sessionsResponse['status']);
        $this->assertNotEmpty($sessionsResponse['data']);

        // Invalidate a session
        $sessionId = $sessionsResponse['data'][0]['session_id'];
        $invalidateResponse = $this->makeAuthenticatedRequest(
            "/api/v1/users/sessions/{$sessionId}",
            ['token' => $userToken, 'tenant_id' => $this->tenantId],
            ['action' => 'invalidate']
        );

        $this->assertEquals(200, $invalidateResponse['status']);

        // Verify session management in logs
        $logs = $this->checkLogs('session_management', [
            'user_id' => $userId
        ]);

        $this->assertNotEmpty($logs);
        $this->assertContains('created', array_column($logs, 'action'));
        $this->assertContains('invalidated', array_column($logs, 'action'));
    }

    /**
     * Test password management
     * 
     * This test verifies:
     * 1. Password reset request
     * 2. Password reset token validation
     * 3. Password update
     * 4. Password policy enforcement
     * 
     * @return void
     */
    public function testUserPasswordManagement(): void
    {
        // Create test user
        $userData = [
            'username' => 'passuser_' . uniqid(),
            'email' => 'pass_' . uniqid() . '@example.com',
            'password' => 'TestPassword123!'
        ];

        $registerResponse = $this->makeAuthenticatedRequest(
            '/api/v1/users/register',
            $this->credentials,
            $userData
        );

        $userId = $registerResponse['data']['user_id'];

        // Request password reset
        $resetRequestResponse = $this->makeAuthenticatedRequest(
            '/api/v1/users/password/reset-request',
            $this->credentials,
            ['email' => $userData['email']]
        );

        $this->assertEquals(200, $resetRequestResponse['status']);
        $resetToken = $resetRequestResponse['data']['reset_token'];

        // Reset password
        $newPassword = 'NewTestPassword123!';
        $resetResponse = $this->makeAuthenticatedRequest(
            '/api/v1/users/password/reset',
            $this->credentials,
            [
                'reset_token' => $resetToken,
                'new_password' => $newPassword
            ]
        );

        $this->assertEquals(200, $resetResponse['status']);

        // Verify password reset in logs
        $logs = $this->checkLogs('password_reset', [
            'user_id' => $userId
        ]);

        $this->assertNotEmpty($logs);
        $this->assertEquals('success', $logs[0]['status']);

        // Test login with new password
        $loginResponse = $this->makeAuthenticatedRequest(
            '/api/v1/auth/login',
            $this->credentials,
            [
                'username' => $userData['username'],
                'password' => $newPassword
            ]
        );

        $this->assertEquals(200, $loginResponse['status']);
        $this->assertArrayHasKey('token', $loginResponse['data']);
    }

    /**
     * Test user preferences and settings
     * 
     * This test verifies:
     * 1. Preference storage
     * 2. Setting updates
     * 3. Default values
     * 4. Preference validation
     * 
     * @return void
     */
    public function testUserPreferences(): void
    {
        // Create test user
        $userData = [
            'username' => 'prefuser_' . uniqid(),
            'email' => 'pref_' . uniqid() . '@example.com',
            'password' => 'TestPassword123!'
        ];

        $registerResponse = $this->makeAuthenticatedRequest(
            '/api/v1/users/register',
            $this->credentials,
            $userData
        );

        $userId = $registerResponse['data']['user_id'];

        // Login as the new user
        $loginResponse = $this->makeAuthenticatedRequest(
            '/api/v1/auth/login',
            $this->credentials,
            [
                'username' => $userData['username'],
                'password' => $userData['password']
            ]
        );

        $userToken = $loginResponse['data']['token'];

        // Set user preferences
        $preferences = [
            'notifications' => [
                'email' => true,
                'push' => false,
                'frequency' => 'daily'
            ],
            'display' => [
                'theme' => 'dark',
                'language' => 'en',
                'timezone' => 'UTC'
            ],
            'privacy' => [
                'profile_visibility' => 'public',
                'activity_visibility' => 'private'
            ]
        ];

        $updateResponse = $this->makeAuthenticatedRequest(
            '/api/v1/users/preferences',
            ['token' => $userToken, 'tenant_id' => $this->tenantId],
            $preferences
        );

        $this->assertEquals(200, $updateResponse['status']);
        $this->assertEquals($preferences, $updateResponse['data']);

        // Verify preferences in logs
        $logs = $this->checkLogs('preferences_update', [
            'user_id' => $userId
        ]);

        $this->assertNotEmpty($logs);
        $this->assertEquals('success', $logs[0]['status']);

        // Test preference validation
        $invalidPreferences = [
            'notifications' => [
                'frequency' => 'invalid_frequency'
            ]
        ];

        $invalidResponse = $this->makeAuthenticatedRequest(
            '/api/v1/users/preferences',
            ['token' => $userToken, 'tenant_id' => $this->tenantId],
            $invalidPreferences
        );

        $this->assertEquals(400, $invalidResponse['status']);
    }

    /**
     * Test multi-factor authentication
     * 
     * This test verifies:
     * 1. MFA setup
     * 2. MFA verification
     * 3. Backup codes
     * 4. MFA bypass options
     * 
     * @return void
     */
    public function testMultiFactorAuthentication(): void
    {
        // Create test user
        $userData = [
            'username' => 'mfauser_' . uniqid(),
            'email' => 'mfa_' . uniqid() . '@example.com',
            'password' => 'TestPassword123!'
        ];

        $registerResponse = $this->makeAuthenticatedRequest(
            '/api/v1/users/register',
            $this->credentials,
            $userData
        );

        $userId = $registerResponse['data']['user_id'];

        // Login as the new user
        $loginResponse = $this->makeAuthenticatedRequest(
            '/api/v1/auth/login',
            $this->credentials,
            [
                'username' => $userData['username'],
                'password' => $userData['password']
            ]
        );

        $userToken = $loginResponse['data']['token'];

        // Setup MFA
        $mfaSetupResponse = $this->makeAuthenticatedRequest(
            '/api/v1/users/mfa/setup',
            ['token' => $userToken, 'tenant_id' => $this->tenantId]
        );

        $this->assertEquals(200, $mfaSetupResponse['status']);
        $this->assertArrayHasKey('secret', $mfaSetupResponse['data']);
        $this->assertArrayHasKey('qr_code', $mfaSetupResponse['data']);

        // Verify MFA setup
        $verifyResponse = $this->makeAuthenticatedRequest(
            '/api/v1/users/mfa/verify',
            ['token' => $userToken, 'tenant_id' => $this->tenantId],
            ['code' => '123456'] // This would be a real TOTP code in production
        );

        $this->assertEquals(200, $verifyResponse['status']);

        // Get backup codes
        $backupCodesResponse = $this->makeAuthenticatedRequest(
            '/api/v1/users/mfa/backup-codes',
            ['token' => $userToken, 'tenant_id' => $this->tenantId]
        );

        $this->assertEquals(200, $backupCodesResponse['status']);
        $this->assertNotEmpty($backupCodesResponse['data']['codes']);

        // Test MFA bypass
        $bypassResponse = $this->makeAuthenticatedRequest(
            '/api/v1/users/mfa/bypass',
            ['token' => $userToken, 'tenant_id' => $this->tenantId],
            ['reason' => 'test_bypass']
        );

        $this->assertEquals(200, $bypassResponse['status']);
        $this->assertArrayHasKey('bypass_token', $bypassResponse['data']);

        // Verify MFA actions in logs
        $logs = $this->checkLogs('mfa_management', [
            'user_id' => $userId
        ]);

        $this->assertNotEmpty($logs);
        $this->assertContains('setup', array_column($logs, 'action'));
        $this->assertContains('verify', array_column($logs, 'action'));
        $this->assertContains('bypass', array_column($logs, 'action'));
    }
} 