<?php

/**
 * Classe pour représenter une combinaison de propositions de rapprochement bancaire
 * 
 * Cette classe encapsule une combinaison d'écritures dont le montant additionné
 * correspond au montant d'une opération de relevé (StatementOperation).
 */
class MultiProposalCombination {
    private $CI;

    public $combination_data = []; // Données brutes de la combinaison d'écritures
    public $totalAmount = 0; // Montant total de la combinaison
    public $confidence = 0; // Niveau de confiance global de la combinaison (0-100)
    public $combinationId = null; // Identifiant unique de la combinaison

    /**
     * Constructeur de la classe
     * MultiProposalCombination(['combination_data' => $combination_array]); // Pour une combinaison depuis get_multiple_proposals
     */
    public function __construct($data = null) {
        $this->CI = &get_instance();

        // Initialize with data if provided
        if ($data == null) {
            return;
        }

        if (isset($data['combination_data'])) {
            // Cas où on reçoit une combinaison depuis get_multiple_proposals
            // $data['combination_data'] est un array d'écritures
            $this->combination_data = $data['combination_data'];
            $this->calculateTotalAmount();
            $this->confidence = 70; // Confiance plus faible pour les combinaisons multiples
        }
        
        if (isset($data['combinationId'])) {
            $this->combinationId = $data['combinationId'];
        } else {
            // Générer un ID unique
            $this->combinationId = uniqid('combo_');
        }
    }

    /**
     * Calcule le montant total de la combinaison
     */
    private function calculateTotalAmount() {
        $total = 0;
        foreach ($this->combination_data as $ecriture_data) {
            if (isset($ecriture_data['montant'])) {
                $total += floatval($ecriture_data['montant']);
            }
        }
        $this->totalAmount = $total;
    }

    /**
     * Ajoute une écriture à la combinaison
     * 
     * @param array $ecriture_data Les données de l'écriture à ajouter
     */
    public function addEcriture($ecriture_data) {
        $this->combination_data[] = $ecriture_data;
        $this->calculateTotalAmount();
    }

    /**
     * Retire une écriture de la combinaison
     * 
     * @param int $index Index de l'écriture à retirer
     */
    public function removeEcriture($index) {
        if (isset($this->combination_data[$index])) {
            array_splice($this->combination_data, $index, 1);
            $this->calculateTotalAmount();
        }
    }

    /**
     * Génère une représentation HTML de la combinaison de propositions
     * 
     * @return string Représentation HTML de la combinaison
     */
    public function to_HTML() {
        $html = "";

        if (!empty($this->combination_data)) {
            $html .= "<div class='multi-proposal-combination' data-combination-id='" . $this->combinationId . "'>";
            $html .= "<div class='combination-header'>";
            $html .= "<h5>Combinaison de " . count($this->combination_data) . " écritures";
            $html .= " - Total: " . number_format($this->totalAmount, 2) . " €";
            $html .= " - Confiance: " . $this->confidence . "%</h5>";
            $html .= "<button class='btn btn-sm btn-success accept-combination'>Accepter la combinaison</button>";
            $html .= "</div>";

            $html .= "<table class='table table-sm combination-table'>";
            $html .= "<thead>";
            $html .= "<tr>";
            $html .= "<th>ID</th>";
            $html .= "<th>Description</th>";
            $html .= "<th>Montant</th>";
            $html .= "<th>Action</th>";
            $html .= "</tr>";
            $html .= "</thead>";
            $html .= "<tbody>";

            foreach ($this->combination_data as $index => $ecriture_data) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($ecriture_data['ecriture']) . '</td>';
                $html .= '<td>' . htmlspecialchars($ecriture_data['image']) . '</td>';
                $html .= '<td>' . number_format($ecriture_data['montant'], 2) . ' €</td>';
                $html .= '<td><button class="btn btn-sm btn-warning remove-from-combination" data-index="' . $index . '">Retirer</button></td>';
                $html .= '</tr>';
            }

            $html .= "</tbody>";
            $html .= "</table>";
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
        echo "MultiProposalCombination $title\n";
        echo "from file: " . $caller['file'] . " Line: " . $caller['line'] . "\n";
        
        echo $tab . "combinationId: " . $this->combinationId . "\n";
        echo $tab . "totalAmount: " . number_format($this->totalAmount, 2) . " €\n";
        echo $tab . "confidence: " . $this->confidence . "%\n";
        echo $tab . "ecritures count: " . count($this->combination_data) . "\n";

        foreach ($this->combination_data as $index => $ecriture_data) {
            echo $tab . "ecriture[$index]:\n";
            echo $tab . $tab . "id: " . $ecriture_data['ecriture'] . "\n";
            echo $tab . $tab . "description: " . $ecriture_data['image'] . "\n";
            echo $tab . $tab . "montant: " . $ecriture_data['montant'] . " €\n";
        }

        echo "</pre>";
        if ($exit) {
            exit;
        }
    }
}
