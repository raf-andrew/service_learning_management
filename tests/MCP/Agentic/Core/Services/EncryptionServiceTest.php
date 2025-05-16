<?php

namespace Tests\MCP\Agentic\Core\Services;

use PHPUnit\Framework\TestCase;
use MCP\Agentic\Core\Services\EncryptionService;
use MCP\Agentic\Core\Services\AccessControl;
use MCP\Agentic\Core\Services\Logging;
use MCP\Agentic\Core\Services\Monitoring;
use MCP\Agentic\Core\Services\Reporting;

class EncryptionServiceTest extends TestCase
{
    protected EncryptionService $encryptionService;
    protected AccessControl $accessControl;
    protected Logging $logging;
    protected Monitoring $monitoring;
    protected Reporting $reporting;

    protected function setUp(): void
    {
        $this->accessControl = $this->createMock(AccessControl::class);
        $this->logging = $this->createMock(Logging::class);
        $this->monitoring = $this->createMock(Monitoring::class);
        $this->reporting = $this->createMock(Reporting::class);

        $this->encryptionService = new EncryptionService(
            $this->accessControl,
            $this->logging,
            $this->monitoring,
            $this->reporting
        );
    }

    public function testGenerateKey(): void
    {
        $options = [
            'algorithm' => 'AES-256-GCM',
            'key_size' => 256,
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('encryption.key.generate')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Generating encryption key', ['options' => $options]],
                ['Encryption key generated', ['key_id' => 'test-key-123']]
            );

        $result = $this->encryptionService->generateKey($options);
        $this->assertIsArray($result);
    }

    public function testGenerateKeyWithAccessDenied(): void
    {
        $options = [
            'algorithm' => 'AES-256-GCM',
            'key_size' => 256,
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('encryption.key.generate')
            ->willReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Access denied: encryption.key.generate');

        $this->encryptionService->generateKey($options);
    }

    public function testEncrypt(): void
    {
        $data = 'sensitive data';
        $options = [
            'algorithm' => 'AES-256-GCM',
            'key_id' => 'test-key-123',
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('encryption.data.encrypt')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Encrypting data', ['options' => $options]],
                ['Data encrypted', ['key_id' => 'test-key-123']]
            );

        $result = $this->encryptionService->encrypt($data, $options);
        $this->assertIsString($result);
    }

    public function testDecrypt(): void
    {
        $data = 'encrypted data';
        $options = [
            'algorithm' => 'AES-256-GCM',
            'key_id' => 'test-key-123',
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('encryption.data.decrypt')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Decrypting data', ['options' => $options]],
                ['Data decrypted', ['key_id' => 'test-key-123']]
            );

        $result = $this->encryptionService->decrypt($data, $options);
        $this->assertIsString($result);
    }

    public function testRotateKey(): void
    {
        $keyId = 'test-key-123';
        $options = [
            'algorithm' => 'AES-256-GCM',
            'key_size' => 256,
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('encryption.key.rotate')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Rotating encryption key', ['key_id' => $keyId, 'options' => $options]],
                ['Key rotated', ['old_key_id' => $keyId, 'new_key_id' => 'test-key-456']]
            );

        $result = $this->encryptionService->rotateKey($keyId, $options);
        $this->assertIsArray($result);
    }

    public function testValidateSecurity(): void
    {
        $options = [
            'algorithm' => 'AES-256-GCM',
            'key_id' => 'test-key-123',
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('encryption.security.validate')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Validating encryption security', ['options' => $options]],
                ['Security validation complete', ['results' => [
                    'key_security' => [],
                    'encryption_strength' => [],
                    'compliance' => [],
                ]]]
            );

        $result = $this->encryptionService->validateSecurity($options);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('key_security', $result);
        $this->assertArrayHasKey('encryption_strength', $result);
        $this->assertArrayHasKey('compliance', $result);
    }

    public function testCheckCompliance(): void
    {
        $options = [
            'standards' => ['FIPS-140-2', 'NIST'],
            'regulations' => ['GDPR', 'HIPAA'],
            'policies' => ['internal', 'external'],
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('encryption.compliance.check')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Checking encryption compliance', ['options' => $options]],
                ['Compliance check complete', ['results' => [
                    'standards' => [],
                    'regulatory' => [],
                    'policy' => [],
                ]]]
            );

        $result = $this->encryptionService->checkCompliance($options);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('standards', $result);
        $this->assertArrayHasKey('regulatory', $result);
        $this->assertArrayHasKey('policy', $result);
    }

    public function testEncryptWithError(): void
    {
        $data = 'sensitive data';
        $options = [
            'algorithm' => 'AES-256-GCM',
            'key_id' => 'test-key-123',
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('encryption.data.encrypt')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Encrypting data', ['options' => $options]]
            );

        $this->logging->expects($this->once())
            ->method('error')
            ->with(
                'Encryption failed',
                [
                    'options' => $options,
                    'error' => 'Encryption failed',
                ]
            );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Encryption failed');

        $this->encryptionService->encrypt($data, $options);
    }

    public function testDecryptWithError(): void
    {
        $data = 'encrypted data';
        $options = [
            'algorithm' => 'AES-256-GCM',
            'key_id' => 'test-key-123',
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('encryption.data.decrypt')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Decrypting data', ['options' => $options]]
            );

        $this->logging->expects($this->once())
            ->method('error')
            ->with(
                'Decryption failed',
                [
                    'options' => $options,
                    'error' => 'Decryption failed',
                ]
            );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Decryption failed');

        $this->encryptionService->decrypt($data, $options);
    }

    public function testRotateKeyWithError(): void
    {
        $keyId = 'test-key-123';
        $options = [
            'algorithm' => 'AES-256-GCM',
            'key_size' => 256,
        ];

        $this->accessControl->expects($this->once())
            ->method('hasPermission')
            ->with('encryption.key.rotate')
            ->willReturn(true);

        $this->logging->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Rotating encryption key', ['key_id' => $keyId, 'options' => $options]]
            );

        $this->logging->expects($this->once())
            ->method('error')
            ->with(
                'Key rotation failed',
                [
                    'key_id' => $keyId,
                    'options' => $options,
                    'error' => 'Key rotation failed',
                ]
            );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Key rotation failed');

        $this->encryptionService->rotateKey($keyId, $options);
    }
} 