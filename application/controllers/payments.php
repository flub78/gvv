<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * GVV Gestion vol à voile - Payments Controller
 * 
 * Handles online payment integration with HelloAsso
 * 
 * @package controllers
 * @author Development Team
 * @license GNU General Public License v3
 */
class Payments extends CI_Controller {

    /**
     * Constructor
     * 
        * Loads required libraries and helpers
     */
    public function __construct() {
        parent::__construct();

        // Load required libraries and models
        $this->load->library('log');
        $this->load->config('helloasso');
        $this->load->helper('form');
        $this->load->helper('url');
    }

    /**
     * Check if current user is authorized to access payment features (dev admin)
     * 
     * @return boolean TRUE if user is in dev_menu_users, FALSE otherwise
     */
    private function _is_dev_authorized() {
        // Try to get username from session (supports both legacy DX_Auth and modern auth)
        $username = $this->session->userdata('username') ?: $this->session->userdata('DX_username');
        $dev_users_config = $this->config->item('dev_menu_users');
        
        if (empty($dev_users_config) || empty($username)) {
            return FALSE;
        }
        
        $dev_users = array_map('trim', explode(',', $dev_users_config));
        return in_array($username, $dev_users);
    }

    /**
     * Show 403 Forbidden page
     */
    private function _show_forbidden() {
        $this->output->set_status_header(403);
        show_error('Access Denied', 403, 'You do not have permission to access this feature.');
    }

    /**
     * Test HelloAsso Payment Integration
     * 
     * GET: Display payment form
     * POST: Process payment submission and call HelloAsso API
     * 
     * Restricted to dev_menu_users (development admins only)
     */
    public function test_helloasso() {
        // Payment test page is restricted to authenticated dev users.
        $this->dx_auth->check_login();

        // Check authorization
        if (!$this->_is_dev_authorized()) {
            $this->_show_forbidden();
            return;
        }

        $data = array();
        // Get username from session (supports both legacy DX_Auth and modern auth)
        $data['username'] = $this->session->userdata('username') ?: $this->session->userdata('DX_username');
        $data['page_title'] = 'HelloAsso Payment Test';
        
        // Handle form submission (POST)
        if ($this->input->post()) {
            $this->_process_helloasso_payment($data);
            return;
        }

        // Display form (GET)
        $data['csrf_token_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        
        $this->load->view('payments/test_helloasso', $data);
    }

    /**
     * Process HelloAsso Payment Form Submission
     * 
     * @param array $data View data to pass to template
     */
    private function _process_helloasso_payment(&$data) {
        $data['csrf_token_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        
        // Validate form inputs
        $validation_result = $this->_validate_payment_form();
        if ($validation_result !== TRUE) {
            $data['errors'] = $validation_result;
            $data['form_data'] = $this->input->post();
            $this->load->view('payments/test_helloasso', $data);
            return;
        }

        // Extract and sanitize form data
        $reference = $this->input->post('reference');
        $payer_name = $this->input->post('payer_name');
        $amount = (float) $this->input->post('amount');
        $payer_email = $this->input->post('payer_email');

        // Call HelloAsso API
        $api_result = $this->_call_helloasso_api($reference, $payer_name, $amount, $payer_email);

        if ($api_result['success']) {
            $data['message_type'] = 'success';
            $data['message'] = 'Payment initiated successfully!';
            $data['redirect_url'] = $api_result['redirect_url'];
            $data['session_id'] = $api_result['session_id'];
            $data['reference_returned'] = $api_result['reference'];
        } else {
            $data['message_type'] = 'error';
            $data['message'] = 'Payment initiation failed: ' . $api_result['error_message'];
            $data['error_code'] = $api_result['error_code'];
            $data['form_data'] = $this->input->post();
        }

        $this->load->view('payments/test_helloasso', $data);
    }

    /**
     * Validate Payment Form Inputs
     * 
     * @return boolean TRUE if valid, array of errors otherwise
     */
    private function _validate_payment_form() {
        $errors = array();

        // Reference validation
        $reference = $this->input->post('reference');
        if (empty($reference)) {
            $errors[] = 'Reference is required';
        } elseif (strlen($reference) > 100) {
            $errors[] = 'Reference must be 100 characters or less';
        }

        // Payer name validation
        $payer_name = $this->input->post('payer_name');
        if (empty($payer_name)) {
            $errors[] = 'Payer name is required';
        } elseif (strlen($payer_name) > 255) {
            $errors[] = 'Payer name must be 255 characters or less';
        }

        // Amount validation
        $amount = $this->input->post('amount');
        if (empty($amount)) {
            $errors[] = 'Amount is required';
        } else {
            $amount_float = (float) $amount;
            $min_amount = $this->config->item('helloasso_min_amount');
            $max_amount = $this->config->item('helloasso_max_amount');

            if ($amount_float < $min_amount) {
                $errors[] = 'Minimum amount is €' . $min_amount;
            } elseif ($amount_float > $max_amount) {
                $errors[] = 'Maximum amount is €' . $max_amount;
            }
        }

        // Email validation (optional)
        $payer_email = $this->input->post('payer_email');
        if (!empty($payer_email) && !filter_var($payer_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address';
        }

        return count($errors) > 0 ? $errors : TRUE;
    }

    /**
     * Call HelloAsso Payment Initiation API
     * 
     * @param string $reference Merchant reference
     * @param string $payer_name Name of payer
     * @param float $amount Amount in EUR
     * @param string $payer_email Optional payer email
     * 
     * @return array Result array with keys: success (bool), redirect_url (string), error_message (string)
     */
    private function _call_helloasso_api($reference, $payer_name, $amount, $payer_email = '') {
        try {
            // Get configuration
            $auth_method = $this->config->item('helloasso_auth_method');
            $api_base_url = $this->_get_api_url();
            
            // Get authentication token
            $auth_header = $this->_get_auth_header();
            if (empty($auth_header)) {
                return $this->_api_error_response('Authentication failed: Missing credentials');
            }

            // Prepare payment data
            $amount_cents = round($amount * 100);
            $payment_data = array(
                'initialAmount' => $amount_cents,
                'totalAmount' => $amount_cents,
                // Required by HelloAsso checkout-intents.
                'itemName' => 'Paiement GVV - ' . $reference,
                // Required by HelloAsso checkout-intents.
                'containsDonation' => FALSE,
                'payer' => array(
                    'firstName' => $payer_name,
                    'email' => !empty($payer_email) ? $payer_email : '',
                ),
                'metadata' => array(
                    'reference' => $reference,
                    'type' => 'spike_test',
                    'date' => date('Y-m-d H:i:s'),
                ),
                // HelloAsso checkout-intents expects returnUrl as a single string URL.
                'returnUrl' => $this->config->item('helloasso_return_url_success'),
                // Required by HelloAsso: URL used when user goes back/cancels checkout.
                'backUrl' => $this->config->item('helloasso_back_url'),
                // Required by HelloAsso: URL used when checkout returns an error.
                'errorUrl' => $this->config->item('helloasso_error_url'),
            );

            // Build correct HelloAsso checkout-intents endpoint
            $account_slug = $this->config->item('helloasso_account_slug');
            $endpoint = $api_base_url . 'organizations/' . $account_slug . '/checkout-intents';

            // Log API request if debug enabled
            if ($this->config->item('helloasso_debug')) {
                $this->_log_helloasso('API REQUEST', 'POST ' . $endpoint, $payment_data);
            }

            // Make HTTP request using cURL
            $response = $this->_http_post($endpoint, $payment_data, $auth_header);

            // Log API response if debug enabled
            if ($this->config->item('helloasso_debug')) {
                $this->_log_helloasso('API RESPONSE', 'Response Code: ' . $response['http_code'], $response['body']);
            }

            // Handle API response
            if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
                $response_data = json_decode($response['body'], TRUE);
                if (isset($response_data['redirectUrl'])) {
                    return array(
                        'success' => TRUE,
                        'redirect_url' => $response_data['redirectUrl'],
                        'session_id' => $response_data['id'],
                        'reference' => $reference,
                    );
                }
            }

            // API error
            $error_body = json_decode($response['body'], TRUE);
            $error_message = 'Unknown API error';

            if (is_array($error_body)) {
                if (isset($error_body['message']) && $error_body['message'] !== '') {
                    $error_message = $error_body['message'];
                } elseif (isset($error_body['title']) && $error_body['title'] !== '') {
                    $error_message = $error_body['title'];
                }

                // Format used by some HelloAsso validation responses: {"errors":[{"code":"...","message":"..."}]}
                if (isset($error_body['errors']) && is_array($error_body['errors']) && isset($error_body['errors'][0]) && is_array($error_body['errors'][0])) {
                    if (isset($error_body['errors'][0]['message']) && $error_body['errors'][0]['message'] !== '') {
                        $error_message = $error_body['errors'][0]['message'];
                    }
                }

                if (isset($error_body['errors']) && is_array($error_body['errors'])) {
                    foreach ($error_body['errors'] as $field => $field_errors) {
                        if (is_array($field_errors) && isset($field_errors[0])) {
                            $error_message .= ' [' . $field . ': ' . $field_errors[0] . ']';
                            break;
                        }
                    }
                }
            }

            return $this->_api_error_response($error_message, $response['http_code']);

        } catch (Exception $e) {
            $error_msg = 'Exception: ' . $e->getMessage();
            $this->_log_helloasso('API ERROR', 'Exception', $error_msg);
            return $this->_api_error_response($error_msg);
        }
    }

    /**
     * Get API Base URL (sandbox or production)
     * 
     * @return string API base URL
     */
    private function _get_api_url() {
        $environment = $this->config->item('helloasso_environment');
        $urls = $this->config->item('helloasso_api_urls');
        return isset($urls[$environment]) ? $urls[$environment] : $urls['sandbox'];
    }

    /**
     * Get Authentication Header
     * 
     * Returns Bearer token for API requests
     * 
     * @return string Authorization header value (Bearer token)
     */
    private function _get_auth_header() {
        $auth_method = $this->config->item('helloasso_auth_method');

        if ($auth_method === 'api_key') {
            $api_key = $this->config->item('helloasso_api_key');
            return 'Bearer ' . $api_key;
        } elseif ($auth_method === 'oauth2') {
            $token = $this->_get_oauth_token();
            if ($token === FALSE) {
                return ''; // Caller checks for empty and aborts
            }
            return 'Bearer ' . $token;
        }

        return '';
    }

    /**
     * Get OAuth2 Access Token
     * 
     * Requests a new token from HelloAsso OAuth2 endpoint
     * 
     * @return string|FALSE Access token or FALSE on failure
     */
    private function _get_oauth_token() {
        $oauth_config = $this->config->item('helloasso_oauth');

        $token_request = array(
            'grant_type'    => 'client_credentials',
            'client_id'     => $oauth_config['client_id'],
            'client_secret' => $oauth_config['client_secret'],
        );

        // Log token request if debug enabled (redact secret)
        if ($this->config->item('helloasso_debug')) {
            $log_data = $token_request;
            $log_data['client_secret'] = '***REDACTED***';
            $this->_log_helloasso('OAUTH REQUEST', $oauth_config['token_url'], $log_data);
        }

        // OAuth2 token endpoint REQUIRES application/x-www-form-urlencoded (not JSON)
        $response = $this->_http_post_form($oauth_config['token_url'], $token_request);

        if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
            $response_data = json_decode($response['body'], TRUE);
            if (isset($response_data['access_token'])) {
                return $response_data['access_token'];
            }
        }

        $this->_log_helloasso('OAUTH ERROR', 'Status: ' . $response['http_code'], $response['body']);
        return FALSE;
    }

    /**
     * Make HTTP POST with application/x-www-form-urlencoded (for OAuth token endpoint)
     */
    private function _http_post_form($url, $data) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config->item('helloasso_timeout'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->config->item('helloasso_verify_ssl'));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ));

        $response_body = curl_exec($ch);
        $http_code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return array('http_code' => $http_code, 'body' => $response_body);
    }

    /**
     * Make HTTP POST Request
     * 
     * Uses cURL to make POST request to external API
     * 
     * @param string $url Target URL
     * @param array $data Data to post (will be JSON encoded)
     * @param string $auth_header Authorization header (e.g., "Bearer token")
     * 
     * @return array Result array with keys: http_code, body
     */
    private function _http_post($url, $data, $auth_header) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config->item('helloasso_timeout'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->config->item('helloasso_verify_ssl'));

        // Set headers
        $headers = array(
            'Content-Type: application/json',
            'Accept: application/json',
        );

        if (!empty($auth_header)) {
            $headers[] = 'Authorization: ' . $auth_header;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Execute request
        $response_body = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return array(
            'http_code' => $http_code,
            'body' => $response_body,
        );
    }

    /**
     * Helper: Format API Error Response
     * 
     * @param string $error_message Error message to return
     * @param int $http_code HTTP status code (optional)
     * 
     * @return array Error response array
     */
    private function _api_error_response($error_message, $http_code = 0) {
        $this->_log_helloasso('API ERROR', 'Code: ' . $http_code, $error_message);
        return array(
            'success' => FALSE,
            'error_message' => $error_message,
            'error_code' => $http_code,
        );
    }

    /**
     * Log HelloAsso Operations
     * 
     * Logs all HelloAsso API operations to separate log file
     * 
     * @param string $level Log level (API REQUEST, API RESPONSE, API ERROR, etc.)
     * @param string $context Context/endpoint being called
     * @param mixed $data Data being logged (will be serialized if array)
     */
    private function _log_helloasso($level, $context, $data) {
        $log_file = $this->config->item('helloasso_log_file');
        $log_level = $this->config->item('helloasso_log_level');

        // Check if we should log this level
        $levels = array('debug' => 3, 'info' => 2, 'error' => 1);
        $current_level = isset($levels[$log_level]) ? $levels[$log_level] : $levels['info'];
        $msg_level = stripos($level, 'ERROR') !== FALSE ? $levels['error'] : $levels['info'];

        if ($msg_level > $current_level && $log_level !== 'debug') {
            return;
        }

        // Format log message
        $timestamp = date('Y-m-d H:i:s');
        $data_str = is_array($data) ? json_encode($data) : (string) $data;
        $log_msg = "[$timestamp] [$level] $context - $data_str\n";

        // Write to log file
        if (!is_dir(dirname($log_file))) {
            mkdir(dirname($log_file), 0755, TRUE);
        }

        file_put_contents($log_file, $log_msg, FILE_APPEND);
    }

    /**
     * Webhook Callback Handler (Placeholder for Phase 2+)
     * 
     * This will be implemented in a later phase to handle
     * async payment confirmation from HelloAsso
     */
    public function helloasso_webhook() {
        // Webhook endpoint is public but should only accept server-to-server POST calls.
        if (strtoupper($this->input->server('REQUEST_METHOD')) !== 'POST') {
            $this->output->set_status_header(405);
            echo 'Method Not Allowed';
            return;
        }

        // Placeholder for webhook implementation
        echo "Webhook endpoint ready (Phase 2+)";
    }

    /**
     * Payment Callback Handler (Placeholder for Phase 2+)
     * 
     * This receives redirects from HelloAsso after payment
     */
    public function helloasso_callback() {
        // Callback endpoint is public but should only be reached through browser redirects (GET).
        if (strtoupper($this->input->server('REQUEST_METHOD')) !== 'GET') {
            $this->output->set_status_header(405);
            echo 'Method Not Allowed';
            return;
        }

        $status = $this->input->get('status');
        $data = array();
        $data['status'] = $status;
        $data['message'] = $status === 'success' 
            ? 'Payment completed successfully!' 
            : 'Payment was cancelled or failed.';
        
        $this->load->view('payments/helloasso_callback', $data);
    }
}

/* End of file payments.php */
/* Location: ./application/controllers/payments.php */
