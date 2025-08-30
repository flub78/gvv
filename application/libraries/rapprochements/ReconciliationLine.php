<?php

/**
 * Classe pour représenter une ligne de rapprochement bancaire
 * 
 * Cette classe encapsule les données d'une ligne de rapprochement
 * entre une opération bancaire et une écriture comptable.
 */
class ReconciliationLine {
    private $CI;

    public $rapprochements = []; // Liste des rapprochements associés
    /**
     * Constructeur de la classe
     * ReconciliationLine(['rapprochements' => $gvv_ecriture]);
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
    }


    /**
     * Génère une représentation HTML de la ligne de rapprochement
     * 
     * @return string Représentation HTML de la ligne de rapprochement
     */
    public function to_HTML() {
        $html = "";

        if ($this->rapprochements) {
            $html .= "<div class='reconciliation-line'>";
            $html .= '<tr>';
            $html .= '<td>' . $this->rapprochements['ecriture']['id'] . '</td>';
            $html .= '<td>' . $this->rapprochements['ecriture']['description'] . '</td>';
            $html .= '<td>' . number_format($this->rapprochements['ecriture']['montant'], 2) . ' €</td>';
            $html .= '<td></td>';
            $html .= '<td></td>';
            $html .= '<td></td>';
            $html .= '</tr>';
            $html .= "</div>";
        }

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
        $tab = str_repeat(" ", 4);

        echo "<pre>";
        echo "ReconciliationLine $title: $exit\n";
        echo "from file: " . $caller['file'] . " Line: " . $caller['line'] . "\n";
        
        if ($this->rapprochements) {
            echo $tab . "rapprochements:\n";
            echo $tab . $tab . "id_ecriture_gvv: " . $this->rapprochements['id_ecriture_gvv'] . "\n";
            echo $tab . $tab . "description: " . $this->rapprochements['ecriture']['description'] . "\n";
            echo $tab . $tab . "montant: " . number_format($this->rapprochements['ecriture']['montant'], 2) . " €\n";
        } else {
            echo "empty\n";
        }

        echo "</pre>";
        if ($exit) {
            exit;
        }
    }
}
