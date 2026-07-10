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
  1 => "Vol de découverte",
  2 => "Vol d'essai",
  3 => "Remorquage",
  4 => "Vol propriétaire",
  5 => "Vol porte ouverte",
  6 => "Vol BIA",
  7 => "Convoyage",
  9 => "Vol de standardisation"
);

/*
 * Catégories des vols avion, version courte utilisée dans les tables
 */
$config['categories_vol_avion_short'] = array(
  0 => 'Std',
  1 => "VD",
  2 => "VE",
  3 => "R",
  4 => "PROP",
  5 => "PO",
  6 => "BIA",
  7 => "Conv",
  9 => "Stdz"
);

/*
 * Catégories des vols planeurs
 */
$config['categories_vol_planeur'] = array(
  0 => 'Standard',
  1 => "Vol de découverte",
  2 => "Vol d'essai",
  3 => "Concours",
  4 => "Vol Porte Ouverte",
  5 => "Vol BIA"
);

/*
 * Catégories des vols planeur, version courte utilisée dans les tables
 */
$config['categories_vol_planeur_short'] = array(
  0 => 'Std',
  1 => "VI",
  2 => "VE",
  3 => "CONC",
  4 => "PO",
  5 => "BIA"
);


/*
 * Copie systematique des emails envoyés
 */
$config['copie_a'] = "president@free.fr; gestion@monclub.fr";


$config['gestion_tickets'] = true;
$config['gestion_pompes'] = true;

$config['gestion_vd'] = true;
$config['gestion_of'] = true;
$config['gestion_rapprochements'] = true;

/*
 * OpenFlyers feature flag
 *
 * NOTE: The runtime feature flag is defined in application/config/gvv_config.php
 * using $config['openflyers_enabled'].
 *
 * Behavior:
 * - if FALSE: OpenFlyers controller is blocked (404) and related UI is hidden.
 * - if not defined: treated as disabled (same behavior as FALSE).
 */

/*
 * Unification de l'envoi des emails pour les vols de découverte (VD)
 * Si TRUE, le contrôleur VD utilisera la configuration standard `application/config/email.php`
 * Si FALSE ou si le paramètre n'existe pas, VD continue d'utiliser sa configuration SMTP dédiée
 */
$config['use_standard_email_configuration_for_vd'] = false;

/*
 * Les pilotes sont autorisés à saisir leur propres vols quand ils ne sont pas planchistes
 */
$config['auto_planchiste'] = false;

$config['new_layout'] = true;

/*
 * Couleur de fond de la bannière principale (CSS color, ex: "#1f6f8b" ou "darkgreen")
 * Si non défini ou vide, la couleur par défaut est verte.
 */
$config['banner_color'] = 'green';

/*
 * Numéro de la visite médicale dans event_type
 */
$config['medical_id'] = 26;

$config['dev_users'] = '';

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

$config['timeline_increment'] = "15";

$config['reservation_balance_check'] = TRUE;

// Secret protégeant l'URL de déclenchement manuel du scheduler
$config['reservation_scheduler_secret'] = 'CHANGE_ME_IN_PRODUCTION';

// Clé API Brevo pour l'envoi de SMS (laisser vide pour désactiver les SMS)
$config['brevo_sms_api_key'] = '';

// Nom de l'expéditeur SMS affiché sur le téléphone (11 caractères max)
$config['brevo_sms_sender']  = 'GVV';

// Redirection test : si défini, tous les emails/SMS sortants sont redirigés vers ces adresses.
// Laisser vide en production.
$config['test_email'] = '';
$config['test_phone'] = '';

/* End of file program.php */
/* Location: .application/config/program.php */
