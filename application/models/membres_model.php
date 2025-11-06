<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = & get_instance();
$CI->load->model('common_model');
$CI->load->model('event_model');
$CI->load->helper('statistic');

/**
 * Membres_model CRUD pattern.
 *
 * Everything is done in Common_Model except the table
 * name declaration
 */
class Membres_model extends Common_Model {
    public $table = 'membres';
    protected $primary_key = 'mlogin';

    /**
     * Retourne une chaine de caractère qui identifie une ligne de façon unique.
     * Cette chaine est utilisé dans les affichages.
     * Par défaut elle retourne la valeur de la clé, mais elle est conçue pour être
     * surchargée.
     *
     * @param $key -
     *            identifiant du pilote
     * @param $short -
     *            retourne le trigramme si non vide, sinon l'image normale
     */
    public function image($key, $short = false) {
        if ($key == "")
            return "";
        $vals = $this->get_by_id('mlogin', $key);
        if (array_key_exists('mprenom', $vals) && array_key_exists('mnom', $vals)) {
            if ($short && array_key_exists('trigramme', $vals) && ($vals ['trigramme'] != '')) {
                return $vals ['trigramme'];
            }
            return $vals ['mnom'] . " " . $vals ['mprenom'];
        } else {
            return "pilote inconnu $key";
        }
    }
    public function age($key) {
        if ($key == "")
            return "";
        $vals = $this->get_by_id('mlogin', $key);

        if (array_key_exists('m25ans', $vals))
            return $vals ['m25ans'];
        else
            return "nil";
    }

    /**
     * Les gens sons considérés comme moins de 25 ans la case à cocher - 25 ans est active dans la fiche membre.
     * C'est au trésorier de basculer en fonction de la règle du club
     * - soit quand ils dépassent 25 ans
     * - soit au moment de leur première licence plus de 25 ans
     */
    public function moins_25ans($key, $date) {
        if ($key == "")
            return "";

        gvv_debug("-25 ans $key $date");

        $date25 = date_m25ans(substr($date, 0, 4));

        $this->db->select("(mdaten > \"$date25\") as m25ans, year(mdaten) as year")->where('mlogin', $key);
        $vals = $this->db->get($this->table)->row_array();
        gvv_debug(var_export($vals, true));

        return $vals ['m25ans'];

        /*
         * if (!$vals['year']) {
         * return $vals['m25ans'];
         * } else {
         *
         * }
         * $birth_year = $vals['year'];
         * $bill_year = substr($date, 0, 4);
         * $m25 = (($bill_year - $birth_year) <= 25);
         * gvv_debug("-25 ans $key $birth_year $bill_year $m25");
         * return $m25;
         */
    }

    /**
     * Retourne un hash qui peut-être utilisé dans un menu drow-down
     *
     * @param
     *            hash des valeurs
     */
    public function qualif_selector($key, $level) {
        $allkeys = $this->select_columns($key . ',mniveaux', 0, 0, array (
                'actif' => 1
        ));
        $result = array ();
        $result [''] = '';
        foreach ( $allkeys as $row ) {
            $niveaux = $row ['mniveaux'];
            if (($niveaux & ($level)) != 0) {
                $value = $row [$key];
                $result [$value] = $this->image($value);
            }
        }
        return $result;
    }

    /**
     * Retourne le tableau tableau utilisé pour l'affichage par page
     *
     * @return objet La liste
     */
    public function select_page($nb = 1000, $debut = 0, $selection = array()) {
        $select = $this->db->select('mlogin, mprenom, trigramme, mnom, madresse, cp, ville, mtelf, mtelm, memail, mdaten, m25ans, msexe, actif, categorie, photo, place_of_birth, inscription_date, validation_date')->from($this->table)->order_by('mnom, mprenom')->where($selection)->
        // ->limit($nb, $debut)
        get()->result_array();

        $this->load->model('sections_model');
        $this->load->model('comptes_model');
        $sections = $this->sections_model->section_list();

        foreach ( $select as $key => $row ) {
            $select [$key] ['image'] = "le pilote " . $row ['mprenom'] . ' ' . $row ['mnom'];

            $pilote = $row ['mlogin'];

            // Get section IDs where member is registered using the efficient method
            $registered_section_ids = $this->registered_in_sections($pilote);

            // Build array of section details for registered sections
            $member_sections = array();
            foreach ($sections as $section) {
                if (in_array($section['id'], $registered_section_ids)) {
                    $member_sections[] = $section;
                }
            }
            $select[$key]['member_sections'] = $member_sections;

            $photo_path = 'uploads/photos/' . $row['photo'];
            $photo_html = '';
            if ($row['photo'] && file_exists($photo_path)) {
                $photo_url = base_url($photo_path);
                // Create custom attachment HTML for member photos with specific sizing
                $photo_html .= '<a href="' . $photo_url . '" target="_blank" title="Cliquer pour voir en taille réelle">';
                $photo_html .= '<img src="' . $photo_url . '" style="width: 100px; max-width: 100px; height: auto; border: 1px solid #dee2e6; border-radius: 0.25rem; padding: 0.25rem; background-color: #f8fafc;" />';
                $photo_html .= '</a>';
            }
            
            // Only show badges if there are sections in the database
            $badges = '';
            if (count($sections) > 0) {
                $badges = '<div class="d-flex justify-content-start mt-2">';
                foreach ($member_sections as $section) {
                    $badge_class = 'badge rounded-pill me-1';
                    $badge_style = '';
                    if (!empty($section['couleur'])) {
                        $badge_style = ' style="background-color: ' . $section['couleur'] . '; color: black; border: 1px solid black;"';
                    } else {
                        $badge_class .= ' bg-primary';
                    }
                    $badges .= '<span class="' . $badge_class . '" title="' . $section['nom'] . '"' . $badge_style . '>' . $section['acronyme'] . '</span>';
                }
                $badges .= '</div>';
            }
            $select[$key]['photo_with_badges'] = $photo_html . $badges;

            $select [$key] ['vols_avion'] = anchor(controller_url("vols_avion/vols_du_pilote/$pilote"), "avion");
            $select [$key] ['vols_planeur'] = anchor(controller_url("vols_planeur/vols_du_pilote/$pilote"), "planeur");

            $select [$key] ['liens'] = form_dropdown('goto', array (
                    'planeur' => "Vols planeur",
                    'avion' => "Vols avion",
                    'certificats' => "Certificats",
                    'compte' => "Compte",
                    'tickets' => 'Tickets'
            ), 'tickets', "id='goto' onchange=goto(\"$pilote\");");

            $list = array (
                    anchor(controller_url("vols_avion/vols_du_pilote/$pilote"), "Vols avion"),
                    anchor(controller_url("vols_planeur/vols_du_pilote/$pilote"), "Vols planeur"),
                    anchor(controller_url("membre/certificats/$pilote"), "Certificats")
            );

            $select [$key] ['vols_avion'] = ul($list);

            $msexe = $select [$key] ['msexe'];
            $select [$key] ['msexe'] = "<div class=\"gendre_$msexe\">" . $msexe . "</div>";

            // désactivé, pertube le tri par date
            // $mdaten = $select[$key]['mdaten'];
            // $class = "m25";
            // $select[$key]['mdaten'] = "<div class=\"$class\">" . $mdaten . "</div>";
            // var_dump($select[$key]);
        }

        $this->gvvmetadata->store_table($this->table, $select);
        return $select;
    }

    /**
     * Retourne le tableau tableau utilisé pour l'affichage par page
     *
     * @return objet La liste
     */
    public function select_licences() {
        $select = $this->db->select("mlogin, mprenom, mnom, madresse, cp, ville, mdaten, CONCAT(mnom, ' ', mprenom) as nom_prenom", FALSE)->from($this->table)->order_by('mnom, mprenom')->where(array (
                'actif' => 1
        ))->get()->result_array();

        $this->gvvmetadata->store_table($this->table, $select);

        return $select;
    }

    /**
     *
     * @return a default user, either the logged in one or the first one.
     */
    public function default_id() {
        $id = $this->dx_auth->get_username();
        $count = $this->count(array (
                'mlogin' => $id
        ));
        // echo "count=$count $id<br>";
        if ($count == 0) {
            return '';
        /**
         * $query = $this->db->select('mlogin')
         * ->from($this->table)
         * ->get()
         * ->result_array();
         * $row = $query[0];
         * $id = $row['mlogin'];
         */
        } else {
            return $id;
        }
    }

    /**
     * Temporaire tratement d'une date
     */
    function certif($mlogin, $id, $key, $value) {
        $select = $this->db->select('*')->from('events')->where(array (
                'emlogin' => $mlogin,
                'etype' => $id
        ))->get()->result_array();

        $count = count($select);
        if (! $count && ('0000-00-00' != $value))
            echo "$count $mlogin: $key => $value ... $id" . br();
    }

    /**
     * Fonction temporaire de migration des dates de certificats
     *
     * (14, 'Premier vol', 1),
     * (15, 'Laché planeur', 1),
     * (16, 'Vol 1h', 1),
     * (17, 'Vol 5h', 4),
     * (18, 'Gain de 1000m', 4),
     * (19, 'Gain de 3000m', 4),
     * (20, 'Gain de 5000m', 4),
     * (21, 'Distance de 50km', 4),
     * (22, 'Distance de 300km', 4),
     * (23, 'Distance de 500km', 4),
     * (24, 'Distance de 750km', 4),
     * (25, 'Distance de 1000km', 4),
     * (26, 'Visite médical', 0),
     * (27, 'BPP', 1),
     * (28, 'BIA', 0),
     * (29, 'Campagne', 1),
     * (30, 'Contôle de compétence', 1),
     * (31, 'Circuit de 300km FAI', 4),
     * (33, 'Théorique BPP', 1),
     * (34, 'Emport passager', 1),
     * (35, 'Laché avion', 2),
     * (36, 'BB', 2),
     * (37, 'PPL', 2);
     * (38, 'Validité licence avion', 2);
     * (39, 'FI Formateur instructeur', 2);
     * (40, 'FE Formateur examinateur', 2);
     * (41, 'Autorisation remorquage', 2);
     * (42, 'Premier vol avion', 1);
     *
     * (43, 'ITP', 1);
     * (44, 'ITV', 1);
     */
    public function export($nb = 1000, $debut = 0) {
        $select = $this->db->select('*')->from($this->table)->order_by('mnom, mprenom')->limit($nb, $debut)->get()->result_array();

        // var_dump($select);

        $certifs = array (
                'mbranum' => 37,
                'mbradat' => 37,
                'mbraval' => 38,
                'mbrpnum' => 27,
                'mbrpdat' => 27,
                'numinstavion' => 39,
                'dateinstavion' => 39,
                'numivv' => 43,
                'dateivv' => 43,
                'medical' => 26
        );
        /*
         */
        foreach ( $select as $row ) {
            $mlogin = $row ['mlogin'];
            foreach ( $row as $key => $value ) {
                if (array_key_exists($key, $certifs)) {
                    if ($value) {
                        $id = $certifs [$key];
                        $this->certif($mlogin, $id, $key, $value);
                    }
                }
            }
        }
    }

    /**
     * Retourne la liste des adresses emails selectionnées sour forme de chaine de caractères.
     *
     * @param unknown_type $where
     * @return unknown
     */
    public function emails($where = array()) {
        if ($where == '')
            $where = array ();
        if ($where == "solde < 0") {
            $where = array ();
            $debiteur = true;
        } else {
            $debiteur = false;
        }

        $select = $this->db->select("memail, memailparent, mlogin")->from($this->table)->where($where)->where(array (
                'actif' => 1
        ))->get()->result_array();

        $adresses = array ();
        foreach ( $select as $row ) {
            if ($debiteur) {
                // TODO: Il faudra faire qq chose de plus robuste
                $solde = $this->comptes_model->solde_pilote($row ['mlogin']);
                if ($solde >= 0) {
                    gvv_debug("solde positif $solde " . $row ['mlogin']);
                    continue;
                }
            }
            if (isset($row ['memail']) && $row ['memail'])
                $adresses [] = $row ['memail'];
            if (isset($row ['memailparent']) && $row ['memailparent'])
                $adresses [] = $row ['memailparent'];
        }
        $res = join(", ", $adresses);
        gvv_debug("emails = $res");
        return $res;
    }

    /**
     * selectionne les pilotes qui ont une adresse email donnée
     *
     * @param unknown_type $email
     * @return unknown
     */
    public function pilote_with_email($email) {
        $select = $this->db->select("mlogin, mprenom, mnom, madresse, cp, ville, mdaten, memail, memailparent", FALSE)->from($this->table)->order_by('mnom, mprenom')->where("actif = \"1\" and (memail = \"" . $email . "\" or memailparent = \"" . $email . "\") ")->get()->result_array();

        gvv_debug("sql: " . $this->db->last_query());
        $cnt = 0;
        foreach ( $select as $row ) {
            $select [$cnt] ['solde'] = $this->comptes_model->solde_pilote($row ['mlogin']);
            $cnt ++;
        }
        // gvv_debug("query result=" . var_export($select, true));
        return $select;
    }

    /**
     * Delete a membre with validation
     * Checks if the membre is referenced in other tables before deletion
     * 
     * @param array $where - selection criteria
     * @return boolean - TRUE if deleted, FALSE if blocked
     */
    function delete($where = array()) {
        // Get mlogin from where clause
        if (!isset($where['mlogin'])) {
            // If no mlogin specified, can't validate - abort
            return FALSE;
        }
        
        $mlogin = $where['mlogin'];
        
        // Get CodeIgniter instance for language support
        $CI =& get_instance();
        $CI->lang->load('membres');
        
        // Check if membre is referenced in other tables
        $references = array();
        
        // Check comptes.pilote
        $this->db->where('pilote', $mlogin);
        $count = $this->db->count_all_results('comptes');
        if ($count > 0) {
            $references[] = $CI->lang->line('membre_delete_ref_compte') . " ($count)";
        }
        
        // Check volsa.vapilid (airplane flights - pilot)
        $this->db->where('vapilid', $mlogin);
        $count = $this->db->count_all_results('volsa');
        if ($count > 0) {
            $references[] = $CI->lang->line('membre_delete_ref_volsa_pilot') . " ($count)";
        }
        
        // Check volsa.vainst (airplane flights - instructor)
        $this->db->where('vainst', $mlogin);
        $count = $this->db->count_all_results('volsa');
        if ($count > 0) {
            $references[] = $CI->lang->line('membre_delete_ref_volsa_instructor') . " ($count)";
        }
        
        // Check volsp.vppilid (glider flights - pilot)
        $this->db->where('vppilid', $mlogin);
        $count = $this->db->count_all_results('volsp');
        if ($count > 0) {
            $references[] = $CI->lang->line('membre_delete_ref_volsp_pilot') . " ($count)";
        }
        
        // Check volsp.vpinst (glider flights - instructor)
        $this->db->where('vpinst', $mlogin);
        $count = $this->db->count_all_results('volsp');
        if ($count > 0) {
            $references[] = $CI->lang->line('membre_delete_ref_volsp_instructor') . " ($count)";
        }
        
        // Check volsp.pilote_remorqueur (glider flights - tow pilot)
        $this->db->where('pilote_remorqueur', $mlogin);
        $count = $this->db->count_all_results('volsp');
        if ($count > 0) {
            $references[] = $CI->lang->line('membre_delete_ref_volsp_towpilot') . " ($count)";
        }
        
        // If there are references, block deletion with error message
        if (!empty($references)) {
            $CI->load->library('session');
            $unique_refs = array_unique($references);
            
            // Create detailed error message with mlogin
            $error_msg = sprintf($CI->lang->line('membre_delete_blocked'), $mlogin) . "\n\n";
            $error_msg .= $CI->lang->line('membre_delete_dependencies') . "\n";
            $error_msg .= "• " . implode("\n• ", $unique_refs);
            
            $CI->session->set_flashdata('error', $error_msg);
            $CI->session->set_flashdata('delete_failed_membre', $mlogin);
            return FALSE;
        }
        
        // If no references, proceed with deletion using parent method
        parent::delete($where);
        return TRUE;
    }

    /**
     * Get list of section IDs where the member is registered
     * A member is registered in a section if they have an account (comptes)
     * with codec='411' and pilote=membre.mlogin
     *
     * @param string $mlogin Member login identifier
     * @return array Array of section_id values
     */
    public function registered_in_sections($mlogin)
    {
        $this->db->select('club as section_id');
        $this->db->from('comptes');
        $this->db->where('codec', '411');
        $this->db->where('pilote', $mlogin);
        $this->db->where('actif', 1);

        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            // Convert to integers to ensure consistent type for comparison
            return array_map('intval', array_column($query->result_array(), 'section_id'));
        }

        return array();
    }
}

/* End of file membres_model.php */
/* Location: ./application/models/membres_model.php */