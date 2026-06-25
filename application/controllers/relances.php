<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Relances — Suivi des comptes membres débiteurs.
 *
 * Phase 1 : affichage de la liste des débiteurs avec seuils d'alerte.
 * Phase 2 : relances email (à implémenter ultérieurement).
 *
 * Rôles autorisés : tresorier, bureau, club-admin.
 *
 * Playwright tests :
 *   cd playwright && npx playwright test tests/relances.spec.js
 */

include('./application/libraries/Gvv_Controller.php');

class Relances extends MY_Controller
{
    function __construct()
    {
        parent::__construct();

        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
        }

        $this->require_roles(['tresorier', 'bureau', 'club-admin']);

        $this->load->model('relances_model');
        $this->load->model('configuration_model');
        $this->lang->load('relances');
    }

    /**
     * Page principale : liste des débiteurs.
     */
    public function index()
    {
        $seuil_alarme   = (float)($this->configuration_model->get_param('relances.seuil_alarme')   ?? 300);
        $seuil_critique = (float)($this->configuration_model->get_param('relances.seuil_critique') ?? 500);

        $data = $this->relances_model->get_debiteurs();

        load_last_view('relances/bs_relancesView', array(
            'sections'        => $data['sections'],
            'debiteurs'       => $data['rows'],
            'seuil_alarme'    => $seuil_alarme,
            'seuil_critique'  => $seuil_critique,
            'controller'      => 'relances',
        ));
    }

    /**
     * POST : sauvegarde les seuils d'alerte.
     */
    public function update_seuils()
    {
        if (!$this->input->is_ajax_request() && strtolower($_SERVER['REQUEST_METHOD']) !== 'post') {
            redirect('relances/index');
        }

        $seuil_alarme   = (float)$this->input->post('seuil_alarme');
        $seuil_critique = (float)$this->input->post('seuil_critique');

        if ($seuil_alarme <= 0 || $seuil_critique <= 0 || $seuil_alarme >= $seuil_critique) {
            $this->session->set_flashdata('error', $this->lang->line('relances_seuils_invalides'));
            redirect('relances/index');
            return;
        }

        $this->_upsert_config('relances.seuil_alarme',   $seuil_alarme);
        $this->_upsert_config('relances.seuil_critique', $seuil_critique);

        $this->session->set_flashdata('success', $this->lang->line('relances_seuils_sauvegardes'));
        redirect('relances/index');
    }

    /**
     * Upsert d'une clé dans la table configuration.
     */
    private function _upsert_config($cle, $valeur)
    {
        $exists = $this->db->where('cle', $cle)->count_all_results('configuration') > 0;
        if ($exists) {
            $this->db->where('cle', $cle)->update('configuration', array(
                'valeur'     => (string)$valeur,
                'updated_at' => date('Y-m-d H:i:s'),
            ));
        } else {
            $this->db->insert('configuration', array(
                'cle'        => $cle,
                'valeur'     => (string)$valeur,
                'categorie'  => 'relances',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ));
        }
    }
}
