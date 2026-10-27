<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Newsletter_annotations_model
 *
 * Commentaires insérés par l'administrateur sous un bloc d'une version du
 * brouillon (« Commentaire du DTO : … »). Ils figurent dans le brouillon
 * annoté et dans les consignes de modification destinées à l'agent.
 *
 * @see doc/design_notes/veille_documentaire_design.md (Annotations)
 */
class Newsletter_annotations_model extends CI_Model {

    public $table = 'newsletter_annotations';
    protected $primary_key = 'id';

    public function primary_key() { return $this->primary_key; }
    public function table()       { return $this->table; }

    /**
     * @param array $data  newsletter_id, version, block_index, anchor_text, label, content
     * @return int|false   ID créé
     */
    public function add($data) {
        $user = $this->dx_auth->get_username();
        $now  = date('Y-m-d H:i:s');
        $row  = array(
            'newsletter_id' => (int) $data['newsletter_id'],
            'version'       => (int) $data['version'],
            'block_index'   => (int) $data['block_index'],
            'anchor_text'   => isset($data['anchor_text']) ? mb_substr($data['anchor_text'], 0, 255) : NULL,
            'label'         => mb_substr(trim($data['label']), 0, 100),
            'content'       => trim($data['content']),
            'created_at'    => $now,
            'updated_at'    => $now,
            'created_by'    => $user,
            'updated_by'    => $user,
        );
        if ($this->db->insert($this->table, $row)) {
            return $this->db->insert_id();
        }
        log_message('error', 'newsletter_annotations_model::add — ' . $this->db->_error_message());
        return FALSE;
    }

    public function get($id) {
        return $this->db->get_where($this->table, array('id' => (int) $id))->row_array() ?: NULL;
    }

    /**
     * Commentaires d'une version, dans l'ordre du document.
     */
    public function get_for_version($newsletter_id, $version) {
        return $this->db->where('newsletter_id', (int) $newsletter_id)
                        ->where('version', (int) $version)
                        ->order_by('block_index', 'ASC')->order_by('id', 'ASC')
                        ->get($this->table)->result_array();
    }

    public function delete($id) {
        $this->db->where('id', (int) $id)->delete($this->table);
        return $this->db->affected_rows() > 0;
    }
}
