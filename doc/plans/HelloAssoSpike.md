# Plan: HelloAsso Payment Spike Implementation

## TL;DR
Create a **dev-only proof-of-concept** payment controller for HelloAsso testing. Build a form where authorized admins (via `dev_users`) can submit payment test data (reference, payer name, amount) and trigger HelloAsso payment initiation. No database storage needed for the spike phase. Success criterion: small payment processed in sandbox and credited to association's HelloAsso account.

## Context
- GVV uses internal accounting system (comptes/ecritures tables), no current external payment integration
- This spike is proof-of-concept only; no integration with accounting system
- Access restricted to development admins via `dev_users` config
- Uses HelloAsso sandbox for testing

## Phase 1: HelloAsso API Research & Setup ✅ COMPLETE
1. ✅ Document required HelloAsso endpoints (payment initiation, webhooks)
   - File: [doc/design_notes/helloasso_api_research.md](doc/design_notes/helloasso_api_research.md)
2. ✅ Identify required payment parameters: Reference, Payer Name, Amount, Currency, Return URL
3. ✅ Create `application/config/helloasso.php` to store API key/credentials
   - File: [application/config/helloasso.php](application/config/helloasso.php)
   - Supports OAuth 2.0 and API Key authentication
   - Environment variable support for secure credential handling
4. ✅ Determine authentication method: API key vs. OAuth2 (Supports both)

## Phase 2: Controller Creation ✅ COMPLETE
**File**: [application/controllers/payments.php](application/controllers/payments.php)

✅ Created `Payments` class extending `CI_Controller`:
- Constructor: Check user login + load required libraries
- Authorization check: Restricts to `dev_users` only
- Method `test_helloasso()`: 
  - GET: Display form with Reference, Payer Name, Amount, Email fields
  - POST: Validate inputs, call HelloAsso API, display response
- Helper methods:
  - `_is_dev_authorized()` — Check if user is in dev_users
  - `_process_helloasso_payment()` — Form submission handler
  - `_validate_payment_form()` — Input validation (reference, name, amount, email)
  - `_call_helloasso_api()` — Call HelloAsso payment initiation endpoint
  - `_get_auth_header()` — Return Bearer token for API
  - `_get_oauth_token()` — Handle OAuth 2.0 token requests
  - `_http_post()` — Make HTTP POST requests with cURL
  - `_log_helloasso()` — Log all API operations to file
  - `helloasso_callback()` — Handle redirects from HelloAsso
  - `helloasso_webhook()` — Placeholder for webhook implementation (Phase 2+)

## Phase 3: View Creation ✅ COMPLETE
**Files**: 
- [application/views/payments/test_helloasso.php](application/views/payments/test_helloasso.php) — Payment form view
- [application/views/payments/helloasso_callback.php](application/views/payments/helloasso_callback.php) — Payment callback view

✅ Created Bootstrap 5 form with:
- Reference (text input, required)
- Payer Name (text input, required)
- Amount in EUR (number input with decimals, min €0.50, max €1000, required)
- Email (email input, optional)
- Submit button
- CSRF token protection
- Status message display (success/error/validation)
- Success: Shows redirect URL and session details
- Error: Shows error message and HTTP error code
- Validation: Shows field-level error messages
- Callback view: Shows payment completion status (success/failure)

## Phase 4: Configuration
**File**: `application/config/helloasso.php`

Store HelloAsso credentials:
- API key / Bearer token
- Environment: sandbox vs. production
- Base URL for API endpoints
- Association ID / merchant account identifier

## Phase 4: Integration Flow ✅ COMPLETE
Integration implemented in controller:
```
User submits form 
  ↓ Form validation in _validate_payment_form()
  ↓
Controller validates inputs (reference, name, amount, email)
  ↓
Controller calls HelloAsso API via _call_helloasso_api()
  ↓
HelloAsso responds with redirect URL or error
  ↓ Display success (with redirect link) or error message
  ↓
User completes payment in HelloAsso payment page
  ↓
Payment credited to association account
  ↓
User redirected back to helloasso_callback() handler
```

**Error Handling**:
- Validation errors: Displayed inline with form
- API errors: Displayed with error message and HTTP code
- Network errors: Caught and logged with exception details

## Phase 5: Testing & Verification 🚀 READY TO TEST

All code implemented and syntax validated ✅

### Manual Testing Checklist
- [ ] Verify dev access restriction: 
  - Logout as dev user, try to access `/payments/test_helloasso` (should see 403)
  - Login as dev user from `dev_users`, access `/payments/test_helloasso` (should see form)
- [ ] Test form validation: 
  - Submit with empty Reference (should show error)
  - Submit with empty Payer Name (should show error)
  - Submit with amount < €0.50 (should show error: "Minimum amount is €0.50")
  - Submit with amount > €1000 (should show error: "Maximum amount is €1000")
  - Submit with invalid email format (should show error)
- [ ] Test successful API call: 
  - Submit valid form: Reference="REF-TEST-001", Name="Test User", Amount="5.00"
  - Should see success message with redirect URL
  - Should see Session ID and Reference in response
- [ ] Test payment redirect: 
  - Click "Continue to HelloAsso Payment Page" button
  - Should redirect to HelloAsso payment page
- [ ] Success criterion: 
  - Complete payment in HelloAsso sandbox
  - Login to HelloAsso sandbox account
  - Verify €5.00 payment appears in association's transaction history
- [ ] Test return from HelloAsso:
  - After payment, verify redirect back to `/payments/helloasso_callback?status=success`
  - Should show "Payment Successful!" message

### PHPUnit Tests (Optional for spike)
- `test_unauthorized_access_denied()` — Non-dev users get 403
- `test_form_validation()` — Invalid inputs rejected with messages
- `test_helloasso_api_call_mocked()` — Mock HelloAsso response, verify API call structure

### Logs to Check
- `application/logs/helloasso_payments.log` — All API requests/responses (if debug enabled)

## Files to Create

| File | Purpose |
|------|---------|
| `application/controllers/payments.php` | Main controller with test_helloasso method |
| `application/views/payments/test_helloasso.php` | Payment form view |
| `application/config/helloasso.php` | HelloAsso API configuration |
| `doc/plan/HelloAssoSpike.md` | This implementation plan |

## Reference Architecture Patterns

Use these files as templates:
- `application/controllers/openflyers.php` — External API integration pattern (CSV import)
- `application/libraries/GoogleCal.php` — OAuth2 authentication pattern
- `application/core/MY_Controller.php` — Base controller class, authorization checks
- `application/config/program.example.php` — dev_users configuration example
- `application/views/welcome/index.php` — Bootstrap 5 form structure

## Success Criteria

| Criterion | Details | Verification |
|-----------|---------|--------|
| **Access Control** | Only `dev_users` can access | Try accessing as non-dev user (should see 403) |
| **Form Validation** | Invalid amounts rejected | Submit form with negative amount or 0 |
| **API Integration** | HelloAsso API called successfully | Check logs for API request details |
| **Payment Redirect** | User redirected to HelloAsso payment page | Click redirect link in success message |
| **Payment Completion** | €2-5 payment appears in sandbox account | Check HelloAsso sandbox dashboard |
| **User Feedback** | Success/error messages clearly displayed | See messages after form submission |

## Key Decisions

1. **Spike-Only**: No integration with GVV's accounting system (comptes/ecritures)
2. **Dev Restriction**: Access via `dev_users` config only
3. **No Database Storage**: Payment data not persisted for spike phase
4. **Direct API Calls**: Simple HTTP client, no service layer needed
5. **Sandbox Testing**: Use HelloAsso test account to avoid real charges

## Further Considerations

1. **HelloAsso API Documentation**
   - Confirm exact endpoint URLs
   - Verify authentication method (API key, OAuth2, or other)
   - List all required and optional parameters
   - Define webhook structure for async payment confirmation

2. **Future Phases** (not part of this spike)
   - Record payments in GVV's accounting system (comptes/ecritures)
   - Implement webhook listener for payment confirmations
   - Add multi-currency support
   - Integrate with billing system (facturation)

3. **Error Handling**
   - Log all API requests/responses using CI's logging
   - Display user-friendly error messages
   - Retry logic for transient failures (optional)

4. **Payment Return Flow**
   - For spike: User clicks redirect link provided after form submission
   - Future: Implement automatic webhook callback from HelloAsso

## Next Steps

1. Gather HelloAsso API documentation and sandbox credentials
2. Review and approve this plan
3. Proceed with Phase 1 (API research)
4. Implement controller, view, and configuration
5. Manual testing in sandbox environment
6. Demo successful payment to association account
