<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Newsletter_runs_model
 *
 * Exécutions de l'agent IA de veille : origine (bureau, calendrier,
 * lancement manuel), statut, consignes utilisées, coût, paquet archivé
 * et newsletter produite.
 *
 * @see doc/design_notes/veille_documentaire_design.md
 */
class Newsletter_runs_model extends CI_Model {

    public $table = 'newsletter_runs';
    protected $primary_key = 'id';

    const ORIGIN_DESKTOP  = 'desktop';
    const ORIGIN_SCHEDULE = 'schedule';
    const ORIGIN_MANUAL   = 'manual';

    const STATUS_REQUESTED = 'requested';
    const STATUS_RUNNING   = 'running';
    const STATUS_SUCCEEDED = 'succeeded';
    const STATUS_FAILED    = 'failed';

    public function primary_key() { return $this->primary_key; }
    public function table()       { return $this->table; }

    private function current_user() {
        return $this->dx_auth->get_username();
    }

    private function now() {
        return date('Y-m-d H:i:s');
    }

    /**
     * Identifiant lisible d'exécution, unique : RUN-AAAAMMJJ-HHMMSS[-n].
     */
    public function generate_run_id($timestamp = NULL) {
        $base = 'RUN-' . date('Ymd-His', $timestamp ?: time());
        $run_id = $base;
        $n = 1;
        while ($this->get_by_run_id($run_id)) {
            $run_id = $base . '-' . (++$n);
        }
        return $run_id;
    }

    /**
     * Crée une exécution.
     *
     * @param array $data  origin, status, context_id, newsletter_id, started_at,
     *                     finished_at, model, cost_usd, package_path, error, run_id (optionnel)
     * @return int|false
     */
    public function create($data) {
        $user = $this->current_user();
        $now  = $this->now();
        $row  = array(
            'run_id'        => $data['run_id'] ?? $this->generate_run_id(),
            'origin'        => $data['origin'],
            'status'        => $data['status'] ?? self::STATUS_REQUESTED,
            'context_id'    => $data['context_id'] ?? NULL,
            'newsletter_id' => $data['newsletter_id'] ?? NULL,
            'started_at'    => $data['started_at'] ?? NULL,
            'finished_at'   => $data['finished_at'] ?? NULL,
            'model'         => $data['model'] ?? NULL,
            'cost_usd'      => (isset($data['cost_usd']) && $data['cost_usd'] !== '') ? $data['cost_usd'] : NULL,
            'package_path'  => $data['package_path'] ?? NULL,
            'error'         => $data['error'] ?? NULL,
            'created_at'    => $now,
            'updated_at'    => $now,
            'created_by'    => $user,
            'updated_by'    => $user,
        );
        if ($this->db->insert($this->table, $row)) {
            return $this->db->insert_id();
        }
        log_message('error', 'newsletter_runs_model::create — ' . $this->db->_error_message());
        return FALSE;
    }

    public function get($id) {
        return $this->db->get_where($this->table, array('id' => (int) $id))->row_array() ?: NULL;
    }

    public function get_by_run_id($run_id) {
        return $this->db->get_where($this->table, array('run_id' => $run_id))->row_array() ?: NULL;
    }

    public function update($id, $data) {
        $data['updated_at'] = $this->now();
        $data['updated_by'] = $this->current_user();
        $this->db->where('id', (int) $id)->update($this->table, $data);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Exécutions avec la version des consignes et le titre de la newsletter,
     * les plus récentes en premier.
     */
    public function get_all() {
        return $this->db->select('r.*, c.version AS context_version, n.title AS newsletter_title, n.status AS newsletter_status')
                        ->from($this->table . ' r')
                        ->join('newsletter_contexts c', 'c.id = r.context_id', 'left')
                        ->join('newsletters n', 'n.id = r.newsletter_id', 'left')
                        ->order_by('r.id', 'DESC')
                        ->get()->result_array();
    }

    /**
     * Exécution qui a produit une newsletter (la plus récente).
     */
    public function get_by_newsletter($newsletter_id) {
        return $this->db->where('newsletter_id', (int) $newsletter_id)
                        ->order_by('id', 'DESC')
                        ->get($this->table)->row_array() ?: NULL;
    }

    /**
     * Dernière exécution réussie, dont l'inventaire est l'inventaire courant.
     */
    public function get_last_succeeded() {
        return $this->db->where('status', self::STATUS_SUCCEEDED)
                        ->order_by('id', 'DESC')
                        ->get($this->table)->row_array() ?: NULL;
    }
}
