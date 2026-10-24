<?php

/**
 * Newsletter_admin
 *
 * Administration de la newsletter de veille : gestion des abonnés,
 * publication manuelle d'une newsletter PDF, envoi aux abonnés,
 * configuration IMAP/SMTP, pages de test d'infrastructure.
 * Réservé aux club-admin.
 *
 * Routes :
 *   GET  newsletter_admin/index          Liste des abonnés
 *   POST newsletter_admin/delete/:id     Suppression d'un abonné
 *   GET  newsletter_admin/publish        Formulaire de publication manuelle
 *   POST newsletter_admin/publish        Traitement de la publication
 *   GET  newsletter_admin/send/:id       Envoi d'une newsletter publiée aux abonnés
 *   GET/POST newsletter_admin/configuration  Configuration IMAP/SMTP
 *   GET/POST newsletter_admin/test_email     Validation envoi email
 *   GET/POST newsletter_admin/test_imap      Validation lecture IMAP
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
        $this->load->library('Newsletter_imap');
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

        $cfg       = $this->_load_newsletter_config();
        $smtp      = $this->_init_newsletter_smtp($cfg);
        $from      = $smtp['from'];
        $nom_club  = $smtp['from_name'] ?: $this->config->item('nom_club') ?: 'Le club';
        $unsub_url = site_url('newsletter_public/unsubscribe/');
        $period    = substr($newsletter['period_start'], 0, 7) . ' – ' . substr($newsletter['period_end'], 0, 7);
        $subject   = '[' . $nom_club . '] Newsletter veille — ' . $period;

        $sent = 0;
        foreach ($subscribers as $sub) {
            if ($this->newsletter_subscribers_model->already_sent($newsletter_id, $sub['id'])) {
                continue;
            }

            // Diffusion déclenchée manuellement : envoi aux abonnés réels, pas de redirection test_email
            $to = $sub['email'];

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

    // ══════════════════════════════════════════════════════════════════════
    // Configuration et validation d'infrastructure
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Page de configuration IMAP/SMTP (GET = affiche, POST = sauvegarde).
     */
    public function configuration() {
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $this->_post_configuration();
            return;
        }
        $cfg = $this->_load_newsletter_config();
        $data = array(
            'title'   => $this->lang->line('newsletter_config_title'),
            'cfg'     => $cfg,
            'message' => $this->session->flashdata('message'),
        );
        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu');
        $this->load->view('bs_banner');
        $this->load->view('newsletter_admin/bs_configuration', $data);
        $this->load->view('bs_footer');
    }

    private function _post_configuration() {
        $fields = array(
            'newsletter_imap_host', 'newsletter_imap_port', 'newsletter_imap_user',
            'newsletter_imap_ssl', 'newsletter_imap_mailbox',
            'newsletter_smtp_host', 'newsletter_smtp_port', 'newsletter_smtp_crypto',
            'newsletter_smtp_user',
            'newsletter_smtp_from', 'newsletter_smtp_from_name',
            'newsletter_reviewers', 'newsletter_validators', 'newsletter_review_delay_days',
            'newsletter_storage_path',
            'newsletter_review_subject', 'newsletter_review_body',
            'newsletter_context_path', 'newsletter_output_path',
            'newsletter_alerts_enabled',
            'newsletter_anthropic_model',
        );
        $values = array();
        foreach ($fields as $key) {
            $values[$key] = $this->input->post($key) ?: '';
        }
        // Mots de passe : ne mettre à jour que s'ils sont renseignés
        $cfg = $this->_load_newsletter_config();

        $raw_imap_pass = $this->input->post('newsletter_imap_password');
        $values['newsletter_imap_password'] = !empty($raw_imap_pass)
            ? Newsletter_imap::encode_password($raw_imap_pass)
            : ($cfg['newsletter_imap_password'] ?? '');

        $raw_smtp_pass = $this->input->post('newsletter_smtp_password');
        $values['newsletter_smtp_password'] = !empty($raw_smtp_pass)
            ? Newsletter_imap::encode_password($raw_smtp_pass)
            : ($cfg['newsletter_smtp_password'] ?? '');

        $raw_api_key = $this->input->post('newsletter_anthropic_api_key');
        $values['newsletter_anthropic_api_key'] = !empty($raw_api_key)
            ? Newsletter_imap::encode_password($raw_api_key)
            : ($cfg['newsletter_anthropic_api_key'] ?? '');

        $ok = $this->_save_newsletter_config($values);
        $this->session->set_flashdata('message', $ok
            ? '<div class="alert alert-success">' . $this->lang->line('newsletter_config_saved') . '</div>'
            : '<div class="alert alert-danger">Erreur d\'écriture du fichier de configuration. Vérifiez les droits sur ' . APPPATH . 'config/newsletter.php</div>'
        );
        redirect('newsletter_admin/configuration');
    }

    private function _load_newsletter_config() {
        $file = APPPATH . 'config/newsletter.php';
        if (!file_exists($file)) return array();
        // Utiliser file_get_contents + regex pour éviter que OPcache serve
        // une version compilée obsolète après une écriture file_put_contents.
        $content = file_get_contents($file);
        if (!$content) return array();
        $result = array();
        // Extrait les paires $config['clé'] = 'valeur';
        if (preg_match_all(
            '/\$config\[\'([^\']+)\'\]\s*=\s*\'((?:[^\'\\\\]|\\\\.)*)\'\s*;/',
            $content, $m, PREG_SET_ORDER
        )) {
            foreach ($m as $match) {
                // Désescaper les simples quotes escapées (\')
                $result[$match[1]] = str_replace("\\'", "'", $match[2]);
            }
        }
        return $result;
    }

    private function _save_newsletter_config($values) {
        $file    = APPPATH . 'config/newsletter.php';
        $content = "<?php\nif (!defined('BASEPATH')) exit('No direct script access allowed');\n\n";
        $content .= "/*\n|----------------------------------------------------------\n";
        $content .= "| Configuration veille réglementaire — généré par GVV\n";
        $content .= "|----------------------------------------------------------\n*/\n\n";
        foreach ($values as $key => $val) {
            $val_escaped = str_replace("'", "\\'", $val);
            $content .= "\$config['{$key}'] = '{$val_escaped}';\n";
        }
        $content .= "\n/* End of file newsletter.php */\n";

        if (file_put_contents($file, $content) === false) {
            log_message('error', 'newsletter_admin::_save_newsletter_config — impossible d\'écrire ' . $file);
            return false;
        }
        return true;
    }

    /**
     * Page de test d'envoi email (GET = formulaire, POST = envoi).
     */
    public function test_email() {
        $result = null;
        $form   = array();
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $form = array(
                'to_email' => $this->input->post('to_email'),
                'subject'  => $this->input->post('subject'),
                'body'     => $this->input->post('body'),
            );
            $result = $this->_run_test_email($form);
        }
        $cfg  = $this->_load_newsletter_config();
        $data = array(
            'title'      => $this->lang->line('newsletter_test_email_title'),
            'cfg'        => $cfg,
            'result'     => $result,
            'form'       => $form,
            'user_email' => $this->_current_user_email(),
        );
        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu');
        $this->load->view('bs_banner');
        $this->load->view('newsletter_admin/bs_test_email', $data);
        $this->load->view('bs_footer');
    }

    /**
     * Initialise la bibliothèque email avec le SMTP de la newsletter.
     * Si newsletter_smtp_host est vide, utilise la config globale (Brevo).
     * Retourne un tableau ['from'=>..., 'from_name'=>...].
     */
    private function _current_user_email() {
        $login = $this->dx_auth->get_username();
        if (empty($login)) return '';
        $row = $this->db->select('memail')->where('mlogin', $login)->get('membres')->row_array();
        return $row['memail'] ?? '';
    }

    private function _init_newsletter_smtp($cfg) {
        $this->load->library('email');
        $this->load->helper('email');

        if (!empty($cfg['newsletter_smtp_host'])) {
            $this->email->initialize(array(
                'protocol'     => 'smtp',
                'smtp_host'    => $cfg['newsletter_smtp_host'],
                'smtp_port'    => (int) ($cfg['newsletter_smtp_port']   ?: 587),
                'smtp_crypto'  => $cfg['newsletter_smtp_crypto'] ?: 'tls',
                'smtp_user'    => $cfg['newsletter_smtp_user']   ?: $cfg['newsletter_smtp_from'],
                'smtp_pass'    => Newsletter_imap::decode_password($cfg['newsletter_smtp_password'] ?? ''),
                'smtp_timeout' => 20,
            ));
        }
        // Si smtp_host vide : la config globale email.php (Brevo) reste active

        return array(
            'from'      => $cfg['newsletter_smtp_from']      ?: $this->config->item('email_club'),
            'from_name' => $cfg['newsletter_smtp_from_name'] ?: $this->config->item('nom_club'),
        );
    }

    private function _run_test_email($form = array()) {
        $cfg  = $this->_load_newsletter_config();
        $smtp = $this->_init_newsletter_smtp($cfg);
        $from      = $smtp['from'];
        $from_name = $smtp['from_name'];

        if (empty($from)) {
            return array('ok' => false, 'msg' => 'Adresse d\'expédition non configurée (newsletter_smtp_from).');
        }

        $to      = !empty($form['to_email']) ? $form['to_email'] : $this->dx_auth->get_email();
        $subject = !empty($form['subject'])  ? $form['subject']  : '[GVV Veille] Test d\'envoi — ' . date('d/m/Y H:i');
        $body    = !empty($form['body'])     ? $form['body']     : "Test depuis GVV\nExpéditeur : {$from}";

        // Envoi déclenché manuellement : destinataires saisis par l'utilisateur, pas de redirection test_email
        $this->email->clear(true);
        $this->email->set_mailtype('text');
        $this->email->set_newline("\r\n");
        $this->email->from($from, $from_name);
        $this->email->to($to);
        $this->email->subject($subject);
        $this->email->message($body);

        // Pièce jointe uploadée
        $attachment_info = '';
        $tmp_copy = null;
        if (!empty($_FILES['attachment']['tmp_name']) && is_uploaded_file($_FILES['attachment']['tmp_name'])) {
            $file = $_FILES['attachment'];
            if ($file['size'] > 10 * 1024 * 1024) {
                return array('ok' => false, 'msg' => 'Pièce jointe trop volumineuse (max 10 Mo).');
            }
            // CI2 attach() utilise basename($filename) : copie avec le vrai nom pour conserver l'extension
            $tmp_copy = sys_get_temp_dir() . '/gvv_' . uniqid() . '_' . basename($file['name']);
            copy($file['tmp_name'], $tmp_copy);
            $this->email->attach($tmp_copy);
            $attachment_info = ' + "' . $file['name'] . '" (' . round($file['size'] / 1024) . ' Ko)';
        }

        $sent = $this->email->send();
        if ($tmp_copy && file_exists($tmp_copy)) {
            unlink($tmp_copy);
        }
        if ($sent) {
            return array('ok' => true, 'msg' => "Email envoyé de {$from} vers {$to}{$attachment_info}.");
        }
        return array('ok' => false, 'msg' => "Échec : " . $this->email->print_debugger(array('headers')));
    }

    /**
     * Page de test de l'agent IA Anthropic (GET = formulaire, POST = appel API).
     */
    public function test_ai() {
        $result      = null;
        $ai_response = null;
        $form        = array();

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $form = array(
                'prompt'     => $this->input->post('prompt'),
                'model'      => $this->input->post('model'),
                'max_tokens' => max(64, (int) $this->input->post('max_tokens') ?: 1024),
            );
            $cfg     = $this->_load_newsletter_config();
            $api_key = Newsletter_imap::decode_password($cfg['newsletter_anthropic_api_key'] ?? '');
            if (empty($api_key)) {
                $result = array('ok' => false, 'msg' => $this->lang->line('newsletter_test_ai_no_key'));
            } elseif (empty(trim($form['prompt']))) {
                $result = array('ok' => false, 'msg' => 'Le prompt est requis.');
            } else {
                $ai_response = $this->_call_anthropic_api(
                    $api_key,
                    $form['model'] ?: 'claude-sonnet-4-6',
                    $form['prompt'],
                    $form['max_tokens']
                );
                $result = array(
                    'ok'  => $ai_response['ok'],
                    'msg' => $ai_response['ok']
                        ? 'Réponse reçue (' . $ai_response['output_tokens'] . ' tokens out / ' . $ai_response['input_tokens'] . ' in).'
                        : 'Erreur API : ' . $ai_response['error'],
                );
            }
        }

        $cfg  = $this->_load_newsletter_config();
        $data = array(
            'title'              => $this->lang->line('newsletter_test_ai_title'),
            'api_key_configured' => !empty($cfg['newsletter_anthropic_api_key']),
            'default_model'      => $cfg['newsletter_anthropic_model'] ?? 'claude-sonnet-4-6',
            'result'             => $result,
            'form'               => $form,
            'ai_response'        => $ai_response,
        );
        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu');
        $this->load->view('bs_banner');
        $this->load->view('newsletter_admin/bs_test_ai', $data);
        $this->load->view('bs_footer');
    }

    private function _call_anthropic_api($api_key, $model, $prompt, $max_tokens = 1024) {
        $this->load->library('Newsletter_ai');
        return $this->newsletter_ai->message($api_key, $model, $prompt, '', $max_tokens, 60);
    }

    /**
     * Page de test IMAP (GET = formulaire, POST = exécution des tests).
     */
    public function test_imap() {
        $result = null;
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $result = $this->_run_test_imap();
        }
        $cfg  = $this->_load_newsletter_config();
        $data = array(
            'title'  => $this->lang->line('newsletter_test_imap_title'),
            'cfg'    => $cfg,
            'result' => $result,
        );
        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu');
        $this->load->view('bs_banner');
        $this->load->view('newsletter_admin/bs_test_imap', $data);
        $this->load->view('bs_footer');
    }

    private function _run_test_imap() {
        $cfg = $this->_load_newsletter_config();
        if (empty($cfg['newsletter_imap_host'])) {
            return array('error' => 'IMAP non configuré. Allez d\'abord dans Configuration.');
        }

        $this->newsletter_imap->init($cfg);

        $steps      = array();
        $since_post = $this->input->post('since_date') ?: date('d-M-Y', strtotime('-30 days'));
        $pattern    = $this->input->post('subject_pattern') ?: '';

        // Étape 1 — Connexion
        $ok = $this->newsletter_imap->test_connection();
        $steps[] = array(
            'label' => 'Connexion IMAP (' . $cfg['newsletter_imap_host'] . ':' . $cfg['newsletter_imap_port'] . ')',
            'ok'    => $ok,
            'msg'   => $ok ? 'Connexion et authentification réussies.' : $this->newsletter_imap->last_error(),
        );
        if (!$ok) return array('steps' => $steps);

        // Étape 2 — Comptage
        $count = $this->newsletter_imap->count_since($since_post);
        $steps[] = array(
            'label' => 'Emails reçus depuis ' . $since_post,
            'ok'    => $count !== false,
            'msg'   => $count !== false ? "{$count} email(s) trouvé(s)." : $this->newsletter_imap->last_error(),
        );
        if ($count === false) return array('steps' => $steps);

        // Étape 3 — Liste des messages avec sujets, filtre et pièces jointes
        $uids    = $this->newsletter_imap->search_since($since_post, 20);
        $headers = ($uids !== false) ? $this->newsletter_imap->fetch_headers($uids, $pattern) : array();

        // Enrichir chaque message avec la liste de ses pièces jointes
        foreach ($headers as &$h) {
            $parts = $this->newsletter_imap->fetch_structure($h['uid']);
            $h['attachments'] = ($parts !== false) ? $parts : array();
        }
        unset($h);

        $steps[] = array(
            'label'   => 'Messages' . ($pattern ? ' contenant "' . htmlspecialchars($pattern) . '"' : ' (20 derniers)'),
            'ok'      => $uids !== false,
            'msg'     => $uids !== false ? count($headers) . ' message(s).' : $this->newsletter_imap->last_error(),
            'headers' => $headers,
        );

        return array('steps' => $steps, 'since' => $since_post, 'pattern' => $pattern);
    }

    /**
     * Affiche le corps d'un email dans un onglet séparé.
     * URL : newsletter_admin/test_imap_body/UID
     */
    public function test_imap_body($uid = 0) {
        $cfg = $this->_load_newsletter_config();
        $this->newsletter_imap->init($cfg);

        $uid     = (int) $uid;
        $headers = $this->newsletter_imap->fetch_headers(array($uid));
        $subject = !empty($headers[0]['subject']) ? $headers[0]['subject'] : "Message UID={$uid}";
        $from    = !empty($headers[0]['from'])    ? $headers[0]['from']    : '';
        $date    = !empty($headers[0]['date'])    ? $headers[0]['date']    : '';
        $body    = $this->newsletter_imap->fetch_body($uid);

        // Vider le buffer CI2 avant d'envoyer notre propre réponse
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><meta charset="utf-8">'
           . '<title>' . htmlspecialchars($subject) . '</title>'
           . '<style>body{font-family:sans-serif;margin:2rem;max-width:900px}'
           . '.meta{color:#666;font-size:.85rem;margin-bottom:1rem;border-bottom:1px solid #ddd;padding-bottom:.5rem}'
           . 'pre{white-space:pre-wrap;word-break:break-word;background:#f8f8f8;padding:1rem;border-radius:4px}'
           . '</style></head><body>';
        echo '<h2>' . htmlspecialchars($subject) . '</h2>';
        echo '<div class="meta">'
           . '<strong>De :</strong> ' . htmlspecialchars($from) . '&nbsp;&nbsp;'
           . '<strong>Date :</strong> ' . htmlspecialchars($date)
           . '</div>';
        if ($body === false) {
            echo '<p style="color:red">Erreur : ' . htmlspecialchars($this->newsletter_imap->last_error()) . '</p>';
        } else {
            // Détecter HTML vs texte
            if (stripos($body, '<html') !== false || stripos($body, '<body') !== false) {
                echo $body; // HTML : afficher tel quel
            } else {
                echo '<pre>' . htmlspecialchars($body) . '</pre>';
            }
        }
        echo '</body></html>';
        exit;
    }

    /**
     * Télécharge ou affiche une pièce jointe dans un onglet.
     * URL : newsletter_admin/test_imap_attachment/UID/PART/FILENAME
     */
    public function test_imap_attachment($uid = 0, $part = '1', $name = 'attachment') {
        $cfg = $this->_load_newsletter_config();
        $this->newsletter_imap->init($cfg);

        $uid    = (int) $uid;
        $part   = preg_replace('/[^0-9.]/', '', $part); // sécurité : chiffres et points seulement
        $name   = basename(rawurldecode($name));
        $data   = $this->newsletter_imap->fetch_part($uid, $part);

        if ($data === false) {
            while (ob_get_level()) ob_end_clean();
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Impossible de récupérer la pièce jointe : ' . $this->newsletter_imap->last_error();
            exit;
        }

        // Détecter le type MIME depuis l'extension
        $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $mime_map = array(
            'pdf'  => 'application/pdf',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'txt'  => 'text/plain',
        );
        $mime = isset($mime_map[$ext]) ? $mime_map[$ext] : 'application/octet-stream';

        // Afficher dans l'onglet (inline) pour les types prévisualisables, télécharger sinon
        $inline_types = array('application/pdf', 'image/png', 'image/jpeg', 'image/gif', 'text/plain');
        $disposition  = in_array($mime, $inline_types) ? 'inline' : 'attachment';

        while (ob_get_level()) ob_end_clean();
        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . $disposition . '; filename="' . addslashes($name) . '"');
        header('Content-Length: ' . strlen($data));
        echo $data;
        exit;
    }

    // ── Génération newsletter via claude --print stream-json (async) ──────────

    /**
     * Page de test de génération.
     * POST → lance claude en arrière-plan, redirige vers GET?job=draft_name.
     * GET?job= → page de polling JS (pas d'attente côté PHP).
     */
    public function test_generate() {
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $model = $this->input->post('model') ?: 'claude-sonnet-4-6';
            $valid = array('claude-opus-4-8', 'claude-sonnet-4-6', 'claude-haiku-4-5-20251001');
            if (!in_array($model, $valid)) $model = 'claude-sonnet-4-6';
            $result = $this->_start_generate($model);
            if ($result['ok']) {
                redirect('newsletter_admin/test_generate?job=' . rawurlencode($result['draft_name']));
                return;
            }
            // Erreur de démarrage : affiche directement
            $this->session->set_flashdata('generate_error', $result['error']);
            redirect('newsletter_admin/test_generate');
            return;
        }

        $job = $this->input->get('job');
        if ($job && !preg_match('/^draft_\d{8}_\d{6}\.md$/', $job)) $job = null;

        $cfg          = $this->_load_newsletter_config();
        $context_file = APPPATH . '../tmp/veille_agent_contexte.md';
        $context_info = file_exists($context_file) ? array(
            'name'  => basename($context_file),
            'size'  => filesize($context_file),
            'mtime' => filemtime($context_file),
        ) : null;

        $drafts     = array();
        $output_dir = APPPATH . '../tmp/output/';
        if (is_dir($output_dir)) {
            foreach (glob($output_dir . 'draft_*.md') as $f) {
                $drafts[] = array('name' => basename($f), 'size' => filesize($f), 'mtime' => filemtime($f));
            }
            usort($drafts, function($a, $b) { return $b['mtime'] - $a['mtime']; });
        }

        $data = array(
            'title'              => 'Test génération newsletter',
            'api_key_configured' => !empty($cfg['newsletter_anthropic_api_key']),
            'default_model'      => $cfg['newsletter_anthropic_model'] ?? 'claude-sonnet-4-6',
            'context_info'       => $context_info,
            'generate_error'     => $this->session->flashdata('generate_error'),
            'job'                => $job,
            'drafts'             => $drafts,
        );
        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu');
        $this->load->view('bs_banner');
        $this->load->view('newsletter_admin/bs_test_generate', $data);
        $this->load->view('bs_footer');
    }

    /**
     * Endpoint AJAX : retourne le statut d'un job de génération en JSON.
     * Statuts : running | done | error
     */
    public function generate_status() {
        $draft_name = $this->input->get('draft') ?: '';
        if (!preg_match('/^draft_\d{8}_\d{6}\.md$/', $draft_name)) {
            $this->output->set_content_type('application/json');
            echo json_encode(array('status' => 'error', 'error' => 'Nom invalide'));
            return;
        }
        $dir   = APPPATH . '../tmp/output/';
        $draft = $dir . $draft_name;
        $run   = $draft . '.running';
        $err   = $draft . '.err';

        $stream = $draft . '.stream';

        $this->output->set_content_type('application/json');

        // Draft final écrit → terminé
        if (file_exists($draft) && filesize($draft) > 0) {
            echo json_encode(array('status' => 'done', 'text' => file_get_contents($draft), 'name' => $draft_name));
            return;
        }

        // Lire le stream NDJSON pour extraire le texte en cours
        $parsed = $this->_parse_stream($stream);

        if ($parsed['done']) {
            // Résultat final disponible dans le stream → écrire draft.md
            file_put_contents($draft, $parsed['text']);
            @unlink($run);
            echo json_encode(array('status' => 'done', 'text' => $parsed['text'], 'name' => $draft_name));
            return;
        }

        if ($parsed['error']) {
            echo json_encode(array('status' => 'error', 'error' => $parsed['error']));
            return;
        }

        if (!empty($parsed['text'])) {
            // Streaming en cours : texte partiel disponible
            echo json_encode(array('status' => 'streaming', 'text' => $parsed['text']));
            return;
        }

        // Rien encore dans le stream
        if (file_exists($run)) {
            $started = file_get_contents($run);
            echo json_encode(array('status' => 'running', 'started' => $started));
            return;
        }

        // Fichier err avec contenu → erreur
        if (file_exists($err) && filesize($err) > 0) {
            echo json_encode(array('status' => 'error', 'error' => file_get_contents($err)));
            return;
        }

        echo json_encode(array('status' => 'error', 'error' => 'Job introuvable.'));
    }

    /**
     * Parse le fichier NDJSON produit par bin/newsletter_generate.php.
     * Format des lignes : {"type":"chunk","text":"..."} | {"type":"done"} | {"type":"error","message":"..."}
     * Retourne ['done'=>bool, 'text'=>string, 'error'=>string|null].
     */
    private function _parse_stream($stream_path) {
        if (!file_exists($stream_path) || filesize($stream_path) === 0) {
            return array('done' => false, 'text' => '', 'error' => null);
        }

        $text  = '';
        $done  = false;
        $error = null;

        $handle = @fopen($stream_path, 'r');
        if (!$handle) return array('done' => false, 'text' => '', 'error' => null);

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '') continue;
            $obj = json_decode($line, true);
            if (!is_array($obj) || !isset($obj['type'])) continue;

            switch ($obj['type']) {
                case 'chunk':
                    $text .= $obj['text'] ?? '';
                    break;
                case 'done':
                    $done = true;
                    break;
                case 'error':
                    $error = $obj['message'] ?? 'Erreur inconnue.';
                    $done  = true;
                    break;
            }
        }
        fclose($handle);

        return array('done' => $done, 'text' => $text, 'error' => $error);
    }

    /**
     * Télécharge un brouillon généré depuis tmp/output/.
     */
    private function _draft_name_from_request() {
        $name = $this->input->get('draft') ?: '';
        return preg_match('/^draft_\d{8}_\d{6}\.md$/', $name) ? $name : null;
    }

    public function delete_draft() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            show_error('Méthode non autorisée.', 405);
            return;
        }
        $name = $this->input->post('draft') ?: '';
        if (!preg_match('/^draft_\d{8}_\d{6}\.md$/', $name)) {
            show_error('Nom de fichier invalide.', 400);
            return;
        }
        $dir = APPPATH . '../tmp/output/';
        // Supprime le .md et le .pdf associé s'il existe
        foreach (array($name, preg_replace('/\.md$/', '.pdf', $name)) as $f) {
            $path = $dir . $f;
            if (file_exists($path)) unlink($path);
        }
        $this->session->set_flashdata('message',
            '<div class="alert alert-success alert-dismissible">Brouillon <strong>'
            . htmlspecialchars($name) . '</strong> supprimé.</div>'
        );
        redirect('newsletter_admin/test_generate');
    }

    /**
     * Envoie le brouillon en PDF aux relecteurs configurés.
     */
    public function send_to_reviewers() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            show_error('Méthode non autorisée.', 405); return;
        }
        $name = $this->input->post('draft') ?: '';
        if (!preg_match('/^draft_\d{8}_\d{6}\.md$/', $name)) {
            show_error('Nom de fichier invalide.', 400); return;
        }

        $cfg = $this->_load_newsletter_config();

        $reviewers_raw = trim($cfg['newsletter_reviewers'] ?? '');
        if (empty($reviewers_raw)) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">Aucun relecteur configuré (<a href="' . site_url('newsletter_admin/configuration') . '">Configuration</a>).</div>');
            redirect('newsletter_admin/test_generate'); return;
        }

        $md_path = APPPATH . '../tmp/output/' . $name;
        if (!file_exists($md_path)) { show_404(); return; }

        // Générer ou réutiliser le PDF
        $pdf_name = preg_replace('/\.md$/', '.pdf', $name);
        $pdf_path = APPPATH . '../tmp/output/' . $pdf_name;
        if (!file_exists($pdf_path) || filemtime($md_path) > filemtime($pdf_path)) {
            $period = $this->_draft_period($name);
            $cmd = 'pandoc ' . escapeshellarg($md_path)
                . ' --pdf-engine=wkhtmltopdf'
                . ' --metadata title=' . escapeshellarg('Veille sécurité et réglementation — ' . $period)
                . ' -V margin-top=20mm -V margin-bottom=20mm -V margin-left=20mm -V margin-right=20mm'
                . ' -o ' . escapeshellarg($pdf_path) . ' 2>&1';
            shell_exec($cmd);
            if (!file_exists($pdf_path)) {
                $this->session->set_flashdata('message', '<div class="alert alert-danger">Échec de la génération PDF.</div>');
                redirect('newsletter_admin/test_generate'); return;
            }
        }

        // Variables du template
        $period  = $this->_draft_period($name);
        $version = $this->_draft_version($name);
        $vars    = array(
            '{period}'     => $period,
            '{version}'    => $version,
            '{date}'       => date('d/m/Y'),
            '{deadline}'   => date('d/m/Y', strtotime('+7 days')),
            '{from_name}'  => $cfg['newsletter_smtp_from_name'] ?: ($cfg['newsletter_smtp_from'] ?: ''),
            '{draft_name}' => $name,
        );

        $default_subject = '[Veille ACS] Brouillon {period} (v{version}) — relecture souhaitée';
        $default_body    = "Bonjour,\n\nJe vous envoie en pièce jointe le brouillon v{version} de la lettre de veille sécurité et réglementation pour la période : {period}.\n\nMerci de me faire parvenir vos remarques, corrections ou suggestions d'articles en répondant à cet email, avant le {deadline}.\n\nCe brouillon est préparé automatiquement à partir des sources réglementaires et fédérales (EASA, BEA, DSAC, FFA, FFVP, FFPLUM…). Vos retours permettront d'affiner la sélection et la rédaction avant validation par le CPS.\n\nCordialement,\n{from_name}\n\n---\nVeille sécurité et réglementation — {period}\nAéroclub d'Abbeville – Buigny – Baie de Somme";

        $raw_body = $cfg['newsletter_review_body'] ?? $default_body;
        $subject  = strtr($cfg['newsletter_review_subject'] ?? $default_subject, $vars);
        $body     = strtr(str_replace('\n', "\n", $raw_body), $vars);

        // Init SMTP newsletter
        $smtp = $this->_init_newsletter_smtp($cfg);
        if (empty($smtp['from'])) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">Adresse SMTP d\'expédition non configurée.</div>');
            redirect('newsletter_admin/test_generate'); return;
        }

        // Copie du PDF avec nom lisible pour la pièce jointe
        $attach_name = 'Veille_ACS_' . substr($name, 6, 6) . '_brouillon_v' . $version . '.pdf';
        $attach_tmp  = sys_get_temp_dir() . '/' . $attach_name;
        copy($pdf_path, $attach_tmp);

        $reviewers = array_filter(array_map('trim', explode(',', $reviewers_raw)));
        $sent = 0; $errors = array();

        foreach ($reviewers as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;
            $to = test_intercept_email($email, $subject);
            $this->email->clear(true);
            $this->email->set_mailtype('text');
            $this->email->set_newline("\r\n");
            $this->email->from($smtp['from'], $smtp['from_name']);
            $this->email->to($to);
            $this->email->subject($subject);
            $this->email->message($body);
            $this->email->attach($attach_tmp);
            $this->email->send() ? $sent++ : $errors[] = $email;
        }

        if (file_exists($attach_tmp)) unlink($attach_tmp);

        if ($sent > 0 && empty($errors)) {
            $msg = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Brouillon envoyé à <strong>' . $sent . ' relecteur(s)</strong> : ' . htmlspecialchars(implode(', ', $reviewers)) . '</div>';
        } elseif ($sent > 0) {
            $msg = '<div class="alert alert-warning">Envoyé à ' . $sent . ' relecteur(s), échec : ' . htmlspecialchars(implode(', ', $errors)) . '</div>';
        } else {
            $msg = '<div class="alert alert-danger">Échec de l\'envoi à tous les relecteurs.</div>';
        }
        $this->session->set_flashdata('message', $msg);
        redirect('newsletter_admin/test_generate');
    }

    private function _draft_period($draft_name) {
        $months = array('', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
                        'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre');
        if (preg_match('/^draft_(\d{4})(\d{2})/', $draft_name, $m)) {
            return $months[(int) $m[2]] . ' ' . $m[1];
        }
        return date('m/Y');
    }

    private function _draft_version($draft_name) {
        $dir = APPPATH . '../tmp/output/';
        if (!preg_match('/^draft_(\d{6})/', $draft_name, $m)) return '1';
        $prefix  = 'draft_' . $m[1];
        $version = 1;
        foreach (glob($dir . $prefix . '*.md') as $f) {
            $bn = basename($f);
            if ($bn < $draft_name) $version++;
        }
        return (string) $version;
    }

    public function download_draft() {
        $name = $this->_draft_name_from_request();
        if (!$name) { show_error('Nom de fichier invalide.', 400); return; }
        $path = APPPATH . '../tmp/output/' . $name;
        if (!file_exists($path)) { show_404(); return; }
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: text/markdown; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    /**
     * Affiche le brouillon Markdown dans un nouvel onglet (inline).
     * Le visualisateur Markdown du navigateur prend le relais.
     */
    public function view_draft() {
        $name = $this->_draft_name_from_request();
        if (!$name) { show_error('Nom de fichier invalide.', 400); return; }
        $path = APPPATH . '../tmp/output/' . $name;
        if (!file_exists($path)) { show_404(); return; }
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: text/markdown; charset=utf-8');
        header('Content-Disposition: inline; filename="' . $name . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    /**
     * Convertit le brouillon Markdown en PDF via pandoc+wkhtmltopdf
     * et l'ouvre dans le navigateur.
     */
    public function pdf_draft() {
        $name = $this->_draft_name_from_request();
        if (!$name) { show_error('Nom de fichier invalide.', 400); return; }
        $md_path = APPPATH . '../tmp/output/' . $name;
        if (!file_exists($md_path)) { show_404(); return; }

        $pdf_name = preg_replace('/\.md$/', '.pdf', $name);
        $pdf_path = APPPATH . '../tmp/output/' . $pdf_name;

        // Regénérer le PDF si le .md est plus récent
        if (!file_exists($pdf_path) || filemtime($md_path) > filemtime($pdf_path)) {
            $title = 'Veille sécurité et réglementation — ' . date('m/Y', filemtime($md_path));
            $cmd = 'pandoc '
                . escapeshellarg($md_path)
                . ' --pdf-engine=wkhtmltopdf'
                . ' --metadata title=' . escapeshellarg($title)
                . ' -V margin-top=20mm -V margin-bottom=20mm'
                . ' -V margin-left=20mm -V margin-right=20mm'
                . ' -o ' . escapeshellarg($pdf_path)
                . ' 2>&1';
            $output = shell_exec($cmd);
            if (!file_exists($pdf_path)) {
                show_error('Échec de la conversion PDF : ' . htmlspecialchars($output), 500);
                return;
            }
            chmod($pdf_path, 0644);
        }

        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $pdf_name . '"');
        header('Content-Length: ' . filesize($pdf_path));
        readfile($pdf_path);
        exit;
    }

    /**
     * Lance claude --print en arrière-plan via un script shell temporaire.
     * Retourne immédiatement avec le nom du draft à surveiller.
     */
    /**
     * Lance bin/newsletter_generate.php en arrière-plan via un script shell.
     * Le script PHP appelle l'API Anthropic en streaming HTTP et écrit
     * progressivement des lignes NDJSON dans le .stream — pas de buffering CLI.
     */
    private function _start_generate($model) {
        $cfg     = $this->_load_newsletter_config();
        $api_key = Newsletter_imap::decode_password($cfg['newsletter_anthropic_api_key'] ?? '');
        if (empty($api_key)) {
            return array('ok' => false, 'error' => $this->lang->line('newsletter_test_ai_no_key'));
        }

        $context_file = realpath(APPPATH . '../tmp/veille_agent_contexte.md');
        if (!$context_file || !file_exists($context_file)) {
            return array('ok' => false, 'error' => 'Fichier de contexte introuvable.');
        }

        $generate_script = realpath(APPPATH . '../bin/newsletter_generate.php');
        if (!$generate_script) {
            return array('ok' => false, 'error' => 'Script de génération introuvable.');
        }

        $output_dir = APPPATH . '../tmp/output/';
        if (!is_dir($output_dir)) mkdir($output_dir, 0777, true);

        $draft_name   = 'draft_' . date('Ymd_His') . '.md';
        $draft_path   = $output_dir . $draft_name;
        $prompt_path  = $output_dir . $draft_name . '.prompt';
        $stream_path  = $output_dir . $draft_name . '.stream';
        $error_path   = $output_dir . $draft_name . '.err';
        $running_path = $output_dir . $draft_name . '.running';
        $script_path  = $output_dir . $draft_name . '.sh';

        $instruction = "Génère le brouillon de la lettre de veille mensuelle pour octobre 2026 (exécution simulée). "
            . "Respecte les règles du § 8 : 6 rubriques, 12 éléments au plus, chaque élément avec titre + résumé 3-6 lignes + « Ce que ça change pour nous » + public + urgence. "
            . "Rubrique « Un REX par activité » obligatoire (un REX avion, un REX planeur, un REX ULM). "
            . "Les éléments peuvent être plausibles mais fictifs. "
            . "Termine avec la mention obligatoire en pied de lettre. Format markdown.";

        file_put_contents($prompt_path, $instruction);
        touch($stream_path);
        touch($error_path);

        // Variables d'environnement : la clé API n'apparaît pas dans argv (ps)
        $sh  = "#!/bin/bash\n";
        $sh .= "export NL_API_KEY="      . escapeshellarg($api_key)      . "\n";
        $sh .= "export NL_MODEL="        . escapeshellarg($model)        . "\n";
        $sh .= "export NL_CONTEXT_FILE=" . escapeshellarg($context_file) . "\n";
        $sh .= "export NL_PROMPT_FILE="  . escapeshellarg($prompt_path)  . "\n";
        $sh .= "export NL_STREAM_FILE="  . escapeshellarg($stream_path)  . "\n";
        $sh .= "export NL_DRAFT_FILE="   . escapeshellarg($draft_path)   . "\n";
        $sh .= "export NL_ERROR_FILE="   . escapeshellarg($error_path)   . "\n";
        $sh .= "/usr/bin/php8.4 " . escapeshellarg($generate_script) . "\n";
        $sh .= "rm -f " . escapeshellarg($prompt_path)
            . " " . escapeshellarg($running_path)
            . " " . escapeshellarg($script_path) . "\n";

        file_put_contents($script_path, $sh);
        chmod($script_path, 0700);
        file_put_contents($running_path, date('Y-m-d H:i:s'));

        shell_exec('nohup bash ' . escapeshellarg($script_path) . ' >/dev/null 2>&1 &');

        return array('ok' => true, 'draft_name' => $draft_name);
    }

    // ══════════════════════════════════════════════════════════════════════
    // Cycle de la version 1 : éditions, consignes, import, relecture,
    // validation, publication (plan P2)
    // ══════════════════════════════════════════════════════════════════════

    /** Taille maximale d'un fichier importé (brouillon, rapport, inventaire, consignes) */
    const NL_MAX_IMPORT = 5242880;

    private function _render($view, $data) {
        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu');
        $this->load->view('bs_banner');
        $this->load->view($view, $data);
        $this->load->view('bs_footer');
    }

    /**
     * Message de résultat affiché après redirection (alerte Bootstrap refermable).
     */
    private function _flash($type, $html) {
        $this->session->set_flashdata('message',
            '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">' . $html
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
    }

    private function _require_post($redirect) {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect($redirect);
            return FALSE;
        }
        return TRUE;
    }

    private function _files() {
        $cfg = $this->_load_newsletter_config();
        $this->load->library('Newsletter_files', array('base_path' => $cfg['newsletter_storage_path'] ?? ''));
        return $this->newsletter_files;
    }

    private function _load_cycle_models() {
        $this->load->model('newsletter_contexts_model');
        $this->load->model('newsletter_runs_model');
        $this->load->model('newsletter_reviews_model');
    }

    /**
     * Contenu texte d'un fichier envoyé par formulaire.
     *
     * @return string|null|false  contenu, NULL si aucun fichier, FALSE si refusé
     */
    private function _uploaded_text($field, array $extensions, &$error) {
        if (empty($_FILES[$field]['name'])) return NULL;
        $f = $_FILES[$field];
        if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            $error = sprintf($this->lang->line('nl_upload_failed'), $f['name']);
            return FALSE;
        }
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $extensions, TRUE)) {
            $error = sprintf($this->lang->line('nl_upload_bad_type'), $f['name'], implode(', ', $extensions));
            return FALSE;
        }
        if ($f['size'] > self::NL_MAX_IMPORT) {
            $error = sprintf($this->lang->line('nl_upload_too_big'), $f['name']);
            return FALSE;
        }
        $content = file_get_contents($f['tmp_name']);
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }
        return preg_replace('/^\xEF\xBB\xBF/', '', $content);
    }

    private function _edition_or_404($id) {
        $nl = $this->newsletter_model->get((int) $id);
        if (!$nl) show_404();
        return $nl;
    }

    private function _current_draft($nl) {
        return $nl['draft_path'] ? $this->_files()->read($nl['draft_path']) : FALSE;
    }

    // ── Éditions ──────────────────────────────────────────────────────────

    public function editions() {
        $this->_load_cycle_models();
        $data = array(
            'title'       => $this->lang->line('nl_editions_title'),
            'newsletters' => $this->newsletter_model->get_all(),
            'context'     => $this->newsletter_contexts_model->get_active(),
            'message'     => $this->session->flashdata('message'),
        );
        $this->_render('newsletter_admin/bs_editions', $data);
    }

    public function edition($id) {
        $this->_load_cycle_models();
        $this->load->library('Newsletter_review_cycle');
        $nl    = $this->_edition_or_404($id);
        $files = $this->_files();
        $draft = $this->_current_draft($nl);
        $cfg   = $this->_load_newsletter_config();

        $data = array(
            'title'       => $nl['title'],
            'nl'          => $nl,
            'version'     => Newsletter_files::version_of($nl['draft_path']),
            'versions'    => $files->list_versions($nl['id']),
            'draft_html'  => ($draft !== FALSE) ? $this->_markdown($draft) : NULL,
            'mentions'    => ($draft !== FALSE) ? Newsletter_files::find_open_mentions($draft) : array(),
            'run'         => $this->newsletter_runs_model->get_by_newsletter($nl['id']),
            'suggestions' => $this->newsletter_reviews_model->get_for_newsletter($nl['id']),
            'postponed'   => $this->newsletter_reviews_model->get_postponed(),
            'reviewers'   => Newsletter_review_cycle::parse_addresses($cfg['newsletter_reviewers'] ?? ''),
            'pending'     => $this->newsletter_reviews_model->count_pending($nl['id']),
            'accepted'    => count($this->newsletter_reviews_model->get_accepted($nl['id'], $nl['review_iteration'])),
            'attachments' => $this->_reply_attachments($files, $nl['id']),
            'collect'     => $this->session->flashdata('collect_report'),
            'message'     => $this->session->flashdata('message'),
        );
        $this->_render('newsletter_admin/bs_edition', $data);
    }

    /**
     * Pièces jointes conservées des réponses d'une édition, par suggestion.
     */
    private function _reply_attachments($files, $newsletter_id) {
        $out = array();
        foreach ($this->newsletter_reviews_model->get_for_newsletter($newsletter_id) as $s) {
            if ($s['has_attachments']) $out[(int) $s['id']] = $files->list_reply_files($s['id']);
        }
        return $out;
    }

    public function attachment($review_id, $name = '') {
        $this->_load_cycle_models();
        $s = $this->newsletter_reviews_model->get((int) $review_id);
        $name = basename(rawurldecode($name));
        if (!$s || $name === '') show_404();
        $files = $this->_files();
        $content = $files->read($files->reply_dir($s['id']) . $name);
        if ($content === FALSE) show_404();
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $this->_send_file($content, $finfo->buffer($content) ?: 'application/octet-stream', $name);
    }

    private function _markdown($md) {
        $this->load->helper('markdown');
        return markdown($md);
    }

    /**
     * Affiche une version du brouillon, avec ses différences par rapport à la précédente.
     */
    public function version($id, $n) {
        $nl    = $this->_edition_or_404($id);
        $files = $this->_files();
        $versions = $files->list_versions($nl['id']);
        $current = $previous = NULL;
        foreach ($versions as $v) {
            if ($v['version'] === (int) $n) $current = $v;
            if ($v['version'] === (int) $n - 1) $previous = $v;
        }
        if (!$current) show_404();
        $text = $files->read($current['path']);
        $data = array(
            'title'    => $nl['title'] . ' — v' . (int) $n,
            'nl'       => $nl,
            'n'        => (int) $n,
            'html'     => $this->_markdown($text),
            'diff'     => $previous ? Newsletter_files::diff_lines($files->read($previous['path']), $text) : NULL,
        );
        $this->_render('newsletter_admin/bs_version', $data);
    }

    /**
     * Aperçu PDF de la version courante du brouillon (notes internes comprises).
     */
    public function edition_pdf($id) {
        $nl = $this->_edition_or_404($id);
        $draft = $this->_current_draft($nl);
        if ($draft === FALSE) show_404();
        $this->load->library('Newsletter_pdf');
        $pdf = $this->newsletter_pdf->render($draft, $nl['title']);
        $this->_send_file($pdf, 'application/pdf', 'brouillon_' . (int) $nl['id'] . '_v'
            . Newsletter_files::version_of($nl['draft_path']) . '.pdf', 'inline');
    }

    private function _send_file($content, $type, $name, $disposition = 'attachment') {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: ' . $type);
        header('Content-Disposition: ' . $disposition . '; filename="' . $name . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }

    public function delete_edition($id) {
        if (!$this->_require_post('newsletter_admin/editions')) return;
        $nl = $this->_edition_or_404($id);
        if ($nl['status'] !== Newsletter_model::STATUS_DRAFT) {
            $this->_flash('warning', $this->lang->line('nl_delete_only_draft'));
            redirect('newsletter_admin/edition/' . $nl['id']);
            return;
        }
        $this->_files()->delete_edition($nl['id']);
        $this->newsletter_model->delete($nl['id']);
        $this->_flash('success', sprintf($this->lang->line('nl_edition_deleted'), htmlspecialchars($nl['title'])));
        redirect('newsletter_admin/editions');
    }

    // ── Import du brouillon d'une période ─────────────────────────────────

    public function import() {
        $this->_load_cycle_models();
        $form = array();
        $result = NULL;
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $form = array(
                'title'        => trim((string) $this->input->post('title')),
                'period_start' => $this->input->post('period_start'),
                'period_end'   => $this->input->post('period_end'),
                'model'        => trim((string) $this->input->post('model')),
                'cost_usd'     => str_replace(',', '.', trim((string) $this->input->post('cost_usd'))),
            );
            $errors = array();
            $err = '';
            $draft  = $this->_uploaded_text('draft_file', array('md', 'markdown', 'txt'), $err);  if ($err) { $errors[] = $err; $err = ''; }
            $report = $this->_uploaded_text('report_file', array('md', 'markdown', 'txt'), $err); if ($err) { $errors[] = $err; $err = ''; }
            $inv    = $this->_uploaded_text('inventory_file', array('csv', 'txt'), $err);         if ($err) { $errors[] = $err; $err = ''; }
            if ($form['cost_usd'] !== '' && !is_numeric($form['cost_usd'])) {
                $errors[] = $this->lang->line('nl_import_bad_cost');
            }

            if (!$errors) {
                $this->load->library('Newsletter_importer', array('files' => $this->_files()));
                $result = $this->newsletter_importer->import(array_merge($form, array(
                    'draft' => (string) $draft, 'report' => (string) $report, 'inventory' => (string) $inv,
                    'origin' => Newsletter_runs_model::ORIGIN_DESKTOP,
                )));
                if ($result['ok']) {
                    $html = sprintf($this->lang->line('nl_import_success'), htmlspecialchars($form['title']), htmlspecialchars($result['run_id']));
                    foreach ($result['warnings'] as $w) {
                        $html .= '<br><i class="fas fa-exclamation-triangle text-warning"></i> ' . htmlspecialchars($w);
                    }
                    $this->_flash($result['warnings'] ? 'warning' : 'success', $html);
                    redirect('newsletter_admin/edition/' . $result['newsletter_id']);
                    return;
                }
                $errors = $result['errors'];
            }
            $result = array('ok' => FALSE, 'errors' => $errors);
        }
        $data = array(
            'title'   => $this->lang->line('nl_import_title'),
            'form'    => $form,
            'result'  => $result,
            'context' => $this->newsletter_contexts_model->get_active(),
        );
        $this->_render('newsletter_admin/bs_import', $data);
    }

    // ── Consignes de l'agent ──────────────────────────────────────────────

    public function context() {
        $this->_load_cycle_models();
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $err = '';
            $uploaded = $this->_uploaded_text('context_file', array('md', 'markdown', 'txt'), $err);
            $content  = ($uploaded !== NULL && $uploaded !== FALSE) ? $uploaded : (string) $this->input->post('content');
            $comment  = trim((string) $this->input->post('comment'));
            $active   = $this->newsletter_contexts_model->get_active();

            if ($err) {
                $this->_flash('danger', htmlspecialchars($err));
            } elseif (trim($content) === '') {
                $this->_flash('danger', $this->lang->line('nl_context_empty'));
            } elseif ($active && $active['content'] === $content) {
                $this->_flash('info', $this->lang->line('nl_context_unchanged'));
            } else {
                $id = $this->newsletter_contexts_model->create_version($content, $comment);
                if ($id) {
                    $v = $this->newsletter_contexts_model->get($id);
                    $this->_flash('success', sprintf($this->lang->line('nl_context_saved'), (int) $v['version']));
                } else {
                    $this->_flash('danger', $this->lang->line('nl_context_save_error'));
                }
            }
            redirect('newsletter_admin/context');
            return;
        }
        $data = array(
            'title'    => $this->lang->line('nl_context_title'),
            'active'   => $this->newsletter_contexts_model->get_active(),
            'versions' => $this->newsletter_contexts_model->get_all(),
            'message'  => $this->session->flashdata('message'),
        );
        $this->_render('newsletter_admin/bs_context', $data);
    }

    public function context_version($id) {
        $this->_load_cycle_models();
        $v = $this->newsletter_contexts_model->get((int) $id);
        if (!$v) show_404();
        $this->load->library('Newsletter_files');
        $active = $this->newsletter_contexts_model->get_active();
        $data = array(
            'title'   => sprintf($this->lang->line('nl_context_version_title'), (int) $v['version']),
            'v'       => $v,
            'active'  => $active,
            'diff'    => ($active && (int) $active['id'] !== (int) $v['id'])
                         ? Newsletter_files::diff_lines($v['content'], $active['content']) : NULL,
        );
        $this->_render('newsletter_admin/bs_context_version', $data);
    }

    public function context_restore($id) {
        if (!$this->_require_post('newsletter_admin/context')) return;
        $this->_load_cycle_models();
        $new = $this->newsletter_contexts_model->restore((int) $id);
        if ($new) {
            $v = $this->newsletter_contexts_model->get($new);
            $this->_flash('success', sprintf($this->lang->line('nl_context_saved'), (int) $v['version']));
        } else {
            $this->_flash('danger', $this->lang->line('nl_context_save_error'));
        }
        redirect('newsletter_admin/context');
    }

    // ── Kit pour l'agent de bureau ────────────────────────────────────────

    public function kit() {
        $this->_load_cycle_models();
        $this->load->library('Newsletter_importer', array('files' => $this->_files()));
        $data = array(
            'title'     => $this->lang->line('nl_kit_title'),
            'context'   => $this->newsletter_contexts_model->get_active(),
            'inventory' => $this->newsletter_importer->current_inventory(),
            'postponed' => $this->newsletter_reviews_model->get_postponed(),
        );
        $this->_render('newsletter_admin/bs_kit', $data);
    }

    public function kit_file($what = '') {
        $this->_load_cycle_models();
        $date = date('Ymd');
        switch ($what) {
            case 'consignes':
                $c = $this->newsletter_contexts_model->get_active();
                if (!$c) show_404();
                $this->_send_file($c['content'], 'text/markdown; charset=UTF-8', 'veille_agent_contexte_v' . (int) $c['version'] . '.md');
                break;
            case 'inventaire':
                $this->load->library('Newsletter_importer', array('files' => $this->_files()));
                $inv = $this->newsletter_importer->current_inventory();
                if (!$inv) show_404();
                $this->_send_file($inv['content'], 'text/csv; charset=UTF-8', 'inventaire_documents_' . $date . '.csv');
                break;
            case 'suggestions':
                $md = "# Suggestions d'articles reportées\n\n";
                foreach ($this->newsletter_reviews_model->get_postponed() as $s) {
                    $md .= '## ' . ($s['reviewer_name'] ?: $s['reviewer_email']) . ' — ' . substr($s['created_at'], 0, 10) . "\n\n"
                        . trim($s['admin_text'] ?: $s['content']) . "\n\n";
                }
                $this->_send_file($md, 'text/markdown; charset=UTF-8', 'suggestions_reportees_' . $date . '.md');
                break;
            default:
                show_404();
        }
    }

    // ── Exécutions et rapports d'activité ─────────────────────────────────

    public function runs() {
        $this->_load_cycle_models();
        $data = array(
            'title' => $this->lang->line('nl_runs_title'),
            'runs'  => $this->newsletter_runs_model->get_all(),
        );
        $this->_render('newsletter_admin/bs_runs', $data);
    }

    public function run($id) {
        $this->_load_cycle_models();
        $run = $this->newsletter_runs_model->get((int) $id);
        if (!$run) show_404();
        $files  = $this->_files();
        $report = $files->run_file($run['run_id'], Newsletter_files::REPORT_FILE);
        $context = $run['context_id'] ? $this->newsletter_contexts_model->get($run['context_id']) : NULL;
        $data = array(
            'title'       => $run['run_id'],
            'run'         => $run,
            'context'     => $context,
            'newsletter'  => $run['newsletter_id'] ? $this->newsletter_model->get($run['newsletter_id']) : NULL,
            'report_html' => $report ? $this->_markdown($files->read($report)) : NULL,
            'has_inventory' => (bool) $files->run_file($run['run_id'], Newsletter_files::INVENTORY_FILE),
        );
        $this->_render('newsletter_admin/bs_run', $data);
    }

    public function run_file($id, $what = '') {
        $this->_load_cycle_models();
        $run = $this->newsletter_runs_model->get((int) $id);
        $files = $this->_files();
        $names = array('brouillon' => Newsletter_files::DRAFT_FILE, 'rapport' => Newsletter_files::REPORT_FILE,
                       'inventaire' => Newsletter_files::INVENTORY_FILE);
        if (!$run || !isset($names[$what])) show_404();
        $path = $files->run_file($run['run_id'], $names[$what]);
        if (!$path) show_404();
        $type = ($what === 'inventaire') ? 'text/csv; charset=UTF-8' : 'text/markdown; charset=UTF-8';
        $this->_send_file($files->read($path), $type, $run['run_id'] . '_' . $names[$what]);
    }

    // ── Transmission aux relecteurs ───────────────────────────────────────

    public function send_for_review($id) {
        if (!$this->_require_post('newsletter_admin/edition/' . (int) $id)) return;
        $this->load->library('Newsletter_review_cycle');
        $nl  = $this->_edition_or_404($id);
        $res = $this->_send_for_review($nl);
        $this->_flash($res['ok'] ? 'success' : 'danger', $res['message']);
        redirect('newsletter_admin/edition/' . $nl['id']);
    }

    /**
     * Envoie la version courante aux relecteurs et passe l'édition en relecture.
     *
     * @return array ok, message (HTML)
     */
    private function _send_for_review($nl) {
        if ($nl['status'] !== Newsletter_model::STATUS_DRAFT) {
            return array('ok' => FALSE, 'message' => $this->lang->line('nl_send_not_draft'));
        }
        $cfg = $this->_load_newsletter_config();
        $reviewers = Newsletter_review_cycle::parse_addresses($cfg['newsletter_reviewers'] ?? '');
        if (!$reviewers) {
            return array('ok' => FALSE, 'message' => sprintf($this->lang->line('nl_no_reviewers'), site_url('newsletter_admin/configuration')));
        }
        $draft = $this->_current_draft($nl);
        if ($draft === FALSE) {
            return array('ok' => FALSE, 'message' => $this->lang->line('nl_draft_missing'));
        }

        $version  = Newsletter_files::version_of($nl['draft_path']);
        $delay    = ($cfg['newsletter_review_delay_days'] ?? '') !== '' ? (int) $cfg['newsletter_review_delay_days'] : 2;
        $deadline = Newsletter_review_cycle::deadline($delay);
        $mail     = Newsletter_review_cycle::build_review_email($cfg, $nl, $version, $deadline);

        $this->load->library('Newsletter_pdf');
        $pdf_tmp = sys_get_temp_dir() . '/' . 'Veille_brouillon_' . (int) $nl['id'] . '_v' . $version . '_' . uniqid() . '.pdf';
        file_put_contents($pdf_tmp, $this->newsletter_pdf->render($draft, $nl['title']));

        $smtp = $this->_init_newsletter_smtp($cfg);
        if (empty($smtp['from'])) {
            @unlink($pdf_tmp);
            return array('ok' => FALSE, 'message' => $this->lang->line('nl_no_sender'));
        }

        $sent = array();
        $failed = array();
        foreach ($reviewers as $to) {
            $this->email->clear(TRUE);
            $this->email->set_mailtype('text');
            $this->email->set_newline("\r\n");
            $this->email->from($smtp['from'], $smtp['from_name']);
            $this->email->reply_to($smtp['from']);
            $this->email->to($to);
            $this->email->subject($mail['subject']);
            $this->email->message($mail['body']);
            $this->email->attach($pdf_tmp, 'attachment', 'Veille_brouillon_v' . $version . '.pdf', 'application/pdf');
            if ($this->email->send()) {
                $sent[] = $to;
            } else {
                $failed[] = $to;
                log_message('error', 'newsletter_admin: échec envoi relecture #' . $nl['id'] . ' à ' . $to);
            }
        }
        @unlink($pdf_tmp);

        if (!$sent) {
            return array('ok' => FALSE, 'message' => sprintf($this->lang->line('nl_send_all_failed'), htmlspecialchars(implode(', ', $failed))));
        }
        $this->newsletter_model->transition($nl['id'], Newsletter_model::STATUS_REVIEWING, array(
            'review_sent_at'  => date('Y-m-d H:i:s'),
            'review_deadline' => $deadline,
        ));
        $msg = sprintf($this->lang->line('nl_send_success'), $version, htmlspecialchars(implode(', ', $sent)), date('d/m/Y', strtotime($deadline)));
        if ($failed) {
            $msg .= '<br>' . sprintf($this->lang->line('nl_send_some_failed'), htmlspecialchars(implode(', ', $failed)));
        }
        return array('ok' => TRUE, 'message' => $msg);
    }

    // ── Récupération des réponses ─────────────────────────────────────────

    public function collect_replies($id) {
        if (!$this->_require_post('newsletter_admin/edition/' . (int) $id)) return;
        $this->_load_cycle_models();
        $this->load->library('Newsletter_review_cycle');
        $nl = $this->_edition_or_404($id);
        if ($nl['status'] !== Newsletter_model::STATUS_REVIEWING) {
            $this->_flash('warning', $this->lang->line('nl_collect_not_reviewing'));
            redirect('newsletter_admin/edition/' . $nl['id']);
            return;
        }
        $cfg = $this->_load_newsletter_config();
        $this->newsletter_imap->init($cfg);
        $since = date('d-M-Y', strtotime($nl['review_sent_at'] ?: $nl['created_at']));
        $uids  = $this->newsletter_imap->search_since($since, 200);
        if ($uids === FALSE) {
            $this->_flash('danger', sprintf($this->lang->line('nl_collect_imap_error'), htmlspecialchars($this->newsletter_imap->last_error())));
            redirect('newsletter_admin/edition/' . $nl['id']);
            return;
        }

        $reviewers = Newsletter_review_cycle::parse_addresses($cfg['newsletter_reviewers'] ?? '');
        $files = $this->_files();
        $report = array('retained' => array(), 'rejected' => array(), 'read' => 0);
        foreach ($uids as $uid) {
            $raw = $this->newsletter_imap->fetch_raw($uid);
            if ($raw === FALSE) continue;
            $report['read']++;
            $r = $this->newsletter_review_cycle->process_message($nl, $raw, $reviewers, $files);
            if ($r['status'] === 'retained') {
                $report['retained'][] = $r;
            } elseif ($r['status'] === 'rejected' || $r['reason'] === 'déjà récupérée') {
                $report['rejected'][] = $r;
            }
        }
        $this->session->set_flashdata('collect_report', $report);
        redirect('newsletter_admin/edition/' . $nl['id'] . '#suggestions');
    }

    // ── Tri des suggestions ───────────────────────────────────────────────

    public function decide($review_id) {
        $this->_load_cycle_models();
        $s = $this->newsletter_reviews_model->get((int) $review_id);
        if (!$s) show_404();
        $back = 'newsletter_admin/edition/' . (int) $s['newsletter_id'] . '#suggestion-' . (int) $s['id'];
        if (!$this->_require_post($back)) return;

        $res = $this->newsletter_reviews_model->decide($s['id'], $this->input->post('decision'),
            $this->input->post('admin_text'), $this->input->post('kind'));
        if ($res === TRUE) {
            $this->_flash('success', sprintf($this->lang->line('nl_decision_saved'),
                htmlspecialchars($this->lang->line('nl_decision_' . $this->input->post('decision')))));
        } else {
            $this->_flash('danger', $this->lang->line('nl_decision_error_' . $res) ?: $res);
        }
        redirect($back);
    }

    public function attach_postponed($review_id, $newsletter_id) {
        if (!$this->_require_post('newsletter_admin/edition/' . (int) $newsletter_id)) return;
        $this->_load_cycle_models();
        $nl = $this->_edition_or_404($newsletter_id);
        $ok = $this->newsletter_reviews_model->attach((int) $review_id, $nl['id'], $nl['review_iteration']);
        $this->_flash($ok ? 'success' : 'danger', $this->lang->line($ok ? 'nl_attach_ok' : 'nl_attach_error'));
        redirect('newsletter_admin/edition/' . $nl['id'] . '#suggestions');
    }

    // ── Nouvelle version par l'agent IA ───────────────────────────────────

    public function revise($id) {
        if (!$this->_require_post('newsletter_admin/edition/' . (int) $id)) return;
        $this->_load_cycle_models();
        $this->load->library('Newsletter_review_cycle');
        $nl   = $this->_edition_or_404($id);
        $back = 'newsletter_admin/edition/' . $nl['id'];

        if ($nl['status'] !== Newsletter_model::STATUS_REVIEWING) {
            $this->_flash('warning', $this->lang->line('nl_revise_not_reviewing'));
            redirect($back);
            return;
        }
        $suggestions = $this->newsletter_reviews_model->get_accepted($nl['id'], $nl['review_iteration']);
        if (!$suggestions) {
            $this->_flash('warning', $this->lang->line('nl_revise_nothing'));
            redirect($back);
            return;
        }
        $draft = $this->_current_draft($nl);
        $cfg   = $this->_load_newsletter_config();
        $key   = Newsletter_imap::decode_password($cfg['newsletter_anthropic_api_key'] ?? '');
        if ($draft === FALSE || $key === '') {
            $this->_flash('danger', $draft === FALSE ? $this->lang->line('nl_draft_missing') : $this->lang->line('newsletter_test_ai_no_key'));
            redirect($back);
            return;
        }

        $context = $this->newsletter_contexts_model->get_active();
        $prompt  = Newsletter_review_cycle::build_revision_prompt($draft, $suggestions, $context ? $context['content'] : '');
        $model   = ($cfg['newsletter_anthropic_model'] ?? '') ?: 'claude-sonnet-4-6';
        @set_time_limit(360);
        $this->load->library('Newsletter_ai');
        $resp = $this->newsletter_ai->message($key, $model, $prompt['user'], $prompt['system'], 32000, 300);

        if (!$resp['ok'] || trim($resp['text']) === '') {
            $this->_flash('danger', sprintf($this->lang->line('nl_revise_failed'), htmlspecialchars($resp['error'] ?? 'réponse vide')));
            redirect($back);
            return;
        }
        if ($resp['stop_reason'] === 'max_tokens') {
            $this->_flash('danger', $this->lang->line('nl_revise_truncated'));
            redirect($back);
            return;
        }

        $saved = $this->_files()->save_version($nl['id'], Newsletter_review_cycle::clean_ai_output($resp['text']));
        if (!$saved) {
            $this->_flash('danger', $this->lang->line('nl_revise_save_error'));
            redirect($back);
            return;
        }
        $this->newsletter_model->update($nl['id'], array('draft_path' => $saved['path']));
        $this->newsletter_model->transition($nl['id'], Newsletter_model::STATUS_DRAFT);
        $this->_flash('success', sprintf($this->lang->line('nl_revise_success'), $saved['version'], count($suggestions),
            (int) $resp['input_tokens'], (int) $resp['output_tokens'],
            site_url('newsletter_admin/version/' . $nl['id'] . '/' . $saved['version'])));
        redirect($back);
    }

    // ── Validation et publication ─────────────────────────────────────────

    public function validate_edition($id) {
        if (!$this->_require_post('newsletter_admin/edition/' . (int) $id)) return;
        $nl = $this->_edition_or_404($id);
        $this->_files();
        if ($nl['status'] !== Newsletter_model::STATUS_DRAFT) {
            $this->_flash('warning', $this->lang->line('nl_validate_not_draft'));
        } elseif ($this->newsletter_model->transition($nl['id'], Newsletter_model::STATUS_APPROVED)) {
            $this->_flash('success', sprintf($this->lang->line('nl_validate_success'), Newsletter_files::version_of($nl['draft_path'])));
        } else {
            $this->_flash('danger', $this->lang->line('nl_validate_error'));
        }
        redirect('newsletter_admin/edition/' . $nl['id']);
    }

    public function publish_edition($id) {
        if (!$this->_require_post('newsletter_admin/edition/' . (int) $id)) return;
        $nl   = $this->_edition_or_404($id);
        $back = 'newsletter_admin/edition/' . $nl['id'];
        if ($nl['status'] !== Newsletter_model::STATUS_APPROVED) {
            $this->_flash('warning', $this->lang->line('nl_publish_not_approved'));
            redirect($back);
            return;
        }
        $draft = $this->_current_draft($nl);
        if ($draft === FALSE) {
            $this->_flash('danger', $this->lang->line('nl_draft_missing'));
            redirect($back);
            return;
        }

        // Notes internes retirées de façon déterministe ; PDF dans le répertoire de l'archive membres
        $this->load->library('Newsletter_pdf');
        $pdf = $this->newsletter_pdf->render(Newsletter_files::remove_internal_notes($draft), $nl['title']);
        if (!is_dir(self::$pdf_upload_dir)) {
            mkdir(self::$pdf_upload_dir, 0775, TRUE);
        }
        $pdf_path = self::$pdf_upload_dir . 'veille_' . (int) $nl['id'] . '_' . bin2hex(random_bytes(8)) . '.pdf';
        if (file_put_contents($pdf_path, $pdf) === FALSE) {
            $this->_flash('danger', sprintf($this->lang->line('nl_publish_write_error'), self::$pdf_upload_dir));
            redirect($back);
            return;
        }
        $this->newsletter_model->transition($nl['id'], Newsletter_model::STATUS_PUBLISHED, array('pdf_path' => $pdf_path));
        $sent = $this->_send_to_subscribers($nl['id']);
        $this->_flash('success', sprintf($this->lang->line('nl_publish_success'), $sent, site_url('newsletter/pdf/' . $nl['id'])));
        redirect($back);
    }
}
