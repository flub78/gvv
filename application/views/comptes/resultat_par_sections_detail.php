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
        $this->load->model('associations_ecriture_model');
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
    // warning_count
    public function count_account($compte = '') {
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
     *            solde (localisée), si non spécifié, retourne le dernier solde du compte
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
        $date_db = date_ht2db($date); // date_ht2db est transparent si le format est déjà db
        $where = "date_op $operation \"" . $date_db . "\" and id < \"$id\"";

        $debit = $this->db->select_sum('montant')->from($this->table)->where($where)->where(array(
            'compte1' => $compte
        ))->get()->row()->montant;

        gvv_debug("sql: " . $this->db->last_query());

        $credit = $this->db->select_sum('montant')->from($this->table)->where($where)->where(array(
            'compte2' => $compte
        ))->get()->row()->montant;

        gvv_debug("sql: " . $this->db->last_query());
        $solde = $credit - $debit;  // $debut and $credit could be null...

        gvv_debug("solde_jour compte=$compte, date=$date, id=$id, solde=$solde");
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

        // Get previous state to check if we're freezing an unfrozen entry
        $previous = $this->get_by_id('id', $id);
        $was_frozen = isset($previous['gel']) && $previous['gel'] == 1;
        $is_frozen = isset($data['gel']) && $data['gel'] == 1;

        // Allow modification if:
        // - Entry is not frozen (gel == 0)
        // - OR we're freezing an unfrozen entry (was_frozen == false && is_frozen == true)
        if ($data['gel'] == 0 || (!$was_frozen && $is_frozen)) {
            $this->db->trans_start();
            $this->comptes_model->maj_comptes($compte1, $compte2, $montant);
            $this->update($id, $data);
            $this->db->trans_complete();
        } else {
            // Entry was already frozen and we're trying to modify it (not just freezing it)
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

            // Delete associated attachments and their files before deleting the ecriture
            $this->delete_attachments_for_ecriture($id);

            $res = $this->db->delete($this->table, array(
                'id' => $id
            ));
            $this->associations_ecriture_model->delete_rapprochements($id);
            $this->db->trans_complete();
            return $res;
        } else {
            $msg = "Suppression impossible, écriture gelée";
            if (! $this->session->userdata('popup')) {
                $this->session->set_flashdata('popup', $msg);
            }
            return false;
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
     * Selects accounting entries to be deleted based on specific criteria
     *
     * @param string $start_date The start date for filtering entries
     * @param string $end_date The end date for filtering entries
     * @param int $section_id The ID of the section/club
     * @param bool $all Flag to determine whether to include all entries or filter specific entries
     * @return array Filtered accounting entries
     */
    function select_ecritures_to_delete($start_date, $end_date, $section_id = 0, $all) {
        $this->db->select("ecritures.id, date_op, montant, description, num_cheque, ecritures.club, compte1.id as compte1, compte1.codec as codec1, compte1.nom as nom1, compte2.codec as codec2, compte2.nom as nom2, compte2.id as compte2")
            ->from("ecritures")
            ->join("comptes as compte1", "compte1.id = ecritures.compte1", "left")
            ->join("comptes as compte2", "compte2.id = ecritures.compte2", "left")
            ->where("(compte1.codec = '411' OR compte2.codec = '411')");

        if ($section_id) {
            $this->db->where("ecritures.club", $section_id);
        }

        $this->db->where("ecritures.date_op BETWEEN '$start_date' AND '$end_date'");

        if (!$all) {
            $this->db->where("ecritures.num_cheque LIKE 'OpenFlyers : %'");
        }

        $db_res = $this->db->get();
        // echo $this->db->last_query();

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
     * Normalement les dépenses ne sont qu'au débit des comptes 600, mais il faut aussi prendre en compte
     * les crédits (avoir, annulation de dépenses)
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