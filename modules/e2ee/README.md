# E2EE (End-to-End Encryption) System

A comprehensive, modular End-to-End Encryption system for Laravel applications that provides per-transaction E2EE control, user-specific keys, and complete auditability.

## 🚀 Features

### 🔐 Core Encryption
- **AES-256-GCM** encryption with authenticated encryption
- **Zero-knowledge architecture** - server cannot decrypt user data
- **Perfect forward secrecy** - compromise of current keys doesn't affect past data
- **Cryptographically secure** random number generation
- **PBKDF2** key derivation with configurable iterations

### 🔑 Key Management
- **User-specific encryption keys** - each user has unique keys
- **Automatic key generation** and rotation
- **Key fingerprinting** for integrity verification
- **Secure key storage** with encryption at rest
- **Key backup and recovery** procedures

### 💼 Transaction Management
- **Per-transaction E2EE control** - enable/disable on individual transactions
- **Transaction state tracking** with metadata
- **Automatic transaction lifecycle** management
- **Transaction statistics** and monitoring

### 🛡️ Security & Compliance
- **Comprehensive audit logging** of all operations
- **SOC2, GDPR, HIPAA, PCI DSS** compliance ready
- **Access control policies** and authorization
- **Security incident response** procedures
- **Threat modeling** and risk assessment

### 🔧 Integration
- **Laravel middleware** for automatic E2EE integration
- **Route-based E2EE** enablement
- **Request/response encryption** handling
- **Database integration** with encrypted fields
- **API endpoints** for management

## 📋 Requirements

- PHP 8.1+
- Laravel 10+
- OpenSSL extension
- SQLite/MySQL/PostgreSQL database

## 🛠️ Installation

### 1. Register Service Provider

Add the E2EE service provider to your `config/app.php`:

```php
'providers' => [
    // ...
    E2ee\Providers\E2eeServiceProvider::class,
],
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag=e2ee-config
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Configure Environment

Add to your `.env` file:

```env
E2EE_ENABLED=true
E2EE_ENCRYPTION_ALGORITHM=AES-256-GCM
E2EE_KEY_SIZE=32
E2EE_IV_SIZE=16
E2EE_AUTH_TAG_SIZE=16
E2EE_DERIVATION_ITERATIONS=100000
```

## 🚀 Quick Start

### Basic Usage

```php
use E2ee\Services\E2eeTransactionService;

// Enable E2EE for a transaction
$transactionService = app(E2eeTransactionService::class);
$transaction = $transactionService->enableTransaction('my_transaction', $userId);

// Encrypt data
$encrypted = $transactionService->encryptTransactionData('my_transaction', 'sensitive data');

// Decrypt data
$decrypted = $transactionService->decryptTransactionData(
    'my_transaction',
    $encrypted['encrypted_data'],
    $encrypted['iv'],
    $encrypted['auth_tag']
);
```

### Route Integration

```php
// Enable E2EE for specific routes
Route::get('/secure-data', function () {
    return response()->json(['data' => 'encrypted']);
})->middleware('e2ee');

// Or use the secure macro
Route::get('/secure-data', function () {
    return response()->json(['data' => 'encrypted']);
})->secure();
```

### Request/Response Handling

```php
// Enable E2EE for a request
$request->enableE2ee();
$request->setE2eeTransactionId('my_transaction');

// Add E2EE headers to response
return response()->json(['data' => 'encrypted'])->withE2ee(true);
```

## 📚 API Reference

### Services

#### E2eeEncryptionService

Core encryption operations:

```php
$encryptionService = app(E2eeEncryptionService::class);

// Generate secure key
$key = $encryptionService->generateKey();

// Encrypt data
$encrypted = $encryptionService->encrypt($data, $key);

// Decrypt data
$decrypted = $encryptionService->decrypt(
    $encrypted['encrypted_data'],
    $key,
    $encrypted['iv'],
    $encrypted['auth_tag']
);
```

#### E2eeKeyManagementService

User key management:

```php
$keyService = app(E2eeKeyManagementService::class);

// Generate user key
$userKey = $keyService->generateUserKey($userId);

// Rotate user key
$newKey = $keyService->rotateUserKey($userId);

// Get key statistics
$stats = $keyService->getKeyStats($userId);
```

#### E2eeTransactionService

Transaction management:

```php
$transactionService = app(E2eeTransactionService::class);

// Enable transaction
$transaction = $transactionService->enableTransaction($transactionId, $userId);

// Check if enabled
$isEnabled = $transactionService->isTransactionEnabled($transactionId);

// Disable transaction
$transactionService->disableTransaction($transactionId);
```

### Models

#### E2eeUserKey

```php
// Get user keys
$keys = E2eeUserKey::forUser($userId)->active()->get();

// Check if key is active
$isActive = $userKey->isActive();

// Deactivate key
$userKey->deactivate();
```

#### E2eeTransaction

```php
// Get active transactions
$transactions = E2eeTransaction::enabled()->active()->get();

// Get transaction duration
$duration = $transaction->duration_human;

// Enable/disable transaction
$transaction->enable();
$transaction->disable();
```

#### E2eeAuditLog

```php
// Get audit logs
$logs = E2eeAuditLog::forUser($userId)->successful()->get();

// Get operation logs
$encryptionLogs = E2eeAuditLog::withOperation('encrypt')->get();

// Get risk level
$riskLevel = $auditLog->risk_level;
```

## 🔧 Configuration

### Encryption Settings

```php
// config/e2ee.php
'encryption' => [
    'algorithm' => env('E2EE_ENCRYPTION_ALGORITHM', 'AES-256-GCM'),
    'key_size' => env('E2EE_KEY_SIZE', 32),
    'iv_size' => env('E2EE_IV_SIZE', 16),
    'auth_tag_size' => env('E2EE_AUTH_TAG_SIZE', 16),
    'patterns' => [
        '*_data',
        '*_content',
        '*_message',
        '*_text',
        '*_body',
        'description',
        'notes',
        'comments',
    ],
    'blacklist' => [
        'password',
        'token',
        'api_key',
        'secret',
        'hash',
        'signature',
    ],
],
```

### Key Management

```php
'keys' => [
    'derivation_iterations' => env('E2EE_DERIVATION_ITERATIONS', 100000),
    'rotation_interval' => env('E2EE_KEY_ROTATION_INTERVAL', 90), // days
    'backup_enabled' => env('E2EE_KEY_BACKUP_ENABLED', true),
    'storage_path' => env('E2EE_KEY_STORAGE_PATH', storage_path('e2ee/keys')),
],
```

### Transaction Settings

```php
'transactions' => [
    'cache_ttl' => env('E2EE_TRANSACTION_CACHE_TTL', 3600),
    'cleanup_interval' => env('E2EE_CLEANUP_INTERVAL', 30), // days
    'max_duration' => env('E2EE_MAX_TRANSACTION_DURATION', 86400), // seconds
],
```

## 🧪 Testing

Run the E2EE system tests:

```bash
php artisan test --filter=E2eeSystemTest
```

Or run specific test methods:

```bash
php artisan test --filter=testEncryptionService
php artisan test --filter=testKeyManagementService
php artisan test --filter=testTransactionService
```

## 🔍 Monitoring & Auditing

### Audit Logs

All E2EE operations are logged with detailed information:

- User ID and transaction ID
- Operation type and success status
- IP address and user agent
- Timestamp and metadata
- Risk level and category

### Dashboard

Access the E2EE dashboard at `/e2ee/dashboard` for:

- System statistics and metrics
- Key management overview
- Transaction monitoring
- Audit log analysis
- Security alerts

### Commands

```bash
# Generate user keys
php artisan e2ee:key:generate --user=1

# Rotate user keys
php artisan e2ee:key:rotate --user=1

# Enable transaction
php artisan e2ee:transaction:enable --transaction=my_transaction --user=1

# View audit logs
php artisan e2ee:audit --user=1 --operation=encrypt

# Cleanup expired data
php artisan e2ee:cleanup --days=30
```

## 🔒 Security Considerations

### Best Practices

1. **Key Management**
   - Regularly rotate encryption keys
   - Use secure key storage (HSM recommended)
   - Implement key backup and recovery procedures
   - Monitor key usage and access patterns

2. **Access Control**
   - Implement least privilege access
   - Use multi-factor authentication for key management
   - Monitor and log all access attempts
   - Implement session management

3. **Audit and Compliance**
   - Maintain comprehensive audit logs
   - Regular security assessments
   - Compliance monitoring and reporting
   - Incident response procedures

4. **Operational Security**
   - Regular security updates
   - Vulnerability scanning
   - Penetration testing
   - Security training for staff

### Threat Model

The E2EE system protects against:

- **Server compromise** - Zero-knowledge architecture prevents data access
- **Database breach** - Encrypted data not decryptable without keys
- **Man-in-the-middle attacks** - TLS and certificate pinning
- **Key compromise** - Key rotation and isolation
- **Insider threats** - Audit logging and access controls

## 📄 Compliance

The E2EE system is designed to support:

- **SOC2** - Security, availability, processing integrity, confidentiality, privacy
- **GDPR** - Data protection and privacy requirements
- **HIPAA** - Healthcare data protection
- **PCI DSS** - Payment card data security

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass
6. Submit a pull request

## 📄 License

This E2EE system is licensed under the MIT License.

## 🆘 Support

For support and questions:

- Check the documentation
- Review the test cases
- Open an issue on GitHub
- Contact the development team

## 🔄 Changelog

### Version 1.0.0
- Initial release
- Core encryption functionality
- Key management system
- Transaction management
- Audit logging
- Laravel integration
- Comprehensive testing
- Security documentation 