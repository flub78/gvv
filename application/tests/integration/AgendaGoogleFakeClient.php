<?php

require_once APPPATH . 'libraries/Google_agenda_client.php';

/**
 * Faux client Google pour les tests : enregistre les appels, garde les
 * événements en mémoire par compte et simule les erreurs, sans réseau.
 */
class AgendaGoogleFakeClient extends Google_agenda_client
{
    public $calls = array();
    public $email = 'pilote@gmail.com';
    public $exchange_error = NULL;
    public $revoke_error = NULL;

    /** @var array jeton de renouvellement => type d'erreur à lever pour l'obtention du jeton d'accès */
    public $token_errors = array();

    /** @var array jeton d'accès => (identifiant => événement) */
    public $events = array();

    private $seq = 0;

    public function __construct()
    {
        parent::__construct(array('client_id' => 'id', 'client_secret' => 'secret', 'redirect_uri' => 'r',
            'bouchon' => FALSE, 'timezone' => 'Europe/Paris'));
    }

    public function exchange_code($code)
    {
        $this->calls[] = array('exchange_code', $code);
        if ($this->exchange_error) {
            throw new Google_agenda_exception('simulé', $this->exchange_error);
        }
        return array('refresh_token' => 'renouvellement-' . (++$this->seq), 'access_token' => 'a', 'email' => $this->email);
    }

    public function revoke($token)
    {
        $this->calls[] = array('revoke', $token);
        if ($this->revoke_error) {
            throw new Google_agenda_exception('simulé', $this->revoke_error);
        }
        return TRUE;
    }

    public function access_token($refresh_token)
    {
        $this->calls[] = array('access_token', $refresh_token);
        if (isset($this->token_errors[$refresh_token])) {
            throw new Google_agenda_exception('simulé', $this->token_errors[$refresh_token]);
        }
        return 'acces:' . $refresh_token;
    }

    public function insert_event($access_token, $event)
    {
        $id = 'evt' . (++$this->seq);
        $this->calls[] = array('insert_event', $access_token, $id);
        $this->events[$access_token][$id] = $event;
        return $id;
    }

    public function update_event($access_token, $event_id, $event)
    {
        $this->calls[] = array('update_event', $access_token, $event_id);
        if (!isset($this->events[$access_token][$event_id])) {
            throw new Google_agenda_exception('simulé', Google_agenda_exception::DISPARU);
        }
        $this->events[$access_token][$event_id] = $event;
    }

    public function delete_event($access_token, $event_id)
    {
        $this->calls[] = array('delete_event', $access_token, $event_id);
        unset($this->events[$access_token][$event_id]);
    }

    /** Appels d'un type donné */
    public function calls_of($name)
    {
        return array_values(array_filter($this->calls, function ($c) use ($name) { return $c[0] === $name; }));
    }

    protected function http($method, $url, $headers = array(), $body = null)
    {
        throw new LogicException('aucun appel réseau dans les tests');
    }
}
