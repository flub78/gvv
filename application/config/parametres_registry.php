<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Registre des paramètres de la page Configuration.
 *
 * Tableau PHP pur, sans appel à CodeIgniter : un autre outil (installeur) peut
 * l'inclure, à condition de définir au préalable la constante BASEPATH exigée par
 * la garde ci-dessus. Chaque onglet n'est affiché que s'il contient au moins un
 * paramètre lisible par l'utilisateur.
 *
 * Profils, du plus restreint au plus large : 'ca', 'club-admin', 'dev'.
 * Un club-admin a les droits du CA ; un dev_user a tous les droits.
 *
 * Paramètre :
 *   tab       onglet (clé de $config['parametres_tabs'])
 *   store     'db' (table configuration) ou 'file:<nom>' (application/config/<nom>.php)
 *   type      'text', 'email', 'url', 'textarea', 'markdown', 'bool', 'int', 'password',
 *             'select' (valeurs dans options), 'image' (fichier déposé dans uploads/)
 *   options   pour 'select' : valeur => libellé affiché
 *   label     clé de langue du libellé ; help : clé de langue de l'aide (facultatif)
 *   read      profil minimal pour lire ; write : profil minimal pour modifier
 *   per_lang  TRUE si une valeur peut être saisie par langue
 *   required  TRUE si le paramètre est obligatoire (état de l'onglet)
 *   required_if  obligatoire seulement si d'autres paramètres ont la valeur donnée :
 *             array('<clé>' => '<valeur>')
 *   requires  pour un module : paramètres dont il a besoin ; s'ils manquent quand
 *             le module est actif, un avertissement désigne l'onglet à compléter
 *   secret    TRUE pour ne jamais réafficher la valeur
 *   rules     règles de validation CodeIgniter (facultatif)
 *   default   valeur par défaut affichée tant que rien n'est défini (facultatif)
 *
 * @see doc/prds/configuration_unifiee_prd.md
 * @see doc/design_notes/configuration_unifiee_design.md
 */

$config['parametres_tabs'] = array(
    'club' => array(
        'icon'  => 'fas fa-building',
        'label' => 'parametres_tab_club',
        'help'  => 'parametres_tab_club_help',
    ),
    'modules' => array(
        'icon'  => 'fas fa-puzzle-piece',
        'label' => 'parametres_tab_modules',
        'help'  => 'parametres_tab_modules_help',
    ),
    'messagerie' => array(
        'icon'  => 'fas fa-envelope',
        'label' => 'parametres_tab_messagerie',
        'help'  => 'parametres_tab_messagerie_help',
    ),
    'pages_publiques' => array(
        'icon'  => 'fas fa-globe',
        'label' => 'parametres_tab_pages_publiques',
        'help'  => 'parametres_tab_pages_publiques_help',
    ),
);

$config['parametres_registry'] = array(

    // Onglet Club : identité, lisible par le CA, modifiable par le club-admin (EX-PRO-06).
    'sigle_club' => array(
        'tab'   => 'club',
        'store' => 'file:club',
        'type'  => 'text',
        'label' => 'parametres_sigle_club',
        'help'  => 'parametres_sigle_club_help',
        'read'  => 'ca',
        'write' => 'club-admin',
        'required' => TRUE,
        'rules' => 'max_length[255]',
    ),
    'nom_club' => array(
        'tab'   => 'club',
        'store' => 'file:club',
        'type'  => 'text',
        'label' => 'parametres_nom_club',
        'help'  => 'parametres_nom_club_help',
        'read'  => 'ca',
        'write' => 'club-admin',
        'required' => TRUE,
        'rules' => 'max_length[255]',
    ),
    'code_club' => array(
        'tab'   => 'club',
        'store' => 'file:club',
        'type'  => 'text',
        'label' => 'parametres_code_club',
        'help'  => 'parametres_code_club_help',
        'read'  => 'ca',
        'write' => 'club-admin',
        'rules' => 'max_length[255]',
    ),
    'adresse_club' => array(
        'tab'   => 'club',
        'store' => 'file:club',
        'type'  => 'text',
        'label' => 'parametres_adresse_club',
        'help'  => 'parametres_adresse_club_help',
        'read'  => 'ca',
        'write' => 'club-admin',
        'required' => TRUE,
        'rules' => 'max_length[255]',
    ),
    'cp_club' => array(
        'tab'   => 'club',
        'store' => 'file:club',
        'type'  => 'text',
        'label' => 'parametres_cp_club',
        'help'  => 'parametres_cp_club_help',
        'read'  => 'ca',
        'write' => 'club-admin',
        'required' => TRUE,
        'rules' => 'max_length[255]',
    ),
    'ville_club' => array(
        'tab'   => 'club',
        'store' => 'file:club',
        'type'  => 'text',
        'label' => 'parametres_ville_club',
        'help'  => 'parametres_ville_club_help',
        'read'  => 'ca',
        'write' => 'club-admin',
        'required' => TRUE,
        'rules' => 'max_length[255]',
    ),
    'tel_club' => array(
        'tab'   => 'club',
        'store' => 'file:club',
        'type'  => 'text',
        'label' => 'parametres_tel_club',
        'help'  => 'parametres_tel_club_help',
        'read'  => 'ca',
        'write' => 'club-admin',
        'required' => TRUE,
        'rules' => 'max_length[255]',
    ),
    'email_club' => array(
        'tab'   => 'club',
        'store' => 'file:club',
        'type'  => 'email',
        'label' => 'parametres_email_club',
        'help'  => 'parametres_email_club_help',
        'read'  => 'ca',
        'write' => 'club-admin',
        'required' => TRUE,
        'rules' => 'max_length[255]',
    ),
    'url_club' => array(
        'tab'   => 'club',
        'store' => 'file:club',
        'type'  => 'url',
        'label' => 'parametres_url_club',
        'help'  => 'parametres_url_club_help',
        'read'  => 'ca',
        'write' => 'club-admin',
        'rules' => 'max_length[255]',
    ),
    'logo_club' => array(
        'tab'   => 'club',
        'store' => 'file:club',
        'type'  => 'image',
        'label' => 'parametres_logo_club',
        'help'  => 'parametres_logo_club_help',
        'read'  => 'ca',
        'write' => 'club-admin',
        'rules' => 'max_length[255]',
    ),
    'contact_rgpd' => array(
        'tab'   => 'club',
        'store' => 'file:club',
        'type'  => 'text',
        'label' => 'parametres_contact_rgpd',
        'help'  => 'parametres_contact_rgpd_help',
        'read'  => 'ca',
        'write' => 'club-admin',
        'rules' => 'max_length[255]',
    ),

    // Onglet Modules : drapeaux de program.php.
    'gestion_vd' => array(
        'tab'     => 'modules',
        'store'   => 'file:program',
        'type'    => 'bool',
        'label'   => 'parametres_gestion_vd',
        'help'    => 'parametres_gestion_vd_help',
        'read'    => 'ca',
        'write'   => 'club-admin',
        'default' => FALSE,
        'requires' => array('smtp_host'),
    ),
    'gestion_reservations' => array(
        'tab'     => 'modules',
        'store'   => 'file:program',
        'type'    => 'bool',
        'label'   => 'parametres_gestion_reservations',
        'help'    => 'parametres_gestion_reservations_help',
        'read'    => 'ca',
        'write'   => 'club-admin',
        'default' => FALSE,
    ),
    'gestion_formations' => array(
        'tab'     => 'modules',
        'store'   => 'file:program',
        'type'    => 'bool',
        'label'   => 'parametres_gestion_formations',
        'help'    => 'parametres_gestion_formations_help',
        'read'    => 'ca',
        'write'   => 'club-admin',
        'default' => FALSE,
    ),
    'gestion_documentaire' => array(
        'tab'     => 'modules',
        'store'   => 'file:program',
        'type'    => 'bool',
        'label'   => 'parametres_gestion_documentaire',
        'help'    => 'parametres_gestion_documentaire_help',
        'read'    => 'ca',
        'write'   => 'club-admin',
        'default' => FALSE,
    ),
    'gestion_paiements' => array(
        'tab'     => 'modules',
        'store'   => 'file:program',
        'type'    => 'bool',
        'label'   => 'parametres_gestion_paiements',
        'help'    => 'parametres_gestion_paiements_help',
        'read'    => 'ca',
        'write'   => 'club-admin',
        'default' => FALSE,
    ),
    'gestion_ulm' => array(
        'tab'     => 'modules',
        'store'   => 'file:program',
        'type'    => 'bool',
        'label'   => 'parametres_gestion_ulm',
        'help'    => 'parametres_gestion_ulm_help',
        'read'    => 'ca',
        'write'   => 'club-admin',
        'default' => FALSE,
    ),
    'gestion_tickets' => array(
        'tab'     => 'modules',
        'store'   => 'file:program',
        'type'    => 'bool',
        'label'   => 'parametres_gestion_tickets',
        'help'    => 'parametres_gestion_tickets_help',
        'read'    => 'ca',
        'write'   => 'club-admin',
        'default' => FALSE,
    ),
    'gestion_pompes' => array(
        'tab'     => 'modules',
        'store'   => 'file:program',
        'type'    => 'bool',
        'label'   => 'parametres_gestion_pompes',
        'help'    => 'parametres_gestion_pompes_help',
        'read'    => 'ca',
        'write'   => 'club-admin',
        'default' => FALSE,
    ),
    'gestion_rapprochements' => array(
        'tab'     => 'modules',
        'store'   => 'file:program',
        'type'    => 'bool',
        'label'   => 'parametres_gestion_rapprochements',
        'help'    => 'parametres_gestion_rapprochements_help',
        'read'    => 'ca',
        'write'   => 'club-admin',
        'default' => FALSE,
    ),
    'gestion_of' => array(
        'tab'     => 'modules',
        'store'   => 'file:program',
        'type'    => 'bool',
        'label'   => 'parametres_gestion_of',
        'help'    => 'parametres_gestion_of_help',
        'read'    => 'ca',
        'write'   => 'club-admin',
        'default' => FALSE,
    ),
    'veille' => array(
        'tab'     => 'modules',
        'store'   => 'file:program',
        'type'    => 'bool',
        'label'   => 'parametres_veille',
        'help'    => 'parametres_veille_help',
        'read'    => 'ca',
        'write'   => 'club-admin',
        'default' => FALSE,
        'requires' => array('smtp_host'),
    ),
    'auto_planchiste' => array(
        'tab'     => 'modules',
        'store'   => 'file:program',
        'type'    => 'bool',
        'label'   => 'parametres_auto_planchiste',
        'help'    => 'parametres_auto_planchiste_help',
        'read'    => 'ca',
        'write'   => 'club-admin',
        'default' => FALSE,
    ),

    // Onglet Messagerie : serveur d'envoi (email.php) et redirection de test (program.php).
    'protocol' => array(
        'tab'      => 'messagerie',
        'store'    => 'file:email',
        'type'     => 'select',
        'options'  => array('mail' => 'mail (PHP)', 'smtp' => 'SMTP', 'sendmail' => 'sendmail'),
        'label'    => 'parametres_protocol',
        'help'     => 'parametres_protocol_help',
        'read'     => 'club-admin',
        'write'    => 'club-admin',
        'required' => TRUE,
        'default'  => 'mail',
    ),
    'smtp_host' => array(
        'tab'         => 'messagerie',
        'store'       => 'file:email',
        'type'        => 'text',
        'label'       => 'parametres_smtp_host',
        'help'        => 'parametres_smtp_host_help',
        'read'        => 'club-admin',
        'write'       => 'club-admin',
        'required_if' => array('protocol' => 'smtp'),
        'rules'       => 'max_length[255]',
    ),
    'smtp_port' => array(
        'tab'         => 'messagerie',
        'store'       => 'file:email',
        'type'        => 'int',
        'label'       => 'parametres_smtp_port',
        'help'        => 'parametres_smtp_port_help',
        'read'        => 'club-admin',
        'write'       => 'club-admin',
        'required_if' => array('protocol' => 'smtp'),
        'default'     => 25,
        'rules'       => 'range[1,65535]',
    ),
    'smtp_crypto' => array(
        'tab'     => 'messagerie',
        'store'   => 'file:email',
        'type'    => 'select',
        'options' => array('' => '—', 'tls' => 'TLS (STARTTLS)', 'ssl' => 'SSL'),
        'label'   => 'parametres_smtp_crypto',
        'help'    => 'parametres_smtp_crypto_help',
        'read'    => 'club-admin',
        'write'   => 'club-admin',
    ),
    'smtp_user' => array(
        'tab'   => 'messagerie',
        'store' => 'file:email',
        'type'  => 'text',
        'label' => 'parametres_smtp_user',
        'help'  => 'parametres_smtp_user_help',
        'read'  => 'club-admin',
        'write' => 'club-admin',
        'rules' => 'max_length[255]',
    ),
    'smtp_pass' => array(
        'tab'    => 'messagerie',
        'store'  => 'file:email',
        'type'   => 'password',
        'label'  => 'parametres_smtp_pass',
        'help'   => 'parametres_smtp_pass_help',
        'read'   => 'club-admin',
        'write'  => 'club-admin',
        'secret' => TRUE,
        'rules'  => 'max_length[255]',
    ),
    'test_email' => array(
        'tab'   => 'messagerie',
        'store' => 'file:program',
        'type'  => 'email',
        'label' => 'parametres_test_email',
        'help'  => 'parametres_test_email_help',
        'read'  => 'club-admin',
        'write' => 'club-admin',
    ),
    'test_phone' => array(
        'tab'   => 'messagerie',
        'store' => 'file:program',
        'type'  => 'text',
        'label' => 'parametres_test_phone',
        'help'  => 'parametres_test_phone_help',
        'read'  => 'club-admin',
        'write' => 'club-admin',
        'rules' => 'max_length[20]',
    ),

    // Onglet Pages publiques.
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
