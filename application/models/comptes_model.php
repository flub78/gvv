<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Comptes model
 *
 * C'est un CRUD de base.
 */

$CI = &get_instance();
$CI->load->model('common_model');
class Comptes_model extends Common_Model {
    public $table = 'comptes';
    protected $primary_key = 'id';
    protected $CI;

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();
        $this->CI = &get_instance();
        $this->load->model("ecritures_model");
        $this->load->model("membres_model");
        $this->load->model("sections_model");
    }

    /**
     * Retourne une ligne de base
     *
     * @return hash des valeurs
     */
    public function get_by_id($keyid, $keyvalue) {

        // every account should have a valid section/club so it is safe to make a joint

        $this->db->select('comptes.*, sections.nom as section_name');
        $this->db->from('comptes');
        $this->db->join('sections', 'comptes.club = sections.id', 'left');
        $this->db->where('comptes.id', $keyvalue);

        $res = $this->db->get()->row_array();

        return $res;
    }

    /**
     * Ajoute un élément
     *
     * @param $data hash
     *            des valeurs
     */
    public function create($data) {
        if (isset($data[$this->primary_key])) {
            unset($data[$this->primary_key]);
        }

        if ($this->db->insert($this->table, $data)) {
            $last_id = $this->db->insert_id();
            gvv_debug("create succesful, \$last_id=$last_id");
            if (! $last_id) {
                $last_id = $data[$this->primary_key];
                gvv_debug("\$last_id=$last_id (\$data[primary_key])");
            }
            return $last_id;
        } else {
            gvv_error("create error: " . var_export($data, false));
            return FALSE;
        }
    }

    /**
     * Retourne une liste d'objets
     *
     * <pre>
     * foreach ($list as $line) {
     * $this->table->add_row($line->mlogin,
     * $line->mprenom,
     * $line->mnom,
     * </pre>
     *
     * @param integer $nb
     *            taille de la page
     * @param integer $debut
     *            nombre à sauter
     * @return objet La liste
     */
    public function list_of($where = array(), $order = "") {
        return $this->db->select('*')->from($this->table)->where($where)->order_by($order)->get()->result_array();
    }

    /**
     * Retourne le tableau des comptes utilisé pour l'affichage par page
     * Les soldes sont calculés en fonction des sommes des écritures
     *
     * @param $selection des
     *            comptes à afficher
     * @param $date des
     *            soldes en français
     * @param $filter_solde 0
     *            =>, tous, 1 = debiteur, 2 => non nuls, 3 => crediteur
     * @return objet La liste
     */
    public function select_page($selection = array(), $date, $filter_solde = "") {

        // selectionne les comptes
        $result = $this->db
            ->select('id, nom, codec, actif, debit, credit, club')
            ->from('comptes, planc')
            ->where($selection)
            ->where('codec = planc.pcode');

        if ($this->sections_model->section()) {
            $this->db->where('club', $this->sections_model->section_id());
        }

        $result = $this->db->
            // ->limit($nb, $debut)
            order_by('codec, nom')->get()->result_array();

        $balance_date = date_ht2db($date);

        // Va chercher les soldes à la date donnée pour chaque compte
        foreach ($result as $key => $row) {
            // echo "$key => ";
            // var_dump($row);

            // Ajustement à la date donnée
            $soldes = $this->ecritures_model->solde_compte($row['id'], $balance_date, "<=", true);
            // $codec = substr($row['codec'], 0, 1);

            $row['debit'] = $soldes[0];
            $row['credit'] = $soldes[1];
            $result[$key]['debit'] = $row['debit'];
            $result[$key]['credit'] = $row['credit'];
            $result[$key]['image'] = 'le compte (' . $row['codec'] . ') ' . $row['nom'];

            $section = $this->sections_model->get_by_id('id', $row['club']);
            if ($section) {
                $result[$key]['section_name'] = $section['nom'];
            } else {
                $result[$key]['section_name'] = '';
            }

            if ($row['debit'] > $row['credit']) {
                // Solde débiteur
                $result[$key]['solde_debit'] = $row['debit'] - $row['credit'];
                $result[$key]['solde_credit'] = '';
                if ($filter_solde == 3) {
                    unset($result[$key]);
                }
            } else if ($row['debit'] < $row['credit']) {
                // Solde créditeur
                $result[$key]['solde_debit'] = '';
                $result[$key]['solde_credit'] = $row['credit'] - $row['debit'];
                if ($filter_solde == 1) {
                    unset($result[$key]);
                }
            } else {
                // Solde null
                $result[$key]['solde_debit'] = '';
                $result[$key]['solde_credit'] = $row['credit'] - $row['debit'];
                if (($filter_solde != 0)) {
                    unset($result[$key]);
                }
            }
        }
        // var_dump($result);
        return $result;
    }

    /**
     * Retourne le tableau des comptes utilisé pour l'affichage par page
     * Les soldes sont calculés en fonction des sommes des écritures
     *
     * @param $selection des comptes à afficher, codec ou codec range
     * @param $date des
     *            soldes en français
     * @param $filter_solde 0
     *            =>, tous, 1 = debiteur, 2 => non nuls, 3 => crediteur
     * @return objet La liste
     */
    public function select_page_general($selection = array(), $date, $filter_solde = "") {

        // selectionne les comptes
        $this->db
            ->select('pcode as codec, pdesc as nom, club')
            ->from('planc, comptes')
            ->where('codec = planc.pcode')
            ->where($selection)->order_by('codec');

        if ($this->sections_model->section()) {
            $this->db->where('club', $this->sections_model->section_id());
        }

        $result = $this->db->group_by('codec')
            ->get()->result_array();

        $balance_date = date_ht2db($date);

        // Va chercher les soldes à la date donnée pour chaque compte
        foreach ($result as $key => $row) {
            // echo "$key => "; var_dump($row);

            // Ajustement à la date donnée
            $soldes = $this->ecritures_model->solde_compte_general($row['codec'], $balance_date, "<=", true);

            $row['debit'] = $soldes[0];
            $row['credit'] = $soldes[1];
            $result[$key]['debit'] = $row['debit'];
            $result[$key]['credit'] = $row['credit'];
            $result[$key]['image'] = 'le compte général (' . $row['codec'] . ') ' . $row['nom'];

            if ($row['debit'] > $row['credit']) {
                // Solde débiteur
                $result[$key]['solde_debit'] = $row['debit'] - $row['credit'];
                $result[$key]['solde_credit'] = '';
                if ($filter_solde == 3) {
                    unset($result[$key]);
                }
            } else if ($row['debit'] < $row['credit']) {
                // Solde créditeur
                $result[$key]['solde_debit'] = '';
                $result[$key]['solde_credit'] = $row['credit'] - $row['debit'];
                if ($filter_solde == 1) {
                    unset($result[$key]);
                }
            } else {
                // Solde null
                $result[$key]['solde_debit'] = '';
                $result[$key]['solde_credit'] = $row['credit'] - $row['debit'];
                if (($filter_solde != 0)) {
                    unset($result[$key]);
                }
            }
        }
        // var_dump($result);
        return $result;
    }

    /**
     * Selection des écritures par catégories
     */
    public function select_categorie() {
    }

    /**
     * Retourne une chaine de caractère qui identifie un compte de façon unique.
     */
    public function image($key) {
        $vals = $this->get_by_id('id', $key);
        return '(' . $vals['codec'] . ") " . $vals['nom'];
    }

    /**
     * Mise à jour les comptes référencés
     *
     * @param unknown_type $deb_id
     * @param unknown_type $cred_id
     * @param unknown_type $montant
     */
    public function maj_comptes($deb_id, $cred_id, $montant) {
        $compte_deb = $this->get_by_id('id', $deb_id);
        // gvv_debug("sql: " . $this->db->last_query());

        $compte_cred = $this->get_by_id('id', $cred_id);
        // gvv_debug("sql: " . $this->db->last_query());

        /**
         * TODO: est-ce qu'il faut maintenir les soldes dans les comptes ?
         * Je ne suis pas sûr qu'il y ai des endroits ou c'est utilisé, je pense que la plupart des soldes sont systématiquement recalculés...
         * Et ca génére une duplication de l'information, donc des risques...
         */

        if ($compte_deb['actif'] != $compte_cred['actif']) {
            $compte_deb['debit'] += $montant;
            $compte_cred['credit'] += $montant;
        } else {
            $compte_deb['debit'] += $montant;
            $compte_cred['credit'] += $montant;
        }

        // Patch pour certains contextes desc n'es pas analysé comme un champ et
        // provoque une erreur MySQL. Le plus bizarre est que ce n'est pas systématique.
        // peut-être les caractères d'échappement d'Active record
        if (isset($compte_deb['desc'])) {
            unset($compte_deb['desc']);
        }
        if (isset($compte_deb['section_name'])) {
            unset($compte_deb['section_name']);
        }

        if (isset($compte_cred['desc'])) {
            unset($compte_cred['desc']);
        }
        if (isset($compte_cred['section_name'])) {
            unset($compte_cred['section_name']);
        }

        $this->update('id', $compte_deb);
        // gvv_debug("sql: " . $this->db->last_query());
        $this->update('id', $compte_cred);
        // gvv_debug("sql: " . $this->db->last_query());
    }

    /**
     * Retourne le compte d'un pilote
     *
     * @param
     *            $pilote
     */
    public function compte_pilote($pilote) {
        $info_pilote = $this->membres_model->get_by_id('mlogin', $pilote);

        if ($info_pilote['compte']) {
            return $info_pilote['compte'];
        }

        $select = $this->db->select('id, nom, debit, credit, actif')->from($this->table)->where(array(
            'pilote' => $pilote
        ))->get()->result_array();

        return $select[0];
    }

    /**
     * Retourne l'identifiant du compte d'un pilote
     *
     * @param
     *            $pilote
     */
    public function compte_pilote_id($pilote) {
        $res = $this->compte_pilote($pilote);
        return $res['id'];
    }

    /**
     * Retourne le solde du compte d'un pilote
     *
     * @param
     *            $pilote
     */
    public function solde_pilote($pilote) {
        $compte_id = $this->compte_pilote_id($pilote, true);
        $solde = $this->ecritures_model->solde_compte($compte_id);
        return $solde;
    }

    /**
     * Retourne le propriétaire (membre) d'un compte pilote
     * $key id du compte 411 dont on cherche le pilote
     */
    public function user($key) {
        $vals = $this->get_by_id('id', $key);
        return $vals['pilote'];
    }

    /**
     * Vérifie si un utilisateur a un compte
     *
     * @param
     *            $user
     */
    public function has_compte($user) {

        return $this->pilot_account($user);
    }


    public function pilot_account($user) {
        $section = $this->sections_model->section();

        $this->db
            ->select('id, nom, codec, desc, actif, debit, credit, club')
            ->from('comptes')
            ->where("pilote = '$user'");

        // On ne retourne rien s'il n'y a pas de section active 
        $this->db->where('club', $this->sections_model->section_id());

        $result = $this->db->get();
        gvv_debug("sql: " . $this->db->last_query());


        if ($result) {
            return $result->row_array();
        } else {
            return false;
        }
    }

    /**
     * Calculate total of a list of values
     * @param array $list List of values to sum
     * @return float Total sum
     */
    function total_of($list) {
        $total = 0;
        foreach ($list as $row) {
            $total += $row['solde'];
        }
        return $total;
    }


    /**
     * Selection globale de données pour le bilan
     * retourn une table de hash avec les données collectées
     */
    public function select_all_for_bilan($year) {
        $data = array();
        $data['controller'] = "compta";
        $data['nom'] = $this->config->item('nom_club');

        // $year = $this->session->userdata('year');
        $data['annee_exercise'] = $year;
        $data['year_selector'] = $this->ecritures_model->getYearSelector("date_op");
        $data['year'] = $year;
        $day = 31;
        $month = 12;
        $data['date'] = "$day/$month/$year";
        $date_op = "$year-$month-$day";

        // immobilisation = solde des comptes de classe 2
        $data['immo'] = $this->ecritures_model->select_solde($date_op, 2, 28, TRUE);

        // Disponible = solde des comptes de classe 5
        $data['dispo'] = $this->ecritures_model->select_solde($date_op, 5, 6, TRUE);

        // Fonds associatif = solde des comptes de classe 1 et 11
        $data['capital_2'] = $this->ecritures_model->select_solde($date_op, 1, 12, FALSE);

        $data['ammortissements_corp'] = $this->total_of($this->ecritures_model->select_solde($date_op, "28", "29", TRUE));

        $data['fonds_associatifs'] = $this->total_of($this->ecritures_model->select_solde($date_op, 102, 103, TRUE));
        $data['reports_cred'] = $this->total_of($this->ecritures_model->select_solde($date_op, 110, 111, TRUE));
        $data['reports_deb'] = $this->total_of($this->ecritures_model->select_solde($date_op, 119, 120, TRUE));

        $data['valeur_brute_immo_corp'] = -$this->total_of($data['immo']);
        $data['valeur_nette_immo_corp'] = $data['valeur_brute_immo_corp'] - $data['ammortissements_corp'];
        $tiers = $this->ecritures_model->select_solde($date_op, 4, 5, FALSE);

        $creances_pilotes = 0.0;
        $dettes_pilotes = 0.0;
        foreach ($tiers as $row) {
            // var_dump($row);
            if ($row['solde'] > 0) {
                $dettes_pilotes += $row['solde'];
            } else {
                $creances_pilotes -= $row['solde'];
            }
        }
        $data['creances_pilotes'] = $creances_pilotes;
        $data['dettes_pilotes'] = $dettes_pilotes;

        // Résultat
        $charges = $this->ecritures_model->select_depenses($year, "");
        $total_charges = $charges[0]['montant'];

        $produits = $this->ecritures_model->select_recettes($year, "");
        $total_produits = $produits[0]['montant'];

        $data['resultat'] = $total_produits - $total_charges;

        $total_dispo = $this->total_of($data['dispo']);
        $data['total_actif'] = $creances_pilotes + $data['valeur_nette_immo_corp'] - $total_dispo;

        $total_capital = $this->total_of($data['capital_2']);
        $data['total_passif'] = $dettes_pilotes + $total_capital + $data['resultat'];

        $data['emprunts'] = $this->total_of($this->ecritures_model->select_solde($date_op, 16, 17, TRUE));
        $data['total_passif'] += $data['emprunts'];

        // var_dump($data); exit;
        return $data;
    }
}

/* End of file */