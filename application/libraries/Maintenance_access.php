<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Maintenance Access Library
 *
 * Centralise la matrice de droits du module Maintenance (PRD EF8) au lieu
 * de dupliquer des `user_has_role()` bruts dans chaque controleur --
 * miroir de `Formation_access` pour le module Formation.
 *
 * - mecano/admin : ecriture complete sur leur perimetre (section pour
 *   mecano, toutes sections pour admin).
 * - ca/tresorier : lecture seule de l'etat de navigabilite et de
 *   l'historique (dossiers, operations, bulletins, programmes) de leur
 *   section (PRD EF8.3, EF7.4).
 * - tout autre membre connecte (« pilote ») : lecture seule limitee a
 *   l'etat de navigabilite, sans detail d'intervention (PRD EF8.4).
 *
 * @package libraries
 * @see doc/prds/maintenance_aeronefs_prd.md (EF8)
 * @see doc/plans/maintenance_aeronefs_plan.md (Etape 6.1)
 */
class Maintenance_access {

    protected $CI;

    public function __construct() {
        $this->CI =& get_instance();
    }

    /**
     * Section courante (session), NULL si "Toutes les sections" ou non definie
     * -- meme normalisation que MY_Controller::_has_role().
     */
    private function section_id() {
        $raw = $this->CI->session->userdata('section');
        return ($raw !== NULL && $raw !== FALSE && (int) $raw > 0) ? (int) $raw : NULL;
    }

    /**
     * Mecano de la section courante, ou admin (toutes sections).
     * @return bool
     */
    public function is_mecano() {
        if ($this->CI->dx_auth->is_admin()) {
            return true;
        }
        $this->CI->load->library('Gvv_Authorization');
        return $this->CI->gvv_authorization->has_role(
            $this->CI->dx_auth->get_user_id(), 'mecano', $this->section_id()
        );
    }

    /**
     * Ecriture complete sur le module (creation/edition/suppression logique) --
     * alias explicite de is_mecano() pour la lisibilite des controleurs.
     * @return bool
     */
    public function can_write() {
        return $this->is_mecano();
    }

    /**
     * Lecture de l'historique (dossiers, operations, bulletins, programmes)
     * de la section courante : mecano/admin, responsable de section (ca)
     * et tresorier (PRD EF8.3, EF7.4).
     * @return bool
     */
    public function can_view_historique() {
        if ($this->is_mecano()) {
            return true;
        }
        $this->CI->load->library('Gvv_Authorization');
        return $this->CI->gvv_authorization->has_any_role(
            $this->CI->dx_auth->get_user_id(), array('ca', 'tresorier'), $this->section_id()
        );
    }

    /**
     * Lecture de l'etat de navigabilite (vue de synthese) : accessible a
     * tout membre connecte, y compris le pilote (PRD EF8.4) -- aucun detail
     * d'intervention n'est expose par cette vue.
     * @return bool
     */
    public function can_view_synthese() {
        return $this->CI->dx_auth->is_logged_in();
    }

    /**
     * Bloque l'action courante si l'utilisateur n'a pas les droits
     * d'ecriture (403 explicite, jamais silencieux).
     * @return void
     */
    public function require_write() {
        if (!$this->can_write()) {
            show_error($this->CI->lang->line('maintenance_acces_refuse'), 403);
        }
    }
}

/* End of file Maintenance_access.php */
/* Location: ./application/libraries/Maintenance_access.php */
