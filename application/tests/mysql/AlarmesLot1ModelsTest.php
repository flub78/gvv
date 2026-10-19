<?php

use PHPUnit\Framework\TestCase;

/**
 * Alarmes, Lot 1 : catalogue, cases autorisées et effectives (alarme ×
 * section ou Club), droits, alarmes propres, détection des documents
 * (expiration, manquants, obligation par rôle).
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
    const SECTION_TYPE_CODE = 'zztest_alarm_sec';

    private $section_id;
    private $other_section_id;
    private $type_id;
    private $section_type_id;
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
        $this->CI->db->insert('document_types', array('code' => self::SECTION_TYPE_CODE, 'name' => 'Type section test',
            'scope' => 'section', 'required' => 0, 'has_expiration' => 1, 'alert_days_before' => 30, 'active' => 1));
        $this->section_type_id = (int) $this->CI->db->insert_id();
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
                     WHERE dt.code IN ('" . self::TYPE_CODE . "', '" . self::SECTION_TYPE_CODE . "'))");
        $db->query("DELETE FROM alarm_subscriptions WHERE subscriber_login IN ('" . implode("','", $logins) . "')");
        $db->query("DELETE FROM document_types WHERE code IN ('" . self::TYPE_CODE . "', '" . self::SECTION_TYPE_CODE . "')"); // alarmes en cascade
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

    /** Alarme du catalogue par code (défaut : le type de document pilote du test) */
    private function definition($code = null)
    {
        $code = $code ?: 'document_' . $this->type_id;
        return $this->CI->alarm_definitions_model->get(
            $this->CI->db->get_where('alarm_definitions', array('code' => $code))->row_array()['id']);
    }

    /** Alarme du type de test restreinte aux élèves */
    private function eleveDefinition()
    {
        $eleve = $this->CI->db->get_where('types_roles', array('nom' => 'eleve'))->row_array()['id'];
        $this->CI->db->insert('alarm_definitions', array('code' => 'document_' . $this->type_id . '_eleve',
            'label' => 'Type alarme test — élèves', 'source' => 'document', 'document_type_id' => $this->type_id,
            'target_role_id' => $eleve, 'lead_days' => 30, 'active' => 1));
        return $this->definition('document_' . $this->type_id . '_eleve');
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

    /** Clés des occurrences d'une alarme retenues pour un membre (propres + cases effectives) */
    private function visible($login, $def)
    {
        $this->CI->load->library('Notification_manager');
        $cells = $this->CI->alarm_subscriptions_model->effective_for($login);
        $keys = array_keys($this->CI->notification_manager->occurrences_for(
            $login, $cells, array($def['id'] => $def), array($def['id'] => $this->itemsFor($def))));
        sort($keys);
        return $keys;
    }

    private function roleId($role)
    {
        return (int) $this->CI->db->get_where('types_roles', array('nom' => $role))->row_array()['id'];
    }

    // ------------------------------------------------------------------

    public function test_catalogue_suit_le_type_de_document(): void
    {
        $this->assertNotNull($this->definition(), 'Une alarme par type de document');
        $this->assertNotNull($this->definition('document_' . $this->section_type_id));

        $this->CI->db->where('id', $this->type_id)->update('document_types', array('has_expiration' => 0));
        $this->CI->alarm_definitions_model->sync_document_types();
        $this->assertEquals(0, (int) $this->definition()['active'], 'Désactivée sans expiration');
    }

    public function test_type_sans_alarmes_possibles(): void
    {
        $this->CI->db->where('id', $this->type_id)->update('document_types', array('alarm_enabled' => 0));
        $this->CI->alarm_definitions_model->sync_document_types();
        $this->assertEquals(0, (int) $this->definition()['active'], 'Désactivée');
        $this->assertEmpty($this->CI->alarm_subscriptions_model->allowed_cells(self::SUPERVISOR, $this->definition()),
            'Plus proposée');

        $this->CI->db->where('id', $this->type_id)->update('document_types', array('alarm_enabled' => 1));
        $this->CI->alarm_definitions_model->sync_document_types();
        $this->assertEquals(1, (int) $this->definition()['active'], 'Réactivée');
    }

    public function test_reactivation_du_type_reactive_l_alarme_des_eleves(): void
    {
        $eleve_def = $this->eleveDefinition();
        foreach (array('alarm_enabled', 'active', 'has_expiration') as $field) {
            $this->CI->db->where('id', $this->type_id)->update('document_types', array($field => 0));
            $this->CI->alarm_definitions_model->sync_document_types();
            $this->assertEquals(0, (int) $this->definition()['active'], "$field = 0");
            $this->assertEquals(0, (int) $this->definition($eleve_def['code'])['active'], "$field = 0 : alarme des élèves");

            $this->CI->db->where('id', $this->type_id)->update('document_types', array($field => 1));
            $this->CI->alarm_definitions_model->sync_document_types();
            $this->assertEquals(1, (int) $this->definition()['active'], "$field = 1");
            $this->assertEquals(1, (int) $this->definition($eleve_def['code'])['active'], "$field = 1 : alarme des élèves");
        }
    }

    public function test_desactivation_dans_le_catalogue_respectee_par_la_synchronisation(): void
    {
        $eleve_def = $this->eleveDefinition();
        $this->CI->alarm_definitions_model->configure($eleve_def['id'], false, 30);
        $this->CI->alarm_definitions_model->sync_document_types();
        $this->assertEquals(0, (int) $this->definition($eleve_def['code'])['active'], 'Choix de l\'administrateur');

        // Même après une désactivation puis réactivation du type
        $this->CI->db->where('id', $this->type_id)->update('document_types', array('alarm_enabled' => 0));
        $this->CI->alarm_definitions_model->sync_document_types();
        $this->CI->db->where('id', $this->type_id)->update('document_types', array('alarm_enabled' => 1));
        $this->CI->alarm_definitions_model->sync_document_types();
        $this->assertEquals(0, (int) $this->definition($eleve_def['code'])['active']);
        $this->assertEquals(1, (int) $this->definition()['active']);
    }

    public function test_aucun_abonnement_par_defaut(): void
    {
        $this->assertEmpty($this->CI->alarm_subscriptions_model->effective_for(self::PILOT));
        $this->assertEmpty($this->CI->alarm_subscriptions_model->effective_for(self::SUPERVISOR));
    }

    public function test_cases_autorisees(): void
    {
        $model = $this->CI->alarm_subscriptions_model;
        $club = Alarm_subscriptions_model::CLUB;
        $section_def = $this->definition('document_' . $this->section_type_id);

        $this->assertEmpty($model->allowed_cells(self::PILOT, $this->definition()), 'Simple membre');
        $this->assertEmpty($model->allowed_cells(self::PILOT, $this->definition('missing_document')));
        $this->assertEquals(array($this->section_id => true), $model->allowed_cells(self::SUPERVISOR, $this->definition()),
            'CA : sa section, pas de colonne Club pour des documents de pilote');
        $this->assertEquals(array($this->section_id => true, $club => true), $model->allowed_cells(self::SUPERVISOR, $section_def),
            'Documents de section : colonne Club en plus');
        $this->assertEmpty($model->allowed_cells(self::INSTRUCTOR, $section_def), 'Instructeur, document de section');
        $this->assertEmpty($model->proposed_definitions(self::PILOT), 'Pas de matrice pour un simple membre');

        $this->grant(self::SUPERVISOR, 'ca', $this->other_section_id);
        $this->assertEquals(array($this->section_id => true, $this->other_section_id => true),
            $model->allowed_cells(self::SUPERVISOR, $this->definition()), 'Une case par section supervisée');

        $this->assertFalse($model->subscribe($section_def['id'], self::PILOT, $this->section_id)['ok']);
        $this->assertFalse($model->subscribe($this->definition()['id'], self::SUPERVISOR, $club)['ok'], 'Pas de colonne Club');
        $result = $model->subscribe($this->definition()['id'], self::INSTRUCTOR, $this->section_id);
        $this->assertFalse($result['ok']);
        $this->assertNotEmpty($result['error']);
        $this->assertTrue($model->subscribe($this->definition()['id'], self::SUPERVISOR, $this->other_section_id)['ok']);
        $this->assertEquals(array($this->other_section_id), array_keys($model->effective_for(self::SUPERVISOR)[$this->definition()['id']]));
    }

    public function test_abonnement_par_categorie_retrait_et_obligatoire(): void
    {
        $model = $this->CI->alarm_subscriptions_model;
        $def = $this->definition();
        $this->grant(self::INSTRUCTOR, 'ca', $this->section_id);

        $this->assertTrue($model->subscribe_category($def['id'], $this->roleId('ca'), $this->section_id)['ok']);
        $cell = $model->effective_for(self::SUPERVISOR)[$def['id']][$this->section_id];
        $this->assertEquals('category', $cell['origin']);
        $this->assertArrayNotHasKey($def['id'], $model->effective_for(self::PILOT), 'Pas le rôle');

        // Décochée → retrait de la case, les autres membres restent abonnés
        $this->assertTrue($model->save_choices(self::SUPERVISOR, array())['ok']);
        $this->assertArrayNotHasKey($def['id'], $model->effective_for(self::SUPERVISOR));
        $this->assertArrayHasKey($def['id'], $model->effective_for(self::INSTRUCTOR));

        // Recochée → retrait levé
        $model->save_choices(self::SUPERVISOR, array($def['id'] => array($this->section_id)));
        $this->assertEquals('category', $model->effective_for(self::SUPERVISOR)[$def['id']][$this->section_id]['origin']);

        // Obligatoire → ne peut pas être décochée
        $model->subscribe($def['id'], self::SUPERVISOR, $this->section_id, true);
        $result = $model->save_choices(self::SUPERVISOR, array());
        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey($def['id'], $model->effective_for(self::SUPERVISOR));
    }

    public function test_categorie_sans_section_couvre_les_sections_du_role_et_club(): void
    {
        $model = $this->CI->alarm_subscriptions_model;
        $def = $this->definition('document_' . $this->section_type_id);
        $model->subscribe_category($def['id'], $this->roleId('ca'), null);

        $this->assertEquals(array($this->section_id, Alarm_subscriptions_model::CLUB),
            array_keys($model->effective_for(self::SUPERVISOR)[$def['id']]));
        $this->revoke(self::SUPERVISOR, 'ca');
        $this->assertArrayNotHasKey($def['id'], $model->effective_for(self::SUPERVISOR), 'Perte du rôle');
    }

    public function test_detection_des_documents_qui_expirent(): void
    {
        $upcoming = $this->addDocument(self::PILOT, 10);
        $expired = $this->addDocument(self::SUPERVISOR, -5);
        $this->addDocument(self::INSTRUCTOR, 60);                               // hors délai
        $this->addDocument(self::INSTRUCTOR, -1, array('alarm_disabled' => 1));  // alerte désactivée

        $items = array();
        foreach ($this->itemsFor($this->definition()) as $item) {
            $items[$item['key']] = $item;
        }
        $this->assertCount(2, $items);
        $this->assertEquals('upcoming', $items['document:' . $upcoming]['state']);
        $this->assertEquals('active', $items['document:' . $expired]['state']);
        $this->assertEquals(array($this->section_id), $items['document:' . $upcoming]['section_ids']);
    }

    public function test_alarmes_propres_sans_abonnement(): void
    {
        $pilot_doc = $this->addDocument(self::PILOT, 10);
        $supervisor_doc = $this->addDocument(self::SUPERVISOR, 10);
        $def = $this->definition();

        $this->assertEquals(array('document:' . $pilot_doc), $this->visible(self::PILOT, $def), 'Simple membre : ses documents');
        $this->assertEquals(array('document:' . $supervisor_doc), $this->visible(self::SUPERVISOR, $def),
            'Superviseur non abonné : ses documents seulement');

        $this->CI->alarm_subscriptions_model->subscribe($def['id'], self::SUPERVISOR, $this->section_id);
        $expected = array('document:' . $pilot_doc, 'document:' . $supervisor_doc);
        sort($expected);
        $this->assertEquals($expected, $this->visible(self::SUPERVISOR, $def), 'Abonné à sa section : aussi ceux des autres');
    }

    public function test_abonnement_limite_aux_pilotes_actifs_de_la_section(): void
    {
        $model = $this->CI->alarm_subscriptions_model;
        $def = $this->definition();
        $doc = 'document:' . $this->addDocument(self::PILOT, -2);
        $this->grant(self::SUPERVISOR, 'ca', $this->other_section_id);
        $model->subscribe($def['id'], self::SUPERVISOR, $this->other_section_id);

        $this->assertNotContains($doc, $this->visible(self::SUPERVISOR, $def), 'Pilote actif dans une autre section');

        $this->grant(self::PILOT, 'user', $this->other_section_id);
        $this->assertContains($doc, $this->visible(self::SUPERVISOR, $def), 'Pilote actif dans la section cochée');

        $this->revoke(self::SUPERVISOR, 'ca');
        $this->assertNotContains($doc, $this->visible(self::SUPERVISOR, $def), 'Droit retiré');
    }

    public function test_colonne_club_pour_les_documents_sans_section(): void
    {
        $model = $this->CI->alarm_subscriptions_model;
        $def = $this->definition('document_' . $this->section_type_id);
        $doc = 'document:' . $this->addDocument(null, 5, array('document_type_id' => $this->section_type_id, 'uploaded_by' => self::SUPERVISOR));

        $model->subscribe($def['id'], self::SUPERVISOR, $this->section_id);
        $this->assertNotContains($doc, $this->visible(self::SUPERVISOR, $def), 'Case de section seulement');

        $model->subscribe($def['id'], self::SUPERVISOR, Alarm_subscriptions_model::CLUB);
        $this->assertContains($doc, $this->visible(self::SUPERVISOR, $def), 'Colonne Club');
    }

    public function test_alarme_restreinte_aux_eleves(): void
    {
        $model = $this->CI->alarm_subscriptions_model;
        $def = $this->eleveDefinition();
        $doc = 'document:' . $this->addDocument(self::PILOT, 10);

        $this->assertEquals(array($this->section_id => true), $model->allowed_cells(self::INSTRUCTOR, $def), 'Instructeur de la section');
        $this->assertEmpty($model->allowed_cells(self::PILOT, $def), 'Simple membre');
        $this->assertCount(0, $this->itemsFor($def), 'Pas encore élève');

        $this->grant(self::PILOT, 'eleve', $this->section_id);
        $this->assertCount(1, $this->itemsFor($def));
        $this->assertTrue($model->subscribe($def['id'], self::INSTRUCTOR, $this->section_id)['ok']);
        $this->assertEquals(array($doc), $this->visible(self::INSTRUCTOR, $def));
    }

    public function test_pilote_inactif_sans_alarme(): void
    {
        $doc = $this->addDocument(self::PILOT, 10);
        $this->revoke(self::PILOT, 'user');
        $keys = array_column($this->itemsFor($this->definition()), 'key');
        $this->assertNotContains('document:' . $doc, $keys);
    }

    public function test_document_obligatoire_pour_un_role(): void
    {
        $eleve = $this->roleId('eleve');
        $this->CI->db->where('id', $this->type_id)
            ->update('document_types', array('required' => 1, 'required_role_id' => $eleve));
        $missing_def = $this->definition('missing_document');
        $key = 'missing:' . $this->type_id . ':' . self::PILOT;
        $find = function () use ($missing_def, $key) {
            return array_values(array_filter($this->itemsFor($missing_def), function ($i) use ($key) {
                return $i['key'] === $key;
            }));
        };

        $this->assertEmpty($find(), 'Pas élève');

        $this->grant(self::PILOT, 'eleve', $this->section_id);
        $this->assertCount(1, $find(), 'Élève sans document');
        $this->assertContains($key, $this->visible(self::PILOT, $missing_def), 'Alarme propre');
        $this->CI->alarm_subscriptions_model->subscribe($missing_def['id'], self::SUPERVISOR, $this->section_id);
        $this->assertContains($key, $this->visible(self::SUPERVISOR, $missing_def), 'Superviseur abonné');

        $this->CI->load->model('archived_documents_model');
        $missing_ids = function () {
            return array_column($this->CI->archived_documents_model->get_missing_documents(self::PILOT), 'id');
        };
        $this->assertContains($this->type_id, array_map('intval', $missing_ids()), 'Page du pilote : manquant');
        $this->assertNotContains($this->type_id, array_map('intval', array_column(
            $this->CI->archived_documents_model->get_missing_documents(self::PILOT, $this->other_section_id), 'id')),
            'Pas élève dans l\'autre section');

        $this->addDocument(self::PILOT, 100);
        $this->assertEmpty($find(), 'Document valide');
        $this->assertNotContains($this->type_id, array_map('intval', $missing_ids()));
    }
}
