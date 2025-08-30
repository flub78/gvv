<?php

/**
 * Classe pour représenter une combinaison de propositions de rapprochement bancaire
 * 
 * Cette classe encapsule une combinaison d'écritures dont le montant additionné
 * correspond au montant d'une opération de relevé (StatementOperation).
 */
class MultiProposalCombination {
    private $CI;

    public $proposals = []; // Liste des ProposalLine composant cette combinaison
    public $totalAmount = 0; // Montant total de la combinaison
    public $confidence = 0; // Niveau de confiance global de la combinaison (0-100)
    public $combinationId = null; // Identifiant unique de la combinaison

    /**
     * Constructeur de la classe
     * MultiProposalCombination(['proposals' => $proposals, 'totalAmount' => $amount, 'confidence' => $confidence]);
     */
    public function __construct($data = null) {
        $this->CI = &get_instance();

        // Initialize with data if provided
        if ($data == null) {
            return;
        }

        if (isset($data['proposals'])) {
            $this->proposals = $data['proposals'];
            // Calculer le montant total automatiquement
            $this->calculateTotalAmount();
        }
        if (isset($data['totalAmount'])) {
            $this->totalAmount = $data['totalAmount'];
        }
        if (isset($data['confidence'])) {
            $this->confidence = $data['confidence'];
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
        foreach ($this->proposals as $proposal) {
            if (is_array($proposal->ecriture) && isset($proposal->ecriture['montant'])) {
                $total += $proposal->ecriture['montant'];
            }
        }
        $this->totalAmount = $total;
    }

    /**
     * Ajoute une proposition à la combinaison
     * 
     * @param ProposalLine $proposal La proposition à ajouter
     */
    public function addProposal($proposal) {
        $this->proposals[] = $proposal;
        $this->calculateTotalAmount();
    }

    /**
     * Retire une proposition de la combinaison
     * 
     * @param int $index Index de la proposition à retirer
     */
    public function removeProposal($index) {
        if (isset($this->proposals[$index])) {
            array_splice($this->proposals, $index, 1);
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

        if (!empty($this->proposals)) {
            $html .= "<div class='multi-proposal-combination' data-combination-id='" . $this->combinationId . "'>";
            $html .= "<div class='combination-header'>";
            $html .= "<h5>Combinaison de " . count($this->proposals) . " écritures";
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
            $html .= "<th>Confiance</th>";
            $html .= "<th>Critères</th>";
            $html .= "<th>Action</th>";
            $html .= "</tr>";
            $html .= "</thead>";
            $html .= "<tbody>";

            foreach ($this->proposals as $index => $proposal) {
                $html .= $proposal->to_HTML();
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
        echo $tab . "proposals count: " . count($this->proposals) . "\n";

        foreach ($this->proposals as $index => $proposal) {
            echo $tab . "proposal[$index]:\n";
            if (is_array($proposal->ecriture)) {
                echo $tab . $tab . "id: " . (isset($proposal->ecriture['id']) ? $proposal->ecriture['id'] : 'N/A') . "\n";
                echo $tab . $tab . "description: " . (isset($proposal->ecriture['description']) ? $proposal->ecriture['description'] : 'N/A') . "\n";
                echo $tab . $tab . "montant: " . (isset($proposal->ecriture['montant']) ? $proposal->ecriture['montant'] : 'N/A') . "\n";
            }
            echo $tab . $tab . "confidence: " . $proposal->confidence . "%\n";
        }

        echo "</pre>";
        if ($exit) {
            exit;
        }
    }
}
