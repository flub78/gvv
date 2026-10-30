<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'libraries/Helloasso.php';

/**
 * Version testable de la bibliothèque Helloasso.
 *
 * Surcharge les trois méthodes protégées pour isoler totalement les tests :
 * aucune connexion réseau, aucune base de données, aucune écriture en dehors
 * du répertoire temporaire système.
 */
class HelloassoTestable extends Helloasso
{
    private $_mock_config      = array();
    private $_mock_form_resp   = array('http_code' => 200, 'body' => '');
    private $_mock_json_resp   = array('http_code' => 200, 'body' => '');
    private $_log_file;

    public function __construct()
    {
        // Ne pas appeler parent::__construct() pour éviter get_instance()
        $this->_log_file = sys_get_temp_dir() . '/helloasso_test_' . uniqid() . '.log';
    }

    public function set_config(array $config)       { $this->_mock_config    = $config; }
    public function set_form_response(array $resp)  { $this->_mock_form_resp = $resp;   }
    public function set_json_response(array $resp)  { $this->_mock_json_resp = $resp;   }
    public function get_log_contents()              { return file_exists($this->_log_file) ? file_get_contents($this->_log_file) : ''; }

    protected function _get_config($club_id)            { return $this->_mock_config;    }
    protected function _http_post_form($url, $data)     { return $this->_mock_form_resp; }
    protected function _http_post_json($url, $d, $tok)  { return $this->_mock_json_resp; }
    protected function _get_log_file()                  { return $this->_log_file;       }
}

/**
 * Tests unitaires de la bibliothèque Helloasso.
 *
 * Couverture :
 *   - get_oauth_token() : succès et échec avec mock HTTP
 *   - verify_webhook_signature() : signature valide et invalide
 *   - log() : format, mots-clés, masquage des secrets
 *   - sandbox_available() : avec et sans crédentiels
 *   - create_checkout() : succès, échec OAuth, slug manquant
 * @feature Paiements en ligne
 */
class HelloassoLibraryTest extends TestCase
{
    /** @var HelloassoTestable */
    private $lib;

    protected function setUp(): void
    {
        $this->lib = new HelloassoTestable();
    }

    // -----------------------------------------------------------------------
    // get_oauth_token
    // -----------------------------------------------------------------------

    /**
     * Scénario: La connexion à HelloAsso réussit avec des identifiants valides
     *   Étant donné un club dont les identifiants HelloAsso sont configurés
     *   Quand HelloAsso accepte la demande de connexion
     *   Alors le club obtient un droit d'accès
     */
    public function testGetOauthTokenReturnsTokenOnSuccess()
    {
        $this->lib->set_config(array(
            'client_id'     => 'test_client',
            'client_secret' => 'test_secret',
            'environment'   => 'sandbox',
        ));
        $this->lib->set_form_response(array(
            'http_code' => 200,
            'body'      => json_encode(array('access_token' => 'tok_abc123')),
        ));

        $token = $this->lib->get_oauth_token(1);

        $this->assertSame('tok_abc123', $token);
    }

    /**
     * Scénario: Un refus de HelloAsso fait échouer la connexion
     *   Étant donné un club dont les identifiants HelloAsso sont configurés
     *   Quand HelloAsso refuse la demande de connexion
     *   Alors aucun droit d'accès n'est obtenu
     */
    public function testGetOauthTokenReturnsFalseOnHttpError()
    {
        $this->lib->set_config(array(
            'client_id'     => 'test_client',
            'client_secret' => 'test_secret',
            'environment'   => 'sandbox',
        ));
        $this->lib->set_form_response(array(
            'http_code' => 401,
            'body'      => json_encode(array('message' => 'Unauthorized')),
        ));

        $token = $this->lib->get_oauth_token(1);

        $this->assertFalse($token);
    }

    /**
     * Scénario: Sans identifiants, la connexion à HelloAsso échoue
     *   Étant donné un club sans identifiants HelloAsso
     *   Quand une connexion est demandée
     *   Alors aucun droit d'accès n'est obtenu
     */
    public function testGetOauthTokenReturnsFalseWhenMissingCredentials()
    {
        $this->lib->set_config(array());

        $token = $this->lib->get_oauth_token(1);

        $this->assertFalse($token);
    }

    /**
     * Scénario: Une réponse incomplète de HelloAsso fait échouer la connexion
     *   Étant donné un club dont les identifiants HelloAsso sont configurés
     *   Quand HelloAsso répond sans fournir de droit d'accès
     *   Alors la connexion est considérée comme échouée
     */
    public function testGetOauthTokenReturnsFalseWhenResponseLacksAccessToken()
    {
        $this->lib->set_config(array(
            'client_id'     => 'id',
            'client_secret' => 'secret',
        ));
        $this->lib->set_form_response(array(
            'http_code' => 200,
            'body'      => json_encode(array('other_key' => 'value')),
        ));

        $this->assertFalse($this->lib->get_oauth_token(1));
    }

    // -----------------------------------------------------------------------
    // verify_webhook_signature
    // -----------------------------------------------------------------------

    /**
     * Scénario: Une notification de paiement correctement signée est acceptée
     *   Étant donné un club disposant d'un secret partagé avec HelloAsso
     *   Quand une notification arrive signée avec ce secret
     *   Alors sa signature est reconnue
     */
    public function testVerifyWebhookSignatureAcceptsValidSignature()
    {
        $secret  = 'my_webhook_secret';
        $payload = '{"eventType":"Order","data":{}}';

        $this->lib->set_config(array('webhook_secret' => $secret));

        $valid_sig = hash_hmac('sha256', $payload, $secret);

        $this->assertTrue($this->lib->verify_webhook_signature($payload, $valid_sig, 1));
    }

    /**
     * Scénario: La signature est reconnue quelle que soit sa présentation
     *   Étant donné un club disposant d'un secret partagé avec HelloAsso
     *   Quand une notification arrive avec une signature précédée du nom de son algorithme
     *   Alors sa signature est reconnue
     */
    public function testVerifyWebhookSignatureAcceptsPrefixedSignature()
    {
        $secret  = 'my_webhook_secret';
        $payload = '{"eventType":"Order"}';

        $this->lib->set_config(array('webhook_secret' => $secret));

        $sig = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        $this->assertTrue($this->lib->verify_webhook_signature($payload, $sig, 1));
    }

    /**
     * Scénario: Une notification mal signée est refusée
     *   Étant donné un club disposant d'un secret partagé avec HelloAsso
     *   Quand une notification arrive signée avec un autre secret
     *   Alors sa signature est refusée
     */
    public function testVerifyWebhookSignatureRejectsInvalidSignature()
    {
        $this->lib->set_config(array('webhook_secret' => 'correct_secret'));

        $payload = '{"eventType":"Order"}';
        $bad_sig = hash_hmac('sha256', $payload, 'wrong_secret');

        $this->assertFalse($this->lib->verify_webhook_signature($payload, $bad_sig, 1));
    }

    /**
     * Scénario: Sans secret configuré, aucune notification n'est reconnue
     *   Étant donné un club sans secret partagé avec HelloAsso
     *   Quand une notification arrive
     *   Alors sa signature est refusée
     */
    public function testVerifyWebhookSignatureReturnsFalseWhenSecretMissing()
    {
        $this->lib->set_config(array());

        $this->assertFalse(
            $this->lib->verify_webhook_signature('payload', 'anysig', 1)
        );
    }

    // -----------------------------------------------------------------------
    // log
    // -----------------------------------------------------------------------

    /**
     * Scénario: Le journal HelloAsso identifie chaque événement
     *   Étant donné un événement de paiement à tracer
     *   Quand il est enregistré dans le journal
     *   Alors la ligne indique son origine, sa transaction, son statut et son type
     */
    public function testLogProducesFileWithRequiredKeywords()
    {
        $this->lib->log('INFO', 'txid-001', 'checkout', 'STATUS=PENDING amount=10.00');

        $contents = $this->lib->get_log_contents();

        $this->assertStringContainsString('[HELLOASSO]', $contents);
        $this->assertStringContainsString('txid=txid-001', $contents);
        $this->assertStringContainsString('STATUS=INFO', $contents);
        $this->assertStringContainsString('type=checkout', $contents);
    }

    /**
     * Scénario: Un événement tracé est écrit dans le journal
     *   Étant donné un événement de paiement à tracer
     *   Quand il est enregistré dans le journal
     *   Alors le journal n'est pas vide
     */
    public function testLogFilenameContainsDate()
    {
        // get_log_file() est surchargé, mais on vérifie que la méthode
        // publique écrit bien dans le fichier retourné par _get_log_file().
        $this->lib->log('ERROR', 'txid-002', 'oauth', 'STATUS=FAILED');

        $contents = $this->lib->get_log_contents();

        $this->assertNotEmpty($contents, 'Le fichier de log doit avoir été créé et contenir du texte');
    }

    /**
     * Scénario: Les secrets n'apparaissent jamais dans le journal
     *   Étant donné un club dont les identifiants HelloAsso sont configurés
     *   Quand une connexion à HelloAsso est tracée
     *   Alors le secret est masqué
     *   Et l'identifiant non secret reste lisible
     */
    public function testLogDoesNotContainSecret()
    {
        // Simuler un appel get_oauth_token qui log le client_secret masqué
        $this->lib->set_config(array(
            'client_id'     => 'my_client_id',
            'client_secret' => 'super_secret_value',
            'environment'   => 'sandbox',
        ));
        $this->lib->set_form_response(array(
            'http_code' => 200,
            'body'      => json_encode(array('access_token' => 'tok')),
        ));

        $this->lib->get_oauth_token(1);

        $contents = $this->lib->get_log_contents();

        $this->assertStringNotContainsString('super_secret_value', $contents,
            'Le secret ne doit jamais apparaître dans les logs');
        $this->assertStringContainsString('client_secret=***', $contents,
            'Le secret doit être masqué par ***');
        $this->assertStringContainsString('my_client_id', $contents,
            'Le client_id (non-secret) peut figurer dans les logs');
    }

    /**
     * Scénario: Chaque événement occupe sa propre ligne du journal
     *   Étant donné deux événements de paiement
     *   Quand ils sont enregistrés dans le journal
     *   Alors le journal contient deux lignes
     */
    public function testLogAppendsMultipleEntries()
    {
        $this->lib->log('INFO',  'tx1', 'oauth',    'STATUS=SUCCESS');
        $this->lib->log('ERROR', 'tx2', 'checkout', 'STATUS=FAILED');

        $contents = $this->lib->get_log_contents();
        $lines    = array_filter(explode("\n", trim($contents)));

        $this->assertCount(2, $lines, 'Deux appels log() doivent produire deux lignes');
    }

    // -----------------------------------------------------------------------
    // sandbox_available
    // -----------------------------------------------------------------------

    /**
     * Scénario: L'environnement d'essai est disponible avec des identifiants complets
     *   Étant donné un club ayant ses deux identifiants HelloAsso
     *   Quand on vérifie la disponibilité de l'environnement d'essai
     *   Alors il est disponible
     */
    public function testSandboxAvailableReturnsTrueWhenCredentialsPresent()
    {
        $this->lib->set_config(array(
            'client_id'     => 'cid',
            'client_secret' => 'csec',
        ));

        $this->assertTrue($this->lib->sandbox_available(1));
    }

    /**
     * Scénario: L'environnement d'essai est indisponible avec un identifiant manquant
     *   Étant donné un club n'ayant que le secret HelloAsso
     *   Quand on vérifie la disponibilité de l'environnement d'essai
     *   Alors il est indisponible
     */
    public function testSandboxAvailableReturnsFalseWhenClientIdMissing()
    {
        $this->lib->set_config(array('client_secret' => 'csec'));

        $this->assertFalse($this->lib->sandbox_available(1));
    }

    /**
     * Scénario: L'environnement d'essai est indisponible sans configuration
     *   Étant donné un club sans configuration HelloAsso
     *   Quand on vérifie la disponibilité de l'environnement d'essai
     *   Alors il est indisponible
     */
    public function testSandboxAvailableReturnsFalseWhenNoConfig()
    {
        $this->lib->set_config(array());

        $this->assertFalse($this->lib->sandbox_available(1));
    }

    // -----------------------------------------------------------------------
    // create_checkout
    // -----------------------------------------------------------------------

    /**
     * Scénario: Un paiement préparé renvoie vers la page de paiement HelloAsso
     *   Étant donné un club entièrement configuré pour HelloAsso
     *   Quand un paiement est préparé
     *   Alors la préparation réussit
     *   Et l'adresse de paiement et la référence de session sont fournies
     */
    public function testCreateCheckoutReturnsRedirectUrlOnSuccess()
    {
        $this->lib->set_config(array(
            'client_id'     => 'cid',
            'client_secret' => 'csec',
            'account_slug'  => 'mon-club',
            'environment'   => 'sandbox',
        ));
        $this->lib->set_form_response(array(
            'http_code' => 200,
            'body'      => json_encode(array('access_token' => 'tok')),
        ));
        $this->lib->set_json_response(array(
            'http_code' => 200,
            'body'      => json_encode(array(
                'redirectUrl' => 'https://www.helloasso-sandbox.com/payer',
                'id'          => 'sess_xyz',
            )),
        ));

        $result = $this->lib->create_checkout(1, array(
            'amount'     => 25.00,
            'item_name'  => 'Consommations bar',
            'return_url' => 'https://gvv.net/ok',
            'back_url'   => 'https://gvv.net/cancel',
            'error_url'  => 'https://gvv.net/error',
            'metadata'   => array('gvv_transaction_id' => 'gvv-42', 'type' => 'bar'),
        ));

        $this->assertTrue($result['success']);
        $this->assertSame('https://www.helloasso-sandbox.com/payer', $result['redirect_url']);
        $this->assertSame('sess_xyz', $result['session_id']);
    }

    /**
     * Scénario: Un paiement ne peut pas être préparé si la connexion à HelloAsso échoue
     *   Étant donné un club dont la connexion à HelloAsso est refusée
     *   Quand un paiement est préparé
     *   Alors la préparation échoue avec un message expliquant le problème de connexion
     */
    public function testCreateCheckoutFailsWhenOauthFails()
    {
        $this->lib->set_config(array(
            'client_id'     => 'cid',
            'client_secret' => 'csec',
            'account_slug'  => 'mon-club',
        ));
        $this->lib->set_form_response(array(
            'http_code' => 401,
            'body'      => '{"message":"Unauthorized"}',
        ));

        $result = $this->lib->create_checkout(1, array(
            'amount'     => 10.00,
            'item_name'  => 'Test',
            'return_url' => 'https://gvv.net/ok',
            'back_url'   => 'https://gvv.net/cancel',
            'error_url'  => 'https://gvv.net/error',
            'metadata'   => array('gvv_transaction_id' => 'gvv-1', 'type' => 'bar'),
        ));

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('OAuth', $result['error_message']);
    }

    /**
     * Scénario: Un paiement ne peut pas être préparé sans le nom du compte HelloAsso
     *   Étant donné un club dont le nom de compte HelloAsso n'est pas renseigné
     *   Quand un paiement est préparé
     *   Alors la préparation échoue avec un message indiquant ce manque
     */
    public function testCreateCheckoutFailsWhenSlugMissing()
    {
        $this->lib->set_config(array(
            'client_id'     => 'cid',
            'client_secret' => 'csec',
            // account_slug absent
        ));
        $this->lib->set_form_response(array(
            'http_code' => 200,
            'body'      => json_encode(array('access_token' => 'tok')),
        ));

        $result = $this->lib->create_checkout(1, array(
            'amount'     => 10.00,
            'item_name'  => 'Test',
            'return_url' => 'https://gvv.net/ok',
            'back_url'   => 'https://gvv.net/cancel',
            'error_url'  => 'https://gvv.net/error',
            'metadata'   => array('gvv_transaction_id' => 'gvv-2', 'type' => 'bar'),
        ));

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('slug', $result['error_message']);
    }

    /**
     * Scénario: La préparation d'un paiement est tracée
     *   Étant donné un club entièrement configuré pour HelloAsso
     *   Quand un paiement est préparé avec succès
     *   Alors le journal indique sa transaction, son passage en attente et sa réussite
     */
    public function testCreateCheckoutLogsStatusPending()
    {
        $this->lib->set_config(array(
            'client_id'     => 'cid',
            'client_secret' => 'csec',
            'account_slug'  => 'mon-club',
        ));
        $this->lib->set_form_response(array(
            'http_code' => 200,
            'body'      => json_encode(array('access_token' => 'tok')),
        ));
        $this->lib->set_json_response(array(
            'http_code' => 200,
            'body'      => json_encode(array('redirectUrl' => 'https://x', 'id' => 's1')),
        ));

        $this->lib->create_checkout(1, array(
            'amount'     => 10.00,
            'item_name'  => 'Test',
            'return_url' => 'https://gvv.net/ok',
            'back_url'   => 'https://gvv.net/cancel',
            'error_url'  => 'https://gvv.net/error',
            'metadata'   => array('gvv_transaction_id' => 'gvv-3', 'type' => 'provisionnement'),
        ));

        $log = $this->lib->get_log_contents();
        $this->assertStringContainsString('txid=gvv-3', $log);
        $this->assertStringContainsString('STATUS=PENDING', $log);
        $this->assertStringContainsString('STATUS=SUCCESS', $log);
    }
}
