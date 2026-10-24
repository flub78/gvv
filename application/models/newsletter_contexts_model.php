<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Newsletter_contexts_model
 *
 * Versions successives des consignes de l'agent IA de veille.
 * Une seule version est active ; l'historique n'est jamais réécrit :
 * restaurer une version crée une nouvelle version au même contenu.
 *
 * @see doc/design_notes/veille_documentaire_design.md
 */
class Newsletter_contexts_model extends CI_Model {

    public $table = 'newsletter_contexts';
    protected $primary_key = 'id';

    public function primary_key() { return $this->primary_key; }
    public function table()       { return $this->table; }

    private function current_user() {
        return $this->dx_auth->get_username();
    }

    private function now() {
        return date('Y-m-d H:i:s');
    }

    /**
     * Enregistre de nouvelles consignes et les rend actives.
     *
     * @param string $content  Consignes (Markdown)
     * @param string $comment  Motif de la modification
     * @return int|false       ID de la version créée
     */
    public function create_version($content, $comment = '') {
        $row = $this->db->select_max('version', 'v')->get($this->table)->row_array();
        $version = (int) ($row['v'] ?? 0) + 1;
        $user = $this->current_user();
        $now  = $this->now();

        $this->db->trans_start();
        $this->db->update($this->table, array('is_active' => 0), array('is_active' => 1));
        $this->db->insert($this->table, array(
            'version'    => $version,
            'content'    => $content,
            'comment'    => ($comment !== '') ? mb_substr($comment, 0, 255) : NULL,
            'is_active'  => 1,
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => $user,
            'updated_by' => $user,
        ));
        $id = $this->db->insert_id();
        $this->db->trans_complete();

        if (!$this->db->trans_status()) {
            log_message('error', 'newsletter_contexts_model::create_version — ' . $this->db->_error_message());
            return FALSE;
        }
        return $id;
    }

    /**
     * Retourne la version active, ou NULL s'il n'y a encore aucune consigne.
     */
    public function get_active() {
        return $this->db->get_where($this->table, array('is_active' => 1))->row_array() ?: NULL;
    }

    public function get($id) {
        return $this->db->get_where($this->table, array('id' => (int) $id))->row_array() ?: NULL;
    }

    /**
     * Liste des versions, la plus récente en premier (sans le contenu).
     */
    public function get_all() {
        return $this->db->select('id, version, comment, is_active, created_at, created_by, CHAR_LENGTH(content) AS length')
                        ->order_by('version', 'DESC')
                        ->get($this->table)->result_array();
    }

    /**
     * Restaure une version : crée une nouvelle version active au même contenu.
     *
     * @return int|false  ID de la nouvelle version
     */
    public function restore($id) {
        $old = $this->get($id);
        if (!$old) return FALSE;
        return $this->create_version($old['content'], 'Restauration de la version ' . $old['version']);
    }
}
