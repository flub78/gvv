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
 *   pièces jointes) et enregistrement en remarques ;
 * - consignes de modification destinées à l'agent de rédaction, et
 *   remarques reportées à fournir pour une prochaine édition.
 *
 * L'envoi des emails et les appels réseau restent à la charge de l'appelant.
 *
 * @see doc/design_notes/veille_documentaire_design.md (Réponses et remarques, Fichiers échangés)
 */
class Newsletter_review_cycle {

    const DEFAULT_SUBJECT = '[Veille] Brouillon {period} (v{version}) — relecture souhaitée';
    const DEFAULT_BODY    = "Bonjour,\n\nVous trouverez en pièce jointe le brouillon v{version} de la lettre de veille pour la période : {period}.\n\nMerci de répondre à cet email avant le {deadline} avec vos remarques, corrections ou suggestions d'articles. Une réponse par sujet facilite le tri.\n\nSurtout, prévenez-nous si vous avez connaissance d'une information qui aurait logiquement dû figurer dans cette lettre et qui n'y est pas, en indiquant si possible sa source (lien, référence du document, émetteur).\n\nCordialement,\n{from_name}";

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
     * Trie un message reçu et l'enregistre en remarque s'il est retenu.
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

    // ── Consignes pour l'agent de rédaction ───────────────────────────────

    /**
     * Texte retenu d'une remarque : celui de l'administrateur si elle est modifiée.
     */
    public static function retained_text($remark) {
        return ($remark['decision'] === Newsletter_reviews_model::DECISION_MODIFIED && $remark['admin_text'] !== NULL)
            ? $remark['admin_text'] : $remark['content'];
    }

    /** Statuts transmis à l'agent de rédaction */
    const STATUS_APPLY    = 'APPLIQUER';
    const STATUS_POSTPONE = 'REPORTER';

    /**
     * Statut d'une remarque pour l'agent de rédaction.
     */
    public static function agent_status($remark) {
        return ($remark['decision'] === Newsletter_reviews_model::DECISION_POSTPONED) ? self::STATUS_POSTPONE : self::STATUS_APPLY;
    }

    /**
     * Liste Markdown de remarques avec leur statut et leurs images ; les
     * images sont ajoutées à $images [nom dans img/ => chemin].
     */
    private static function remarks_markdown(array $remarks, $files, array &$images) {
        $out = '';
        foreach (array_values($remarks) as $i => $r) {
            $who = $r['reviewer_name'] ? $r['reviewer_name'] . ' <' . $r['reviewer_email'] . '>' : $r['reviewer_email'];
            $modified = ($r['decision'] === Newsletter_reviews_model::DECISION_MODIFIED && $r['admin_text'] !== NULL);
            $out .= '### R' . ($i + 1) . '. ' . self::agent_status($r) . ' — ' . $who . "\n\n"
                  . '- Statut : **' . self::agent_status($r) . "**\n"
                  . '- Décision de l\'administrateur : ' . self::decision_label($r['decision']) . "\n\n";
            if ($modified) {
                $out .= "Texte à appliquer (rédigé par l'administrateur) :\n\n" . trim($r['admin_text']) . "\n\n"
                      . "Texte d'origine du relecteur, pour mémoire :\n\n" . self::quote(trim($r['content'])) . "\n\n";
            } else {
                $out .= trim($r['content']) . "\n\n";
            }
            foreach ($files->list_reply_files($r['id']) as $name) {
                if (!Newsletter_files::is_image($name)) continue;
                $img = 'remarque' . (int) $r['id'] . '_' . $name;
                $images[$img] = $files->reply_dir($r['id']) . $name;
                $out .= '![' . $name . '](img/' . $img . ")\n\n";
            }
        }
        return $out;
    }

    private static function decision_label($decision) {
        $labels = array(
            Newsletter_reviews_model::DECISION_ACCEPTED  => 'acceptée',
            Newsletter_reviews_model::DECISION_MODIFIED  => 'modifiée',
            Newsletter_reviews_model::DECISION_POSTPONED => 'reportée à une prochaine édition',
        );
        return $labels[$decision] ?? $decision;
    }

    private static function quote($text) {
        return '> ' . implode("\n> ", preg_split('/\R/', $text));
    }

    /**
     * Consignes de modification d'une version, pour l'agent de rédaction :
     * commentaires de l'administrateur (APPLIQUER), remarques acceptées ou
     * modifiées (APPLIQUER) et remarques reportées (REPORTER) du tour. Les
     * remarques rejetées n'y figurent pas.
     *
     * @param array            $newsletter
     * @param int              $version      Version de référence
     * @param string           $draft        Markdown de la version (pour situer les commentaires)
     * @param array            $annotations  Commentaires de la version
     * @param array            $remarks      Remarques acceptées, modifiées ou reportées du tour
     * @param Newsletter_files $files
     * @return array  markdown, images [nom dans img/ => chemin]
     */
    public static function build_directives($newsletter, $version, $draft, array $annotations, array $remarks, $files) {
        $images = array();
        $md = '# Consignes de modification — ' . $newsletter['title'] . "\n\n"
            . '- Édition : ' . $newsletter['title'] . ' (période ' . self::period_label($newsletter) . ")\n"
            . '- Version de référence : v' . (int) $version . ' ; version à produire : v' . ((int) $version + 1) . "\n"
            . '- Préparé le ' . date('d/m/Y H:i') . "\n\n"
            . "Produis la version suivante du brouillon en traitant chaque élément ci-dessous selon son statut, et seulement ces éléments :\n\n"
            . "- **" . self::STATUS_APPLY . "** : à intégrer dans la version v" . ((int) $version + 1) . ". Pour un commentaire de l'administrateur, l'insérer à l'endroit indiqué sous la forme demandée ; pour une remarque modifiée, appliquer le texte de l'administrateur ;\n"
            . "- **" . self::STATUS_POSTPONE . "** : ne pas intégrer dans cette édition ; à conserver pour une prochaine édition.\n\n"
            . "Les remarques refusées par l'administrateur ne figurent pas dans ce fichier. Les images citées sont dans le répertoire img/. "
            . "Mets à jour la mention « BROUILLON v" . ((int) $version + 1) . " ».\n\n";

        $md .= "## Commentaires de l'administrateur\n\n";
        if (!$annotations) {
            $md .= "Aucun.\n\n";
        }
        $blocks = Newsletter_files::split_blocks($draft);
        foreach (array_values($annotations) as $i => $a) {
            $anchor = $blocks[(int) $a['block_index']] ?? ($a['anchor_text'] ?? '');
            $anchor = mb_strimwidth(preg_replace('/\s+/u', ' ', trim($anchor)), 0, 200, '…');
            $md .= '### C' . ($i + 1) . '. ' . self::STATUS_APPLY . " — après le passage : « " . $anchor . " »\n\n"
                 . '- Statut : **' . self::STATUS_APPLY . "**\n\n"
                 . "Insérer :\n\n" . Newsletter_files::annotation_markdown($a['label'], $a['content']) . "\n\n";
        }

        $md .= "## Remarques des relecteurs\n\n";
        $md .= $remarks ? self::remarks_markdown($remarks, $files, $images) : "Aucune.\n\n";
        return array('markdown' => rtrim($md) . "\n", 'images' => $images);
    }

    /**
     * Remarques reportées, à fournir à l'agent pour une prochaine édition.
     *
     * @return array  markdown, images [nom dans img/ => chemin]
     */
    public static function build_postponed(array $remarks, $files) {
        $images = array();
        $md = "# Remarques reportées à une prochaine édition\n\n"
            . '- Préparé le ' . date('d/m/Y H:i') . "\n\n"
            . "Remarques des relecteurs que l'administrateur a reportées (statut " . self::STATUS_POSTPONE . "). "
            . "À examiner pour la version initiale de la prochaine édition ; les images citées sont dans le répertoire img/.\n\n";
        $md .= $remarks ? self::remarks_markdown($remarks, $files, $images) : "Aucune.\n";
        return array('markdown' => rtrim($md) . "\n", 'images' => $images);
    }
}
