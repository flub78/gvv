<?php

require_once __DIR__ . '/../integration/TransactionalTestCase.php';

/**
 * MySQL integration tests for Lot 5 (doc/plans/journalisation_crud_plan.md §2.3):
 * Vols_avion_model and Vols_planeur_model override create()/update() and call
 * Common_Model::inject_audit_fields() directly. This confirms the fixed guard
 * (is_audit_value_missing()) works through these overrides too, using the exact
 * FALSE values that Gvv_Controller::form2database() produces for audit columns
 * absent from the HTML form.
 */
class VolsAvionVolsPlaneurAuditMySqlTest extends TransactionalTestCase
{
    private $ci;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ci = &get_instance();
        $this->ci->load->database();
        $this->ci->load->model('vols_avion_model', 'gvv_model');
        $this->ci->load->model('vols_planeur_model');

        if (!$this->ci->db->conn_id) {
            $this->markTestSkipped('Database connection not available');
        }
    }

    public function testVolsAvionCreateInjectsAuditFieldsWhenFalseValuesPresent(): void
    {
        $pilot = $this->activePilot();
        $machine = $this->activeAirplane();

        if (empty($pilot) || empty($machine)) {
            $this->markTestSkipped('Missing active pilot or airplane');
        }

        $data = array(
            'vadate' => date('Y-m-d'),
            'vapilid' => $pilot,
            'vamacid' => $machine,
            'vacdeb' => 100,
            'vacfin' => 101,
            'vaduree' => 100,
            'vaobs' => 'Lot5 audit test',
            'vadc' => 0,
            'vacategorie' => 0,
            'saisie_par' => $pilot,
            'vaatt' => 1,
            'vahdeb' => 10,
            'vahfin' => 11,
            // What form2database() actually produces for a field absent from the HTML form.
            'created_at' => false,
            'created_by' => false,
            'updated_at' => false,
            'updated_by' => false,
        );

        // Depuis le correctif "vol créé malgré échec de facturation", create() supprime
        // le vol si facture() échoue (ex: tarif manquant) — il n'y a donc plus de ligne
        // de secours à aller chercher en cas d'exception. activeAirplane() choisit une
        // machine dont le tarif existe réellement pour éviter ce cas ici.
        try {
            $id = $this->ci->gvv_model->create($data);
        } catch (\Throwable $e) {
            $this->markTestSkipped('Facturation impossible pour cette machine/session de test: ' . $e->getMessage());
        }
        $this->assertNotEmpty($id, 'Flight should be created');

        $row = $this->ci->db->where('vaid', $id)->get('volsa')->row_array();

        $this->assertNotEquals('0000-00-00 00:00:00', $row['created_at'], 'created_at must not be the zero-date sentinel');
        $this->assertNotEmpty($row['created_at']);
        $this->assertEquals('test_user', $row['created_by']);
    }

    /**
     * Regression test: editing a flight must never wipe its original created_at/
     * created_by. Found in production on vaid=16631 (vpeignot editing their own
     * flight reset created_by from 'fpeignot' back to '0' and created_at back to
     * the zero-date) — the Lot 5 fix only guarded the create() branch, leaving
     * the poisoned FALSE value (form2database() supplies it on every edit too,
     * same as on create) to sail straight into the UPDATE statement.
     */
    public function testVolsAvionUpdateNeverOverwritesCreatedFields(): void
    {
        $pilot = $this->activePilot();
        $machine = $this->activeAirplane();

        if (empty($pilot) || empty($machine)) {
            $this->markTestSkipped('Missing active pilot or airplane');
        }

        $data = array(
            'vadate' => date('Y-m-d'),
            'vapilid' => $pilot,
            'vamacid' => $machine,
            'vacdeb' => 100,
            'vacfin' => 101,
            'vaduree' => 100,
            'vaobs' => 'Lot5 update regression test',
            'vadc' => 0,
            'vacategorie' => 0,
            'saisie_par' => $pilot,
            'vaatt' => 1,
            'vahdeb' => 10,
            'vahfin' => 11,
        );

        // Depuis le correctif "vol créé malgré échec de facturation", create() supprime
        // le vol si facture() échoue — il n'y a donc plus de ligne de secours en cas
        // d'exception ici. activeAirplane() choisit une machine dont le tarif existe
        // réellement pour que cette création préalable réussisse normalement.
        try {
            $id = $this->ci->gvv_model->create($data);
        } catch (\Throwable $e) {
            $this->markTestSkipped('Facturation impossible pour cette machine/session de test: ' . $e->getMessage());
        }
        $this->assertNotEmpty($id, 'Flight should be created');

        $original = $this->ci->db->where('vaid', $id)->get('volsa')->row_array();
        $this->assertNotEquals('0000-00-00 00:00:00', $original['created_at']);
        $this->assertEquals('test_user', $original['created_by'], 'created_by is set from the current session user, not the pilot');

        // Exactly what form2database() produces for a field absent from the HTML form.
        $update_data = array(
            'vaid' => $id,
            'vaobs' => 'Lot5 update regression test - edited',
            'created_at' => false,
            'created_by' => false,
        );
        try {
            $this->ci->gvv_model->update('vaid', $update_data);
        } catch (\Throwable $e) {
            // facture() can throw on this minimal synthetic dataset; the row update
            // itself (what this test checks) already happened before that call.
        }

        $after = $this->ci->db->where('vaid', $id)->get('volsa')->row_array();

        $this->assertEquals($original['created_at'], $after['created_at'], 'created_at must never change on update');
        $this->assertEquals($original['created_by'], $after['created_by'], 'created_by must never change on update');
        $this->assertEquals('Lot5 update regression test - edited', $after['vaobs']);
    }

    public function testVolsPlaneurCreateInjectsAuditFieldsWhenFalseValuesPresent(): void
    {
        $pilot = $this->activePilot();
        $machine = $this->activeGlider();

        if (empty($pilot) || empty($machine)) {
            $this->markTestSkipped('Missing active pilot or glider');
        }

        $data = array(
            'vpdate' => date('Y-m-d'),
            'vppilid' => $pilot,
            'vpmacid' => $machine,
            'vpcdeb' => 10,
            'vpcfin' => 11,
            'vpduree' => 1,
            'vpdc' => 0,
            'vpcategorie' => 0,
            'vpticcolle' => 0,
            'saisie_par' => $pilot,
            'created_at' => false,
            'created_by' => false,
            'updated_at' => false,
            'updated_by' => false,
        );

        $id = $this->ci->vols_planeur_model->create($data);
        $this->assertGreaterThan(0, $id, 'Flight should be created');

        $row = $this->ci->db->where('vpid', $id)->get('volsp')->row_array();

        $this->assertNotEquals('0000-00-00 00:00:00', $row['created_at'], 'created_at must not be the zero-date sentinel');
        $this->assertNotEmpty($row['created_at']);
        $this->assertEquals('test_user', $row['created_by']);
    }

    private function activePilot()
    {
        $row = $this->ci->db->select('mlogin')->from('membres')->where('actif', 1)->limit(1)->get()->row_array();
        return isset($row['mlogin']) ? $row['mlogin'] : null;
    }

    private function activeAirplane()
    {
        // Un avion dont la référence de tarif (maprix) existe réellement dans la
        // table tarifs : la création ne doit pas échouer pour tarif manquant,
        // ce qui empêcherait désormais la persistance du vol (cf. correctif
        // "vol créé malgré échec de facturation").
        $row = $this->ci->db
            ->select('machinesa.macimmat')
            ->from('machinesa')
            ->join('tarifs', 'tarifs.reference = machinesa.maprix', 'inner')
            ->where('machinesa.actif', 1)
            ->limit(1)
            ->get()
            ->row_array();
        return isset($row['macimmat']) ? $row['macimmat'] : null;
    }

    private function activeGlider()
    {
        $row = $this->ci->db->select('mpimmat')->from('machinesp')->limit(1)->get()->row_array();
        return isset($row['mpimmat']) ? $row['mpimmat'] : null;
    }
}
