<?php

/**
 * Newsletter_admin
 *
 * Administration de la newsletter de veille : gestion des abonnés,
 * publication manuelle d'une newsletter PDF, envoi aux abonnés.
 * Réservé aux club-admin.
 *
 * Routes :
 *   GET  newsletter_admin/index          Liste des abonnés
 *   POST newsletter_admin/delete/:id     Suppression d'un abonné
 *   GET  newsletter_admin/publish        Formulaire de publication manuelle
 *   POST newsletter_admin/publish        Traitement de la publication
 *   GET  newsletter_admin/send/:id       Envoi d'une newsletter publiée aux abonnés
 */

class Newsletter_admin extends MY_Controller {

    private static $pdf_upload_dir = './uploads/newsletters/';

    function __construct() {
        parent::__construct();

        if (!$this->config->item('veille')) {
            show_404();
        }

        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
            return;
        }

        if (!$this->user_has_role('club-admin')) {
            show_error('Accès refusé', 403);
            return;
        }

        $this->lang->load('newsletter');
        $this->load->model('newsletter_model');
        $this->load->model('newsletter_subscribers_model');
    }

    /**
     * Liste des abonnés.
     */
    public function index() {
        $data = array(
            'title'       => $this->lang->line('newsletter_admin_title'),
            'subscribers' => $this->newsletter_subscribers_model->get_all(),
            'message'     => $this->session->flashdata('message'),
        );
        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu');
        $this->load->view('bs_banner');
        $this->load->view('newsletter_admin/bs_subscribers', $data);
        $this->load->view('bs_footer');
    }

    /**
     * Supprime un abonné.
     */
    public function delete($id) {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect('newsletter_admin');
            return;
        }
        $this->newsletter_subscribers_model->delete((int) $id);
        $this->session->set_flashdata('message',
            '<div class="alert alert-success">' . $this->lang->line('newsletter_delete_success') . '</div>');
        redirect('newsletter_admin');
    }

    /**
     * Formulaire de publication manuelle (GET) et traitement (POST).
     * Upload d'un PDF + création de l'entrée newsletter + envoi aux abonnés.
     */
    public function publish() {
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $this->_post_publish();
            return;
        }

        $data = array(
            'title'       => $this->lang->line('newsletter_publish_title'),
            'newsletters' => $this->newsletter_model->get_all(),
            'message'     => $this->session->flashdata('message'),
        );
        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu');
        $this->load->view('bs_banner');
        $this->load->view('newsletter_admin/bs_publish', $data);
        $this->load->view('bs_footer');
    }

    private function _post_publish() {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('nl_title',       $this->lang->line('newsletter_publish_newsletter_title'), 'trim|required|max_length[255]');
        $this->form_validation->set_rules('period_start',   $this->lang->line('newsletter_publish_period_start'),     'trim|required');
        $this->form_validation->set_rules('period_end',     $this->lang->line('newsletter_publish_period_end'),       'trim|required');

        if (!$this->form_validation->run()) {
            $data = array(
                'title'       => $this->lang->line('newsletter_publish_title'),
                'newsletters' => $this->newsletter_model->get_all(),
                'form_errors' => validation_errors(),
                'form_data'   => $this->input->post(),
            );
            $this->load->view('bs_header', $data);
            $this->load->view('bs_menu');
            $this->load->view('bs_banner');
            $this->load->view('newsletter_admin/bs_publish', $data);
            $this->load->view('bs_footer');
            return;
        }

        // Upload du PDF
        if (!is_dir(self::$pdf_upload_dir)) {
            mkdir(self::$pdf_upload_dir, 0775, TRUE);
        }

        $upload_config = array(
            'upload_path'   => self::$pdf_upload_dir,
            'allowed_types' => 'pdf',
            'max_size'      => 20480,
            'encrypt_name'  => TRUE,
        );
        $this->load->library('upload', $upload_config);

        if (!$this->upload->do_upload('pdf_file')) {
            $error = $this->upload->display_errors('', '');
            $data  = array(
                'title'       => $this->lang->line('newsletter_publish_title'),
                'newsletters' => $this->newsletter_model->get_all(),
                'form_errors' => '<p class="text-danger">' . htmlspecialchars($error) . '</p>',
                'form_data'   => $this->input->post(),
            );
            $this->load->view('bs_header', $data);
            $this->load->view('bs_menu');
            $this->load->view('bs_banner');
            $this->load->view('newsletter_admin/bs_publish', $data);
            $this->load->view('bs_footer');
            return;
        }

        $uploaded = $this->upload->data();
        $pdf_path = self::$pdf_upload_dir . $uploaded['file_name'];

        $newsletter_id = $this->newsletter_model->create(array(
            'title'        => $this->input->post('nl_title'),
            'period_start' => $this->input->post('period_start'),
            'period_end'   => $this->input->post('period_end'),
            'draft_path'   => NULL,
        ));

        if (!$newsletter_id) {
            $this->session->set_flashdata('message',
                '<div class="alert alert-danger">' . $this->lang->line('newsletter_publish_error') . '</div>');
            redirect('newsletter_admin/publish');
            return;
        }

        // Passage direct draft → approved → published
        $this->newsletter_model->transition($newsletter_id, 'reviewing');
        $this->newsletter_model->transition($newsletter_id, 'validating');
        $this->newsletter_model->transition($newsletter_id, 'approved');
        $this->newsletter_model->update($newsletter_id, array('pdf_path' => $pdf_path));
        $this->newsletter_model->transition($newsletter_id, 'published');

        $sent = $this->_send_to_subscribers($newsletter_id);

        $this->session->set_flashdata('message',
            '<div class="alert alert-success">' . sprintf($this->lang->line('newsletter_publish_success'), $sent) . '</div>');
        redirect('newsletter_admin/publish');
    }

    /**
     * Envoie une newsletter déjà publiée aux abonnés actifs (hors doublons).
     *
     * @param int $newsletter_id
     * @return int Nombre d'emails envoyés
     */
    public function send($newsletter_id) {
        $newsletter = $this->newsletter_model->get((int) $newsletter_id);
        if (!$newsletter || $newsletter['status'] !== 'published') {
            show_error('Newsletter introuvable ou non publiée.', 404);
            return;
        }
        $sent = $this->_send_to_subscribers((int) $newsletter_id);
        $this->session->set_flashdata('message',
            '<div class="alert alert-success">' . sprintf($this->lang->line('newsletter_publish_success'), $sent) . '</div>');
        redirect('newsletter_admin/publish');
    }

    /**
     * Envoie la newsletter à tous les abonnés actifs non encore notifiés.
     *
     * @param int $newsletter_id
     * @return int Nombre d'envois réalisés
     */
    private function _send_to_subscribers($newsletter_id) {
        $newsletter  = $this->newsletter_model->get($newsletter_id);
        $subscribers = $this->newsletter_subscribers_model->get_active();
        if (empty($subscribers) || empty($newsletter)) return 0;

        $this->load->library('email');
        $this->load->helper('email');

        $nom_club  = $this->config->item('nom_club') ?: 'Le club';
        $from      = $this->config->item('email_club');
        $unsub_url = site_url('newsletter_public/unsubscribe/');
        $period    = substr($newsletter['period_start'], 0, 7) . ' – ' . substr($newsletter['period_end'], 0, 7);
        $subject   = '[' . $nom_club . '] Newsletter veille — ' . $period;

        $sent = 0;
        foreach ($subscribers as $sub) {
            if ($this->newsletter_subscribers_model->already_sent($newsletter_id, $sub['id'])) {
                continue;
            }

            $to = test_intercept_email($sub['email'], $subject);

            $body  = 'Cher(e) ' . $sub['first_name'] . ' ' . $sub['last_name'] . ',' . "\n\n";
            $body .= 'Voici la newsletter de veille sécurité et réglementaire de ' . $nom_club . ' pour la période ' . $period . '.' . "\n\n";
            $body .= 'Vous trouverez la newsletter en pièce jointe de cet email.' . "\n\n";
            $body .= 'Pour vous désabonner : ' . $unsub_url . $sub['unsubscribe_token'] . "\n";

            $this->email->clear(TRUE);
            $this->email->set_mailtype('text');
            $this->email->set_newline("\r\n");
            $this->email->from($from, $nom_club);
            $this->email->to($to);
            $this->email->subject($subject);
            $this->email->message($body);

            if (!empty($newsletter['pdf_path']) && file_exists($newsletter['pdf_path'])) {
                $this->email->attach($newsletter['pdf_path'], 'attachment', $newsletter['title'] . '.pdf', 'application/pdf');
            }

            if ($this->email->send()) {
                $this->newsletter_subscribers_model->record_sending($newsletter_id, $sub['id']);
                $sent++;
                log_message('info', 'newsletter_admin: envoi newsletter #' . $newsletter_id . ' à ' . $sub['email']);
            } else {
                log_message('error', 'newsletter_admin: échec envoi #' . $newsletter_id . ' à ' . $sub['email']);
            }
        }
        return $sent;
    }
}
