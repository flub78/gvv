<?php

/**
 * Newsletter_admin
 *
 * Administration de la newsletter de veille : gestion des abonnés,
 * configuration IMAP/SMTP, pages de test d'infrastructure, et cycle de
 * relecture des brouillons produits par l'agent de rédaction (hors GVV).
 * Réservé aux club-admin.
 *
 * Routes :
 *   GET  newsletter_admin/index          Liste des abonnés
 *   POST newsletter_admin/delete/:id     Suppression d'un abonné
 *   GET/POST newsletter_admin/configuration  Configuration IMAP/SMTP
 *   GET/POST newsletter_admin/test_email     Validation envoi email
 *   GET/POST newsletter_admin/test_imap      Validation lecture IMAP
 *   GET/POST newsletter_admin/test_ai        Validation de l'accès à l'API Anthropic
 *
 * Cycle de relecture :
 *   GET  editions, edition/:id, version/:id/:n, edition_pdf/:id, image/:id/:n/:nom
 *   GET/POST import                      Import d'un brouillon (.md ou .zip)
 *   POST annotate/:id, delete_annotation/:id
 *   POST send_for_review/:id, collect_replies/:id, decide/:id, attach_postponed/:id/:nl
 *   GET  directives/:id, postponed       Fichiers pour l'agent de rédaction
 *   POST validate_and_publish/:id, publish_edition/:id, resend_to_subscribers/:id
 *   POST delete_edition/:id              Suppression d'une édition (liste, édition, archive)
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
        $this->load->library('Newsletter_mailer');
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
     * Envoie la newsletter à tous les abonnés actifs non encore notifiés.
     * Les envois réussis sont journalisés : un nouvel appel ne reprend que
     * les abonnés non atteints.
     *
     * @param int $newsletter_id
     * @return array  sent (nombre d'envois réalisés), failed (adresses en échec)
     */
    private function _send_to_subscribers($newsletter_id) {
        $result      = array('sent' => 0, 'failed' => array());
        $newsletter  = $this->newsletter_model->get($newsletter_id);
        if (empty($newsletter)) return $result;
        $subscribers = $this->newsletter_mailer->not_sent($newsletter);
        if (empty($subscribers)) return $result;
        // Envoi synchrone : ne pas l'interrompre au milieu de la liste
        @set_time_limit(0);

        $cfg  = $this->_load_newsletter_config();
        $smtp = $this->_init_newsletter_smtp($cfg);
        foreach ($subscribers as $sub) {
            if ($this->newsletter_mailer->send($newsletter, $sub, $cfg, $smtp)) {
                $result['sent']++;
            } else {
                $result['failed'][] = $sub['email'];
            }
        }
        return $result;
    }

    /**
     * Message de résultat d'un envoi aux abonnés : succès, ou avertissement
     * listant les abonnés non atteints, à reprendre par un nouvel envoi.
     */
    private function _flash_sending($intro, $res) {
        if (!$res['failed']) {
            $this->_flash('success', $intro . ' ' . sprintf($this->lang->line('nl_sending_ok'), $res['sent']));
            return;
        }
        $this->_flash('warning', $intro . ' ' . sprintf($this->lang->line('nl_sending_partial'),
            $res['sent'], count($res['failed']), htmlspecialchars(implode(', ', $res['failed']))));
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
            'newsletter_reviewers', 'newsletter_review_delay_days',
            'newsletter_storage_path',
            'newsletter_review_subject', 'newsletter_review_body',
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
        return $this->newsletter_mailer->load_config();
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

    private function _current_user_email() {
        $login = $this->dx_auth->get_username();
        if (empty($login)) return '';
        $row = $this->db->select('memail')->where('mlogin', $login)->get('membres')->row_array();
        return $row['memail'] ?? '';
    }

    /**
     * Initialise la bibliothèque email avec le SMTP de la newsletter.
     * @see Newsletter_mailer::init_smtp()
     */
    private function _init_newsletter_smtp($cfg) {
        return $this->newsletter_mailer->init_smtp($cfg);
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

    // ══════════════════════════════════════════════════════════════════════
    // Cycle de relecture des brouillons de l'agent de rédaction : éditions,
    // import, annotation, relecture, consignes de modification, validation,
    // publication (plan P2)
    // ══════════════════════════════════════════════════════════════════════

    /** Taille maximale d'un brouillon importé (.md ou .zip) */
    const NL_MAX_IMPORT = 31457280;

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
        $this->load->model('newsletter_reviews_model');
        $this->load->model('newsletter_annotations_model');
    }

    private function _edition_or_404($id) {
        $nl = $this->newsletter_model->get((int) $id);
        if (!$nl) show_404();
        return $nl;
    }

    private function _current_draft($nl) {
        return $nl['draft_path'] ? $this->_files()->read($nl['draft_path']) : FALSE;
    }

    private function _version($nl) {
        $this->_files();
        return Newsletter_files::version_of($nl['draft_path']);
    }

    /**
     * Brouillon annoté de la version courante (commentaires de l'administrateur insérés).
     */
    private function _annotated($nl, $draft) {
        $this->_load_cycle_models();
        return Newsletter_files::annotate($draft, $this->newsletter_annotations_model->get_for_version($nl['id'], $this->_version($nl)));
    }

    /**
     * HTML d'un Markdown de brouillon, images servies par le contrôleur.
     */
    private function _markdown($md, $newsletter_id, $version) {
        $this->load->helper('markdown');
        $html = markdown(Newsletter_files::strip_html_comments($md));
        $base = site_url('newsletter_admin/image/' . (int) $newsletter_id . '/' . (int) $version) . '/';
        return preg_replace_callback('#(<img\b[^>]*\bsrc=")img/([^"/]+)"#i', function ($m) use ($base) {
            return $m[1] . $base . rawurlencode(html_entity_decode($m[2])) . '" style="max-width:100%;"';
        }, $html);
    }

    /**
     * PDF du brouillon annoté (relecture) ou de la version publiée.
     */
    private function _pdf($nl, $markdown, $badge = '') {
        $this->_load_cycle_models();
        $this->load->library('Newsletter_pdf');
        $version = $this->_version($nl);
        $labels = array_column($this->newsletter_annotations_model->get_for_version($nl['id'], $version), 'label');
        $labels[] = $this->lang->line('nl_annotation_default_label');
        $this->load->library('Newsletter_review_cycle');
        return $this->newsletter_pdf->render($markdown, $nl['title'], array(
            'club'           => $this->config->item('nom_club') ?: '',
            'period'         => sprintf($this->lang->line('nl_pdf_period'), Newsletter_review_cycle::period_label($nl)),
            'badge'          => $badge,
            'image_dir'      => $this->_files()->version_dir($nl['id'], $version) . 'img',
            'comment_labels' => array_unique($labels),
        ));
    }

    /**
     * Texte publié : brouillon annoté, notes internes retirées, mention de validation ajoutée.
     */
    private function _publication_markdown($nl, $draft) {
        $mention = sprintf($this->lang->line('nl_validated_mention'),
            $nl['approved_at'] ? date('d/m/Y', strtotime($nl['approved_at'])) : date('d/m/Y'),
            $this->_member_name($nl['approved_by']));
        return Newsletter_files::append_validation(Newsletter_files::remove_internal_notes($this->_annotated($nl, $draft)), $mention);
    }

    /**
     * Prénom et nom d'un membre à partir de son identifiant de connexion.
     */
    private function _member_name($login) {
        $m = $login ? $this->db->select('mprenom, mnom')->get_where('membres', array('mlogin' => $login))->row_array() : NULL;
        return ($m && trim($m['mprenom'] . ' ' . $m['mnom']) !== '') ? trim($m['mprenom'] . ' ' . $m['mnom']) : (string) $login;
    }

    private function _send_file($content, $type, $name, $disposition = 'attachment') {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: ' . $type);
        header('Content-Disposition: ' . $disposition . '; filename="' . $name . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }

    /**
     * Période de l'édition pour les noms de fichiers téléchargés (AAAAMMJJ-AAAAMMJJ).
     */
    private static function _period_slug($nl) {
        return str_replace('-', '', $nl['period_start']) . '-' . str_replace('-', '', $nl['period_end']);
    }

    /**
     * Envoie un Markdown, ou une archive zip avec ses images sous img/.
     */
    private function _send_md_or_zip($basename, $markdown, array $images) {
        if (!$images) {
            $this->_send_file($markdown, 'text/markdown; charset=utf-8', $basename . '.md');
        }
        $zip = Newsletter_files::make_zip($basename . '.md', $markdown, $images);
        if ($zip === FALSE) show_error($this->lang->line('nl_zip_error'), 500);
        $this->_send_file($zip, 'application/zip', $basename . '.zip');
    }

    /**
     * Les réponses peuvent être relevées dès qu'une version a été transmise.
     */
    private static function _can_collect($nl) {
        return !empty($nl['review_sent_at'])
            && in_array($nl['status'], array(Newsletter_model::STATUS_DRAFT, Newsletter_model::STATUS_REVIEWING), TRUE);
    }

    // ── Éditions ──────────────────────────────────────────────────────────

    public function editions() {
        $this->_load_cycle_models();
        $newsletters = $this->newsletter_model->get_all();
        foreach ($newsletters as &$n) {
            $n['can_collect'] = self::_can_collect($n);
        }
        unset($n);
        $data = array(
            'title'       => $this->lang->line('nl_editions_title'),
            'newsletters' => $newsletters,
            'postponed'   => count($this->newsletter_reviews_model->get_postponed()),
            'message'     => $this->session->flashdata('message'),
        );
        $this->_render('newsletter_admin/bs_editions', $data);
    }

    public function edition($id) {
        $this->_load_cycle_models();
        $this->load->library('Newsletter_review_cycle');
        $nl      = $this->_edition_or_404($id);
        $files   = $this->_files();
        $draft   = $this->_current_draft($nl);
        $version = $this->_version($nl);
        $cfg     = $this->_load_newsletter_config();

        $blocks = array();
        if ($draft !== FALSE) {
            foreach (Newsletter_files::split_blocks($draft) as $i => $b) {
                $blocks[$i] = array('html' => $this->_markdown($b, $nl['id'], $version), 'annotations' => array());
            }
        }
        $annotations = $this->newsletter_annotations_model->get_for_version($nl['id'], $version);
        foreach ($annotations as $a) {
            $i = min((int) $a['block_index'], max(0, count($blocks) - 1));
            if (isset($blocks[$i])) $blocks[$i]['annotations'][] = $a;
        }
        $remarks = $this->newsletter_reviews_model->get_for_newsletter($nl['id']);

        $data = array(
            'title'       => $nl['title'],
            'nl'          => $nl,
            'version'     => $version,
            'versions'    => $files->list_versions($nl['id']),
            'blocks'      => ($draft !== FALSE) ? $blocks : NULL,
            'annotations' => $annotations,
            'mentions'    => ($draft !== FALSE) ? Newsletter_files::find_open_mentions($draft) : array(),
            'remarks'     => $remarks,
            'postponed'   => $this->newsletter_reviews_model->get_postponed($nl['id']),
            'reviewers'   => Newsletter_review_cycle::parse_addresses($cfg['newsletter_reviewers'] ?? ''),
            'pending'     => $this->newsletter_reviews_model->count_pending($nl['id']),
            'retained'    => count($this->_directive_remarks($nl)),
            'can_collect' => self::_can_collect($nl),
            'approver'    => $nl['approved_by'] ? $this->_member_name($nl['approved_by']) : '',
            'unsent'      => ($nl['status'] === Newsletter_model::STATUS_PUBLISHED)
                             ? count($this->newsletter_mailer->not_sent($nl)) : 0,
            'attachments' => $this->_reply_attachments($files, $remarks),
            'collect'     => $this->session->flashdata('collect_report'),
            'message'     => $this->session->flashdata('message'),
        );
        $this->_render('newsletter_admin/bs_edition', $data);
    }

    /**
     * Pièces jointes conservées des réponses, par remarque.
     */
    private function _reply_attachments($files, array $remarks) {
        $out = array();
        foreach ($remarks as $r) {
            if ($r['has_attachments']) $out[(int) $r['id']] = $files->list_reply_files($r['id']);
        }
        return $out;
    }

    public function attachment($review_id, $name = '') {
        $this->_load_cycle_models();
        $r = $this->newsletter_reviews_model->get((int) $review_id);
        $name = basename(rawurldecode($name));
        if (!$r || $name === '') show_404();
        $files = $this->_files();
        $content = $files->read($files->reply_dir($r['id']) . $name);
        if ($content === FALSE) show_404();
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $this->_send_file($content, $finfo->buffer($content) ?: 'application/octet-stream', $name);
    }

    /**
     * Image d'une version du brouillon.
     */
    public function image($id, $n, $name = '') {
        $nl = $this->_edition_or_404($id);
        $files = $this->_files();
        $path = $files->image_path($nl['id'], (int) $n, rawurldecode($name));
        $content = $path ? $files->read($path) : FALSE;
        if ($content === FALSE) show_404();
        $type = (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'svg') ? 'image/svg+xml'
              : ((new finfo(FILEINFO_MIME_TYPE))->buffer($content) ?: 'application/octet-stream');
        header('Content-Security-Policy: default-src \'none\'; style-src \'unsafe-inline\'');
        $this->_send_file($content, $type, Newsletter_files::safe_name($name), 'inline');
    }

    /**
     * Affiche une version du brouillon, avec ses différences par rapport à la précédente.
     */
    public function version($id, $n) {
        $nl    = $this->_edition_or_404($id);
        $files = $this->_files();
        $current = $previous = NULL;
        foreach ($files->list_versions($nl['id']) as $v) {
            if ($v['version'] === (int) $n) $current = $v;
            if ($v['version'] < (int) $n) $previous = $v;
        }
        if (!$current) show_404();
        $text = $files->read($current['path']);
        $data = array(
            'title'    => $nl['title'] . ' — v' . (int) $n,
            'nl'       => $nl,
            'n'        => (int) $n,
            'previous' => $previous ? $previous['version'] : NULL,
            'html'     => $this->_markdown($text, $nl['id'], (int) $n),
            'diff'     => $previous ? Newsletter_files::diff_lines($files->read($previous['path']), $text) : NULL,
        );
        $this->_render('newsletter_admin/bs_version', $data);
    }

    /**
     * Aperçu PDF de la version courante : brouillon annoté (notes internes
     * comprises) avant validation, texte publié après.
     */
    public function edition_pdf($id) {
        $this->_load_cycle_models();
        $nl = $this->_edition_or_404($id);
        $draft = $this->_current_draft($nl);
        if ($draft === FALSE) show_404();
        $version = $this->_version($nl);
        if (in_array($nl['status'], array(Newsletter_model::STATUS_APPROVED, Newsletter_model::STATUS_PUBLISHED), TRUE)) {
            $pdf = $this->_pdf($nl, $this->_publication_markdown($nl, $draft));
        } else {
            $pdf = $this->_pdf($nl, $this->_annotated($nl, $draft), sprintf($this->lang->line('nl_pdf_badge'), $version));
        }
        $this->_send_file($pdf, 'application/pdf', 'brouillon_' . self::_period_slug($nl) . '_v' . $version . '.pdf', 'inline');
    }

    /**
     * Supprime une édition, quel que soit son état : PDF publié de l'archive,
     * versions du brouillon, puis remarques, commentaires et journal des
     * envois (en cascade). Appelée depuis la liste des éditions, la page de
     * l'édition ou l'archive des membres (champ « back »).
     */
    public function delete_edition($id) {
        $targets = array('archive' => 'newsletter', 'editions' => 'newsletter_admin/editions');
        $back = $targets[$this->input->post('back')] ?? 'newsletter_admin/editions';
        if (!$this->_require_post($back)) return;
        $nl = $this->_edition_or_404($id);
        if (!$this->newsletter_model->delete($nl['id'])) {
            $this->_flash('danger', sprintf($this->lang->line('nl_delete_error'), htmlspecialchars($nl['title'])));
            redirect($back);
            return;
        }
        $pdf = $nl['pdf_path'] ? realpath($nl['pdf_path']) : FALSE;
        $archive = realpath(self::$pdf_upload_dir);
        if ($pdf && $archive && strpos($pdf, $archive . '/') === 0) {
            @unlink($pdf);
        }
        $this->_files()->delete_edition($nl['id']);
        $this->_flash('success', sprintf($this->lang->line('nl_edition_deleted'), htmlspecialchars($nl['title'])));
        redirect($back);
    }

    // ── Import d'un brouillon ─────────────────────────────────────────────

    public function import() {
        $result = NULL;
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $f = $_FILES['draft_file'] ?? NULL;
            $errors = array();
            if (empty($f['name'])) {
                $errors[] = $this->lang->line('nl_import_no_file');
            } elseif ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
                $errors[] = sprintf($this->lang->line('nl_upload_failed'), $f['name']);
            } elseif ($f['size'] > self::NL_MAX_IMPORT) {
                $errors[] = sprintf($this->lang->line('nl_upload_too_big'), $f['name']);
            }

            if (!$errors) {
                $this->load->library('Newsletter_importer', array('files' => $this->_files()));
                $result = $this->newsletter_importer->import($f['name'], file_get_contents($f['tmp_name']));
                if ($result['ok']) {
                    $this->load->library('Newsletter_review_cycle');
                    $nl = $this->newsletter_model->get($result['newsletter_id']);
                    $html = sprintf($this->lang->line($result['created'] ? 'nl_import_success_new' : 'nl_import_success_version'),
                        (int) $result['version'], htmlspecialchars($nl['title']),
                        htmlspecialchars(Newsletter_review_cycle::period_label($nl)));
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
        $this->_render('newsletter_admin/bs_import', array(
            'title'  => $this->lang->line('nl_import_title'),
            'result' => $result,
        ));
    }

    // ── Annotations ───────────────────────────────────────────────────────

    public function annotate($id) {
        if (!$this->_require_post('newsletter_admin/edition/' . (int) $id)) return;
        $this->_load_cycle_models();
        $nl = $this->_edition_or_404($id);
        $block = (int) $this->input->post('block_index');
        $back = 'newsletter_admin/edition/' . $nl['id'] . '#bloc-' . $block;
        $label = trim((string) $this->input->post('label'));
        $content = trim((string) $this->input->post('content'));
        $draft = $this->_current_draft($nl);

        if (!in_array($nl['status'], array(Newsletter_model::STATUS_DRAFT, Newsletter_model::STATUS_REVIEWING), TRUE)) {
            $this->_flash('warning', $this->lang->line('nl_annotate_not_editable'));
        } elseif ($content === '' || $label === '') {
            $this->_flash('danger', $this->lang->line('nl_annotate_empty'));
        } elseif ($draft === FALSE || !array_key_exists($block, $blocks = Newsletter_files::split_blocks($draft))) {
            $this->_flash('danger', $this->lang->line('nl_annotate_bad_block'));
        } else {
            $ok = $this->newsletter_annotations_model->add(array(
                'newsletter_id' => $nl['id'],
                'version'       => $this->_version($nl),
                'block_index'   => $block,
                'anchor_text'   => mb_substr(preg_replace('/\s+/u', ' ', trim($blocks[$block])), 0, 255),
                'label'         => $label,
                'content'       => $content,
            ));
            $this->_flash($ok ? 'success' : 'danger', $this->lang->line($ok ? 'nl_annotate_ok' : 'nl_annotate_error'));
        }
        redirect($back);
    }

    public function delete_annotation($annotation_id) {
        $this->_load_cycle_models();
        $a = $this->newsletter_annotations_model->get((int) $annotation_id);
        if (!$a) show_404();
        $back = 'newsletter_admin/edition/' . (int) $a['newsletter_id'] . '#bloc-' . (int) $a['block_index'];
        if (!$this->_require_post($back)) return;
        $nl = $this->_edition_or_404($a['newsletter_id']);
        if (!in_array($nl['status'], array(Newsletter_model::STATUS_DRAFT, Newsletter_model::STATUS_REVIEWING), TRUE)) {
            $this->_flash('warning', $this->lang->line('nl_annotate_not_editable'));
        } else {
            $ok = $this->newsletter_annotations_model->delete($a['id']);
            $this->_flash($ok ? 'success' : 'danger', $this->lang->line($ok ? 'nl_annotation_deleted' : 'nl_annotate_error'));
        }
        redirect($back);
    }

    // ── Transmission aux relecteurs ───────────────────────────────────────

    public function send_for_review($id) {
        if (!$this->_require_post('newsletter_admin/edition/' . (int) $id)) return;
        $this->_load_cycle_models();
        $this->load->library('Newsletter_review_cycle');
        $nl  = $this->_edition_or_404($id);
        $res = $this->_send_for_review($nl);
        $this->_flash($res['ok'] ? 'success' : 'danger', $res['message']);
        redirect('newsletter_admin/edition/' . $nl['id']);
    }

    /**
     * Envoie le brouillon annoté de la version courante aux relecteurs et
     * passe l'édition en relecture.
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

        $version  = $this->_version($nl);
        $delay    = ($cfg['newsletter_review_delay_days'] ?? '') !== '' ? (int) $cfg['newsletter_review_delay_days'] : 2;
        $deadline = Newsletter_review_cycle::deadline($delay);
        $mail     = Newsletter_review_cycle::build_review_email($cfg, $nl, $version, $deadline);

        $pdf_tmp = sys_get_temp_dir() . '/' . 'Veille_brouillon_' . (int) $nl['id'] . '_v' . $version . '_' . uniqid() . '.pdf';
        file_put_contents($pdf_tmp, $this->_pdf($nl, $this->_annotated($nl, $draft), sprintf($this->lang->line('nl_pdf_badge'), $version)));

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

    // ── Relevé des réponses ───────────────────────────────────────────────

    public function collect_replies($id) {
        $back = $this->input->post('back') === 'editions' ? 'newsletter_admin/editions' : 'newsletter_admin/edition/' . (int) $id;
        if (!$this->_require_post($back)) return;
        $this->_load_cycle_models();
        $this->load->library('Newsletter_review_cycle');
        $nl = $this->_edition_or_404($id);
        if (!self::_can_collect($nl)) {
            $this->_flash('warning', $this->lang->line('nl_collect_not_sent'));
            redirect($back);
            return;
        }
        $cfg = $this->_load_newsletter_config();
        $this->newsletter_imap->init($cfg);
        // Depuis la création de l'édition : une réponse tardive à une version
        // précédente reste valable pour la version courante
        $since = date('d-M-Y', strtotime($nl['created_at'] ?: $nl['review_sent_at']));
        $uids  = $this->newsletter_imap->search_since($since, 200);
        if ($uids === FALSE) {
            $this->_flash('danger', sprintf($this->lang->line('nl_collect_imap_error'), htmlspecialchars($this->newsletter_imap->last_error())));
            redirect($back);
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
        $html = sprintf($this->lang->line('nl_collect_summary'), (int) $report['read'], count($report['retained']), count($report['rejected']));
        if ($back === 'newsletter_admin/editions') {
            $this->_flash($report['retained'] ? 'success' : 'info', htmlspecialchars($nl['title']) . ' : ' . $html);
        }
        redirect($back === 'newsletter_admin/editions' ? $back : $back . '#remarques');
    }

    // ── Tri des remarques ─────────────────────────────────────────────────

    public function decide($review_id) {
        $this->_load_cycle_models();
        $r = $this->newsletter_reviews_model->get((int) $review_id);
        if (!$r) show_404();
        $back = 'newsletter_admin/edition/' . (int) $r['newsletter_id'] . '#remarque-' . (int) $r['id'];
        if (!$this->_require_post($back)) return;

        $decision = (string) $this->input->post('decision');
        $res = $this->newsletter_reviews_model->decide($r['id'], $decision, $this->input->post('admin_text'));
        if ($res === TRUE) {
            $this->_flash('success', sprintf($this->lang->line('nl_decision_saved'),
                htmlspecialchars($this->lang->line('nl_decision_' . $decision))));
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
        redirect('newsletter_admin/edition/' . $nl['id'] . '#remarques');
    }

    // ── Fichiers pour l'agent de rédaction ────────────────────────────────

    /**
     * Remarques du tour courant transmises à l'agent : acceptées ou modifiées
     * (APPLIQUER) et reportées (REPORTER), dans l'ordre d'arrivée.
     */
    private function _directive_remarks($nl) {
        $remarks = array_merge(
            $this->newsletter_reviews_model->get_accepted($nl['id'], $nl['review_iteration']),
            $this->newsletter_reviews_model->get_postponed_in_round($nl['id'], $nl['review_iteration']));
        usort($remarks, function ($a, $b) { return (int) $a['id'] - (int) $b['id']; });
        return $remarks;
    }

    /**
     * Consignes de modification de la version courante (.md, ou .zip avec les images).
     */
    public function directives($id) {
        $this->_load_cycle_models();
        $this->load->library('Newsletter_review_cycle');
        $nl = $this->_edition_or_404($id);
        $draft = $this->_current_draft($nl);
        $version = $this->_version($nl);
        $annotations = $this->newsletter_annotations_model->get_for_version($nl['id'], $version);
        $remarks = $this->_directive_remarks($nl);
        if ($draft === FALSE || (!$annotations && !$remarks)) {
            $this->_flash('warning', $this->lang->line($draft === FALSE ? 'nl_draft_missing' : 'nl_directives_nothing'));
            redirect('newsletter_admin/edition/' . $nl['id']);
            return;
        }
        $d = Newsletter_review_cycle::build_directives($nl, $version, $draft, $annotations, $remarks, $this->_files());
        $this->_send_md_or_zip('consignes_modification_' . self::_period_slug($nl) . '_v' . $version, $d['markdown'], $d['images']);
    }

    /**
     * Remarques reportées, pour la version initiale d'une prochaine édition.
     */
    public function postponed() {
        $this->_load_cycle_models();
        $this->load->library('Newsletter_review_cycle');
        $remarks = $this->newsletter_reviews_model->get_postponed();
        if (!$remarks) {
            $this->_flash('info', $this->lang->line('nl_postponed_none'));
            redirect('newsletter_admin/editions');
            return;
        }
        $d = Newsletter_review_cycle::build_postponed($remarks, $this->_files());
        $this->_send_md_or_zip('remarques_reportees_' . date('Ymd'), $d['markdown'], $d['images']);
    }

    // ── Validation et publication ─────────────────────────────────────────

    /**
     * Valide la version courante puis la publie : PDF, archive membres, envoi aux abonnés.
     */
    public function validate_and_publish($id) {
        if (!$this->_require_post('newsletter_admin/edition/' . (int) $id)) return;
        $nl = $this->_edition_or_404($id);
        $back = 'newsletter_admin/edition/' . $nl['id'];
        if ($nl['status'] !== Newsletter_model::STATUS_DRAFT) {
            $this->_flash('warning', $this->lang->line('nl_validate_not_draft'));
        } elseif ($this->_current_draft($nl) === FALSE) {
            $this->_flash('danger', $this->lang->line('nl_draft_missing'));
        } elseif (!$this->newsletter_model->transition($nl['id'], Newsletter_model::STATUS_APPROVED)) {
            $this->_flash('danger', $this->lang->line('nl_validate_error'));
        } else {
            $this->_publish($this->newsletter_model->get($nl['id']));
        }
        redirect($back);
    }

    /**
     * Publie une édition validée dont la publication a échoué (nouvel essai).
     */
    public function publish_edition($id) {
        if (!$this->_require_post('newsletter_admin/edition/' . (int) $id)) return;
        $nl = $this->_edition_or_404($id);
        if ($nl['status'] !== Newsletter_model::STATUS_APPROVED) {
            $this->_flash('warning', $this->lang->line('nl_publish_not_approved'));
        } else {
            $this->_publish($nl);
        }
        redirect('newsletter_admin/edition/' . $nl['id']);
    }

    /**
     * Publication d'une édition validée : notes internes retirées et mention de
     * validation ajoutée, PDF déposé dans l'archive membres, envoi aux abonnés.
     * Le résultat est affiché par un message ; en cas d'échec, l'édition reste validée.
     */
    private function _publish($nl) {
        $this->_load_cycle_models();
        $version = $this->_version($nl);
        $draft = $this->_current_draft($nl);
        if ($draft === FALSE) {
            $this->_flash('danger', $this->lang->line('nl_draft_missing'));
            return FALSE;
        }
        $pdf = $this->_pdf($nl, $this->_publication_markdown($nl, $draft));
        if (!is_dir(self::$pdf_upload_dir)) {
            mkdir(self::$pdf_upload_dir, 0775, TRUE);
        }
        $pdf_path = self::$pdf_upload_dir . 'veille_' . (int) $nl['id'] . '_' . bin2hex(random_bytes(8)) . '.pdf';
        if (file_put_contents($pdf_path, $pdf) === FALSE) {
            $this->_flash('danger', sprintf($this->lang->line('nl_publish_write_error'), self::$pdf_upload_dir));
            return FALSE;
        }
        if (!$this->newsletter_model->transition($nl['id'], Newsletter_model::STATUS_PUBLISHED, array('pdf_path' => $pdf_path))) {
            @unlink($pdf_path);
            $this->_flash('danger', $this->lang->line('nl_publish_error'));
            return FALSE;
        }
        $res = $this->_send_to_subscribers($nl['id']);
        $this->_flash_sending(sprintf($this->lang->line('nl_publish_success'), $version, site_url('newsletter/pdf/' . $nl['id'])), $res);
        return TRUE;
    }

    /**
     * Envoie une newsletter publiée aux abonnés qui ne l'ont pas encore reçue
     * (envoi précédent en échec ou interrompu, nouveaux abonnés).
     */
    public function resend_to_subscribers($id) {
        if (!$this->_require_post('newsletter_admin/edition/' . (int) $id)) return;
        $nl = $this->_edition_or_404($id);
        if ($nl['status'] !== Newsletter_model::STATUS_PUBLISHED) {
            $this->_flash('warning', $this->lang->line('nl_resend_not_published'));
        } elseif (!$this->newsletter_mailer->not_sent($nl)) {
            $this->_flash('info', $this->lang->line('nl_resend_none'));
        } else {
            $this->_flash_sending($this->lang->line('nl_resend_done'), $this->_send_to_subscribers($nl['id']));
        }
        redirect('newsletter_admin/edition/' . $nl['id']);
    }
}
