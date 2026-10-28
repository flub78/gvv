<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require_once(APPPATH . 'libraries/Newsletter_imap.php');

/**
 * Newsletter_mailer
 *
 * Envoi d'une newsletter publiée à un abonné, partagé par la diffusion
 * (newsletter_admin) et l'accueil d'un nouvel abonné (newsletter_public),
 * qui reçoit à la confirmation de son abonnement le dernier numéro paru.
 * Chaque envoi réussi est journalisé dans newsletter_sendings.
 *
 * @see doc/design_notes/veille_documentaire_design.md (Abonnements)
 */
class Newsletter_mailer {

    /** Résultats de send_latest() */
    const LATEST_SENT    = 'sent';
    const LATEST_NONE    = 'none';
    const LATEST_ALREADY = 'already';
    const LATEST_FAILED  = 'failed';

    private $CI;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('newsletter_model');
        $this->CI->load->model('newsletter_subscribers_model');
        $this->CI->load->library('email');
        $this->CI->load->helper('email');
        $this->CI->lang->load('newsletter');
    }

    /**
     * Lit application/config/newsletter.php.
     *
     * @return array  clé => valeur
     */
    public function load_config() {
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

    /**
     * Initialise la bibliothèque email avec le SMTP de la newsletter.
     * Si newsletter_smtp_host est vide, utilise la config globale (Brevo).
     *
     * @return array  from, from_name
     */
    public function init_smtp($cfg) {
        if (!empty($cfg['newsletter_smtp_host'])) {
            $this->CI->email->initialize(array(
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
            'from'      => ($cfg['newsletter_smtp_from']      ?? '') ?: $this->CI->config->item('email_club'),
            'from_name' => ($cfg['newsletter_smtp_from_name'] ?? '') ?: $this->CI->config->item('nom_club'),
        );
    }

    /**
     * Sujet et texte de l'email accompagnant une newsletter.
     *
     * @param array  $newsletter
     * @param array  $sub       Abonné (first_name, last_name, unsubscribe_token)
     * @param array  $cfg       Configuration newsletter
     * @param string $nom_club  Nom de l'expéditeur
     * @param bool   $welcome   Dernier numéro paru, envoyé à la confirmation de l'abonnement
     * @return array  subject, body
     */
    public function message($newsletter, $sub, $cfg, $nom_club, $welcome = FALSE) {
        $club    = $this->CI->config->item('nom_club') ?: $nom_club;
        $start   = date('d/m/Y', strtotime($newsletter['period_start']));
        $end     = date('d/m/Y', strtotime($newsletter['period_end']));
        $contact = ($cfg['newsletter_imap_user'] ?? '') ?: ($cfg['newsletter_smtp_from'] ?? '');
        $intro   = $welcome ? 'nl_mail_welcome_intro' : 'nl_mail_intro';

        $body  = sprintf($this->CI->lang->line('nl_mail_greeting'), $sub['first_name'], $sub['last_name']) . "\n\n";
        $body .= sprintf($this->CI->lang->line($intro), $club, $start, $end) . "\n\n";
        if ($contact) {
            $body .= sprintf($this->CI->lang->line('nl_mail_feedback'), $contact) . "\n\n";
        }
        $body .= sprintf($this->CI->lang->line('nl_mail_signature'), $nom_club) . "\n\n";
        $body .= sprintf($this->CI->lang->line('nl_mail_unsubscribe'),
                         site_url('newsletter_public/unsubscribe/') . $sub['unsubscribe_token']) . "\n";

        return array(
            'subject' => sprintf($this->CI->lang->line('nl_mail_subject'), $nom_club, $start, $end),
            'body'    => $body,
        );
    }

    /**
     * Envoie une newsletter publiée à un abonné, PDF en pièce jointe, et
     * journalise l'envoi réussi. init_smtp() doit avoir été appelé.
     *
     * @param array $newsletter
     * @param array $sub
     * @param array $cfg
     * @param array $smtp       Résultat de init_smtp()
     * @param bool  $welcome    Envoi d'accueil : adresse soumise à la redirection test_email
     * @return bool
     */
    public function send($newsletter, $sub, $cfg, $smtp, $welcome = FALSE) {
        $nom_club = $smtp['from_name'] ?: $this->CI->config->item('nom_club') ?: 'Le club';
        $msg      = $this->message($newsletter, $sub, $cfg, $nom_club, $welcome);
        $subject  = $msg['subject'];
        // Diffusion déclenchée manuellement : envoi aux abonnés réels ;
        // envoi d'accueil déclenché par le public : redirection test_email
        $to = $welcome ? test_intercept_email($sub['email'], $subject) : $sub['email'];

        $this->CI->email->clear(TRUE);
        $this->CI->email->set_mailtype('text');
        $this->CI->email->set_newline("\r\n");
        $this->CI->email->from($smtp['from'], $nom_club);
        $this->CI->email->to($to);
        $this->CI->email->subject($subject);
        $this->CI->email->message($msg['body']);
        if (!empty($newsletter['pdf_path']) && file_exists($newsletter['pdf_path'])) {
            $this->CI->email->attach($newsletter['pdf_path'], 'attachment', $newsletter['title'] . '.pdf', 'application/pdf');
        }

        if ($this->CI->email->send()) {
            $this->CI->newsletter_subscribers_model->record_sending($newsletter['id'], $sub['id']);
            log_message('info', 'newsletter_mailer: envoi newsletter #' . $newsletter['id'] . ' à ' . $sub['email']);
            return TRUE;
        }
        log_message('error', 'newsletter_mailer: échec envoi #' . $newsletter['id'] . ' à ' . $sub['email']);
        return FALSE;
    }

    /**
     * Dernier numéro publié, ou NULL s'il n'y en a pas.
     */
    public function latest_published() {
        $published = $this->CI->newsletter_model->get_published();
        return $published ? $published[0] : NULL;
    }

    /**
     * Envoie à un abonné qui vient de confirmer son abonnement le dernier
     * numéro paru, s'il ne l'a pas déjà reçu.
     *
     * @param array $sub
     * @return string  LATEST_SENT, LATEST_NONE (aucun numéro publié),
     *                 LATEST_ALREADY (déjà reçu) ou LATEST_FAILED
     */
    public function send_latest($sub) {
        $newsletter = $this->latest_published();
        if (!$newsletter) return self::LATEST_NONE;
        if ($this->CI->newsletter_subscribers_model->already_sent($newsletter['id'], $sub['id'])) {
            return self::LATEST_ALREADY;
        }
        if (empty($newsletter['pdf_path']) || !file_exists($newsletter['pdf_path'])) {
            log_message('error', 'newsletter_mailer: PDF absent pour le numéro #' . $newsletter['id']);
            return self::LATEST_FAILED;
        }
        $cfg  = $this->load_config();
        $smtp = $this->init_smtp($cfg);
        return $this->send($newsletter, $sub, $cfg, $smtp, TRUE) ? self::LATEST_SENT : self::LATEST_FAILED;
    }
}
