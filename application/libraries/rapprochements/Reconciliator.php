<?php

/**
 * Classe pour gérer les rapprochements bancaires
 * 
 * Cette classe fournit des méthodes pour effectuer des rapprochements
 * entre les opérations bancaires et les écritures comptables.
 * 
 * Elle prend en charge le filtre et le résultat dépend du filtre.
 */
class Reconciliator {
    private $CI;
    private $parser_result;
    private $gvv_bank_account;
    private $operations = [];
    private $filename = "";

    private $rapproched_operations_count = 0;
    private $unique_count = 0;
    private $choices_count = 0;
    private $multiple_count = 0;
    private $no_suggestion_count = 0;

    /**
     * Constructeur de la classe
     */
    public function __construct($parser_result = null) {
        $this->CI = &get_instance();
        // Chargement des modèles et bibliothèques nécessaires
        $this->CI->load->model('associations_releve_model');
        $this->CI->load->library('rapprochements/StatementOperation');

        // Only run reconciliation if we have parser result
        if ($parser_result == null) {
            return;
        }

        $this->parser_result = $parser_result;
        $this->reconciliate();
    }

    public function set_filename($filename) {
        $this->filename = $filename;
    }

    public function filename() {
        return $this->filename;
    }

    public function basename() {
        return basename($this->filename);
    }

    /**
     * Effectue le rapprochement entre les opérations bancaires et les écritures
     * 
     * @param array $operations_bancaires Liste des opérations bancaires
     * @param array $ecritures_comptables Liste des écritures comptables
     * @return array Résultat du rapprochement avec les correspondances trouvées
     */
    public function dump_parser_result($exit = true) {
        gvv_dump($this->parser_result, $exit);
    }

    /**
     * Affiche un dump pour le débogage
     * 
     * @param string $title Titre du dump
     * @param bool $exit Indique si le script doit s'arrêter après le dump
     */
    public function dump($title = "", $exit = true) {
        echo "<pre>";
        echo "$title:\n";
        echo "bank: " . $this->bank() . "\n";
        echo "iban: " . $this->iban() . "\n";
        echo "section: " . $this->section() . "\n";
        echo "gvv_bank_account: " . $this->gvv_bank_account() . "\n";
        echo "date_solde: " . $this->date_solde() . "\n";
        echo "titles: ";
        print_r($this->titles());
        echo "total operations: " . $this->total_operation_count() . "\n";
        echo "filtered operations: " . count($this->operations) . "\n";
        foreach ($this->operations as $op) {
            $op->dump("StatementOperation", false);
        }
        echo "</pre>";
        if ($exit) {
            exit;
        }
    }

    private function reconciliate() {
        if ($this->parser_result === null) {
            $this->dump("No parser result to reconciliate", false);
            return;
        }

        $this->gvv_bank_account = $this->CI->associations_releve_model->get_gvv_account($this->iban());

        $filter_active = $this->CI->session->userdata('filter_active');
        $startDate = $this->CI->session->userdata('startDate');
        $endDate = $this->CI->session->userdata('endDate');
        $filter_type = $this->CI->session->userdata('filter_type');
        $type_selector = $this->CI->session->userdata('type_selector');

        foreach ($this->parser_result['ops'] as $op) {
            // D'abord on filtre sur la date et le type. Ce sont les informations de filtrage indépendantes 
            // du rapprochement
            if ($filter_active) {
                $op_date = $op->date;
                if ($startDate) {
                    // les dates sont au format "yyyy-mm-dd" 
                    // si $op_date < $startDate
                    if ($op_date < $startDate) {
                        continue; // Skip this operation
                    }
                }
                if ($endDate) {
                    // les dates sont au format "yyyy-mm-dd" 
                    // si $op_date > $endDate
                    if ($op_date > $endDate) {
                        continue; // Skip this operation
                    }
                }
                if ($type_selector && ($type_selector != "all")) {
                    if ($type_selector != $op->type) {
                        continue;
                    }
                }
            }

            // Entrée de relevé à traiter, elle sera peut-être éliminé quand même en fonction de ses
            // caractéristiques, mais on commence l'analyse
            $statement_operation = new StatementOperation([
                'parser_info' => $op,
                'gvv_bank_account' => $this->gvv_bank_account(),
                'recognized_types' => $this->recognized_types()
            ]);

            if ($filter_active) {

                if ($filter_type && ($filter_type != "display_all")) {
                    if ($filter_type == 'filter_unmatched') {
                        if ($statement_operation->is_rapproched()) {
                            // On élimine cette opération
                            continue;
                        }
                    } elseif ($filter_type == 'filter_matched') {
                        if (!$statement_operation->is_rapproched()) {
                            // On élimine cette opération
                            continue;
                        }
                    } elseif ($filter_type == 'filter_unmatched_0') {
                        if (!$statement_operation->nothing_found()) {
                            // On élimine cette opération
                            continue;
                        }
                    } elseif ($filter_type == 'filter_unmatched_1') {
                        if (!$statement_operation->is_unique()) {
                            // On élimine cette opération
                            continue;
                        }
                    } elseif ($filter_type == 'filter_unmatched_choices') {
                        if ($statement_operation->is_rapproched() || 
                            !$statement_operation->is_multiple()) {
                            // On élimine cette opération car elle ne correspond pas au filtre "plusieurs choix"
                            continue;
                        }
                    } elseif ($filter_type == 'filter_unmatched_multi') {
                        if (!$statement_operation->is_multiple_combination()) {
                            // $statement_operation->dump("Not multiple combination", false);
                            // On élimine cette opération
                            continue;
                        } else {    
                            // $statement_operation->dump("Is multiple combination", false);
                        }
                    }
                }

                if ($type_selector && ($type_selector != "all")) {
                    if ($type_selector != $statement_operation->type()) {
                        // On élimine cette opération
                        continue;
                    }
                }
            }

            $this->operations[] = $statement_operation;

            if ($statement_operation->is_rapproched()) {
                $this->rapproched_operations_count++;
            } elseif ($statement_operation->is_unique()) {
                $this->unique_count++;
            } elseif ($statement_operation->choices_count()) {
                $this->choices_count++;
            } elseif ($statement_operation->multiple_count()) {
                $this->multiple_count++;
            } else {
                $this->no_suggestion_count++;
            }
        }

        // $this->dump("Reconciliator", false);
    }

    public function bank() {
        return $this->parser_result['bank'] ?? null;
    }

    public function iban() {
        return $this->parser_result['iban'] ?? null;
    }

    public function section() {
        return $this->parser_result['section'] ?? null;
    }

    public function solde() {
        return $this->parser_result['solde'] ?? null;
    }

    public function gvv_bank_account() {
        return $this->gvv_bank_account;
    }

    /**
     * Retourne la date de solde en français
     */
    public function date_solde() {
        return $this->parser_result['date_solde'] ?? null;
    }

    public function start_date() {
        return $this->parser_result['start_date'] ?? null;
    }

    public function end_date() {
        return $this->parser_result['end_date'] ?? null;
    }

    public function local_start_date() {
        return date_db2ht($this->start_date());
    }

    public function local_end_date() {
        return date_db2ht($this->end_date());
    }

    /**
     * Retourne les titres des colonnes du parser_result si disponibles
     *
     * @return array|null
     */
    public function titles() {
        return $this->parser_result['titles'] ?? null;
    }

    /**
     * Retourne le nombre total d'opérations bancaires dans le parser_result
     *
     * @return int
     */
    public function total_operation_count() {
        if (isset($this->parser_result['ops']) && is_array($this->parser_result['ops'])) {
            return count($this->parser_result['ops']);
        }
        return 0;
    }

    public function filtered_operations_count() {
        return count($this->operations);
    }

    public function rapproched_operations_count() {
        return $this->rapproched_operations_count;
    }

    /**
     * Retourne l'en-tête du relevé bancaire s'il est disponible
     */
    public function header() {
        $header = [];
        $header[] = ["Banque: ",  $this->bank(), '', ''];
        $gvv_bank_acount = $this->gvv_bank_account();

        if ($gvv_bank_acount) {
            $compte_bank_gvv = anchor_compte($gvv_bank_acount);
            $header[] = ["IBAN: ",  $this->iban(), 'Compte GVV:', $compte_bank_gvv];
        } else {
            // On affiche un sélecteur
            $compte_selector = $this->CI->comptes_model->selector_with_null(['codec' => 512], TRUE);
            $attrs = 'class="form-control big_select" onchange="associateAccount(this, \''
                . $this->iban()  . '\')"';
            $compte_bank_gvv = dropdown_field(
                "compte_bank",
                "",
                $compte_selector,
                $attrs
            );
            $header[] = ["IBAN: ",  $this->iban(), 'Compte GVV:', $compte_bank_gvv];
        }

        $header[] = ["Section: ",  $this->section(), 'Fichier', $this->basename()];

        $header[] = ["Date de solde: ",  $this->date_solde(), "Solde: ", euro($this->solde())];
        $header[] = ["Date de début: ",  $this->local_start_date(), "Date de fin: ",  $this->local_end_date()];

        // $rap = $ot['count_rapproches'] . ", Choix: " . $ot['count_choices'] . ", Uniques: " . $ot['count_uniques'];
        $header[] = [
            'Nombre opérations: ',
            $this->filtered_operations_count() 
            . ' / ' . $this->total_operation_count(),

            'Rapprochées / Uniques / Choix / Combinaisons / sans suggestion:',

            $this->rapproched_operations_count() 
            . ' / ' . $this->unique_count 
            . ' / ' . $this->choices_count 
            . ' / ' . $this->multiple_count
            . ' / ' . $this->no_suggestion_count
        ];
        return $header;
    }

    public function to_HTML() {
        $res = "";
        
        foreach ($this->operations as $op) {
            $res .= $op->to_HTML();
        }
        return $res;
    }

    /**
     * Retourne les types d'opérations reconnus
     * 
     * @return array Associative array des types d'opérations
     */
    function recognized_types() {
        return  [
            "cheque_debite"       => "Chèque débité",
            "frais_bancaire"      => "Frais bancaire",
            "paiement_cb"         => "Paiement en carte bancaire",
            "prelevement"         => "Prélèvement",
            "prelevement_pret"    => "Prélèvement prêt",
            "virement_emis"       => "Virement émis",
            "encaissement_cb"     => "Encaissement carte bancaire",
            "remise_cheque"       => "Remise de chèque",
            "remise_especes"      => "Remise d'espèces",
            "regularisation_frais" => "Régularisation de frais",
            "virement_recu"       => "Virement reçu",
            "inconnu"             => "Opération inconnue"
        ];
    }
}
