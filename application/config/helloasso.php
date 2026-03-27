<?php
/**
 * HelloAsso Payment Configuration
 * 
 * This file stores credentials and configuration for HelloAsso payment API integration.
 * 
 * HelloAsso is a French payment platform for associations (loi 1901).
 * This configuration supports both sandbox (testing) and production environments.
 * 
 * SECURITY: Never commit actual credentials to version control.
 * Use environment variables or secrets management for production.
 */

// ============================================================================
// ENVIRONMENT SELECTION
// ============================================================================
// Set to 'sandbox' for testing, 'production' for live transactions
$config['helloasso_environment'] = getenv('HELLOASSO_ENV') ?: 'sandbox';

// ============================================================================
// API ENDPOINTS
// ============================================================================
$config['helloasso_api_urls'] = array(
    'sandbox'    => 'https://api.helloasso-sandbox.com/v5/',
    'production' => 'https://api.helloasso.com/v5/'
);

// ============================================================================
// AUTHENTICATION
// ============================================================================
/**
 * Authentication Method: 
 * - 'oauth2': Use OAuth 2.0 Client Credentials flow
 * - 'api_key': Use Bearer token API key
 * 
 * Confirm with HelloAsso which method your account uses.
 */
$config['helloasso_auth_method'] = 'oauth2'; // or 'api_key'

/**
 * OAuth 2.0 Credentials
 * 
 * Required if auth_method = 'oauth2'
 * Obtain from https://dev.helloasso.com/
 */
$config['helloasso_oauth'] = array(
    'client_id'      => 'fc392b0be8154f2981c4216027046f50',
    'client_secret'  => 'laBpM+YxXp8bN+gK/v5bARFdFHtLs6DL',
    'token_url'      => 'https://api.helloasso-sandbox.com/oauth2/token',
    'token_scope'    => 'API', // Scope for API access
);

/**
 * API Key Authentication
 * 
 * Required if auth_method = 'api_key'
 * Format: "Bearer YOUR_API_KEY"
 */
$config['helloasso_api_key'] = getenv('HELLOASSO_API_KEY') ?: '';

// ============================================================================
// ASSOCIATION/MERCHANT ACCOUNT
// ============================================================================
/**
 * HelloAsso Association Account ID
 * 
 * This is your association's identifier in HelloAsso.
 * Find this in your HelloAsso account settings.
 */
$config['helloasso_merchant_id'] = getenv('HELLOASSO_MERCHANT_ID') ?: '';

/**
 * Association Account Slug
 * 
 * Human-readable identifier (e.g., "club-vol-avion-montlucon")
 * Used in payment URLs visible to customers
 */
$config['helloasso_account_slug'] = getenv('HELLOASSO_ACCOUNT_SLUG') ?: 'aeroclub-d-abbeville';

// Base URL used for payment callbacks.
// Priority: HELLOASSO_APP_URL > APP_URL > default.
// HelloAsso checkout URLs must be valid absolute URLs and are expected in HTTPS.
$app_url = getenv('HELLOASSO_APP_URL') ?: (getenv('APP_URL') ?: 'https://gvv.net');
$app_url = rtrim($app_url, '/');

// ============================================================================
// PAYMENT CONFIGURATION
// ============================================================================
/**
 * Payment Success Return URL
 * 
 * Customer is redirected here after successful payment
 * Use full URL with protocol (http:// or https://)
 */
$config['helloasso_return_url_success'] = $app_url . '/payments/helloasso_callback?status=success';

/**
 * Payment Failure Return URL
 * 
 * Customer redirected here if payment fails or is cancelled
 */
$config['helloasso_return_url_failure'] = $app_url . '/payments/helloasso_callback?status=failure';

/**
 * Payment Back URL
 *
 * Customer redirected here when going back/cancelling checkout.
 */
$config['helloasso_back_url'] = $app_url . '/payments/helloasso_callback?status=cancel';

/**
 * Payment Error URL
 *
 * Customer redirected here when checkout returns an error.
 */
$config['helloasso_error_url'] = $app_url . '/payments/helloasso_callback?status=error';

/**
 * Payment Webhook URL (For Async Confirmation)
 * 
 * HelloAsso sends payment confirmation here.
 * Not required for spike phase, but needed for production.
 * 
 * Usage: Enable in Phase 2+ when webhook listener is implemented
 */
$config['helloasso_webhook_url'] = $app_url . '/payments/helloasso_webhook';

/**
 * Webhook Secret Key
 * 
 * Provided by HelloAsso for verifying webhook signatures
 * Used to validate that webhooks come from HelloAsso
 */
$config['helloasso_webhook_secret'] = getenv('HELLOASSO_WEBHOOK_SECRET') ?: '';

// ============================================================================
// PAYMENT CONSTRAINTS
// ============================================================================
/**
 * Minimum Transaction Amount (in euros)
 * 
 * HelloAsso typically requires minimum €0.50
 * Adjust based on your requirements
 */
$config['helloasso_min_amount'] = 0.50;

/**
 * Maximum Transaction Amount (in euros)
 * 
 * Prevent accidental large transactions
 */
$config['helloasso_max_amount'] = 1000;

/**
 * Currency (ISO 4217)
 * 
 * EUR for European transactions (French associations)
 */
$config['helloasso_currency'] = 'EUR';

// ============================================================================
// API CLIENT CONFIGURATION
// ============================================================================
/**
 * HTTP Client Timeout (in seconds)
 * 
 * How long to wait for HelloAsso API responses
 */
$config['helloasso_timeout'] = 30;

/**
 * Enable SSL Verification
 * 
 * SECURITY: Always use TRUE in production
 * Set FALSE only for local development if needed (NOT RECOMMENDED)
 */
$config['helloasso_verify_ssl'] = TRUE;

/**
 * Debug Mode
 * 
 * Enable detailed logging of API requests/responses
 * Use only in development; disable in production
 */
$config['helloasso_debug'] = TRUE; // Set to TRUE to log API calls

// ============================================================================
// LOGGING
// ============================================================================
/**
 * Log Directory for HelloAsso API Calls
 * 
 * Relative to application root
 * Must be writable by web server
 */
$config['helloasso_log_file'] = 'application/logs/helloasso_payments.log';

/**
 * Log Level
 * 
 * 'debug' = All requests/responses
 * 'info' = High-level operations
 * 'error' = Errors only
 */
$config['helloasso_log_level'] = 'debug';

// ============================================================================
// FEATURE FLAGS
// ============================================================================
/**
 * Enable HelloAsso Payment Feature
 * 
 * Quick toggle to enable/disable the feature globally
 */
$config['helloasso_enabled'] = getenv('HELLOASSO_ENABLED') ?: FALSE;

/**
 * Sandbox Mode (Testing)
 * 
 * When TRUE: Use sandbox environment, no real charges
 * When FALSE: Use production environment with real charges
 * 
 * Recommend: Always TRUE until spike is verified successful
 */
$config['helloasso_sandbox_mode'] = TRUE;

// ============================================================================
// ENVIRONMENT SETUP HELPER
// ============================================================================
/**
 * Get the appropriate API URL based on environment setting
 */
function helloasso_get_api_url() {
    $CI = &get_instance();
    $env = $CI->config->item('helloasso_environment');
    $urls = $CI->config->item('helloasso_api_urls');
    return isset($urls[$env]) ? $urls[$env] : $urls['sandbox'];
}

/**
 * Check if configuration is complete and safe to use
 */
function helloasso_config_valid() {
    $CI = &get_instance();
    
    $required_fields = array(
        'helloasso_auth_method',
        'helloasso_merchant_id',
        'helloasso_return_url_success',
    );
    
    // Check OAuth credentials if using OAuth
    if ($CI->config->item('helloasso_auth_method') === 'oauth2') {
        $oauth = $CI->config->item('helloasso_oauth');
        if (empty($oauth['client_id']) || empty($oauth['client_secret'])) {
            return FALSE;
        }
    }
    
    // Check API key if using API key auth
    if ($CI->config->item('helloasso_auth_method') === 'api_key') {
        if (empty($CI->config->item('helloasso_api_key'))) {
            return FALSE;
        }
    }
    
    // Check other required fields
    foreach ($required_fields as $field) {
        if (empty($CI->config->item($field))) {
            return FALSE;
        }
    }
    
    return TRUE;
}

/* End of file helloasso.php */
/* Location: ./application/config/helloasso.php */
