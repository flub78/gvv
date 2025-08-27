<?php

/**
 * Classe pour représenter une ligne de rapprochement bancaire
 * 
 * Cette classe encapsule les données d'une ligne de rapprochement
 * entre une opération bancaire et une écriture comptable.
 */
class ReconciliationLine {
    private $CI;
    private $rapprochements;

    /**
     * Constructeur de la classe
     */
    public function __construct($data = null) {
        $this->CI = &get_instance();

        // Initialize with data if provided
        if ($data == null) {
            return;
        }

        if (isset($data['rapprochements'])) {
            $this->rapprochements = $data['rapprochements'];
        }
        echo "ReconciliationLine::__construct\n";
        gvv_dump($this->rapprochements);
    }


    /**
     * Génère une représentation HTML de la ligne de rapprochement
     * 
     * @return string Représentation HTML de la ligne de rapprochement
     */
    public function to_HTML() {
        $html = "<div class='reconciliation-line'>";
        $html .= "<span class='amount'>" . htmlspecialchars($this->amount ?? '') . "</span>";
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
        echo "amount: " . $this->amount() . "\n";
        echo "</pre>";
        if ($exit) {
            exit;
        }
    }
}
