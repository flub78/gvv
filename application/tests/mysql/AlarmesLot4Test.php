<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'libraries/Notification_manager.php';

/**
 * Notification_manager limité aux membres du test, emails en mémoire
 */
class Lot4_notification_manager extends Notification_manager {
    public $logins = array();
    public $emails = array();

    protected function members_to_notify() {
        return array_intersect_key(parent::members_to_notify(), array_flip($this->logins));
    }

    protected function deliver_email($email, $subject, $body) {
        $this->emails[] = array('to' => $email, 'subject' => $subject, 'body' => $body);
        return true;
    }

    protected function deliver_sms($phone, $message) {
        return array('ok' => true, 'error' => null);
    }
}

/**
 * Alarmes, Lot 4 : désactivation d'une occurrence pour soi ou pour tout le
 * monde, droits, réactivation automatique (date, état, occurrence disparue)
 * et manuelle, synthèses sans les désactivées, migration 180.
 *
 * Toutes les données sont créées par le test et supprimées en fin de test.
 *
 * @see application/models/alarm_dismissals_model.php
 */
class AlarmesLot4Test extends TestCase
{
    /** @var CI_Controller */
    private $CI;
    /** @var Lot4_notification_manager */
    private $manager;
    /** @var Alarm_dismissals_model */
    private $model;

    const PILOT = 'zzlot4pil';
    const SUPERVISOR = 'zzlot4ca';
    const OTHER = 'zzlot4ca2';
    const TYPE_CODE = 'zztest_lot4';
    const MONDAY = '2026-10-19';

    private $section_id;
    private $type_id;
    private $user_ids = array();
    private $doc_ids = array();

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        $this->CI->load->model('alarm_definitions_model');
        $this->CI->load->model('alarm_subscriptions_model');
        $this->CI->load->model('alarm_member_settings_model');
        $this->CI->load->model('alarm_dismissals_model');
        $this->model = $this->CI->alarm_dismissals_model;
        $this->model->set_today(date('Y-m-d'));

        $this->section_id = (int) $this->CI->db->query("SELECT id FROM sections ORDER BY id LIMIT 1")->row_array()['id'];
        $this->cleanup();

        foreach (array(self::PILOT, self::SUPERVISOR, self::OTHER) as $login) {
            $this->CI->db->insert('membres', array('mlogin' => $login, 'mnom' => 'Test', 'mprenom' => $login,
                'actif' => 1, 'memail' => $login . '@example.invalid'));
            $this->CI->db->insert('users', array('username' => $login, 'password' => 'x',
                'email' => $login . '@example.invalid', 'last_ip' => '127.0.0.1'));
            $this->user_ids[$login] = (int) $this->CI->db->insert_id();
            $this->grant($login, 'user');
        }
        $this->grant(self::SUPERVISOR, 'ca');
        $this->grant(self::OTHER, 'ca');

        $this->CI->db->insert('document_types', array('code' => self::TYPE_CODE, 'name' => 'Type désactivation test',
            'scope' => 'pilot', 'required' => 0, 'has_expiration' => 1, 'alert_days_before' => 30, 'active' => 1));
        $this->type_id = (int) $this->CI->db->insert_id();
        $this->CI->alarm_definitions_model->sync_document_types();
        $this->CI->alarm_subscriptions_model->reset_roles_cache();

        $def_id = (int) $this->CI->db->get_where('alarm_definitions', array('code' => 'document_' . $this->type_id))->row_array()['id'];
        foreach (array(self::SUPERVISOR, self::OTHER) as $login) {
            $this->assertTrue($this->CI->alarm_subscriptions_model->subscribe($def_id, $login, $this->section_id)['ok']);
        }

        $this->manager = new Lot4_notification_manager();
        $this->manager->logins = array(self::PILOT, self::SUPERVISOR, self::OTHER);
    }

    protected function tearDown(): void
    {
        $this->cleanup();
    }

    private function cleanup()
    {
        $db = $this->CI->db;
        $logins = "'" . self::PILOT . "','" . self::SUPERVISOR . "','" . self::OTHER . "'";
        foreach ($this->doc_ids as $id) {
            $db->where('id', $id)->delete('archived_documents');
        }
        $this->doc_ids = array();
        $db->query("DELETE FROM notification_history WHERE recipient_login IN ($logins)");
        $db->query("DELETE FROM motd_messages WHERE source_type = 'alarm' AND target_user_login IN ($logins)");
        $db->query("DELETE urps FROM user_roles_per_section urps JOIN users u ON u.id = urps.user_id WHERE u.username IN ($logins)");
        $db->query("DELETE FROM users WHERE username IN ($logins)");
        // Alarme du type, abonnements et désactivations en cascade
        $db->query("DELETE FROM document_types WHERE code = '" . self::TYPE_CODE . "'");
        $db->query("DELETE FROM membres WHERE mlogin IN ($logins)");
        $this->CI->alarm_subscriptions_model->reset_roles_cache();
    }

    private function grant($login, $role)
    {
        $role_id = $this->CI->db->get_where('types_roles', array('nom' => $role))->row_array()['id'];
        $this->CI->db->insert('user_roles_per_section', array('user_id' => $this->user_ids[$login],
            'types_roles_id' => $role_id, 'section_id' => $this->section_id));
        $this->CI->alarm_subscriptions_model->reset_roles_cache();
    }

    private function addDocument($pilot, $days_from_now)
    {
        $this->CI->db->insert('archived_documents', array(
            'document_type_id' => $this->type_id, 'pilot_login' => $pilot,
            'file_path' => 'uploads/documents/test/zzlot4.pdf', 'original_filename' => 'zzlot4.pdf',
            'uploaded_by' => $pilot, 'uploaded_at' => date('Y-m-d H:i:s'),
            'valid_until' => $this->daysFromNow($days_from_now),
            'alarm_disabled' => 0, 'is_current_version' => 1, 'validation_status' => 'approved',
        ));
        $this->doc_ids[] = (int) $this->CI->db->insert_id();
        return 'document:' . end($this->doc_ids);
    }

    private function daysFromNow($days)
    {
        return date('Y-m-d', strtotime(($days >= 0 ? '+' : '') . $days . ' days'));
    }

    private function occurrences($login)
    {
        return $this->manager->member_occurrences($login);
    }

    private function dismissal($key)
    {
        return $this->CI->db->where('alarm_key', $key)->order_by('id', 'desc')->get('alarm_dismissals')->row_array();
    }

    // ------------------------------------------------------------------

    public function test_desactivation_pour_soi(): void
    {
        $key = $this->addDocument(self::PILOT, -3);
        $item = $this->occurrences(self::SUPERVISOR)[$key];

        $result = $this->model->dismiss($item, self::SUPERVISOR, null, '');
        $this->assertTrue($result['ok'], (string) $result['error']);

        $this->assertArrayNotHasKey($key, $this->occurrences(self::SUPERVISOR), 'Masquée pour le superviseur');
        $this->assertArrayHasKey($key, $this->occurrences(self::OTHER), 'Un autre abonné la voit');
        $this->assertArrayHasKey($key, $this->occurrences(self::PILOT), 'Le pilote la voit');

        $dismissed = array();
        list($definitions, $items_by_def) = $this->manager->collect_items();
        $this->manager->occurrences_for(self::SUPERVISOR,
            $this->CI->alarm_subscriptions_model->effective_for(self::SUPERVISOR), $definitions, $items_by_def, $dismissed);
        $this->assertArrayHasKey($key, $dismissed, 'Rendue à part pour l\'affichage des désactivées');
        $this->assertEquals(self::SUPERVISOR, $dismissed[$key]['dismissal']['member_login']);
    }

    public function test_alarme_propre_non_desactivable_pour_soi(): void
    {
        $key = $this->addDocument(self::PILOT, -3);
        $item = $this->occurrences(self::PILOT)[$key];
        $this->assertTrue($item['own']);

        $result = $this->model->dismiss($item, self::PILOT, null, 'non');
        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey($key, $this->occurrences(self::PILOT));
    }

    public function test_desactivation_pour_tout_le_monde(): void
    {
        $key = $this->addDocument(self::PILOT, -3);
        $item = $this->occurrences(self::SUPERVISOR)[$key];

        $this->assertFalse($this->model->dismiss($item, null, null, '  ')['ok'], 'Motif obligatoire');
        $this->assertTrue($this->model->dismiss($item, null, null, 'Licencié ailleurs')['ok']);

        foreach (array(self::PILOT, self::SUPERVISOR, self::OTHER) as $login) {
            $this->assertArrayNotHasKey($key, $this->occurrences($login), "Masquée pour $login");
        }
        $this->assertArrayHasKey($key, $this->model->global_keys(array($item)));
    }

    public function test_date_de_fin(): void
    {
        $key = $this->addDocument(self::PILOT, -3);
        $item = $this->occurrences(self::SUPERVISOR)[$key];

        $this->assertFalse($this->model->dismiss($item, self::SUPERVISOR, $this->daysFromNow(-1), '')['ok'], 'Date passée refusée');
        $this->assertTrue($this->model->dismiss($item, self::SUPERVISOR, $this->daysFromNow(5), '')['ok']);
        $this->assertArrayNotHasKey($key, $this->occurrences(self::SUPERVISOR));

        $this->manager->set_today($this->daysFromNow(5));
        $this->assertArrayNotHasKey($key, $this->occurrences(self::SUPERVISOR), 'Encore désactivée le jour de fin');

        $this->manager->set_today($this->daysFromNow(6));
        $this->assertArrayHasKey($key, $this->occurrences(self::SUPERVISOR), 'Réapparaît le lendemain');
        $row = $this->dismissal($key);
        $this->assertNotNull($row['reactivated_at']);
        $this->assertNull($row['reactivated_by'], 'Réactivation automatique');
    }

    public function test_reactivation_au_changement_d_etat(): void
    {
        $key = $this->addDocument(self::PILOT, 10);
        $item = $this->occurrences(self::SUPERVISOR)[$key];
        $this->assertEquals('upcoming', $item['state']);
        $this->assertTrue($this->model->dismiss($item, null, null, 'Renouvellement en cours')['ok']);
        $this->assertArrayNotHasKey($key, $this->occurrences(self::SUPERVISOR));

        // Le document expire : l'occurrence devient active et réapparaît
        $this->CI->db->where('id', end($this->doc_ids))->update('archived_documents', array('valid_until' => $this->daysFromNow(-1)));
        $occurrences = $this->occurrences(self::SUPERVISOR);
        $this->assertArrayHasKey($key, $occurrences);
        $this->assertEquals('active', $occurrences[$key]['state']);
        $this->assertNotNull($this->dismissal($key)['reactivated_at']);
    }

    public function test_reactivation_quand_l_occurrence_disparait(): void
    {
        $key = $this->addDocument(self::PILOT, -3);
        $this->assertTrue($this->model->dismiss($this->occurrences(self::SUPERVISOR)[$key], self::SUPERVISOR, null, '')['ok']);

        $this->CI->db->where('id', end($this->doc_ids))->update('archived_documents', array('valid_until' => $this->daysFromNow(400)));
        $this->manager->set_today(self::MONDAY);
        $this->manager->run('test');
        $this->assertNotNull($this->dismissal($key)['reactivated_at'], 'Condition résolue : désactivation close');
    }

    public function test_reactivation_manuelle(): void
    {
        $key = $this->addDocument(self::PILOT, -3);
        $this->assertTrue($this->model->dismiss($this->occurrences(self::SUPERVISOR)[$key], null, null, 'Erreur')['ok']);
        $row = $this->dismissal($key);

        $this->assertTrue($this->model->reactivate($row['id'], 'panoramix'));
        $this->assertFalse($this->model->reactivate($row['id'], 'panoramix'), 'Déjà réactivée');
        $this->assertArrayHasKey($key, $this->occurrences(self::PILOT));
        $this->assertEquals('panoramix', $this->dismissal($key)['reactivated_by']);
    }

    public function test_une_seule_desactivation_en_cours_par_portee(): void
    {
        $key = $this->addDocument(self::PILOT, -3);
        $item = $this->occurrences(self::SUPERVISOR)[$key];
        $this->assertTrue($this->model->dismiss($item, self::SUPERVISOR, null, 'a')['ok']);
        $this->assertTrue($this->model->dismiss($item, self::SUPERVISOR, $this->daysFromNow(3), 'b')['ok']);

        $pending = $this->CI->db->where('alarm_key', $key)->where('reactivated_at IS NULL', null, false)
            ->count_all_results('alarm_dismissals');
        $this->assertEquals(1, $pending);
        $this->assertEquals('b', $this->dismissal($key)['reason']);
    }

    public function test_synthese_sans_les_desactivees(): void
    {
        $key = $this->addDocument(self::PILOT, -3);
        $this->CI->alarm_member_settings_model->save_for(self::SUPERVISOR, true, false, false, 'weekly');
        $this->CI->alarm_member_settings_model->save_for(self::OTHER, true, false, false, 'weekly');
        $this->assertTrue($this->model->dismiss($this->occurrences(self::SUPERVISOR)[$key], self::SUPERVISOR, null, '')['ok']);

        $this->manager->set_today(self::MONDAY);
        $this->manager->run('test');
        $recipients = array_column($this->manager->emails, 'to');
        $this->assertNotContains(self::SUPERVISOR . '@example.invalid', $recipients, 'Rien à envoyer au superviseur');
        $this->assertContains(self::OTHER . '@example.invalid', $recipients, 'L\'autre abonné reçoit sa synthèse');
    }

    public function test_migration_180(): void
    {
        if (!class_exists('CI_Migration')) {
            require_once BASEPATH . 'libraries/Migration.php';
        }
        require_once APPPATH . 'migrations/180_alarmes_lot4.php';
        $migration = new Migration_Alarmes_lot4();
        $this->assertTrue($migration->up(), 'up() idempotent');
        $this->assertTrue($this->CI->db->table_exists('alarm_dismissals'));

        // L'aller-retour supprime la table : seulement sans désactivation réelle
        if ((int) $this->CI->db->query('SELECT COUNT(*) AS n FROM alarm_dismissals')->row_array()['n'] > 0) {
            $this->markTestSkipped('Désactivations existantes : aller-retour down()/up() non exécuté');
        }
        $this->assertTrue($migration->down());
        $this->assertFalse($this->CI->db->table_exists('alarm_dismissals'));
        $this->assertTrue($migration->up());
        $this->assertTrue($this->CI->db->table_exists('alarm_dismissals'));
    }
}
