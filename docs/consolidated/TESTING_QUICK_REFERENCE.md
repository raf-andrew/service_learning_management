# Testing Quick Reference Guide

## Quick Commands

### Run All Tests
```bash
npx vitest run
```

### Run Tests with Coverage
```bash
npx vitest run --coverage
```

### Watch Mode (Development)
```bash
npx vitest
```

### Run Specific Test Categories

#### Functional Tests (API Endpoints)
```bash
npx vitest run tests/Functional/
```

#### Sanity Tests (System Health)
```bash
npx vitest run tests/Sanity/
```

#### Chaos Tests (Failure Scenarios)
```bash
npx vitest run tests/Chaos/
```

#### AI Tests (Machine Learning)
```bash
npx vitest run tests/AI/
```

#### MCP Tests (Model Context Protocol)
```bash
npx vitest run tests/MCP/
```

#### Security Tests (Vulnerability Testing)
```bash
npx vitest run tests/Security/
```

#### Frontend Tests (UI Components)
```bash
npx vitest run tests/Frontend/
```

#### Unit Tests
```bash
npx vitest run tests/Unit/
```

### Run Specific Test Files
```bash
# Run a specific test file
npx vitest run tests/Functional/Codespaces/ListCodespaces.test.ts

# Run multiple specific files
npx vitest run tests/Functional/Codespaces/ tests/Functional/GitHub/
```

### Run Tests by Tags
```bash
# Run tests with specific tags
npx vitest run --reporter=verbose -t "codespace"
npx vitest run --reporter=verbose -t "security"
npx vitest run --reporter=verbose -t "laravel"
```

## Test Statistics

- **Total Tests**: 841
- **Test Files**: 95
- **Categories**: 8 (Functional, Sanity, Chaos, AI, MCP, Security, Frontend, Unit)
- **Coverage**: 23.39%
- **Execution Time**: ~40 seconds

## Test Categories Overview

| Category | Tests | Purpose |
|----------|-------|---------|
| Functional | 165 | API endpoint testing with mocks |
| Sanity | 15 | Basic system health verification |
| Chaos | 21 | Failure scenario simulation |
| AI | 22 | Machine learning functionality |
| MCP | 20 | Model Context Protocol testing |
| Security | 25 | Vulnerability and security testing |
| Frontend | 600+ | UI components and services |
| Unit | 7 | Individual component testing |

## Key Features

### ✅ Modular Architecture
- Each test file focuses on specific functionality
- Easy to maintain and extend
- Clear separation of concerns

### ✅ Laravel-Centric
- Follows Laravel conventions
- API endpoint testing matches Laravel routing
- Validation rules match Laravel validation

### ✅ Mock-Based Testing
- Safe testing environment
- No real HTTP requests
- Predictable results

### ✅ Comprehensive Coverage
- All major functionality tested
- Edge cases covered
- Security vulnerabilities simulated

### ✅ Fast Execution
- ~40 seconds for 841 tests
- Efficient mock implementations
- Optimized test structure

## Common Test Patterns

### Functional Test Structure
```typescript
describe('API Endpoint', () => {
  it('should perform action successfully', async () => {
    const response = await makeRequest('GET', '/api/endpoint');
    expect(response.status).toBe(200);
    expect(response.data).toBeDefined();
  });
});
```

### Mock Response Pattern
```typescript
this.mockResponses.set('GET:/api/endpoint', {
  status: 200,
  data: { result: 'success' },
  headers: { 'x-security-header': 'value' }
});
```

### Security Header Validation
```typescript
expect(response.headers['x-content-type-options']).toBe('nosniff');
expect(response.headers['x-frame-options']).toBe('DENY');
expect(response.headers['x-xss-protection']).toBe('1; mode=block');
```

## Troubleshooting

### Tests Failing
1. Check if all dependencies are installed
2. Verify test environment setup
3. Check for import errors
4. Ensure mock responses are properly configured

### Slow Test Execution
1. Run specific test categories instead of all tests
2. Use watch mode for development
3. Check for unnecessary async operations

### Coverage Issues
1. Ensure all code paths are tested
2. Add tests for edge cases
3. Check for untested error scenarios

## Best Practices

### Writing New Tests
1. Follow existing naming conventions
2. Use descriptive test names
3. Include proper JSDoc documentation
4. Add appropriate tags for searchability
5. Use mocks for external dependencies

### Maintaining Tests
1. Keep tests focused and specific
2. Update tests when functionality changes
3. Remove obsolete tests
4. Maintain consistent patterns

### Test Organization
1. Group related tests together
2. Use clear describe blocks
3. Separate setup, execution, and assertions
4. Clean up after tests

## File Structure

```
tests/
├── Functional/           # API endpoint testing
│   ├── Auth/            # Authentication
│   ├── Codespaces/      # Codespace management
│   ├── GitHub/          # GitHub integration
│   ├── Health/          # Health monitoring
│   ├── Sniffing/        # Network sniffing
│   └── Tenants/         # Tenant management
├── Sanity/              # System health checks
├── Chaos/               # Failure scenarios
├── AI/                  # AI/ML functionality
├── MCP/                 # Model Context Protocol
├── Security/            # Security testing
├── Frontend/            # Frontend testing
│   └── __tests__/
│       ├── wireframe/   # UI components
│       ├── models/      # Data models
│       ├── services/    # Business logic
│       └── stores/      # State management
└── Unit/                # Unit tests
    └── Commands/        # Artisan commands
```

## Performance Tips

1. **Run Specific Tests**: Use targeted test runs during development
2. **Watch Mode**: Use `npx vitest` for development feedback
3. **Parallel Execution**: Tests run in parallel by default
4. **Mock Optimization**: Use efficient mock implementations
5. **Test Isolation**: Ensure tests don't depend on each other

## Security Testing

The security tests cover:
- Authentication vulnerabilities
- Authorization bypass attempts
- Input validation attacks
- Session management issues
- CSRF protection
- Rate limiting
- File upload security
- API key security
- Data encryption
- Secure headers
- Content security
- Audit logging

All security tests use safe mocks and don't perform actual attacks. 