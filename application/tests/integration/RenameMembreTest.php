<?php

require_once(__DIR__ . '/TransactionalTestCase.php');

/**
 * Tests d'intégration pour le renommage de l'identifiant membre (mlogin).
 *
 * Chaque test s'exécute dans une transaction annulée en tearDown() →
 * la base est toujours restaurée à son état initial.
 *
 * Données de test créées dynamiquement dans chaque test pour rester
 * indépendants de l'état réel de la DB.
 */
class RenameMembreTest extends TransactionalTestCase
{
    /** @var Membres_model */
    private $model;

    // Compteur pour générer des logins uniques
    private static $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->CI->load->model('membres_model');
        $this->model = $this->CI->membres_model;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function _unique_login(string $prefix = 'test'): string
    {
        return $prefix . '_' . getmypid() . '_' . (++self::$seq);
    }

    /**
     * Insère un membre minimal en base et retourne son mlogin.
     */
    private function _create_membre(array $overrides = []): string
    {
        $login = $this->_unique_login($overrides['mlogin_prefix'] ?? 'tm');
        unset($overrides['mlogin_prefix']);

        $defaults = [
            'mlogin'  => $login,
            'mnom'    => 'Nom_' . $login,
            'mprenom' => 'Prenom_' . $login,
            'memail'  => $login . '@test.invalid',
            'msexe'   => 'M',
            'actif'   => 1,
            'ext'     => 0,
            'm25ans'  => 0,
        ];
        $data = array_merge($defaults, $overrides);
        $this->CI->db->insert('membres', $data);
        return $login;
    }

    /**
     * Crée un vol planeur pour un pilote donné.
     */
    private function _create_vol_planeur(string $pilot_login, array $overrides = []): int
    {
        $defaults = [
            'vppilid'     => $pilot_login,
            'vpdate'      => date('Y-m-d'),
            'vpmacid'     => 'F-XXXX',
            'vpcdeb'      => 10.00,
            'vpcfin'      => 11.00,
            'vpduree'     => 60,
            'vpdc'        => 0,
            'vpcategorie' => 0,
            'vpticcolle'  => 0,
        ];
        $data = array_merge($defaults, $overrides);
        $this->CI->db->insert('volsp', $data);
        return (int) $this->CI->db->insert_id();
    }

    /**
     * Crée un ticket pour un pilote donné.
     */
    private function _create_ticket(string $pilot_login, array $overrides = []): int
    {
        $defaults = [
            'pilote'      => $pilot_login,
            'date'        => date('Y-m-d'),
            'quantite'    => 50.00,
            'description' => 'Test ticket',
            'saisie_par'  => 'phpunit',
            'club'        => 1,
            'type'        => 0,
        ];
        $data = array_merge($defaults, $overrides);
        $this->CI->db->insert('tickets', $data);
        return (int) $this->CI->db->insert_id();
    }

    /**
     * Crée un utilisateur dx_auth pour un membre.
     */
    private function _create_dx_auth_user(string $username, string $password = 'password'): int
    {
        $this->CI->db->insert('users', [
            'username'   => $username,
            'password'   => substr(md5($password), 0, 34),
            'email'      => $username . '@test.invalid',
            'role_id'    => 2,
            'banned'     => 0,
            'last_ip'    => '127.0.0.1',
            'last_login' => '0000-00-00 00:00:00',
            'created'    => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->CI->db->insert_id();
    }

    // -------------------------------------------------------------------------
    // Tests de validation
    // -------------------------------------------------------------------------

    public function test_validate_new_mlogin_empty()
    {
        $result = $this->model->validate_new_mlogin('', 'oldlogin');
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('vide', $result['errors'][0]);
    }

    public function test_validate_new_mlogin_purely_numeric()
    {
        $result = $this->model->validate_new_mlogin('12345', 'oldlogin');
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('uniquement numérique', $result['errors'][0]);
    }

    public function test_validate_new_mlogin_invalid_characters()
    {
        $result = $this->model->validate_new_mlogin('login@invalid', 'oldlogin');
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    public function test_validate_new_mlogin_already_exists()
    {
        $existing = $this->_create_membre();
        $result = $this->model->validate_new_mlogin($existing, 'oldlogin');
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('existe déjà', $result['errors'][0]);
    }

    public function test_validate_new_mlogin_valid()
    {
        $new_login = $this->_unique_login('valid');
        $result = $this->model->validate_new_mlogin($new_login, 'oldlogin');
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    // -------------------------------------------------------------------------
    // Tests de prévisualisation
    // -------------------------------------------------------------------------

    public function test_preview_rename_member_not_found()
    {
        $preview = $this->model->preview_rename('nonexistent', 'newlogin');
        $this->assertArrayHasKey('error', $preview);
    }

    public function test_preview_rename_shows_affected_tables()
    {
        $old_login = $this->_create_membre();
        $this->_create_vol_planeur($old_login);
        $this->_create_ticket($old_login);

        $new_login = $this->_unique_login('new');
        $preview = $this->model->preview_rename($old_login, $new_login);

        $this->assertArrayNotHasKey('error', $preview);
        $this->assertEquals($old_login, $preview['old_mlogin']);
        $this->assertEquals($new_login, $preview['new_mlogin']);
        $this->assertNotEmpty($preview['affected_tables']);
        $this->assertGreaterThan(0, $preview['total_records']);

        // Verify volsp and tickets are in affected tables
        $table_names = array_column($preview['affected_tables'], 'table');
        $this->assertContains('volsp', $table_names);
        $this->assertContains('tickets', $table_names);
    }

    public function test_preview_rename_detects_dx_auth_account()
    {
        $old_login = $this->_create_membre();
        $this->_create_dx_auth_user($old_login);

        $new_login = $this->_unique_login('new');
        $preview = $this->model->preview_rename($old_login, $new_login);

        $this->assertTrue($preview['dx_auth_exists']);
    }

    // -------------------------------------------------------------------------
    // Tests d'exécution du renommage
    // -------------------------------------------------------------------------

    public function test_execute_rename_success()
    {
        $old_login = $this->_create_membre();
        $this->_create_vol_planeur($old_login);
        $this->_create_ticket($old_login);

        $new_login = $this->_unique_login('renamed');
        $result = $this->model->execute_rename($old_login, $new_login, 'test_user');

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['total_updated']);

        // Verify old login no longer exists in membres
        $count = $this->CI->db->where('mlogin', $old_login)->count_all_results('membres');
        $this->assertEquals(0, $count);

        // Verify new login exists in membres
        $count = $this->CI->db->where('mlogin', $new_login)->count_all_results('membres');
        $this->assertEquals(1, $count);

        // Verify volsp updated
        $count = $this->CI->db->where('vppilid', $new_login)->count_all_results('volsp');
        $this->assertGreaterThan(0, $count);

        // Verify tickets updated
        $count = $this->CI->db->where('pilote', $new_login)->count_all_results('tickets');
        $this->assertGreaterThan(0, $count);
    }

    public function test_execute_rename_updates_dx_auth()
    {
        $old_login = $this->_create_membre();
        $this->_create_dx_auth_user($old_login);

        $new_login = $this->_unique_login('renamed');
        $result = $this->model->execute_rename($old_login, $new_login, 'test_user');

        $this->assertTrue($result['success']);

        // Verify old username no longer exists
        $count = $this->CI->db->where('username', $old_login)->count_all_results('users');
        $this->assertEquals(0, $count);

        // Verify new username exists
        $count = $this->CI->db->where('username', $new_login)->count_all_results('users');
        $this->assertEquals(1, $count);
    }

    public function test_execute_rename_exhaustive()
    {
        // Create member with references in multiple tables
        $old_login = $this->_create_membre();

        // Create references in various tables
        $this->_create_vol_planeur($old_login);
        $this->_create_ticket($old_login);
        $this->_create_dx_auth_user($old_login);

        // Add event
        $this->CI->db->insert('events', [
            'emlogin' => $old_login,
            'etype' => 1,
            'edate' => date('Y-m-d'),
        ]);

        $new_login = $this->_unique_login('renamed');
        $result = $this->model->execute_rename($old_login, $new_login, 'test_user');

        $this->assertTrue($result['success']);

        // Verify NO traces of old login remain in database
        // This is the exhaustive check
        $tables_to_check = [
            'membres' => 'mlogin',
            'events' => 'emlogin',
            'volsp' => 'vppilid',
            'tickets' => 'pilote',
            'users' => 'username',
        ];

        foreach ($tables_to_check as $table => $column) {
            $count = $this->CI->db->where($column, $old_login)->count_all_results($table);
            $this->assertEquals(0, $count, "Old login '$old_login' still found in $table.$column");
        }

        // Verify new login exists where expected
        $this->assertEquals(1, $this->CI->db->where('mlogin', $new_login)->count_all_results('membres'));
        $this->assertGreaterThan(0, $this->CI->db->where('emlogin', $new_login)->count_all_results('events'));
        $this->assertGreaterThan(0, $this->CI->db->where('vppilid', $new_login)->count_all_results('volsp'));
        $this->assertGreaterThan(0, $this->CI->db->where('pilote', $new_login)->count_all_results('tickets'));
        $this->assertEquals(1, $this->CI->db->where('username', $new_login)->count_all_results('users'));
    }

    public function test_execute_rename_atomicity_on_error()
    {
        $old_login = $this->_create_membre();
        $this->_create_vol_planeur($old_login);

        // Create a conflicting member with the target name to force a constraint violation
        $conflicting_login = $this->_unique_login('conflict');
        $this->_create_membre(['mlogin' => $conflicting_login]);

        // Try to rename to the conflicting login - should fail
        $result = $this->model->execute_rename($old_login, $conflicting_login, 'test_user');

        $this->assertFalse($result['success']);

        // Verify old login still exists (rollback occurred)
        $count = $this->CI->db->where('mlogin', $old_login)->count_all_results('membres');
        $this->assertEquals(1, $count);

        // Verify volsp still references old login (rollback occurred)
        $count = $this->CI->db->where('vppilid', $old_login)->count_all_results('volsp');
        $this->assertGreaterThan(0, $count);
    }

    public function test_execute_rename_preserves_business_data()
    {
        $old_login = $this->_create_membre([
            'mnom' => 'TestNom',
            'mprenom' => 'TestPrenom',
            'memail' => 'test@example.com',
        ]);

        $this->_create_vol_planeur($old_login, [
            'vpdate' => '2025-01-15',
            'vpobs'  => 'test-obs',
        ]);

        $ticket_prix = 123.45;
        $this->_create_ticket($old_login, ['quantite' => $ticket_prix]);

        $new_login = $this->_unique_login('renamed');
        $result = $this->model->execute_rename($old_login, $new_login, 'test_user');

        $this->assertTrue($result['success']);

        // Verify member data unchanged (except mlogin)
        $membre = $this->CI->db->where('mlogin', $new_login)->get('membres')->row_array();
        $this->assertEquals('TestNom', $membre['mnom']);
        $this->assertEquals('TestPrenom', $membre['mprenom']);
        $this->assertEquals('test@example.com', $membre['memail']);

        // Verify vol data unchanged (except mlogin reference)
        $vol = $this->CI->db->where('vppilid', $new_login)->get('volsp')->row_array();
        $this->assertEquals('2025-01-15', $vol['vpdate']);
        $this->assertEquals('test-obs', $vol['vpobs']);

        // Verify ticket quantity unchanged
        $ticket = $this->CI->db->where('pilote', $new_login)->get('tickets')->row_array();
        $this->assertEquals($ticket_prix, (float)$ticket['quantite']);
    }
}
