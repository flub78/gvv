<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Override CI_Security:
 * - avoid deprecated /e modifier in entity_decode (PHP 7.4+)
 * - protection CSRF toujours active, adaptée à GVV (voir csrf_verify())
 */
class MY_Security extends CI_Security
{
    /** Nom du champ de formulaire et de l'en-tête portant le jeton. */
    const CSRF_TOKEN_NAME = 'gvv_csrf_token';
    const CSRF_HEADER = 'HTTP_X_CSRF_TOKEN';

    /** Nom du cookie (préfixé par cookie_prefix s'il est défini). */
    const CSRF_COOKIE_NAME = 'gvv_csrf_cookie';

    /** Durée de vie du cookie, prolongée à chaque requête. */
    const CSRF_EXPIRE = 604800; // 7 jours

    /**
     * Requêtes POST venant de l'extérieur, authentifiées autrement
     * (signature, liste d'adresses IP) : elles ne portent pas de jeton.
     */
    protected $csrf_excluded_uris = array(
        '#^paiements_en_ligne/helloasso_webhook(/|$)#',
        '#^payments/helloasso_webhook(/|$)#',
        '#^agenda_google_bouchon(/|$)#',
    );

    public function __construct()
    {
        // La protection est activée par le code et non par config.php (non
        // versionné, à FALSE sur les installations existantes). Seul
        // $config['csrf_disable'] = TRUE la coupe, en secours.
        $config =& get_config();
        $config['csrf_protection'] = empty($config['csrf_disable']);
        $config['csrf_token_name'] = self::CSRF_TOKEN_NAME;
        $config['csrf_cookie_name'] = self::CSRF_COOKIE_NAME;
        $config['csrf_expire'] = self::CSRF_EXPIRE;

        parent::__construct();

        // Tous les cookies de GVV (session, jeton CSRF, connexion automatique,
        // ceux de CodeIgniter compris) sont renforcés juste avant l'envoi des
        // en-têtes (harden_cookie_header()) : SameSite, HttpOnly, Secure.
        // ATTENTION : PHP ne garde qu'un seul callback d'en-têtes. Tout autre
        // appel à header_register_callback() remplacerait celui-ci sans erreur
        // et les cookies repartiraient sans protection : ajouter le traitement
        // dans harden_cookies(), plutôt que d'enregistrer un second callback.
        if (PHP_SAPI !== 'cli') {
            header_register_callback(array('MY_Security', 'harden_cookies'));
        }
    }

    /**
     * Renforce les en-têtes Set-Cookie de la réponse.
     * Appelée par PHP juste avant l'envoi des en-têtes.
     */
    public static function harden_cookies()
    {
        $cookies = array();
        foreach (headers_list() as $header) {
            if (stripos($header, 'Set-Cookie:') === 0) {
                $cookies[] = $header;
            }
        }
        if (empty($cookies)) {
            return;
        }
        $https = self::is_https();
        header_remove('Set-Cookie');
        foreach ($cookies as $header) {
            header(self::harden_cookie_header($header, $https), FALSE);
        }
    }

    /**
     * Ajoute à un en-tête Set-Cookie les attributs qui lui manquent :
     * - SameSite=Lax : le navigateur ne joint plus le cookie aux requêtes
     *   qu'un autre site déclenche par une image, un iframe ou un formulaire
     *   (Firefox n'applique pas Lax par défaut) ;
     * - HttpOnly : un script de la page ne peut pas lire le cookie (vol de
     *   session par une faille XSS), sauf le cookie du jeton CSRF, que
     *   csrf.js doit lire ;
     * - Secure, en HTTPS seulement : le cookie n'est jamais envoyé en clair.
     *   En HTTP (gvv.net), le navigateur refuserait un cookie Secure.
     *
     * @param string $header En-tête complet « Set-Cookie: nom=valeur; ... »
     * @param bool $https La requête est-elle arrivée en HTTPS ?
     * @return string
     */
    public static function harden_cookie_header($header, $https)
    {
        $pair = trim(substr($header, strlen('Set-Cookie:')));
        $name = trim(strstr($pair, '=', TRUE) ?: $pair);

        if (!preg_match('/;\s*samesite=/i', $header)) {
            $header .= '; SameSite=Lax';
        }
        $is_csrf_cookie = substr($name, -strlen(self::CSRF_COOKIE_NAME)) === self::CSRF_COOKIE_NAME;
        if (!$is_csrf_cookie && !preg_match('/;\s*httponly\b/i', $header)) {
            $header .= '; HttpOnly';
        }
        if ($https && !preg_match('/;\s*secure\b/i', $header)) {
            $header .= '; Secure';
        }
        return $header;
    }

    /**
     * La requête est-elle arrivée en HTTPS (directement ou derrière un
     * mandataire qui l'indique) ?
     *
     * @return bool
     */
    public static function is_https()
    {
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            return TRUE;
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
            return TRUE;
        }
        return isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443;
    }

    /**
     * Vérification CSRF (double soumission du jeton : cookie + requête).
     *
     * Différences avec CodeIgniter 2 :
     * - le jeton est accepté dans le formulaire ou dans l'en-tête X-CSRF-Token
     *   (appels AJAX, corps JSON) ;
     * - il reste stable pendant la durée du cookie au lieu d'être régénéré à
     *   chaque POST, ce qui casserait les onglets multiples et les appels
     *   AJAX successifs d'une même page ;
     * - les webhooks externes listés dans $csrf_excluded_uris sont ignorés.
     *
     * @return object
     */
    public function csrf_verify()
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
            return $this->csrf_set_cookie();
        }

        if ($this->csrf_uri_excluded()) {
            return $this;
        }

        $token = '';
        if (isset($_POST[$this->_csrf_token_name])) {
            $token = $_POST[$this->_csrf_token_name];
        } elseif (isset($_SERVER[self::CSRF_HEADER])) {
            $token = $_SERVER[self::CSRF_HEADER];
        }
        $cookie = isset($_COOKIE[$this->_csrf_cookie_name]) ? $_COOKIE[$this->_csrf_cookie_name] : '';

        if (!is_string($token) || !is_string($cookie) || $cookie === '' || !hash_equals($cookie, $token)) {
            $this->csrf_show_error();
        }

        unset($_POST[$this->_csrf_token_name]);
        $this->csrf_set_cookie();

        log_message('debug', 'CSRF token verified');
        return $this;
    }

    /**
     * La requête vise-t-elle une adresse exclue de la vérification ?
     *
     * @return bool
     */
    protected function csrf_uri_excluded()
    {
        $uri =& load_class('URI', 'core');
        $uri_string = trim((string) $uri->uri_string(), '/');
        foreach ($this->csrf_excluded_uris as $pattern) {
            if (preg_match($pattern, $uri_string)) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * Refus d'une requête sans jeton valide : message explicite et traduit
     * (page HTML, ou JSON pour un appel AJAX), statut 403.
     */
    public function csrf_show_error()
    {
        log_message('error', 'CSRF: requête POST refusée sur ' . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '?'));

        $lang =& load_class('Lang', 'core');
        // Chargé avant le contrôleur : chemin explicite, get_instance() n'existe pas encore
        $lang->load('gvv', '', FALSE, TRUE, APPPATH);
        $title = $lang->line('gvv_csrf_error_title') ?: 'Request refused';
        $text = $lang->line('gvv_csrf_error_text') ?: 'The page has expired. Reload it and try again.';

        if ($this->is_script_request()) {
            set_status_header(403);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(array('success' => FALSE, 'error' => 'csrf', 'message' => $text));
            exit;
        }

        show_error($text, 403, $title);
    }

    /**
     * La requête vient-elle d'un script (XMLHttpRequest, fetch) plutôt que
     * d'une navigation ? Un appel fetch n'envoie en général ni
     * X-Requested-With ni Accept: application/json, mais csrf.js lui ajoute
     * l'en-tête X-CSRF-Token : seul un script peut poser cet en-tête.
     *
     * @return bool
     */
    public function is_script_request()
    {
        if (isset($_SERVER[self::CSRF_HEADER])) {
            return TRUE;
        }
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return TRUE;
        }
        $accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
        return strpos($accept, 'application/json') !== FALSE;
    }

    /**
     * Jeton CSRF : reprend celui du cookie s'il est valide, sinon en génère un
     * avec un générateur cryptographiquement sûr (CodeIgniter 2 utilise
     * md5(uniqid(rand())), prévisible). Même format : 32 caractères
     * hexadécimaux, ce qui garde valides les cookies déjà émis.
     *
     * @return string
     */
    protected function _csrf_set_hash()
    {
        if ($this->_csrf_hash == '') {
            if (isset($_COOKIE[$this->_csrf_cookie_name]) && is_string($_COOKIE[$this->_csrf_cookie_name])
                && preg_match('#^[0-9a-f]{32}$#iS', $_COOKIE[$this->_csrf_cookie_name]) === 1) {
                return $this->_csrf_hash = $_COOKIE[$this->_csrf_cookie_name];
            }
            $this->_csrf_hash = bin2hex(random_bytes(16));
        }
        return $this->_csrf_hash;
    }

    /**
     * HTML Entities Decode (patched)
     *
     * @param string $str
     * @param string $charset
     * @return string
     */
    public function entity_decode($str, $charset = 'UTF-8')
    {
        if (stristr($str, '&') === FALSE) {
            return $str;
        }

        $str = html_entity_decode($str, ENT_COMPAT, $charset);

        $str = preg_replace_callback('~&#x(0*[0-9a-f]{2,5})~i', function ($matches) {
            return chr(hexdec($matches[1]));
        }, $str);

        $str = preg_replace_callback('~&#([0-9]{2,4})~', function ($matches) {
            return chr($matches[1]);
        }, $str);

        return $str;
    }
}

/* End of file MY_Security.php */
/* Location: ./application/core/MY_Security.php */
