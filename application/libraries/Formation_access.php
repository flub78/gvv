<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Formation Access Library
 *
 * Handles feature flag checking and access control for the training tracking system.
 * All formation controllers should use this library to verify access.
 *
 * Usage in controllers:
 * ```php
 * $this->load->library('formation_access');
 * $this->formation_access->check_access_or_403();
 * ```
 *
 * @author GVV Development Team
 * @see doc/prds/suivi_formation_prd.md
 */
class Formation_access {

    protected $CI;

    /**
     * Constructor
     */
    public function __construct() {
        $this->CI =& get_instance();
        // program.php is auto-loaded in config/autoload.php
    }

    /**
     * Check if the formation feature is enabled
     *
     * @return bool True if gestion_formations flag is enabled
     */
    public function is_enabled() {
        return (bool) $this->CI->config->item('gestion_formations');
    }

    /**
     * Check access and show 403 error if feature is disabled
     *
     * Should be called in controller constructors to prevent access
     * when the formation feature is not enabled.
     *
     * @return void
     * @throws CI_Exceptions Shows 403 error if disabled
     */
    public function check_access_or_403() {
        if (!$this->is_enabled()) {
            show_error(
                $this->CI->lang->line('formation_feature_disabled') ?:
                'La fonctionnalité de gestion des formations n\'est pas activée.',
                403,
                'Accès non autorisé'
            );
        }
    }

    /**
     * Check if current user can manage programs (admin only)
     *
     * @return bool True if user can manage programs
     */
    public function can_manage_programmes() {
        if (!$this->is_enabled()) {
            return false;
        }
        // Admin can always manage programmes
        if ($this->CI->dx_auth->is_admin()) {
            return true;
        }

        // Members of the Conseil d'Administration (CA) should also be allowed
        $this->CI->load->model('membres_model');
        $username = $this->CI->dx_auth->get_username();
        $membre = $this->CI->membres_model->get_by_id('mlogin', $username);

        if ($membre && isset($membre['mniveaux'])) {
            return (($membre['mniveaux'] & CA) != 0);
        }

        return false;
    }

    /**
     * Check if current user is an instructor
     *
     * @return bool True if user has instructor privileges
     */
    public function is_instructeur() {
        if (!$this->is_enabled()) {
            return false;
        }

        // Check mniveaux bit flags for instructor roles
        // ITP (32768), IVV (65536), FI_AVION (131072), FE_AVION (262144)
        $instructeur_flags = 32768 + 65536 + 131072 + 262144;

        $this->CI->load->model('membres_model');
        $username = $this->CI->dx_auth->get_username();
        $membre = $this->CI->membres_model->get_by_id('mlogin', $username);

        if ($membre && isset($membre['mniveaux'])) {
            return (($membre['mniveaux'] & $instructeur_flags) != 0);
        }

        return false;
    }

    /**
     * Check if user can create/edit training sessions
     *
     * @return bool True if user can manage sessions
     */
    public function can_manage_seances() {
        return $this->is_instructeur() || $this->CI->dx_auth->is_admin();
    }

    /**
     * Check if user can view a specific enrollment
     *
     * Rules:
     * - Admin can view all
     * - Instructor can view their students
     * - Pilot can view their own enrollments
     *
     * @param array $inscription Enrollment data
     * @return bool True if user can view
     */
    public function can_view_inscription($inscription) {
        if (!$this->is_enabled()) {
            return false;
        }

        $username = $this->CI->dx_auth->get_username();

        // Admin can view all
        if ($this->CI->dx_auth->is_admin()) {
            return true;
        }

        // Student can view their own
        if ($inscription['pilote_id'] == $username) {
            return true;
        }

        // Instructor can view their students
        if ($inscription['instructeur_referent_id'] == $username) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can edit a specific session
     *
     * @param array $seance Session data
     * @return bool True if user can edit
     */
    public function can_edit_seance($seance) {
        if (!$this->is_enabled()) {
            return false;
        }

        $username = $this->CI->dx_auth->get_username();

        // Admin can edit all
        if ($this->CI->dx_auth->is_admin()) {
            return true;
        }

        // Instructor can edit their own sessions
        if ($seance['instructeur_id'] == $username) {
            return true;
        }

        return false;
    }

    /**
     * Get current user's member login
     *
     * @return string|null Member login or null if not logged in
     */
    public function get_current_pilote_id() {
        return $this->CI->dx_auth->get_username();
    }
}

/* End of file Formation_access.php */
/* Location: ./application/libraries/Formation_access.php */
