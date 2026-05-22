<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Contrôleur — Fusion de deux comptes membres en doublon.
 *
 * Accès réservé aux utilisateurs listés dans la configuration 'dev_users'.
 *
 * Actions :
 *   index()    → formulaire de sélection source / destination
 *   preview()  → rapport de prévisualisation (POST)
 *   executer() → exécution de la fusion (POST avec confirmation)
 */
class Membres_fusion extends CI_Controller {

    protected $controller = 'membres_fusion';

    public function __construct() {
        parent::__construct();
        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
        }
        $this->_check_dev_user();
        $this->load->model('membres_fusion_model');
        $this->lang->load('gvv');

        // Bouton retour → tableau de bord Développement
        $this->lang->load('tableaux_de_bord');
        $this->load->vars([
            'nav_back_url'   => $this->session->userdata('nav_from_url')   ?: 'welcome/section/dev',
            'nav_back_label' => $this->session->userdata('nav_from_label') ?: $this->lang->line('db_section_dev'),
        ]);
    }

    public function index() {
        $data = array(
            'controller' => $this->controller,
            'membres'    => $this->membres_fusion_model->get_all_membres(),
            'error'      => $this->session->flashdata('fusion_error') ?: '',
        );
        load_last_view('membres_fusion/bs_index', $data);
    }

    public function preview() {
        $source      = trim($this->input->post('source') ?: '');
        $destination = trim($this->input->post('destination') ?: '');

        if (empty($source) || empty($destination)) {
            $this->session->set_flashdata('fusion_error', $this->lang->line('gvv_fusion_error_select_both'));
            redirect(controller_url('membres_fusion'));
            return;
        }
        if ($source === $destination) {
            $this->session->set_flashdata('fusion_error', $this->lang->line('gvv_fusion_error_same'));
            redirect(controller_url('membres_fusion'));
            return;
        }

        $rapport = $this->membres_fusion_model->analyse($source, $destination);

        if (!$rapport['source'] || !$rapport['destination']) {
            $this->session->set_flashdata('fusion_error', $this->lang->line('gvv_fusion_error_not_found'));
            redirect(controller_url('membres_fusion'));
            return;
        }

        $data = array(
            'controller' => $this->controller,
            'source'     => $source,
            'destination'=> $destination,
            'rapport'    => $rapport,
        );
        load_last_view('membres_fusion/bs_preview', $data);
    }

    public function executer() {
        $source      = trim($this->input->post('source') ?: '');
        $destination = trim($this->input->post('destination') ?: '');

        if (empty($source) || empty($destination) || $source === $destination) {
            $this->session->set_flashdata('fusion_error', $this->lang->line('gvv_fusion_error_invalid'));
            redirect(controller_url('membres_fusion'));
            return;
        }

        $result = $this->membres_fusion_model->fusionner($source, $destination);

        if ($result['success']) {
            $this->session->set_flashdata('fusion_success',
                sprintf($this->lang->line('gvv_fusion_success'), $source, $destination)
            );
        } else {
            $this->session->set_flashdata('fusion_error', $result['error']);
        }

        redirect(controller_url('membres_fusion'));
    }

    // -------------------------------------------------------------------------

    private function _check_dev_user() {
        $dev_users = array_map('trim', explode(',', $this->config->item('dev_users') ?: ''));
        if (!in_array($this->dx_auth->get_username(), $dev_users)) {
            show_error($this->lang->line('gvv_fusion_error_access'), 403);
        }
    }
}
