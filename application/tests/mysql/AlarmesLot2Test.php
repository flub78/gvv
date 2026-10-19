<?php

use PHPUnit\Framework\TestCase;

/**
 * Alarmes, Lot 2 : alarmes calculées « vols sans cotisation » et « vols sans
 * licence » — migration 178, occurrences et sections, droits du trésorier,
 * alarme propre du pilote, régularisation.
 *
 * Les vols sont créés dans une année sans données réelles (2099). Toutes les
 * données sont créées par le test et supprimées en fin de test.
 *
 * @see application/libraries/Computed_alarm_provider.php
 * @see application/migrations/178_alarmes_lot2.php
 */
class AlarmesLot2Test extends TestCase
{
    /** @var CI_Controller */
    private $CI;
    /** @var Computed_alarm_provider */
    private $provider;

    const YEAR = 2099;
    const PILOT = 'zzlot2pil';
    const TREASURER = 'zzlot2tres';

    private $pla;
    private $avi;
    private $user_ids = array();

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        if (!class_exists('CI_Migration')) {
            require_once BASEPATH . 'libraries/Migration.php';
        }
        require_once APPPATH . 'migrations/178_alarmes_lot2.php';
        $migration = new Migration_Alarmes_lot2();
        $this->assertTrue($migration->up());

        $this->CI->load->model('alarm_definitions_model');
        $this->CI->load->model('alarm_subscriptions_model');
        $this->CI->load->library('Computed_alarm_provider');
        $this->CI->load->library('Notification_manager');
        $this->provider = $this->CI->computed_alarm_provider;
        $this->provider->set_year(self::YEAR);

        foreach (array('PLA' => 'pla', 'AVI' => 'avi') as $acronyme => $property) {
            $row = $this->CI->db->get_where('sections', array('acronyme' => $acronyme))->row_array();
            if (!$row) {
                $this->markTestSkipped("Section $acronyme requise");
            }
            $this->$property = (int) $row['id'];
        }

        $this->cleanup();
        foreach (array(self::PILOT, self::TREASURER) as $login) {
            $this->CI->db->insert('membres', array('mlogin' => $login, 'mnom' => 'Test', 'mprenom' => $login, 'actif' => 1, 'ext' => 0));
            $this->CI->db->insert('users', array('username' => $login, 'password' => 'x',
                'email' => $login . '@example.invalid', 'last_ip' => '127.0.0.1'));
            $this->user_ids[$login] = (int) $this->CI->db->insert_id();
            $this->grant($login, 'user', $this->pla);
            $this->grant($login, 'user', $this->avi);
        }
        $this->grant(self::TREASURER, 'tresorier', $this->avi);

        $this->CI->db->insert('volsp', array('vpdate' => self::YEAR . '-03-01', 'vppilid' => self::PILOT, 'vpmacid' => 'ZZTEST',
            'vpcdeb' => 10, 'vpcfin' => 11, 'vpduree' => 60, 'vpdc' => 0, 'vpcategorie' => 0, 'vpticcolle' => 0));
        $this->CI->db->insert('volsa', array('vadate' => self::YEAR . '-04-01', 'vapilid' => self::PILOT, 'vamacid' => 'ZZTEST',
            'vacdeb' => 0, 'vacfin' => 1, 'vaduree' => 1, 'vadc' => 0, 'vacategorie' => 0, 'vahdeb' => 10, 'vahfin' => 11,
            'club' => $this->avi));
    }

    protected function tearDown(): void
    {
        $this->cleanup();
    }

    private function cleanup()
    {
        $db = $this->CI->db;
        $logins = "'" . self::PILOT . "','" . self::TREASURER . "'";
        $db->query("DELETE FROM volsp WHERE vppilid = '" . self::PILOT . "' AND YEAR(vpdate) = " . self::YEAR);
        $db->query("DELETE FROM volsa WHERE vapilid = '" . self::PILOT . "' AND YEAR(vadate) = " . self::YEAR);
        $db->query("DELETE FROM licences WHERE pilote = '" . self::PILOT . "' AND year = " . self::YEAR);
        $db->query("DELETE urps FROM user_roles_per_section urps JOIN users u ON u.id = urps.user_id WHERE u.username IN ($logins)");
        $db->query("DELETE FROM users WHERE username IN ($logins)");
        $db->query("DELETE FROM membres WHERE mlogin IN ($logins)"); // abonnements en cascade
        $this->CI->alarm_subscriptions_model->reset_roles_cache();
    }

    private function grant($login, $role, $section_id)
    {
        $role_id = $this->CI->db->get_where('types_roles', array('nom' => $role))->row_array()['id'];
        $this->CI->db->insert('user_roles_per_section', array('user_id' => $this->user_ids[$login],
            'types_roles_id' => $role_id, 'section_id' => $section_id));
        $this->CI->alarm_subscriptions_model->reset_roles_cache();
    }

    private function definition($rule)
    {
        $row = $this->CI->db->get_where('alarm_definitions', array('code' => 'computed_' . $rule))->row_array();
        return $this->CI->alarm_definitions_model->get($row['id']);
    }

    /** Occurrences du pilote de test pour une règle, par clé */
    private function items($rule)
    {
        $items = array();
        foreach ($this->provider->collect(array($this->definition($rule))) as $item) {
            if ($item['pilot_login'] === self::PILOT) {
                $items[$item['key']] = $item;
            }
        }
        return $items;
    }

    /** Clés retenues pour un membre (alarmes propres + cases effectives) */
    private function visible($login, $rule)
    {
        $def = $this->definition($rule);
        $cells = $this->CI->alarm_subscriptions_model->effective_for($login);
        $keys = array_keys($this->CI->notification_manager->occurrences_for($login, $cells,
            array($def['id'] => $def), array($def['id'] => array_values($this->items($rule)))));
        sort($keys);
        return $keys;
    }

    // ------------------------------------------------------------------

    public function test_catalogue_et_migration_idempotente(): void
    {
        foreach (array('vols_sans_cotisation', 'vols_sans_licence') as $rule) {
            $def = $this->definition($rule);
            $this->assertEquals('computed', $def['source']);
            $this->assertEquals($rule, $def['rule']);
            $this->assertNotEmpty($def['display_label']);
        }
        $count = $this->CI->db->where('source', 'computed')->count_all_results('alarm_definitions');
        $migration = new Migration_Alarmes_lot2();
        $this->assertTrue($migration->up());
        $this->assertEquals($count, $this->CI->db->where('source', 'computed')->count_all_results('alarm_definitions'));
    }

    public function test_vols_sans_cotisation_et_regularisation(): void
    {
        $key = 'computed:vols_sans_cotisation:' . self::PILOT . ':' . self::YEAR;
        $items = $this->items('vols_sans_cotisation');
        $this->assertArrayHasKey($key, $items);
        $this->assertEquals('active', $items[$key]['state']);
        $this->assertEquals(array($this->pla, $this->avi), $items[$key]['section_ids']);

        $this->CI->db->insert('licences', array('pilote' => self::PILOT, 'type' => 0, 'year' => self::YEAR,
            'date' => self::YEAR . '-01-10', 'comment' => 'zz test'));
        $this->assertArrayNotHasKey($key, $this->items('vols_sans_cotisation'), 'Cotisation enregistrée');
    }

    public function test_vols_sans_licence_par_activite(): void
    {
        $prefix = 'computed:vols_sans_licence:' . self::PILOT . ':';
        $items = $this->items('vols_sans_licence');
        $this->assertEquals(array($this->pla), $items[$prefix . '1:' . self::YEAR]['section_ids'], 'Planeur');
        $this->assertEquals(array($this->avi), $items[$prefix . '2:' . self::YEAR]['section_ids'], 'Avion');

        $this->CI->db->insert('licences', array('pilote' => self::PILOT, 'type' => 2, 'year' => self::YEAR,
            'date' => self::YEAR . '-01-10', 'comment' => 'zz test'));
        $this->assertEquals(array($prefix . '1:' . self::YEAR), array_keys($this->items('vols_sans_licence')));
    }

    public function test_tresorier_de_la_section_et_alarme_propre(): void
    {
        $model = $this->CI->alarm_subscriptions_model;
        $def = $this->definition('vols_sans_licence');
        $this->assertEquals(array($this->avi => true), $model->allowed_cells(self::TREASURER, $def), 'Trésorier avion');
        $this->assertEmpty($model->allowed_cells(self::PILOT, $def), 'Simple membre');
        $document = $this->CI->db->get_where('alarm_definitions', array('source' => 'missing_document'))->row_array();
        $this->assertEmpty($model->allowed_cells(self::TREASURER, $this->CI->alarm_definitions_model->get($document['id'])),
            'Le trésorier ne supervise pas les documents');

        // Le pilote voit ses alarmes ; le trésorier abonné à la colonne Avion ne voit que les vols avion
        $this->assertCount(2, $this->visible(self::PILOT, 'vols_sans_licence'));
        $this->assertEquals(array(), $this->visible(self::TREASURER, 'vols_sans_licence'), 'Non abonné');
        $this->assertTrue($model->subscribe($def['id'], self::TREASURER, $this->avi)['ok']);
        $this->assertEquals(array('computed:vols_sans_licence:' . self::PILOT . ':2:' . self::YEAR),
            $this->visible(self::TREASURER, 'vols_sans_licence'));
    }

    public function test_liens_pour_le_pilote_et_le_superviseur(): void
    {
        $cotisation = array_values($this->items('vols_sans_cotisation'))[0] + array('own' => true);
        $this->assertEquals('paiements_en_ligne/cotisation', Notification_manager::item_link($cotisation, true));
        $this->assertEquals('licences/vols_sans_cotisation/' . self::YEAR, Notification_manager::item_link($cotisation, false));

        $licence = array_values($this->items('vols_sans_licence'))[0] + array('own' => true);
        $this->assertSame('', Notification_manager::item_link($licence, true), 'Licence : rien à ouvrir pour le pilote');
        $this->assertEquals('licences/per_year', Notification_manager::item_link($licence, false));
    }

    public function test_down_puis_up(): void
    {
        $codes = array('computed_vols_sans_cotisation', 'computed_vols_sans_licence');
        $ids = array_column($this->CI->db->where_in('code', $codes)->get('alarm_definitions')->result_array(), 'id');
        if ($this->CI->db->where_in('alarm_definition_id', $ids)->count_all_results('alarm_subscriptions')) {
            $this->markTestSkipped('Des abonnements réels existent : aller-retour down/up non exécuté');
        }
        $migration = new Migration_Alarmes_lot2();
        $this->assertTrue($migration->down());
        $this->assertEquals(0, $this->CI->db->where_in('code', $codes)->count_all_results('alarm_definitions'));
        $this->assertTrue($migration->up());
        $this->assertEquals(2, $this->CI->db->where_in('code', $codes)->count_all_results('alarm_definitions'));
    }
}
