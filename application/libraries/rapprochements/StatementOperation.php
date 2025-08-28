<?php

/**
 * Classe pour représenter une opération de relevé bancaire
 * 
 * Cette classe encapsule les données d'une opération bancaire
 * et fournit des méthodes pour accéder et manipuler ces données.
 */
class StatementOperation {
    private $CI;
    private $parser_info;
    private $reconciliated = []; // I wonder if it should be a hash too?
    private $proposals = []; // hash of ecriture images with ecritures id as key
    private $multiple_proposals = [];

    /**
     * $reconciliated : référence les écritures GVV qui ont été rapprochées avec cette opération.
     * il peut y en avoir plusieurs. Invariant: La somme des écritures rapprochées doit correspondre au montant dans le relevé.
     * 
     * $proposals : Propose des écritures GVV qui pourraient être rapprochées avec cette opération. Ces écritures on un montant qui correspond au montant dans le relevé.
     * 
     * $multiple_proposals : Propose des ensembles d'écritures GVV qui pourraient être rapprochées avec cette opération. La somme des montants des écritures dans chaque ensemble doit correspondre au montant dans le relevé. Il peut y avoir plusieurs ensembles proposés. L'ensemble doit être rapproché globalement.
     */

    /**
     * Constructeur de la classe
     */
    public function __construct($data = null) {
        $this->CI = &get_instance();

        // Initialize with data if provided
        if ($data == null) {
            return;
        }

        $this->CI->load->library('rapprochements/ReconciliationLine');
        $this->CI->load->model('ecritures_model');

        $this->parser_info = $data['parser_info'];
        $this->gvv_bank_account = isset($data['gvv_bank_account']) ? $data['gvv_bank_account'] : null;
        $this->reconciliate();
    }

    /**
     * Génère une représentation HTML de l'opération
     * 
     * @return string Représentation HTML de l'opération
     */
    public function to_HTML() {
        $html = "<div class='statement-operation'>";
        $html .= "<span class='date'>" . htmlspecialchars($this->date ?? '') . "</span>";
        $html .= "</div>";
        return $html;
    }

    /**
     * Affiche un dump pour le débogage
     * 
     * @param string $title Titre du dump
     * @param bool $exit Indique si le script doit s'arrêter après le dump
     */
    public function dump($title = "", $exit = true) {
        $bt = debug_backtrace();
        $caller = $bt[0];
        echo "<pre>";
        echo "$title:\n";
        echo "from file: " . $caller['file'] . " Line: " . $caller['line'] . "\n";

        echo "date: " . $this->date() . "\n";
        echo "local_date: " . $this->local_date() . "\n";
        echo "value_date: " . $this->value_date() . "\n";
        echo "local_value_date: " . $this->local_value_date() . "\n";
        echo "nature: " . $this->nature() . "\n";
        echo "debit: " . $this->debit() . "\n";
        echo "credit: " . $this->credit() . "\n";
        echo "amount: " . $this->amount() . "\n";
        echo "interbank_label: " . $this->interbank_label() . "\n";
        echo "comments: " . "\n";
        foreach ($this->comments() as $comment) {
            echo "    " . $comment . "\n";
        }
        echo "line: " . $this->line() . "\n";
        echo "type: " . $this->type() . "\n";

        foreach ($this->reconciliated as $reconciliation) {
            $reconciliation->dump("rapprochement");
        }
        echo "Proposals:\n";
        gvv_dump($this->proposals, false);
        echo "</pre>";
        if ($exit) {
            exit;
        }
    }

    private function reconciliate() {

        // Les informations scalaires sont définies
        // Maintenant il faut voir si l'objet est raprochable avec une ou
        // plusieurs écritures comptables

        $this->reconciliated = $this->get_reconciliated();

        if (empty($this->reconciliated)) {
            // Look for proposals
            $this->proposals = $this->get_proposals();

            if (empty($this->proposals)) {
                // try to split into multiple
                $this->proposals = $this->get_multiple_proposals();
            }
        }
    }

    public function date() {
        return isset($this->parser_info->date) ? $this->parser_info->date : null;
    }

    public function local_date() {
        return date_db2ht($this->date());
    }

    public function value_date() {
        return isset($this->parser_info->value_date) ? $this->parser_info->value_date : null;
    }

    public function local_value_date() {
        return date_db2ht($this->value_date());
    }

    public function nature() {
        return isset($this->parser_info->nature) ? $this->parser_info->nature : null;
    }

    public function debit() {
        return isset($this->parser_info->debit) ? $this->parser_info->debit : null;
    }

    public function credit() {
        return isset($this->parser_info->credit) ? $this->parser_info->credit : null;
    }

    public function amount() {
        if (!empty($this->debit())) {
            $amount = $this->debit();
            $amount = abs(str_replace([' ', ','], ['', '.'], $amount));
            return $amount;
        } elseif (!empty($this->credit())) {
            $amount = $this->credit();
            $amount = abs(str_replace([' ', ','], ['', '.'], $amount));
            return $amount;
        }
        return null;
    }

    public function currency() {
        return isset($this->parser_info->currency) ? $this->parser_info->currency : null;
    }

    public function interbank_label() {
        return isset($this->parser_info->interbank_label) ? $this->parser_info->interbank_label : null;
    }

    public function comments() {
        return isset($this->parser_info->comments) ? $this->parser_info->comments : null;
    }

    public function line() {
        return isset($this->parser_info->line) ? $this->parser_info->line : null;
    }

    public function type() {
        // todo move the treatment to here
        return isset($this->parser_info->type) ? $this->parser_info->type : null;
    }

    public function reconciliated() {
        return $this->reconciliated;
    }

    /**
     * Récupère les lignes de rapprochement associées à cette opération.
     * @return array
     */
    public function get_reconciliated() {
        $lines = [];

        // si il y a déjà une ou plusieurs écritures associées
        // On crée une ligne de rapprochement par écriture
        $string_releve = $this->str_releve();

        $gvv_ecritures_list = $this->CI->associations_ecriture_model->get_by_string_releve($string_releve);
        foreach ($gvv_ecritures_list as $gvv_ecriture) {
            $line = new ReconciliationLine(['rapprochements' => $gvv_ecriture]);
            $lines[] = $line;
        }
        return $lines;
    }

    /**
     * les lignes de suggestions
     * @return array
     */
    private function get_proposals() {
        $lines = [];
        if ($this->type() === 'prelevement_pret') {
            // split the amount into two parts
            // Extract capital amorti and interest amounts from comments
            $capital = 0.0;
            $interets = 0.0;

            $comments = $this->comments();

            // Check if we have comments and the first one matches 'CAPITAL AMORTI'
            if (!empty($comments[0]) && strpos($comments[0], 'CAPITAL AMORTI') !== false) {
                // Extract the numeric value after ': '
                $parts = explode(': ', $comments[0]);
                if (count($parts) == 2) {
                    $capital = str_replace(',', '.', trim($parts[1]));
                }

                // Check for interest amount in comments
                if (!empty($comments[1]) && strpos($comments[1], 'INTERETS') !== false) {
                    $parts = explode(': ', $comments[1]);
                    if (count($parts) == 2) {
                        $interets = str_replace(',', '.', trim($parts[1]));
                    }

                    $capital_lines = $this->get_proposals_for_amount($capital);
                    $interets_lines = $this->get_proposals_for_amount($interets);

                    $lines = $capital_lines + $interets_lines;
                }
            }

            return $lines;
        } else {
            $lines = $this->get_proposals_for_amount($this->amount());
        }

        // Si on sait proposer une ou plusieurs écritures à rapprocher
        // Avec le montant global
        // On crée les objets et retourne la liste
        return $lines;
    }

    private function get_proposals_for_amount($amount) {
        $lines = [];
        // Logic to get proposals for a specific amount
        // On utilise le modèle ecritures_model pour obtenir les écritures
        // qui correspondent à l'opération du relevé bancaire
        $delta = $this->CI->session->userdata('rapprochement_delta');
        if (!$delta) {
            $delta = 5; // Default delta value
        }

        if ($this->debit()) {
            $compte1 = null;
            $compte2 = $this->gvv_bank_account;
        } else {
            $compte1 = $this->gvv_bank_account;
            $compte2 = null;
        }

        $start_date = "2000-01-01";
        $end_date = "2100-01-01";
        $reference_date = $this->value_date();

        $lines = $this->CI->ecritures_model->ecriture_selector($start_date, $end_date, $amount, $compte1, $compte2, $reference_date, $delta);
        return $lines;
    }

    /**
     * 
     */
    public function get_multiple_proposals() {
        $this->dump("get_multiple_proposals not implemented yet", false);
        $amount = $this->amount();
        $reference_date = $this->value_date();
        $delta = $this->CI->session->userdata('rapprochement_delta');
                if (!$delta) {
            $delta = 5; // Default delta value
        }
        if ($this->debit()) {
            $compte1 = null;
            $compte2 = $this->gvv_bank_account;
        } else {
            $compte1 = $this->gvv_bank_account;
            $compte2 = null;
        }
        $lines = $this->CI->ecritures_model->ecriture_selector_lower_than($amount, $compte1, $compte2, $reference_date, $delta);

        $this->search_combinations($lines, $amount);

        gvv_dump($lines);
    }

    /**
     * Recherche récursive de combinaisons d'écritures dont la somme des montants est égale à $target_amount.
     * @param array $lines Liste des écritures (tableau associatif id => ['montant' => ...])
     * @param string $target_amount Montant cible à atteindre (décimal, ne pas convertir en float)
     * @param array $current_combination (interne) Combinaison courante d'écritures
     * @param int $start (interne) Index de départ pour éviter les doublons
     * @return array|false Retourne la première combinaison trouvée ou false si aucune
     */
    private function search_combinations($lines, $target_amount) {
        if (count($lines) == 1) {
            foreach ($lines as $key => $line) {
                if ($line['montant'] = $target_amount) {
                    return $lines;
                } else {
                    return false;
                }
            }
        }

        foreach ($lines as $id => $line) {
            $montant = $target_amount - $line['montant'];
            $sub_lines = $lines;
            unset($sub_lines[$id]);

            $search = $this->search_combinations($sub_lines, $montant);
            if ($search !== false) {
                return $search + [$id => $line];
            }
        }
    
        // Aucune combinaison trouvée
        return false;
    }

    public function str_releve() {

        $str = "";
        $str .= $this->date() . "_";
        $str .= $this->nature() . "_";
        $str .= $this->amount() . "_";
        $str .= $this->currency() . "_";
        $str .= $this->value_date() . "_";
        $str .= $this->interbank_label() . "_";
        $str .= implode(" ", $this->comments()) . "_";
        $str = preg_replace('/[^a-zA-Z0-9]+/', '_', $str);
        return $str;
    }
}
