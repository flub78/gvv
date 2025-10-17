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

            $member_sections = array();
            foreach ($sections as $section) {
                $account = $this->comptes_model->get_by_pilote_codec($pilote, '411', $section['id']);
                if ($account) {
                    $member_sections[$section['nom']] = true;
                } else {
                    $member_sections[$section['nom']] = false;
                }
            }
            $select[$key]['member_sections'] = $member_sections;

            $photo_path = 'uploads/photos/' . $row['photo'];
            $photo_html = '';
            if ($row['photo'] && file_exists($photo_path)) {
                $photo_html .= '<img src="' . base_url($photo_path) . '" style="width: 100px;" />';
            }
            $badges = '<div class="d-flex justify-content-start mt-2">';
            if (isset($member_sections['Avion']) && $member_sections['Avion']) {
                $badges .= '<span class="badge bg-primary rounded-pill me-1" title="Vol Moteur">VM</span>';
            }
            if (isset($member_sections['Planeur']) && $member_sections['Planeur']) {
                $badges .= '<span class="badge bg-secondary rounded-pill me-1" title="Vol à Voile">VP</span>';
            }
            if (isset($member_sections['ULM']) && $member_sections['ULM']) {
                $badges .= '<span class="badge bg-info rounded-pill" title="ULM">ULM</span>';
            }
            $badges .= '</div>';
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
}

/* End of file membres_model.php */
/* Location: ./application/models/membres_model.php */