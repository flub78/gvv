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

    public function members() {
        return $this->members_to_notify();
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
 * Alarmes, Lot 1 : alarmes propres et cases d'abonnement, fréquences et
 * jours d'envoi, contenu des synthèses, canaux, rappel SMS, idempotence.
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

    /** Alarme du type de test ('' = tous les membres, 'eleve' = restreinte aux élèves) */
    private function defId($variant = '')
    {
        $code = 'document_' . $this->type_id . ($variant ? '_' . $variant : '');
        if ($variant === 'eleve' && !$this->CI->db->where('code', $code)->count_all_results('alarm_definitions')) {
            $eleve = $this->CI->db->get_where('types_roles', array('nom' => 'eleve'))->row_array()['id'];
            $this->CI->db->insert('alarm_definitions', array('code' => $code, 'label' => 'Test élèves',
                'source' => 'document', 'document_type_id' => $this->type_id, 'target_role_id' => $eleve,
                'lead_days' => 30, 'active' => 1));
        }
        return (int) $this->CI->db->get_where('alarm_definitions', array('code' => $code))->row_array()['id'];
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

    /**
     * Réglages d'un membre et, facultativement, abonnement à la case de la
     * section du test pour chaque variante d'alarme
     */
    private function configure($login, $email, $motd, $sms, $frequency, $variants = array())
    {
        foreach ($variants as $variant) {
            $this->assertTrue($this->CI->alarm_subscriptions_model->subscribe($this->defId($variant), $login, $this->section_id)['ok']);
        }
        $this->CI->alarm_member_settings_model->save_for($login, $email, $motd, $sms, $frequency);
    }

    private function runOn($date)
    {
        $this->manager->set_today($date);
        return $this->manager->run('test');
    }

    // ------------------------------------------------------------------

    public function test_alarmes_propres_envoyees_sans_abonnement(): void
    {
        $this->addDocument(self::SUPERVISOR, 10);
        $this->configure(self::PILOT, true, false, false, 'weekly');

        $this->runOn(self::MONDAY);
        $this->assertCount(0, $this->manager->emails, 'Aucun document du pilote');

        $this->addDocument(self::PILOT, 10);
        $this->runOn(self::FIRST_MONDAY);
        $this->assertCount(1, $this->manager->emails);
        $this->assertStringNotContainsString(self::SUPERVISOR, $this->manager->emails[0]['body']);
    }

    public function test_sans_reglage_rien_n_est_envoye(): void
    {
        $this->addDocument(self::PILOT, 10);
        $this->addDocument(self::SUPERVISOR, 10);
        $this->configure(self::SUPERVISOR, false, false, false, 'weekly', array(''));
        $this->CI->db->where('member_login', self::SUPERVISOR)->delete('alarm_member_settings');

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

    public function test_document_couvert_par_deux_alarmes_liste_une_fois(): void
    {
        // Le document d'un élève relève de l'alarme du type et de celle des élèves
        $this->grant(self::PILOT, 'eleve');
        $this->addDocument(self::PILOT, 10);
        $this->configure(self::SUPERVISOR, true, false, false, 'weekly', array('', 'eleve'));
        $this->assertCount(2, $this->CI->alarm_subscriptions_model->effective_for(self::SUPERVISOR));

        $this->runOn(self::MONDAY);
        $this->assertCount(1, $this->manager->emails);
        $this->assertEquals(1, substr_count($this->manager->emails[0]['body'], 'archived_documents/view/'));
    }

    public function test_superviseur_recoit_les_documents_des_autres_s_il_est_abonne(): void
    {
        $this->addDocument(self::PILOT, 10);
        $this->configure(self::SUPERVISOR, true, false, false, 'weekly');

        $this->runOn(self::MONDAY);
        $this->assertCount(0, $this->manager->emails, 'Non abonné : rien que ses propres alarmes');

        $this->configure(self::SUPERVISOR, true, false, false, 'weekly', array(''));
        $this->runOn(self::FIRST_MONDAY);
        $this->assertCount(1, $this->manager->emails);
        $this->assertStringContainsString(self::PILOT, $this->manager->emails[0]['body']);
    }

    public function test_cron_horaire_rien_avant_l_heure_puis_une_seule_synthese(): void
    {
        $saved = $this->CI->config->item('alarm_scheduler_hour');
        $this->CI->config->set_item('alarm_scheduler_hour', 7);
        try {
            $this->addDocument(self::PILOT, 10);
            $this->configure(self::PILOT, true, false, true, 'weekly');
            $this->manager->set_today(self::MONDAY);

            $this->assertNull($this->manager->run_scheduled('test', 0), 'Pas de synthèse la nuit');
            $this->assertCount(0, $this->manager->emails);

            foreach (array(7, 8, 15, 23) as $hour) {
                $this->assertNotNull($this->manager->run_scheduled('test', $hour));
            }
            $this->assertCount(1, $this->manager->emails, 'Une seule synthèse par lundi');
            $this->assertCount(1, $this->manager->sms, 'Un seul SMS par lundi');
        } finally {
            $this->CI->config->set_item('alarm_scheduler_hour', $saved);
        }
    }

    public function test_cron_sans_gestion_documentaire(): void
    {
        $saved = $this->CI->config->item('gestion_documentaire');
        $this->CI->config->set_item('gestion_documentaire', false);
        try {
            $this->addDocument(self::PILOT, -1);
            $this->configure(self::PILOT, true, false, false, 'immediate');
            $this->assertNull($this->manager->run_scheduled('test', 12));
            $this->assertCount(0, $this->manager->emails);
        } finally {
            $this->CI->config->set_item('gestion_documentaire', $saved);
        }
    }

    public function test_ligne_de_compte_rendu_du_cron(): void
    {
        $this->assertEquals('skipped', Notification_manager::format_stats(null));
        $this->assertEquals('email=2 motd=1 sms=0 failed=3', Notification_manager::format_stats(
            array('email' => 2, 'motd' => 1, 'sms' => 0, 'failed' => 3)));
    }

    public function test_membres_sans_canal_ni_sms_non_traites(): void
    {
        $this->configure(self::PILOT, false, false, false, 'weekly');
        $this->configure(self::SUPERVISOR, false, false, true, 'weekly');
        $members = $this->manager->members();
        $this->assertArrayNotHasKey(self::PILOT, $members);
        $this->assertArrayHasKey(self::SUPERVISOR, $members, 'Rappel SMS seul');
    }

    public function test_lien_selon_le_lecteur(): void
    {
        $doc = $this->addDocument(self::PILOT, -1);
        $missing = array('own' => true, 'link' => 'archived_documents/pilot_documents/' . self::PILOT,
            'link_own' => 'archived_documents/my_documents');
        $this->assertEquals('archived_documents/my_documents', Notification_manager::item_link($missing, true));
        $this->assertEquals('archived_documents/pilot_documents/' . self::PILOT,
            Notification_manager::item_link($missing, false), 'Administrateur consultant un autre membre');

        $occurrences = $this->manager->member_occurrences(self::PILOT);
        $this->assertTrue($occurrences['document:' . $doc]['own']);
        $this->assertEquals('archived_documents/view/' . $doc, $occurrences['document:' . $doc]['link']);
    }

    public function test_nombre_d_alarmes_actives_pour_le_tableau_de_bord(): void
    {
        $this->assertEquals(0, $this->manager->active_count_for(self::PILOT));
        $this->addDocument(self::PILOT, 10);    // à venir
        $this->addDocument(self::PILOT, -3);    // active
        $this->assertEquals(1, $this->manager->active_count_for(self::PILOT), 'Alarmes propres, sans réglage');

        $this->addDocument(self::SUPERVISOR, -1);
        $this->assertEquals(1, $this->manager->active_count_for(self::SUPERVISOR), 'Non abonné : les siennes');
        $this->configure(self::SUPERVISOR, false, false, false, 'weekly', array(''));
        $this->assertEquals(2, $this->manager->active_count_for(self::SUPERVISOR), 'Abonné à sa section');
    }

    public function test_occurrence_de_la_section_active(): void
    {
        $this->assertTrue(Notification_manager::item_in_section(array('section_ids' => array(3)), 0), 'Toutes les sections');
        $this->assertTrue(Notification_manager::item_in_section(array('section_ids' => array()), 3), 'Club : toutes les sections');
        $this->assertTrue(Notification_manager::item_in_section(array('section_ids' => array('1', '3')), 3));
        $this->assertFalse(Notification_manager::item_in_section(array('section_ids' => array(1)), 3));
    }

    public function test_nombre_d_alarmes_actives_de_la_section_active(): void
    {
        $this->addDocument(self::PILOT, 10);    // à venir
        $this->addDocument(self::PILOT, -3);    // active, section du test
        $this->assertEquals(array(1, 1), $this->manager->active_counts_for(self::PILOT, 0), 'Toutes les sections');
        $this->assertEquals(array(1, 1), $this->manager->active_counts_for(self::PILOT, $this->section_id));
        $this->assertEquals(array(0, 1), $this->manager->active_counts_for(self::PILOT, $this->section_id + 100000),
            'Autre section : masquée, mais comptée dans le total');
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
