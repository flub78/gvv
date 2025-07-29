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
         * Récupère un compte par section et code codec
         * 
         * @param string $club L'identifiant de la section/club
         * @param string $codec Le code codec du compte
         * @return array Le compte correspondant aux critères
         */
    public function get_by_section_and_codec($club, $codec) {
        $this->db->select('comptes.*');
        $this->db->from('comptes');
        $this->db->where('comptes.club', $club);
        $this->db->where('comptes.codec', $codec);

        $res = $this->db->get()->row_array();

        return $res;
    }

    /**
     * Retourne un compte par pilote codec
     * 
     * @param string $pilote_codec Le code du pilote
     * @return array Le compte trouvé
     */
    public function get_by_pilote_codec($pilote, $codec, $section) {
        $this->db->select('comptes.*, sections.nom as section_name');
        $this->db->from('comptes');
        $this->db->join('sections', 'comptes.club = sections.id', 'left');
        $this->db->where('comptes.pilote', $pilote);
        $this->db->where('comptes.codec', $codec);
        $this->db->where('comptes.club', $section);

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

        $this->db->select('*')
            ->from($this->table)
            ->where($where)
            ->order_by($order);

        $section = $this->sections_model->section();
        if ($section) {
            $this->db->where('club', $section['id']);
        }
        $result = $this->db->get();

        if ($result) {
            return $result->result_array();
        } else {
            return [];
        }
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
    public function select_page_general($selection = array(), $date, $filter_solde = "", $with_sections = true) {

        // selectionne les comptes
        $this->db
            ->select('pcode as codec, pdesc as nom, club')
            ->from('planc, comptes')
            ->where('codec = planc.pcode')
            ->where($selection)->order_by('codec');

        if ($this->sections_model->section() && $with_sections) {
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
        $image = '(' . $vals['codec'] . ':' . $vals['section_name'] . ") " . $vals['nom'];
        return $image;
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
            $compte_id = $info_pilote['compte'];
            $compte= $this->comptes_model->get_by_id('id', $compte_id);
            return $compte;
        }

        $section = $this->gvv_model->section();

        $this->db
            ->select('id, nom, debit, credit, actif')
            ->from($this->table)
            ->where(array('pilote' => $pilote));

        if ($this->section) {
            $this->db->where('comptes.club', $section['id']);
        }

        $result = $this->db->get();

        if ($result) {
            return $result->result_array()[0];
        } else {
            gvv_error("Erreur lors de la recherche du compte du pilote $pilote");
            return null;
        }
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
        if ($section) {
            $this->db->where('club', $section['id']);
        }

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

        $data['amortissements_corp'] = $this->total_of($this->ecritures_model->select_solde($date_op, "28", "29", TRUE));

        $data['fonds_associatifs'] = $this->total_of($this->ecritures_model->select_solde($date_op, 102, 103, TRUE));
        $data['reports_cred'] = $this->total_of($this->ecritures_model->select_solde($date_op, 110, 111, TRUE));
        $data['reports_deb'] = $this->total_of($this->ecritures_model->select_solde($date_op, 119, 120, TRUE));

        $data['prets'] = $this->total_of($this->ecritures_model->select_solde($date_op, 274, 275, TRUE)) * - 1;

        $data['valeur_brute_immo_corp'] = -$this->total_of($data['immo']);
        $data['valeur_nette_immo_corp'] = $data['valeur_brute_immo_corp'] - $data['amortissements_corp'];
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
        $data['total_actif'] = $creances_pilotes + $data['valeur_nette_immo_corp'] - $total_dispo + $data['prets'];

        $total_capital = $this->total_of($data['capital_2']);
        $data['total_passif'] = $dettes_pilotes + $total_capital + $data['resultat'];

        $data['emprunts'] = $this->total_of($this->ecritures_model->select_solde($date_op, 16, 17, TRUE));
        $data['total_passif'] += $data['emprunts'];

        // var_dump($data); exit;
        return $data;
    }

    /**
     * Liste par codec des solde par sections plus total
     *
     * @param array $selection Filtering criteria for selecting accounts
     * @param string $balance_date Date for calculating account balances
     * @param float $factor Multiplication factor for balance values (default: 1)
     * @return array Table of account data with sections and totals
     */
    function select_par_section($selection, $balance_date, $factor = 1, $with_sections = true) {

        // $depenses = $this->comptes_model->list_of('codec >= "6" and codec < "7"', 'codec');
        // $recettes = $this->comptes_model->list_of('codec >= "7" and codec < "8"', 'codec');

        $table = [];
        $title = ["Code", "Comptes"];

        // selectionne les codec de comptes de la selection
        $this->db
            ->select('pcode as codec, pdesc as nom, club')
            ->from('planc, comptes')
            ->where('codec = planc.pcode')
            ->where($selection)->order_by('codec');

        if ($this->sections_model->section() && $with_sections) {
            $this->db->where('club', $this->sections_model->section_id());
        }

        $res = $this->db->group_by('codec')
            ->get()->result_array();

        $sections = $this->sections_model->select_columns('id, nom, description');
        foreach ($sections as $section) {
            $title[] = $section['nom'];
        }
        $title[] = "Total";
        $table[] = $title;

        foreach ($res as $codec) {

            // http://gvv.net/comptes/page/606
            if ($html) {
                $url = controller_url("comptes") . "/page/" . $codec['codec'];
                $anchor = anchor($url, $codec['codec']);

                $row = [$anchor, $codec['nom']];
            } else {
                $row = [$codec['codec'], $codec['nom']];
            }

            $total = 0;
            foreach ($sections as $section) {
                $solde = $this->ecritures_model->solde_compte_general($codec['codec'], $balance_date, "<=", false, $section['id']);

                $total += $solde;
                if ($html) {
                    $row[] = euro($solde * $factor);
                } else {
                    $row[] = number_format((float) $solde * $factor, 2, ",", "");
                }
            }
            if ($html) {
                $row[] = euro($total * $factor);
            } else {
                $row[] = number_format((float) $total * $factor, 2, ",", "");
            }

            $table[] = $row;
        }
        return $table;
    }

    /**
     * Computes the total for a given table with a header
     * 
     * @param array $header The header row for the table
     * @param array $table The table data to compute totals for
     * @return array A row with computed totals for each section
     */
    function compute_total($header, $table) {

        $res = $header;
        $header_count = 2;      // columns to skip tin the table
        $line = 0;
        $sections = $this->sections_model->select_columns('id, nom, description');
        $sections_count = count($sections);

        for ($i = $header_count; $i <= $header_count + $sections_count; $i++) {
            $total = 0.0;
            foreach ($table as $elt) {
                if ($line) {
                    $val = str_replace(',', '.', $elt[$i]);
                    $total += $val;
                }
                $line += 1;
            }
            $res[] = $total;
        }
        return $res;
    }

    /**
     * Computes the financial result by comparing charges and products across different sections
     * 
     * @param array $charges The list of expense entries for each section
     * @param array $produits The list of income/product entries for each section
     * @return array A table representing the financial result with totals per section
     */
    function compute_resultat($charges, $produits, $html = false) {
        $sections = $this->sections_model->select_columns('id, nom, description');
        $sections_count = count($sections);
        $header_count = 1;

        $resultat = [];
        // todo aller chercher la liste des titres dans les sections
        $resultat[] = ['', 'Planeur', 'ULM', 'Avion', 'Général', 'Total'];
        $resultat[] = $this->compute_total(["Total des recettes"], $produits);
        $resultat[] = $this->compute_total(["Total des dépenses"], $charges);

        $total_row = ["Résultat"];

        for ($i = $header_count; $i <= $header_count + $sections_count; $i++) {
            $total = $resultat[1][$i] - $resultat[2][$i];
            $total_row[] = $this->format_currency($total, $html);
        }

        if ($html) {
            for ($i = $header_count; $i <= $header_count + $sections_count; $i++) {
                $resultat[1][$i] = euro($resultat[1][$i]);
                $resultat[2][$i] = euro($resultat[2][$i]);
            }
        }
        $resultat[] = $total_row;
        return $resultat;
    }

    public function format_currency($value, $html = false) {
        $negative = ($value < 0);

        if ($html) {
            $value = euro($value);
            if ($negative) $value = '<p class="text-danger">' . $value . '</p>';
        } else {
            $value = number_format((float) $value, 2, ",", "");
        }
        return $value;
    }

    /**
     * Computes the available financial resources for a given balance date
     * 
     * @param string $balance_date The date for which to calculate available funds
     * @return array A table representing available financial resources by section
     */
    function compute_disponible($balance_date, $html = false) {
        // les sections
        $sections = $this->sections_model->select_columns('id, nom, description');
        $sections_count = count($sections);
        $header_count = 1;

        $date_op = date_ht2db($balance_date);

        // La colonne de gauche
        $title = [""];
        if ($html) {
            // http://gvv.net/comptes/page/512
            $url = controller_url("comptes") . "/page/512/600";
            $banques = [anchor($url, "Comptes de banque et financiers")];

            // http://gvv.net/comptes/page/4/5/1
            $url = controller_url("comptes") . "/page/4/5/1";
            $creances = [anchor($url, "Créances de tiers")];

            // http://gvv.net/comptes/page/4/5/1
            $url = controller_url("comptes") . "/page/4/5/1";
            $dettes_tiers = [anchor($url, "Dettes envers des tiers")];

            // http://gvv.net/comptes/page/16/17/1
            $url = controller_url("comptes") . "/page/16/17/1";
            $emprunts = [anchor($url, "Emprunts bancaires")];

            // https://gvv.planeur-abbeville.fr/index.php/comptes/page/2/28
            $url = controller_url("comptes") . "/page/2/28/1";
            $immos_brutes = [anchor($url, "Valeur brute")]; 

            // https://gvv.planeur-abbeville.fr/index.php/comptes/page/281
            $url = controller_url("comptes") . "/page/281";
            $immos_cumul_amortissements = [anchor($url, "Cumul amortissements")];

            $url = controller_url("comptes") . "/page/274";
            $prets = [anchor($url, "Prêts")];

            $immos_depreciations = ["Dépréciations"];
            $immos_nettes = ["Valeur nette"];

        } else {
            $banques = ["Comptes de banque et financiers"];
            $creances = ["Créances de tiers"];
            $dettes_tiers = ["Dettes envers des tiers"];
            $emprunts = ["Emprunts bancaires"];
            $prets = ["Prêts"];

            $immos_brutes = ["Valeur brute"];
            $immos_cumul_amortissements = ["Cumul amortissements"];
            $immos_depreciations = ["Dépréciations"];
            $immos_nettes = ["Valeur nette"];
        }

        $total_dispo = ["Total des actifs financiers"];
        $total_dettes = ["Total des dettes"];
        $diff_actif_passif = ["Total des actifs - total des dettes"];

        $tot_banque = 0;
        $tot_creances = 0;
        $tot_emprunt = 0;
        $tot_dettes = 0;
        $tot_prets = 0;

        $tot_immos_brutes = 0;
        $tot_immos_cumul_amortissements = 0;
        $tot_immos_depreciations = 0; 
        $tot_immos_nettes = 0; 

        foreach ($sections as $section) {
            // Les colonnes de section
            $title[] = $section['nom'];

            $solde_banque = $this->total_of($this->ecritures_model->select_solde($date_op, 5, 6, TRUE, $section['id'])) * -1;
            $banques[] = $this->format_currency($solde_banque, false);
            $tot_banque += $solde_banque;

            $solde_emprunt = $this->total_of($this->ecritures_model->select_solde($date_op, 16, 17, TRUE, $section['id']));
            $emprunts[] = $this->format_currency($solde_emprunt, false);
            $tot_emprunt += $solde_emprunt;

            $solde_prets = $this->total_of($this->ecritures_model->select_solde($date_op, 274, 275, TRUE, $section['id'])) * - 1;
            $prets[] = $this->format_currency($solde_prets, false);
            $tot_prets += $solde_prets;

            // La liste des comptes de tiers
            $tiers = $this->ecritures_model->select_solde($date_op, 4, 5, FALSE, $section['id']);

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

            $solde_creances = $creances_pilotes;
            $creances[] = $solde_creances;
            $tot_creances += $solde_creances;

            $solde_dette_tiers = $dettes_pilotes;
            $dettes_tiers[] = $solde_dette_tiers;
            $tot_dettes += $solde_dette_tiers;

            $t_dispo = $solde_banque + $solde_creances + $solde_prets;
            $total_dispo[] = $t_dispo;
            $t_dettes = $solde_dette_tiers + $solde_emprunt;
            $total_dettes[] = $t_dettes;
            $diff_actif_passif[] = $t_dispo - $t_dettes;

            $solde_immos_brutes = $this->total_of($this->ecritures_model->select_solde($date_op, 2, 28, TRUE, $section['id'])) * -1;
            $immos_brutes[] = $solde_immos_brutes;
            $tot_immos_brutes += $solde_immos_brutes;

            $solde_immos_cumul_amortissements = $this->total_of($this->ecritures_model->select_solde($date_op, 28, 29, TRUE, $section['id']));
            $immos_cumul_amortissements[] = $solde_immos_cumul_amortissements;
            $tot_immos_cumul_amortissements += $solde_immos_cumul_amortissements;

            $tot_immos_depreciations = 0; 
            
            $solde_immos_nettes = $solde_immos_brutes - $solde_immos_cumul_amortissements;
            $immos_nettes[] = $solde_immos_nettes; 
            $tot_immos_nettes += $solde_immos_nettes;

        }

        // la colonne Total
        $title[] = "Total";
        $banques[] =  $tot_banque;
        $emprunts[] =  $tot_emprunt;
        $prets[] = $tot_prets;

        $creances[] = $tot_creances;
        $dettes_tiers[] = $tot_dettes;

        $total_dispo[] = $tot_banque + $tot_creances;
        $total_dettes[] = $tot_emprunt + $tot_dettes;
        $diff_actif_passif[] = $tot_banque + $tot_creances - $tot_emprunt - $tot_dettes;

        
        $immos_brutes[] = $tot_immos_brutes;
        $immos_cumul_amortissements[] = $tot_immos_cumul_amortissements;
        $immos_nettes[] = $tot_immos_nettes;

        // Mise en forme
        if ($html) {
            for ($i = $header_count; $i <= $header_count + $sections_count; $i++) {
                $prets[$i] = $this->format_currency($prets[$i], $html);
                $banques[$i] = $this->format_currency($banques[$i], $html);
                $creances[$i] = $this->format_currency($creances[$i], $html);
                $total_dispo[$i] = $this->format_currency($total_dispo[$i], $html);

                $dettes_tiers[$i] = $this->format_currency($dettes_tiers[$i], $html);
                $emprunts[$i] = $this->format_currency($emprunts[$i], $html);
                $total_dettes[$i]  = $this->format_currency($total_dettes[$i], $html);
                $diff_actif_passif[$i]  = $this->format_currency($diff_actif_passif[$i], $html);

                $immos_brutes[$i] = $this->format_currency($immos_brutes[$i], $html);
                $immos_cumul_amortissements[$i] = $this->format_currency($immos_cumul_amortissements[$i], $html);
                $immos_nettes[$i] = $this->format_currency($immos_nettes[$i], $html);     
            }
        }

        // ===============================
        $disponible = [];
        $disponible[] = $title;
        $disponible[] = $prets;
        $disponible[] = $banques;
        $disponible[] = $creances;
        $disponible[] = $total_dispo;

        $dettes = [];
        $dettes[] = $title;
        $dettes[] = $emprunts;
        $dettes[] = $dettes_tiers;
        $dettes[] = $total_dettes;
        $dettes[] = $diff_actif_passif;

        $immos =[];
        $immos[] = $title;
        $immos[] = $immos_brutes;
        $immos[] = $immos_cumul_amortissements;
        $immos[] = $immos_nettes; 

        $res = [];
        $res['disponible'] = $disponible;
        $res['dettes'] = $dettes;
        $res['immos'] = $immos;

        // Créances de tiers
        // http://gvv.net/comptes/page/4/5/1

        // Comptes de banque et financiers
        // http://gvv.net/comptes/page/512

        return $res;
    }

    function format_table_html(&$table) {

        $line = 0;
        foreach ($table as $row) {
            if ($line != 0) {
                $url = controller_url("comptes") . "/page/" . $row[0];
                $anchor = anchor($url, $row[0]);
                $table[$line][0] = $anchor;

                for ($i = 2; $i <= 6; $i++) {
                    $table[$line][$i] = euro($table[$line][$i] ); 
                }
            }
            $line++;
        }
    }

    /**
     * Retrieves and processes expense and income entries for a specific balance date
     * 
     * @param string $balance_date The date for which to retrieve financial entries
     * @return array A collection of financial tables including charges, products, results, and available funds
     */
    function select_charges_et_produits($balance_date, $html = false) {

        $this->load->model('comptes_model');

        // listes des comptes de dépenses et de recettes
        $tables = [];
        $tables['charges'] = $this->select_par_section('codec >= "6" and codec < "7"', $balance_date, -1, false, false);
        $tables['produits'] = $this->select_par_section('codec >= "7" and codec < "8"', $balance_date, 1, false, false);

        $tables['resultat'] = $this->compute_resultat($tables['charges'], $tables['produits'], $html);

        if ($html) {
            $this->format_table_html($tables['charges']);
            $this->format_table_html($tables['produits']);
        }

        $dispo = $this->compute_disponible($balance_date, $html);
        $tables['disponible'] = $dispo['disponible'];
        $tables['dettes'] = $dispo['dettes'];

        $tables['immos'] = $dispo['immos'];

        return $tables;
    }
}

/* End of file */