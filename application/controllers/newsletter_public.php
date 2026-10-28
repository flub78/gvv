<?php

/**
 * Newsletter_public
 *
 * Pages publiques d'abonnement et de désinscription à la newsletter de veille.
 * Accessibles sans authentification GVV.
 *
 * Routes :
 *   GET/POST newsletter_public/subscribe      Formulaire d'abonnement
 *   GET      newsletter_public/confirm/:token Confirmation double opt-in, envoi du dernier numéro paru
 *   GET      newsletter_public/unsubscribe/:token Désinscription
 */

class Newsletter_public extends MY_Controller {

    function __construct() {
        parent::__construct();

        if (!$this->config->item('veille')) {
            show_404();
        }

        $this->lang->load('newsletter');
        $this->load->model('newsletter_subscribers_model');
        $this->load->helper('email');
        $this->load->library('email');
    }

    /**
     * Formulaire d'abonnement (GET) et traitement (POST).
     */
    public function subscribe() {
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $this->_post_subscribe();
            return;
        }

        $data = array(
            'title'      => $this->lang->line('newsletter_subscribe_title'),
            'flash_info' => $this->session->flashdata('newsletter_info'),
            'flash_error'=> $this->session->flashdata('newsletter_error'),
        );
        $this->load->view('bs_header', $data);
        $this->load->view('bs_banner');
        $this->load->view('newsletter_public/bs_subscribe', $data);
        $this->load->view('bs_footer');
    }

    private function _post_subscribe() {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('first_name', $this->lang->line('newsletter_first_name'), 'trim|required|max_length[100]');
        $this->form_validation->set_rules('last_name',  $this->lang->line('newsletter_last_name'),  'trim|required|max_length[100]');
        $this->form_validation->set_rules('email',      $this->lang->line('newsletter_email'),      'trim|required|valid_email|max_length[255]');

        if (!$this->form_validation->run()) {
            $data = array(
                'title'       => $this->lang->line('newsletter_subscribe_title'),
                'form_errors' => validation_errors(),
                'form_data'   => $this->input->post(),
            );
            $this->load->view('bs_header', $data);
            $this->load->view('bs_banner');
            $this->load->view('newsletter_public/bs_subscribe', $data);
            $this->load->view('bs_footer');
            return;
        }

        $email      = $this->input->post('email');
        $first_name = $this->input->post('first_name');
        $last_name  = $this->input->post('last_name');

        $result = $this->newsletter_subscribers_model->subscribe(array(
            'email'      => $email,
            'first_name' => $first_name,
            'last_name'  => $last_name,
        ));

        if ($result === FALSE) {
            $this->session->set_flashdata('newsletter_error', $this->lang->line('newsletter_already_subscribed'));
            redirect('newsletter_public/subscribe');
            return;
        }

        $this->_send_confirmation_email($email, $first_name, $last_name, $result['confirmation_token']);

        $this->session->set_flashdata('newsletter_info',
            sprintf($this->lang->line('newsletter_subscribe_success'), htmlspecialchars($email)));
        redirect('newsletter_public/subscribe');
    }

    private function _send_confirmation_email($email, $first_name, $last_name, $token) {
        $confirm_url = site_url('newsletter_public/confirm/' . $token);
        $nom_club    = $this->config->item('nom_club') ?: 'Le club';
        $from        = $this->config->item('email_club');
        $subject     = '[' . $nom_club . '] ' . $this->lang->line('newsletter_subscribe_title');

        $body  = 'Bonjour ' . $first_name . ' ' . $last_name . ',' . "\n\n";
        $body .= 'Merci de votre abonnement à la newsletter de veille de ' . $nom_club . '.' . "\n\n";
        $body .= 'Cliquez sur le lien ci-dessous pour confirmer votre abonnement :' . "\n";
        $body .= $confirm_url . "\n\n";
        $body .= 'Si vous n\'avez pas fait cette demande, ignorez simplement cet email.' . "\n";

        $to = test_intercept_email($email, $subject);
        $this->email->clear();
        $this->email->set_mailtype('text');
        $this->email->set_newline("\r\n");
        $this->email->from($from, $nom_club);
        $this->email->to($to);
        $this->email->subject($subject);
        $this->email->message($body);
        if (!$this->email->send()) {
            log_message('error', 'newsletter_public: confirmation email failed to ' . $email . ' — ' . $this->email->print_debugger());
        }
    }

    /**
     * Confirmation d'abonnement via le token envoyé par email.
     */
    public function confirm($token = '') {
        $sub = $this->newsletter_subscribers_model->get_by_confirmation_token($token);
        $ok  = $sub && $this->newsletter_subscribers_model->confirm($token);
        $level   = $ok ? 'success' : 'warning';
        $message = $this->lang->line('newsletter_confirm_invalid');
        if ($ok) {
            // Accueil : le dernier numéro paru est envoyé au nouvel abonné
            $this->load->library('Newsletter_mailer');
            $latest  = $this->newsletter_mailer->send_latest($sub);
            $message = $this->lang->line('newsletter_confirm_success_' . $latest);
            if ($latest === Newsletter_mailer::LATEST_FAILED) $level = 'warning';
        }
        $data = array(
            'title'   => $this->lang->line('newsletter_confirm_title'),
            'success' => $ok,
            'level'   => $level,
            'message' => $message,
        );
        $this->load->view('bs_header', $data);
        $this->load->view('bs_banner');
        $this->load->view('newsletter_public/bs_confirm', $data);
        $this->load->view('bs_footer');
    }

    /**
     * Désinscription via le token inclus dans chaque email envoyé.
     */
    public function unsubscribe($token = '') {
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $token = $this->input->post('token');
            $ok    = $this->newsletter_subscribers_model->unsubscribe_by_token($token);
            $data  = array(
                'title'   => $this->lang->line('newsletter_unsubscribe_title'),
                'success' => $ok,
                'message' => $ok
                    ? $this->lang->line('newsletter_unsubscribe_success')
                    : $this->lang->line('newsletter_unsubscribe_invalid'),
            );
            $this->load->view('bs_header', $data);
            $this->load->view('bs_banner');
            $this->load->view('newsletter_public/bs_unsubscribe', $data);
            $this->load->view('bs_footer');
            return;
        }

        $data = array(
            'title' => $this->lang->line('newsletter_unsubscribe_title'),
            'token' => $token,
        );
        $this->load->view('bs_header', $data);
        $this->load->view('bs_banner');
        $this->load->view('newsletter_public/bs_unsubscribe', $data);
        $this->load->view('bs_footer');
    }
}
