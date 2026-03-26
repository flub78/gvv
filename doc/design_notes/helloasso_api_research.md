# HelloAsso API Research - Phase 1

## Overview
HelloAsso is a French payment platform specialized for associations (loi 1901). This document captures API research for integrating HelloAsso payment initiation into GVV.

## API Endpoints & Authentication

### Key Resources
- **Base URL**: `https://api.helloasso.com/v5/` (production) or sandbox equivalent
- **Authentication**: OAuth 2.0 or API Key (to be confirmed)
- **Sandbox URL**: `https://sandbox.helloasso.com/api/...` (likely pattern)

### Required Information from HelloAsso
- [ ] Confirm API base URL for sandbox environment
- [ ] Confirm API base URL for production environment
- [ ] Verify authentication method (OAuth 2.0 vs API Key)
- [ ] Get client credentials (Client ID, Client Secret, API Key)
- [ ] Association/Merchant account ID in HelloAsso

## Payment Initiation Flow

### Typical Payment API Pattern
```
1. Merchant collects customer payment data (amount, payer name, reference)
2. Merchant calls payment initiation endpoint with details
3. API returns payment session/redirect URL
4. Merchant redirects customer to HelloAsso payment page
5. Customer completes payment in HelloAsso interface
6. HelloAsso redirects customer back to merchant return URL (success/failure)
7. HelloAsso sends webhook notification for async confirmation
```

### Required Parameters (Expected)
Based on standard payment APIs, likely parameters:

| Parameter | Type | Required | Notes |
|-----------|------|----------|-------|
| amount | number | Yes | Amount in cents (e.g., 100 = €1.00) |
| payer_name | string | Yes | Full name of payer |
| reference | string | Yes | Merchant reference/order ID |
| currency | string | Yes | ISO 4217 code (likely EUR only) |
| return_url | string | Yes | URL to redirect after payment |
| webhook_url | string | No | URL for async payment confirmation |
| description | string | No | Payment description |
| merchant_id | string | Conditional | Association/account identifier |

### Optional Parameters (Expected)
- `payer_email` — Payer email for receipt
- `payer_phone` — Payer phone number
- `metadata` — Additional custom fields
- `campaign_id` — Link to HelloAsso campaign/fundraiser
- `item_name`, `item_description` — What is being paid for

## Response Format (Expected)

### Success Response
```json
{
  "redirect_url": "https://checkout.helloasso.com/...",
  "session_id": "sess_xyz123",
  "reference": "REF-001",
  "status": "pending"
}
```

### Error Response
```json
{
  "error": "invalid_amount",
  "message": "Amount must be greater than 0.50 EUR",
  "code": 400
}
```

## Webhook Events (For Future Phases)

HelloAsso likely sends webhook notifications for:
- `payment.completed` — Payment successfully processed
- `payment.failed` — Payment declined
- `payment.cancelled` — Payment cancelled by user
- `payment.refunded` — Payment refunded

## Association/Merchant Setup in HelloAsso

### Required Configuration
1. Association must have HelloAsso account (standard for French loi 1901)
2. API credentials for sandbox testing (Client Secret, API Key)
3. Webhook endpoint configuration (for async payment confirmation)
4. Return URL configuration (success/error landing pages)

## Rate Limits & Constraints

To be confirmed:
- API rate limits (typical: 1000 requests/hour or similar)
- Minimum transaction amount (typical: €0.50)
- Maximum transaction amount (typical: €500,000)
- Payment retry logic (typical: 3 retries)
- Timeout values for API calls (typical: 10-30 seconds)

## Fee Structure

**French Association Reduced Rates** (as mentioned):
- **Stripe**: 1.5% + €0.25 per transaction
- **HelloAsso**: Likely similar or better for associations

To confirm HelloAsso rates for loi 1901 associations.

## Next Steps

1. **Obtain HelloAsso API Credentials**
   - Create sandbox account at https://dev.helloasso.com
   - Generate API credentials for testing
   - Document Client ID, Client Secret, API Key

2. **Confirm API Endpoints**
   - GET `https://api.helloasso.com/v5/...` (list endpoints)
   - POST `https://api.helloasso.com/v5/payments/initiate` (likely endpoint)
   - GET webhook events format

3. **Test Connectivity**
   - Simple curl test to confirm API accessibility
   - Verify authentication method works

4. **Document in Config**
   - Create `application/config/helloasso.php`
   - Store credentials securely
   - Document required fields

## Resources

- HelloAsso Developer Portal: https://dev.helloasso.com/documentation
- HelloAsso API Reference: https://api.helloasso.com/documentation
- HelloAsso Test/Sandbox: https://sandbox.helloasso.com or similar
- OAuth 2.0 Documentation: https://tools.ietf.org/html/rfc6749
