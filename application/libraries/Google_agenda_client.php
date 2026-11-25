<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Google_agenda_client — adaptateur HTTP vers Google (autorisation OAuth et
 * événements de l'agenda principal). Aucune règle métier : il traduit les
 * appels et classe les erreurs (Google_agenda_exception).
 *
 * Périmètres demandés : calendar.events (écriture des événements) et
 * openid email (adresse du compte, affichée au membre).
 *
 * Sur une plateforme de test, le paramètre $config['agenda_google_bouchon']
 * de program.php (absent de la page Configuration) dirige toutes les adresses
 * vers le contrôleur agenda_google_bouchon, qui simule Google.
 *
 * @see doc/design_notes/synchronisation_agenda_google_reservations_design.md (D4)
 */
class Google_agenda_client
{
    const SCOPES = 'https://www.googleapis.com/auth/calendar.events openid email';

    protected $client_id;
    protected $client_secret;
    protected $redirect_uri;
    protected $timezone;

    /** @var array adresses des points d'accès : auth, token, revoke, calendar */
    protected $endpoints;

    /**
     * @param array $params client_id, client_secret, redirect_uri, timezone,
     *                      bouchon (bool), base_url (pour le bouchon)
     *                      — lus dans la configuration quand ils manquent
     */
    public function __construct($params = array())
    {
        $CI = function_exists('get_instance') ? get_instance() : null;
        $item = function ($key) use ($CI) {
            return ($CI && isset($CI->config)) ? $CI->config->item($key) : null;
        };
        if ($CI && isset($CI->config) && !array_key_exists('client_id', (array) $params)) {
            $CI->config->load('program', FALSE, TRUE);
        }

        $this->client_id = isset($params['client_id']) ? $params['client_id'] : (string) $item('agenda_google_client_id');
        $this->client_secret = isset($params['client_secret']) ? $params['client_secret'] : (string) $item('agenda_google_client_secret');
        $this->redirect_uri = isset($params['redirect_uri']) ? $params['redirect_uri']
            : (function_exists('site_url') ? site_url('mes_reservations/agenda_google_retour') : '');
        $this->timezone = isset($params['timezone']) ? $params['timezone'] : date_default_timezone_get();

        $bouchon = isset($params['bouchon']) ? (bool) $params['bouchon'] : self::bouchon_actif();
        if ($bouchon) {
            $base = isset($params['base_url']) ? rtrim($params['base_url'], '/')
                : rtrim(site_url('agenda_google_bouchon'), '/');
            $this->endpoints = array(
                'auth'     => $base . '/autoriser',
                'token'    => $base . '/token',
                'revoke'   => $base . '/revoquer',
                'calendar' => $base . '/calendar',
            );
        } else {
            $this->endpoints = array(
                'auth'     => 'https://accounts.google.com/o/oauth2/v2/auth',
                'token'    => 'https://oauth2.googleapis.com/token',
                'revoke'   => 'https://oauth2.googleapis.com/revoke',
                'calendar' => 'https://www.googleapis.com/calendar/v3',
            );
        }
    }

    /**
     * Le bouchon remplace Google seulement si program.php le demande
     * explicitement (plateforme de test). Ce paramètre n'est pas proposé dans
     * la page Configuration.
     */
    public static function bouchon_actif()
    {
        $CI = function_exists('get_instance') ? get_instance() : null;
        if (!$CI || !isset($CI->config)) {
            return FALSE;
        }
        $CI->config->load('program', FALSE, TRUE);
        return $CI->config->item('agenda_google_bouchon') === TRUE;
    }

    /**
     * L'application Google est-elle déclarée (identifiant et secret) ?
     */
    public function is_configured()
    {
        return $this->client_id !== '' && $this->client_secret !== '';
    }

    // =========================================================================
    // Autorisation
    // =========================================================================

    /**
     * Adresse de l'écran d'autorisation de Google.
     *
     * @param string $state valeur liée à la session, renvoyée au retour
     */
    public function authorization_url($state)
    {
        return $this->endpoints['auth'] . '?' . http_build_query(array(
            'client_id'     => $this->client_id,
            'redirect_uri'  => $this->redirect_uri,
            'response_type' => 'code',
            'scope'         => self::SCOPES,
            'access_type'   => 'offline',
            'prompt'        => 'consent',      // garantit un jeton de renouvellement
            'include_granted_scopes' => 'false',
            'state'         => $state,
        ), '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Échange le code reçu au retour contre les jetons.
     *
     * @param string $code
     * @return array refresh_token, access_token, email
     * @throws Google_agenda_exception
     */
    public function exchange_code($code)
    {
        $data = $this->token_request(array(
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $this->redirect_uri,
        ));
        if (empty($data['refresh_token'])) {
            throw new Google_agenda_exception('Google n\'a pas fourni de jeton de renouvellement', Google_agenda_exception::AUTORISATION);
        }
        if (empty($data['scope']) || strpos($data['scope'], 'auth/calendar.events') === FALSE) {
            throw new Google_agenda_exception('L\'accès à l\'agenda n\'a pas été accordé', Google_agenda_exception::REFUS);
        }
        return array(
            'refresh_token' => $data['refresh_token'],
            'access_token'  => isset($data['access_token']) ? $data['access_token'] : '',
            'email'         => self::email_from_id_token(isset($data['id_token']) ? $data['id_token'] : ''),
        );
    }

    /**
     * Obtient un jeton d'accès à partir du jeton de renouvellement.
     *
     * @throws Google_agenda_exception AUTORISATION si le jeton n'est plus valable
     */
    public function access_token($refresh_token)
    {
        $data = $this->token_request(array(
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refresh_token,
        ));
        if (empty($data['access_token'])) {
            throw new Google_agenda_exception('Réponse de Google sans jeton d\'accès', Google_agenda_exception::PASSAGERE);
        }
        return $data['access_token'];
    }

    /**
     * Révoque le jeton chez Google. Un jeton déjà invalide est considéré
     * comme révoqué.
     *
     * @return bool TRUE si le jeton n'est plus utilisable
     * @throws Google_agenda_exception PASSAGERE si Google est injoignable
     */
    public function revoke($token)
    {
        list($status, $body) = $this->http('POST', $this->endpoints['revoke'],
            array('Content-Type: application/x-www-form-urlencoded'),
            http_build_query(array('token' => $token)));
        if ($status >= 200 && $status < 300) {
            return TRUE;
        }
        $error = self::classify($status, $body);
        if ($error->kind === Google_agenda_exception::PASSAGERE) {
            throw $error;
        }
        return TRUE;  // 400 invalid_token : déjà inutilisable
    }

    // =========================================================================
    // Événements de l'agenda principal
    // =========================================================================

    /**
     * Crée un événement dans l'agenda principal.
     *
     * @param string $access_token
     * @param array  $event ressource Event de l'API Google Agenda
     * @return string identifiant de l'événement créé
     * @throws Google_agenda_exception
     */
    public function insert_event($access_token, $event)
    {
        $data = $this->calendar_request('POST', '', $access_token, $event);
        if (empty($data['id'])) {
            throw new Google_agenda_exception('Réponse de Google sans identifiant d\'événement', Google_agenda_exception::PASSAGERE);
        }
        return (string) $data['id'];
    }

    /**
     * Remplace le contenu d'un événement créé par GVV.
     *
     * @throws Google_agenda_exception DISPARU si le membre l'a supprimé
     */
    public function update_event($access_token, $event_id, $event)
    {
        $this->calendar_request('PUT', $event_id, $access_token, $event);
    }

    /**
     * Supprime un événement créé par GVV. Un événement déjà supprimé n'est
     * pas une erreur.
     */
    public function delete_event($access_token, $event_id)
    {
        try {
            $this->calendar_request('DELETE', $event_id, $access_token, NULL);
        } catch (Google_agenda_exception $e) {
            if ($e->kind !== Google_agenda_exception::DISPARU) {
                throw $e;
            }
        }
    }

    /**
     * Requête sur les événements de l'agenda principal.
     */
    protected function calendar_request($method, $event_id, $access_token, $event)
    {
        $url = $this->endpoints['calendar'] . '/calendars/primary/events'
            . ($event_id !== '' ? '/' . rawurlencode($event_id) : '');
        $headers = array('Authorization: Bearer ' . $access_token, 'Content-Type: application/json; charset=UTF-8');
        list($status, $body) = $this->http($method, $url, $headers, $event === NULL ? NULL : json_encode($event));
        if ($status < 200 || $status >= 300) {
            throw self::classify($status, $body, $event_id !== '');
        }
        $data = json_decode((string) $body, TRUE);
        return is_array($data) ? $data : array();
    }

    /**
     * Fuseau horaire du club, appliqué aux dates des événements (SYNC-CNT-04).
     */
    public function timezone()
    {
        return $this->timezone;
    }

    // =========================================================================
    // Classement des erreurs et transport
    // =========================================================================

    /**
     * Classe une réponse d'erreur de Google.
     *
     * @param int    $status code HTTP (0 : Google injoignable)
     * @param string $body
     * @param bool   $event_call TRUE pour un appel sur un événement existant
     * @return Google_agenda_exception
     */
    public static function classify($status, $body, $event_call = FALSE)
    {
        $json = json_decode((string) $body, TRUE);
        $code = '';
        $reason = '';
        if (is_array($json)) {
            if (isset($json['error']) && is_string($json['error'])) {
                $code = $json['error'];                                   // OAuth
            } elseif (isset($json['error']['errors'][0]['reason'])) {
                $reason = $json['error']['errors'][0]['reason'];          // API agenda
            } elseif (isset($json['error']['status'])) {
                $reason = $json['error']['status'];
            }
        }
        $message = "HTTP $status" . ($code ? " $code" : '') . ($reason ? " $reason" : '');

        if ($status === 0 || $status >= 500 || $status === 429) {
            return new Google_agenda_exception($message, Google_agenda_exception::PASSAGERE);
        }
        if ($status === 403 && in_array($reason, array('rateLimitExceeded', 'userRateLimitExceeded', 'quotaExceeded', 'RESOURCE_EXHAUSTED'), TRUE)) {
            return new Google_agenda_exception($message, Google_agenda_exception::PASSAGERE);
        }
        if ($status === 401 || $code === 'invalid_grant' || $code === 'unauthorized_client' || $status === 403) {
            return new Google_agenda_exception($message, Google_agenda_exception::AUTORISATION);
        }
        if ($event_call && ($status === 404 || $status === 410)) {
            return new Google_agenda_exception($message, Google_agenda_exception::DISPARU);
        }
        return new Google_agenda_exception($message, Google_agenda_exception::REQUETE);
    }

    /**
     * Adresse du compte, lue dans le jeton d'identité renvoyé directement
     * par Google lors de l'échange du code (pas de vérification de signature
     * nécessaire pour un jeton reçu du point d'accès de Google en HTTPS).
     */
    public static function email_from_id_token($id_token)
    {
        $parts = explode('.', (string) $id_token);
        if (count($parts) < 2) {
            return '';
        }
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), TRUE);
        return (is_array($payload) && !empty($payload['email'])) ? (string) $payload['email'] : '';
    }

    /**
     * Requête au point d'accès des jetons.
     */
    protected function token_request($fields)
    {
        $fields['client_id'] = $this->client_id;
        $fields['client_secret'] = $this->client_secret;
        list($status, $body) = $this->http('POST', $this->endpoints['token'],
            array('Content-Type: application/x-www-form-urlencoded'),
            http_build_query($fields));
        if ($status < 200 || $status >= 300) {
            throw self::classify($status, $body);
        }
        $data = json_decode($body, TRUE);
        if (!is_array($data)) {
            throw new Google_agenda_exception('Réponse illisible de Google', Google_agenda_exception::PASSAGERE);
        }
        return $data;
    }

    /**
     * Transport HTTP. Remplacé dans les tests.
     *
     * @return array [code HTTP (0 si injoignable), corps]
     */
    protected function http($method, $url, $headers = array(), $body = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $response = curl_exec($ch);
        $status = ($response === FALSE) ? 0 : (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return array($status, ($response === FALSE) ? '' : (string) $response);
    }
}

/**
 * Erreur classée d'un appel à Google.
 */
class Google_agenda_exception extends Exception
{
    /** Google injoignable, surchargé ou en erreur : réessayer plus tard */
    const PASSAGERE = 'passagere';
    /** Autorisation retirée ou expirée : la liaison doit être rétablie */
    const AUTORISATION = 'autorisation';
    /** Le membre a refusé l'accès à son agenda sur l'écran de Google */
    const REFUS = 'refus';
    /** Événement supprimé de l'agenda par le membre */
    const DISPARU = 'disparu';
    /** Requête refusée pour une autre raison */
    const REQUETE = 'requete';

    /** @var string */
    public $kind;

    public function __construct($message, $kind)
    {
        parent::__construct($message);
        $this->kind = $kind;
    }
}
