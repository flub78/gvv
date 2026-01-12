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
    // warning_list_of
    public function list_of_account($where = array(), $order = "") {

        $this->db->select('comptes.*, sections.nom as section_name')
            ->from($this->table)
            ->join('sections', 'comptes.club = sections.id', 'left')
            ->where($where)
            ->where('masked', 0);  // Exclude masked accounts
            
        // Only add ORDER BY if $order is not empty
        if (!empty($order)) {
            $this->db->order_by($order);
        }

        $section = $this->sections_model->section();
        if ($section) {
            $this->db->where('comptes.club', $section['id']);
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
    public function select_page($selection = array(), $date, $filter_solde = "", $filter_masked = 1) {

        // selectionne les comptes
        $result = $this->db
            ->select('id, nom, codec, actif, debit, credit, club, masked')
            ->from('comptes, planc')
            ->where($selection)
            ->where('codec = planc.pcode');

        if ($this->sections_model->section()) {
            $this->db->where('club', $this->sections_model->section_id());
        }
        
        // Filtre sur les comptes masqués
        // 0 = Tous, 1 = Non masqués uniquement (défaut), 2 = Masqués uniquement
        if ($filter_masked == 1) {
            $this->db->where('masked', 0);
        } elseif ($filter_masked == 2) {
            $this->db->where('masked', 1);
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
                if ($filter_solde == 3 || $filter_solde == 4) {
                    unset($result[$key]);
                }
            } else if ($row['debit'] < $row['credit']) {
                // Solde créditeur
                $result[$key]['solde_debit'] = '';
                $result[$key]['solde_credit'] = $row['credit'] - $row['debit'];
                if ($filter_solde == 1 || $filter_solde == 4) {
                    unset($result[$key]);
                }
            } else {
                // Solde null
                $result[$key]['solde_debit'] = '';
                $result[$key]['solde_credit'] = $row['credit'] - $row['debit'];
                if ($filter_solde == 1 || $filter_solde == 2 || $filter_solde == 3) {
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
    public function select_page_general($selection = array(), $date, $filter_solde = "", $filter_masked = 1, $with_sections = true) {

        // selectionne les comptes
        $this->db
            ->select('pcode as codec, pdesc as nom, club')
            ->from('planc, comptes')
            ->where('codec = planc.pcode')
            ->where($selection)->order_by('codec');

        if ($this->sections_model->section() && $with_sections) {
            $this->db->where('club', $this->sections_model->section_id());
        }
        
        // Filtre sur les comptes masqués
        // 0 = Tous, 1 = Non masqués uniquement (défaut), 2 = Masqués uniquement
        if ($filter_masked == 1) {
            $this->db->where('comptes.masked', 0);
        } elseif ($filter_masked == 2) {
            $this->db->where('comptes.masked', 1);
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
                if ($filter_solde == 3 || $filter_solde == 4) {
                    unset($result[$key]);
                }
            } else if ($row['debit'] < $row['credit']) {
                // Solde créditeur
                $result[$key]['solde_debit'] = '';
                $result[$key]['solde_credit'] = $row['credit'] - $row['debit'];
                if ($filter_solde == 1 || $filter_solde == 4) {
                    unset($result[$key]);
                }
            } else {
                // Solde null
                $result[$key]['solde_debit'] = '';
                $result[$key]['solde_credit'] = $row['credit'] - $row['debit'];
                if ($filter_solde == 1 || $filter_solde == 2 || $filter_solde == 3) {
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
    public function compte_pilote($pilote, $section = null) {
        $info_pilote = $this->membres_model->get_by_id('mlogin', $pilote);
        $target_section = null;
        if (is_array($section) && isset($section['id'])) {
            $target_section = $section;
        } else if ($section) {
            $target_section = $this->sections_model->get_by_id('id', $section);
        } else {
            $target_section = $this->sections_model->section();
        }

        // Si un membre_payeur est défini, chercher son compte dans la section courante
        if ($info_pilote && isset($info_pilote['membre_payeur']) && $info_pilote['membre_payeur']) {
            $membre_payeur = $info_pilote['membre_payeur'];

            // Chercher le compte 411 de ce membre dans la section courante
            $this->db
                ->select('id, nom, debit, credit, actif, club, pilote')
                ->from($this->table)
                ->where(array(
                    'pilote' => $membre_payeur,
                    'codec' => '411'
                ));

            // Filtrer par section si une section est active
            if ($target_section) {
                $this->db->where('comptes.club', $target_section['id']);
            }

            $result = $this->db->get();

            if ($result && $result->num_rows() > 0) {
                return $result->row_array();
            } else {
                // Aucun compte trouvé pour le membre payeur dans la section courante
                if ($target_section) {
                    gvv_error("Aucun compte 411 trouvé pour le membre payeur '$membre_payeur' dans la section " . $target_section['nom']);
                } else {
                    gvv_error("Aucun compte 411 trouvé pour le membre payeur '$membre_payeur' (aucune section active)");
                }
                return null;
            }
        }

        // Pas de membre_payeur défini, chercher le compte du pilote lui-même
        $this->db
            ->select('id, nom, debit, credit, actif, club, pilote')
            ->from($this->table)
            ->where(array(
                'pilote' => $pilote,
                'codec' => '411'
            ));

        // Filtrer par section si une section est active
        if ($target_section) {
            $this->db->where('comptes.club', $target_section['id']);
        }

        $result = $this->db->get();

        if ($result && $result->num_rows() > 0) {
            return $result->row_array();
        } else {
            if ($target_section) {
                gvv_error("Aucun compte 411 trouvé pour le pilote $pilote dans la section " . $target_section['nom']);
            } else {
                gvv_error("Aucun compte 411 trouvé pour le pilote $pilote (aucune section active)");
            }
            return null;
        }
    }

    /**
     * Retourne l'identifiant du compte d'un pilote
     *
     * @param
     *            $pilote
     */
    public function compte_pilote_id($pilote, $section = null) {
        $res = $this->compte_pilote($pilote, $section);
        return $res['id'];
    }

    /**
     * Retourne le solde du compte d'un pilote
     *
     * @param
     *            $pilote
     */
    public function solde_pilote($pilote, $section = null) {
        $compte_id = $this->compte_pilote_id($pilote, $section);
        $solde = $this->ecritures_model->solde_compte($compte_id);
        return $solde;
    }

    /**
     * Retourne le solde d'un compte
     *
     * @param int $compte_id ID du compte
     * @return float Solde du compte (débit - crédit)
     */
    public function solde($compte_id) {
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
    public function has_compte($user, $section = null) {

        return $this->pilot_account($user, $section);
    }


    public function pilot_account($user, $section = null) {
        $target_section = null;
        if (is_array($section) && isset($section['id'])) {
            $target_section = $section;
        } else if ($section) {
            $target_section = $this->sections_model->get_by_id('id', $section);
        } else {
            $target_section = $this->sections_model->section();
        }

        $this->db
            ->select('id, nom, codec, desc, actif, debit, credit, club')
            ->from('comptes')
            ->where("pilote = '$user'");

        // On ne retourne rien s'il n'y a pas de section active
        if ($target_section) {
            $this->db->where('club', $target_section['id']);
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
     * Retourne tous les comptes 411 d'un pilote, toutes sections confondues
     *
     * @param string $pilote Login du pilote
     * @return array Liste des comptes avec informations de section
     */
    public function get_pilote_comptes($pilote) {
        $info_pilote = $this->membres_model->get_by_id('mlogin', $pilote);
        $account_owner = ($info_pilote && isset($info_pilote['membre_payeur']) && $info_pilote['membre_payeur'])
            ? $info_pilote['membre_payeur']
            : $pilote;

        $this->db->select('comptes.id, comptes.nom, comptes.club, sections.nom as section_name, sections.acronyme as section_acronym');
        $this->db->from($this->table);
        $this->db->join('sections', 'comptes.club = sections.id', 'left');
        $this->db->where('comptes.pilote', $account_owner);
        $this->db->where('comptes.codec', '411');
        $this->db->where('comptes.actif', 1);
        $this->db->where('comptes.masked', 0);
        $this->db->order_by('comptes.club', 'ASC');

        $result = $this->db->get();
        if ($result) {
            return $result->result_array();
        }
        return [];
    }

    /**
     * Vérifie qu'un pilote possède un compte 411 dans une section donnée
     *
     * @param string $pilote Login du pilote
     * @param int $section_id Identifiant de la section
     * @return bool
     */
    public function has_compte_in_section($pilote, $section_id) {
        $info_pilote = $this->membres_model->get_by_id('mlogin', $pilote);
        $account_owner = ($info_pilote && isset($info_pilote['membre_payeur']) && $info_pilote['membre_payeur'])
            ? $info_pilote['membre_payeur']
            : $pilote;

        $this->db->from($this->table);
        $this->db->where('pilote', $account_owner);
        $this->db->where('codec', '411');
        $this->db->where('club', $section_id);
        $this->db->where('actif', 1);
        $this->db->where('masked', 0);

        return $this->db->count_all_results() > 0;
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
     * @param bool $with_sections Whether to filter by selected section
     * @param bool $html Whether to create HTML links
     * @param bool $use_full_names Whether to use full section names instead of acronyms in headers
     * @return array Table of account data with sections and totals
     */
    function select_par_section($selection, $balance_date, $factor = 1, $with_sections = true, $html = false, $use_full_names = true, $sections = null) {

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

        // Use provided sections list or fall back to all sections
        if ($sections === null) {
            $sections = $this->sections_model->section_list();
        }
        
        $section_field = $use_full_names ? 'nom' : 'acronyme';
        foreach ($sections as $section) {
            $title[] = $section[$section_field];
        }
        // Only add "Total Club" column if there are 2 or more sections
        if (count($sections) >= 2) {
            $title[] = "Total Club";
        }
        $table[] = $title;

        foreach ($res as $codec) {

            // http://gvv.net/comptes/page/606
            if ($html) {
                $url = site_url('comptes/resultat_par_sections_detail/' . $codec['codec']);
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
            // Only add total column if there are 2 or more sections
            if (count($sections) >= 2) {
                if ($html) {
                    $row[] = euro($total * $factor);
                } else {
                    $row[] = number_format((float) $total * $factor, 2, ",", "");
                }
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
        $sections = $this->sections_model->section_list();
        $sections_count = count($sections);

        // Calculate the correct column range based on whether we have "Total Club" column
        $max_col_index = $header_count + $sections_count;
        if ($sections_count < 2) {
            // If less than 2 sections, don't add the "Total Club" column
            $max_col_index = $header_count + $sections_count - 1;
        }

        for ($i = $header_count; $i <= $max_col_index; $i++) {
            $total = 0.0;
            foreach ($table as $elt) {
                if ($line) {
                    if (isset($elt[$i])) {
                        $val = str_replace(',', '.', $elt[$i]);
                        $total += $val;
                    }
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
     * @param bool $html Whether to format for HTML display
     * @param bool $use_full_names Whether to use full section names instead of acronyms in headers
     * @return array A table representing the financial result with totals per section
     */
    function compute_resultat($charges, $produits, $html = false, $use_full_names = true) {
        $sections = $this->sections_model->section_list();
        $sections_count = count($sections);
        $header_count = 1;
        $section_field = $use_full_names ? 'nom' : 'acronyme';

        $resultat = [];

        // Construction de l'en-tête avec les vrais noms des sections depuis la base de données
        $title = [''];
        foreach ($sections as $section) {
            $title[] = $section[$section_field];
        }
        // Only add "Total Club" column if there are 2 or more sections
        if ($sections_count >= 2) {
            $title[] = "Total Club";
        }
        $resultat[] = $title;
        $resultat[] = $this->compute_total(["Total des recettes"], $produits);
        $resultat[] = $this->compute_total(["Total des dépenses"], $charges);

        $total_row = ["Résultat"];

        // Calculate the correct column range based on whether we added "Total Club"
        $max_col_index = $header_count + $sections_count;
        if ($sections_count < 2) {
            // If less than 2 sections, don't access the "Total Club" column index
            $max_col_index = $header_count + $sections_count - 1;
        }

        for ($i = $header_count; $i <= $max_col_index; $i++) {
            if (isset($resultat[1][$i]) && isset($resultat[2][$i])) {
                $total = $resultat[1][$i] - $resultat[2][$i];
                $total_row[] = $this->format_currency($total, $html);
            }
        }

        if ($html) {
            for ($i = $header_count; $i <= $max_col_index; $i++) {
                if (isset($resultat[1][$i])) {
                    $resultat[1][$i] = euro($resultat[1][$i]);
                }
                if (isset($resultat[2][$i])) {
                    $resultat[2][$i] = euro($resultat[2][$i]);
                }
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
     * @param bool $html Whether to format for HTML display
     * @param bool $use_full_names Whether to use full section names instead of acronyms in headers
     * @return array A table representing available financial resources by section
     */
    function compute_disponible($balance_date, $html = false, $use_full_names = true) {
        // les sections
        $sections = $this->sections_model->section_list();
        $sections_count = count($sections);
        $header_count = 1;
        $section_field = $use_full_names ? 'nom' : 'acronyme';

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
            $title[] = $section[$section_field];

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

        // la colonne Total - Only add "Total Club" column if there are 2 or more sections
        if ($sections_count >= 2) {
            $title[] = "Total Club";
            $banques[] =  $tot_banque;
            $emprunts[] =  $tot_emprunt;
            $prets[] = $tot_prets;

            $creances[] = $tot_creances;
            $dettes_tiers[] = $tot_dettes;

            $total_dispo[] = $tot_banque + $tot_creances + $tot_prets;
            $total_dettes[] = $tot_emprunt + $tot_dettes;
            $diff_actif_passif[] = $tot_banque + $tot_creances + $tot_prets - $tot_emprunt - $tot_dettes;

            
            $immos_brutes[] = $tot_immos_brutes;
            $immos_cumul_amortissements[] = $tot_immos_cumul_amortissements;
            $immos_nettes[] = $tot_immos_nettes;
        }

        // Mise en forme
        if ($html) {
            // Calculate the number of column indices to format
            $max_col_index = $header_count + $sections_count;
            if ($sections_count < 2) {
                // If less than 2 sections, don't access the "Total Club" column index
                $max_col_index = $header_count + $sections_count - 1;
            }
            
            for ($i = $header_count; $i <= $max_col_index; $i++) {
                if (isset($prets[$i])) {
                    $prets[$i] = $this->format_currency($prets[$i], $html);
                }
                if (isset($banques[$i])) {
                    $banques[$i] = $this->format_currency($banques[$i], $html);
                }
                if (isset($creances[$i])) {
                    $creances[$i] = $this->format_currency($creances[$i], $html);
                }
                if (isset($total_dispo[$i])) {
                    $total_dispo[$i] = $this->format_currency($total_dispo[$i], $html);
                }

                if (isset($dettes_tiers[$i])) {
                    $dettes_tiers[$i] = $this->format_currency($dettes_tiers[$i], $html);
                }
                if (isset($emprunts[$i])) {
                    $emprunts[$i] = $this->format_currency($emprunts[$i], $html);
                }
                if (isset($total_dettes[$i])) {
                    $total_dettes[$i]  = $this->format_currency($total_dettes[$i], $html);
                }
                if (isset($diff_actif_passif[$i])) {
                    $diff_actif_passif[$i]  = $this->format_currency($diff_actif_passif[$i], $html);
                }

                if (isset($immos_brutes[$i])) {
                    $immos_brutes[$i] = $this->format_currency($immos_brutes[$i], $html);
                }
                if (isset($immos_cumul_amortissements[$i])) {
                    $immos_cumul_amortissements[$i] = $this->format_currency($immos_cumul_amortissements[$i], $html);
                }
                if (isset($immos_nettes[$i])) {
                    $immos_nettes[$i] = $this->format_currency($immos_nettes[$i], $html);
                }
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
                $url = site_url('comptes/resultat_par_sections_detail/' . $row[0]);
                $anchor = anchor($url, $row[0]);
                $table[$line][0] = $anchor;

                // Format all numeric columns (from index 2 to last column)
                for ($i = 2; $i < count($row); $i++) {
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
     * @param bool $html Whether to format for HTML display
     * @param bool $use_full_names Whether to use full section names instead of acronyms in headers
     * @return array A collection of financial tables including charges, products, results, and available funds
     */
    function select_charges_et_produits($balance_date, $html = false, $use_full_names = true) {

        $this->load->model('comptes_model');

        // listes des comptes de dépenses et de recettes
        $tables = [];
        $tables['charges'] = $this->select_par_section('codec >= "6" and codec < "7"', $balance_date, -1, false, false, $use_full_names);
        $tables['produits'] = $this->select_par_section('codec >= "7" and codec < "8"', $balance_date, 1, false, false, $use_full_names);

        $tables['resultat'] = $this->compute_resultat($tables['charges'], $tables['produits'], $html, $use_full_names);

        if ($html) {
            $this->format_table_html($tables['charges']);
            $this->format_table_html($tables['produits']);
        }

        $dispo = $this->compute_disponible($balance_date, $html, $use_full_names);
        $tables['disponible'] = $dispo['disponible'];
        $tables['dettes'] = $dispo['dettes'];

        $tables['immos'] = $dispo['immos'];

        return $tables;
    }

    /**
     * Retourne un sélecteur basé sur les comptes 411 de la section active
     * Utilisé pour la sélection du payeur dans les formulaires de vol
     * 
     * @return array Selector avec les comptes 411 de la section active
     */
    public function payeur_selector_with_null() {
        $selector = ['' => '-- Sélectionner --'];
        
        // Obtenir les comptes 411 de la section active, triés par codec puis par nom
        $pilot_accounts = $this->list_of_account([
            'codec LIKE' => '411%'
        ], 'codec, nom');
        
        foreach ($pilot_accounts as $account) {
            if (!empty($account['pilote'])) {
                // Clé = ID du compte, Valeur = (code) Nom du compte
                $selector[$account['id']] = "({$account['codec']}) {$account['nom']}";
            }
        }
        
        return $selector;
    }

    /**
     * Retourne un sélecteur des comptes clients (411) d'une section spécifique
     * Filtre en fonction des membres actifs ou non
     * 
     * @param int $section_id L'ID de la section (0 = section active)
     * @param bool $only_actif true pour ne retourner que les comptes de membres actifs, false pour tous
     * @return array Sélecteur avec les comptes au format [id => (codec) nom]
     */
    public function section_client_accounts($section_id = 0, $only_actif = true) {
        $selector = array('' => '-- Sélectionner --');

        // Détermine la section à utiliser
        if ($section_id == 0) {
            $section_id = $this->section_id();
        }

        // Requête de base pour les comptes 411 de la section
        $this->db->select('comptes.id, comptes.codec, comptes.nom, comptes.pilote');
        $this->db->from('comptes');
        $this->db->where('comptes.codec LIKE', '411%');
        $this->db->where('comptes.club', $section_id);
        $this->db->where('comptes.actif', 1);
        $this->db->where('comptes.masked', 0);

        // Si on filtre sur les membres actifs, on joint la table membres
        if ($only_actif) {
            $this->db->join('membres', 'membres.mlogin = comptes.pilote', 'inner');
            $this->db->where('membres.actif', 1);
        }

        $this->db->order_by('comptes.codec', 'ASC');
        $this->db->order_by('comptes.nom', 'ASC');

        $query = $this->db->get();

        if ($query && $query->num_rows() > 0) {
            foreach ($query->result_array() as $account) {
                if (!empty($account['pilote'])) {
                    // Clé = ID du compte, Valeur = (code) Nom du compte
                    $selector[$account['id']] = "({$account['codec']}) {$account['nom']}";
                }
            }
        }

        return $selector;
    }

    /**
     * Override parent selector to exclude masked accounts
     * 
     * @param array $where Additional where conditions
     * @param string $order Sort order (asc/desc)
     * @param bool $filter_section Filter by section
     * @return array Selector array excluding masked accounts
     */
    public function selector($where = array(), $order = "asc", $filter_section = FALSE) {
        // Add condition to exclude masked accounts
        $where['masked'] = 0;
        
        // Call parent selector with updated where clause
        return parent::selector($where, $order, $filter_section);
    }

    /**
     * Override parent selector_with_all to exclude masked accounts
     * 
     * @param array $where Additional where conditions
     * @param bool $filter_section Filter by section
     * @return array Selector array with "All" option, excluding masked accounts
     */
    public function selector_with_all($where = array(), $filter_section = FALSE) {
        // Add condition to exclude masked accounts
        $where['masked'] = 0;
        
        // Call parent selector_with_all with updated where clause
        return parent::selector_with_all($where, $filter_section);
    }

    /**
     * Override parent selector_with_null to exclude masked accounts
     *
     * @param array $where Additional where conditions
     * @param bool $filter_section Filter by section
     * @return array Selector array with null option, excluding masked accounts
     */
    public function selector_with_null($where = array(), $filter_section = FALSE) {
        // Add condition to exclude masked accounts
        $where['masked'] = 0;

        // Call parent selector_with_null with updated where clause
        return parent::selector_with_null($where, $filter_section);
    }

    /**
     * Retourne un sélecteur de comptes pilotes (411) avec pilote associé
     *
     * @param bool $filter_section Filter by section
     * @return array Selector array for pilot accounts (411) with associated pilot
     */
    public function selector_comptes_411($filter_section = TRUE) {
        $where = array(
            'codec' => '411',
            'pilote !=' => NULL,
            'pilote !=' => ''
        );

        return $this->selector_with_null($where, $filter_section);
    }

    /**
     * Retourne un sélecteur de comptes banque (512)
     *
     * @param bool $filter_section Filter by section
     * @return array Selector array for bank accounts (512)
     */
    public function selector_comptes_512($filter_section = TRUE) {
        $where = array(
            'codec >=' => '512',
            'codec <' => '513'
        );

        return $this->selector_with_null($where, $filter_section);
    }


    /**
     * Liste par codec des soldes par sections pour deux années consécutives
     * Retourne les données brutes (float) sans formatage pour permettre les calculs.
     * Le formatage doit être fait après les calculs via format_numeric_columns().
     *
     * @param string $selection Filtering criteria for selecting accounts
     * @param string $balance_date Date for calculating account balances (format: DD/MM/YYYY)
     * @param float $factor Multiplication factor for balance values (default: 1)
     * @param bool $with_sections Whether to filter by selected section
     * @param bool $html Whether to create HTML links for codes (formatting is done separately)
     * @param bool $use_full_names Whether to use full section names instead of acronyms in headers
     * @return array Table with raw float data for current year and previous year side by side
     */
    function select_par_section_deux_annees($selection, $balance_date, $factor = 1, $with_sections = true, $html = false, $use_full_names = false) {
        // Détermine les années et bornes de période (accepte JJ/MM/AAAA ou AAAA-MM-JJ)
        $year_current = 0;
        $date_parts = explode('/', $balance_date);
        if (count($date_parts) == 3) {
            $year_current = intval($date_parts[2]);
        } else {
            $date_parts_dash = explode('-', $balance_date);
            if (count($date_parts_dash) == 3) {
                // format AAAA-MM-JJ
                $year_current = intval($date_parts_dash[0]);
            }
        }
        if ($year_current <= 0) {
            $year_current = intval(date('Y'));
        }
        $year_prev = $year_current - 1;

        $end_current_db = $year_current . '-12-31';
        $end_prev_db = $year_prev . '-12-31';

        // Récupère la liste des codecs et noms (sans montants)
        $this->db
            ->select('pcode as codec, pdesc as nom, club')
            ->from('planc, comptes')
            ->where('codec = planc.pcode')
            ->where($selection)
            ->order_by('codec');

        if ($this->sections_model->section() && $with_sections) {
//            $this->db->where('club', $this->sections_model->section_id());
        }

        $res = $this->db->group_by('codec')->get()->result_array();

        $sections = $this->sections_model->section_list();
        $sections_count = count($sections);

        // En-tête avec années groupées par section (N et N-1 adjacentes)
        $header = ['Code', 'Comptes'];
        $section_field = $use_full_names ? 'nom' : 'acronyme';
        foreach ($sections as $section) {
            $header[] = $section[$section_field] . ' ' . $year_current;
            $header[] = $section[$section_field] . ' ' . $year_prev;
        }
        // Only add Total Club columns if there are 2 or more sections
        if ($sections_count > 1) {
            $header[] = 'Total Club ' . $year_current;
            $header[] = 'Total Club ' . $year_prev;
        }

        $table = [$header];

        foreach ($res as $codec_row) {
            $codec = $codec_row['codec'];
            $row_label = $codec_row['nom'];

            // Cellule code (avec lien si HTML)
            if ($html) {
                $url = controller_url("comptes") . "/resultat_par_sections_detail/" . $codec;
                $code_cell = anchor($url, $codec);
            } else {
                $code_cell = $codec;
            }

            $row = [$code_cell, $row_label];

            $total_current = 0.0;
            $total_prev = 0.0;

            // Montants par section : année N et N-1 adjacentes pour chaque section
            $is_charge = (substr($codec, 0, 1) == '6');
            foreach ($sections as $section) {
                $sid = $section['id'];
                $amount_current_raw = $this->year_amount_codec_section($codec, $year_current, $sid, $is_charge);
                $amount_prev_raw = $this->year_amount_codec_section($codec, $year_prev, $sid, $is_charge);

                $amount_current = $amount_current_raw * $factor;
                $amount_prev = $amount_prev_raw * $factor;

                $total_current += $amount_current;
                $total_prev += $amount_prev;

                // Année N puis année N-1 pour cette section
                $row[] = $amount_current;
                $row[] = $amount_prev;
            }

            // Totaux club : année N puis année N-1 (only if 2+ sections)
            if ($sections_count > 1) {
                $row[] = $total_current;
                $row[] = $total_prev;
            }

            $table[] = $row;
        }

        return $table;
    }

    /**
     * Calcule le montant d'un codec pour une section et une année, en suivant la logique de /comptes/resultat.
     * Charges (6xx): débit sur compte1 (codec=6xx) moins crédit sur compte2 (codec=6xx).
     * Produits (7xx): crédit sur compte2 (codec=7xx) moins débit sur compte1 (codec=7xx).
     *
     * Utilise ecritures_model::solde_compte_gestion() qui a été validé par les tests phpunit.
     */
    private function year_amount_codec_section($codec, $year, $section_id, $is_charge) {
        $date_op = "$year-12-31";

        return $this->ecritures_model->solde_compte_gestion(
            $date_op,
            '',         // pas de compte spécifique
            $codec,     // codec_min
            $codec,     // codec_max (même valeur pour un seul codec)
            $section_id
        );
    }

    /**
     * Calcule le montant d'un compte spécifique pour une section et une année.
     * Utilise l'ID du compte pour un calcul précis par compte.
     *
     * @param int $compte_id ID du compte
     * @param int $year Année
     * @param int $section_id ID de la section
     * @return float Le montant calculé
     */
    private function year_amount_compte_section($compte_id, $year, $section_id) {
        $date_op = "$year-12-31";

        return $this->ecritures_model->solde_compte_gestion(
            $date_op,
            $compte_id,  // ID du compte spécifique
            '',          // pas de filtre par codec
            '',          // pas de filtre par codec
            $section_id
        );
    }

    /**
     * Diagnostic: compare la somme des montants par section (courant et précédent) pour un codec
     * avec les totaux calculés par /comptes/resultat (select_depenses/select_recettes).
     * Ecrit des logs via gvv_debug pour aider à identifier les écarts.
     */
    public function run_sections_diagnostic($codec, $year_current) {
        $this->load->model('ecritures_model');
        $sections = $this->sections_model->section_list();
        $is_charge = (intval($codec) >= 600 && intval($codec) < 700);

        $sum_sections_current = 0.0;
        foreach ($sections as $section) {
            $sum_sections_current += $this->year_amount_codec_section($codec, $year_current, $section['id'], $is_charge);
        }

        // Totaux côté /comptes/resultat
        $date_op = $year_current . '-12-31';
        if ($is_charge) {
            $charges = $this->ecritures_model->select_depenses($year_current, 'compte1', $date_op);
            // somme des montants pour le codec
            $total_current = 0.0;
            foreach ($charges as $row) {
                if (isset($row['code']) && $row['code'] == $codec) {
                    $total_current += floatval($row['montant']);
                }
            }
        } else {
            $produits = $this->ecritures_model->select_recettes($year_current, 'compte2', $date_op);
            $total_current = 0.0;
            foreach ($produits as $row) {
                if (isset($row['code']) && $row['code'] == $codec) {
                    $total_current += floatval($row['montant']);
                }
            }
        }

        gvv_debug("DIAG codec=$codec year=$year_current sum_sections_current=" . $sum_sections_current . " total_resultat_current=" . $total_current);

        // Année précédente
        $year_prev = $year_current - 1;
        $sum_sections_prev = 0.0;
        foreach ($sections as $section) {
            $sum_sections_prev += $this->year_amount_codec_section($codec, $year_prev, $section['id'], $is_charge);
        }
        $date_op_prev = $year_prev . '-12-31';
        if ($is_charge) {
            $charges_prev = $this->ecritures_model->select_depenses($year_prev, 'compte1', $date_op_prev);
            $total_prev = 0.0;
            foreach ($charges_prev as $row) {
                if (isset($row['code']) && $row['code'] == $codec) {
                    $total_prev += floatval($row['montant']);
                }
            }
        } else {
            $produits_prev = $this->ecritures_model->select_recettes($year_prev, 'compte2', $date_op_prev);
            $total_prev = 0.0;
            foreach ($produits_prev as $row) {
                if (isset($row['code']) && $row['code'] == $codec) {
                    $total_prev += floatval($row['montant']);
                }
            }
        }

        gvv_debug("DIAG codec=$codec year_prev=$year_prev sum_sections_prev=" . $sum_sections_prev . " total_resultat_prev=" . $total_prev);
    }


    /**
     * Formate les colonnes numériques d'une table
     * Applique le formatage monétaire aux colonnes à partir de $start_col
     *
     * @param array $table Table avec des valeurs float dans les colonnes numériques
     * @param int $start_col Index de la première colonne numérique à formater (défaut: 2 pour Code, Comptes)
     * @param bool $format_html Si true, formate avec euro() pour HTML, sinon avec number_format pour CSV/PDF
     * @return array Copie de la table avec les valeurs formatées
     */
    function format_numeric_columns($table, $start_col = 2, $format_html = false) {
        $formatted_table = [];

        foreach ($table as $row_index => $row) {
            $formatted_row = [];

            foreach ($row as $col_index => $cell_value) {
                if ($col_index >= $start_col && $row_index > 0) {
                    // Colonne numérique (pas l'en-tête)
                    if ($format_html) {
                        $formatted_row[] = euro($cell_value);
                    } else {
                        $formatted_row[] = number_format((float) $cell_value, 2, ",", " ");
                    }
                } else {
                    // En-tête ou colonnes textuelles (Code, Comptes)
                    $formatted_row[] = $cell_value;
                }
            }

            $formatted_table[] = $formatted_row;
        }

        return $formatted_table;
    }

    /**
     * Récupère les charges, produits et résultats par sections pour deux années consécutives
     * Les calculs sont effectués sur les données brutes (float), puis formatés selon le mode d'affichage.
     *
     * @param string $balance_date Date for calculating account balances (format: DD/MM/YYYY)
     * @param bool $html Whether to format for HTML display
     * @param bool $use_full_names Whether to use full section names instead of acronyms in headers
     * @return array Array with 'charges', 'produits', and 'resultat' tables for two years
     */
    function select_resultat_par_sections_deux_annees($balance_date, $html = false, $use_full_names = false) {
        $this->load->model('comptes_model');

        // Récupération des charges et produits pour deux années (données brutes en float)
        // Les charges sont affichées en positif (facteur = 1) pour la présentation
        // Le paramètre $html est utilisé uniquement pour créer les liens dans la colonne Code
        $tables = [];
        $tables['charges'] = $this->select_par_section_deux_annees('codec >= "6" and codec < "7"', $balance_date, 1, false, $html, $use_full_names);
        $tables['produits'] = $this->select_par_section_deux_annees('codec >= "7" and codec < "8"', $balance_date, 1, false, $html, $use_full_names);

        // Calcul du résultat pour les deux années (sur les données brutes)
        $tables['resultat'] = $this->compute_resultat_deux_annees($tables['charges'], $tables['produits']);

        // Formatage des colonnes numériques après les calculs
        $tables['charges'] = $this->format_numeric_columns($tables['charges'], 2, $html);
        $tables['produits'] = $this->format_numeric_columns($tables['produits'], 2, $html);
        $tables['resultat'] = $this->format_numeric_columns($tables['resultat'], 2, $html); // Ne pas formater la colonne 1 (Charges/Produits/Total)

        return $tables;
    }

    /**
     * Calcule le résultat (produits - charges) pour deux années
     * Travaille sur des données brutes (float) et retourne des float.
     * Le formatage doit être fait après via format_numeric_columns().
     *
     * @param array $charges Table des charges pour deux années (contient des float)
     * @param array $produits Table des produits pour deux années (contient des float)
     * @return array Table du résultat avec les totaux par section pour deux années (en float)
     */
    function compute_resultat_deux_annees($charges, $produits) {
        $sections = $this->sections_model->section_list();
        $sections_count = count($sections);

        $resultat = [];

        // Construction de l'en-tête (identique aux charges/produits)
        if (!empty($produits)) {
            $resultat[] = $produits[0]; // En-tête
        }

        // Calcul des totaux pour chaque année
        // La structure dépend du nombre de sections:
        // Si sections > 1: [Code, Comptes, Sections_Année1..., Total_Année1, Sections_Année2..., Total_Année2]
        // Si sections <= 1: [Code, Comptes, Sections_Année1..., Sections_Année2...]
        // Nombre de colonnes de sections (+ total si > 1 section) pour une année
        $cols_per_year = ($sections_count > 1) ? ($sections_count + 1) : $sections_count;
        $header_offset = 2; // Code + Comptes

        // Calculer les totaux par section ET les totaux globaux
        $total_charges = ["", "Charges"];
        $total_produits = ["", "Produits"];
        $total_resultat = ["", "Total"];

        // Pour chaque colonne de section (+ total si applicable)
        for ($i = 0; $i < $cols_per_year * 2; $i++) {
            $col_index = $header_offset + $i;

            // Somme des charges pour cette colonne (directement sur les float)
            $sum_charges = 0.0;
            for ($row = 1; $row < count($charges); $row++) {
                if (isset($charges[$row][$col_index])) {
                    $sum_charges += floatval($charges[$row][$col_index]);
                }
            }
            $total_charges[] = $sum_charges;

            // Somme des produits pour cette colonne (directement sur les float)
            $sum_produits = 0.0;
            for ($row = 1; $row < count($produits); $row++) {
                if (isset($produits[$row][$col_index])) {
                    $sum_produits += floatval($produits[$row][$col_index]);
                }
            }
            $total_produits[] = $sum_produits;

            // Résultat pour cette colonne
            $sum_resultat = $sum_produits - $sum_charges;
            $total_resultat[] = $sum_resultat;
        }

        $resultat[] = $total_charges;
        $resultat[] = $total_produits;
        $resultat[] = $total_resultat;

        return $resultat;
    }


    /**
     * Liste détaillée des comptes d'un codec par sections pour deux années consécutives
     * Retourne les données brutes (float) sans formatage pour permettre les calculs.
     * Le formatage doit être fait après via format_numeric_columns().
     *
     * @param string $codec Le codec dont on veut le détail (ex: "606", "701")
     * @param string $balance_date Date for calculating account balances (format: DD/MM/YYYY)
     * @param float $factor Multiplication factor for balance values (default: 1)
     * @param bool $use_full_names Whether to use full section names instead of acronyms in headers
     * @return array Table with raw float data for detailed accounts of the codec for two years
     */
    function select_detail_codec_deux_annees($codec, $balance_date, $factor = 1, $use_full_names = false) {
        // Années et bornes de périodes (accepte JJ/MM/AAAA ou AAAA-MM-JJ)
        $year_current = 0;
        $date_parts = explode('/', $balance_date);
        if (count($date_parts) == 3) {
            $year_current = intval($date_parts[2]);
        } else {
            $date_parts_dash = explode('-', $balance_date);
            if (count($date_parts_dash) == 3) {
                $year_current = intval($date_parts_dash[0]);
            }
        }
        if ($year_current <= 0) {
            $year_current = intval(date('Y'));
        }
        $year_prev = $year_current - 1;

        // Récupération des comptes du codec avec leur section
        $this->db
            ->select('comptes.id, comptes.codec, comptes.nom, comptes.club, sections.nom as section_nom, sections.acronyme as section_acronyme')
            ->from('comptes')
            ->join('sections', 'comptes.club = sections.id', 'left')
            ->where('comptes.codec', $codec)
            ->order_by('sections.ordre_affichage, sections.nom, comptes.codec, comptes.nom');
        $res = $this->db->get()->result_array();

        // En-tête simplifié : Code, Libellé, Section, N, N-1
        $header = ["Code", "Libellé", "Section", $year_current, $year_prev];
        $table = [$header];

        foreach ($res as $compte) {
            $compte_id = $compte['id'];
            $section_id = $compte['club'];
            
            // Nom de section selon préférence
            $section_field = $use_full_names ? 'section_nom' : 'section_acronyme';
            $section_name = $compte[$section_field] ?? '';

            // Calcul des montants pour l'année N et N-1
            $amount_current_raw = $this->year_amount_compte_section($compte_id, $year_current, $section_id);
            $amount_prev_raw = $this->year_amount_compte_section($compte_id, $year_prev, $section_id);

            $amount_current = $amount_current_raw * $factor;
            $amount_prev = $amount_prev_raw * $factor;

            // Ligne simplifiée : Code, Libellé, compte_id (caché), Section, N, N-1
            $row = [
                $compte['codec'],
                $compte['nom'],
                $compte_id,  // ID for creating links (will be hidden in display)
                $section_name,
                $amount_current,
                $amount_prev
            ];

            $table[] = $row;
        }

        return $table;
    }

    /**
     * Liste détaillée des comptes d'un codec par sections pour une année
     * 
     * @param string $codec Le codec dont on veut le détail (ex: "606", "701")
     * @param string $balance_date Date for calculating account balances
     * @param float $factor Multiplication factor for balance values
     * @param bool $html Whether to format for HTML display
     * @return array Table with detailed accounts for the codec
     */
    function select_detail_codec($codec, $balance_date, $factor = 1, $html = false) {
        $table = [];
        $title = ["Code", "Libellé"];
        
        // Sélection de tous les comptes du codec spécifié
        $this->db
            ->select('comptes.id, comptes.codec, comptes.nom, comptes.club')
            ->from('comptes')
            ->where('codec', $codec)
            ->order_by('codec, nom');
        
        $res = $this->db->get()->result_array();
        
        // Construction de l'en-tête avec les sections
        $sections = $this->sections_model->section_list();
        foreach ($sections as $section) {
            $title[] = $section['acronyme'];
        }
        $title[] = "Total Club";
        $table[] = $title;
        
        // Pour chaque compte du codec
        foreach ($res as $compte) {
            $row = [$compte['codec'], $compte['nom']];
            
            $total = 0;
            foreach ($sections as $section) {
                // Calcul du solde pour cette section
                $solde = $this->ecritures_model->solde_compte($compte['id'], $balance_date, "<=", false, $section['id']);
                
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
     * Retourne un sélecteur de comptes recette cotisation (700-708)
     *
     * @param bool $filter_section Filter by section
     * @return array Selector array for membership revenue accounts (700-708)
     */
    public function selector_comptes_700($filter_section = TRUE) {
        $where = array(
            'codec >=' => '700',
            'codec <' => '709'
        );

        return $this->selector_with_null($where, $filter_section);
    }
}

/* End of file */