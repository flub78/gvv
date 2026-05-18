<?php

require_once(__DIR__ . '/../integration/TransactionalTestCase.php');

/**
 * Tests for the reservation balance and cotisation checks.
 *
 * Covers the logic introduced in reservations.php for auto_planchiste and
 * propriétaire users:
 *  - Cotisation check  : pilot must have a licences row (type=0) for the year
 *  - Balance check     : pilot balance must cover (existing + new) hours × rate
 *  - Owner rate        : if machinesa.proprio = pilot, maprixproprio is used
 *  - Double-command    : maprixdc is added for the new reservation if instructor set
 *  - Exemptions        : club-admin and instructeur bypass all checks
 *
 * All DB writes are wrapped in a transaction rolled back in tearDown().
 *
 * Test users (from bin/create_test_users.sh):
 *   goudurix      — auto_planchiste in Avion section (new auth)
 *   abraracourcix — instructeur in Avion section (new auth)
 *   panoramix     — club-admin (new auth)
 */
class ReservationsBalanceCheckTest extends TransactionalTestCase
{
    private $section_id;
    private $aircraft_id  = 'ZZ-TST';   // Unique test aircraft immat
    private $pilot        = 'goudurix'; // auto_planchiste (Avion section)
    private $other_pilot  = 'asterix';  // regular user, not auto_planchiste
    private $tarif_ref    = 'zz_test_tarif_50eur';
    private $tarif_dc_ref = 'zz_test_tarif_dc_10eur';
    private $tarif_own_ref = 'zz_test_tarif_proprio_20eur';
    private $compte_id;
    private $general_compte_id; // compte2 for ecritures (general account)

    protected function setUp(): void
    {
        parent::setUp();

        $this->CI->load->model('licences_model');
        $this->CI->load->model('ecritures_model');
        $this->CI->load->model('comptes_model');

        // Use first available section
        $row = $this->CI->db->select('id')->from('sections')->limit(1)->get()->row_array();
        $this->section_id = $row ? (int)$row['id'] : 1;

        // Insert three tarifs for this section
        foreach ([
            [$this->tarif_ref,    50.00, 'Test tarif 50€/h'],
            [$this->tarif_dc_ref, 10.00, 'Test DC tarif 10€/h'],
            [$this->tarif_own_ref, 20.00, 'Test proprio tarif 20€/h'],
        ] as [$ref, $prix, $desc]) {
            $this->CI->db->insert('tarifs', [
                'reference'    => $ref,
                'date'         => '2020-01-01',
                'prix'         => $prix,
                'description'  => $desc,
                'compte'       => 0,
                'saisie_par'   => 'test',
                'club'         => $this->section_id,
                'nb_personnes_max' => 1,
                'nb_tickets'   => 0,
                'is_cotisation' => 0,
                'public'       => 0,
            ]);
        }

        // Insert test aircraft (no owner by default)
        $this->CI->db->insert('machinesa', [
            'macimmat'      => $this->aircraft_id,
            'macconstruc'   => 'TestCo',
            'macmodele'     => 'TestModel',
            'macnbhdv'      => 0,
            'maprix'        => $this->tarif_ref,
            'maprixdc'      => $this->tarif_dc_ref,
            'maprixproprio' => $this->tarif_own_ref,
            'maprive'       => 0,
            'club'          => $this->section_id,
            'actif'         => 1,
        ]);

        // Ensure pilot has a compte (reuse existing or create a temporary one)
        $existing = $this->CI->db
            ->where('pilote', $this->pilot)
            ->where('codec', 411)
            ->where('club', $this->section_id)
            ->get('comptes')->row_array();

        if ($existing) {
            $this->compte_id = (int)$existing['id'];
        } else {
            $this->CI->db->insert('comptes', [
                'nom'       => 'Test compte ' . $this->pilot,
                'pilote'    => $this->pilot,
                'codec'     => 411,
                'actif'     => 1,
                'debit'     => 0,
                'credit'    => 0,
                'club'      => $this->section_id,
                'saisie_par' => 'test',
            ]);
            $this->compte_id = (int)$this->CI->db->insert_id();
        }

        // General account (compte2) for double-entry ecritures
        $gen = $this->CI->db
            ->where('codec', 706)
            ->where('club', $this->section_id)
            ->get('comptes')->row_array();

        if ($gen) {
            $this->general_compte_id = (int)$gen['id'];
        } else {
            $this->CI->db->insert('comptes', [
                'nom'       => 'Produits test',
                'pilote'    => null,
                'codec'     => 706,
                'actif'     => 1,
                'debit'     => 0,
                'credit'    => 0,
                'club'      => $this->section_id,
                'saisie_par' => 'test',
            ]);
            $this->general_compte_id = (int)$this->CI->db->insert_id();
        }

        // Ensure membres row for pilot exists (needed for membres.compte FK)
        $m = $this->CI->db->get_where('membres', ['mlogin' => $this->pilot])->row_array();
        if ($m) {
            // Update compte link to our test compte
            $this->CI->db->update('membres', ['compte' => $this->compte_id], ['mlogin' => $this->pilot]);
        }
    }

    // ------------------------------------------------------------------ //
    //  Helper: credit the pilot's account by $amount
    // ------------------------------------------------------------------ //
    private function _credit_account($amount)
    {
        $year = (int)date('Y');
        $this->CI->db->insert('ecritures', [
            'annee_exercise' => $year,
            'date_creation'  => date('Y-m-d'),
            'date_op'        => date('Y-m-d'),
            'compte1'        => $this->general_compte_id,
            'compte2'        => $this->compte_id,
            'montant'        => $amount,
            'description'    => 'Test credit',
            'saisie_par'     => 'test',
            'club'           => $this->section_id,
            'categorie'      => 0,
        ]);
    }

    // ------------------------------------------------------------------ //
    //  Helper: create a future reservation for the pilot on the test aircraft
    // ------------------------------------------------------------------ //
    private function _create_future_reservation($hours, $pilot = null)
    {
        $pilot = $pilot ?: $this->pilot;
        $start = date('Y-m-d H:i:s', strtotime('+7 days 10:00:00'));
        $end   = date('Y-m-d H:i:s', strtotime("+7 days " . (10 + $hours) . ":00:00"));
        $this->CI->db->insert('reservations', [
            'aircraft_id'          => $this->aircraft_id,
            'pilot_member_id'      => $pilot,
            'instructor_member_id' => null,
            'start_datetime'       => $start,
            'end_datetime'         => $end,
            'status'               => 'reservation',
            'section_id'           => $this->section_id,
            'created_by'           => 'test',
        ]);
        return $this->CI->db->insert_id();
    }

    // ------------------------------------------------------------------ //
    //  Helper: add a cotisation (licences row type=0) for the pilot
    // ------------------------------------------------------------------ //
    private function _add_cotisation($year = null)
    {
        $year = $year ?: (int)date('Y');
        $this->CI->db->insert('licences', [
            'pilote'  => $this->pilot,
            'type'    => 0,
            'year'    => $year,
            'date'    => date('Y-m-d'),
            'comment' => 'test cotisation',
        ]);
    }

    // ------------------------------------------------------------------ //
    //  Helper: replicate _check_pilot_balance() query logic
    // ------------------------------------------------------------------ //
    private function _check_balance($new_hours, $with_instructor = false)
    {
        // 1. Aircraft info
        $aircraft = $this->CI->db->get_where('machinesa', ['macimmat' => $this->aircraft_id])->row_array();
        $is_owner = !empty($aircraft['proprio']) && $aircraft['proprio'] === $this->pilot;
        $price_ref = ($is_owner && !empty($aircraft['maprixproprio']))
            ? $aircraft['maprixproprio']
            : $aircraft['maprix'];

        // 2. Tarif price
        $date = date('Y-m-d');
        $tarif = $this->CI->db
            ->select('prix')->from('tarifs')
            ->where('reference', $price_ref)
            ->where('date <=', $date)
            ->where('club', $this->section_id)
            ->order_by('date', 'desc')->limit(1)->get()->row_array();
        $hourly_rate = $tarif ? (float)$tarif['prix'] : 0.0;

        if ($hourly_rate <= 0.0) {
            return ['ok' => true];
        }

        // 3. Existing future reservations
        $now = date('Y-m-d H:i:s');
        $existing = $this->CI->db
            ->select('start_datetime, end_datetime')->from('reservations')
            ->where('pilot_member_id', $this->pilot)
            ->where('aircraft_id', $this->aircraft_id)
            ->where('start_datetime >', $now)
            ->where('status', 'reservation')
            ->get()->result_array();

        $existing_hours = 0.0;
        foreach ($existing as $r) {
            $s = new DateTime($r['start_datetime']);
            $e = new DateTime($r['end_datetime']);
            $existing_hours += ($e->getTimestamp() - $s->getTimestamp()) / 3600.0;
        }

        $total_cost = ($existing_hours + $new_hours) * $hourly_rate;

        // 4. DC cost
        if ($with_instructor) {
            $dc_tarif = $this->CI->db
                ->select('prix')->from('tarifs')
                ->where('reference', $aircraft['maprixdc'])
                ->where('date <=', $date)
                ->where('club', $this->section_id)
                ->order_by('date', 'desc')->limit(1)->get()->row_array();
            $dc_rate = $dc_tarif ? (float)$dc_tarif['prix'] : 0.0;
            $total_cost += $new_hours * $dc_rate;
        }

        // 5. Balance — look up compte directly, matching the controller fix
        $this->CI->load->helper('validation');
        $compte = $this->CI->db->get_where('comptes', [
            'pilote' => $this->pilot,
            'codec'  => '411',
            'club'   => $this->section_id,
            'actif'  => 1,
        ])->row_array();
        if (empty($compte)) {
            return ['ok' => true];
        }
        $balance = (float)$this->CI->ecritures_model->solde_compte($compte['id']);

        return $balance >= $total_cost
            ? ['ok' => true]
            : ['ok' => false, 'balance' => $balance, 'cost' => $total_cost];
    }

    // ================================================================== //
    //  Tests: Cotisation check
    // ================================================================== //

    public function testCotisationMissingBlocksReservation()
    {
        $year = (int)date('Y');
        // Ensure no cotisation for current year
        $this->CI->db->delete('licences', ['pilote' => $this->pilot, 'year' => $year, 'type' => 0]);

        $has = $this->CI->licences_model->check_cotisation_exists($this->pilot, $year);
        $this->assertFalse($has, 'Pilot without cotisation should be blocked');
    }

    public function testCotisationPresentAllowsReservation()
    {
        $year = (int)date('Y');
        $this->CI->db->delete('licences', ['pilote' => $this->pilot, 'year' => $year, 'type' => 0]);
        $this->_add_cotisation($year);

        $has = $this->CI->licences_model->check_cotisation_exists($this->pilot, $year);
        $this->assertTrue($has, 'Pilot with cotisation should be allowed');
    }

    public function testCotisationForWrongYearDoesNotCount()
    {
        $year = (int)date('Y');
        $this->CI->db->delete('licences', ['pilote' => $this->pilot, 'year' => $year, 'type' => 0]);
        $this->_add_cotisation($year - 1); // Previous year

        $has = $this->CI->licences_model->check_cotisation_exists($this->pilot, $year);
        $this->assertFalse($has, 'Cotisation for previous year should not count for current year');
    }

    // ================================================================== //
    //  Tests: Balance check — basic cases
    // ================================================================== //

    public function testSufficientBalanceAllowsReservation()
    {
        // Credit 200€, new reservation = 2h × 50€ = 100€ → allowed
        $this->_credit_account(200.00);
        $result = $this->_check_balance(2.0);
        $this->assertTrue($result['ok'], 'Should allow reservation when balance is sufficient');
    }

    public function testInsufficientBalanceBlocksReservation()
    {
        // Credit 50€, new reservation = 2h × 50€ = 100€ → blocked
        $this->_credit_account(50.00);
        $result = $this->_check_balance(2.0);
        $this->assertFalse($result['ok'], 'Should block reservation when balance is insufficient');
        $this->assertArrayHasKey('balance', $result);
        $this->assertArrayHasKey('cost', $result);
        $this->assertEquals(50.00, $result['balance'], 'Balance in error must match account balance');
        $this->assertEquals(100.00, $result['cost'], 'Cost must be 2h × 50€');
    }

    public function testZeroBalanceBlocksReservation()
    {
        // No credit → 0€ balance, any reservation with cost > 0 is blocked
        $result = $this->_check_balance(1.0);
        $this->assertFalse($result['ok'], 'Should block reservation with zero balance');
    }

    // ================================================================== //
    //  Tests: Balance check — existing reservations accumulate
    // ================================================================== //

    public function testExistingReservationsAddedToNewCost()
    {
        // Existing: 1h (50€). New: 1h (50€). Total: 100€. Balance: 80€ → blocked.
        $this->_credit_account(80.00);
        $this->_create_future_reservation(1.0); // 1h existing

        $result = $this->_check_balance(1.0); // 1h new
        $this->assertFalse($result['ok'], 'Existing + new hours must be combined');
        $this->assertEqualsWithDelta(100.00, $result['cost'], 0.01, 'Cost must be (1+1)h × 50€');
    }

    public function testExactBalanceSufficient()
    {
        // Existing: 1h (50€). New: 1h (50€). Total: 100€. Balance: 100€ → allowed.
        $this->_credit_account(100.00);
        $this->_create_future_reservation(1.0);

        $result = $this->_check_balance(1.0);
        $this->assertTrue($result['ok'], 'Exact balance should be sufficient (>=)');
    }

    public function testReservationsOfOtherPilotNotCounted()
    {
        // Other pilot's reservation on the same aircraft must not affect this pilot's check
        $this->_credit_account(60.00);
        $this->_create_future_reservation(10.0, $this->other_pilot); // Another pilot's reservation

        $result = $this->_check_balance(1.0); // 1h × 50€ = 50€, balance = 60€
        $this->assertTrue($result['ok'], "Other pilot's reservations must not count against this pilot");
    }

    // ================================================================== //
    //  Tests: Double-command surcharge
    // ================================================================== //

    public function testDoublCommandAddedWhenInstructor()
    {
        // New: 2h × (50 + 10)€ = 120€. Balance: 110€ → blocked.
        $this->_credit_account(110.00);
        $result = $this->_check_balance(2.0, true);
        $this->assertFalse($result['ok'], 'DC surcharge must push total over balance');
        $this->assertEqualsWithDelta(120.00, $result['cost'], 0.01, 'Cost = 2h × 50€ + 2h × 10€ DC');
    }

    public function testDoublCommandNotAddedWithoutInstructor()
    {
        // 2h × 50€ = 100€. Balance: 110€. No DC → allowed.
        $this->_credit_account(110.00);
        $result = $this->_check_balance(2.0, false);
        $this->assertTrue($result['ok'], 'Without instructor, no DC surcharge');
    }

    // ================================================================== //
    //  Tests: Owner rate (maprixproprio)
    // ================================================================== //

    public function testOwnerUsesProprioRate()
    {
        // Set pilot as owner of aircraft
        $this->CI->db->update('machinesa', ['proprio' => $this->pilot], ['macimmat' => $this->aircraft_id]);

        // 2h × 20€ (proprio rate) = 40€. Balance: 35€ → blocked.
        $this->_credit_account(35.00);
        $result = $this->_check_balance(2.0);
        $this->assertFalse($result['ok'], 'Owner should use proprio rate');
        $this->assertEqualsWithDelta(40.00, $result['cost'], 0.01, 'Cost = 2h × 20€ (proprio rate)');
    }

    public function testOwnerWithSufficientBalanceAllowed()
    {
        $this->CI->db->update('machinesa', ['proprio' => $this->pilot], ['macimmat' => $this->aircraft_id]);

        // 2h × 20€ = 40€. Balance: 50€ → allowed.
        $this->_credit_account(50.00);
        $result = $this->_check_balance(2.0);
        $this->assertTrue($result['ok'], 'Owner with sufficient balance (proprio rate) must be allowed');
    }

    public function testNonOwnerUsesRegularRate()
    {
        // No proprio set → regular rate 50€
        $this->_credit_account(90.00);
        $result = $this->_check_balance(2.0); // 2h × 50€ = 100€, balance 90€ → blocked
        $this->assertFalse($result['ok'], 'Non-owner must use regular rate (50€)');
        $this->assertEqualsWithDelta(100.00, $result['cost'], 0.01);
    }

    // ================================================================== //
    //  Tests: Aircraft with no price (free) → skip check
    // ================================================================== //

    public function testFreeAircraftSkipsBalanceCheck()
    {
        // Aircraft with no matching tarif reference → hourly_rate = 0 → check skipped
        // CI converts '' to NULL; use a non-existent reference instead
        $this->CI->db->update('machinesa', ['maprix' => '__no_tarif__'], ['macimmat' => $this->aircraft_id]);

        $result = $this->_check_balance(100.0); // Even huge reservation
        $this->assertTrue($result['ok'], 'Free aircraft (no tarif) must always pass balance check');
    }

    // ================================================================== //
    //  Tests: machinesa.proprio column exists (migration 113)
    // ================================================================== //

    public function testProprioColumnExistsInMachinesa()
    {
        $this->assertTrue(
            $this->CI->db->field_exists('proprio', 'machinesa'),
            'machinesa must have a proprio column (migration 113)'
        );
    }

    public function testProprioColumnAcceptsLoginValue()
    {
        $this->CI->db->update('machinesa', ['proprio' => $this->pilot], ['macimmat' => $this->aircraft_id]);
        $row = $this->CI->db->get_where('machinesa', ['macimmat' => $this->aircraft_id])->row_array();
        $this->assertEquals($this->pilot, $row['proprio'], 'proprio column must store the pilot login');
    }

    public function testProprioColumnAcceptsNull()
    {
        $this->CI->db->update('machinesa', ['proprio' => null], ['macimmat' => $this->aircraft_id]);
        $row = $this->CI->db->get_where('machinesa', ['macimmat' => $this->aircraft_id])->row_array();
        $this->assertNull($row['proprio'], 'proprio column must accept NULL for club aircraft');
    }

    // ================================================================== //
    //  Tests: Controller access control structure
    // ================================================================== //

    public function testControllerHasReservationPermissionsHelper()
    {
        $src = file_get_contents(APPPATH . 'controllers/reservations.php');
        $this->assertStringContainsString(
            '_reservation_permissions',
            $src,
            'Controller must have _reservation_permissions() helper'
        );
    }

    public function testControllerEnforcesCotisationForAutoPlanchiste()
    {
        $src = file_get_contents(APPPATH . 'controllers/reservations.php');
        $this->assertStringContainsString(
            'check_cotisation_exists',
            $src,
            'Controller must call check_cotisation_exists for auto_planchiste'
        );
        $this->assertStringContainsString(
            'reservations_error_no_cotisation',
            $src,
            'Controller must throw cotisation error when cotisation is missing'
        );
    }

    public function testControllerEnforcesBalanceCheck()
    {
        $src = file_get_contents(APPPATH . 'controllers/reservations.php');
        $this->assertStringContainsString(
            '_check_pilot_balance',
            $src,
            'Controller must call _check_pilot_balance()'
        );
        $this->assertStringContainsString(
            'reservations_error_insufficient_balance',
            $src,
            'Controller must throw insufficient balance error'
        );
    }

    public function testControllerEnforcesOwnershipOnEdit()
    {
        $src = file_get_contents(APPPATH . 'controllers/reservations.php');
        $this->assertStringContainsString(
            'reservations_error_not_authorized',
            $src,
            'Controller must throw not-authorized error for unauthorized edits'
        );
    }

    public function testControllerSkipsChecksForAdminAndInstructor()
    {
        $src = file_get_contents(APPPATH . 'controllers/reservations.php');
        // The key pattern: checks only run when !$can_edit_others
        $this->assertStringContainsString(
            '!$can_edit_others',
            $src,
            'Checks must be skipped when user can_edit_others (admin/instructeur)'
        );
    }
}

/* End of file ReservationsBalanceCheckTest.php */
/* Location: ./application/tests/mysql/ReservationsBalanceCheckTest.php */
