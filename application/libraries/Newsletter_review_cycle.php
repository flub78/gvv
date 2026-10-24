<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require_once(APPPATH . 'libraries/Newsletter_mail_parser.php');

/**
 * Newsletter_review_cycle
 *
 * Règles du cycle de relecture piloté par l'administrateur (version 1) :
 * - référence d'édition et de version portée par le sujet des emails ;
 * - contenu de l'email de transmission aux relecteurs ;
 * - récupération des réponses sur la boîte de veille (tri, anti-doublon,
 *   pièces jointes) et enregistrement en suggestions ;
 * - préparation de la demande de nouvelle version à l'agent IA.
 *
 * L'envoi des emails et les appels réseau restent à la charge de l'appelant.
 *
 * @see doc/design_notes/veille_documentaire_design.md (Réponses et suggestions)
 */
class Newsletter_review_cycle {

    const DEFAULT_SUBJECT = '[Veille] Brouillon {period} (v{version}) — relecture souhaitée';
    const DEFAULT_BODY    = "Bonjour,\n\nVous trouverez en pièce jointe le brouillon v{version} de la lettre de veille pour la période : {period}.\n\nMerci de répondre à cet email avant le {deadline} avec vos remarques, corrections ou suggestions d'articles. Une réponse par sujet facilite le tri.\n\nCordialement,\n{from_name}";

    /** Taille maximale d'une pièce jointe conservée (octets) */
    const MAX_ATTACHMENT = 10485760;

    private $CI;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('newsletter_model');
        $this->CI->load->model('newsletter_reviews_model');
    }

    // ── Référence portée par le sujet ─────────────────────────────────────

    public static function reference_tag($newsletter_id, $version) {
        return '[NL-' . (int) $newsletter_id . ' v' . (int) $version . ']';
    }

    /**
     * @return array|null  ['newsletter_id' => int, 'version' => int]
     */
    public static function parse_reference($subject) {
        if (preg_match('/\[NL-(\d+) v(\d+)\]/', $subject, $m)) {
            return array('newsletter_id' => (int) $m[1], 'version' => (int) $m[2]);
        }
        return NULL;
    }

    /**
     * Adresses email d'une liste séparée par des virgules, points-virgules ou blancs.
     */
    public static function parse_addresses($raw) {
        $out = array();
        foreach (preg_split('/[\s,;]+/', (string) $raw) as $a) {
            $a = strtolower(trim($a));
            if ($a !== '' && filter_var($a, FILTER_VALIDATE_EMAIL)) $out[] = $a;
        }
        return array_values(array_unique($out));
    }

    public static function period_label($newsletter) {
        return date('d/m/Y', strtotime($newsletter['period_start'])) . ' – ' . date('d/m/Y', strtotime($newsletter['period_end']));
    }

    // ── Transmission ──────────────────────────────────────────────────────

    /**
     * Sujet et corps de l'email de transmission, à partir des modèles de la
     * configuration ; la référence est toujours ajoutée au sujet.
     *
     * @return array  subject, body
     */
    public static function build_review_email($cfg, $newsletter, $version, $deadline) {
        $vars = array(
            '{period}'     => self::period_label($newsletter),
            '{version}'    => (int) $version,
            '{date}'       => date('d/m/Y'),
            '{deadline}'   => date('d/m/Y', strtotime($deadline)),
            '{from_name}'  => ($cfg['newsletter_smtp_from_name'] ?? '') ?: ($cfg['newsletter_smtp_from'] ?? ''),
            '{draft_name}' => $newsletter['title'],
        );
        $subject = strtr(($cfg['newsletter_review_subject'] ?? '') ?: self::DEFAULT_SUBJECT, $vars);
        $body    = strtr(str_replace('\n', "\n", ($cfg['newsletter_review_body'] ?? '') ?: self::DEFAULT_BODY), $vars);
        return array(
            'subject' => $subject . ' ' . self::reference_tag($newsletter['id'], $version),
            'body'    => $body,
        );
    }

    /**
     * Date limite de réponse (AAAA-MM-JJ).
     */
    public static function deadline($delay_days, $from = NULL) {
        $days = max(0, (int) $delay_days);
        return date('Y-m-d', strtotime('+' . $days . ' days', $from ?: time()));
    }

    // ── Récupération des réponses ─────────────────────────────────────────

    /**
     * Trie un message reçu et l'enregistre en suggestion s'il est retenu.
     *
     * @param array            $newsletter
     * @param string           $raw        Message brut
     * @param string[]         $reviewers  Adresses autorisées (minuscules)
     * @param Newsletter_files $files      Pour conserver les pièces jointes
     * @return array  status ('retained'|'ignored'|'rejected'), reason, from, subject, review_id
     */
    public function process_message($newsletter, $raw, array $reviewers, $files) {
        $msg = Newsletter_mail_parser::parse($raw);
        $res = array('status' => 'ignored', 'reason' => '', 'from' => $msg['from_email'],
                     'subject' => $msg['subject'], 'review_id' => NULL);

        $ref = self::parse_reference($msg['subject']);
        if (!$ref || $ref['newsletter_id'] !== (int) $newsletter['id']) {
            $res['reason'] = 'sans rapport avec cette édition';
            return $res;
        }
        if ($msg['message_id'] !== '' && $this->CI->newsletter_reviews_model->message_exists($msg['message_id'])) {
            $res['reason'] = 'déjà récupérée';
            return $res;
        }
        if (!in_array($msg['from_email'], $reviewers, TRUE)) {
            $res['status'] = 'rejected';
            $res['reason'] = 'expéditeur non autorisé';
            return $res;
        }

        $content = Newsletter_mail_parser::strip_quoted($msg['text']);
        if ($content === '' && empty($msg['attachments'])) {
            $res['status'] = 'rejected';
            $res['reason'] = 'réponse vide';
            return $res;
        }

        $review_id = $this->CI->newsletter_reviews_model->add(array(
            'newsletter_id'   => $newsletter['id'],
            'iteration'       => $newsletter['review_iteration'],
            'submitted_via'   => 'email',
            'message_id'      => $msg['message_id'] !== '' ? $msg['message_id'] : NULL,
            'reviewer_email'  => $msg['from_email'],
            'reviewer_name'   => $msg['from_name'],
            'content'         => $content !== '' ? $content : '(pièces jointes seulement)',
            'has_attachments' => empty($msg['attachments']) ? 0 : 1,
        ));
        if (!$review_id) {
            $res['status'] = 'rejected';
            $res['reason'] = "erreur d'enregistrement";
            return $res;
        }

        foreach ($msg['attachments'] as $att) {
            if (strlen($att['content']) > self::MAX_ATTACHMENT) continue;
            $name = preg_replace('/[^\w.\-]+/u', '_', basename($att['name'])) ?: 'piece_jointe';
            $files->store_reply_file($review_id, $name, $att['content']);
        }

        $res['status'] = 'retained';
        $res['review_id'] = $review_id;
        return $res;
    }

    // ── Révision par l'agent IA ───────────────────────────────────────────

    /**
     * Demande de nouvelle version : consignes système et message utilisateur.
     *
     * @param string $draft        Brouillon courant (Markdown)
     * @param array  $suggestions  Suggestions acceptées ou modifiées
     * @param string $consignes    Consignes actives de l'agent (règles de rédaction)
     * @return array  system, user
     */
    public static function build_revision_prompt($draft, array $suggestions, $consignes = '') {
        $system = "Tu es le rédacteur de la lettre de veille sécurité et réglementation du club. "
            . "On te donne la version courante du brouillon (Markdown) et les modifications retenues par l'administrateur après relecture. "
            . "Produis la version suivante du brouillon en appliquant ces modifications, et seulement elles. "
            . "Conserve la structure, les liens, la section interne et les mentions entre crochets qui ne sont pas concernées. "
            . "N'invente aucune information ni aucune source : si une modification demande un élément que tu ne peux pas vérifier, "
            . "intègre-le tel que formulé et ajoute la mention [source à vérifier]. "
            . "Ajoute à la section interne une ligne listant les modifications appliquées. "
            . "Réponds uniquement avec le Markdown complet de la nouvelle version, sans commentaire ni bloc de code.";
        if (trim($consignes) !== '') {
            $system .= "\n\nConsignes de l'agent de veille (règles de rédaction à respecter) :\n\n" . $consignes;
        }

        $list = '';
        foreach (array_values($suggestions) as $i => $s) {
            $text = ($s['decision'] === Newsletter_reviews_model::DECISION_MODIFIED && $s['admin_text'] !== NULL)
                ? $s['admin_text'] : $s['content'];
            $label = ($s['kind'] === Newsletter_reviews_model::KIND_ARTICLE) ? 'Article à intégrer' : 'Remarque';
            $list .= '### ' . ($i + 1) . '. ' . $label . ' (' . ($s['reviewer_name'] ?: $s['reviewer_email']) . ")\n\n" . trim($text) . "\n\n";
        }

        $user = "## Modifications retenues\n\n" . $list . "## Brouillon courant\n\n" . $draft;
        return array('system' => $system, 'user' => $user);
    }

    /**
     * Retire un éventuel bloc de code englobant la réponse du modèle.
     */
    public static function clean_ai_output($text) {
        $text = trim($text);
        if (preg_match('/^```(?:markdown|md)?\s*\n(.*)\n```$/s', $text, $m)) {
            $text = trim($m[1]);
        }
        return $text . "\n";
    }
}
