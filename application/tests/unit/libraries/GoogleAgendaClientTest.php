<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'libraries/Google_agenda_client.php';

/**
 * Client Google dont le transport est simulé : enregistre les requêtes et
 * renvoie des réponses préparées, sans réseau.
 */
class GoogleAgendaClientTestDouble extends Google_agenda_client
{
    public $requests = array();
    public $responses = array();

    protected function http($method, $url, $headers = array(), $body = null)
    {
        $this->requests[] = array('method' => $method, 'url' => $url, 'body' => $body);
        return array_shift($this->responses);
    }
}

/**
 * @feature Synchronisation agenda Google
 *
 * Dialogue avec Google pour l'autorisation d'accès à l'agenda d'un membre,
 * sans appel réseau (réponses de Google simulées).
 */
class GoogleAgendaClientTest extends TestCase
{
    private function client($bouchon = FALSE)
    {
        return new GoogleAgendaClientTestDouble(array(
            'client_id'     => 'id-app.apps.googleusercontent.com',
            'client_secret' => 'secret-app',
            'redirect_uri'  => 'https://club.example/index.php/mes_reservations/agenda_google_retour',
            'bouchon'       => $bouchon,
            'base_url'      => 'https://club.example/index.php/agenda_google_bouchon',
        ));
    }

    private static function id_token($email)
    {
        $part = function ($a) { return rtrim(strtr(base64_encode(json_encode($a)), '+/', '-_'), '='); };
        return $part(array('alg' => 'RS256')) . '.' . $part(array('email' => $email)) . '.signature';
    }

    /**
     * Scénario: GVV ne demande que l'écriture dans l'agenda et l'adresse du compte
     *   Étant donné un membre qui demande à relier son agenda
     *   Quand il est envoyé vers Google
     *   Alors Google lui demande seulement d'autoriser l'écriture des événements de son agenda et la lecture de son adresse
     *   Et l'accès est demandé durablement, pour que GVV mette l'agenda à jour en son absence
     *   Et la demande porte une valeur propre à sa session, vérifiée au retour
     */
    public function testAuthorizationUrlAsksNarrowScopeOfflineWithState()
    {
        $url = $this->client()->authorization_url('etat-123');
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $url);
        parse_str(parse_url($url, PHP_URL_QUERY), $q);

        $this->assertSame('https://www.googleapis.com/auth/calendar.events openid email', $q['scope']);
        $this->assertStringNotContainsString('auth/calendar ', $q['scope'] . ' ', 'pas d\'accès complet à l\'agenda');
        $this->assertSame('offline', $q['access_type']);
        $this->assertSame('etat-123', $q['state']);
        $this->assertSame('code', $q['response_type']);
        $this->assertSame('id-app.apps.googleusercontent.com', $q['client_id']);
    }

    /**
     * Scénario: Sur une plateforme de test, Google est remplacé par le bouchon
     *   Étant donné une plateforme de test où le bouchon est demandé
     *   Quand un membre relie son agenda
     *   Alors il est envoyé vers l'écran simulé de GVV et non vers Google
     */
    public function testBouchonReplacesGoogleEndpoints()
    {
        $url = $this->client(TRUE)->authorization_url('x');
        $this->assertStringStartsWith('https://club.example/index.php/agenda_google_bouchon/autoriser?', $url);
    }

    /**
     * Scénario: Le retour de Google donne une autorisation durable et l'adresse du compte
     *   Étant donné un membre qui a accepté l'accès sur l'écran de Google
     *   Quand GVV échange le code reçu au retour
     *   Alors il obtient une autorisation durable
     *   Et l'adresse du compte Google à présenter au membre
     */
    public function testExchangeCodeReturnsRefreshTokenAndEmail()
    {
        $client = $this->client();
        $client->responses[] = array(200, json_encode(array(
            'access_token'  => 'acces',
            'refresh_token' => 'renouvellement',
            'scope'         => 'openid https://www.googleapis.com/auth/calendar.events https://www.googleapis.com/auth/userinfo.email',
            'id_token'      => self::id_token('pilote@gmail.com'),
        )));

        $result = $client->exchange_code('code-retour');

        $this->assertSame('renouvellement', $result['refresh_token']);
        $this->assertSame('pilote@gmail.com', $result['email']);
        parse_str($client->requests[0]['body'], $sent);
        $this->assertSame('authorization_code', $sent['grant_type']);
        $this->assertSame('code-retour', $sent['code']);
        $this->assertSame('secret-app', $sent['client_secret']);
    }

    /**
     * Scénario: Le membre décoche l'accès à son agenda sur l'écran de Google
     *   Étant donné un membre qui n'a accordé que l'accès à son adresse
     *   Quand GVV échange le code reçu au retour
     *   Alors la liaison est refusée comme un refus d'accès
     */
    public function testExchangeCodeWithoutCalendarScopeIsARefusal()
    {
        $client = $this->client();
        $client->responses[] = array(200, json_encode(array(
            'access_token' => 'a', 'refresh_token' => 'r', 'scope' => 'openid email',
        )));
        try {
            $client->exchange_code('c');
            $this->fail('exception attendue');
        } catch (Google_agenda_exception $e) {
            $this->assertSame(Google_agenda_exception::REFUS, $e->kind);
        }
    }

    /**
     * Scénario: Une autorisation retirée par le membre est reconnue
     *   Étant donné un membre qui a retiré l'accès de GVV dans son compte Google
     *   Quand GVV demande un accès à son agenda
     *   Alors l'erreur est classée comme une autorisation à rétablir
     */
    public function testRevokedGrantIsClassifiedAsAuthorization()
    {
        $client = $this->client();
        $client->responses[] = array(400, json_encode(array('error' => 'invalid_grant')));
        try {
            $client->access_token('perime');
            $this->fail('exception attendue');
        } catch (Google_agenda_exception $e) {
            $this->assertSame(Google_agenda_exception::AUTORISATION, $e->kind);
        }
    }

    /**
     * Scénario: Les réponses de Google sont classées selon l'action à mener
     *   Étant donné une réponse d'erreur de Google
     *   Quand GVV la classe
     *   Alors une panne, une surcharge ou un quota atteint est passager et sera retenté
     *   Et une autorisation retirée demande l'intervention du membre
     *   Mais une erreur de configuration de l'application ne suspend aucun membre
     *   Et un événement supprimé par le membre est reconnu comme disparu
     */
    public function testClassification()
    {
        $kinds = array(
            array(0, '', FALSE, Google_agenda_exception::PASSAGERE),
            array(503, '', FALSE, Google_agenda_exception::PASSAGERE),
            array(429, '', TRUE, Google_agenda_exception::PASSAGERE),
            array(403, '{"error":{"errors":[{"reason":"rateLimitExceeded"}]}}', TRUE, Google_agenda_exception::PASSAGERE),
            array(401, '{"error":{"errors":[{"reason":"authError"}]}}', TRUE, Google_agenda_exception::AUTORISATION),
            array(403, '{"error":{"errors":[{"reason":"forbidden"}]}}', TRUE, Google_agenda_exception::AUTORISATION),
            array(400, '{"error":"invalid_grant"}', FALSE, Google_agenda_exception::AUTORISATION),
            array(404, '', TRUE, Google_agenda_exception::DISPARU),
            array(410, '', TRUE, Google_agenda_exception::DISPARU),
            array(404, '', FALSE, Google_agenda_exception::REQUETE),
            array(400, '{"error":{"errors":[{"reason":"invalid"}]}}', TRUE, Google_agenda_exception::REQUETE),
            // quota journalier du projet atteint : passager, aucun membre suspendu
            array(403, '{"error":{"errors":[{"reason":"dailyLimitExceeded"}]}}', TRUE, Google_agenda_exception::PASSAGERE),
            array(403, '{"error":{"errors":[{"reason":"usageLimits"}]}}', TRUE, Google_agenda_exception::PASSAGERE),
            // 403 sans refus explicite : retenté, pas de suspension
            array(403, '{"error":{"errors":[{"reason":"accessNotConfigured"}]}}', TRUE, Google_agenda_exception::REQUETE),
            array(403, '{"error":{"errors":[{"reason":"insufficientPermissions"}]}}', TRUE, Google_agenda_exception::AUTORISATION),
            // identifiant ou secret de l'application erronés : pas de suspension des membres
            array(401, '{"error":"invalid_client"}', FALSE, Google_agenda_exception::REQUETE),
            array(400, '{"error":"unauthorized_client"}', FALSE, Google_agenda_exception::REQUETE),
        );
        foreach ($kinds as list($status, $body, $event, $expected)) {
            $this->assertSame($expected, Google_agenda_client::classify($status, $body, $event)->kind, "HTTP $status $body");
        }
    }

    /**
     * Scénario: GVV crée, modifie et supprime ses événements dans l'agenda principal du membre
     *   Étant donné un membre qui a relié son agenda
     *   Quand GVV crée, modifie puis supprime un événement de réservation
     *   Alors chaque opération porte sur l'agenda principal du membre
     *   Et une suppression d'un événement déjà supprimé par le membre n'est pas une erreur
     */
    public function testEventOperationsTargetPrimaryCalendar()
    {
        $client = $this->client();
        $client->responses[] = array(200, '{"id":"evt1"}');
        $client->responses[] = array(200, '{"id":"evt1"}');
        $client->responses[] = array(204, '');
        $client->responses[] = array(410, '{"error":{"errors":[{"reason":"deleted"}]}}');

        $this->assertSame('evt1', $client->insert_event('acces', array('summary' => 'Vol')));
        $client->update_event('acces', 'evt1', array('summary' => 'Vol modifié'));
        $client->delete_event('acces', 'evt1');
        $client->delete_event('acces', 'evt1');

        $base = 'https://www.googleapis.com/calendar/v3/calendars/primary/events';
        $this->assertSame(array('POST', $base), array($client->requests[0]['method'], $client->requests[0]['url']));
        $this->assertSame(array('PUT', $base . '/evt1'), array($client->requests[1]['method'], $client->requests[1]['url']));
        $this->assertSame(array('DELETE', $base . '/evt1'), array($client->requests[2]['method'], $client->requests[2]['url']));
        $this->assertSame('Vol modifié', json_decode($client->requests[1]['body'], TRUE)['summary']);
    }

    /**
     * Scénario: La modification d'un événement supprimé par le membre est signalée
     *   Étant donné un événement de réservation que le membre a supprimé de son agenda
     *   Quand GVV tente de le modifier
     *   Alors l'événement est signalé comme disparu
     */
    public function testUpdateOfDeletedEventIsReportedAsGone()
    {
        $client = $this->client();
        $client->responses[] = array(404, '{"error":{"errors":[{"reason":"notFound"}]}}');
        try {
            $client->update_event('acces', 'evt1', array());
            $this->fail('exception attendue');
        } catch (Google_agenda_exception $e) {
            $this->assertSame(Google_agenda_exception::DISPARU, $e->kind);
        }
    }

    /**
     * Scénario: Arrêter avec une autorisation déjà retirée réussit
     *   Étant donné un membre dont l'autorisation n'est plus valable chez Google
     *   Quand il arrête la synchronisation
     *   Alors GVV considère l'autorisation comme retirée
     *   Mais si Google est injoignable, l'arrêt signale une erreur passagère
     */
    public function testRevoke()
    {
        $client = $this->client();
        $client->responses[] = array(200, '');
        $client->responses[] = array(400, '{"error":"invalid_token"}');
        $client->responses[] = array(0, '');

        $this->assertTrue($client->revoke('a'));
        $this->assertTrue($client->revoke('b'));
        $this->expectException(Google_agenda_exception::class);
        $client->revoke('c');
    }
}
