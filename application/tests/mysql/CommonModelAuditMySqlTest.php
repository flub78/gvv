<?php

require_once __DIR__ . '/../integration/TransactionalTestCase.php';

/**
 * MySQL integration tests for Common_Model audit field injection (Lot 0).
 */
class CommonModelAuditMySqlTest extends TransactionalTestCase
{
    /** @var CI_Controller */
    private $ci;

    /** @var Reservations_model */
    private $reservations_model;

    /** @var Configuration_model */
    private $configuration_model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ci = &get_instance();
        $this->ci->load->database();
        $this->ci->load->model('reservations_model');
        $this->ci->load->model('configuration_model');

        $this->reservations_model = $this->ci->reservations_model;
        $this->configuration_model = $this->ci->configuration_model;

        if (!$this->ci->db->conn_id) {
            $this->markTestSkipped('Database connection not available');
        }
    }

    public function testCreateInjectsAuditFieldsWhenColumnsExist(): void
    {
        $aircraft = $this->findActiveAircraft();
        $pilot = $this->findActivePilot();

        if (empty($aircraft) || empty($pilot)) {
            $this->markTestSkipped('Missing active aircraft or pilot for reservation test');
        }

        $data = array(
            'aircraft_id' => $aircraft,
            'pilot_member_id' => $pilot,
            'start_datetime' => date('Y-m-d H:i:s', strtotime('+2 days 10:00:00')),
            'end_datetime' => date('Y-m-d H:i:s', strtotime('+2 days 11:00:00')),
            'purpose' => 'Lot0 create audit test',
            'status' => 'reservation'
        );

        $id = $this->reservations_model->create_reservation($data);
        $this->assertGreaterThan(0, $id, 'Reservation should be created');

        $row = $this->ci->db->where('id', $id)->get('reservations')->row_array();

        $this->assertNotEmpty($row['created_at'], 'created_at should be auto-populated');
        $this->assertNotEmpty($row['updated_at'], 'updated_at should be auto-populated');
        $this->assertEquals('test_user', $row['created_by'], 'created_by should use current user');
        $this->assertEquals('test_user', $row['updated_by'], 'updated_by should use current user on create');
    }

    /**
     * Regression test for the isset(FALSE) bug (Lot 5, see doc/plans/journalisation_crud_plan.md §2.3).
     *
     * CodeIgniter's $this->input->post($field) returns boolean FALSE (not NULL) for a
     * field that has no matching HTML input, which is exactly the case for audit columns
     * in every generic gvvmetadata form. Gvv_Controller::form2database() then puts that
     * FALSE straight into the data array passed to create()/update(). A plain isset()
     * check treats FALSE as "present" and silently skips the auto-population.
     */
    public function testCreateInjectsAuditFieldsWhenFalseValuesPresent(): void
    {
        $aircraft = $this->findActiveAircraft();
        $pilot = $this->findActivePilot();

        if (empty($aircraft) || empty($pilot)) {
            $this->markTestSkipped('Missing active aircraft or pilot for reservation test');
        }

        $data = array(
            'aircraft_id' => $aircraft,
            'pilot_member_id' => $pilot,
            'start_datetime' => date('Y-m-d H:i:s', strtotime('+4 days 10:00:00')),
            'end_datetime' => date('Y-m-d H:i:s', strtotime('+4 days 11:00:00')),
            'purpose' => 'Lot5 false-value create audit test',
            'status' => 'reservation',
            // What form2database() actually produces for fields absent from the HTML form.
            'created_at' => false,
            'created_by' => false,
            'updated_at' => false,
            'updated_by' => false,
        );

        // create_reservation() only fills created_by/section_id/status when the key is
        // absent (isset() check) — our explicit FALSE values survive through to create().
        $id = $this->reservations_model->create_reservation($data);
        $this->assertGreaterThan(0, $id, 'Reservation should be created');

        $row = $this->ci->db->where('id', $id)->get('reservations')->row_array();

        $this->assertNotEmpty($row['created_at'], 'created_at should be auto-populated even when explicitly FALSE');
        $this->assertNotEquals('0000-00-00 00:00:00', $row['created_at']);
        $this->assertEquals('test_user', $row['created_by'], 'created_by should be auto-populated even when explicitly FALSE');
        $this->assertNotEmpty($row['updated_at']);
        $this->assertEquals('test_user', $row['updated_by']);
    }

    public function testUpdateInjectsAuditFieldsWhenFalseValuesPresent(): void
    {
        $aircraft = $this->findActiveAircraft();
        $pilot = $this->findActivePilot();

        if (empty($aircraft) || empty($pilot)) {
            $this->markTestSkipped('Missing active aircraft or pilot for reservation test');
        }

        $data = array(
            'aircraft_id' => $aircraft,
            'pilot_member_id' => $pilot,
            'start_datetime' => date('Y-m-d H:i:s', strtotime('+5 days 10:00:00')),
            'end_datetime' => date('Y-m-d H:i:s', strtotime('+5 days 11:00:00')),
            'purpose' => 'Lot5 false-value update audit test',
            'status' => 'reservation'
        );
        $id = $this->reservations_model->create_reservation($data);
        $this->assertGreaterThan(0, $id, 'Reservation should be created');

        $old_timestamp = '2000-01-01 00:00:00';
        $this->ci->db->where('id', $id)->update('reservations', array(
            'updated_at' => $old_timestamp,
            'updated_by' => 'legacy_user'
        ));

        $this->reservations_model->update('id', array(
            'id' => $id,
            'purpose' => 'Lot5 false-value update audit test changed',
            'updated_at' => false,
            'updated_by' => false,
        ), $id);

        $row = $this->ci->db->where('id', $id)->get('reservations')->row_array();

        $this->assertNotEquals($old_timestamp, $row['updated_at'], 'updated_at should be refreshed even when explicitly FALSE');
        $this->assertEquals('test_user', $row['updated_by'], 'updated_by should be set even when explicitly FALSE');
    }

    public function testUpdateInjectsUpdatedAtWhenMissing(): void
    {
        $aircraft = $this->findActiveAircraft();
        $pilot = $this->findActivePilot();

        if (empty($aircraft) || empty($pilot)) {
            $this->markTestSkipped('Missing active aircraft or pilot for reservation test');
        }

        $data = array(
            'aircraft_id' => $aircraft,
            'pilot_member_id' => $pilot,
            'start_datetime' => date('Y-m-d H:i:s', strtotime('+3 days 10:00:00')),
            'end_datetime' => date('Y-m-d H:i:s', strtotime('+3 days 11:00:00')),
            'purpose' => 'Lot0 update audit test',
            'status' => 'reservation'
        );

        $id = $this->reservations_model->create_reservation($data);
        $this->assertGreaterThan(0, $id, 'Reservation should be created');

        $old_timestamp = '2000-01-01 00:00:00';
        $this->ci->db->where('id', $id)->update('reservations', array(
            'updated_at' => $old_timestamp,
            'updated_by' => 'legacy_user'
        ));

        $this->reservations_model->update('id', array(
            'id' => $id,
            'purpose' => 'Lot0 update audit test changed'
        ), $id);

        $row = $this->ci->db->where('id', $id)->get('reservations')->row_array();

        $this->assertNotEquals($old_timestamp, $row['updated_at'], 'updated_at should be refreshed by Common_Model');
        $this->assertEquals('test_user', $row['updated_by'], 'updated_by should be set by current model flow');
        $this->assertEquals('Lot0 update audit test changed', $row['purpose']);
    }

    public function testCreateUpdateDeleteStillWorkOnTableWithoutAuditColumns(): void
    {
        $key = 'lot0_common_model_' . uniqid();

        $id = $this->configuration_model->create(array(
            'cle' => $key,
            'valeur' => 'v1',
            'description' => 'Lot 0 non audit table check',
            'lang' => 'fr',
            'categorie' => 'tests',
            'club' => null
        ));

        $this->assertGreaterThan(0, $id, 'Create should still work on non-audit table');

        $this->configuration_model->update('id', array(
            'id' => $id,
            'valeur' => 'v2'
        ), $id);

        $row = $this->ci->db->where('id', $id)->get('configuration')->row_array();
        $this->assertEquals('v2', $row['valeur'], 'Update should still work on non-audit table');

        $this->configuration_model->delete(array('id' => $id));

        $count = $this->ci->db->where('id', $id)->from('configuration')->count_all_results();
        $this->assertEquals(0, $count, 'Delete should still work on non-audit table');
    }

    private function findActiveAircraft()
    {
        $row = $this->ci->db
            ->select('macimmat')
            ->from('machinesa')
            ->where('actif', 1)
            ->limit(1)
            ->get()
            ->row_array();

        return isset($row['macimmat']) ? $row['macimmat'] : null;
    }

    private function findActivePilot()
    {
        $row = $this->ci->db
            ->select('mlogin')
            ->from('membres')
            ->where('actif', 1)
            ->limit(1)
            ->get()
            ->row_array();

        return isset($row['mlogin']) ? $row['mlogin'] : null;
    }
}
