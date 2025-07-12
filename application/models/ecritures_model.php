<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');
$CI->load->model('sections_model');

/**
 * Ecritures model
 *
 * C'est un CRUD de base, la seule chose que fait cette classe
 * est de définir le nom de la table. Tous les méthodes sont
 * implémentés dans Common_Model
 */
class Ecritures_model extends Common_Model {
    public $table = 'ecritures';
    protected $primary_key = 'id';

    public function __construct() {
        parent::__construct();
        $this->load->model('clotures_model');
    }

    /**
     * Génère le filtre des écritures à prendre en compte
     *
     * @param $when =
     *            before | after
     * @param $individual TRUE
     *            pour compte individuel, FALSE pour groupe de comptes
     */
    function filtrage($when = '', $individual = FALSE) {
        $selection = "";
        $year = $this->session->userdata('year');
        $selection = "YEAR(date_op) = \"$year\"";
        if ($this->session->userdata('filter_active')) {

            $filter_date = $this->session->userdata('filter_date');
            $date_end = $this->session->userdata('date_end');
            $filter_code1 = $this->session->userdata('filter_code1');
            $code1_end = $this->session->userdata('code1_end');
            $filter_code2 = $this->session->userdata('filter_code2');
            $code2_end = $this->session->userdata('code2_end');
            $montant_min = $this->session->userdata('montant_min');
            $montant_max = $this->session->userdata('montant_max');
            $filter_checked = $this->session->userdata('filter_checked');

            if ($filter_date) {
                // When a start date is selected

                if ($when == 'before') {
                    $selection = "date_op <\"" . date_ht2db($filter_date) . "\"";
                } else if ($when == 'after') {
                    $selection = "date_op >\"" . date_ht2db($filter_date) . "\"";
                    if ($date_end) {
                        $selection = "date_op >\"" . date_ht2db($date_end) . "\"";
                    }
                } else {
                    if ($date_end) {
                        $selection = "date_op >=\"" . date_ht2db($filter_date) . "\"";
                    } else {
                        $selection = "date_op =\"" . date_ht2db($filter_date) . "\"";
                    }

                    // If only the start date is specified, the end_date is set to the
                    // start date by default

                    if ($date_end) {
                        // When a end date is selected
                        if ($selection) {
                            $selection .= " and date_op <= \"" . date_ht2db($date_end) . "\"";
                        } else {
                            $selection = " date_op <= \"" . date_ht2db($date_end) . "\"";
                        }
                    }
                }
            }

            if ($filter_checked) {
                // echo "filter_checked = $filter_checked" . br();
                if (is_array($selection))
                    $selection = "";
                if ($selection)
                    $selection .= " and ";
                if ($filter_checked == 1) {
                    $selection .= "gel = \"1\"";
                } else if ($filter_checked == 2) {
                    $selection .= "gel = \"0\"";
                }
            }

            if ($montant_min) {
                if ($selection) {
                    $selection .= " and montant >= \"" . $montant_min . "\"";
                } else {
                    $selection = "montant >= \"" . $montant_min . "\"";
                }
            }

            if ($montant_max) {
                if ($selection) {
                    $selection .= " and montant <= \"" . $montant_max . "\"";
                } else {
                    $selection = "montant <= \"" . $montant_max . "\"";
                }
            }
            // echo $selection . br();

            if ($individual)
                return ($selection == "") ? array() : $selection;

            if ($filter_code1) {
                if ($selection) {
                    $selection .= " and compte1.codec >= \"" . $filter_code1 . "\"";
                } else {
                    $selection = "compte1.codec >= \"" . $filter_code1 . "\"";
                }

                if ($code1_end) {
                    if ($selection) {
                        $selection .= " and compte1.codec <= \"" . $code1_end . "\"";
                    } else {
                        $selection = "compte1.codec <= \"" . $code1_end . "\"";
                    }
                } else {
                    if ($selection) {
                        $selection .= " and compte1.codec <= \"" . $filter_code1 . "\"";
                    } else {
                        $selection = "compte1.codec <= \"" . $filter_code1 . "\"";
                    }
                }
            }

            if ($filter_code2) {
                if ($selection) {
                    $selection .= " and compte2.codec >= \"" . $filter_code2 . "\"";
                } else {
                    $selection = "compte2.codec >= \"" . $filter_code2 . "\"";
                }

                if ($code2_end) {
                    if ($selection) {
                        $selection .= " and compte2.codec <= \"" . $code2_end . "\"";
                    } else {
                        $selection = "compte2.codec <= \"" . $code2_end . "\"";
                    }
                } else {
                    if ($selection) {
                        $selection .= " and compte2.codec <= \"" . $filter_code2 . "\"";
                    } else {
                        $selection = "compte2.codec <= \"" . $filter_code2 . "\"";
                    }
                }
            }
        }

        // var_dump($selection);
        return ($selection == "") ? array() : $selection;
    }

    /**
     * Selectionne les comptes en débit et crédit, ou débit, ou crédit
     *
     * @param unknown_type $compte
     */
    private function _filtrage_compte($compte) {
        if ($this->session->userdata('filter_active')) {
            $filter_debit = $this->session->userdata('filter_debit');
            if ($filter_debit) {
                if ($filter_debit == 1) {
                    return " and (ecritures.compte1 = \"$compte\" ) ";
                } else if ($filter_debit == 2) {
                    return " and (ecritures.compte2 = \"$compte\" ) ";
                }
            }
        }
        return " and (ecritures.compte1 = \"$compte\" or ecritures.compte2 = \"$compte\") ";
    }

    /**
     * Retourne le tableau tableau utilisé pour l'affichage par page
     *
     * @return objet La liste
     */
    public function select_page($nb = 1000, $debut = 0) {
        $select = $this->select_columns('id, annee_exercise, date_creation, date_op, compte1, compte2, montant, description, num_cheque', $nb, $debut);

        $this->gvvmetadata->store_table($this->table, $select);
        return $select;
    }

    /**
     * Retourne le tableau tableau utilisé pour l'affichage par page
     *
     * @return objet La liste
     */
    public function select_journal($compte, $nb = 1000000, $debut = 0, $selection = array()) {
        $where = "ecritures.compte1 = compte1.id and ecritures.compte2 = compte2.id";

        if ($compte != '') {
            $where .= $this->_filtrage_compte($compte); // " and (ecritures.compte1 = \"$compte\" or ecritures.compte2 = \"$compte\") ";
            $individual = TRUE;
        } else {
            $individual = FALSE;
        }

        $year = $this->session->userdata('year');

        $filtrage = $this->filtrage('', $individual);

        $select = "ecritures.id, ecritures.annee_exercise, date_op, ";
        $select .= "montant, ecritures.description, num_cheque, quantite, achat, prix, gel, ecritures.club as club";
        $select .= ", ecritures.compte1, compte1.nom as nom_compte1, compte1.codec as code1";
        $select .= ", ecritures.compte2, compte2.nom as nom_compte2, compte2.codec as code2";

        $from = 'ecritures, comptes as compte1, comptes as compte2';
        $order_by = 'date_op, ecritures.id';


        $this->db
            ->select($select)
            ->from($from)
            ->where($where, NULL)
            ->where($selection, NULL)
            ->where($filtrage);

        if ($this->sections_model->section()) {
            $this->db->where('ecritures.club', $this->sections_model->section_id());
        }

        $db_res = $this->db->limit($nb, $debut)
            ->order_by($order_by)
            ->get();
        $result = $this->get_to_array($db_res);

        // gvv_debug("sql: select_journal: " . $this->db->last_query());

        $solde = "";
        $current_solde = "";
        $date_change_line = 0;
        if ($compte != '') {
            // compte oriented view
            $cnt = 0;
            foreach ($result as $line => $row) {
                $cnt++;
                if ($cnt == 1) {
                    // première ligne de résultat, on initialise le solde
                    $solde = $this->solde_compte($compte, $row['date_op'], '<');
                    $solde += $this->solde_jour($compte, $row['date_op'], $row['id']);
                }
                if ($row['compte1'] == $compte) {
                    $result[$line]['autre_code'] = $row['code2'];
                    $result[$line]['autre_compte'] = $row['compte2'];
                    $result[$line]['autre_nom_compte'] = $row['nom_compte2'];
                    $result[$line]['debit'] = $row['montant'];
                    $result[$line]['credit'] = '';
                    $solde -= $row['montant'];
                    $result[$line]['solde'] = $solde;
                } else {
                    $result[$line]['autre_code'] = $row['code1'];
                    $result[$line]['autre_compte'] = $row['compte1'];
                    $result[$line]['autre_nom_compte'] = $row['nom_compte1'];
                    $result[$line]['debit'] = '';
                    $result[$line]['credit'] = $row['montant'];
                    $solde += $row['montant'];
                    $result[$line]['solde'] = $solde;
                }
                if ($row['prix'] < 0)
                    $result[$line]['prix'] = '';
            }
        }

        foreach ($result as $line => $row) {
            foreach ($row as $key => $field) {
                // echo $key . " => " . $field . br();
            }
            $achat = $result[$line]['achat'];
            $result[$line]['image'] = "la ligne du " . date_db2ht($result[$line]['date_op']) . " " . $result[$line]['nom_compte1'] . "-" . $result[$line]['nom_compte2'] . " " . $result[$line]['montant'] . " " . $result[$line]['description'];

            // La gestion de la section n'est pas très élégante. Il aurait mieux value faire une jointure
            // Mais la requête SQL est déjà assez compliquée pour ne pas en rajouter.
            $result[$line]['section'] = $this->sections_model->image($result[$line]['club']);
        }

        $this->gvvmetadata->store_table("vue_journal", $result);

        return $result;
    }

    /**
     * Retourne le nombre d'écritures sur un compte sur une année
     *
     * @return integer Le nombre de lignes satisfaisant la condition
     */
    public function count($compte = '') {
        $where = "ecritures.compte1 = compte1.id and ecritures.compte2 = compte2.id";
        if ($compte != '') {
            $where .= $this->_filtrage_compte($compte); // " and (ecritures.compte1 = \"$compte\" or ecritures.compte2 = \"$compte\") ";
            $individual = TRUE;
        } else {
            $individual = FALSE;
        }
        $filtrage = $this->filtrage('', $individual);

        $select = "ecritures.id, ecritures.annee_exercise, date_op, ";
        $select .= "montant, ecritures.description, num_cheque, quantite, achat, prix, gel";
        $select .= ", ecritures.compte1, compte1.nom as nom_compte1, compte1.codec as code1";
        $select .= ", ecritures.compte2, compte2.nom as nom_compte2, compte2.codec as code2";

        $from = 'ecritures, comptes as compte1, comptes as compte2';
        $order_by = 'date_op, ecritures.id';

        $year = $this->session->userdata('year');

        gvv_debug("sql count: select " . $select);
        gvv_debug("sql count: from " . $from);
        gvv_debug("sql count: where " . $where);

        $query = $this->db
            ->from($from)
            ->where($where)
            ->where($filtrage);
        // ->where("YEAR(date_op) = \"$year\"");

        if ($this->sections_model->section()) {
            $query = $this->db->where('ecritures.club', $this->sections_model->section_id());
        }

        if ($query) {
            gvv_debug("sql count: " . $this->db->last_query());
            $count = $query->count_all_results();
            gvv_debug("sql count: count = " . $count);

            return $count;
        } else {
            gvv_debug("sql count: error");
            return 0;
        }
    }

    /**
     * Retourne le nombre d'écritures sur un compte
     *
     * @return integer Le nombre de lignes satisfaisant la condition
     */
    public function count_all($compte = '') {
        $where = "ecritures.compte1 = compte1.id and ecritures.compte2 = compte2.id";
        $where .= " and (ecritures.compte1 = \"$compte\" or ecritures.compte2 = \"$compte\") ";

        $select = "ecritures.id, ecritures.annee_exercise, date_op, ";
        $select .= "montant, ecritures.description, num_cheque, quantite, achat, prix, gel";
        $select .= ", ecritures.compte1, compte1.nom as nom_compte1, compte1.codec as code1";
        $select .= ", ecritures.compte2, compte2.nom as nom_compte2, compte2.codec as code2";

        $from = 'ecritures, comptes as compte1, comptes as compte2';
        $order_by = 'date_op, ecritures.id';

        gvv_debug("sql count: select " . $select);
        gvv_debug("sql count: from " . $from);
        gvv_debug("sql count: where " . $where);

        $query = $this->db->from($from)->where($where);
        if ($query) {
            gvv_debug("sql count_all: " . $this->db->last_query());
            $count = $query->count_all_results();
            gvv_debug("sql count_all: count = " . $count);

            return $count;
        } else {
            gvv_debug("sql count_all: error");
            return 0;
        }
    }

    /**
     * Retourne le montant total des débits ou credits de la selection
     *
     * @param unknown_type $debt_selection
     * @param unknown_type $when
     */
    public function select_amount($debt_selection, $when) {
        $filtrage = $this->filtrage($when, TRUE);

        $amount = $this->db->select_sum('montant')->from($this->table)->where($debt_selection)->where($filtrage)->get()->row()->montant;

        gvv_debug("sql: " . $this->db->last_query());

        return (isset($amount)) ? $amount : 0;
    }

    /**
     * Retourne le solde d'un compte avant une date ou jusqu'à une date donnée.
     *
     * @param unknown_type $compte
     *            identifiant du compte
     * @param $date du
     *            solde, si non spécifié, retourne le dernier solde du compte
     * @param $operation ">="
     *            | "<"
     * @param boolean $all
     *            si vrai retourne un tableau ['debit', 'credit'] si faux retourne le solde (scalaire)
     */
    public function solde_compte($compte, $date = '', $operation = "<=", $all = FALSE) {
        if ($date == '') {
            $date = date("d/m/Y");
        }
        $where = "date_op $operation \"" . date_ht2db($date) . "\"";

        $debit = $this->db->select_sum('montant')->from($this->table)->where($where)->where(array(
            'compte1' => $compte
        ))->get()->row()->montant;

        gvv_debug("sql: " . $this->db->last_query());

        $credit = $this->db->select_sum('montant')->from($this->table)->where($where)->where(array(
            'compte2' => $compte
        ))->get()->row()->montant;

        gvv_debug("sql: " . $this->db->last_query());
        $solde = $credit - $debit;
        gvv_debug("solde_compte compte=$compte, date=$date, operation=$operation, debit=$debit, credit=$credit, solde=$solde");
        if ($all) {
            $res = array(
                $debit,
                $credit
            );
            return $res;
        } else {
            return $solde;
        }
    }

    /**
     * Retourne le solde d'un compte general avant une date ou jusqu'à une date donnée.
     *
     * @param unknown_type $compte
     *            identifiant du compte
     * @param $date du
     *            solde, si non spécifié, retourne le dernier solde du compte
     * @param $operation ">="
     *            | "<"
     * @param boolean $all
     *            si vrai retourne un tableau ['debit', 'credit'] si faux retourne le solde (scalaire)
     */
    public function solde_compte_general($codec, $date = '', $operation = "<=", $all = FALSE, $section_id = 0) {
        if ($date == '') {
            $date = date("d/m/Y");
        }
        $where = "date_op $operation \"" . date_ht2db($date) . "\"";

        $this->db->select('sum(montant) as montant, codec, ecritures.club')
            ->from('ecritures, comptes')
            ->where($where)
            ->where('ecritures.compte1 = comptes.id')
            ->where(array(
                'codec' => $codec
            ));

        if ($section_id) {
            $this->db->where('ecritures.club', $section_id);
        } else {
            if ($this->sections_model->section()) {
                $this->db->where('ecritures.club', $this->sections_model->section_id());
            }
        }

        $debit = $this->db->get()->row()->montant;

        gvv_debug("sql: " . $this->db->last_query());

        $this->db->select('sum(montant) as montant, codec, ecritures.club')
            ->from('ecritures, comptes')
            ->where($where)->where('ecritures.compte2 = comptes.id')
            ->where(array(
                'codec' => $codec
            ));


        if ($section_id) {
            $this->db->where('ecritures.club', $section_id);
        } else {
            if ($this->sections_model->section()) {
                $this->db->where('ecritures.club', $this->sections_model->section_id());
            }
        }

        $credit = $this->db->get()->row()->montant;

        gvv_debug("sql: " . $this->db->last_query());
        $solde = $credit - $debit;
        gvv_debug("solde_compte_general codec=$codec, date=$date, operation=$operation, debit=$debit, credit=$credit, solde=$solde");
        if ($all) {
            $res = array(
                $debit,
                $credit
            );
            return $res;
        } else {
            return $solde;
        }
    }

    /**
     * Retourne le solde des opérations d'un compte à une date donnée avant une ligne donnée
     *
     * @param unknown_type $compte
     *            identifiant du compte
     * @param $date du
     *            solde, si non spécifié, retourne le dernier solde du compte
     * @param $id de
     *            la ligne dont on veux les soldes précédants
     */
    public function solde_jour($compte, $date = '', $id = 0) {
        $operation = "=";

        if ($date == '') {
            $date = date("d/m/Y");
        }
        $where = "date_op $operation \"" . date_ht2db($date) . "\" and id < \"$id\"";

        $debit = $this->db->select_sum('montant')->from($this->table)->where($where)->where(array(
            'compte1' => $compte
        ))->get()->row()->montant;

        gvv_debug("sql: " . $this->db->last_query());

        $credit = $this->db->select_sum('montant')->from($this->table)->where($where)->where(array(
            'compte2' => $compte
        ))->get()->row()->montant;

        gvv_debug("sql: " . $this->db->last_query());
        $solde = $credit - $debit;

        gvv_debug("solde_jour compte=$compte, date=$date, id=$id");
        // echo "solde compte=$compte, date=$date, operation=$operation, debit=$debit, credit=$credit, solde=$solde" . br();
        return $solde;
    }

    /**
     * Creation d'une ecriture comptable.
     * Enregistre l'écriture et modifie les soldes
     * des comptes référencés.
     *
     * @param unknown_type $data
     *            Array ( [id] => 0
     *            [annee_exercise] => 2011
     *            [date_creation] => 2011-03-20
     *            [date_op] => 2011-03-20
     *            [compte1] => 6
     *            [compte2] => 7
     *            [montant] => 100
     *            [description] => heure de vol en fauconet
     *            [num_cheque] => xxx
     *            [saisie_par] => fpeignot )
     */
    public function create_ecriture($data) {
        $compte1 = $data['compte1'];
        $compte2 = $data['compte2'];
        $montant = $data['montant'];


        $this->db->trans_start();
        $this->comptes_model->maj_comptes($compte1, $compte2, $montant);

        $id = $this->create($data);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            gvv_error('error', "Transaction failed for ecriture $id");
            // Get MySQL error number
            $errno = $this->db->_error_number();
            // Get MySQL error message
            $error = $this->db->_error_message();
            gvv_error("MySQL Error #$errno: $error");
            return FALSE;
        }

        return $id;
    }

    /**
     * Creation d'une ecriture comptable.
     * Enregistre l'écriture et modifie les soldes
     * des comptes référencés.
     *
     * @param unknown_type $data
     *            Array ( [id] => 0
     *            [annee_exercise] => 2011
     *            [date_creation] => 2011-03-20
     *            [date_op] => 2011-03-20
     *            [compte1] => 6
     *            [compte2] => 7
     *            [montant] => 100
     *            [description] => heure de vol en fauconet
     *            [num_cheque] => xxx
     *            [saisie_par] => fpeignot )
     */
    public function update_ecriture($id, $data) {
        $compte1 = $data['compte1'];
        $compte2 = $data['compte2'];
        $montant = $data['montant'];
        // echo "$compte1 -> $compte2 : $montant<br>";

        if ($data['gel'] == 0) { // Pas gel
            $this->db->trans_start();
            $this->comptes_model->maj_comptes($compte1, $compte2, $montant);
            $this->update($id, $data);
            $this->db->trans_complete();
        } else {
            if (! $this->session->userdata('popup')) {
                $this->session->set_flashdata('popup', "Modification impossible, écriture gelée");
            }
        }
    }

    /**
     * Destruction d'une ecriture comptable.
     * Détruit l'écriture et modifie les soldes
     * des comptes référencés.
     *
     * @param unknown_type $id
     */
    public function delete_ecriture($id) {
        $this->db->trans_start();
        $previous = $this->ecritures_model->get_by_id('id', $id);
        $compte1 = $previous['compte1'];
        $compte2 = $previous['compte2'];
        $montant = $previous['montant'];

        $date = $previous['date_op'];
        $date_gel = $this->clotures_model->freeze_date(true);

        // format database
        if (preg_match('/(\d+)\-(\d+)\-(\d+)/', $date, $matches)) {
            $day = $matches[3];
            $month = $matches[2];
            $year = $matches[1];

            $time = mktime(0, 0, 0, $month, $day, $year);

            // format français
            if (preg_match('/(\d+)\/(\d+)\/(\d+)/', $date_gel, $matches)) {
                $day = $matches[1];
                $month = $matches[2];
                $year = $matches[3];
                $freeze_time = mktime(0, 0, 0, $month, $day, $year);
                if ($time < $freeze_time) {
                    if (! $this->session->userdata('popup')) {
                        $this->session->set_flashdata('popup', "Suppression impossible, écriture antérieure au " . $date_gel);
                    }
                    return;
                }
            } else {
                gvv_error("Erreur mauvais format de date de gel");
            }
        } else {
            gvv_error("Erreur mauvais format de date d'opération");
        }

        if ($previous['gel'] == 0) { // Pas gel
            $this->load->model("comptes_model");
            $this->comptes_model->maj_comptes($compte1, $compte2, -$montant);

            $this->db->delete($this->table, array(
                'id' => $id
            ));
            $this->db->trans_complete();
        } else {
            if (! $this->session->userdata('popup')) {
                $this->session->set_flashdata('popup', "Suppression impossible, écriture gelée");
            }
        }
    }

    /**
     * Detruit toute les écritures correspondant à la selection
     *
     * @param unknown_type $where
     */
    public function delete_all($where = array()) {
        $db_res = $this->db
            ->select("id")
            ->from('ecritures')
            ->where($where)
            ->get();
        $result = $this->get_to_array($db_res);

        foreach ($result as $row) {
            $this->ecritures_model->delete_ecriture($row['id']);
        }
    }

    /**
     * Utilisé lors des migration de données
     *
     * @deprecated
     *
     */
    public function select_attache() {
        $where = array();
        $db_res = $this->db
            ->select("ecritures.id as id, annee_exercise, date_creation, date_op, compte1, compte2, montant, ecritures.description as description" . ", num_cheque, achat" . ", ecritures.quantite, ecritures.prix")
            ->from("ecritures")
            ->like('num_cheque', 'Facture n')
            ->where($where)
            ->get();
        return $this->get_to_array($db_res);
    }

    /**
     * Utilisé lors des migration de données
     *
     * @deprecated
     *
     */
    public function select_raw() {
        $where = array();
        $db_res = $this->db->select("*")->from("ecritures")->get();
        return $this->get_to_array($db_res);
    }

    /**
     * Selection des écritures par catégories
     *
     * SELECT ecritures.annee_exercise, 
     * ecritures.compte1, compte1.nom as nom_compte1, compte1.codec as code1,
     * sum(montant) as total
     * FROM ecritures,comptes as compte1, comptes as compte2
     * WHERE  ecritures.compte1=compte1.id
     * and ecritures.compte2=compte2.id
     * and compte1.codec >= "6" and compte1.codec < "7"
     * group by compte1
     */
    public function select_categorie() {
        $selection = array();
        $where = "ecritures.compte1 = compte1.id and ecritures.compte2 = compte2.id";
        $where .= " and compte1.codec >= \"6\" and compte1.codec < \"7\"";

        $select = "ecritures.annee_exercise,  ";
        $select .= "ecritures.compte1 as compte1, compte1.nom as nom_compte1, compte1.codec as code1, ";
        $select .= "sum(montant) as total, ";
        $select .= "compte2.nom as nom_compte2, compte2.codec as code2";

        $db_res = $this->db->select($select)
            ->from("ecritures, comptes as compte1, comptes as compte2")
            ->where($where)
            ->where($selection)
            ->group_by('compte1')
            ->get();
        return $this->get_to_array($db_res);
    }

    /**
     * Pointe les écritures
     *
     * @param unknown_type $id
     * @param unknown_type $new_state
     */
    function switch_line($id, $new_state) {
        $this->db->set('gel', $new_state);
        $this->db->where('id', $id);
        $this->db->update($this->table);
    }

    /**
     * Dépenses d'un exercise
     *
     * Normalement les dépenses ne sont qu'au débit des comptes 600, mais il faut aussi prendre
     * en compte les crédits (avoir, annulation de dépenses)
     *
     * On pourrait calculer les dépenses en comparant les soldes en début et en fin d'exercise, mais seuls
     * les soldes courrants sont gardés dans les tables de comptes. Le calcul du solde à une date données
     * entrainnerait un select sur les écritures, il est donc plus simple de faire le select des dépenses
     * en selectionnant les lignes d'écritures.
     *
     * @param $year année
     *            de l'exercise
     * @param $group_by "compte1"
     *            ou "compte2", Il suffit de ne pas grouper pour avoir le total
     */
    function select_depenses($year, $group_by = "", $date = "") {
        if ($date) {
            $when = "date_op <= \"$date\" and YEAR(date_op) = \"$year\"";
        } else {
            $when = "YEAR(date_op) = \"$year\"";
        }

        // Selectionne les dépenses (écritures dans les comptes 600)
        $this->db
            ->select("compte1.codec as code, compte1 as compte, compte1.nom as nom, sum(montant) as montant, date_op, ecritures.club")
            ->from("ecritures, comptes as compte1, comptes as compte2")
            ->where("ecritures.compte1 = compte1.id and ecritures.compte2 = compte2.id")
            ->where($when)
            ->where('compte1.codec >= "6" and compte1.codec < "7"')
            ->where('compte2.codec != "120" and compte2.codec !=  "129"');

        if ($this->sections_model->section()) {
            $this->db->where('ecritures.club', $this->sections_model->section_id());
        }
        $db_res = $this->db->group_by($group_by)->order_by('code')->get();
        $depenses = $this->get_to_array($db_res);

        // création d'un index
        $index = array();
        foreach ($depenses as $idx => $row) {
            $index[$row['compte']] = $idx;
        }

        // on prend aussi en compte les opérations de crédit sur les comptes de dépenses (annulations)
        if ($group_by == "compte1")
            $group_by = "compte2";

        $this->db
            ->select("compte2.codec as code, compte2 as compte, compte2.nom as nom, sum(montant) as montant, date_op, ecritures.club")
            ->from("ecritures, comptes as compte1, comptes as compte2")
            ->where("ecritures.compte1 = compte1.id and ecritures.compte2 = compte2.id")
            ->where($when)
            ->where('compte2.codec >= "6" and compte2.codec < "7"')
            ->where('compte1.codec != "120" and compte1.codec !=  "129"');
        if ($this->sections_model->section()) {
            $this->db->where('ecritures.club', $this->sections_model->section_id());
        }
        $db_res = $this->db->group_by($group_by)
            ->order_by('code')
            ->get();
        $no_depenses = $this->get_to_array($db_res);

        // pour toutes les opérations de crédit sur les comptes de dépenses
        foreach ($no_depenses as $idx => $row) {
            $compte = $row['compte'];
            $montant = $row['montant'];
            if (array_key_exists($compte, $index)) {
                // S'il y avait des débits
                // correction du montant
                $idx = $index[$compte];
                $depenses[$idx]['montant'] -= $montant;
            } else {
                if ($group_by == "") {
                    // total
                    $depenses[0]['montant'] -= $montant;
                } else {
                    // C'est un compte de dépense qui n'a pas de dépense
                    // création de la ligne
                    $row['montant'] = -$montant;
                    $depenses[] = $row;
                }
            }
        }
        return $depenses;
    }

    /**
     * Recettes d'un exercise
     *
     * Normalement les recettes ne sont qu'au crédit des comptes 700 mais il faut aussi prendre en compte
     * les opérations inverses (annulation de recettes).
     *
     * @param $year année
     *            de l'exercise
     * @param $group_by "compte1"
     *            ou "compte2", Il suffit de ne pas grouper pour avoir le total
     */
    function select_recettes($year, $group_by = "", $date = "") {
        if ($date) {
            $when = "date_op <= \"$date\"  and YEAR(date_op) = \"$year\"";
        } else {
            $when = "YEAR(date_op) = \"$year\"";
        }

        // selection des recettes
        $this->db
            ->select("compte2.codec as code, compte2 as compte, compte2.nom as nom, sum(montant) as montant, ecritures.club")
            ->from("ecritures, comptes as compte1, comptes as compte2")
            ->where("ecritures.compte1 = compte1.id and ecritures.compte2 = compte2.id")
            ->where($when)
            ->where('compte2.codec >= "7" and compte2.codec < "8"')
            ->where('compte1.codec != "120" and compte1.codec !=  "129"');
        if ($this->sections_model->section()) {
            $this->db->where('ecritures.club', $this->sections_model->section_id());
        }
        $db_res = $this->db->group_by($group_by)
            ->order_by('code')
            ->get();
        $recettes = $this->get_to_array($db_res);

        // création d'un index des compte existants
        $index = array();
        foreach ($recettes as $idx => $row) {
            $index[$row['compte']] = $idx;
        }

        // corrige des opérations de débit sur les recettes
        if ($group_by == "compte2")
            $group_by = "compte1";

        $this->db
            ->select("compte1.codec as code, compte1 as compte, compte1.nom as nom, sum(montant) as montant, ecritures.club")
            ->from("ecritures, comptes as compte1, comptes as compte2")
            ->where("ecritures.compte1 = compte1.id and ecritures.compte2 = compte2.id")
            ->where($when)
            ->where('compte1.codec >= "7" and compte1.codec < "8"')
            ->where('compte2.codec != "120" and compte2.codec !=  "129"');
        if ($this->sections_model->section()) {
            $this->db->where('ecritures.club', $this->sections_model->section_id());
        }
        $db_res = $this->db->group_by($group_by)->order_by('code')
            ->get();
        $no_recettes = $this->get_to_array($db_res);

        foreach ($no_recettes as $idx => $row) {
            $compte = $row['compte'];
            $montant = $row['montant'];
            if (array_key_exists($compte, $index)) {
                $idx = $index[$compte];
                $recettes[$idx]['montant'] -= $montant;
            } else {
                if ($group_by == "") {
                    // total
                    $recettes[0]['montant'] -= $montant;
                } else {
                    // C'est un compte de dépense qui n'a pas de dépense
                    $row['montant'] = -$montant;
                    $recettes[] = $row;
                }
            }
        }

        return $recettes;
    }

    /**
     * Cherche le solde des comptes
     * Il suffit de ne pas grouper pour avoir le total
     * 
     * retourne un tableau associatif, clé=n° de compte, valeur = tableau associatif code,compte,nomndebit,credit,solde
     * 
     * 
select_solde(2014-12-31, 2, 28, 1)

C:\Users\frede\Dropbox\xampp\htdocs\gvv2\application\models\ecritures_model.php:909:
array (size=2)
  247 => 
    array (size=6)
      'code' => string '215' (length=3)
      'compte' => string '247' (length=3)
      'nom' => string 'planeur PEGASE F CHDB' (length=21)
      'debit' => string '87612.00' (length=8)
      'credit' => float 0
      'solde' => float -87612
  255 => 
    array (size=6)
      'code' => string '215' (length=3)
      'compte' => string '255' (length=3)
      'nom' => string 'planeur monoplace DG 200' (length=24)
      'credit' => string '0.00' (length=4)
      'debit' => float 0
      'solde' => float 0
     */
    function select_solde($date_op, $codec_min, $codec_max, $group = TRUE, $section_id = 0) {

        // if ($codec_min == 1) echo "select_solde($date_op, $codec_min, $codec_max, $group)" . br();
        $group_by = ($group) ? "" : "compte1";
        $this->db
            ->select("compte1.codec as code, compte1 as compte, compte1.nom as nom, sum(montant) as debit")
            ->from("ecritures, comptes as compte1, comptes as compte2")
            ->where("ecritures.compte1 = compte1.id and ecritures.compte2 = compte2.id")
            ->where("date_op <= \"$date_op\"")
            ->where("compte1.codec >= \"$codec_min\" and compte1.codec < \"$codec_max\"");


        if ($section_id) {
            $this->db->where('ecritures.club', $section_id);
        } else {
            if ($this->sections_model->section()) {
                $this->db->where('ecritures.club', $this->sections_model->section_id());
            }
        }

        $db_res = $this->db->group_by($group_by)
            ->order_by('code')
            ->get();
        $debit = $this->get_to_array($db_res);

        $group_by = ($group) ? "" : "compte2";
        $this->db
            ->select("compte2.codec as code, compte2 as compte, compte2.nom as nom, sum(montant) as credit")
            ->from("ecritures, comptes as compte1, comptes as compte2")
            ->where("ecritures.compte1 = compte1.id and ecritures.compte2 = compte2.id")
            ->where("date_op <= \"$date_op\"")
            ->where("compte2.codec >= \"$codec_min\" and compte2.codec < \"$codec_max\"");

        if ($section_id) {
            $this->db->where('ecritures.club', $section_id);
        } else {
            if ($this->sections_model->section()) {
                $this->db->where('ecritures.club', $this->sections_model->section_id());
            }
        }

        $db_res = $this->db->group_by($group_by)
            ->order_by('code')
            ->get();
        $credit = $this->get_to_array($db_res);

        if (false && $codec_min == 1) {
            echo "debit" . br();
            var_dump($debit);
            echo "credit" . br();
            var_dump($credit);
        }

        $res = array();
        foreach ($debit as $key => $row) {
            if (isset($row['compte'])) {
                $row['credit'] = 0.0;
                $res[$row['compte']] = $row;
            }
        }
        foreach ($credit as $key => $row) {
            if (isset($row['compte'])) {
                if (isset($res[$row['compte']])) {
                    $res[$row['compte']]['credit'] = $row['credit'];
                } else {
                    $row['debit'] = 0.0;
                    $res[$row['compte']] = $row;
                }
            }
        }
        foreach ($res as $key => $row) {
            $res[$key]['solde'] = 0.0;
            if (isset($row['credit']))
                $res[$key]['solde'] += $row['credit'];
            if (isset($row['debit']))
                $res[$key]['solde'] -= $row['debit'];
        }
        // if ($codec_min == 1) var_dump($res);
        return $res;
    }

    /**
     * Détermine le solde d'un compte donnée à une date données
     */
    function select_emploi_compte($date_op, $compte) {
        $db_res = $this->db
            ->select("compte1.codec as code, compte1 as compte, compte1.nom as nom, sum(montant) as credit")
            ->from("ecritures, comptes as compte1, comptes as compte2")
            ->where("ecritures.compte1 = compte1.id and ecritures.compte2 = compte2.id")
            ->where("date_op <= \"$date_op\"")
            ->where("compte1.id = \"$compte\" ")
            ->group_by("compte1")
            ->get();
        $credit = $this->get_to_array($db_res);

        if (count($credit)) {
            $res = $credit[0]['credit'];
        } else {
            $res = 0;
        }

        return $res;
    }

    /**
     * Détermine le solde d'un compte donnée à une date données
     */
    function select_ressource_compte($date_op, $compte) {
        $db_res = $this->db
            ->select("compte2.codec as code, compte2 as compte, compte2.nom as nom, sum(montant) as debit")
            ->from("ecritures, comptes as compte1, comptes as compte2")
            ->where("ecritures.compte1 = compte1.id and ecritures.compte2 = compte2.id")
            ->where("date_op <= \"$date_op\"")
            ->where("compte2.id = \"$compte\" ")
            ->group_by("compte2")
            ->get();
        $debit = $this->get_to_array($db_res);
        if (count($debit)) {
            $res = $debit[0]['debit'];
        } else {
            $res = 0;
        }

        return $res;
    }

    /**
     * Selectionne les lignes d'un achat gelée
     *
     * @param
     *            $achat
     */
    function select_frozen_lines($achat) {
        $date_gel = $this->clotures_model->freeze_date();

        $db_res = $this->db
            ->select("achat, gel, date_op")
            ->from("ecritures")
            ->where("(gel != 0  or date_op<\"$date_gel\" )")
            ->where("achat = \"$achat\"")
            ->get();
        $res = $this->get_to_array($db_res);

        gvv_debug("frozen_lines achat=$achat: " . var_export($res, true));
        return $res;
    }

    /**
     * Selectionne les lignes d'un achat gelée
     *
     * @param
     *            $vol
     * @param $field nom
     *            du champ 'vol_planeur', 'vol_avion'
     */
    function select_flight_frozen_lines($vol, $field) {
        $date_gel = $this->clotures_model->freeze_date();

        $db_res = $this->db
            ->select("achat, gel, $field, date_op")
            ->from("ecritures, achats")
            ->where("(gel != 0 or date_op<\"$date_gel\" )")
            ->where("achats.id = ecritures.achat")
            ->where("$field = \"$vol\"")
            ->get();
        $res = $this->get_to_array($db_res);
        gvv_debug("flight_frozen_lines vol=$vol: " . var_export($res, true));
        return $res;
    }

    /**
     * return true when there is at least one row in the ecritures
     * table with $account_id as $compte1 or $compte2 ant the other 
     * compte in the row being a compte with codec=102
     */
    function is_account_initialized($account_id) {
        $db_res = $this->db
            ->select("ecritures.id")
            ->from("ecritures")
            ->join("comptes as c1", "c1.id = ecritures.compte1", "left")
            ->join("comptes as c2", "c2.id = ecritures.compte2", "left")
            ->where("(compte1 = '$account_id' AND c2.codec = '102') OR (compte2 = '$account_id' AND c1.codec = '102')")
            ->limit(1)
            ->get();

        return $db_res->num_rows() > 0;
    }

    /**
     * Retourne un hash des montants par comptes
     * $list: list of array (size=5)
     * 'code' => string '654' (length=3)
     * 'compte' => string '144' (length=3)
     * 'nom' => string 'Redistributions Bourses et Subventions' (length=38)
     * 'montant' => string '1500.00' (length=7)
     * 'date_op' => string '2012-02-11' (length=10)
     * return:
     * ['cpt1' => 10, 'cpt2' => 100, 'cpt3' => 200, ...]
     */
    function montants($list) {
        $result = array();
        foreach ($list as $row) {
            $result[$row['compte']] = $row['montant'];
        }
        return $result;
    }

    /**
     * Retourne les données pour l'affichage du résultat
     *
     * return = (
     * 'controller' => 'comptes',
     * 'years' => [2012, 2013],
     * 'comptes_depenses' => [('code' => 6xx, 'nom' => 'xx', 'compte' => 'xxx'), ...],
     * 'comptes_recettes' => [('code' => 7xx, 'nom' => 'xx', 'compte' => 'xxx'), ...],
     * 'montants' => (
     * '2012' => ['recettes' => ['cpt1' => 10, 'cpt2' => 100, 'cpt3' => 200, ...],
     * 'depenses' => ['cpt1' => 10, 'cpt2' => 100, 'cpt3' => 200, ...],
     * 'total_recettes' => xxx,
     * 'total_depenses' => yyy],
     * '2013' => ['recettes' => ['cpt1' => 10, 'cpt2' => 100, 'cpt3' => 200, ...],
     * 'depenses' => ['cpt1' => 10, 'cpt2' => 100, 'cpt3' => 200, ...],
     * 'total_recettes' => xxx,
     * 'total_depenses' => yyy],
     * )
     * )
     */
    function select_resultat($year = "") {
        $result = array();
        $result['controller'] = "comptes";
        if ($year == "") {
            $year = $this->session->userdata('year');
        }
        $result['years'] = array(
            $year - 1,
            $year
        );

        // listes des comptes de dépenses et de recettes
        $this->load->model('comptes_model');
        $result['comptes_depenses'] = $this->comptes_model->list_of('codec >= "6" and codec < "7"', 'codec');
        $result['comptes_recettes'] = $this->comptes_model->list_of('codec >= "7" and codec < "8"', 'codec');

        // gestion de la date d'affichage
        $balance_date = $this->session->userdata('balance_date');
        if ($balance_date) {
            $result['balance_date'] = $balance_date;
        } else {
            $result['balance_date'] = date('d/m/Y');
        }
        $date_op = date_ht2db($balance_date);

        $charges = $this->ecritures_model->select_depenses($year, "", $date_op);
        $total_charges = $charges[0]['montant'];
        $charges = $this->ecritures_model->select_depenses($year, "compte1", $date_op);

        $produits = $this->ecritures_model->select_recettes($year, "", $date_op);
        $total_produits = $produits[0]['montant'];
        $produits = $this->ecritures_model->select_recettes($year, "compte2", $date_op);

        $result['montants'][$year]['total_depenses'] = $total_charges;
        $result['montants'][$year]['total_recettes'] = $total_produits;
        $result['montants'][$year]['recettes'] = $this->montants($produits);
        $result['montants'][$year]['depenses'] = $this->montants($charges);

        // année précédante
        $year--;
        $date_op = "$year-12-31";

        $charges = $this->ecritures_model->select_depenses($year, "", $date_op);
        $total_charges = $charges[0]['montant'];
        $charges = $this->ecritures_model->select_depenses($year, "compte1", $date_op);

        $produits = $this->ecritures_model->select_recettes($year, "", $date_op);
        $total_produits = $produits[0]['montant'];
        $produits = $this->ecritures_model->select_recettes($year, "compte2", $date_op);

        $result['montants'][$year]['total_depenses'] = $total_charges;
        $result['montants'][$year]['total_recettes'] = $total_produits;
        $result['montants'][$year]['recettes'] = $this->montants($produits);
        $result['montants'][$year]['depenses'] = $this->montants($charges);

        return ($result);
    }

    /**
     * Formate les information de resultat dans un tableau
     */
    function resultat_table($resultat, $links, $tab, $decimal_sep = '', $target = 'html') {
        $CI = &get_instance();
        $CI->lang->load('comptes');

        $tbl = array();
        $year = $resultat['years'][1];
        $previous_year = $resultat['years'][0];
        $tbl[0] = array(
            $this->lang->line("gvv_vue_comptes_short_field_codec"),
            $this->lang->line("comptes_label_expenses"),
            $year,
            $previous_year,
            $tab,
            $this->lang->line("gvv_vue_comptes_short_field_codec"),
            $this->lang->line("comptes_label_earnings"),
            $year,
            $previous_year
        );
        $line = 1;
        $offset = 6;
        $charges = $resultat['comptes_depenses'];
        $produits = $resultat['comptes_recettes'];
        for ($i = 0; $i < max(count($charges), count($produits)); $i++) {
            // Dépenses
            if (isset($charges[$i]['nom'])) {
                $code = $charges[$i]['codec'];
                $nom = $charges[$i]['nom'];
                $compte = $charges[$i]['id'];

                $tbl[$line][0] = ($links) ? anchor(controller_url("comptes/page/$code"), $code) : $code;
                $tbl[$line][1] = ($links) ? anchor(controller_url("compta/journal_compte/$compte"), $nom) : $nom;

                $montant = isset($resultat['montants'][$year]['depenses'][$compte]) ? $resultat['montants'][$year]['depenses'][$compte] : '';
                $tbl[$line][2] = euro($montant, $decimal_sep, $target);

                $montant = isset($resultat['montants'][$previous_year]['depenses'][$compte]) ? $resultat['montants'][$previous_year]['depenses'][$compte] : '';
                $tbl[$line][3] = euro($montant, $decimal_sep, $target);
            } else {
                $tbl[$line][0] = '';
                $tbl[$line][1] = '';
                $tbl[$line][2] = '';
                $tbl[$line][3] = '';
            }

            $tbl[$line][4] = $tab;

            // Recettes
            if (isset($produits[$i]['nom'])) {
                $code = $produits[$i]['codec'];
                $nom = $produits[$i]['nom'];
                $compte = $produits[$i]['id'];

                $tbl[$line][$offset + 0] = ($links) ? anchor(controller_url("comptes/page/$code"), $code) : $code;
                $tbl[$line][$offset + 1] = ($links) ? anchor(controller_url("compta/journal_compte/$compte"), $nom) : $nom;

                $montant = isset($resultat['montants'][$year]['recettes'][$compte]) ? $resultat['montants'][$year]['recettes'][$compte] : '';
                $tbl[$line][$offset + 2] = euro($montant, $decimal_sep, $target);

                $montant = isset($resultat['montants'][$previous_year]['recettes'][$compte]) ? $resultat['montants'][$previous_year]['recettes'][$compte] : '';
                $tbl[$line][$offset + 3] = euro($montant, $decimal_sep, $target);
            } else {
                $tbl[$line][$offset + 0] = '';
                $tbl[$line][$offset + 1] = '';
                $tbl[$line][$offset + 2] = '';
                $tbl[$line][$offset + 3] = '';
            }

            $line++;
        }

        $tbl[] = array(
            $tab,
            $tab,
            $tab,
            $tab,
            $tab,
            $tab,
            $tab,
            $tab,
            $tab
        );

        // Totaux
        $solde_charges_prec = $resultat['montants'][$previous_year]['total_depenses'];
        $solde_charges = $resultat['montants'][$year]['total_depenses'];
        $solde_produits_prec = $resultat['montants'][$previous_year]['total_recettes'];
        $solde_produits = $resultat['montants'][$year]['total_recettes'];
        $tbl[] = array(
            $tab,
            $this->lang->line("comptes_label_total_expenses"),
            euro($solde_charges, $decimal_sep, $target),
            euro($solde_charges_prec, $decimal_sep, $target),
            $tab,
            $tab,
            $this->lang->line("comptes_label_total_incomes"),
            euro($solde_produits, $decimal_sep, $target),
            euro($solde_produits_prec, $decimal_sep, $target)
        );

        // Pertes et benefices
        if ($solde_produits > $solde_charges) {
            $benefice = euro($solde_produits - $solde_charges, $decimal_sep, $target);
            $perte = '';
        } else {
            $perte = euro($solde_charges - $solde_produits, $decimal_sep, $target);
            $benefice = '';
        }

        if ($solde_produits_prec > $solde_charges_prec) {
            $benefice_prec = euro($solde_produits_prec - $solde_charges_prec, $decimal_sep, $target);
            $perte_prec = '';
        } else {
            $perte_prec = euro($solde_charges_prec - $solde_produits_prec, $decimal_sep, $target);
            $benefice_prec = '';
        }
        $tbl[] = array(
            $tab,
            $this->lang->line("comptes_label_total_benefices"),
            $benefice,
            $benefice_prec,
            $tab,
            $tab,
            $this->lang->line("comptes_label_total_pertes"),
            $perte,
            $perte_prec
        );

        // Totaux
        $total = euro(max($solde_charges, $solde_produits), $decimal_sep, $target);
        $total_prec = euro(max($solde_charges_prec, $solde_produits_prec), $decimal_sep, $target);
        // $tbl[] = array(
        //     $tab,
        //     $this->lang->line("comptes_label_total"),
        //     $total,
        //     $total_prec,
        //     $tab,
        //     $tab,
        //     $this->lang->line("comptes_label_total"),
        //     $total,
        //     $total_prec
        // );

        return $tbl;
    }

    /**
     * retourne les dernières références
     *
     * @param unknown_type $term
     */
    function latest($field, $term) {
        $where = array(
            'achat' => 0
        );

        $db_res = $this->db
            ->select($field . ", date_op")
            ->from("ecritures")
            ->where($where)
            ->like($field, $term)
            ->group_by($field)
            ->order_by("date_op desc")
            ->limit(20)
            ->get();
        $select = $this->get_to_array($db_res);

        $res = array();
        foreach ($select as $row) {
            $res[] = $row[$field];
        }
        return $res;
    }

    function json_resultat($year) {
        $depenses = array();
        $recettes = array();
        $cumul_depenses = array();
        $cumul_recettes = array();
        $cumul_recettes[1] = 0;
        $cumul_depenses[1] = 0;
        for ($month = 1; $month <= 12; $month++) {
            $date_op = "$year-$month-01"; // first day of the month
            $date_op = date("Y-m-t", strtotime($date_op)); // last day of the month
            $current_date = date("Y-m-d");

            $datetime_op = new DateTime($date_op);
            $datetime_current = new DateTime($current_date);

            if ($datetime_current < $datetime_op) {
                break;
            }
            $charges = $this->ecritures_model->select_depenses($year, "", $date_op);
            $cumul_depenses[$month] = $charges[0]['montant'];

            $produits = $this->ecritures_model->select_recettes($year, "", $date_op);
            $cumul_recettes[$month] = $produits[0]['montant'];
        }

        $recettes[1] = $cumul_recettes[1];
        $depenses[1] = $cumul_depenses[1];

        for ($month = 2; $month <= 12; $month++) {
            if (isset($cumul_recettes[$month]) && isset($cumul_recettes[$month - 1])) {
                $recettes[$month] = $cumul_recettes[$month] - $cumul_recettes[$month - 1];
            } else {
                $recettes[$month] = 0;
            }
            if (isset($cumul_depenses[$month]) && isset($cumul_depenses[$month - 1])) {
                $depenses[$month] = $cumul_depenses[$month] - $cumul_depenses[$month - 1];
            } else {
                $depenses[$month] = 0;
            }
        }

        $json = "[[";
        $json .= join(", ", $cumul_depenses);
        $json .= "], [";
        $json .= join(", ", $cumul_recettes);
        $json .= "], [";
        $json .= join(", ", $depenses);
        $json .= "], [";
        $json .= join(", ", $recettes);
        $json .= "]]";
        // $json = "[[1, 3, 5], [4, 4]]";
        return $json;
    }

    /**
     * Retourne une chaine de caractère qui identifie un compte de façon unique.
     */
    public function image($key) {
        $vals = $this->get_by_id('id', $key);

        $date = date_db2ht($vals['date_op']);
        return $vals['id'] . ': ' . $date . " " . $vals['montant'] . "€ " . $vals['description'];
    }

    function charges_par_sections($year) {
        echo "charges_par_sections $year";
        exit;
    }
}

/* End of file */
