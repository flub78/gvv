<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Brevo_sms_adapter — Envoi SMS via l'API Brevo (ex-Sendinblue)
 *
 * Configuration requise dans application/config/program.php :
 *   $config['brevo_sms_api_key'] = 'xkeysib-...';
 *   $config['brevo_sms_sender']  = 'GVV';   // max 11 chars alphanumériques
 *
 * L'adaptateur ne lève jamais d'exception : il retourne false + message d'erreur
 * en cas de problème, pour ne pas interrompre le flux de rappel.
 */
class Brevo_sms_adapter
{
    const API_URL = 'https://api.brevo.com/v3/transactionalSMS/sms';

    private $CI;
    private $api_key;
    private $sender;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->config('program', true);
        $this->api_key = $this->CI->config->item('brevo_sms_api_key') ?: '';
        $this->sender  = $this->CI->config->item('brevo_sms_sender')  ?: 'GVV';
    }

    /**
     * Send an SMS via Brevo Transactional SMS API.
     *
     * @param string $phone   E.164 format preferred (+33612345678), or local French format
     * @param string $message Plain text, recommended ≤160 chars
     * @return array ['ok' => bool, 'error' => string|null]
     */
    public function send($phone, $message)
    {
        if (empty($this->api_key)) {
            return array('ok' => false, 'error' => 'brevo_sms_api_key not configured');
        }

        $phone = $this->_normalize_phone($phone);
        if (!$phone) {
            return array('ok' => false, 'error' => 'Invalid phone number: ' . $phone);
        }

        $payload = json_encode(array(
            'sender'    => $this->sender,
            'recipient' => $phone,
            'content'   => $message,
            'type'      => 'transactional',
        ));

        $result = $this->_post($payload);

        if ($result['http_code'] >= 200 && $result['http_code'] < 300) {
            gvv_info("Brevo SMS sent to $phone (HTTP {$result['http_code']})");
            return array('ok' => true, 'error' => null);
        }

        $error = "Brevo SMS failed: HTTP {$result['http_code']} — {$result['body']}";
        gvv_error($error);
        return array('ok' => false, 'error' => $error);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Normalize a French phone number to E.164 (+33XXXXXXXXX).
     * Returns null if the number cannot be normalized.
     */
    private function _normalize_phone($phone)
    {
        $phone = preg_replace('/[\s\.\-]/', '', $phone);

        if (preg_match('/^\+\d{7,15}$/', $phone)) {
            return $phone; // already E.164
        }
        if (preg_match('/^0([67]\d{8})$/', $phone, $m)) {
            return '+33' . $m[1]; // French mobile
        }
        if (preg_match('/^00(\d{7,13})$/', $phone, $m)) {
            return '+' . $m[1];
        }

        return null;
    }

    /**
     * HTTP POST to Brevo API.
     */
    protected function _post($payload)
    {
        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => array(
                'accept: application/json',
                'content-type: application/json',
                'api-key: ' . $this->api_key,
            ),
            CURLOPT_TIMEOUT        => 10,
        ));
        $body      = curl_exec($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error     = curl_error($ch);
        curl_close($ch);

        if ($error) {
            gvv_error("Brevo SMS curl error: $error");
            return array('http_code' => 0, 'body' => $error);
        }
        return array('http_code' => $http_code, 'body' => $body ?: '');
    }
}
