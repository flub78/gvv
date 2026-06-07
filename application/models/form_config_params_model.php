<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Form config params model
 *
 * Manages key/value configuration parameters for the forms module.
 * Scope: NULL club_id = global; integer club_id = section-specific.
 * Resolution: section-level overrides global for the same param_key.
 */
class Form_config_params_model extends CI_Model {

    public $table = 'form_config_params';

    public function __construct() {
        parent::__construct();
    }

    /**
     * Resolve a param_key value: section-specific first, then global fallback.
     *
     * @param  string   $param_key
     * @param  int|null $club_id   Active section id, or null for global only
     * @return string|null
     */
    public function resolve($param_key, $club_id = null) {
        if ($club_id) {
            $row = $this->db
                ->where('param_key', $param_key)
                ->where('club_id', (int) $club_id)
                ->get($this->table)
                ->row_array();
            if ($row && $row['param_value'] !== '' && $row['param_value'] !== null) {
                return $row['param_value'];
            }
        }

        $row = $this->db
            ->where('param_key', $param_key)
            ->where('club_id IS NULL', null, false)
            ->get($this->table)
            ->row_array();

        return ($row && $row['param_value'] !== '' && $row['param_value'] !== null) ? $row['param_value'] : null;
    }

    /**
     * List all params, optionally filtered by club_id (NULL = global only).
     */
    public function list_params($club_id = null, $global_too = true) {
        if ($club_id !== null && $global_too) {
            $this->db->where(
                '(club_id = ' . (int) $club_id . ' OR club_id IS NULL)',
                null, false
            );
        } elseif ($club_id !== null) {
            $this->db->where('club_id', (int) $club_id);
        } else {
            $this->db->where('club_id IS NULL', null, false);
        }

        return $this->db
            ->order_by('param_key', 'ASC')
            ->get($this->table)
            ->result_array();
    }

    public function get_by_id($id) {
        $row = $this->db
            ->where('id', (int) $id)
            ->get($this->table)
            ->row_array();
        return $row ?: false;
    }

    public function create(array $data, $by = null) {
        $now = date('Y-m-d H:i:s');
        $club_id = (isset($data['club_id']) && $data['club_id'] !== '' && $data['club_id'] !== null)
            ? (int) $data['club_id'] : null;

        $row = array(
            'club_id'           => $club_id,
            'param_key'         => trim($data['param_key']),
            'param_value'       => isset($data['param_value']) ? $data['param_value'] : '',
            'param_label'       => trim($data['param_label']),
            'param_description' => isset($data['param_description']) ? trim($data['param_description']) : null,
            'created_at'        => $now,
            'updated_at'        => $now,
            'created_by'        => $by,
            'updated_by'        => $by,
        );

        $this->db->insert($this->table, $row);
        return $this->db->insert_id() ?: false;
    }

    public function update($id, array $data, $by = null) {
        $club_id = (isset($data['club_id']) && $data['club_id'] !== '' && $data['club_id'] !== null)
            ? (int) $data['club_id'] : null;

        $row = array(
            'club_id'           => $club_id,
            'param_value'       => isset($data['param_value']) ? $data['param_value'] : '',
            'param_label'       => trim($data['param_label']),
            'param_description' => isset($data['param_description']) ? trim($data['param_description']) : null,
            'updated_at'        => date('Y-m-d H:i:s'),
            'updated_by'        => $by,
        );

        $this->db->where('id', (int) $id)->update($this->table, $row);
        return $this->db->affected_rows() >= 0;
    }

    public function delete($id) {
        $this->db->where('id', (int) $id)->delete($this->table);
        return $this->db->affected_rows() > 0;
    }

    public function key_exists($param_key, $club_id = null, $exclude_id = 0) {
        $this->db->where('param_key', $param_key);
        if ($club_id !== null) {
            $this->db->where('club_id', (int) $club_id);
        } else {
            $this->db->where('club_id IS NULL', null, false);
        }
        if ($exclude_id) {
            $this->db->where('id !=', (int) $exclude_id);
        }
        return $this->db->count_all_results($this->table) > 0;
    }
}
