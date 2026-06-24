<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de la logique de continuité horamètre des carnets de route.
 *
 * Teste build_continuity_rows() et compute_continuity_summary() en isolation,
 * sans base de données ni stack CodeIgniter.
 */
class CarnetRouteContinuiteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../../helpers/carnets_route_helper.php';
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function flight($vacdeb, $vacfin, $date = '2026-01-01', $pilote = 'Dupont Jean') {
        return [
            'vaid'       => rand(1, 9999),
            'vadate'     => $date,
            'vapilid'    => 'jdupont',
            'pilote'     => $pilote,
            'vamacid'    => 'F-GSRP',
            'vacdeb'     => $vacdeb,
            'vacfin'     => $vacfin,
            'vaduree'    => round($vacfin - $vacdeb, 2),
            'valieudeco' => 'LFOI',
            'valieuatt'  => 'LFOI',
            'vaobs'      => '',
        ];
    }

    private function rowTypes($rows) {
        return array_column($rows, 'type');
    }

    private function flightStatuses($rows) {
        return array_map(function ($r) {
            return $r['type'] === 'flight' ? $r['data']['status'] : null;
        }, array_filter($rows, fn($r) => $r['type'] === 'flight'));
    }

    // ── Cas de base ────────────────────────────────────────────────────────────

    public function testEmptyInputReturnsEmptyArray() {
        $this->assertSame([], build_continuity_rows([]));
    }

    public function testSingleFlightWithValidHorametreIsOk() {
        $rows = build_continuity_rows([$this->flight(100.00, 101.50)]);

        $this->assertCount(1, $rows);
        $this->assertEquals('flight', $rows[0]['type']);
        $this->assertEquals('ok', $rows[0]['data']['status']);
    }

    public function testSingleFlightWithMissingHorametreIsMissing() {
        $rows = build_continuity_rows([$this->flight(0.0, 0.0)]);

        $this->assertCount(1, $rows);
        $this->assertEquals('flight', $rows[0]['type']);
        $this->assertEquals('missing', $rows[0]['data']['status']);
    }

    // ── Continuité exacte ──────────────────────────────────────────────────────

    public function testTwoFlightsInExactContinuityAreBothOk() {
        $flights = [
            $this->flight(100.00, 101.50),
            $this->flight(101.50, 102.75),
        ];
        $rows = build_continuity_rows($flights);

        $this->assertCount(2, $rows);
        $this->assertEquals(['flight', 'flight'], $this->rowTypes($rows));
        $this->assertEquals('ok', $rows[0]['data']['status']);
        $this->assertEquals('ok', $rows[1]['data']['status']);
    }

    public function testContinuityWithFloatingPointTolerance() {
        // delta = 0.001 < 0.005 → doit être traité comme continuité exacte
        $flights = [
            $this->flight(100.00, 101.500),
            $this->flight(101.501, 102.75),
        ];
        $rows = build_continuity_rows($flights);

        $this->assertCount(2, $rows);
        $this->assertEquals('ok', $rows[0]['data']['status']);
        $this->assertEquals('ok', $rows[1]['data']['status']);
    }

    // ── Écart (gap) ────────────────────────────────────────────────────────────

    public function testTwoFlightsWithGapProducesIntermediateGapRow() {
        $flights = [
            $this->flight(100.00, 101.50),
            $this->flight(102.00, 103.25),
        ];
        $rows = build_continuity_rows($flights);

        $this->assertCount(3, $rows);
        $this->assertEquals(['flight', 'gap', 'flight'], $this->rowTypes($rows));
    }

    public function testGapFlightsAreMarkedError() {
        $flights = [
            $this->flight(100.00, 101.50),
            $this->flight(102.00, 103.25),
        ];
        $rows = build_continuity_rows($flights);

        $this->assertEquals('error', $rows[0]['data']['status']);
        $this->assertEquals('error', $rows[2]['data']['status']);
    }

    public function testGapDurationIsCorrect() {
        $flights = [
            $this->flight(100.00, 101.50),
            $this->flight(102.00, 103.25),
        ];
        $rows = build_continuity_rows($flights);

        $this->assertEqualsWithDelta(0.50, $rows[1]['duration'], 0.005);
    }

    // ── Recouvrement (overlap) ─────────────────────────────────────────────────

    public function testTwoFlightsWithOverlapProducesIntermediateOverlapRow() {
        $flights = [
            $this->flight(100.00, 102.00),
            $this->flight(101.50, 103.25),
        ];
        $rows = build_continuity_rows($flights);

        $this->assertCount(3, $rows);
        $this->assertEquals(['flight', 'overlap', 'flight'], $this->rowTypes($rows));
    }

    public function testOverlapFlightsAreMarkedError() {
        $flights = [
            $this->flight(100.00, 102.00),
            $this->flight(101.50, 103.25),
        ];
        $rows = build_continuity_rows($flights);

        $this->assertEquals('error', $rows[0]['data']['status']);
        $this->assertEquals('error', $rows[2]['data']['status']);
    }

    public function testOverlapDurationIsCorrect() {
        $flights = [
            $this->flight(100.00, 102.00),
            $this->flight(101.50, 103.25),
        ];
        $rows = build_continuity_rows($flights);

        $this->assertEqualsWithDelta(0.50, $rows[1]['duration'], 0.005);
    }

    // ── Horamètre manquant ─────────────────────────────────────────────────────

    public function testFlightWithMissingHorametreInSequenceInsertsMissingRow() {
        $flights = [
            $this->flight(100.00, 101.50),
            $this->flight(0.0,    0.0),    // manquant
            $this->flight(102.00, 103.25),
        ];
        $rows = build_continuity_rows($flights);

        // flight ok, missing row, flight missing, missing row, flight error
        $types = $this->rowTypes($rows);
        $this->assertContains('missing', $types);

        // Le vol avec horamètre 0 doit avoir le statut 'missing'
        $middleFlight = array_values(array_filter($rows, fn($r) => $r['type'] === 'flight'))[1];
        $this->assertEquals('missing', $middleFlight['data']['status']);
    }

    // ── Séquence longue avec anomalies entremêlées ─────────────────────────────

    public function testMixedSequenceProducesCorrectRowTypes() {
        $flights = [
            $this->flight(100.00, 101.50, '2026-01-01'), // ok → suivant en continuité
            $this->flight(101.50, 102.75, '2026-01-02'), // ok → gap avec suivant
            $this->flight(104.00, 105.00, '2026-01-03'), // ok → overlap avec suivant
            $this->flight(104.50, 106.00, '2026-01-04'), // ok → continuité avec suivant
            $this->flight(106.00, 107.00, '2026-01-05'), // ok
        ];
        $rows = build_continuity_rows($flights);

        $types = $this->rowTypes($rows);
        $this->assertContains('gap',     $types);
        $this->assertContains('overlap', $types);

        // Compter les vols
        $flightCount = count(array_filter($rows, fn($r) => $r['type'] === 'flight'));
        $this->assertEquals(5, $flightCount);
    }

    // ── Résumé ─────────────────────────────────────────────────────────────────

    public function testSummaryOnCleanSequenceIsAllZero() {
        $flights = [
            $this->flight(100.00, 101.50),
            $this->flight(101.50, 102.75),
        ];
        $rows    = build_continuity_rows($flights);
        $summary = compute_continuity_summary($rows);

        $this->assertEquals(0, $summary['gap']);
        $this->assertEquals(0, $summary['overlap']);
        $this->assertEquals(0, $summary['missing']);
    }

    public function testSummaryCountsEachAnomalyType() {
        $flights = [
            $this->flight(100.00, 101.50), // gap → suivant
            $this->flight(103.00, 104.00), // overlap → suivant
            $this->flight(103.50, 105.00), // ok → suivant
            $this->flight(105.00, 106.00),
        ];
        $rows    = build_continuity_rows($flights);
        $summary = compute_continuity_summary($rows);

        $this->assertEquals(1, $summary['gap']);
        $this->assertEquals(1, $summary['overlap']);
        $this->assertEquals(0, $summary['missing']);
    }

    public function testSummaryCountsMissingHorametre() {
        $flights = [
            $this->flight(100.00, 101.50),
            $this->flight(0.0, 0.0),
            $this->flight(102.00, 103.00),
        ];
        $rows    = build_continuity_rows($flights);
        $summary = compute_continuity_summary($rows);

        $this->assertEquals(0, $summary['gap']);
        $this->assertEquals(0, $summary['overlap']);
        $this->assertEquals(2, $summary['missing']); // une ligne manquant de chaque côté du vol à 0
    }
}
