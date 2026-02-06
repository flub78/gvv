<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Model for document_types table
 *
 * Manages document type definitions and their rules (required, scope, expiration, etc.)
 *
 * @package models
 * @see application/migrations/067_archived_documents.php
 */
class Document_types_model extends Common_Model {
    public $table = 'document_types';
    protected $primary_key = 'id';

    /**
     * Returns paginated list for display
     * @return array
     */
    public function select_page($per_page = 0, $premier = 0, $selection = []) {
        $this->db->select('document_types.id, document_types.code, document_types.name,
            document_types.scope, document_types.required, document_types.has_expiration,
            document_types.alert_days_before,
            document_types.active, document_types.display_order,
            sections.nom as section_name');
        $this->db->from($this->table);
        $this->db->join('sections', 'document_types.section_id = sections.id', 'left');

        if (!empty($selection)) {
            $this->db->where($selection);
        }

        $this->db->order_by('display_order', 'asc');
        $this->db->order_by('name', 'asc');

        $query = $this->db->get();
        $select = $this->get_to_array($query);

        $this->gvvmetadata->store_table("vue_document_types", $select);
        return $select;
    }

    /**
     * Returns all active document types
     * @param string|null $scope Filter by scope (pilot, section, club)
     * @param int|null $section_id Filter by section (NULL = global types)
     * @return array
     */
    public function get_active_types($scope = null, $section_id = null) {
        $this->db->where('active', 1);

        if ($scope !== null) {
            $this->db->where('scope', $scope);
        }

        // Get types for specific section OR global types (section_id IS NULL)
        if ($section_id !== null) {
            $this->db->where("(section_id = " . $this->db->escape($section_id) . " OR section_id IS NULL)", null, false);
        }

        $this->db->order_by('display_order', 'asc');
        $this->db->order_by('name', 'asc');

        return $this->db->get($this->table)->result_array();
    }

    /**
     * Returns required document types for pilots
     * @param int|null $section_id Filter by section
     * @return array
     */
    public function get_required_pilot_types($section_id = null) {
        $this->db->where('active', 1);
        $this->db->where('scope', 'pilot');
        $this->db->where('required', 1);

        if ($section_id !== null) {
            $this->db->where("(section_id = " . $this->db->escape($section_id) . " OR section_id IS NULL)", null, false);
        }

        $this->db->order_by('display_order', 'asc');

        return $this->db->get($this->table)->result_array();
    }

    /**
     * Returns document type by code
     * @param string $code Document type code
     * @param int|null $section_id Section ID (NULL for global types)
     * @return array|null
     */
    public function get_by_code($code, $section_id = null) {
        $this->db->where('code', $code);

        if ($section_id === null) {
            $this->db->where('section_id IS NULL', null, false);
        } else {
            $this->db->where('section_id', $section_id);
        }

        $query = $this->db->get($this->table);
        return $query->row_array();
    }

    /**
     * Returns a human-readable representation of a document type
     * @param mixed $key Document type ID
     * @return string
     */
    public function image($key) {
        if ($key == "")
            return "";
        $vals = $this->get_by_id('id', $key);
        if ($vals && array_key_exists('name', $vals)) {
            return $vals['name'];
        } else {
            return "type inconnu $key";
        }
    }

    /**
     * Returns selector for dropdown menus
     * @param string|null $scope Filter by scope
     * @return array
     */
    public function type_selector($scope = null) {
        $types = $this->get_active_types($scope);
        $result = array();
        foreach ($types as $type) {
            $result[$type['id']] = $type['name'];
        }
        return $result;
    }

    /**
     * Returns selector with empty option for dropdown menus
     * @param string|null $scope Filter by scope
     * @return array
     */
    public function type_selector_with_null($scope = null) {
        $result = array('' => '');
        $types = $this->get_active_types($scope);
        foreach ($types as $type) {
            $result[$type['id']] = $type['name'];
        }
        return $result;
    }

    /**
     * Returns scope options for dropdown
     * @return array
     */
    public function scope_selector() {
        return array(
            'pilot' => 'Pilote',
            'section' => 'Section',
            'club' => 'Club'
        );
    }
}

/* End of file document_types_model.php */
/* Location: ./application/models/document_types_model.php */
