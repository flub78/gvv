<?php

use PHPUnit\Framework\TestCase;

/**
 * Alarmes, Lot 1 : catalogue, abonnements effectifs, droits, détection des
 * documents (expiration, manquants, obligation par rôle).
 *
 * Toutes les données sont créées par le test et supprimées en fin de test.
 *
 * @see application/models/alarm_subscriptions_model.php
 * @see application/libraries/Document_alarm_provider.php
 */
class AlarmesLot1ModelsTest extends TestCase
{
    /** @var CI_Controller */
    private $CI;

    const PILOT = 'zzalarmpil';
    const SUPERVISOR = 'zzalarmca';
    const INSTRUCTOR = 'zzalarminst';
    const TYPE_CODE = 'zztest_alarm';

    private $section_id;
    private $other_section_id;
    private $type_id;
    private $user_ids = array();
    private $doc_ids = array();

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        $this->CI->load->model('alarm_definitions_model');
        $this->CI->load->model('alarm_subscriptions_model');
        $this->CI->load->library('Alarm_aggregator');

        $sections = $this->CI->db->query("SELECT id FROM sections ORDER BY id LIMIT 2")->result_array();
        if (count($sections) < 2) {
            $this->markTestSkipped('Deux sections sont nécessaires');
        }
        $this->section_id = (int) $sections[0]['id'];
        $this->other_section_id = (int) $sections[1]['id'];

        $this->cleanup();

        foreach (array(self::PILOT, self::SUPERVISOR, self::INSTRUCTOR) as $login) {
            $this->CI->db->insert('membres', array('mlogin' => $login, 'mnom' => 'Test', 'mprenom' => $login, 'actif' => 1));
            $this->CI->db->insert('users', array('username' => $login, 'password' => 'x',
                'email' => $login . '@example.invalid', 'last_ip' => '127.0.0.1'));
            $this->user_ids[$login] = (int) $this->CI->db->insert_id();
            $this->grant($login, 'user', $this->section_id);
        }
        $this->grant(self::SUPERVISOR, 'ca', $this->section_id);
        $this->grant(self::INSTRUCTOR, 'instructeur', $this->section_id);

        $this->CI->db->insert('document_types', array('code' => self::TYPE_CODE, 'name' => 'Type alarme test',
            'scope' => 'pilot', 'required' => 0, 'has_expiration' => 1, 'alert_days_before' => 30, 'active' => 1));
        $this->type_id = (int) $this->CI->db->insert_id();
        $this->CI->alarm_definitions_model->sync_document_types();
        $this->CI->alarm_subscriptions_model->reset_roles_cache();
    }

    protected function tearDown(): void
    {
        $this->cleanup();
    }

    private function cleanup()
    {
        $db = $this->CI->db;
        foreach ($this->doc_ids as $id) {
            $db->where('id', $id)->delete('archived_documents');
        }
        $this->doc_ids = array();
        $logins = array(self::PILOT, self::SUPERVISOR, self::INSTRUCTOR);
        $db->query("DELETE urps FROM user_roles_per_section urps JOIN users u ON u.id = urps.user_id
                    WHERE u.username IN ('" . implode("','", $logins) . "')");
        $db->query("DELETE FROM users WHERE username IN ('" . implode("','", $logins) . "')");
        $db->query("DELETE FROM alarm_subscriptions WHERE alarm_definition_id IN
                    (SELECT ad.id FROM alarm_definitions ad JOIN document_types dt ON dt.id = ad.document_type_id
                     WHERE dt.code = '" . self::TYPE_CODE . "')");
        $db->query("DELETE FROM alarm_subscriptions WHERE subscriber_login IN ('" . implode("','", $logins) . "')");
        $db->query("DELETE FROM document_types WHERE code = '" . self::TYPE_CODE . "'"); // alarmes en cascade
        $db->query("DELETE FROM membres WHERE mlogin IN ('" . implode("','", $logins) . "')");
        $this->CI->alarm_subscriptions_model->reset_roles_cache();
    }

    private function grant($login, $role, $section_id)
    {
        $role_id = $this->CI->db->get_where('types_roles', array('nom' => $role))->row_array()['id'];
        $this->CI->db->insert('user_roles_per_section', array('user_id' => $this->user_ids[$login],
            'types_roles_id' => $role_id, 'section_id' => $section_id));
        $this->CI->alarm_subscriptions_model->reset_roles_cache();
    }

    private function revoke($login, $role)
    {
        $role_id = $this->CI->db->get_where('types_roles', array('nom' => $role))->row_array()['id'];
        $this->CI->db->where('user_id', $this->user_ids[$login])->where('types_roles_id', $role_id)
            ->update('user_roles_per_section', array('revoked_at' => date('Y-m-d H:i:s')));
        $this->CI->alarm_subscriptions_model->reset_roles_cache();
    }

    private function definition($kind)
    {
        return $this->CI->alarm_definitions_model->get(
            $this->CI->db->get_where('alarm_definitions', array('code' => 'document_' . $this->type_id . '_' . $kind))
                ->row_array()['id']);
    }

    private function addDocument($pilot, $days_from_now, $extra = array())
    {
        $this->CI->db->insert('archived_documents', array_merge(array(
            'document_type_id' => $this->type_id, 'pilot_login' => $pilot,
            'file_path' => 'uploads/documents/test/zz.pdf', 'original_filename' => 'zz.pdf',
            'uploaded_by' => $pilot, 'uploaded_at' => date('Y-m-d H:i:s'),
            'valid_until' => date('Y-m-d', strtotime(($days_from_now >= 0 ? '+' : '') . $days_from_now . ' days')),
            'alarm_disabled' => 0, 'is_current_version' => 1, 'validation_status' => 'approved',
        ), $extra));
        $id = (int) $this->CI->db->insert_id();
        $this->doc_ids[] = $id;
        return $id;
    }

    private function itemsFor($def)
    {
        return $this->CI->alarm_aggregator->collect(array($def));
    }

    // ------------------------------------------------------------------

    public function test_catalogue_suit_le_type_de_document(): void
    {
        $this->assertNotNull($this->definition('personal'));
        $this->assertNotNull($this->definition('supervision'));

        $this->CI->db->where('id', $this->type_id)->update('document_types', array('has_expiration' => 0));
        $this->CI->alarm_definitions_model->sync_document_types();
        $this->assertEquals(0, (int) $this->definition('personal')['active'], 'Désactivée sans expiration');
    }

    public function test_aucun_abonnement_par_defaut(): void
    {
        $this->assertFalse($this->CI->alarm_subscriptions_model->has_effective_subscription(self::PILOT));
    }

    public function test_droits_de_supervision(): void
    {
        $model = $this->CI->alarm_subscriptions_model;
        $supervision = $this->definition('supervision');

        $this->assertTrue($model->can_subscribe(self::PILOT, $this->definition('personal')));
        $this->assertFalse($model->can_subscribe(self::PILOT, $supervision), 'Simple membre');
        $this->assertTrue($model->can_subscribe(self::SUPERVISOR, $supervision), 'CA de la section');
        $this->assertFalse($model->can_subscribe(self::INSTRUCTOR, $supervision), 'Instructeur sans ciblage');

        $result = $model->subscribe($supervision['id'], self::PILOT);
        $this->assertFalse($result['ok']);
        $this->assertNotEmpty($result['error']);
    }

    public function test_abonnement_par_categorie_retrait_et_obligatoire(): void
    {
        $model = $this->CI->alarm_subscriptions_model;
        $def = $this->definition('personal');
        $user_role = $this->CI->db->get_where('types_roles', array('nom' => 'user'))->row_array()['id'];

        $this->assertTrue($model->subscribe_category($def['id'], $user_role, $this->section_id)['ok']);
        $this->assertArrayHasKey($def['id'], $model->effective_for(self::PILOT));
        $this->assertEquals('category', $model->effective_for(self::PILOT)[$def['id']]['origin']);

        // Décoché → retrait individuel, les autres membres restent abonnés
        $model->save_choices(self::PILOT, array());
        $this->assertArrayNotHasKey($def['id'], $model->effective_for(self::PILOT));
        $this->assertArrayHasKey($def['id'], $model->effective_for(self::SUPERVISOR));

        // Recoché → retrait levé
        $model->save_choices(self::PILOT, array($def['id']));
        $this->assertArrayHasKey($def['id'], $model->effective_for(self::PILOT));

        // Obligatoire → ne peut pas être décoché
        $model->subscribe($def['id'], self::PILOT, true);
        $result = $model->save_choices(self::PILOT, array());
        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey($def['id'], $model->effective_for(self::PILOT));
    }

    public function test_perte_du_role_supprime_l_abonnement_effectif(): void
    {
        $model = $this->CI->alarm_subscriptions_model;
        $def = $this->definition('supervision');
        $ca_role = $this->CI->db->get_where('types_roles', array('nom' => 'ca'))->row_array()['id'];
        $model->subscribe_category($def['id'], $ca_role, null);

        $this->assertArrayHasKey($def['id'], $model->effective_for(self::SUPERVISOR));
        $this->revoke(self::SUPERVISOR, 'ca');
        $this->assertArrayNotHasKey($def['id'], $model->effective_for(self::SUPERVISOR));
    }

    public function test_detection_des_documents_qui_expirent(): void
    {
        $upcoming = $this->addDocument(self::PILOT, 10);
        $expired = $this->addDocument(self::SUPERVISOR, -5);
        $this->addDocument(self::INSTRUCTOR, 60);                               // hors délai
        $this->addDocument(self::INSTRUCTOR, -1, array('alarm_disabled' => 1));  // alerte désactivée

        $items = array();
        foreach ($this->itemsFor($this->definition('supervision')) as $item) {
            $items[$item['key']] = $item;
        }
        $this->assertCount(2, $items);
        $this->assertEquals('upcoming', $items['document:' . $upcoming]['state']);
        $this->assertEquals('active', $items['document:' . $expired]['state']);
        $this->assertEquals(array($this->section_id), $items['document:' . $upcoming]['section_ids']);
    }

    public function test_alarme_personnelle_limitee_au_membre(): void
    {
        $this->addDocument(self::PILOT, 10);
        $this->addDocument(self::SUPERVISOR, 10);
        $model = $this->CI->alarm_subscriptions_model;
        $def = $this->definition('personal');

        $visible = array_filter($this->itemsFor($def), function ($item) use ($model, $def) {
            return $model->can_see_item(self::PILOT, $def, $item);
        });
        $this->assertCount(1, $visible);
        $this->assertEquals(self::PILOT, array_values($visible)[0]['pilot_login']);
    }

    public function test_supervision_limitee_aux_sections_du_superviseur(): void
    {
        $model = $this->CI->alarm_subscriptions_model;
        $def = $this->definition('supervision');
        $doc = $this->addDocument(self::PILOT, 10);
        $item = array_values(array_filter($this->itemsFor($def), function ($i) use ($doc) {
            return $i['key'] === 'document:' . $doc;
        }))[0];

        $this->assertTrue($model->can_see_item(self::SUPERVISOR, $def, $item));

        // Le pilote ne relève plus que de l'autre section
        $this->revoke(self::PILOT, 'user');
        $this->grant(self::PILOT, 'user', $this->other_section_id);
        $item = array_values(array_filter($this->itemsFor($def), function ($i) use ($doc) {
            return $i['key'] === 'document:' . $doc;
        }))[0];
        $this->assertFalse($model->can_see_item(self::SUPERVISOR, $def, $item));
    }

    public function test_pilote_inactif_sans_alarme(): void
    {
        $doc = $this->addDocument(self::PILOT, 10);
        $this->revoke(self::PILOT, 'user');
        $keys = array_column($this->itemsFor($this->definition('supervision')), 'key');
        $this->assertNotContains('document:' . $doc, $keys);
    }

    public function test_document_obligatoire_pour_un_role(): void
    {
        $eleve = $this->CI->db->get_where('types_roles', array('nom' => 'eleve'))->row_array()['id'];
        $this->CI->db->where('id', $this->type_id)
            ->update('document_types', array('required' => 1, 'required_role_id' => $eleve));
        $missing_def = $this->CI->alarm_definitions_model->get(
            $this->CI->db->get_where('alarm_definitions', array('code' => 'missing_document_supervision'))->row_array()['id']);
        $key = 'missing:' . $this->type_id . ':' . self::PILOT;

        $this->assertNotContains($key, array_column($this->itemsFor($missing_def), 'key'), 'Pas élève');

        $this->grant(self::PILOT, 'eleve', $this->section_id);
        $this->assertContains($key, array_column($this->itemsFor($missing_def), 'key'), 'Élève sans document');
        $this->assertTrue($this->CI->alarm_subscriptions_model->can_see_item(self::SUPERVISOR, $missing_def,
            array_values(array_filter($this->itemsFor($missing_def), function ($i) use ($key) { return $i['key'] === $key; }))[0]));

        $this->addDocument(self::PILOT, 100);
        $this->assertNotContains($key, array_column($this->itemsFor($missing_def), 'key'), 'Document valide');
    }
}
