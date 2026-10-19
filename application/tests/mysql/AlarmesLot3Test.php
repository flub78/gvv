<?php

use PHPUnit\Framework\TestCase;

/**
 * Alarmes, Lot 3 : emport passager (3 atterrissages CDB en 90 jours),
 * 120 jours sans voler, compte débiteur — migration 179, états à venir et
 * actif, double commande et instructeur, élèves exclus, fenêtre de 24 mois,
 * seuil des relances, droits.
 *
 * Les vols sont datés autour d'une date de référence fictive (2099) ; le
 * compte débiteur utilise des écritures d'hier. Toutes les données sont
 * créées par le test et supprimées en fin de test.
 *
 * @see application/libraries/Computed_alarm_provider.php
 * @see application/migrations/179_alarmes_lot3.php
 */
class AlarmesLot3Test extends TestCase
{
    /** @var CI_Controller */
    private $CI;
    /** @var Computed_alarm_provider */
    private $provider;

    const TODAY = '2099-06-30';
    const PILOT = 'zzlot3pil';
    const STUDENT = 'zzlot3ele';
    const CA = 'zzlot3ca';
    const TREASURER = 'zzlot3tre';

    private $pla;
    private $avi;
    private $user_ids = array();
    /** @var array Activation des alarmes testées avant le test (réglage du catalogue), restaurée ensuite */
    private $saved_active = array();
    private $compte_ids = array();

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        if (!class_exists('CI_Migration')) {
            require_once BASEPATH . 'libraries/Migration.php';
        }
        require_once APPPATH . 'migrations/179_alarmes_lot3.php';
        $migration = new Migration_Alarmes_lot3();
        $this->assertTrue($migration->up());

        $this->CI->load->model('alarm_definitions_model');
        $this->CI->load->model('alarm_subscriptions_model');
        $this->CI->load->library('Computed_alarm_provider');
        $this->provider = $this->CI->computed_alarm_provider;
        $this->provider->set_today(self::TODAY);

        foreach (array('PLA' => 'pla', 'AVI' => 'avi') as $acronyme => $property) {
            $row = $this->CI->db->get_where('sections', array('acronyme' => $acronyme))->row_array();
            if (!$row) {
                $this->markTestSkipped("Section $acronyme requise");
            }
            $this->$property = (int) $row['id'];
        }

        // Les alarmes testées sont actives pendant le test, quel que soit le réglage du catalogue
        foreach ($this->CI->db->select('code, active')->where_in('code', array('computed_emport_passager', 'computed_inactivite', 'computed_remorquages', 'computed_compte_debiteur', 'computed_vols_sans_cotisation'))
                     ->get('alarm_definitions')->result_array() as $row) {
            $this->saved_active[$row['code']] = $row['active'];
        }
        $this->CI->db->where_in('code', array_keys($this->saved_active))->update('alarm_definitions', array('active' => 1));

        $this->cleanup();
        foreach (array(self::PILOT, self::STUDENT, self::CA, self::TREASURER) as $login) {
            $this->CI->db->insert('membres', array('mlogin' => $login, 'mnom' => 'Test', 'mprenom' => $login, 'actif' => 1, 'ext' => 0));
            $this->CI->db->insert('users', array('username' => $login, 'password' => 'x',
                'email' => $login . '@example.invalid', 'last_ip' => '127.0.0.1'));
            $this->user_ids[$login] = (int) $this->CI->db->insert_id();
            $this->grant($login, 'user', $this->pla);
            $this->grant($login, 'user', $this->avi);
        }
        $this->grant(self::STUDENT, 'eleve', $this->pla);
        $this->grant(self::CA, 'ca', $this->pla);
        $this->grant(self::TREASURER, 'tresorier', $this->pla);
    }

    protected function tearDown(): void
    {
        $this->cleanup();
        foreach ($this->saved_active as $code => $active) {
            $this->CI->db->where('code', $code)->update('alarm_definitions', array('active' => $active));
        }
        $this->saved_active = array();
    }

    private function cleanup()
    {
        $db = $this->CI->db;
        $list = "'" . implode("','", array(self::PILOT, self::STUDENT, self::CA, self::TREASURER)) . "'";
        $db->query("DELETE FROM volsp WHERE vpmacid = 'ZZLOT3'");
        $db->query("DELETE FROM volsa WHERE vamacid = 'ZZLOT3'");
        $db->query("DELETE FROM ecritures WHERE description = 'zz test lot 3'");
        $db->query("DELETE FROM comptes WHERE nom LIKE 'zz test lot 3%'");
        $db->query("DELETE urps FROM user_roles_per_section urps JOIN users u ON u.id = urps.user_id WHERE u.username IN ($list)");
        $db->query("DELETE FROM users WHERE username IN ($list)");
        $db->query("DELETE FROM membres WHERE mlogin IN ($list)");
        $this->CI->alarm_subscriptions_model->reset_roles_cache();
    }

    private function grant($login, $role, $section_id)
    {
        $role_id = $this->CI->db->get_where('types_roles', array('nom' => $role))->row_array()['id'];
        $this->CI->db->insert('user_roles_per_section', array('user_id' => $this->user_ids[$login],
            'types_roles_id' => $role_id, 'section_id' => $section_id));
        $this->CI->alarm_subscriptions_model->reset_roles_cache();
    }

    private function day($offset)
    {
        return date('Y-m-d', strtotime(self::TODAY . ' ' . ($offset >= 0 ? '+' : '') . $offset . ' days'));
    }

    private function glider($pilot, $offset, $dc = 0, $instructor = '', $tow_pilot = '')
    {
        $this->CI->db->insert('volsp', array('vpdate' => $this->day($offset), 'vppilid' => $pilot, 'vpmacid' => 'ZZLOT3',
            'vpcdeb' => 10, 'vpcfin' => 11, 'vpduree' => 60, 'vpdc' => $dc, 'vpinst' => $instructor,
            'vpcategorie' => 0, 'vpticcolle' => 0, 'pilote_remorqueur' => $tow_pilot));
    }

    /** Remorqués par le pilote, à des dates données (décalages en jours) */
    private function tows($offsets)
    {
        foreach ($offsets as $offset) {
            $this->glider(self::STUDENT, $offset, 0, '', self::PILOT);
        }
    }

    private function aeroplane($pilot, $offset, $landings)
    {
        $this->CI->db->insert('volsa', array('vadate' => $this->day($offset), 'vapilid' => $pilot, 'vamacid' => 'ZZLOT3',
            'vacdeb' => 0, 'vacfin' => 1, 'vaduree' => 1, 'vadc' => 0, 'vacategorie' => 0, 'vahdeb' => 10, 'vahfin' => 11,
            'vaatt' => $landings, 'club' => $this->avi));
    }

    private function definition($rule)
    {
        $row = $this->CI->db->get_where('alarm_definitions', array('code' => 'computed_' . $rule))->row_array();
        return $this->CI->alarm_definitions_model->get($row['id']);
    }

    /** Occurrence d'une règle pour un pilote et une section, ou null */
    private function item($rule, $login, $section_id)
    {
        foreach ($this->provider->collect(array($this->definition($rule))) as $item) {
            if ($item['key'] === "computed:$rule:$login:$section_id") {
                return $item;
            }
        }
        return null;
    }

    // ------------------------------------------------------------------

    public function test_migration_role_et_catalogue(): void
    {
        $this->assertEquals(1, $this->CI->db->where('nom', 'emport_passager')->count_all_results('types_roles'));
        foreach (array('emport_passager' => 15, 'inactivite' => 15, 'remorquages' => 15, 'compte_debiteur' => 0) as $rule => $lead) {
            $def = $this->definition($rule);
            $this->assertEquals('computed', $def['source']);
            $this->assertNotEmpty($def['display_label']);
        }
        $count = $this->CI->db->where('source', 'computed')->count_all_results('alarm_definitions');
        $migration = new Migration_Alarmes_lot3();
        $this->assertTrue($migration->up());
        $this->assertEquals($count, $this->CI->db->where('source', 'computed')->count_all_results('alarm_definitions'));
        $this->assertTrue(Computed_alarm_provider::has_deadline('inactivite'));
        $this->assertFalse(Computed_alarm_provider::has_deadline('compte_debiteur'));
    }

    public function test_emport_passager_fenetre_de_90_jours(): void
    {
        $this->assertNull($this->item('emport_passager', self::PILOT, $this->pla), 'Sans le rôle');
        $this->grant(self::PILOT, 'emport_passager', $this->pla);

        $this->glider(self::PILOT, -10);
        $this->glider(self::PILOT, -20);
        $item = $this->item('emport_passager', self::PILOT, $this->pla);
        $this->assertEquals('active', $item['state'], 'Deux atterrissages seulement');
        $this->assertNull($item['due_date']);

        $this->glider(self::PILOT, -80);     // 3e atterrissage : sort de la fenêtre dans 10 jours
        $item = $this->item('emport_passager', self::PILOT, $this->pla);
        $this->assertEquals('upcoming', $item['state']);
        $this->assertEquals($this->day(10), $item['due_date']);

        $this->glider(self::PILOT, -30);     // 3e atterrissage le plus récent : dans 60 jours
        $this->assertNull($this->item('emport_passager', self::PILOT, $this->pla), 'Rien avant le délai d\'anticipation');
    }

    public function test_emport_passager_double_commande_et_instructeur(): void
    {
        $this->grant(self::PILOT, 'emport_passager', $this->pla);
        $this->glider(self::PILOT, -5);
        $this->glider(self::PILOT, -6, 1, self::CA);          // élève en double commande : ne compte pas
        $this->glider(self::STUDENT, -7, 1, self::PILOT);     // le pilote instructeur : compte
        $this->assertEquals('active', $this->item('emport_passager', self::PILOT, $this->pla)['state'], '2 atterrissages CDB');
        $this->glider(self::STUDENT, -8, 1, self::PILOT);
        $this->assertNull($this->item('emport_passager', self::PILOT, $this->pla), '3 atterrissages CDB');
    }

    public function test_emport_passager_avion_compte_les_atterrissages(): void
    {
        $this->grant(self::PILOT, 'emport_passager', $this->avi);
        $this->aeroplane(self::PILOT, -10, 2);
        $this->assertEquals('active', $this->item('emport_passager', self::PILOT, $this->avi)['state']);
        $this->aeroplane(self::PILOT, -12, 1);
        $this->assertNull($this->item('emport_passager', self::PILOT, $this->avi), '3 atterrissages en deux vols');
        $this->assertNull($this->item('emport_passager', self::PILOT, $this->pla), 'Pas le rôle en planeur');
    }

    public function test_remorquages_10_en_12_mois(): void
    {
        $this->tows(range(-10, -18));        // 9 remorqués
        $this->assertNull($this->item('remorquages', self::PILOT, $this->pla), 'Sans le rôle');
        $this->grant(self::PILOT, 'pilote_rem', $this->pla);
        $item = $this->item('remorquages', self::PILOT, $this->pla);
        $this->assertEquals('active', $item['state'], '9 remorqués seulement');
        $this->assertNull($item['due_date']);
        $this->assertEquals('experience', Alarm_definitions_model::category($this->definition('remorquages')));

        $tenth = (int) ((strtotime(self::TODAY . ' -12 months') - strtotime(self::TODAY)) / 86400) + 10;
        $this->tows(array($tenth));          // 10e remorqué : sort de la fenêtre dans 10 jours
        $item = $this->item('remorquages', self::PILOT, $this->pla);
        $this->assertEquals('upcoming', $item['state']);
        $this->assertEquals($this->day(10), $item['due_date']);

        $this->tows(array(-20));             // 10 remorqués récents
        $this->assertNull($this->item('remorquages', self::PILOT, $this->pla));
    }

    public function test_remorquages_pilotes_exterieurs_et_inactifs_exclus(): void
    {
        $this->CI->load->library('Alarm_aggregator');
        $this->CI->computed_alarm_provider->set_today(self::TODAY);
        $def = $this->definition('remorquages');
        $key = 'computed:remorquages:' . self::PILOT . ':' . $this->pla;
        $keys = function () use ($def) {
            return array_column($this->CI->alarm_aggregator->collect(array($def)), 'key');
        };
        $this->grant(self::PILOT, 'pilote_rem', $this->pla);
        $this->tows(range(-10, -12));        // 3 remorqués : alarme active
        $this->assertContains($key, $keys());

        // Pilote extérieur : exclu
        $this->CI->db->where('mlogin', self::PILOT)->update('membres', array('ext' => 1));
        $this->assertNotContains($key, $keys(), 'Pilote extérieur');
        $this->CI->db->where('mlogin', self::PILOT)->update('membres', array('ext' => 0));
        $this->assertContains($key, $keys());

        // Plus actif en section planeur : exclu, même avec le rôle pilote remorqueur
        $user_role = $this->CI->db->get_where('types_roles', array('nom' => 'user'))->row_array()['id'];
        $this->CI->db->where('user_id', $this->user_ids[self::PILOT])->where('types_roles_id', $user_role)
            ->where('section_id', $this->pla)->update('user_roles_per_section', array('revoked_at' => date('Y-m-d H:i:s')));
        $this->CI->alarm_subscriptions_model->reset_roles_cache();
        $this->assertNotContains($key, $keys(), 'Non actif en section planeur');
    }

    public function test_remorquages_seulement_en_section_planeur(): void
    {
        $this->grant(self::PILOT, 'pilote_rem', $this->avi);
        $this->assertNull($this->item('remorquages', self::PILOT, $this->avi), 'Pas de remorqués enregistrés hors section planeur');
    }

    public function test_inactivite_120_jours(): void
    {
        $this->glider(self::PILOT, -125);
        $item = $this->item('inactivite', self::PILOT, $this->pla);
        $this->assertEquals('active', $item['state']);
        $this->assertEquals($this->day(-5), $item['due_date']);
        $this->assertNull($this->item('inactivite', self::PILOT, $this->avi), 'Jamais volé en avion');

        $this->glider(self::PILOT, -110, 1, self::CA);   // vol avec instructeur, dernier vol il y a 110 jours
        $item = $this->item('inactivite', self::PILOT, $this->pla);
        $this->assertEquals('upcoming', $item['state'], 'Échéance dans 10 jours');

        $this->glider(self::PILOT, -50);
        $this->assertNull($this->item('inactivite', self::PILOT, $this->pla));
    }

    public function test_inactivite_eleves_et_fenetre_de_24_mois(): void
    {
        $this->glider(self::STUDENT, -200);
        $this->assertNull($this->item('inactivite', self::STUDENT, $this->pla), 'Élève');

        $this->glider(self::PILOT, -800);
        $this->assertNull($this->item('inactivite', self::PILOT, $this->pla), 'Dernier vol il y a plus de 24 mois');
    }

    public function test_compte_debiteur_au_dela_du_seuil(): void
    {
        $this->CI->load->model('configuration_model');
        $threshold = (float) ($this->CI->configuration_model->get_param('relances.seuil_alarme') ?: 300);
        $this->CI->db->insert('comptes', array('nom' => 'zz test lot 3 pilote', 'codec' => '411', 'pilote' => self::PILOT,
            'club' => $this->pla, 'actif' => 1, 'masked' => 0));
        $pilot_account = (int) $this->CI->db->insert_id();
        $this->CI->db->insert('comptes', array('nom' => 'zz test lot 3 produit', 'codec' => '706', 'club' => $this->pla,
            'actif' => 1, 'masked' => 0));
        $product = (int) $this->CI->db->insert_id();
        $entry = function ($amount) use ($pilot_account, $product) {
            $this->CI->db->insert('ecritures', array('annee_exercise' => (int) date('Y'), 'date_creation' => date('Y-m-d'),
                'date_op' => date('Y-m-d', strtotime('-1 day')), 'compte1' => $pilot_account, 'compte2' => $product,
                'montant' => $amount, 'description' => 'zz test lot 3', 'saisie_par' => 'zztest', 'club' => $this->pla));
        };

        $entry($threshold / 2);
        $this->assertNull($this->item('compte_debiteur', self::PILOT, $this->pla), 'Sous le seuil');
        $entry($threshold);
        $item = $this->item('compte_debiteur', self::PILOT, $this->pla);
        $this->assertEquals('active', $item['state']);
        $this->assertEquals('compta/mon_compte/' . $this->pla, $item['link_own']);
    }

    public function test_pilotes_exterieurs_et_inactifs_exclus(): void
    {
        $this->CI->load->library('Alarm_aggregator');
        $inactivity = $this->definition('inactivite');
        // Instance utilisée par l'agrégateur, à la date de référence du test
        $this->CI->computed_alarm_provider->set_today(self::TODAY);
        $this->CI->computed_alarm_provider->set_year(2099);
        $cotisation = $this->CI->alarm_definitions_model->get(
            $this->CI->db->get_where('alarm_definitions', array('code' => 'computed_vols_sans_cotisation'))->row_array()['id']);
        $keys = function () use ($inactivity, $cotisation) {
            $result = array();
            foreach ($this->CI->alarm_aggregator->collect(array($inactivity, $cotisation)) as $item) {
                if ($item['pilot_login'] === self::PILOT) {
                    $result[$item['key']] = $item['section_ids'];
                }
            }
            return $result;
        };
        $this->glider(self::PILOT, -125);
        $this->aeroplane(self::PILOT, -125, 1);
        $inactive_pla = 'computed:inactivite:' . self::PILOT . ':' . $this->pla;
        $cotisation_key = 'computed:vols_sans_cotisation:' . self::PILOT . ':2099';
        $this->assertArrayHasKey($inactive_pla, $keys());
        $this->assertEquals(array($this->pla, $this->avi), $keys()[$cotisation_key]);

        // Plus actif en section planeur : seule la section avion reste
        $user_role = $this->CI->db->get_where('types_roles', array('nom' => 'user'))->row_array()['id'];
        $this->CI->db->where('user_id', $this->user_ids[self::PILOT])->where('types_roles_id', $user_role)
            ->where('section_id', $this->pla)->update('user_roles_per_section', array('revoked_at' => date('Y-m-d H:i:s')));
        $this->CI->alarm_subscriptions_model->reset_roles_cache();
        $this->assertArrayNotHasKey($inactive_pla, $keys(), 'Inactif en planeur');
        $this->assertEquals(array($this->avi), $keys()[$cotisation_key], 'Sections restreintes aux sections actives');

        // Pilote extérieur : plus aucune alarme
        $this->CI->db->where('mlogin', self::PILOT)->update('membres', array('ext' => 1));
        $this->assertEquals(array(), $keys());
    }

    public function test_categories_d_alarmes(): void
    {
        $categories = array();
        foreach ($this->CI->alarm_definitions_model->get_all(false) as $def) {
            $categories[$def['code']] = $def['category'];
        }
        $this->assertEquals('documents', $categories['missing_document']);
        $this->assertEquals('adhesion', $categories['computed_vols_sans_licence']);
        $this->assertEquals('comptes', $categories['computed_compte_debiteur']);
        $this->assertEquals('experience', $categories['computed_inactivite']);
        $this->assertEquals('experience', $categories['computed_emport_passager']);

        $sorted = Alarm_definitions_model::sort_by_category($this->CI->alarm_definitions_model->get_all(false));
        $order = array_flip(Alarm_definitions_model::categories());
        $previous = -1;
        foreach ($sorted as $def) {
            $this->assertGreaterThanOrEqual($previous, $order[$def['category']], 'Ordre : documents, cotisation et licence, comptes, expérience');
            $previous = $order[$def['category']];
        }
    }

    public function test_droits_par_regle(): void
    {
        $model = $this->CI->alarm_subscriptions_model;
        $debt = $this->definition('compte_debiteur');
        $this->assertEmpty($model->allowed_cells(self::CA, $debt), 'CA : pas de données financières personnelles');
        $this->assertEquals(array($this->pla => true), $model->allowed_cells(self::TREASURER, $debt), 'Trésorier');

        $inactivity = $this->definition('inactivite');
        $this->assertEquals(array($this->pla => true), $model->allowed_cells(self::CA, $inactivity));
        $this->assertEquals(array($this->pla => true), $model->allowed_cells(self::CA, $this->definition('remorquages')));
        $this->assertEmpty($model->allowed_cells(self::TREASURER, $inactivity));
        $this->grant(self::PILOT, 'instructeur', $this->avi);
        $this->assertEquals(array($this->avi => true), $model->allowed_cells(self::PILOT, $inactivity), 'Instructeur');
    }
}
