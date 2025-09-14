<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');
$CI->load->model('avions_model');
$CI->load->model('planeurs_model');
$CI->load->model('tarifs_model');

/**
 *	Accès base vols_decouverte
 *
 *  C'est un CRUD de base, la seule chose que fait cette classe
 *  est de définir le nom de la table. Tous les méthodes sont 
 *  implémentés dans Common_Model
 */
class Vols_decouverte_model extends Common_Model {
    public $table = 'vols_decouverte';
    protected $primary_key = 'id';

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_page($nb = 1000, $debut = 0) {
        $to_select = 'id, date_vente, club, product, beneficiaire, de_la_part, beneficiaire_email, date_vol, pilote, airplane_immat, urgence, cancelled, paiement, participation, prix';
        
        // Build the where clause based on active filters
        $where_conditions = [];
        $db = $this->db;
        
        // Section filter
        if ($this->section) {
            $where_conditions[] = ['field' => 'club', 'value' => $this->section_id];
        }
        
        // Apply session filters
        $filter_active = $this->session->userdata('vd_filter_active');
        if ($filter_active) {
            // Date/Year filtering with precedence logic
            $use_date_range = $this->session->userdata('vd_use_date_range');
            $startDate = $this->session->userdata('vd_startDate');
            $endDate = $this->session->userdata('vd_endDate');
            $year = $this->session->userdata('vd_year');
            
            if ($use_date_range && $startDate && $endDate) {
                // Use date range (takes precedence)
                $where_conditions[] = ['field' => 'date_vente >=', 'value' => $startDate];
                $where_conditions[] = ['field' => 'date_vente <=', 'value' => $endDate];
            } elseif ($year && $year != date('Y')) {
                // Use year filter
                $start_year = $year . '-01-01';
                $end_year = $year . '-12-31';
                $where_conditions[] = ['field' => 'date_vente >=', 'value' => $start_year];
                $where_conditions[] = ['field' => 'date_vente <=', 'value' => $end_year];
            }
            
            // Filter type conditions
            $filter_type = $this->session->userdata('vd_filter_type');
            if ($filter_type && $filter_type != 'all') {
                switch ($filter_type) {
                    case 'done':
                        $where_conditions[] = ['field' => 'date_vol IS NOT NULL', 'value' => null, 'no_escape' => true];
                        $where_conditions[] = ['field' => 'cancelled', 'value' => 0];
                        break;
                    case 'todo':
                        $where_conditions[] = ['field' => 'date_vol IS NULL', 'value' => null, 'no_escape' => true];
                        $where_conditions[] = ['field' => 'cancelled', 'value' => 0];
                        // Not expired
                        $one_year_ago = date('Y-m-d', strtotime('-1 year'));
                        $where_conditions[] = ['field' => 'date_vente >=', 'value' => $one_year_ago];
                        break;
                    case 'cancelled':
                        $where_conditions[] = ['field' => 'cancelled', 'value' => 1];
                        break;
                    case 'expired':
                        $where_conditions[] = ['field' => 'date_vol IS NULL', 'value' => null, 'no_escape' => true];
                        $where_conditions[] = ['field' => 'cancelled', 'value' => 0];
                        // Expired (older than 1 year)
                        $one_year_ago = date('Y-m-d', strtotime('-1 year'));
                        $where_conditions[] = ['field' => 'date_vente <', 'value' => $one_year_ago];
                        break;
                }
            }
        } else {
            // No filters active - show current year by default only if year selector is used
            $year = $this->session->userdata('vd_year');
            if ($year && $year != date('Y')) {
                $start_year = $year . '-01-01';
                $end_year = $year . '-12-31';
                $where_conditions[] = ['field' => 'date_vente >=', 'value' => $start_year];
                $where_conditions[] = ['field' => 'date_vente <=', 'value' => $end_year];
            }
        }
        
        // Build query
        $db->select($to_select)->from('vols_decouverte');
        
        // Apply where conditions
        foreach ($where_conditions as $condition) {
            if (isset($condition['no_escape']) && $condition['no_escape']) {
                $db->where($condition['field']);
            } else {
                $db->where($condition['field'], $condition['value']);
            }
        }
        
        $db->order_by('date_vente desc');
        $db_res = $db->get();
        $select = $this->get_to_array($db_res);
        
        $i = 0;
        foreach ($select as $elt) {
            $product = $elt['product'];
            $tarif = $this->tarifs_model->get_by_id('reference', $product);
            if ($tarif) {
                $select[$i]['product'] = $tarif['description'];
            }

            // Compute validity date (1 year from date_vente)
            $date_vente = new DateTime($elt['date_vente']);
            $select[$i]['validite'] = $date_vente->modify('+1 year')->format('Y-m-d');

            $i += 1;
        }
        $this->gvvmetadata->store_table("vue_vols_decouverte", $select);
        return $select;
    }

    /**
     * Ajoute un élément
     *
     * @param $data hash
     *            des valeurs
     */
    public function create($data) {
        $data['saisie_par'] = $this->dx_auth->get_username();
        $year = date('Y', strtotime($data['date_vente']));

        // les VD sont numérotés de façon croissante chaque année
        $highest_id = $this->highest_id_by_year($year);
        $data['id'] = $highest_id   + 1;

        parent::create($data);
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
        if (array_key_exists('date_vente', $vals) && array_key_exists('beneficiaire', $vals)) {
            $date = date_db2ht($vals['date_vente']);
            return $vals['id'] . " " . $date . " " . $vals['beneficiaire'];
        } else {
            return "vols_decouverte inconnu $key";
        }
    }

    /**
     * Suivant que la section planeur est active ou pas
     * le selecteur retourne
     *      - toutes les machines actives
     *      - les planeurs biplace actifs
     */
    public function machine_selector() {
        if ($this->section) {
            // var_dump($this->section['nom']); exit;
            if ($this->section['nom'] == "Planeur") {
                return $this->planeurs_model->selector_with_null(array('actif' => 1, 'mpbiplace' => 2));
            } else {
                return $this->avions_model->selector_with_null(array('actif' => 1));
            }
        }
    }

    /**
     * Retrieves the highest ID for a given year from the vols_decouverte table
     *
     * @param int $year The year to search for the highest ID
     * @return int The highest ID found for the specified year
     */
    function highest_id_by_year($year) {

        $year2 = $year - 2000;
        $min_id = $year2 * 10000;
        $max_id = ($year2 + 1) * 10000 - 1;

        $this->db->select_max('id', 'highest_id');
        $this->db->from('vols_decouverte');
        $this->db->where('id >=', $min_id);
        $this->db->where('id <=', $max_id);
        $query = $this->db->get();

        // Check if any results were found
        if ($query->num_rows() > 0 && $query->row()->highest_id !== null) {
            return $query->row()->highest_id;
        } else {
            return ($year - 2000) * 10000;
        }
    }

    /**
     * Get available years for year selector dropdown
     *
     * @return array Associative array of years available in the database
     */
    public function get_available_years() {
        $query = $this->db->select('DISTINCT(YEAR(date_vente)) as year')
                          ->from('vols_decouverte')
                          ->order_by('year', 'DESC')
                          ->get();
        
        $years = [];
        foreach ($query->result_array() as $row) {
            $year = $row['year'];
            $years[$year] = (string)$year;
        }
        
        // Always include current year if not present
        $current_year = date('Y');
        if (!isset($years[$current_year])) {
            $years[$current_year] = (string)$current_year;
        }
        
        return $years;
    }
}/* End of file */