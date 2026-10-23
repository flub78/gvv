<?php

/**
 * Newsletter
 *
 * Archive des newsletters publiées, accessible aux membres actifs connectés.
 *
 * Routes :
 *   GET newsletter/index   Liste des newsletters publiées
 */

class Newsletter extends MY_Controller {

    function __construct() {
        parent::__construct();

        if (!$this->config->item('veille')) {
            show_404();
        }

        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
            return;
        }

        $this->lang->load('newsletter');
        $this->load->model('newsletter_model');
    }

    /**
     * Sert un PDF à un membre connecté.
     */
    public function pdf($id) {
        $newsletter = $this->newsletter_model->get((int) $id);
        if (!$newsletter || $newsletter['status'] !== 'published') {
            show_404();
            return;
        }
        $path = $newsletter['pdf_path'];
        // Vérifier que le fichier est dans le répertoire uploads/newsletters/
        $real      = realpath($path);
        $real_base = realpath('./uploads/newsletters/');
        if (!$real || !$real_base || strpos($real, $real_base) !== 0 || !file_exists($real)) {
            show_404();
            return;
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($real) . '"');
        header('Content-Length: ' . filesize($real));
        readfile($real);
        exit;
    }

    /**
     * Archive des newsletters publiées.
     */
    public function index() {
        $data = array(
            'title'       => $this->lang->line('newsletter_archive_title'),
            'newsletters' => $this->newsletter_model->get_published(),
        );
        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu');
        $this->load->view('bs_banner');
        $this->load->view('newsletter/bs_archive', $data);
        $this->load->view('bs_footer');
    }
}
