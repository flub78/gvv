<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Model for acceptance_signatures table
 *
 * Manages external signatures (tactile and paper uploads) linked to acceptance records.
 *
 * @package models
 * @see application/migrations/068_acceptance_system.php
 */
class Acceptance_signatures_model extends Common_Model {
    public $table = 'acceptance_signatures';
    protected $primary_key = 'id';

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();
    }

    /**
     * Returns paginated list for display
     * @param int $per_page Number of items per page
     * @param int $premier Offset
     * @param array $selection Filter criteria
     * @return array
     */
    public function select_page($per_page = 0, $premier = 0, $selection = []) {
        $this->db->select('acceptance_signatures.*,
            acceptance_items.title as item_title,
            acceptance_records.user_login, acceptance_records.external_name,
            acceptance_records.status as record_status');
        $this->db->from($this->table);
        $this->db->join('acceptance_records', 'acceptance_signatures.record_id = acceptance_records.id', 'left');
        $this->db->join('acceptance_items', 'acceptance_records.item_id = acceptance_items.id', 'left');

        if (!empty($selection)) {
            $this->db->where($selection);
        }

        $this->db->order_by('acceptance_signatures.signed_at', 'desc');

        $query = $this->db->get();
        $select = $this->get_to_array($query);

        $this->gvvmetadata->store_table("vue_acceptance_signatures", $select);
        return $select;
    }

    /**
     * Get signatures for a specific record
     * @param int $record_id Record ID
     * @return array
     */
    public function get_by_record($record_id) {
        $this->db->where('record_id', $record_id);
        $this->db->order_by('signed_at', 'desc');
        $query = $this->db->get($this->table);
        return $this->get_to_array($query);
    }

    /**
     * Create a tactile signature record
     * @param int $record_id Acceptance record ID
     * @param array $signer_data Signer info (first_name, last_name, quality, beneficiary)
     * @param string $signature_base64 Base64 encoded signature image data
     * @return int|false Insert ID or false
     */
    public function create_tactile($record_id, $signer_data, $signature_base64) {
        $data = array(
            'record_id' => $record_id,
            'signer_first_name' => $signer_data['first_name'],
            'signer_last_name' => $signer_data['last_name'],
            'signature_type' => 'tactile',
            'signature_data' => $signature_base64,
            'signed_at' => date('Y-m-d H:i:s')
        );

        // Optional fields for parental authorization
        if (isset($signer_data['quality'])) {
            $data['signer_quality'] = $signer_data['quality'];
        }
        if (isset($signer_data['beneficiary_first_name'])) {
            $data['beneficiary_first_name'] = $signer_data['beneficiary_first_name'];
        }
        if (isset($signer_data['beneficiary_last_name'])) {
            $data['beneficiary_last_name'] = $signer_data['beneficiary_last_name'];
        }

        return $this->create($data);
    }

    /**
     * Create an upload signature record
     * @param int $record_id Acceptance record ID
     * @param array $signer_data Signer info
     * @param array $file_info File info (path, original_filename, file_size, mime_type)
     * @return int|false Insert ID or false
     */
    public function create_upload($record_id, $signer_data, $file_info) {
        $data = array(
            'record_id' => $record_id,
            'signer_first_name' => $signer_data['first_name'],
            'signer_last_name' => $signer_data['last_name'],
            'signature_type' => 'upload',
            'file_path' => $file_info['path'],
            'original_filename' => $file_info['original_filename'],
            'file_size' => $file_info['file_size'],
            'mime_type' => $file_info['mime_type'],
            'signed_at' => date('Y-m-d H:i:s'),
            'pilot_attestation' => isset($signer_data['pilot_attestation']) ? 1 : 0
        );

        if (isset($signer_data['quality'])) {
            $data['signer_quality'] = $signer_data['quality'];
        }
        if (isset($signer_data['beneficiary_first_name'])) {
            $data['beneficiary_first_name'] = $signer_data['beneficiary_first_name'];
        }
        if (isset($signer_data['beneficiary_last_name'])) {
            $data['beneficiary_last_name'] = $signer_data['beneficiary_last_name'];
        }

        return $this->create($data);
    }

    /**
     * Human-readable identifier
     * @param mixed $key Primary key value
     * @return string
     */
    public function image($key) {
        if ($key == "") return "";

        $vals = $this->get_by_id('id', $key);
        if ($vals) {
            return trim($vals['signer_first_name'] . ' ' . $vals['signer_last_name']);
        }
        return "signature inconnue $key";
    }
}

/* End of file acceptance_signatures_model.php */
/* Location: ./application/models/acceptance_signatures_model.php */
