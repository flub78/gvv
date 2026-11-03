<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Registre des paramètres de la page Configuration.
 *
 * Tableau PHP pur, sans dépendance à CodeIgniter, pour pouvoir être lu par
 * d'autres outils (installeur). Chaque onglet n'est affiché que s'il contient
 * au moins un paramètre lisible par l'utilisateur.
 *
 * Profils, du plus restreint au plus large : 'ca', 'club-admin', 'dev'.
 * Un club-admin a les droits du CA ; un dev_user a tous les droits.
 *
 * Paramètre :
 *   tab       onglet (clé de $config['parametres_tabs'])
 *   store     'db' (table configuration) ou 'file:<nom>' (application/config/<nom>.php)
 *   type      'text', 'email', 'url', 'textarea', 'markdown', 'bool', 'int', 'password'
 *   label     clé de langue du libellé ; help : clé de langue de l'aide (facultatif)
 *   read      profil minimal pour lire ; write : profil minimal pour modifier
 *   per_lang  TRUE si une valeur peut être saisie par langue
 *   required  TRUE si le paramètre est obligatoire (état de l'onglet)
 *   secret    TRUE pour ne jamais réafficher la valeur
 *   rules     règles de validation CodeIgniter (facultatif)
 *   default   valeur par défaut affichée tant que rien n'est défini (facultatif)
 *
 * @see doc/prds/configuration_unifiee_prd.md
 * @see doc/design_notes/configuration_unifiee_design.md
 */

$config['parametres_tabs'] = array(
    'pages_publiques' => array(
        'icon'  => 'fas fa-globe',
        'label' => 'parametres_tab_pages_publiques',
        'help'  => 'parametres_tab_pages_publiques_help',
    ),
);

$config['parametres_registry'] = array(
    'legal.confidentialite' => array(
        'tab'      => 'pages_publiques',
        'store'    => 'db',
        'type'     => 'markdown',
        'label'    => 'parametres_legal_confidentialite',
        'help'     => 'parametres_legal_confidentialite_help',
        'read'     => 'club-admin',
        'write'    => 'club-admin',
        'per_lang' => TRUE,
        'rules'    => 'max_length[60000]',
    ),
    'legal.conditions' => array(
        'tab'      => 'pages_publiques',
        'store'    => 'db',
        'type'     => 'markdown',
        'label'    => 'parametres_legal_conditions',
        'help'     => 'parametres_legal_conditions_help',
        'read'     => 'club-admin',
        'write'    => 'club-admin',
        'per_lang' => TRUE,
        'rules'    => 'max_length[60000]',
    ),
);

/* End of file parametres_registry.php */
