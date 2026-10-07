<?php
/**
 * Contrôle de la date de gel lors de la modification d'une écriture :
 * - une écriture d'une période clôturée ne peut pas en être extraite ;
 * - une écriture ne peut pas être déplacée dans une période clôturée ;
 * - une écriture postérieure à la date de gel reste modifiable.
 *
 * La date de gel et les écritures de test sont créées dans une transaction
 * annulée en fin de test : la base n'est pas modifiée.
 */

require_once(__DIR__ . '/TransactionalTestCase.php');

class EcritureUpdateFreezeDateTest extends TransactionalTestCase {

    const FREEZE_DATE = '2099-06-30';

    private $section_id;
    private $closed_id;
    private $open_id;

    protected function setUp(): void {
        parent::setUp();
        $this->CI->load->model('ecritures_model');
        $this->CI->load->model('clotures_model');

        $section = $this->CI->db->select('id')->from('sections')->order_by('id')->limit(1)->get()->row();
        if (!$section) {
            $this->markTestSkipped('Aucune section en base');
        }
        $this->section_id = $section->id;

        $comptes = $this->CI->db->select('id')->from('comptes')->where('club', $this->section_id)
            ->limit(2)->get()->result_array();
        if (count($comptes) < 2) {
            $this->markTestSkipped("Pas assez de comptes dans la section {$this->section_id}");
        }

        // Date de gel postérieure à toute clôture réelle : elle devient la date de gel de la section
        $this->CI->db->insert('clotures', [
            'section' => $this->section_id,
            'date' => self::FREEZE_DATE,
            'description' => 'PHPUnit EcritureUpdateFreezeDateTest',
        ]);

        $this->closed_id = $this->insert_ecriture('2099-06-01', $comptes);
        $this->open_id = $this->insert_ecriture('2099-07-15', $comptes);
    }

    private function insert_ecriture($date_op, $comptes) {
        $this->CI->db->insert('ecritures', [
            'annee_exercise' => 2099,
            'date_creation' => date('Y-m-d'),
            'date_op' => $date_op,
            'compte1' => $comptes[0]['id'],
            'compte2' => $comptes[1]['id'],
            'montant' => 1.00,
            'description' => 'PHPUnit date de gel',
            'saisie_par' => 'phpunit',
            'gel' => 0,
            'club' => $this->section_id,
        ]);
        return $this->CI->db->insert_id();
    }

    public function test_freeze_date_is_the_test_one() {
        $this->assertSame(self::FREEZE_DATE, $this->CI->clotures_model->freeze_date(false, $this->section_id));
    }

    // --- Cas acceptés ---

    public function test_accepts_moving_open_entry_to_later_date() {
        $this->assertSame('', $this->CI->ecritures_model->update_freeze_date_violation($this->open_id, '2099-08-01'));
    }

    public function test_accepts_moving_open_entry_to_day_after_freeze_date() {
        $this->assertSame('', $this->CI->ecritures_model->update_freeze_date_violation($this->open_id, '2099-07-01'));
    }

    public function test_accepts_open_entry_with_unchanged_date() {
        $this->assertSame('', $this->CI->ecritures_model->update_freeze_date_violation($this->open_id, '2099-07-15'));
    }

    public function test_accepts_anything_without_freeze_date() {
        $this->assertSame('', Ecritures_model::freeze_date_update_violation('', '2020-01-01', '2020-02-01'));
    }

    // --- Cas rejetés : extraction d'une écriture clôturée ---

    public function test_rejects_extracting_closed_entry_after_freeze_date() {
        $this->assertSame('previous_before_freeze_date',
            $this->CI->ecritures_model->update_freeze_date_violation($this->closed_id, '2099-08-01'));
    }

    public function test_rejects_modifying_closed_entry_without_changing_date() {
        $this->assertSame('previous_before_freeze_date',
            $this->CI->ecritures_model->update_freeze_date_violation($this->closed_id, '2099-06-01'));
    }

    public function test_rejects_entry_dated_on_freeze_date() {
        $this->assertSame('previous_before_freeze_date',
            Ecritures_model::freeze_date_update_violation(self::FREEZE_DATE, self::FREEZE_DATE, '2099-08-01'));
    }

    // --- Cas rejetés : insertion dans une période clôturée ---

    public function test_rejects_moving_open_entry_before_freeze_date() {
        $this->assertSame('new_before_freeze_date',
            $this->CI->ecritures_model->update_freeze_date_violation($this->open_id, '2099-06-15'));
    }

    public function test_rejects_moving_open_entry_on_freeze_date() {
        $this->assertSame('new_before_freeze_date',
            $this->CI->ecritures_model->update_freeze_date_violation($this->open_id, self::FREEZE_DATE));
    }

    // --- Cas rejetés : données invalides ---

    public function test_rejects_unknown_entry() {
        $this->assertSame('entry_not_found', $this->CI->ecritures_model->update_freeze_date_violation(-1, '2099-08-01'));
    }

    public function test_rejects_non_database_date_format() {
        $this->assertSame('invalid_date_format',
            Ecritures_model::freeze_date_update_violation(self::FREEZE_DATE, '2099-07-15', '01/08/2099'));
    }
}
