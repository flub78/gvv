<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');
$CI->load->language('sections');

/**
 *	Accès base Sections
 *
 *  C'est un CRUD de base. Beaucoup de méthodes sont 
 *  implémentés dans Common_Model
 */
class Sections_model extends Common_Model {
    public $table = 'sections';
    protected $primary_key = 'id';

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_page($nb = 1000, $debut = 0) {
        $select = $this->select_columns('id, nom, description, acronyme, couleur, ordre_affichage');
        $this->db->order_by('ordre_affichage', 'asc');
        $this->db->order_by('nom', 'asc');
        $this->gvvmetadata->store_table("vue_sections", $select);
        return $select;
    }

    /**
     * Returns a list of sections ordered by ordre_affichage, then by nom
     */
    public function section_list() {
        $this->db->select('id, nom, description, acronyme, couleur, ordre_affichage');
        $this->db->order_by('ordre_affichage', 'asc');
        $this->db->order_by('nom', 'asc');
        $query = $this->db->get($this->table);
        return $query->result_array();
    }

    /**
     * Overrides Common_Model selector to order by ordre_affichage
     * Returns a hash for use in dropdown menus
     * 
     * @param array $where Selection criteria
     * @param string $order Sort order (asc or desc)
     * @param bool $filter_section Whether to filter by section
     * @return array Hash array for dropdown
     */
    public function selector($where = array(), $order = "asc", $filter_section = FALSE) {
        $key = $this->primary_key;

        $this->db->select($key)->from($this->table)->where($where);
        
        // Add ORDER BY for ordre_affichage, then nom
        $this->db->order_by('ordre_affichage', 'asc');
        $this->db->order_by('nom', 'asc');

        if ($filter_section && $this->section) {
            $this->db->where('club', $this->section_id);
        }
        
        $db_res = $this->db->get();
        $allkeys = $this->get_to_array($db_res);

        $result = array();
        foreach ($allkeys as $row) {
            $value = $row[$key];
            $result[$value] = $this->image($value);
        }
        
        // No need to sort again since ORDER BY is in the query
        return $result;
    }

    /**
     * Retourne une chaîne de caractère qui identifie une ligne de façon unique.
     * Cette chaîne est utilisé dans les affichages.
     * Par défaut elle retourne la valeur de la clé, mais elle est conçue pour être
     * surchargée.
     */
    public function image($key) {
        if ($key == "")
            return "";
        $vals = $this->get_by_id('id', $key);
        if (array_key_exists('nom', $vals)) {
            return $vals['nom'];
        } else {
            return "section inconnu $key";
        }
    }

    /**
     * Retourne un hash qui peut-être utilisé dans un menu drop-down
     * avec une entrée "Tous .
     * ."
     *
     * @param $where selection
     */
    public function selector_with_all($where = array(), $filter_section = false) {
        // Exclude section_id = 0 (cross-section) from UI selector - it's used internally by authorization system only
        $where['id !='] = 0;
        $result = $this->selector($where, $filter_section);
        $result[] = $this->lang->line("all_sections");
        return $result;
    }

	/**
	 * Returns the dropdown array for sections selector with null option but excluding cross-section 
	 * Used by procedures and other entities that need a null option for global scope
	 * @return array The dropdown array for the sections
	 */
	function section_selector_with_null()
	{
		$where = array();
		$where['id !='] = 0; // Exclude cross-section entry (ID=0) from UI
		$allkeys = $this->selector($where);
		
		$result = array();
		$result[''] = 'Globale (toutes sections)';
		foreach ($allkeys as $key => $value) {
			$result[$key] = $value;
		}
		return $result;
	}

    public function safe_count_all($table = 'sections') {
        return parent::safe_count_all($table);;
    }

}

/* End of file */