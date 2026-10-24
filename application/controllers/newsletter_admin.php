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
            'newsletter_reviewers', 'newsletter_validators',
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
        $payload = json_encode(array(
            'model'      => $model,
            'max_tokens' => (int) $max_tokens,
            'messages'   => array(
                array('role' => 'user', 'content' => $prompt),
            ),
        ));

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => array(
                'x-api-key: ' . $api_key,
                'anthropic-version: 2023-06-01',
                'content-type: application/json',
            ),
            CURLOPT_TIMEOUT        => 60,
        ));

        $response   = curl_exec($ch);
        $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            return array('ok' => false, 'error' => 'cURL : ' . $curl_error);
        }

        $data = json_decode($response, true);
        if ($http_code !== 200) {
            $err = isset($data['error']['message']) ? $data['error']['message'] : $response;
            return array('ok' => false, 'error' => "HTTP {$http_code} : {$err}");
        }

        return array(
            'ok'           => true,
            'text'         => $data['content'][0]['text'] ?? '',
            'model'        => $data['model']               ?? $model,
            'input_tokens' => $data['usage']['input_tokens']  ?? 0,
            'output_tokens'=> $data['usage']['output_tokens'] ?? 0,
            'stop_reason'  => $data['stop_reason']         ?? '',
        );
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
}
