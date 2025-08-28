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
        // $this->reconciliate();
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
                'gvv_bank_account' => $this->gvv_bank_account()
            ]);
        }

        // $this->dump_parser_result(false);
        $this->dump("Reconciliator", false);
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

    public function gvv_bank_account() {
        return $this->gvv_bank_account;
    }

    /**
     * Retourne la date de solde en français
     */
    public function date_solde() {
        return $this->parser_result['date_solde'] ?? null;
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
}
