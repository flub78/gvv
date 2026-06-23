<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour les fonctions d'affichage et de conversion des horamètres.
 *
 * Les trois modes supportés :
 *   MODE 0 (centième) : valeur HH.CC (0-99 centièmes), stockée telle quelle
 *   MODE 1 (minutes)  : valeur HH.MM (0-59 minutes), convertie en HH.CC pour stockage
 *   MODE 2 (dixième)  : valeur HH.D  (0-9 dixièmes),  stockée telle quelle
 *
 * Invariant de stockage : quelle que soit l'unité d'entrée, la base de données
 * stocke toujours en heures + centièmes (ex : 1h30 → 1.50).
 */
class HorametreHelperTest extends TestCase
{
    // ===========================================================================
    // centieme_to_hhmm() : conversion centièmes → "HH:MM"
    // ===========================================================================

    /**
     * Zéro → "0:00"
     */
    public function testCentiemeToHhmmZero()
    {
        $this->assertEquals('0:00', centieme_to_hhmm(0));
        $this->assertEquals('0:00', centieme_to_hhmm(0.00));
    }

    /**
     * Cas de l'exemple de la docstring : 10692.32 → "10692:19"
     */
    public function testCentiemeToHhmmDocstringExample()
    {
        $this->assertEquals('10692:19', centieme_to_hhmm(10692.32));
    }

    /**
     * Valeurs standard du mode centième (0-99 pour la partie décimale)
     */
    public function testCentiemeToHhmmStandardValues()
    {
        // 1.20 centièmes = 1h + 0.20×60 = 1h12min
        $this->assertEquals('1:12', centieme_to_hhmm(1.20));
        // 14.70 centièmes = 14h + 0.70×60 = 14h42min
        $this->assertEquals('14:42', centieme_to_hhmm(14.70));
        // 0.40 centièmes = 0h + 0.40×60 = 0h24min
        $this->assertEquals('0:24', centieme_to_hhmm(0.40));
        // 1.50 centièmes = 1h30min
        $this->assertEquals('1:30', centieme_to_hhmm(1.50));
        // 1.00 centièmes = 1h00min
        $this->assertEquals('1:00', centieme_to_hhmm(1.00));
    }

    /**
     * Borne haute de la partie décimale (99 centièmes ≈ 59.4 min → arrondi à 59)
     */
    public function testCentiemeToHhmmHighDecimal()
    {
        // 0.99 centièmes = 0h + 0.99×60 = 59.4 → arrondi 59 min
        $this->assertEquals('0:59', centieme_to_hhmm(0.99));
        // 1.99 centièmes = 1h + 0.99×60 → "1:59"
        $this->assertEquals('1:59', centieme_to_hhmm(1.99));
    }

    /**
     * Valeurs stockées issues d'une conversion depuis le mode minutes
     * ex : utilisateur entre 1.30 (1h30min) → stocké 1.50 centièmes
     */
    public function testCentiemeToHhmmFromMinutesConversion()
    {
        $this->assertEquals('1:30', centieme_to_hhmm(1.50));
        $this->assertEquals('0:45', centieme_to_hhmm(0.75));
        $this->assertEquals('2:00', centieme_to_hhmm(2.00));
    }

    /**
     * Valeurs nulles / non numériques → "0:00"
     */
    public function testCentiemeToHhmmInvalidInputs()
    {
        $this->assertEquals('0:00', centieme_to_hhmm(''));
        $this->assertEquals('0:00', centieme_to_hhmm(null));
        $this->assertEquals('0:00', centieme_to_hhmm('abc'));
    }

    /**
     * Grande valeur d'horamètre (milliers d'heures)
     */
    public function testCentiemeToHhmmLargeValue()
    {
        // 1000.50 = 1000h30min
        $this->assertEquals('1000:30', centieme_to_hhmm(1000.50));
    }

    // ===========================================================================
    // horametre_display() : formatage selon le mode
    // ===========================================================================

    /**
     * Mode 0 (centième) : la valeur est retournée telle quelle
     */
    public function testHorametreDisplayModeCentieme()
    {
        $this->assertEquals(1.50, horametre_display(1.50, 0));
        $this->assertEquals(14.70, horametre_display(14.70, 0));
        $this->assertEquals(0.00, horametre_display(0.00, 0));
        $this->assertEquals(1000.99, horametre_display(1000.99, 0));
    }

    /**
     * Mode 1 (minutes) : appel de centieme_to_hhmm → "HH:MM"
     */
    public function testHorametreDisplayModeMinutes()
    {
        // 1.50 centièmes stockés = 1h30min → "1:30"
        $this->assertEquals('1:30', horametre_display(1.50, 1));
        // 14.70 centièmes stockés = 14h42min → "14:42"
        $this->assertEquals('14:42', horametre_display(14.70, 1));
        // 0.00 = "0:00"
        $this->assertEquals('0:00', horametre_display(0.00, 1));
        // 10692.32 = "10692:19"
        $this->assertEquals('10692:19', horametre_display(10692.32, 1));
    }

    /**
     * Mode 2 (dixième) : la valeur est retournée telle quelle
     */
    public function testHorametreDisplayModeDixieme()
    {
        $this->assertEquals(1.5, horametre_display(1.5, 2));
        $this->assertEquals(14.7, horametre_display(14.7, 2));
        $this->assertEquals(0.0, horametre_display(0.0, 2));
        $this->assertEquals(1000.9, horametre_display(1000.9, 2));
    }

    // ===========================================================================
    // Conversion HH.MM → centièmes (logique de to_hundredth dans le contrôleur)
    //
    // Cette logique est reproduite ici pour tester les invariants de conversion
    // sans dépendre du contrôleur CodeIgniter.
    // ===========================================================================

    /**
     * Réplique la logique privée to_hundredth() du contrôleur vols_avion.
     */
    private function to_hundredth($hm)
    {
        $hours = intval($hm);
        $minutes = ($hm - $hours) * 100;
        $centiemes = $minutes / 60;
        return $hours + $centiemes;
    }

    /**
     * Mode minutes : valeurs valides (MM entre 00 et 59)
     * La partie décimale représente des minutes → convertie en centièmes.
     */
    public function testToHundredthValidMinuteValues()
    {
        // 1h00min → 1.00 centièmes
        $this->assertEqualsWithDelta(1.00, $this->to_hundredth(1.00), 0.001);
        // 1h30min → 1.50 centièmes
        $this->assertEqualsWithDelta(1.50, $this->to_hundredth(1.30), 0.001);
        // 1h45min → 1.75 centièmes
        $this->assertEqualsWithDelta(1.75, $this->to_hundredth(1.45), 0.001);
        // 0h30min → 0.50 centièmes
        $this->assertEqualsWithDelta(0.50, $this->to_hundredth(0.30), 0.001);
        // 100h45min → 100.75 centièmes
        $this->assertEqualsWithDelta(100.75, $this->to_hundredth(100.45), 0.001);
    }

    /**
     * Borne haute valide : 1h59min → 1.9833 centièmes (≈ 1.98 stocké en decimal(8,2))
     */
    public function testToHundredthFiftyNineMinutes()
    {
        $result = $this->to_hundredth(1.59);
        // 1 + 59/60 ≈ 1.9833
        $this->assertEqualsWithDelta(1.9833, $result, 0.001);
    }

    /**
     * Valeur nulle : 0h00min → 0.00 centièmes
     */
    public function testToHundredthZero()
    {
        $this->assertEqualsWithDelta(0.00, $this->to_hundredth(0.00), 0.0001);
    }

    // ===========================================================================
    // Tests d'invariant de round-trip (entrée → centièmes → affichage)
    //
    // L'utilisateur entre une valeur dans son unité, elle est stockée en centièmes,
    // puis ré-affichée dans son unité d'origine.
    // ===========================================================================

    /**
     * Mode centième : round-trip parfait (stockage = entrée)
     * Exemple : l'utilisateur entre 1.50, lit 1.50 en édition.
     */
    public function testRoundTripModeCentieme()
    {
        $inputs = [0.00, 1.00, 1.50, 1.99, 100.50];
        foreach ($inputs as $input) {
            // Mode centième : pas de conversion, stocké et affiché tel quel
            $stored = $input;
            $displayed = horametre_display($stored, 0);
            $this->assertEquals($input, $displayed,
                "Mode centième : $input doit être affiché tel quel");
        }
    }

    /**
     * Mode minutes : round-trip via centièmes
     * Exemple : l'utilisateur entre 1.30 (1h30min),
     * stocké en 1.50 centièmes, affiché "1:30" (HH:MM).
     */
    public function testRoundTripModeMinutes()
    {
        $cases = [
            // [entrée HH.MM, centièmes stockés, affichage attendu HH:MM]
            [1.00,  1.00,   '1:00'],
            [1.30,  1.50,   '1:30'],
            [1.45,  1.75,   '1:45'],
            [0.30,  0.50,   '0:30'],
            [10.15, 10.25,  '10:15'],
        ];
        foreach ($cases as [$input, $expected_stored, $expected_display]) {
            $stored = round($this->to_hundredth($input), 2);
            $this->assertEqualsWithDelta($expected_stored, $stored, 0.005,
                "Mode minutes : $input doit être stocké en $expected_stored centièmes");
            $displayed = horametre_display($stored, 1);
            $this->assertEquals($expected_display, $displayed,
                "Mode minutes : $expected_stored centièmes doit s'afficher '$expected_display'");
        }
    }

    /**
     * Mode dixième : round-trip parfait (stockage = entrée en heures décimales)
     * Exemple : l'utilisateur entre 1.5, stocké en 1.5, affiché 1.5.
     */
    public function testRoundTripModeDixieme()
    {
        $inputs = [0.0, 1.0, 1.5, 1.9, 100.5];
        foreach ($inputs as $input) {
            // Mode dixième : pas de conversion, stocké et affiché tel quel
            $stored = $input;
            $displayed = horametre_display($stored, 2);
            $this->assertEquals($input, $displayed,
                "Mode dixième : $input doit être affiché tel quel");
        }
    }

    // ===========================================================================
    // Cohérence du stockage : quel que soit le mode, la valeur en base
    // est toujours en heures + centièmes (HH.CC).
    // ===========================================================================

    /**
     * Mode minutes : 1h59min doit être stocké comme ~1.98 centièmes, pas comme 1.59.
     */
    public function testStorageIsNotMinutesFormatInDb()
    {
        $user_input = 1.59; // l'utilisateur entre "1h59min"
        $stored = round($this->to_hundredth($user_input), 2);
        // La valeur stockée doit être en centièmes, pas en minutes
        $this->assertNotEquals(1.59, $stored, 'La valeur stockée ne doit pas être en format minutes');
        // La valeur stockée doit être ≈ 1.98 (= 1 + 59/60)
        $this->assertEqualsWithDelta(1.98, $stored, 0.01);
    }

    /**
     * Mode minutes : 1h00min stocké comme 1.00 (pas 1.00 = ambigu, mais invariant OK)
     */
    public function testStorageOneHourZeroMinutes()
    {
        $stored = round($this->to_hundredth(1.00), 2);
        $this->assertEqualsWithDelta(1.00, $stored, 0.001);
        // L'affichage en mode minutes doit donner "1:00"
        $this->assertEquals('1:00', horametre_display($stored, 1));
    }

    /**
     * Les trois modes produisent des valeurs stockées en heures décimales
     * (centièmes). Vérification sur une valeur de référence = 1h30min.
     *
     * Mode 0 : entrée 1.50 → stocké 1.50
     * Mode 1 : entrée 1.30 → stocké 1.50
     * Mode 2 : entrée 1.5  → stocké 1.5
     *
     * Tous stockent 1.50 en base (= 1h30min en décimal centièmes).
     */
    public function testAllModesStoreOneHour30AsDecimal()
    {
        $stored_centieme = 1.50;           // utilisateur entre 1.50 (centième)
        $stored_minutes  = round($this->to_hundredth(1.30), 2); // utilisateur entre 1.30 (min)
        $stored_dixieme  = 1.5;            // utilisateur entre 1.5 (dixième)

        $this->assertEqualsWithDelta(1.50, $stored_centieme, 0.001);
        $this->assertEqualsWithDelta(1.50, $stored_minutes,  0.001);
        $this->assertEqualsWithDelta(1.50, $stored_dixieme,  0.001);
    }

    // ===========================================================================
    // Tests d'affichage en liste (valeurs en heures décimales)
    // ===========================================================================

    /**
     * En liste, les valeurs sont en heures décimales (mode 0 et 2 = valeur brute,
     * mode 1 = HH:MM pour lisibilité). La valeur brute (centièmes) reste la valeur
     * de référence pour le tri et les calculs.
     */
    public function testListDisplayModesCentiemeAndDixieme()
    {
        // Mode 0 : 1.50 affiché comme 1.50 (heures décimales)
        $this->assertEquals(1.50, horametre_display(1.50, 0));
        // Mode 2 : 1.5 affiché comme 1.5 (heures décimales)
        $this->assertEquals(1.5, horametre_display(1.5, 2));
    }

    /**
     * En liste mode minutes, les centièmes stockés s'affichent en HH:MM.
     */
    public function testListDisplayModeMinutes()
    {
        // 1.50 centièmes stockés = "1:30" en liste mode minutes
        $this->assertEquals('1:30', horametre_display(1.50, 1));
        // 14.70 centièmes = "14:42" en liste mode minutes
        $this->assertEquals('14:42', horametre_display(14.70, 1));
    }
}
