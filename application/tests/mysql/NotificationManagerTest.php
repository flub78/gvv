<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'libraries/Notification_manager.php';

/**
 * Notification_manager limité aux membres du test, sans envoi réel :
 * les emails et SMS sont enregistrés en mémoire.
 */
class Testable_notification_manager extends Notification_manager {
    public $logins = array();
    public $emails = array();
    public $sms = array();

    protected function members_to_notify() {
        return array_intersect_key(parent::members_to_notify(), array_flip($this->logins));
    }

    protected function deliver_email($email, $subject, $body) {
        $this->emails[] = array('to' => $email, 'subject' => $subject, 'body' => $body);
        return true;
    }

    protected function deliver_sms($phone, $message) {
        $this->sms[] = array('to' => $phone, 'message' => $message);
        return array('ok' => true, 'error' => null);
    }
}

/**
 * Alarmes, Lot 1 : sélection par abonné, fréquences et jours d'envoi,
 * contenu des synthèses, canaux, rappel SMS, idempotence.
 *
 * Toutes les données sont créées par le test et supprimées en fin de test.
 *
 * @see application/libraries/Notification_manager.php
 */
class NotificationManagerTest extends TestCase
{
    /** @var CI_Controller */
    private $CI;
    /** @var Testable_notification_manager */
    private $manager;

    const PILOT = 'zznotifpil';
    const SUPERVISOR = 'zznotifca';
    const TYPE_CODE = 'zztest_notif';

    const MONDAY = '2026-10-19';
    const TUESDAY = '2026-10-20';
    const FIRST_MONDAY = '2026-11-02';
    const SECOND_MONDAY = '2026-11-09';

    private $section_id;
    private $type_id;
    private $user_ids = array();
    private $doc_ids = array();
    private $saved_sms_enabled;

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        $this->CI->load->model('alarm_definitions_model');
        $this->CI->load->model('alarm_subscriptions_model');
        $this->CI->load->model('alarm_member_settings_model');
        $this->CI->config->load('program');
        $this->saved_sms_enabled = $this->CI->config->item('alarm_sms_enabled');
        $this->CI->config->set_item('alarm_sms_enabled', TRUE);

        $this->section_id = (int) $this->CI->db->query("SELECT id FROM sections ORDER BY id LIMIT 1")->row_array()['id'];
        $this->cleanup();

        foreach (array(self::PILOT, self::SUPERVISOR) as $login) {
            $this->CI->db->insert('membres', array('mlogin' => $login, 'mnom' => 'Test', 'mprenom' => $login,
                'actif' => 1, 'memail' => $login . '@example.invalid', 'mtelm' => '0600000000'));
            $this->CI->db->insert('users', array('username' => $login, 'password' => 'x',
                'email' => $login . '@example.invalid', 'last_ip' => '127.0.0.1'));
            $this->user_ids[$login] = (int) $this->CI->db->insert_id();
            $this->grant($login, 'user');
        }
        $this->grant(self::SUPERVISOR, 'ca');

        $this->CI->db->insert('document_types', array('code' => self::TYPE_CODE, 'name' => 'Type notification test',
            'scope' => 'pilot', 'required' => 0, 'has_expiration' => 1, 'alert_days_before' => 30, 'active' => 1));
        $this->type_id = (int) $this->CI->db->insert_id();
        $this->CI->alarm_definitions_model->sync_document_types();
        $this->CI->alarm_subscriptions_model->reset_roles_cache();

        $this->manager = new Testable_notification_manager();
        $this->manager->logins = array(self::PILOT, self::SUPERVISOR);
    }

    protected function tearDown(): void
    {
        $this->cleanup();
        $this->CI->config->set_item('alarm_sms_enabled', $this->saved_sms_enabled);
    }

    private function cleanup()
    {
        $db = $this->CI->db;
        $logins = "'" . self::PILOT . "','" . self::SUPERVISOR . "'";
        foreach ($this->doc_ids as $id) {
            $db->where('id', $id)->delete('archived_documents');
        }
        $this->doc_ids = array();
        $db->query("DELETE FROM notification_history WHERE recipient_login IN ($logins)");
        $db->query("DELETE FROM motd_messages WHERE source_type = 'alarm' AND target_user_login IN ($logins)");
        $db->query("DELETE urps FROM user_roles_per_section urps JOIN users u ON u.id = urps.user_id WHERE u.username IN ($logins)");
        $db->query("DELETE FROM users WHERE username IN ($logins)");
        $db->query("DELETE FROM document_types WHERE code = '" . self::TYPE_CODE . "'");
        $db->query("DELETE FROM membres WHERE mlogin IN ($logins)"); // abonnements et réglages en cascade
        $this->CI->alarm_subscriptions_model->reset_roles_cache();
    }

    private function grant($login, $role)
    {
        $role_id = $this->CI->db->get_where('types_roles', array('nom' => $role))->row_array()['id'];
        $this->CI->db->insert('user_roles_per_section', array('user_id' => $this->user_ids[$login],
            'types_roles_id' => $role_id, 'section_id' => $this->section_id));
        $this->CI->alarm_subscriptions_model->reset_roles_cache();
    }

    private function defId($kind)
    {
        return (int) $this->CI->db->get_where('alarm_definitions',
            array('code' => 'document_' . $this->type_id . '_' . $kind))->row_array()['id'];
    }

    private function addDocument($pilot, $days_from_now)
    {
        $this->CI->db->insert('archived_documents', array(
            'document_type_id' => $this->type_id, 'pilot_login' => $pilot,
            'file_path' => 'uploads/documents/test/zznotif.pdf', 'original_filename' => 'zznotif.pdf',
            'uploaded_by' => $pilot, 'uploaded_at' => date('Y-m-d H:i:s'),
            'valid_until' => date('Y-m-d', strtotime(($days_from_now >= 0 ? '+' : '') . $days_from_now . ' days')),
            'alarm_disabled' => 0, 'is_current_version' => 1, 'validation_status' => 'approved',
        ));
        $this->doc_ids[] = (int) $this->CI->db->insert_id();
        return end($this->doc_ids);
    }

    private function configure($login, $email, $motd, $sms, $frequency, $kinds = array('personal'))
    {
        foreach ($kinds as $kind) {
            $this->assertTrue($this->CI->alarm_subscriptions_model->subscribe($this->defId($kind), $login)['ok']);
        }
        $this->CI->alarm_member_settings_model->save_for($login, $email, $motd, $sms, $frequency);
    }

    private function runOn($date)
    {
        $this->manager->set_today($date);
        return $this->manager->run('test');
    }

    // ------------------------------------------------------------------

    public function test_sans_reglage_rien_n_est_envoye(): void
    {
        $this->addDocument(self::PILOT, 10);
        $this->CI->alarm_subscriptions_model->subscribe($this->defId('personal'), self::PILOT);

        $stats = $this->runOn(self::MONDAY);
        $this->assertEquals(0, $stats['email'] + $stats['motd'] + $stats['sms']);
        $this->assertEmpty($this->manager->emails);
    }

    public function test_synthese_hebdomadaire_le_lundi_une_seule_fois(): void
    {
        $this->addDocument(self::PILOT, 10);
        $this->configure(self::PILOT, true, false, false, 'weekly');

        $this->runOn(self::TUESDAY);
        $this->assertCount(0, $this->manager->emails, 'Pas de synthèse le mardi');

        $this->runOn(self::MONDAY);
        $this->assertCount(1, $this->manager->emails);
        $this->assertStringContainsString('Type notification test', $this->manager->emails[0]['body']);
        $this->assertStringContainsString('archived_documents/view/', $this->manager->emails[0]['body']);

        $this->runOn(self::MONDAY);
        $this->assertCount(1, $this->manager->emails, 'Idempotent le même jour');
    }

    public function test_synthese_mensuelle_le_premier_lundi(): void
    {
        $this->addDocument(self::PILOT, -3);
        $this->configure(self::PILOT, true, false, false, 'monthly');

        $this->runOn(self::SECOND_MONDAY);
        $this->assertCount(0, $this->manager->emails);
        $this->runOn(self::FIRST_MONDAY);
        $this->assertCount(1, $this->manager->emails);
    }

    public function test_frequence_immediate_ne_renvoie_que_les_nouveautes(): void
    {
        $doc = $this->addDocument(self::PILOT, 10);
        $this->configure(self::PILOT, true, false, false, 'immediate');

        $this->runOn(self::TUESDAY);
        $this->assertCount(1, $this->manager->emails);
        $this->runOn(self::TUESDAY);
        $this->assertCount(1, $this->manager->emails, 'Déjà notifiée');

        // Le document expire : l'occurrence change d'état et est renotifiée
        $this->CI->db->where('id', $doc)->update('archived_documents',
            array('valid_until' => date('Y-m-d', strtotime('-1 day'))));
        $this->runOn(self::TUESDAY);
        $this->assertCount(2, $this->manager->emails);
    }

    public function test_message_du_jour_individuel(): void
    {
        $this->addDocument(self::PILOT, 10);
        $this->configure(self::PILOT, false, true, false, 'weekly');

        $stats = $this->runOn(self::MONDAY);
        $this->assertEquals(1, $stats['motd']);
        $motd = $this->CI->db->get_where('motd_messages',
            array('source_type' => 'alarm', 'target_user_login' => self::PILOT))->row_array();
        $this->assertNotNull($motd);
        $this->assertEquals('user', $motd['target_type']);
        $this->assertStringContainsString('Type notification test', $motd['content']);
    }

    public function test_doublon_entre_alarme_personnelle_et_supervision(): void
    {
        $this->addDocument(self::SUPERVISOR, 10);
        $this->configure(self::SUPERVISOR, true, false, false, 'weekly', array('personal', 'supervision'));

        $this->runOn(self::MONDAY);
        $this->assertCount(1, $this->manager->emails);
        $this->assertEquals(1, substr_count($this->manager->emails[0]['body'], 'archived_documents/view/'));
    }

    public function test_supervision_couvre_les_documents_des_autres(): void
    {
        $this->addDocument(self::PILOT, 10);
        $this->configure(self::SUPERVISOR, true, false, false, 'weekly', array('supervision'));

        $this->runOn(self::MONDAY);
        $this->assertCount(1, $this->manager->emails);
        $this->assertStringContainsString(self::PILOT, $this->manager->emails[0]['body']);
    }

    public function test_rappel_sms_le_lundi_avec_lien_seulement(): void
    {
        $this->addDocument(self::PILOT, 10);
        $this->configure(self::PILOT, true, false, true, 'immediate');

        $this->runOn(self::TUESDAY);
        $this->assertCount(0, $this->manager->sms, 'Pas de SMS le mardi');

        $this->runOn(self::MONDAY);
        $this->assertCount(1, $this->manager->sms);
        $this->assertStringContainsString('mes_alarmes', $this->manager->sms[0]['message']);
        $this->assertStringNotContainsString('Type notification test', $this->manager->sms[0]['message']);

        $this->runOn(self::MONDAY);
        $this->assertCount(1, $this->manager->sms, 'Un seul SMS par période');
    }

    public function test_pas_de_sms_sans_alarme_ou_si_desactive(): void
    {
        $this->configure(self::PILOT, true, false, true, 'weekly');
        $this->runOn(self::MONDAY);
        $this->assertCount(0, $this->manager->sms, 'Aucune alarme');

        $this->addDocument(self::PILOT, 10);
        $this->CI->config->set_item('alarm_sms_enabled', FALSE);
        $this->runOn(self::MONDAY);
        $this->assertCount(0, $this->manager->sms, 'Interrupteur global');
    }
}
