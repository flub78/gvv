<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require_once APPPATH . 'libraries/Google_agenda_client.php';

/**
 * Agenda_google_bouchon — remplace Google sur une plateforme de test.
 *
 * Actif seulement si program.php contient $config['agenda_google_bouchon'] = TRUE
 * (paramètre absent de la page Configuration). Sinon, toute requête reçoit 404.
 *
 * Il joue les points d'accès utilisés par Google_agenda_client :
 *   autoriser                       écran d'autorisation simulé (GET, puis POST du choix)
 *   token                           échange du code, renouvellement du jeton d'accès
 *   revoquer                        révocation d'un jeton
 *   calendar/calendars/primary/events[/{id}]   création, modification, suppression d'un événement
 *
 * Son état (codes, jetons, événements) est conservé dans la table agenda_google_bouchon.
 * Un jeton marqué « révoqué » simule une autorisation retirée par le membre dans Google.
 */
class Agenda_google_bouchon extends CI_Controller
{
    const TABLE = 'agenda_google_bouchon';

    function __construct()
    {
        parent::__construct();
        if (!Google_agenda_client::bouchon_actif()) {
            show_404();
        }
    }

    // =========================================================================
    // Autorisation
    // =========================================================================

    /**
     * Écran d'autorisation simulé. Le choix est envoyé en POST sur la même adresse.
     */
    public function autoriser()
    {
        $redirect_uri = (string) $this->input->get_post('redirect_uri');
        $state = (string) $this->input->get_post('state');
        $expected_uri = site_url('mes_reservations/agenda_google_retour');
        if ($redirect_uri !== $expected_uri) {
            $this->_json(400, array('error' => 'redirect_uri_mismatch'));
            return;
        }

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $separator = (strpos($redirect_uri, '?') === FALSE) ? '?' : '&';
            if ($this->input->post('choix') !== 'autoriser') {
                redirect($redirect_uri . $separator . http_build_query(array('error' => 'access_denied', 'state' => $state)));
                return;
            }
            $scope = 'openid https://www.googleapis.com/auth/userinfo.email';
            if ($this->input->post('agenda')) {
                $scope .= ' https://www.googleapis.com/auth/calendar.events';
            }
            $code = 'code-' . bin2hex(random_bytes(12));
            $this->_insert('code', $code, trim((string) $this->input->post('compte')), json_encode(array('scope' => $scope)));
            redirect($redirect_uri . $separator . http_build_query(array('code' => $code, 'state' => $state)));
            return;
        }

        $h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html lang="fr"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Google simulé</title>'
            . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">'
            . '</head><body class="bg-light"><div class="container py-5" style="max-width:520px">'
            . '<div class="alert alert-warning">Bouchon de test : cet écran remplace Google. Aucune donnée n\'est envoyée à Google.</div>'
            . '<div class="card"><div class="card-body">'
            . '<h1 class="h4 mb-3">GVV souhaite accéder à votre compte Google</h1>'
            . '<form method="post" action="' . $h(site_url('agenda_google_bouchon/autoriser')) . '">'
            . '<input type="hidden" name="redirect_uri" value="' . $h($redirect_uri) . '">'
            . '<input type="hidden" name="state" value="' . $h($state) . '">'
            . '<div class="mb-3"><label class="form-label" for="compte">Compte Google</label>'
            . '<input class="form-control" type="email" id="compte" name="compte" value="compte.test@example.org" required></div>'
            . '<div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="agenda" name="agenda" value="1" checked>'
            . '<label class="form-check-label" for="agenda">Afficher, créer, modifier et supprimer des événements dans vos agendas</label></div>'
            . '<button type="submit" name="choix" value="autoriser" class="btn btn-primary me-2" id="bouchon-autoriser">Autoriser</button>'
            . '<button type="submit" name="choix" value="refuser" class="btn btn-outline-secondary" id="bouchon-refuser" formnovalidate>Refuser</button>'
            . '</form></div></div></div></body></html>';
    }

    /**
     * Point d'accès des jetons (échange du code, renouvellement).
     */
    public function token()
    {
        $grant = $this->input->post('grant_type');
        if ($grant === 'authorization_code') {
            $code = $this->_find('code', (string) $this->input->post('code'));
            if (!$code) {
                $this->_json(400, array('error' => 'invalid_grant'));
                return;
            }
            $this->db->delete(self::TABLE, array('id' => $code['id']));
            $refresh = 'jeton-' . bin2hex(random_bytes(16));
            $this->_insert('jeton', $refresh, $code['compte'], NULL);
            $scope = json_decode($code['contenu'], TRUE)['scope'];
            $this->_json(200, array(
                'access_token'  => 'acces-' . $refresh,
                'refresh_token' => $refresh,
                'expires_in'    => 3599,
                'scope'         => $scope,
                'token_type'    => 'Bearer',
                'id_token'      => self::_id_token($code['compte']),
            ));
            return;
        }
        if ($grant === 'refresh_token') {
            $jeton = $this->_find('jeton', (string) $this->input->post('refresh_token'));
            if (!$jeton || $jeton['revoque']) {
                $this->_json(400, array('error' => 'invalid_grant', 'error_description' => 'Token has been expired or revoked.'));
                return;
            }
            $this->_json(200, array('access_token' => 'acces-' . $jeton['cle'], 'expires_in' => 3599, 'token_type' => 'Bearer'));
            return;
        }
        $this->_json(400, array('error' => 'unsupported_grant_type'));
    }

    /**
     * Révocation d'un jeton de renouvellement.
     */
    public function revoquer()
    {
        $jeton = $this->_find('jeton', (string) $this->input->post('token'));
        if (!$jeton || $jeton['revoque']) {
            $this->_json(400, array('error' => 'invalid_token'));
            return;
        }
        $this->db->update(self::TABLE, array('revoque' => 1, 'updated_at' => date('Y-m-d H:i:s')), array('id' => $jeton['id']));
        $this->_json(200, new stdClass());
    }

    // =========================================================================
    // Événements de l'agenda principal
    // =========================================================================

    /**
     * calendar/calendars/primary/events[/{id}]
     */
    public function calendar($calendars = '', $calendar_id = '', $events = '', $event_id = '')
    {
        if ($calendars !== 'calendars' || $calendar_id !== 'primary' || $events !== 'events') {
            $this->_json(404, array('error' => array('code' => 404, 'errors' => array(array('reason' => 'notFound')))));
            return;
        }
        $jeton = $this->_authenticated();
        if (!$jeton) {
            $this->_json(401, array('error' => array('code' => 401, 'errors' => array(array('reason' => 'authError')))));
            return;
        }

        $method = $this->input->server('REQUEST_METHOD');
        $body = file_get_contents('php://input');
        if ($event_id === '' && $method === 'POST') {
            $id = 'evt' . bin2hex(random_bytes(10));
            $this->_insert('evenement', $id, $jeton['compte'], $body);
            $this->_json(200, array('id' => $id, 'status' => 'confirmed'));
            return;
        }

        $event = $this->_find('evenement', $event_id);
        if ($event_id === '' || !$event || $event['compte'] !== $jeton['compte']) {
            $this->_json(404, array('error' => array('code' => 404, 'errors' => array(array('reason' => 'notFound')))));
            return;
        }
        if ($event['revoque']) {   // supprimé
            $this->_json(410, array('error' => array('code' => 410, 'errors' => array(array('reason' => 'deleted')))));
            return;
        }
        if ($method === 'PUT') {
            $this->db->update(self::TABLE, array('contenu' => $body, 'updated_at' => date('Y-m-d H:i:s')), array('id' => $event['id']));
            $this->_json(200, array('id' => $event_id, 'status' => 'confirmed'));
            return;
        }
        if ($method === 'DELETE') {
            $this->db->update(self::TABLE, array('revoque' => 1, 'updated_at' => date('Y-m-d H:i:s')), array('id' => $event['id']));
            http_response_code(204);
            return;
        }
        $this->_json(405, array('error' => 'method_not_allowed'));
    }

    // =========================================================================
    // Outils
    // =========================================================================

    /**
     * Jeton de renouvellement correspondant au jeton d'accès reçu, s'il est valable.
     */
    private function _authenticated()
    {
        $header = (string) $this->input->server('HTTP_AUTHORIZATION');
        if (!preg_match('/^Bearer acces-(\S+)$/', $header, $m)) {
            return NULL;
        }
        $jeton = $this->_find('jeton', $m[1]);
        return ($jeton && !$jeton['revoque']) ? $jeton : NULL;
    }

    private function _find($type, $cle)
    {
        if ($cle === '') {
            return NULL;
        }
        $row = $this->db->get_where(self::TABLE, array('type' => $type, 'cle' => $cle))->row_array();
        return $row ?: NULL;
    }

    private function _insert($type, $cle, $compte, $contenu)
    {
        $now = date('Y-m-d H:i:s');
        $this->db->insert(self::TABLE, array(
            'type' => $type, 'cle' => $cle, 'compte' => $compte, 'contenu' => $contenu,
            'created_at' => $now, 'updated_at' => $now,
            'created_by' => 'agenda_google_bouchon', 'updated_by' => 'agenda_google_bouchon',
        ));
    }

    private static function _id_token($email)
    {
        $part = function ($a) { return rtrim(strtr(base64_encode(json_encode($a)), '+/', '-_'), '='); };
        return $part(array('alg' => 'none')) . '.' . $part(array('email' => $email, 'email_verified' => TRUE)) . '.';
    }

    private function _json($status, $data)
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data);
    }
}
