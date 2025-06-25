<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;

trait SecurityTestTrait
{
    /**
     * Security test configuration
     */
    protected array $securityConfig = [
        'encryption_enabled' => true,
        'authentication_required' => true,
        'authorization_required' => true,
        'input_validation_required' => true,
        'csrf_protection_required' => true,
        'rate_limiting_enabled' => true,
    ];

    /**
     * Test encryption functionality
     */
    protected function testEncryption(string $data): void
    {
        $encrypted = Crypt::encryptString($data);
        $decrypted = Crypt::decryptString($encrypted);
        
        $this->assertEquals($data, $decrypted, 'Encryption/decryption failed');
        $this->assertNotEquals($data, $encrypted, 'Data was not encrypted');
    }

    /**
     * Test password hashing
     */
    protected function testPasswordHashing(string $password): void
    {
        $hashed = Hash::make($password);
        
        $this->assertTrue(Hash::check($password, $hashed), 'Password hashing failed');
        $this->assertFalse(Hash::check('wrong_password', $hashed), 'Password verification failed');
    }

    /**
     * Test authentication requirements
     */
    protected function testAuthenticationRequirements(string $endpoint, string $method = 'GET'): void
    {
        $response = $this->call($method, $endpoint);
        
        if ($this->securityConfig['authentication_required']) {
            $this->assertContains(
                $response->getStatusCode(),
                [401, 403],
                'Endpoint should require authentication'
            );
        }
    }

    /**
     * Test authorization requirements
     */
    protected function testAuthorizationRequirements(string $endpoint, string $method = 'GET'): void
    {
        // First authenticate
        $user = $this->createTestUser();
        $this->actingAs($user);
        
        $response = $this->call($method, $endpoint);
        
        if ($this->securityConfig['authorization_required']) {
            $this->assertContains(
                $response->getStatusCode(),
                [403, 404],
                'Endpoint should require proper authorization'
            );
        }
    }

    /**
     * Test input validation
     */
    protected function testInputValidation(string $endpoint, array $invalidData, string $method = 'POST'): void
    {
        $response = $this->call($method, $endpoint, $invalidData);
        
        if ($this->securityConfig['input_validation_required']) {
            $this->assertEquals(422, $response->getStatusCode(), 'Invalid input should be rejected');
        }
    }

    /**
     * Test CSRF protection
     */
    protected function testCsrfProtection(string $endpoint, string $method = 'POST'): void
    {
        $response = $this->call($method, $endpoint);
        
        if ($this->securityConfig['csrf_protection_required']) {
            $this->assertEquals(419, $response->getStatusCode(), 'CSRF protection should be active');
        }
    }

    /**
     * Test rate limiting
     */
    protected function testRateLimiting(string $endpoint, int $maxAttempts = 60): void
    {
        if (!$this->securityConfig['rate_limiting_enabled']) {
            $this->markTestSkipped('Rate limiting not enabled');
        }

        // Make multiple requests
        for ($i = 0; $i <= $maxAttempts; $i++) {
            $response = $this->call('GET', $endpoint);
            
            if ($response->getStatusCode() === 429) {
                $this->assertEquals(429, $response->getStatusCode(), 'Rate limiting should be active');
                return;
            }
        }
        
        $this->fail('Rate limiting not working as expected');
    }

    /**
     * Test SQL injection protection
     */
    protected function testSqlInjectionProtection(string $endpoint, array $maliciousInputs): void
    {
        foreach ($maliciousInputs as $input) {
            $response = $this->call('POST', $endpoint, ['input' => $input]);
            
            // Should not result in a 500 error (which might indicate SQL injection)
            $this->assertNotEquals(500, $response->getStatusCode(), 'SQL injection protection failed');
        }
    }

    /**
     * Test XSS protection
     */
    protected function testXssProtection(string $endpoint, array $maliciousInputs): void
    {
        foreach ($maliciousInputs as $input) {
            $response = $this->call('POST', $endpoint, ['input' => $input]);
            
            if ($response->getStatusCode() === 200) {
                $content = $response->getContent();
                
                // Check that malicious script tags are not present in response
                $this->assertStringNotContainsString('<script>', $content, 'XSS protection failed');
                $this->assertStringNotContainsString('javascript:', $content, 'XSS protection failed');
            }
        }
    }

    /**
     * Test secure headers
     */
    protected function testSecureHeaders(string $endpoint): void
    {
        $response = $this->call('GET', $endpoint);
        $headers = $response->headers;
        
        // Test for common security headers
        $securityHeaders = [
            'X-Frame-Options',
            'X-Content-Type-Options',
            'X-XSS-Protection',
            'Strict-Transport-Security',
        ];
        
        foreach ($securityHeaders as $header) {
            $this->assertTrue(
                $headers->has($header),
                "Security header {$header} is missing"
            );
        }
    }

    /**
     * Test session security
     */
    protected function testSessionSecurity(): void
    {
        $config = Config::get('session');
        
        $this->assertTrue($config['secure'], 'Session should be secure');
        $this->assertTrue($config['http_only'], 'Session should be HTTP only');
        $this->assertTrue($config['same_site'] === 'lax' || $config['same_site'] === 'strict', 'Session should have proper SameSite setting');
    }

    /**
     * Test file upload security
     */
    protected function testFileUploadSecurity(string $endpoint, array $maliciousFiles): void
    {
        foreach ($maliciousFiles as $file) {
            $response = $this->call('POST', $endpoint, [], [], ['file' => $file]);
            
            // Should reject malicious files
            $this->assertNotEquals(200, $response->getStatusCode(), 'Malicious file upload should be rejected');
        }
    }

    /**
     * Test authentication bypass
     */
    protected function testAuthenticationBypass(string $endpoint, array $bypassAttempts): void
    {
        foreach ($bypassAttempts as $attempt) {
            $response = $this->call('GET', $endpoint, $attempt);
            
            // Should not allow authentication bypass
            $this->assertNotEquals(200, $response->getStatusCode(), 'Authentication bypass should not be possible');
        }
    }

    /**
     * Test privilege escalation
     */
    protected function testPrivilegeEscalation(string $endpoint, array $escalationAttempts): void
    {
        $user = $this->createTestUser(['role' => 'user']);
        $this->actingAs($user);
        
        foreach ($escalationAttempts as $attempt) {
            $response = $this->call('POST', $endpoint, $attempt);
            
            // Should not allow privilege escalation
            $this->assertNotEquals(200, $response->getStatusCode(), 'Privilege escalation should not be possible');
        }
    }

    /**
     * Assert security configuration
     */
    protected function assertSecurityConfiguration(): void
    {
        foreach ($this->securityConfig as $setting => $required) {
            if ($required) {
                $this->assertTrue(
                    Config::get("security.{$setting}", false),
                    "Security setting {$setting} should be enabled"
                );
            }
        }
    }

    /**
     * Set security configuration
     */
    protected function setSecurityConfig(string $key, bool $value): void
    {
        $this->securityConfig[$key] = $value;
    }

    /**
     * Get security configuration
     */
    protected function getSecurityConfig(string $key = null)
    {
        if ($key === null) {
            return $this->securityConfig;
        }
        
        return $this->securityConfig[$key] ?? null;
    }
} 