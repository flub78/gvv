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
    private $reconciliations_lines = [];

    /**
     * Constructeur de la classe
     */
    public function __construct($parser_info = null) {
        $this->CI = &get_instance();

        // Initialize with data if provided
        if ($parser_info == null) {
            return;
        }

        $this->CI->load->library('rapprochements/ReconciliationLine');
        $this->parser_info = $parser_info;
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
        echo "<pre>";
        echo "$title:\n";
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
        echo "</pre>";
        if ($exit) {
            exit;
        }
    }

    private function reconciliate() {

        // Les informations scalaires sont définies
        // Maintenant il faut voir si l'objet est raprochable avec une ou
        // plusieurs écritures comptables

        $this->reconciliations_lines = $this->get_reconciliations_lines();

        gvv_dump($this->parser_info);
        $this->dump("StatementOperation");
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

    public function reconciliations_lines() {
        return $this->reconciliations_lines;
    }

    /**
     * Récupère les lignes de rapprochement associées à cette opération.
     * ou les lignes de suggestions
     * @return array
     */
    public function get_reconciliations_lines() {
        $lines = [];
        if ($this->type() === 'prelevement_pret') {
            // split the amount into two parts
            return $lines;
        }

        // si il y a déjà une ou plusieurs écritures associées
        // On crée une ligne de rapprochement par écriture
        $string_releve = $this->str_releve();

        $gvv_ecritures_list = $this->CI->associations_ecriture_model->get_by_string_releve($string_releve);
        foreach ($gvv_ecritures_list as $gvv_ecriture) {
            $line = new ReconciliationLine(['rapprochements' => $gvv_ecriture]);
            $lines[] = $line;
        }
        if (!empty($lines)) {
            return $lines;
        }

        // Si on sait proposer une ou plusieurs écritures à rapprocher
        // Avec le montant global
        // On crée les objets et retourne la liste
        if (!empty($lines)) {
            return $lines;
        }

        // Si il existe une proposition unique de décomposition du montant
        // on va la proposer
        if (!empty($lines)) {
            return $lines;
        }

        // Ni rapprochements ni suggestions
        return [];
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
