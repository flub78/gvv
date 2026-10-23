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
            'newsletter_context_path', 'newsletter_output_path',
            'newsletter_alerts_enabled',
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

        $to_intercepted = test_intercept_email($to, $subject);
        $this->email->clear(true);
        $this->email->set_mailtype('text');
        $this->email->set_newline("\r\n");
        $this->email->from($from, $from_name);
        $this->email->to($to_intercepted);
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
            return array('ok' => true, 'msg' => "Email envoyé de {$from} vers {$to_intercepted}{$attachment_info}.");
        }
        return array('ok' => false, 'msg' => "Échec : " . $this->email->print_debugger(array('headers')));
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
}
