<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests MySQL pour les horamètres des vols avion.
 *
 * Vérifie les trois modes d'horamètre :
 *   MODE 0 (centième) : entrée HH.CC (0-99), stockage tel quel
 *   MODE 1 (minutes)  : entrée HH.MM (0-59), stockage en centièmes via to_hundredth
 *   MODE 2 (dixième)  : entrée HH.D  (0-9),  stockage tel quel
 *
 * Invariants testés :
 *   - Le stockage en base est toujours en heures + centièmes (HH.CC).
 *   - L'utilisateur retrouve la valeur qu'il a saisie lors de l'édition.
 *   - Le pré-remplissage utilise le dernier vacfin de la machine.
 *   - La modification d'un vol préserve les invariants (valeur stockée inchangée).
 */
class VolsAvionHorametreTest extends TestCase
{
    /** @var CI_Controller */
    private $CI;

    /** @var Vols_avion_model */
    private $model;

    /** @var string Immatriculation d'un avion de test, mode centième */
    private $machine_cent;

    /** @var string Immatriculation d'un avion de test, mode minutes */
    private $machine_min;

    /** @var string Immatriculation d'un avion de test, mode dixième */
    private $machine_dix;

    /** @var string Login d'un pilote de test */
    private $test_pilot;

    /** @var string Date de test sans risque de conflit */
    private $test_date = '2099-06-23';

    /** @var int[] IDs des vols créés à nettoyer */
    private $created_vaid = array();

    /** @var string[] Immatriculations créées à nettoyer */
    private $created_machines = array();

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        $this->CI->load->model('vols_avion_model');
        $this->model = $this->CI->vols_avion_model;

        // Charger le helper pour horametre_display / centieme_to_hhmm
        if (!function_exists('horametre_display')) {
            $this->CI->load->helper('validation');
        }

        // Trouver un pilote de test
        $member = $this->CI->db
            ->select('mlogin')
            ->from('membres')
            ->where('actif', 1)
            ->limit(1)
            ->get()
            ->row_array();

        if (empty($member)) {
            $this->markTestSkipped('Aucun membre actif trouvé en base');
        }
        $this->test_pilot = $member['mlogin'];

        // Créer trois avions de test avec des modes d'horamètre différents
        $this->machine_cent = $this->createTestMachine('F-TEST0', 0); // centième
        $this->machine_min  = $this->createTestMachine('F-TEST1', 1); // minutes
        $this->machine_dix  = $this->createTestMachine('F-TEST2', 2); // dixième
    }

    protected function tearDown(): void
    {
        // Supprimer les vols de test
        foreach ($this->created_vaid as $vaid) {
            $this->CI->db->delete('volsa', array('vaid' => $vaid));
        }
        // Supprimer les machines de test
        foreach ($this->created_machines as $macimmat) {
            $this->CI->db->delete('machinesa', array('macimmat' => $macimmat));
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createTestMachine($immat, $horametre_mode)
    {
        // Supprimer l'éventuelle machine existante avec cette immat
        $this->CI->db->delete('machinesa', array('macimmat' => $immat));

        $section = $this->model->section_id();
        $this->CI->db->insert('machinesa', array(
            'macimmat'       => $immat,
            'macconstruc'    => 'TestConstructeur',
            'macmodele'      => "TestMode$horametre_mode",
            'macplaces'      => 1,
            'maprix'         => '0',
            'maprive'        => 0,
            'horametre_mode' => $horametre_mode,
            'actif'          => 1,
            'club'           => $section,
        ));
        $this->created_machines[] = $immat;
        return $immat;
    }

    /**
     * Insère un vol directement en base avec vacdeb/vacfin en centièmes.
     */
    private function insertFlight($machine, $vacdeb, $vacfin)
    {
        $section = $this->model->section_id();
        $this->CI->db->insert('volsa', array(
            'vadate'      => $this->test_date,
            'vapilid'     => $this->test_pilot,
            'vamacid'     => $machine,
            'vahdeb'      => 9.00,
            'vahfin'      => 10.00,
            'vacdeb'      => $vacdeb,
            'vacfin'      => $vacfin,
            'vaduree'     => round($vacfin - $vacdeb, 2),
            'vaobs'       => 'Test VolsAvionHorametre',
            'vadc'        => 0,
            'vacategorie' => 0,
            'vaatt'       => 1,
            'club'        => $section,
        ));
        $id = $this->CI->db->insert_id();
        if ($id > 0) {
            $this->created_vaid[] = $id;
        }
        return $id;
    }

    /**
     * Réplique la logique privée to_hundredth() du contrôleur vols_avion.
     * Convertit HH.MM en HH.CC (centièmes).
     */
    private function to_hundredth($hm)
    {
        $hours    = intval($hm);
        $minutes  = ($hm - $hours) * 100;
        $centiemes = $minutes / 60;
        return $hours + $centiemes;
    }

    /**
     * Réplique horametre_to_decimal_hours() : convertit l'entrée utilisateur
     * selon le mode en centièmes pour stockage.
     */
    private function to_decimal($value, $mode)
    {
        if ($mode == 1) {
            return $this->to_hundredth($value);
        }
        return floatval($value); // modes 0 et 2 : pas de conversion
    }

    // =========================================================================
    // Stockage en centièmes
    // =========================================================================

    /**
     * Mode 0 (centième) : la valeur saisie est stockée telle quelle en base.
     */
    public function testStorageModeCentiemePassthrough()
    {
        $input = 1.50; // 1h + 50 centièmes
        $stored_value = round($this->to_decimal($input, 0), 4);

        $vaid = $this->insertFlight($this->machine_cent, $stored_value, $stored_value + 0.50);
        $this->assertGreaterThan(0, $vaid);

        $row = $this->CI->db->where('vaid', $vaid)->get('volsa')->row_array();
        $this->assertEqualsWithDelta($stored_value, floatval($row['vacdeb']), 0.01,
            'Mode centième : vacdeb doit être stocké tel quel');
    }

    /**
     * Mode 1 (minutes) : 1.30 (1h30min) doit être stocké comme 1.50 centièmes.
     */
    public function testStorageModeMinutesConvertsToHundredths()
    {
        $user_input = 1.30; // l'utilisateur entre 1h30min
        $stored_value = round($this->to_decimal($user_input, 1), 4); // → 1.5

        $vaid = $this->insertFlight($this->machine_min, $stored_value, $stored_value + 0.50);
        $this->assertGreaterThan(0, $vaid);

        $row = $this->CI->db->where('vaid', $vaid)->get('volsa')->row_array();
        $this->assertEqualsWithDelta(1.50, floatval($row['vacdeb']), 0.01,
            'Mode minutes : 1.30 (1h30min) doit être stocké en 1.50 centièmes');
    }

    /**
     * Mode 1 (minutes) : 1.59 (1h59min) doit être stocké comme ≈1.98 centièmes.
     */
    public function testStorageModeMinutes59MinutesConvertsCorrectly()
    {
        $user_input = 1.59; // 1h59min
        $stored_value = round($this->to_decimal($user_input, 1), 2); // ≈ 1.98

        $vaid = $this->insertFlight($this->machine_min, $stored_value, $stored_value + 0.02);
        $this->assertGreaterThan(0, $vaid);

        $row = $this->CI->db->where('vaid', $vaid)->get('volsa')->row_array();
        $this->assertEqualsWithDelta(1.98, floatval($row['vacdeb']), 0.01,
            'Mode minutes : 1.59 (1h59min) doit être stocké en ≈1.98 centièmes');

        // Vérifier que la valeur stockée n'est pas restée en format minutes
        $this->assertNotEquals(1.59, floatval($row['vacdeb']),
            'La valeur 1.59 (format minutes) ne doit pas être stockée telle quelle');
    }

    /**
     * Mode 2 (dixième) : la valeur saisie est stockée telle quelle.
     */
    public function testStorageModeDixiemePassthrough()
    {
        $input = 1.5; // 1h + 5 dixièmes = 1h30min
        $stored_value = round($this->to_decimal($input, 2), 4);

        $vaid = $this->insertFlight($this->machine_dix, $stored_value, $stored_value + 0.5);
        $this->assertGreaterThan(0, $vaid);

        $row = $this->CI->db->where('vaid', $vaid)->get('volsa')->row_array();
        $this->assertEqualsWithDelta(1.5, floatval($row['vacdeb']), 0.01,
            'Mode dixième : vacdeb doit être stocké tel quel');
    }

    // =========================================================================
    // Round-trip : l'utilisateur retrouve la valeur saisie lors de l'édition
    // =========================================================================

    /**
     * Mode centième : la valeur stockée en base correspond à ce que l'utilisateur
     * a saisi. horametre_display en mode 0 retourne la valeur telle quelle.
     */
    public function testRoundTripModeCentieme()
    {
        $cases = [
            [1.00, '1.00 centième'],
            [1.50, '1.50 centième'],
            [1.99, '1.99 centième'],
            [100.25, '100.25 centième'],
        ];
        foreach ($cases as [$input, $label]) {
            $stored = round($this->to_decimal($input, 0), 4);
            $vaid = $this->insertFlight($this->machine_cent, $stored, $stored + 0.10);

            $row = $this->CI->db->where('vaid', $vaid)->get('volsa')->row_array();
            $retrieved = floatval($row['vacdeb']);
            $displayed = horametre_display($retrieved, 0);

            $this->assertEqualsWithDelta($input, $displayed, 0.01,
                "Round-trip mode centième : $label doit être retrouvé à l'édition");
        }
    }

    /**
     * Mode minutes : l'utilisateur entre HH.MM et retrouve "HH:MM" à l'édition.
     * Le widget JS réaffiche les centièmes stockés en format heures:minutes.
     * Ce test vérifie la même logique via centieme_to_hhmm (PHP).
     */
    public function testRoundTripModeMinutes()
    {
        $cases = [
            // [entrée HH.MM, centièmes stockés attendus, affichage HH:MM attendu]
            [1.00, 1.00,  '1:00'],
            [1.30, 1.50,  '1:30'],
            [1.45, 1.75,  '1:45'],
            [0.30, 0.50,  '0:30'],
        ];
        foreach ($cases as [$input, $expected_stored, $expected_display]) {
            $stored = round($this->to_decimal($input, 1), 2);
            $vaid = $this->insertFlight($this->machine_min, $stored, $stored + 0.25);

            $row = $this->CI->db->where('vaid', $vaid)->get('volsa')->row_array();
            $retrieved = floatval($row['vacdeb']);

            // Vérifier que la valeur stockée est en centièmes
            $this->assertEqualsWithDelta($expected_stored, $retrieved, 0.01,
                "Round-trip mode minutes : $input doit être stocké en $expected_stored centièmes");

            // Vérifier l'affichage à l'édition (conversion centièmes → HH:MM)
            $displayed = horametre_display($retrieved, 1);
            $this->assertEquals($expected_display, $displayed,
                "Round-trip mode minutes : $expected_stored centièmes doit s'afficher '$expected_display'");
        }
    }

    /**
     * Mode dixième : l'utilisateur entre HH.D et retrouve la même valeur à l'édition.
     */
    public function testRoundTripModeDixieme()
    {
        $cases = [1.0, 1.5, 1.9, 100.5];
        foreach ($cases as $input) {
            $stored = round($this->to_decimal($input, 2), 4);
            $vaid = $this->insertFlight($this->machine_dix, $stored, $stored + 0.5);

            $row = $this->CI->db->where('vaid', $vaid)->get('volsa')->row_array();
            $retrieved = floatval($row['vacdeb']);
            $displayed = horametre_display($retrieved, 2);

            $this->assertEqualsWithDelta($input, $displayed, 0.01,
                "Round-trip mode dixième : $input doit être retrouvé à l'édition");
        }
    }

    // =========================================================================
    // Pré-remplissage : le vacdeb du nouveau vol = vacfin du dernier vol
    // =========================================================================

    /**
     * Après insertion d'un vol, latest_horametre() retourne son vacfin.
     * C'est cette valeur qui pré-remplit le vacdeb du vol suivant.
     */
    public function testPrefillUsesLastFlightVacfin()
    {
        $last_vacfin = 1.75; // centièmes stockés pour le dernier vol
        $this->insertFlight($this->machine_cent, 1.25, $last_vacfin);

        $prefilled = $this->model->latest_horametre(array('vamacid' => $this->machine_cent));
        $this->assertEqualsWithDelta($last_vacfin, floatval($prefilled), 0.01,
            'Le pré-remplissage doit utiliser le vacfin du dernier vol');
    }

    /**
     * Sans vol pour la machine, latest_horametre() retourne 0.
     */
    public function testPrefillReturnsZeroWhenNoFlightExists()
    {
        $prefilled = $this->model->latest_horametre(array('vamacid' => $this->machine_dix));
        $this->assertEquals(0, floatval($prefilled),
            'Sans vol existant, le pré-remplissage doit retourner 0');
    }

    /**
     * Avec plusieurs vols sur la même machine, latest_horametre() retourne
     * le vacfin le plus élevé (dernier vol de l'avion).
     */
    public function testPrefillReturnsHighestVacfin()
    {
        $this->insertFlight($this->machine_cent, 1.00, 1.50);
        $this->insertFlight($this->machine_cent, 1.50, 2.25); // dernier
        $this->insertFlight($this->machine_cent, 0.50, 1.00); // premier

        $prefilled = $this->model->latest_horametre(array('vamacid' => $this->machine_cent));
        $this->assertEqualsWithDelta(2.25, floatval($prefilled), 0.01,
            'Le pré-remplissage doit retourner le vacfin le plus élevé');
    }

    /**
     * Le pré-remplissage est par machine : latest_horametre() avec filtre machine
     * ne retourne que les vols de cette machine.
     */
    public function testPrefillIsPerMachine()
    {
        $this->insertFlight($this->machine_cent, 1.00, 5.00); // machine centième : vacfin=5.00
        $this->insertFlight($this->machine_min,  1.00, 2.00); // machine minutes  : vacfin=2.00

        $prefilled_cent = $this->model->latest_horametre(array('vamacid' => $this->machine_cent));
        $prefilled_min  = $this->model->latest_horametre(array('vamacid' => $this->machine_min));

        $this->assertEqualsWithDelta(5.00, floatval($prefilled_cent), 0.01,
            'Machine centième : pré-remplissage doit retourner 5.00');
        $this->assertEqualsWithDelta(2.00, floatval($prefilled_min), 0.01,
            'Machine minutes : pré-remplissage doit retourner 2.00');
    }

    // =========================================================================
    // Invariants sur modification (la valeur en base ne change pas de format)
    // =========================================================================

    /**
     * Après insertion d'un vol, les valeurs vacdeb/vacfin restent en centièmes
     * en base (invariant de stockage préservé à la lecture).
     */
    public function testInvariantStorageFormatPreservedOnRead()
    {
        // Mode minutes : 1h30min saisi → 1.50 centièmes stockés
        $stored = round($this->to_decimal(1.30, 1), 2);
        $vaid = $this->insertFlight($this->machine_min, $stored, $stored + 0.50);

        $row = $this->CI->db->where('vaid', $vaid)->get('volsa')->row_array();
        $vacdeb = floatval($row['vacdeb']);
        $vacfin = floatval($row['vacfin']);

        // Les valeurs en base sont en centièmes, pas en minutes
        $this->assertEqualsWithDelta(1.50, $vacdeb, 0.01,
            'vacdeb doit rester en centièmes après insertion');
        $this->assertEqualsWithDelta(2.00, $vacfin, 0.01,
            'vacfin doit rester en centièmes après insertion');
    }

    /**
     * Mise à jour d'un vol : la re-lecture donne les valeurs correctes
     * (invariant préservé après UPDATE).
     */
    public function testInvariantPreservedAfterUpdate()
    {
        // Insérer vol avec vacdeb=1.50, vacfin=2.00 (centièmes)
        $vaid = $this->insertFlight($this->machine_cent, 1.50, 2.00);

        // Simuler une modification : changement de vacfin à 2.50
        $new_vacfin = 2.50;
        $this->CI->db->update('volsa',
            array('vacfin' => $new_vacfin, 'vaduree' => round($new_vacfin - 1.50, 2)),
            array('vaid' => $vaid)
        );

        // Re-lecture
        $row = $this->CI->db->where('vaid', $vaid)->get('volsa')->row_array();

        // vacdeb inchangé
        $this->assertEqualsWithDelta(1.50, floatval($row['vacdeb']), 0.01,
            'vacdeb ne doit pas changer lors de la modification de vacfin');
        // vacfin mis à jour
        $this->assertEqualsWithDelta(2.50, floatval($row['vacfin']), 0.01,
            'vacfin doit être mis à jour correctement');
        // horametre_display pour mode centième : valeur brute
        $this->assertEquals(2.50, horametre_display(floatval($row['vacfin']), 0),
            'Après modification, horametre_display mode centième doit retourner la valeur brute');
        // horametre_display pour mode minutes : 2.50 centièmes = "2:30"
        $this->assertEquals('2:30', horametre_display(floatval($row['vacfin']), 1),
            'Après modification, horametre_display mode minutes doit donner "2:30"');
    }

    /**
     * La vaduree est calculée en centièmes (fin - début), indépendamment du mode.
     */
    public function testDureeIsCalculatedInHundredths()
    {
        // Mode minutes : utilisateur entre deb=1.30 (1h30min), fin=2.00 (2h00min)
        $deb_stored = round($this->to_decimal(1.30, 1), 2); // 1.50
        $fin_stored = round($this->to_decimal(2.00, 1), 2); // 2.00
        $duree = round($fin_stored - $deb_stored, 2);       // 0.50 centièmes = 30min

        $vaid = $this->insertFlight($this->machine_min, $deb_stored, $fin_stored);
        // Mettre à jour vaduree comme le ferait le contrôleur
        $this->CI->db->update('volsa', array('vaduree' => $duree), array('vaid' => $vaid));

        $row = $this->CI->db->where('vaid', $vaid)->get('volsa')->row_array();
        $this->assertEqualsWithDelta(0.50, floatval($row['vaduree']), 0.01,
            'La durée doit être de 0.50 centièmes (30 minutes en décimal)');
    }
}
