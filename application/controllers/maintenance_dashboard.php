<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Maintenance Dashboard Controller
 *
 * Point d'entree unique du module Maintenance, regroupant les cartes
 * vers chacun des ecrans (equipements, programmes, dossiers, operations,
 * bulletins, synthese). Les deux cartes reservees sur le dashboard
 * principal (db_card_maintenance_prog, db_card_maintenance_ops)
 * pointent ici plutot que directement vers un sous-ecran (Etape 5.7).
 *
 * @package controllers
 * @see doc/prds/maintenance_aeronefs_prd.md (EF9)
 * @see doc/plans/maintenance_aeronefs_plan.md (Etape 5.7)
 */
class Maintenance_dashboard extends MY_Controller {

    public function __construct() {
        parent::__construct();

        $this->lang->load('maintenance');
        $this->lang->load('gvv');
        $this->lang->load('tableaux_de_bord');

        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
        }

        // Visibilite conditionnee a is_mecano || is_admin (PRD EF9.3,
        // coherent avec l'existant : dx_auth->is_admin() bypass deja
        // integre dans user_has_role()).
        if (!$this->user_has_role('mecano')) {
            show_error($this->lang->line('maintenance_acces_refuse'), 403);
        }

        $this->load->vars([
            'nav_back_url'   => 'welcome/section/maintenance',
            'nav_back_label' => $this->lang->line('db_section_maintenance'),
        ]);
    }

    public function index() {
        $this->load->view('maintenance_dashboard/index', array());
    }
}

/* End of file maintenance_dashboard.php */
/* Location: ./application/controllers/maintenance_dashboard.php */
