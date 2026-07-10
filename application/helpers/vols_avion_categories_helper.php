<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Calcule les catégories de vol accessibles selon les rôles de l'utilisateur.
 *
 * Logique métier pure, sans dépendance au framework CI.
 *
 * Règles :
 *   - admin / planchiste       : toutes les catégories
 *   - instructeur              : Standard, VD, Essai, Propriétaire, PO, BIA, Convoyage, Standardisation
 *   - pilote_vd                : Standard, VD, PO, BIA
 *   - pilote_rem               : Standard, Remorquage, Convoyage (JS filtre selon machine)
 *   - propriétaire de machine  : Standard, Vol propriétaire (JS filtre selon machine)
 *   - mecano                   : Vol d'essai uniquement
 *   - auto_planchiste seul     : Standard uniquement
 *
 * @param array $all   Tableau complet [int => string] (config categories_vol_avion)
 * @param array $roles Drapeaux booléens : admin, planchiste, instructeur,
 *                     pilote_vd, pilote_rem, auto_planchiste, owns_machine, mecano
 * @return array       Sous-tableau des catégories autorisées (clés préservées)
 */
function compute_vols_avion_categories(array $all, array $roles)
{
    $r = array_merge(array(
        'admin'           => false,
        'planchiste'      => false,
        'instructeur'     => false,
        'pilote_vd'       => false,
        'pilote_rem'      => false,
        'auto_planchiste' => false,
        'owns_machine'    => false,
        'mecano'          => false,
    ), $roles);

    if ($r['admin'] || $r['planchiste']) {
        return $all;
    }

    $allowed = array();

    // Standard (0)
    if ($r['instructeur'] || $r['pilote_vd'] || $r['pilote_rem'] || $r['auto_planchiste']) {
        $allowed[0] = $all[0];
    }

    // Vol de découverte (1), Vol porte ouverte (5), Vol BIA (6)
    if ($r['instructeur'] || $r['pilote_vd']) {
        $allowed[1] = $all[1];
        $allowed[5] = $all[5];
        $allowed[6] = $all[6];
    }

    // Vol d'essai (2)
    if ($r['instructeur'] || $r['mecano']) {
        $allowed[2] = $all[2];
    }

    // Remorquage (3) : JS masque si la machine n'est pas remorqueur
    if ($r['pilote_rem']) {
        $allowed[3] = $all[3];
    }

    // Convoyage (7)
    if (($r['instructeur'] || $r['pilote_rem']) && isset($all[7])) {
        $allowed[7] = $all[7];
    }

    // Remise en vol (8)
    if ($r['instructeur'] && isset($all[8])) {
        $allowed[8] = $all[8];
    }

    // Vol de standardisation (9)
    if ($r['instructeur'] && isset($all[9])) {
        $allowed[9] = $all[9];
    }

    // Vol propriétaire (4) : instructeur OU propriétaire d'au moins une machine
    if ($r['instructeur'] || $r['owns_machine']) {
        $allowed[4] = $all[4];
    }

    ksort($allowed);
    return $allowed;
}
