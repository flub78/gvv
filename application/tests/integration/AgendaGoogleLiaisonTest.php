<?php

require_once(__DIR__ . '/TransactionalTestCase.php');
require_once(__DIR__ . '/AgendaGoogleFakeClient.php');
require_once APPPATH . 'libraries/Secret_box.php';

/**
 * @feature Synchronisation agenda Google
 *
 * Liaison d'un membre avec son agenda Google : activation, arrêt, état, et
 * conditions dans lesquelles la fonction est proposée. Google est remplacé
 * par un faux client ; la clé de chiffrement est temporaire.
 */
class AgendaGoogleLiaisonTest extends TransactionalTestCase
{
    /** @var Agenda_google_sync */
    private $sync;
    /** @var AgendaGoogleFakeClient */
    private $client;
    /** @var Secret_box */
    private $box;
    private $key_file;
    private $saved_config = array();
    private static $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->key_file = sys_get_temp_dir() . '/gvv_agenda_google_test_' . getmypid() . '_' . uniqid() . '.php';
        $this->client = new AgendaGoogleFakeClient();
        $this->box = new Secret_box(array('key_file' => $this->key_file));
        $this->CI->load->library('Agenda_google_sync', array('client' => $this->client, 'box' => $this->box));
        $this->sync = $this->CI->agenda_google_sync;
        $this->saved_config['agenda_google_sync'] = $this->CI->config->item('agenda_google_sync');
        $this->CI->config->set_item('agenda_google_sync', TRUE);
    }

    protected function tearDown(): void
    {
        foreach ($this->saved_config as $key => $value) {
            $this->CI->config->set_item($key, $value);
        }
        if (is_file($this->key_file)) {
            unlink($this->key_file);
        }
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Données de test
    // -------------------------------------------------------------------------

    /**
     * Membre avec un compte de connexion et, éventuellement, un rôle dans une section.
     */
    private function _member($role = 'auto_planchiste')
    {
        $login = 'agg_' . getmypid() . '_' . (++self::$seq);
        $this->CI->db->insert('membres', array(
            'mlogin' => $login, 'mnom' => 'Nom', 'mprenom' => 'Prenom',
            'memail' => $login . '@test.invalid', 'msexe' => 'M', 'actif' => 1, 'ext' => 0, 'm25ans' => 0,
        ));
        $this->CI->db->insert('users', array(
            'username' => $login, 'password' => 'x', 'email' => $login . '@test.invalid',
            'role_id' => 1, 'banned' => 0, 'last_ip' => '127.0.0.1',
            'last_login' => '2000-01-01 00:00:00', 'created' => date('Y-m-d H:i:s'),
        ));
        $user_id = (int) $this->CI->db->insert_id();
        if ($role) {
            $type = $this->CI->db->get_where('types_roles', array('nom' => $role))->row_array();
            $section = $this->CI->db->select('id')->limit(1)->get('sections')->row_array();
            $this->assertNotEmpty($type, "rôle $role");
            $this->CI->db->insert('user_roles_per_section', array(
                'user_id' => $user_id, 'types_roles_id' => $type['id'],
                'section_id' => ($type['scope'] ?? 'section') === 'global' ? NULL : $section['id'],
                'granted_by' => $user_id, 'granted_at' => date('Y-m-d H:i:s'),
            ));
        }
        return $login;
    }

    private function _row($login)
    {
        return $this->CI->db->get_where('agenda_google_liaisons', array('mlogin' => $login))->row_array();
    }

    // -------------------------------------------------------------------------
    // Disponibilité
    // -------------------------------------------------------------------------

    /**
     * Scénario: Un membre qui peut réserver se voit proposer la synchronisation
     *   Étant donné la synchronisation activée pour le club
     *   Et un membre autorisé à réserver
     *   Quand il consulte ses réservations
     *   Alors la synchronisation avec son agenda Google lui est proposée
     */
    public function test_available_for_member_who_can_book()
    {
        $this->assertTrue($this->sync->available_for($this->_member('auto_planchiste')));
        $this->assertTrue($this->sync->available_for($this->_member('instructeur')));
    }

    /**
     * Scénario: Un membre qui ne peut pas réserver ne voit pas la fonction
     *   Étant donné la synchronisation activée pour le club
     *   Et un membre sans droit de réserver
     *   Quand il consulte ses réservations
     *   Alors la synchronisation ne lui est pas proposée
     */
    public function test_not_available_for_member_who_cannot_book()
    {
        $this->assertFalse($this->sync->available_for($this->_member(NULL)));
        $this->assertFalse($this->sync->available_for('membre_inexistant_' . getmypid()));
    }

    /**
     * Scénario: La fonction est invisible quand le club ne l'a pas activée
     *   Étant donné un club qui n'a pas activé la synchronisation avec Google Agenda
     *   Et un membre autorisé à réserver
     *   Quand il consulte ses réservations
     *   Alors la synchronisation ne lui est pas proposée
     */
    public function test_not_available_when_module_disabled()
    {
        $login = $this->_member();
        $this->CI->config->set_item('agenda_google_sync', FALSE);
        $this->assertFalse($this->sync->available_for($login));
    }

    // -------------------------------------------------------------------------
    // Activation
    // -------------------------------------------------------------------------

    /**
     * Scénario: Le membre relie son agenda Google
     *   Étant donné un membre autorisé à réserver
     *   Quand il accepte l'accès à son agenda sur l'écran de Google
     *   Alors sa synchronisation est active avec son compte Google
     *   Et l'autorisation est conservée sous une forme illisible
     */
    public function test_activation_stores_encrypted_token()
    {
        $login = $this->_member();
        $result = $this->sync->activate($login, 'code-1');

        $this->assertSame('pilote@gmail.com', $result['compte']);
        $row = $this->_row($login);
        $this->assertSame('active', $row['etat']);
        $this->assertSame('pilote@gmail.com', $row['compte_google']);
        $this->assertStringNotContainsString('renouvellement', $row['jeton_renouvellement']);
        $this->assertSame('renouvellement-1', $this->box->decrypt($row['jeton_renouvellement']));
        $this->assertArrayNotHasKey('jeton_renouvellement', $this->sync->status($login), 'l\'état affiché ne contient jamais le jeton');
    }

    /**
     * Scénario: Un refus sur l'écran de Google n'active rien
     *   Étant donné un membre autorisé à réserver
     *   Quand Google refuse la liaison
     *   Alors la synchronisation n'est pas active
     */
    public function test_failed_activation_records_nothing()
    {
        $login = $this->_member();
        $this->client->exchange_error = Google_agenda_exception::REFUS;
        try {
            $this->sync->activate($login, 'code');
            $this->fail('exception attendue');
        } catch (Google_agenda_exception $e) {
            $this->assertSame(Google_agenda_exception::REFUS, $e->kind);
        }
        $this->assertNull($this->sync->status($login));
    }

    /**
     * Scénario: Relier un autre compte Google abandonne le suivi de l'ancien agenda
     *   Étant donné un membre dont la synchronisation a été arrêtée
     *   Et des événements créés dans l'agenda de son ancien compte
     *   Quand il relie un autre compte Google
     *   Alors les événements de l'ancien agenda ne sont plus suivis
     *   Mais une réactivation avec le même compte les aurait conservés
     */
    public function test_reactivation_keeps_links_only_for_same_account()
    {
        $login = $this->_member();
        $this->sync->activate($login, 'c1');
        $id = (int) $this->_row($login)['id'];
        $this->CI->db->insert('agenda_google_evenements', array(
            'liaison_id' => $id, 'reservation_id' => 999999999, 'google_event_id' => 'evt', 'empreinte' => str_repeat('a', 64),
        ));
        $this->sync->stop($login);

        $this->sync->activate($login, 'c2');   // même compte
        $this->assertSame(1, $this->CI->db->where('liaison_id', $id)->count_all_results('agenda_google_evenements'));

        $this->sync->stop($login);
        $this->client->email = 'autre@gmail.com';
        $result = $this->sync->activate($login, 'c3');
        $this->assertTrue($result['changement_compte']);
        $this->assertSame(0, $this->CI->db->where('liaison_id', $id)->count_all_results('agenda_google_evenements'));
        $this->assertSame('autre@gmail.com', $this->_row($login)['compte_google']);
    }

    // -------------------------------------------------------------------------
    // Arrêt
    // -------------------------------------------------------------------------

    /**
     * Scénario: Le membre arrête la synchronisation
     *   Étant donné un membre dont la synchronisation est active
     *   Et des événements déjà créés dans son agenda
     *   Quand il arrête la synchronisation
     *   Alors l'autorisation est retirée chez Google
     *   Et GVV ne la conserve plus
     *   Et les événements déjà créés restent suivis tels quels
     */
    public function test_stop_revokes_and_erases_token_and_keeps_links()
    {
        $login = $this->_member();
        $this->sync->activate($login, 'code');
        $id = (int) $this->_row($login)['id'];
        $this->CI->db->insert('agenda_google_evenements', array(
            'liaison_id' => $id, 'reservation_id' => 999999998, 'google_event_id' => 'evt', 'empreinte' => str_repeat('b', 64),
        ));

        $result = $this->sync->stop($login);

        $this->assertTrue($result['revoque']);
        $this->assertContains(array('revoke', 'renouvellement-1'), $this->client->calls);
        $row = $this->_row($login);
        $this->assertSame('arretee', $row['etat']);
        $this->assertNull($row['jeton_renouvellement']);
        $this->assertSame(1, $this->CI->db->where('liaison_id', $id)->count_all_results('agenda_google_evenements'));
    }

    /**
     * Scénario: L'arrêt efface l'autorisation même si Google ne répond pas
     *   Étant donné un membre dont la synchronisation est active
     *   Et Google injoignable
     *   Quand il arrête la synchronisation
     *   Alors GVV ne conserve plus l'autorisation
     *   Et le membre est prévenu que le retrait chez Google n'est pas confirmé
     */
    public function test_stop_erases_token_even_if_google_unreachable()
    {
        $login = $this->_member();
        $this->sync->activate($login, 'code');
        $this->client->revoke_error = Google_agenda_exception::PASSAGERE;

        $result = $this->sync->stop($login);

        $this->assertFalse($result['revoque']);
        $this->assertNull($this->_row($login)['jeton_renouvellement']);
        $this->assertSame('arretee', $this->_row($login)['etat']);
    }

    /**
     * Scénario: Un membre ne gère que sa propre liaison
     *   Étant donné deux membres ayant chacun relié leur agenda
     *   Quand l'un d'eux arrête sa synchronisation
     *   Alors la synchronisation de l'autre reste active
     *   Et arrêter une synchronisation jamais activée ne change rien
     */
    public function test_member_only_manages_own_liaison()
    {
        $a = $this->_member();
        $b = $this->_member();
        $this->sync->activate($a, 'ca');
        $this->sync->activate($b, 'cb');

        $this->sync->stop($a);

        $this->assertSame('active', $this->_row($b)['etat']);
        $this->assertNull($this->sync->stop($this->_member()));
        $this->assertNull($this->sync->stop($a), 'déjà arrêtée');
    }
}
