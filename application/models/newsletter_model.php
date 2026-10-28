<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Newsletter_model
 *
 * Gestion des éditions de la newsletter de veille documentaire.
 *
 * @see doc/design_notes/veille_documentaire_design.md
 */
class Newsletter_model extends CI_Model {

    public $table = 'newsletters';
    protected $primary_key = 'id';

    const STATUS_DRAFT      = 'draft';
    const STATUS_REVIEWING  = 'reviewing';
    const STATUS_VALIDATING = 'validating';
    const STATUS_APPROVED   = 'approved';
    const STATUS_PUBLISHED  = 'published';

    public function primary_key() { return $this->primary_key; }
    public function table()       { return $this->table; }

    private function current_user() {
        return $this->dx_auth->get_username();
    }

    private function now() {
        return date('Y-m-d H:i:s');
    }

    /**
     * Crée une nouvelle édition avec statut draft.
     *
     * @param array $data  title, period_start, period_end, draft_path (optionnel)
     * @return int|false   ID inséré ou FALSE
     */
    public function create($data) {
        $user = $this->current_user();
        $now  = $this->now();
        $row  = array(
            'title'            => $data['title'],
            'period_start'     => $data['period_start'],
            'period_end'       => $data['period_end'],
            'status'           => self::STATUS_DRAFT,
            'review_iteration' => 0,
            'draft_path'       => isset($data['draft_path']) ? $data['draft_path'] : NULL,
            'created_at'       => $now,
            'updated_at'       => $now,
            'created_by'       => $user,
            'updated_by'       => $user,
        );
        if ($this->db->insert($this->table, $row)) {
            return $this->db->insert_id();
        }
        log_message('error', 'newsletter_model::create — ' . $this->db->_error_message());
        return FALSE;
    }

    /**
     * Retourne une édition par son ID.
     */
    public function get($id) {
        return $this->db->get_where($this->table, array('id' => (int) $id))->row_array();
    }

    /**
     * Retourne la liste de toutes les éditions, les plus récentes en premier.
     */
    public function get_all() {
        return $this->db->order_by('period_start', 'DESC')->get($this->table)->result_array();
    }

    /**
     * Retourne les éditions publiées (archive membres).
     */
    public function get_latest_published() {
        return $this->db->where('status', self::STATUS_PUBLISHED)
                        ->order_by('published_at', 'DESC')
                        ->limit(1)
                        ->get($this->table)->row_array() ?: NULL;
    }

    public function get_published() {
        return $this->db->where('status', self::STATUS_PUBLISHED)
                        ->order_by('published_at', 'DESC')
                        ->get($this->table)->result_array();
    }

    /**
     * Met à jour les champs d'une édition.
     *
     * @param int   $id
     * @param array $data  Champs à modifier
     */
    public function update($id, $data) {
        $data['updated_at'] = $this->now();
        $data['updated_by'] = $this->current_user();
        $this->db->where('id', (int) $id)->update($this->table, $data);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Transition d'état avec contrôle de la séquence autorisée.
     *
     * @param int    $id
     * @param string $new_status  Constante STATUS_*
     * @param array  $extra       Champs additionnels (approved_by, pdf_path, …)
     * @return bool
     */
    public function transition($id, $new_status, $extra = array()) {
        $newsletter = $this->get($id);
        if (!$newsletter) return FALSE;

        // Cycle v1 : draft → reviewing → draft (nouvelle version) … draft → approved.
        // validating est réservé à l'approbation par un validateur distinct (évolution).
        $allowed = array(
            self::STATUS_DRAFT      => array(self::STATUS_REVIEWING, self::STATUS_APPROVED),
            self::STATUS_REVIEWING  => array(self::STATUS_REVIEWING, self::STATUS_VALIDATING, self::STATUS_DRAFT),
            self::STATUS_VALIDATING => array(self::STATUS_APPROVED),
            self::STATUS_APPROVED   => array(self::STATUS_PUBLISHED),
            self::STATUS_PUBLISHED  => array(),
        );
        $current = $newsletter['status'];
        if (!in_array($new_status, $allowed[$current], true)) {
            log_message('error', "newsletter_model::transition — transition {$current}→{$new_status} refusée pour id={$id}");
            return FALSE;
        }

        $data = array_merge($extra, array('status' => $new_status));

        if ($new_status === self::STATUS_REVIEWING) {
            $data['review_iteration'] = (int) $newsletter['review_iteration'] + 1;
        }
        if ($new_status === self::STATUS_APPROVED) {
            $data['approved_by'] = $this->current_user();
            $data['approved_at'] = $this->now();
        }
        if ($new_status === self::STATUS_PUBLISHED) {
            $data['published_at'] = $this->now();
        }

        return $this->update($id, $data);
    }

    /**
     * Enregistre un retour de relecteur ou de validateur.
     *
     * @param int    $newsletter_id
     * @param array  $data  reviewer_email, reviewer_name, content, submitted_via, has_attachments
     * @return int|false
     */
    public function add_review($newsletter_id, $data) {
        $newsletter = $this->get($newsletter_id);
        if (!$newsletter) return FALSE;
        $now  = $this->now();
        $user = $this->current_user();
        $row  = array(
            'newsletter_id'  => (int) $newsletter_id,
            'iteration'      => (int) $newsletter['review_iteration'],
            'submitted_via'  => isset($data['submitted_via']) ? $data['submitted_via'] : 'gvv',
            'reviewer_email' => $data['reviewer_email'],
            'reviewer_name'  => isset($data['reviewer_name']) ? $data['reviewer_name'] : NULL,
            'content'        => $data['content'],
            'has_attachments'=> isset($data['has_attachments']) ? (int) $data['has_attachments'] : 0,
            'created_at'     => $now,
            'updated_at'     => $now,
            'created_by'     => $user ?: $data['reviewer_email'],
            'updated_by'     => $user ?: $data['reviewer_email'],
        );
        if ($this->db->insert('newsletter_reviews', $row)) {
            return $this->db->insert_id();
        }
        log_message('error', 'newsletter_model::add_review — ' . $this->db->_error_message());
        return FALSE;
    }

    /**
     * Retourne les retours de l'itération courante d'une édition.
     */
    public function get_reviews($newsletter_id, $iteration = NULL) {
        $newsletter = $this->get($newsletter_id);
        if (!$newsletter) return array();
        $iter = ($iteration !== NULL) ? (int) $iteration : (int) $newsletter['review_iteration'];
        return $this->db->where('newsletter_id', (int) $newsletter_id)
                        ->where('iteration', $iter)
                        ->order_by('created_at', 'ASC')
                        ->get('newsletter_reviews')->result_array();
    }

    /**
     * Supprime une édition, quel que soit son état ; ses remarques,
     * commentaires et envois sont supprimés en cascade.
     */
    public function delete($id) {
        $this->db->where('id', (int) $id)->delete($this->table);
        return $this->db->affected_rows() > 0;
    }
}
