# Payment System Documentation

## Overview
The Payment System is a critical component of the LMS platform, handling course purchases and instructor payouts through multiple payment gateways. The system is designed to be extensible and configurable per payment gateway.

## Core Components

### 1. Payment Model (`Payment_model.php`)
Located in `application/models/Payment_model.php`, this model handles:
- Payment configuration
- Gateway integration
- Payment processing
- Transaction verification
- Instructor payouts

### 2. Supported Payment Gateways

#### Primary Gateways
1. PayPal
2. Stripe
3. Razorpay
4. Skrill
5. PayU
6. SSLCommerz
7. PagSeguro
8. Xendit
9. Doku
10. bKash
11. Cashfree
12. Maxicash
13. Aamarpay
14. Flutterwave
15. Tazapay

### 3. Payment Flows

#### Course Purchase Flow
```php
function configure_course_payment() {
    // 1. Calculate total amount
    // 2. Apply coupon if any
    // 3. Add tax
    // 4. Create payment session
    // 5. Configure success/cancel URLs
}
```

#### Instructor Payout Flow
```php
function configure_instructor_payment() {
    // 1. Verify payout request
    // 2. Calculate amount
    // 3. Configure payment details
    // 4. Set up success/cancel URLs
}
```

## Gateway Integration

### 1. Gateway Configuration
```php
$payment_gateway = [
    'identifier' => 'gateway_name',
    'keys' => [
        'sandbox_client_id' => '',
        'sandbox_secret_key' => '',
        'production_client_id' => '',
        'production_secret_key' => ''
    ],
    'enabled_test_mode' => true|false
];
```

### 2. Payment Verification
Each gateway implements its verification method:
```php
public function check_[gateway]_payment($identifier) {
    // 1. Get payment details
    // 2. Verify transaction
    // 3. Return status
}
```

## Data Structures

### 1. Payment Details
```php
$payment_details = [
    'total_payable_amount' => float,
    'items' => array,
    'is_instructor_payout_user_id' => int|false,
    'payment_title' => string,
    'success_url' => string,
    'cancel_url' => string,
    'back_url' => string
];
```

### 2. Item Structure
```php
$item_details = [
    'id' => int,
    'title' => string,
    'thumbnail' => string,
    'creator_id' => int,
    'discount_flag' => boolean,
    'discounted_price' => float,
    'price' => float,
    'actual_price' => float,
    'sub_items' => array
];
```

## Security Measures

### 1. Payment Verification
- Gateway-specific signature verification
- Transaction amount validation
- Order status confirmation
- Duplicate payment prevention

### 2. Error Handling
- Payment failure logging
- Transaction rollback
- User notification
- Error reporting

## Integration Points

### 1. Course System
- Course purchase processing
- Bundle purchase handling
- Coupon application
- Tax calculation

### 2. User System
- Instructor payout processing
- Payment history tracking
- Wallet integration
- Commission handling

## Migration Considerations

### 1. Payment Gateway Migration
- [ ] Create gateway service providers
- [ ] Implement Laravel's payment handling
- [ ] Add webhook controllers
- [ ] Create payment events

### 2. Database Changes
- [ ] Create payment migrations
- [ ] Add transaction tables
- [ ] Implement audit logging
- [ ] Add gateway configurations

### 3. Architecture Updates
- [ ] Implement repository pattern
- [ ] Add service layer
- [ ] Create payment facades
- [ ] Add event listeners

## Multi-tenant Considerations

### 1. Gateway Configuration
- Tenant-specific gateway settings
- Individual API credentials
- Separate webhook endpoints
- Isolated payment processing

### 2. Payment Routing
- Tenant identification
- Gateway selection
- Commission structure
- Payout handling

## Testing Strategy

### 1. Unit Tests
- Gateway integration tests
- Payment calculation tests
- Verification logic tests
- Error handling tests

### 2. Integration Tests
- Payment flow tests
- Webhook handling tests
- Refund processing tests
- Payout tests

### 3. Security Tests
- Signature verification tests
- Amount tampering tests
- Duplicate payment tests
- Authorization tests

## Monitoring and Logging

### 1. Transaction Logging
- Payment attempts
- Success/failure status
- Gateway responses
- Error details

### 2. Performance Monitoring
- Gateway response times
- Success rates
- Error rates
- Transaction volumes

## Documentation Requirements

### 1. Integration Guide
- Gateway setup instructions
- API credentials configuration
- Webhook setup guide
- Error handling documentation

### 2. User Guide
- Payment process documentation
- Troubleshooting guide
- Security best practices
- Support contact information

## Future Enhancements

### 1. Payment Features
- [ ] Implement subscription payments
- [ ] Add recurring billing
- [ ] Create payment plans
- [ ] Add partial payments

### 2. Gateway Additions
- [ ] Add more payment gateways
- [ ] Implement cryptocurrency payments
- [ ] Add mobile payment methods
- [ ] Integrate digital wallets

### 3. Security Enhancements
- [ ] Implement 3D Secure
- [ ] Add fraud detection
- [ ] Enhance encryption
- [ ] Improve authentication 