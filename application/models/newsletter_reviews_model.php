<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Newsletter_reviews_model
 *
 * Remarques issues des réponses des relecteurs et décision de
 * l'administrateur (acceptée, modifiée, rejetée, reportée). Une remarque
 * reportée reste rattachée à son édition d'origine (elle figure dans les
 * consignes de modification de celle-ci, statut REPORTER) et est proposée
 * à la reprise dans les autres éditions.
 *
 * @see doc/design_notes/veille_documentaire_design.md
 */
class Newsletter_reviews_model extends CI_Model {

    public $table = 'newsletter_reviews';
    protected $primary_key = 'id';

    const DECISION_PENDING   = 'en_attente';
    const DECISION_ACCEPTED  = 'acceptee';
    const DECISION_MODIFIED  = 'modifiee';
    const DECISION_REJECTED  = 'rejetee';
    const DECISION_POSTPONED = 'reportee';

    public function primary_key() { return $this->primary_key; }
    public function table()       { return $this->table; }

    public static function decisions() {
        return array(self::DECISION_PENDING, self::DECISION_ACCEPTED, self::DECISION_MODIFIED,
                     self::DECISION_REJECTED, self::DECISION_POSTPONED);
    }

    private function current_user() {
        return $this->dx_auth->get_username();
    }

    private function now() {
        return date('Y-m-d H:i:s');
    }

    /**
     * Indique si un message a déjà été enregistré (anti-doublon).
     */
    public function message_exists($message_id) {
        if ($message_id === NULL || $message_id === '') return FALSE;
        return $this->db->where('message_id', $message_id)->count_all_results($this->table) > 0;
    }

    /**
     * Enregistre une remarque en attente de décision.
     *
     * @param array $data  newsletter_id, iteration, reviewer_email, reviewer_name,
     *                     content, message_id, submitted_via, has_attachments
     * @return int|false   ID créé, FALSE si erreur ou message déjà enregistré
     */
    public function add($data) {
        $message_id = $data['message_id'] ?? NULL;
        if ($this->message_exists($message_id)) {
            return FALSE;
        }
        $user = $this->current_user();
        $now  = $this->now();
        $row  = array(
            'newsletter_id'   => isset($data['newsletter_id']) ? (int) $data['newsletter_id'] : NULL,
            'iteration'       => (int) ($data['iteration'] ?? 0),
            'submitted_via'   => $data['submitted_via'] ?? 'email',
            'message_id'      => ($message_id !== '') ? $message_id : NULL,
            'reviewer_email'  => $data['reviewer_email'],
            'reviewer_name'   => $data['reviewer_name'] ?? NULL,
            'content'         => $data['content'],
            'decision'        => self::DECISION_PENDING,
            'has_attachments' => (int) ($data['has_attachments'] ?? 0),
            'created_at'      => $now,
            'updated_at'      => $now,
            'created_by'      => $user ?: mb_substr($data['reviewer_email'], 0, 25),
            'updated_by'      => $user ?: mb_substr($data['reviewer_email'], 0, 25),
        );
        if ($this->db->insert($this->table, $row)) {
            return $this->db->insert_id();
        }
        log_message('error', 'newsletter_reviews_model::add — ' . $this->db->_error_message());
        return FALSE;
    }

    public function get($id) {
        return $this->db->get_where($this->table, array('id' => (int) $id))->row_array() ?: NULL;
    }

    /**
     * Remarques d'une édition, dans l'ordre d'arrivée.
     *
     * @param int      $newsletter_id
     * @param int|null $iteration  Tour de relecture ; toutes si NULL
     */
    public function get_for_newsletter($newsletter_id, $iteration = NULL) {
        $this->db->where('newsletter_id', (int) $newsletter_id);
        if ($iteration !== NULL) {
            $this->db->where('iteration', (int) $iteration);
        }
        return $this->db->order_by('created_at', 'ASC')->order_by('id', 'ASC')
                        ->get($this->table)->result_array();
    }

    /**
     * Remarques reportées, en attente d'une édition future.
     *
     * @param int|null $except_newsletter_id  Édition à exclure (celle qui les a reportées)
     */
    public function get_postponed($except_newsletter_id = NULL) {
        $this->db->where('decision', self::DECISION_POSTPONED);
        if ($except_newsletter_id !== NULL) {
            $this->db->where('(newsletter_id IS NULL OR newsletter_id != ' . (int) $except_newsletter_id . ')', NULL, FALSE);
        }
        return $this->db->order_by('created_at', 'ASC')->get($this->table)->result_array();
    }

    /**
     * Remarques reportées pendant un tour de relecture d'une édition.
     */
    public function get_postponed_in_round($newsletter_id, $iteration) {
        return $this->db->where('newsletter_id', (int) $newsletter_id)
                        ->where('iteration', (int) $iteration)
                        ->where('decision', self::DECISION_POSTPONED)
                        ->order_by('id', 'ASC')
                        ->get($this->table)->result_array();
    }

    /**
     * Remarques à transmettre à l'agent IA dans les consignes de modification :
     * acceptées ou modifiées pendant le tour de relecture courant (les tours
     * précédents ont déjà été intégrés dans une version antérieure).
     */
    public function get_accepted($newsletter_id, $iteration) {
        return $this->db->where('newsletter_id', (int) $newsletter_id)
                        ->where('iteration', (int) $iteration)
                        ->where_in('decision', array(self::DECISION_ACCEPTED, self::DECISION_MODIFIED))
                        ->order_by('id', 'ASC')
                        ->get($this->table)->result_array();
    }

    /**
     * Nombre de remarques encore sans décision pour une édition.
     */
    public function count_pending($newsletter_id) {
        return $this->db->where('newsletter_id', (int) $newsletter_id)
                        ->where('decision', self::DECISION_PENDING)
                        ->count_all_results($this->table);
    }

    /**
     * Enregistre la décision de l'administrateur.
     *
     * Une modification exige un texte.
     *
     * @return string|true  TRUE, ou un code d'erreur
     */
    public function decide($id, $decision, $admin_text = NULL) {
        $rem = $this->get($id);
        if (!$rem) return 'not_found';
        if (!in_array($decision, self::decisions(), TRUE)) return 'invalid_decision';

        $admin_text = ($admin_text !== NULL) ? trim($admin_text) : NULL;
        if ($decision === self::DECISION_MODIFIED && ($admin_text === NULL || $admin_text === '')) return 'modified_text_required';

        $data = array(
            'decision'   => $decision,
            'admin_text' => ($decision === self::DECISION_MODIFIED) ? $admin_text : NULL,
            'decided_by' => ($decision === self::DECISION_PENDING) ? NULL : $this->current_user(),
            'decided_at' => ($decision === self::DECISION_PENDING) ? NULL : $this->now(),
            'updated_at' => $this->now(),
            'updated_by' => $this->current_user(),
        );
        $this->db->where('id', (int) $id)->update($this->table, $data);
        return TRUE;
    }

    /**
     * Reprend une remarque reportée dans une autre édition, en attente de décision.
     */
    public function attach($id, $newsletter_id, $iteration) {
        $rem = $this->get($id);
        if (!$rem || $rem['decision'] !== self::DECISION_POSTPONED
            || (int) $rem['newsletter_id'] === (int) $newsletter_id) return FALSE;
        $this->db->where('id', (int) $id)->update($this->table, array(
            'newsletter_id' => (int) $newsletter_id,
            'iteration'     => (int) $iteration,
            'decision'      => self::DECISION_PENDING,
            'decided_by'    => NULL,
            'decided_at'    => NULL,
            'updated_at'    => $this->now(),
            'updated_by'    => $this->current_user(),
        ));
        return $this->db->affected_rows() > 0;
    }
}
