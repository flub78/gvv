<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Model for preparation_cards table
 *
 * Manages dashboard cards for “Météo & préparation des vols”.
 *
 * @package models
 * @see application/migrations/069_create_preparation_cards.php
 */
class Preparation_cards_model extends Common_Model {
    public $table = 'preparation_cards';
    protected $primary_key = 'id';

    /**
     * Normalize display order value
     * @param mixed $value
     * @return int|null
     */
    private function normalize_display_order($value) {
        if ($value === null || $value === '') {
            return null;
        }
        return (int)$value;
    }

    /**
     * Shift display_order for existing rows to make room at a given position
     * @param int $order
     * @param int|null $exclude_id
     */
    private function shift_orders_for_insert($order, $exclude_id = null) {
        $this->db->set('display_order', 'display_order + 1', false);
        $this->db->where('display_order >=', $order);
        if (!empty($exclude_id)) {
            $this->db->where($this->primary_key . ' !=', $exclude_id);
        }
        $this->db->update($this->table);
    }

    /**
     * Renumber all display_order values to remove holes
     */
    private function renumber_orders() {
        $rows = $this->db->select($this->primary_key)
            ->from($this->table)
            ->order_by('display_order', 'asc')
            ->order_by('title', 'asc')
            ->order_by($this->primary_key, 'asc')
            ->get()
            ->result_array();

        $order = 1;
        foreach ($rows as $row) {
            $this->db->where($this->primary_key, $row[$this->primary_key]);
            $this->db->update($this->table, array('display_order' => $order));
            $order++;
        }
    }

    /**
     * Create a card with display_order reindexing and timestamps
     * @param array $data
     * @return int|false
     */
    public function create($data) {
        $order = $this->normalize_display_order(isset($data['display_order']) ? $data['display_order'] : null);
        if ($order !== null) {
            $this->shift_orders_for_insert($order);
        }

        if (empty($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        $data['updated_at'] = date('Y-m-d H:i:s');

        $id = parent::create($data);
        if ($id) {
            $this->renumber_orders();
        }
        return $id;
    }

    /**
     * Update a card with display_order reindexing and timestamp
     * @param string $keyid
     * @param array $data
     * @param string $keyvalue
     */
    public function update($keyid, $data, $keyvalue = '') {
        if ($keyvalue == '') {
            $keyvalue = $data[$keyid];
        }

        if (array_key_exists('display_order', $data)) {
            $new_order = $this->normalize_display_order($data['display_order']);
            if ($new_order !== null) {
                $current = $this->get_by_id($keyid, $keyvalue);
                $current_order = isset($current['display_order']) ? (int)$current['display_order'] : null;
                if ($current_order !== $new_order) {
                    $this->shift_orders_for_insert($new_order, $keyvalue);
                }
            }
        }

        $data['updated_at'] = date('Y-m-d H:i:s');

        parent::update($keyid, $data, $keyvalue);
        $this->renumber_orders();
    }

    /**
     * Delete a card and renumber display_order
     * @param array $where
     */
    public function delete($where = array()) {
        parent::delete($where);
        $this->renumber_orders();
    }

    /**
     * Returns paginated list for display
     * @return array
     */
    public function select_page($per_page = 0, $premier = 0, $selection = []) {
        $this->db->select('preparation_cards.id, preparation_cards.title, preparation_cards.type,
            preparation_cards.html_fragment, preparation_cards.image_url, preparation_cards.link_url,
            preparation_cards.category, preparation_cards.display_order, preparation_cards.visible,
            preparation_cards.created_at, preparation_cards.updated_at');
        $this->db->from($this->table);

        if (!empty($selection)) {
            $this->db->where($selection);
        }

        $this->db->order_by('display_order', 'asc');
        $this->db->order_by('title', 'asc');
        $this->db->order_by('id', 'asc');

        if ($per_page > 0) {
            $this->db->limit($per_page, $premier);
        }

        $query = $this->db->get();
        $select = $this->get_to_array($query);

        $this->gvvmetadata->store_table('vue_preparation_cards', $select);
        return $select;
    }
}

/* End of file preparation_cards_model.php */
/* Location: ./application/models/preparation_cards_model.php */
