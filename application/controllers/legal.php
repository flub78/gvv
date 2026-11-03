<?php

/**
 * Legal
 *
 * Pages publiques, accessibles sans authentification :
 *   GET legal/confidentialite  Règles de confidentialité
 *   GET legal/conditions       Conditions d'utilisation
 *
 * Le texte vient de la page Configuration (onglet Pages publiques) ou, à défaut,
 * du gabarit générique livré avec GVV. Voir libraries/Legal_pages.php.
 *
 * @see doc/prds/configuration_unifiee_prd.md §6 bis
 */
class Legal extends MY_Controller {

    function __construct() {
        parent::__construct();
        $this->lang->load('legal');
        $this->load->library('Legal_pages');
        $this->load->helper('validation');
    }

    public function index() {
        redirect('legal/confidentialite');
    }

    public function confidentialite() {
        $this->_show('confidentialite');
    }

    public function conditions() {
        $this->_show('conditions');
    }

    private function _show($page) {
        $content = $this->legal_pages->page($page, $this->config->item('language'));
        $data = array(
            'title'      => $this->lang->line('legal_' . $page),
            'html'       => $content['html'],
            'updated_at' => $content['updated_at'],
            'page'       => $page,
        );
        $this->load->view('bs_header', $data);
        $this->load->view('bs_banner');
        $this->load->view('legal/bs_page', $data);
        $this->load->view('bs_footer');
    }
}

/* End of file legal.php */
