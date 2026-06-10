<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3) . '/helpers/vols_avion_categories_helper.php';

/**
 * Tests unitaires pour compute_vols_avion_categories()
 *
 * Vérifie que chaque rôle donne accès exactement aux catégories définies :
 *
 *   Standard (0)          : auto_planchiste, instructeur, club_admin
 *   Vol de découverte (1) : instructeur, club_admin, pilote_vd
 *   Vol d'essai (2)       : instructeur, club_admin
 *   Remorquage (3)        : pilote_rem (+ machine remorqueur côté JS)
 *   Vol propriétaire (4)  : instructeur, club_admin, propriétaire de la machine
 *   Vol porte ouverte (5) : instructeur, club_admin, pilote_vd
 *   Vol BIA (6)           : instructeur, club_admin, pilote_vd
 *   Convoyage (7)         : instructeur, club_admin, pilote_rem
 */
class VolsAvionCategoriesTest extends TestCase
{
    private static $all = array(
        0 => 'Standard',
        1 => "Vol de découverte",
        2 => "Vol d'essai",
        3 => 'Remorquage',
        4 => 'Vol propriétaire',
        5 => 'Vol porte ouverte',
        6 => 'Vol BIA',
        7 => 'Convoyage',
    );

    private function roles($overrides = array())
    {
        return array_merge(array(
            'admin'           => false,
            'planchiste'      => false,
            'instructeur'     => false,
            'pilote_vd'       => false,
            'pilote_rem'      => false,
            'auto_planchiste' => false,
            'owns_machine'    => false,
        ), $overrides);
    }

    private function compute($roles)
    {
        return compute_vols_avion_categories(self::$all, $roles);
    }

    // ------------------------------------------------------------------
    // Aucun rôle
    // ------------------------------------------------------------------

    public function test_no_roles_gets_empty_list()
    {
        $result = $this->compute($this->roles());
        $this->assertEmpty($result, 'Aucun rôle => aucune catégorie accessible');
    }

    // ------------------------------------------------------------------
    // auto_planchiste seul
    // ------------------------------------------------------------------

    public function test_auto_planchiste_only_standard()
    {
        $result = $this->compute($this->roles(['auto_planchiste' => true]));
        $this->assertSame([0], array_keys($result));
    }

    // ------------------------------------------------------------------
    // instructeur
    // ------------------------------------------------------------------

    public function test_instructeur_categories()
    {
        $result = $this->compute($this->roles(['instructeur' => true]));
        $this->assertArrayHasKey(0, $result, 'Standard');
        $this->assertArrayHasKey(1, $result, 'Vol de découverte');
        $this->assertArrayHasKey(2, $result, "Vol d'essai");
        $this->assertArrayNotHasKey(3, $result, 'Remorquage interdit');
        $this->assertArrayHasKey(4, $result, 'Vol propriétaire');
        $this->assertArrayHasKey(5, $result, 'Vol porte ouverte');
        $this->assertArrayHasKey(6, $result, 'Vol BIA');
        $this->assertArrayHasKey(7, $result, 'Convoyage');
    }

    // ------------------------------------------------------------------
    // club_admin (drapeau admin)
    // ------------------------------------------------------------------

    public function test_admin_gets_all_categories()
    {
        $result = $this->compute($this->roles(['admin' => true]));
        $this->assertSame(array_keys(self::$all), array_keys($result));
    }

    // ------------------------------------------------------------------
    // planchiste
    // ------------------------------------------------------------------

    public function test_planchiste_gets_all_categories()
    {
        $result = $this->compute($this->roles(['planchiste' => true]));
        $this->assertSame(array_keys(self::$all), array_keys($result));
    }

    // ------------------------------------------------------------------
    // pilote_rem
    // ------------------------------------------------------------------

    public function test_pilote_rem_categories()
    {
        $result = $this->compute($this->roles(['pilote_rem' => true]));
        $this->assertArrayHasKey(0, $result, 'Standard');
        $this->assertArrayHasKey(3, $result, 'Remorquage');
        $this->assertArrayHasKey(7, $result, 'Convoyage');
        $this->assertArrayNotHasKey(1, $result, 'VD interdit');
        $this->assertArrayNotHasKey(2, $result, 'Essai interdit');
        $this->assertArrayNotHasKey(4, $result, 'Propriétaire interdit');
        $this->assertArrayNotHasKey(5, $result, 'PO interdit');
        $this->assertArrayNotHasKey(6, $result, 'BIA interdit');
    }

    // ------------------------------------------------------------------
    // pilote_vd
    // ------------------------------------------------------------------

    public function test_pilote_vd_categories()
    {
        $result = $this->compute($this->roles(['pilote_vd' => true]));
        $this->assertArrayHasKey(0, $result, 'Standard');
        $this->assertArrayHasKey(1, $result, 'Vol de découverte');
        $this->assertArrayNotHasKey(2, $result, 'Essai interdit');
        $this->assertArrayNotHasKey(3, $result, 'Remorquage interdit');
        $this->assertArrayNotHasKey(4, $result, 'Propriétaire interdit');
        $this->assertArrayHasKey(5, $result, 'Vol porte ouverte');
        $this->assertArrayHasKey(6, $result, 'Vol BIA');
        $this->assertArrayNotHasKey(7, $result, 'Convoyage interdit');
    }

    // ------------------------------------------------------------------
    // Propriétaire de machine (sans rôle élevé)
    // ------------------------------------------------------------------

    public function test_proprietaire_machine_categories()
    {
        $result = $this->compute($this->roles(['auto_planchiste' => true, 'owns_machine' => true]));
        $this->assertArrayHasKey(0, $result, 'Standard');
        $this->assertArrayHasKey(4, $result, 'Vol propriétaire');
        $this->assertArrayNotHasKey(1, $result, 'VD interdit');
        $this->assertArrayNotHasKey(2, $result, 'Essai interdit');
        $this->assertArrayNotHasKey(3, $result, 'Remorquage interdit');
        $this->assertArrayNotHasKey(5, $result, 'PO interdit');
        $this->assertArrayNotHasKey(6, $result, 'BIA interdit');
        $this->assertArrayNotHasKey(7, $result, 'Convoyage interdit');
    }

    public function test_owns_machine_without_auto_planchiste_no_standard()
    {
        // Un propriétaire sans aucun rôle pilote n'a pas Standard
        $result = $this->compute($this->roles(['owns_machine' => true]));
        $this->assertArrayHasKey(4, $result, 'Vol propriétaire');
        $this->assertArrayNotHasKey(0, $result, 'Standard sans rôle pilote');
    }

    // ------------------------------------------------------------------
    // Cumul de rôles
    // ------------------------------------------------------------------

    public function test_pilote_vd_and_pilote_rem()
    {
        $result = $this->compute($this->roles(['pilote_vd' => true, 'pilote_rem' => true]));
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(3, $result);
        $this->assertArrayHasKey(5, $result);
        $this->assertArrayHasKey(6, $result);
        $this->assertArrayHasKey(7, $result);
        $this->assertArrayNotHasKey(2, $result);
        $this->assertArrayNotHasKey(4, $result);
    }

    public function test_result_keys_are_sorted()
    {
        $result = $this->compute($this->roles(['instructeur' => true]));
        $keys = array_keys($result);
        $sorted = $keys;
        sort($sorted);
        $this->assertSame($sorted, $keys, 'Les clés doivent être triées');
    }
}
