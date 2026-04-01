<?php

/**
 * GVV Gestion vol à voile
 * Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @filesource Helloasso.php
 * @package libraries
 *
 * Bibliothèque HelloAsso — intégration OAuth2 + checkout-intents, multi-section.
 *
 * Chaque section dispose de ses propres crédentiels stockés dans la table
 * `paiements_en_ligne_config`. Les secrets ne sont jamais écrits dans les logs.
 *
 * Usage :
 *   $this->load->library('Helloasso');
 *   $token = $this->helloasso->get_oauth_token($club_id);
 *   $result = $this->helloasso->create_checkout($club_id, $params);
 *
 * PHPUnit tests :
 *   phpunit --configuration phpunit.xml application/tests/unit/libraries/HelloassoLibraryTest.php
 */

class Helloasso {

    /** @var object CodeIgniter instance */
    protected $_CI;

    /** @var array API base URLs by environment */
    protected static $_API_URLS = array(
        'sandbox'    => 'https://api.helloasso-sandbox.com/v5/',
        'production' => 'https://api.helloasso.com/v5/',
    );

    /** @var array OAuth2 token endpoints by environment */
    protected static $_TOKEN_URLS = array(
        'sandbox'    => 'https://api.helloasso-sandbox.com/oauth2/token',
        'production' => 'https://api.helloasso.com/oauth2/token',
    );

    /** @var array Webhook source IPs by environment */
    protected static $_WEBHOOK_SOURCE_IPS = array(
        'sandbox'    => array('4.233.135.234'),
        'production' => array('51.138.206.200'),
    );

    // -----------------------------------------------------------------------
    // Constructeur
    // -----------------------------------------------------------------------

    public function __construct()
    {
        $this->_CI =& get_instance();
    }

    // -----------------------------------------------------------------------
    // API publique
    // -----------------------------------------------------------------------

    /**
     * Vérifie que les crédentiels sandbox sont définis et non vides pour un club.
     *
     * @param  int  $club_id  Identifiant de la section
     * @return bool
     */
    public function sandbox_available($club_id)
    {
        $config = $this->_get_config($club_id);
        return !empty($config['client_id']) && !empty($config['client_secret']);
    }

    /**
     * Obtient un token OAuth2 client_credentials pour un club.
     *
     * @param  int         $club_id  Identifiant de la section
     * @return string|FALSE  Token d'accès, ou FALSE en cas d'échec
     */
    public function get_oauth_token($club_id)
    {
        $config = $this->_get_config($club_id);

        if (empty($config['client_id']) || empty($config['client_secret'])) {
            $this->log('ERROR', 'none', 'oauth', 'Missing credentials for club=' . (int) $club_id);
            return FALSE;
        }

        $env       = isset($config['environment']) ? $config['environment'] : 'sandbox';
        $token_url = isset(self::$_TOKEN_URLS[$env])
            ? self::$_TOKEN_URLS[$env]
            : self::$_TOKEN_URLS['sandbox'];

        // Ne jamais logger le secret en clair
        $this->log('INFO', 'none', 'oauth',
            'Requesting token club=' . (int) $club_id
            . ' env=' . $env
            . ' client_id=' . $config['client_id']
            . ' client_secret=***'
        );

        $response = $this->_http_post_form($token_url, array(
            'grant_type'    => 'client_credentials',
            'client_id'     => $config['client_id'],
            'client_secret' => $config['client_secret'],
        ));

        if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
            $data = json_decode($response['body'], TRUE);
            if (isset($data['access_token'])) {
                $this->log('INFO', 'none', 'oauth', 'STATUS=SUCCESS token obtained');
                return $data['access_token'];
            }
        }

        $this->log('ERROR', 'none', 'oauth',
            'STATUS=FAILED http_code=' . $response['http_code']
            . ' body=' . $response['body']
        );
        return FALSE;
    }

    /**
     * Obtient un token OAuth2 avec des crédentiels fournis directement (sans passer par la DB).
     * Utilisé par le test de connexion avant la première sauvegarde.
     *
     * @param  string      $client_id
     * @param  string      $client_secret
     * @param  string      $environment  'sandbox' ou 'production'
     * @return string|FALSE  Token d'accès, ou FALSE en cas d'échec
     */
    public function get_oauth_token_with_credentials($client_id, $client_secret, $environment = 'sandbox')
    {
        $token_url = isset(self::$_TOKEN_URLS[$environment])
            ? self::$_TOKEN_URLS[$environment]
            : self::$_TOKEN_URLS['sandbox'];

        $this->log('INFO', 'none', 'oauth',
            'Requesting token (form) env=' . $environment
            . ' client_id=' . $client_id
            . ' client_secret=***'
        );

        $response = $this->_http_post_form($token_url, array(
            'grant_type'    => 'client_credentials',
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
        ));

        if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
            $data = json_decode($response['body'], TRUE);
            if (isset($data['access_token'])) {
                $this->log('INFO', 'none', 'oauth', 'STATUS=SUCCESS token obtained (form)');
                return $data['access_token'];
            }
        }

        $this->log('ERROR', 'none', 'oauth',
            'STATUS=FAILED http_code=' . $response['http_code']
            . ' body=' . $response['body']
        );
        return FALSE;
    }

    /**
     * Crée un checkout-intent HelloAsso et retourne l'URL de redirection.
     *
     * Paramètres $params :
     *   - amount           (float)  Montant en euros
     *   - item_name        (string) Libellé affiché sur HelloAsso
     *   - payer_first_name (string) Optionnel
     *   - payer_email      (string) Optionnel
     *   - return_url       (string) URL de retour après paiement réussi
     *   - back_url         (string) URL si l'utilisateur annule
     *   - error_url        (string) URL en cas d'erreur
     *   - metadata         (array)  Doit contenir 'gvv_transaction_id' et 'type'
     *
     * Retourne :
     *   array['success']      bool
     *   array['redirect_url'] string   (si success)
     *   array['session_id']   string   (si success)
     *   array['error_message'] string  (si !success)
     *   array['error_code']   int      (si !success)
     *
     * @param  int   $club_id
     * @param  array $params
     * @return array
     */
    public function create_checkout($club_id, array $params)
    {
        $txid = isset($params['metadata']['gvv_transaction_id'])
            ? $params['metadata']['gvv_transaction_id']
            : 'unknown';

        $token = $this->get_oauth_token($club_id);
        if ($token === FALSE) {
            return $this->_error('OAuth authentication failed', 0, $txid, 'checkout');
        }

        $config = $this->_get_config($club_id);
        $env    = isset($config['environment']) ? $config['environment'] : 'sandbox';
        $slug   = isset($config['account_slug']) ? $config['account_slug'] : '';

        if (empty($slug)) {
            $this->log('ERROR', $txid, 'checkout',
                'STATUS=FAILED Missing account_slug for club=' . (int) $club_id);
            return $this->_error('HelloAsso account slug not configured', 0, $txid, 'checkout');
        }

        $api_url  = isset(self::$_API_URLS[$env]) ? self::$_API_URLS[$env] : self::$_API_URLS['sandbox'];
        $endpoint = $api_url . 'organizations/' . $slug . '/checkout-intents';

        $amount_cents = (int) round($params['amount'] * 100);

        $payload = array(
            'initialAmount'    => $amount_cents,
            'totalAmount'      => $amount_cents,
            'itemName'         => $params['item_name'],
            'containsDonation' => FALSE,
            'payer'            => array(
                'firstName' => isset($params['payer_first_name']) ? $params['payer_first_name'] : '',
                'lastName'  => isset($params['payer_last_name'])  ? $params['payer_last_name']  : '',
                'email'     => isset($params['payer_email'])      ? $params['payer_email']      : '',
            ),
            'metadata'         => isset($params['metadata']) ? $params['metadata'] : array(),
            'returnUrl'        => $params['return_url'],
            'backUrl'          => $params['back_url'],
            'errorUrl'         => $params['error_url'],
        );

        $this->log('INFO', $txid, 'checkout',
            'STATUS=PENDING amount=' . $params['amount'] . ' endpoint=' . $endpoint);

        $response = $this->_http_post_json($endpoint, $payload, $token);

        if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
            $data = json_decode($response['body'], TRUE);
            if (isset($data['redirectUrl'])) {
                $this->log('INFO', $txid, 'checkout',
                    'STATUS=SUCCESS session_id=' . $data['id']);
                return array(
                    'success'      => TRUE,
                    'redirect_url' => $data['redirectUrl'],
                    'session_id'   => $data['id'],
                );
            }
        }

        $error = $this->_parse_api_error($response['body']);
        $this->log('ERROR', $txid, 'checkout',
            'STATUS=FAILED http_code=' . $response['http_code'] . ' error=' . $error);
        return $this->_error($error, $response['http_code'], $txid, 'checkout');
    }

    /**
     * Vérifie la signature HMAC-SHA256 d'un webhook HelloAsso.
     *
     * @param  string $payload   Corps brut de la requête (php://input)
     * @param  string $signature Valeur du header X-Ha-Signature
     * @param  int    $club_id
     * @return bool
     */
    public function verify_webhook_signature($payload, $signature, $club_id)
    {
        $config = $this->_get_config($club_id);
        $secret = isset($config['webhook_secret']) ? $config['webhook_secret'] : '';

        if (empty($secret)) {
            return FALSE;
        }

        $expected = hash_hmac('sha256', $payload, $secret);
        // HelloAsso peut envoyer "sha256=<hash>" ou directement "<hash>"
        $received = preg_replace('/^sha256=/i', '', trim((string) $signature));

        return hash_equals($expected, $received);
    }

    /**
     * Vérifie que l'IP source de la requête webhook est autorisée.
     *
     * Pour les comptes non partenaires HelloAsso, l'authenticité peut être
     * validée par allowlist IP selon l'environnement sandbox/production.
     *
     * @param  int         $club_id
     * @param  string|null $ip      IP explicite (sinon auto-détection)
     * @return bool
     */
    public function is_webhook_ip_allowed($club_id, $ip = null)
    {
        $config = $this->_get_config($club_id);
        $env = isset($config['environment']) ? $config['environment'] : 'sandbox';
        $allowed_ips = isset(self::$_WEBHOOK_SOURCE_IPS[$env])
            ? self::$_WEBHOOK_SOURCE_IPS[$env]
            : self::$_WEBHOOK_SOURCE_IPS['sandbox'];

        $client_ip = $ip !== null ? trim((string) $ip) : $this->get_request_ip();
        if ($client_ip === '') {
            return FALSE;
        }

        return in_array($client_ip, $allowed_ips, true);
    }

    /**
     * Récupère l'IP cliente de la requête courante.
     *
     * Priorité : X-Forwarded-For (première IP) puis REMOTE_ADDR.
     *
     * @return string
     */
    public function get_request_ip()
    {
        $xff = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? trim((string) $_SERVER['HTTP_X_FORWARDED_FOR']) : '';
        if ($xff !== '') {
            $parts = explode(',', $xff);
            $candidate = trim($parts[0]);
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }

        $remote_addr = isset($_SERVER['REMOTE_ADDR']) ? trim((string) $_SERVER['REMOTE_ADDR']) : '';
        if (filter_var($remote_addr, FILTER_VALIDATE_IP)) {
            return $remote_addr;
        }

        return '';
    }

    /**
     * Log structuré au format GVV/HelloAsso.
     *
     * Format : [timestamp] [HELLOASSO] txid=<txid> STATUS=<LEVEL> type=<type> <message>
     * Fichier : helloasso_payments_YYYY-MM-DD.log (un fichier par jour)
     *
     * Les secrets ne doivent jamais figurer dans $message.
     *
     * @param string $level   INFO | ERROR | DEBUG
     * @param string $txid    Identifiant de transaction GVV
     * @param string $type    Contexte (oauth, checkout, webhook, …)
     * @param string $message Message libre (sans secrets)
     */
    public function log($level, $txid, $type, $message)
    {
        $log_file  = $this->_get_log_file();
        $timestamp = date('Y-m-d H:i:s');
        $line      = sprintf(
            "[%s] [HELLOASSO] txid=%s STATUS=%s type=%s %s\n",
            $timestamp,
            $txid,
            strtoupper($level),
            $type,
            $message
        );
        @file_put_contents($log_file, $line, FILE_APPEND | LOCK_EX);
    }

    // -----------------------------------------------------------------------
    // Méthodes protégées — overridables pour les tests
    // -----------------------------------------------------------------------

    /**
     * Charge la configuration HelloAsso d'une section depuis paiements_en_ligne_config.
     *
     * Clés retournées : client_id, client_secret, account_slug, environment, webhook_secret
     *
     * @param  int   $club_id
     * @return array Tableau associatif param_key => param_value
     */
    protected function _get_config($club_id)
    {
        $this->_CI->load->model('paiements_en_ligne_model');
        return $this->_CI->paiements_en_ligne_model->get_all_config('helloasso', (int) $club_id);
    }

    /**
     * HTTP POST application/x-www-form-urlencoded (endpoint OAuth2 token).
     *
     * @param  string $url
     * @param  array  $data
     * @return array  ['http_code' => int, 'body' => string]
     */
    protected function _http_post_form($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,            $url);
        curl_setopt($ch, CURLOPT_POST,           TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS,     http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT,        30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER,     array(
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ));
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return array('http_code' => $code, 'body' => $body);
    }

    /**
     * HTTP POST application/json (checkout-intents et autres endpoints API).
     *
     * @param  string $url
     * @param  array  $data
     * @param  string $token  Bearer token OAuth2
     * @return array  ['http_code' => int, 'body' => string]
     */
    protected function _http_post_json($url, $data, $token)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,            $url);
        curl_setopt($ch, CURLOPT_POST,           TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS,     json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT,        30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER,     array(
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ));
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return array('http_code' => $code, 'body' => $body);
    }

    /**
     * Retourne le chemin du fichier de log du jour.
     * Overridable pour les tests (évite d'écrire dans application/logs).
     *
     * @return string
     */
    protected function _get_log_file()
    {
        return APPPATH . 'logs/helloasso_payments_' . date('Y-m-d') . '.log';
    }

    // -----------------------------------------------------------------------
    // Helpers privés
    // -----------------------------------------------------------------------

    /**
     * Construit un tableau d'erreur uniforme.
     */
    private function _error($message, $code, $txid, $type)
    {
        return array(
            'success'       => FALSE,
            'error_message' => $message,
            'error_code'    => $code,
        );
    }

    /**
     * Extrait le message d'erreur lisible depuis le corps d'une réponse HelloAsso.
     */
    private function _parse_api_error($body)
    {
        $data = json_decode($body, TRUE);
        if (!is_array($data)) {
            return 'Unknown error';
        }
        if (!empty($data['message'])) {
            return $data['message'];
        }
        if (!empty($data['title'])) {
            return $data['title'];
        }
        if (!empty($data['errors']) && is_array($data['errors'])) {
            $first = reset($data['errors']);
            if (is_array($first) && !empty($first['message'])) {
                return $first['message'];
            }
            if (is_string($first)) {
                return $first;
            }
        }
        return 'Unknown error';
    }
}

/* End of file Helloasso.php */
/* Location: ./application/libraries/Helloasso.php */
