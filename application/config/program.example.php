<?php

/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *	Configuration du programme
 */

if (! defined('BASEPATH')) exit('No direct script access allowed');
/*
|--------------------------------------------------------------------------
| Titre des pages HTML
|--------------------------------------------------------------------------
*/
$config['program_title'] = "Testing";


/*
|--------------------------------------------------------------------------
| Interdit l'accès sauf aux admins
|--------------------------------------------------------------------------
*/
$config['locked'] = FALSE;

/*
|--------------------------------------------------------------------------
| Utilise Ajax pour charger les tables page par page
|--------------------------------------------------------------------------
*/
$config['ajax'] = TRUE;

$config['test_api'] = TRUE;

/*
 * Catégorie de pilote
*
* Attention, une fois que vous avez saisie des données vous ne devriez qu'ajouter
* des catégories, jamais en supprimer.
* $config['categories_pilote'] = array (
		  0 => 'Membre',
		  1 => 'Extérieur',
		  2 => 'Etranger'
);
*/
$config['categories_pilote'] = array(
  0 => 'Membre',
  1 => 'Extérieur',
  2 => 'Etranger',
  3 => 'Convention lycée'
);

/*
 * Catégories des vols avion
 */
$config['categories_vol_avion'] = array(
  0 => 'Standard',
  1 => "Vol d'initiation",
  2 => "Vol d'essai",
  3 => "Remorquage",
  // 4 => "Ependage"
);

/*
 * Catégories des vols avion, version courte utilisée dans les tables
 */
$config['categories_vol_avion_short'] = array(
  0 => 'Std',
  1 => "VI",
  2 => "VE",
  3 => "R",
  // 4 => "E"
);

/*
 * Catégories des vols planeurs
 */
$config['categories_vol_planeur'] = array(
  0 => 'Standard',
  1 => "Vol d'initiation",
  2 => "Vol d'essai",
  3 => "Concours"
);

/*
 * Catégories des vols planeur, version courte utilisée dans les tables
 */
$config['categories_vol_planeur_short'] = array(
  0 => 'Std',
  1 => "VI",
  2 => "VE",
  3 => "CONC"
);

/*
 * Gestion des courriels
 */
// Selection des destinataires, cette lsite définie les options qui sont
// proposées dans le formulaire de mail. A chaque fois que le sélecteur est modifié
// La liste des destinataires est remplacée.
$config['listes_de_destinataires'] = array(
  '0' => 'Tous les membres actif',
  '1' => 'Tous les pilotes débiteurs',
  '2' => 'Les instructeurs',
  '3' => 'Les membres du conseil',
  '4' => 'le conseil et les instructeurs',
  '5' => 'les propriétaires'
);

/*
 * A chaque valeur de la liste ci-dessus doit correspondre un segment de requête SQL qui
 * selectionne les membres dont on veut garder les adresses emails
 * 
 * Les champs les plus interressant sont:
 * categorie:	entier qui code la catégorie du pilote
 * mniveaux: enier qui contient un champ de bits avec les valeurs suivants
 *    define("TRESORIER", 8);		// 2**3
 *    define("SECRETAIRE", 16);		// 2**4
 *    define("SECRETAIRE_ADJ", 32); // 2**5
 *    define("CA", 64);             // 2**6
 *    define("CHEF_PILOTE", 128);       // 2**7
 *    define("VI_PLANEUR", 256);
 *    define("VI_AVION", 512);
 *    define("MECANO", 1024);
 *    define("PILOTE_PLANEUR", 2048);
 *    define("PILOTE_AVION", 4096);
 *    define("REMORQUEUR", 8192);
 *    define("PLIEUR", 16384);
 *    define("ITP", 32768);
 *    define("IVV", 65536);
 *    define("FI_AVION", 131072);
 *    define("FE_AVION", 262144);
 *    define("TREUILLARD", 524288);
 */
$instructeurs = 65536 + 32768 + 131072 + 262144;
$ca = 64;            // membres du conseil d'adminstration       
$config['listes_de_requetes'] = array(
  '0' => '',
  '1' => 'solde < 0',
  '2' => "(mniveaux & ($instructeurs)) != 0",
  '3' => "(mniveaux & ($ca)) != 0",
  '4' => "(mniveaux & ($ca + $instructeurs)) != 0",
  '5' => 'categorie = 3'
);

/*
 * Copie systematique des emails envoyés
 */
$config['copie_a'] = "president@free.fr; gestion@monclub.fr";


/**
 * Le club gére des avions, des planeurs, des ulms
 */
$config['gestion_avion'] = true;
$config['gestion_planeur'] = true;
$config['gestion_ulm'] = true;
$config['gestion_tickets'] = true;
$config['gestion_pompes'] = true;

$config['gestion_vd'] = true;
$config['gestion_of'] = true;
$config['gestion_rapprochements'] = true;

/*
 * Les pilotes sont autorisés à saisir leur propres vols quand ils ne sont pas planchistes
 */
$config['auto_planchiste'] = false;

$config['new_layout'] = true;

/*
 * Numéro de la visite médicale dans event_type
 */
$config['medical_id'] = 26;

$config['dev_menu'] = true;

/**
 * Ce paramètre s'il existe détermine une section dans laquelle créer un compte 411 supplémentaire 
 */
$config['section_general'] = '0';

/**
 * Mode RAN (Retrospective Adjustment Nullification)
 * Active la saisie d'écritures rétrospectives avec compensation automatique
 * Permet de passer des écritures 2024 sans modifier les soldes finaux
 *
 * ATTENTION: En mode RAN, le formulaire s'affiche sur fond rouge et le contrôle
 * de date de gel est désactivé. À utiliser uniquement pour la ventilation 2024.
 */
$config['ran_mode_enabled'] = false;  // Désactivé pour tests

$config['passphrase'] = "Il était une fois...";


/* End of file program.php */
/* Location: .application/config/program.php */
