<?php
if (!defined('BASEPATH'))
    exit ('No direct script access allowed');

$CI = & get_instance();
$CI->load->model('common_model');

/**
 *	Accès base Licences
 *
 *  C'est un CRUD de base, la seule chose que fait cette classe
 *  est de définir le nom de la table. Tous les méthodes sont
 *  implémentés dans Common_Model
 */
class Licences_model extends Common_Model {
    public $table = 'licences';
    protected $primary_key = 'pilote,year,type';

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_page($nb = 1000, $debut = 0, $where = array ()) {
        $select = $this->select_columns('id, pilote, type, year, date, comment', $nb, $debut, $where);
        $this->gvvmetadata->store_table("vue_licences", $select);
        return $select;
    }

    /**
     * Extrait les informations de formation
     */
    public function per_year($type) {

        // Liste de pilotes actifs.
        $select = 'mlogin, mnom, mprenom, m25ans';
        $actifs = $this->db->select($select)->from("membres,licences")
        ->where("membres.mlogin = licences.pilote")
        ->group_by("membres.mlogin")
        ->order_by("mnom, mprenom")->get()->result_array();

        // extraction des licences
        $selection = $this->select_columns('id, pilote, type, year, date, comment', 1000000, 0, array (
            'type' => $type
        ));

        // look for min and max year
        $min_range = 10;
        $min = date("Y");
        $max = $min;
        foreach ($selection as $licence) {
            $year = $licence['year'];
            if ($year < $min)
                $min = $year;
            if ($year > $max)
                $max = $year;
        }
        if (($max - $min) < $min_range)
            $min = $max - $min_range;

        // Initialise the array
        $results = array ();
        $line = 0;
        $col = 0;
        $total_annuel = array ();
        $total_annuel[$col] = "Total";
        $results[$line][$col++] = "Pilote";

        for ($year = $min; $year <= $max; $year++) {
            $results[$line][$col] = $year;
            $total_annuel[$col] = 0;
            $col++;
        }
        $pilote_line = array ();
        foreach ($actifs as $pilote) {
            $line++;
            $col = 0;
            $mlogin = $pilote['mlogin'];
            $pilote_line[$mlogin] = $line;
            $results[$line][$col++] = anchor(controller_url("event/page/$mlogin"), $pilote['mnom'] . ' ' . $pilote['mprenom']);
            for ($year = $min; $year <= $max; $year++) {
                // Checkbox non cochée par défaut
                $checkbox = '<input type="checkbox" class="licence-checkbox" data-pilote="' . $mlogin . '" data-year="' . $year . '" data-type="' . $type . '">';
                $results[$line][$col++] = $checkbox;
            }
        }

        foreach ($selection as $licence) {
            $pilote = $licence['pilote'];
            $year = $licence['year'];
            // Checkbox cochée pour les licences existantes
            $checkbox = '<input type="checkbox" class="licence-checkbox" data-pilote="' . $pilote . '" data-year="' . $year . '" data-type="' . $type . '" checked>';
            $col = $year - $min +1;
            if ( array_key_exists($pilote, $pilote_line) ) {
            	$results[$pilote_line[$pilote]][$col] = $checkbox;
            	$total_annuel[$col] += 1;
            }
        }
        $results[] = $total_annuel;
        //var_dump($results);
        return $results;
    }

}

/* End of file licences_model.php */
/* Location: ./application/models/licences_model.php */