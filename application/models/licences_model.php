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
     * @param int $type Type de licence
     * @param int|null $year_min Année de début (null = auto)
     * @param int|null $year_max Année de fin (null = auto)
     * @param string $member_status Status des membres ('all', 'active', 'inactive')
     * @param int|null $section_id ID de la section (null = toutes les sections)
     * @return array Array avec 'data' (lignes de la table) et 'total' (ligne de total)
     */
    public function per_year($type, $year_min = null, $year_max = null, $member_status = 'active', $section_id = null) {

        // Liste de pilotes selon le statut demandé
        $this->db->distinct();
        $this->db->select('membres.mlogin, membres.mnom, membres.mprenom, membres.m25ans, membres.actif');
        $this->db->from("membres");

        // Si un filtre de section est appliqué, joindre avec la table comptes
        if ($section_id !== null && $section_id !== 'all') {
            $this->db->join("comptes", "membres.mlogin = comptes.pilote", "inner");
            $this->db->where('comptes.club', $section_id);
            $this->db->where('comptes.codec', '411');
        }

        // Filtrer selon le statut des membres
        if ($member_status === 'active') {
            $this->db->where('membres.actif', 1);
        } elseif ($member_status === 'inactive') {
            $this->db->where('membres.actif', 0);
        }
        // Si 'all', pas de filtre sur actif

        $actifs = $this->db->order_by("membres.mnom, membres.mprenom")->get()->result_array();

        // extraction des licences
        $selection = $this->select_columns('id, pilote, type, year, date, comment', 1000000, 0, array (
            'type' => $type
        ));

        // Déterminer min et max
        if ($year_min === null || $year_max === null) {
            // Mode automatique : calculer depuis les données
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
        } else {
            // Utiliser les paramètres fournis
            $min = $year_min;
            $max = $year_max;
        }

        // Initialise the array
        $results = array ();
        $line = 0;
        $col = 0;
        
        // Initialiser la ligne d'en-tête ET la ligne de total
        $total_annuel = array ();
        $results[$line][$col] = "Pilote";
        $total_annuel[$col] = "Total";
        $col++;

        // Créer les colonnes d'années
        for ($year = $min; $year <= $max; $year++) {
            $results[$line][$col] = $year;
            $total_annuel[$col] = 0;
            $col++;
        }
        
        // Nombre total de colonnes (1 pour "Pilote" + nombre d'années)
        $num_columns = $col;

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
            
            // Vérifier que l'année est dans la plage affichée
            if ($year < $min || $year > $max) {
                continue;
            }
            
            // Checkbox cochée pour les licences existantes
            $checkbox = '<input type="checkbox" class="licence-checkbox" data-pilote="' . $pilote . '" data-year="' . $year . '" data-type="' . $type . '" checked>';
            $col = $year - $min + 1;
            if ( array_key_exists($pilote, $pilote_line) ) {
            	$results[$pilote_line[$pilote]][$col] = $checkbox;
            	$total_annuel[$col] += 1;
            }
        }
        
        // S'assurer que le total a exactement le bon nombre de colonnes
        // Remplir les colonnes manquantes avec 0
        for ($i = 0; $i < $num_columns; $i++) {
            if (!isset($total_annuel[$i])) {
                $total_annuel[$i] = 0;
            }
        }
        
        // Retourner les données et le total séparément
        return array(
            'data' => $results,
            'total' => $total_annuel
        );
    }

    /**
     * Retourne l'année minimum pour laquelle il y a des données
     * @return int Année minimum, ou année courante - 5 si pas de données
     */
    public function get_min_year() {
        $this->db->select_min('year');
        $this->db->from('licences');
        $result = $this->db->get()->row_array();
        
        $min_year = isset($result['year']) && !empty($result['year']) ? $result['year'] : (date("Y") - 5);
        return (int)$min_year;
    }

}

/* End of file licences_model.php */
/* Location: ./application/models/licences_model.php */