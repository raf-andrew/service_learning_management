<?php

namespace Tests\MCP\Security;

use MCP\Security\Authentication;
use MCP\Security\RBAC;
use MCP\Exceptions\AuthenticationException;
use MCP\Interfaces\Authenticatable;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Authentication Service Test
 * 
 * Tests the functionality of the Authentication service.
 * 
 * @package Tests\MCP\Security
 */
class AuthenticationTest extends TestCase
{
    protected Authentication $auth;
    protected RBAC|MockObject $rbac;
    protected Authenticatable|MockObject $user;

    protected function setUp(): void
    {
        $this->rbac = $this->createMock(RBAC::class);
        $this->auth = new Authentication($this->rbac);
        $this->user = $this->createMock(Authenticatable::class);
    }

    /**
     * Test successful authentication
     */
    public function testSuccessfulAuthentication(): void
    {
        $this->user->method('getUsername')->willReturn('testuser');
        $this->user->method('getPasswordHash')->willReturn(password_hash('password123', PASSWORD_DEFAULT));
        $this->user->method('isActive')->willReturn(true);
        $this->user->method('hasMFA')->willReturn(false);

        $this->auth->authenticate('testuser', 'password123');

        $this->assertTrue($this->auth->isAuthenticated());
        $this->assertSame($this->user, $this->auth->getCurrentUser());
    }

    /**
     * Test authentication with invalid credentials
     */
    public function testAuthenticationWithInvalidCredentials(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid credentials.');

        $this->auth->authenticate('testuser', 'wrongpassword');
    }

    /**
     * Test authentication with inactive user
     */
    public function testAuthenticationWithInactiveUser(): void
    {
        $this->user->method('getUsername')->willReturn('testuser');
        $this->user->method('getPasswordHash')->willReturn(password_hash('password123', PASSWORD_DEFAULT));
        $this->user->method('isActive')->willReturn(false);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Account is inactive.');

        $this->auth->authenticate('testuser', 'password123');
    }

    /**
     * Test authentication with MFA required
     */
    public function testAuthenticationWithMFARequired(): void
    {
        $this->user->method('getUsername')->willReturn('testuser');
        $this->user->method('getPasswordHash')->willReturn(password_hash('password123', PASSWORD_DEFAULT));
        $this->user->method('isActive')->willReturn(true);
        $this->user->method('hasMFA')->willReturn(true);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('MFA verification required.');

        $this->auth->authenticate('testuser', 'password123');
    }

    /**
     * Test successful authentication with MFA
     */
    public function testSuccessfulAuthenticationWithMFA(): void
    {
        $this->user->method('getUsername')->willReturn('testuser');
        $this->user->method('getPasswordHash')->willReturn(password_hash('password123', PASSWORD_DEFAULT));
        $this->user->method('isActive')->willReturn(true);
        $this->user->method('hasMFA')->willReturn(true);
        $this->user->method('getMFASecret')->willReturn('testsecret');

        $this->auth->authenticate('testuser', 'password123', ['mfa_code' => '123456']);

        $this->assertTrue($this->auth->isAuthenticated());
        $this->assertSame($this->user, $this->auth->getCurrentUser());
    }

    /**
     * Test logout
     */
    public function testLogout(): void
    {
        $this->user->method('getUsername')->willReturn('testuser');
        $this->user->method('getPasswordHash')->willReturn(password_hash('password123', PASSWORD_DEFAULT));
        $this->user->method('isActive')->willReturn(true);
        $this->user->method('hasMFA')->willReturn(false);

        $this->auth->authenticate('testuser', 'password123');
        $this->assertTrue($this->auth->isAuthenticated());

        $this->auth->logout();
        $this->assertFalse($this->auth->isAuthenticated());
        $this->assertNull($this->auth->getCurrentUser());
    }

    /**
     * Test too many failed attempts
     */
    public function testTooManyFailedAttempts(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Too many failed login attempts. Please try again later.');

        // Simulate multiple failed attempts
        for ($i = 0; $i < 6; $i++) {
            try {
                $this->auth->authenticate('testuser', 'wrongpassword');
            } catch (AuthenticationException $e) {
                // Ignore individual failures
            }
        }

        // This should now throw the too many attempts exception
        $this->auth->authenticate('testuser', 'wrongpassword');
    }
} 