<?php

/**
 * Classe pour représenter une ligne de proposition de rapprochement bancaire
 * 
 * Cette classe encapsule les données d'une proposition de rapprochement
 * entre une opération bancaire et une écriture comptable.
 */
class ProposalLine {
    private $CI;

    public $ecriture = null; // Écriture proposée
    public $image = null; // Image/description de la proposition
    public $montant = null; // Montant de l'écriture
    public $confidence = 0; // Niveau de confiance de la proposition (0-100)
    public $criteria = []; // Critères ayant mené à cette proposition

    /**
     * Constructeur de la classe
     * ProposalLine(['ecriture' => $ecriture, 'image' => $image, 'confidence' => $confidence, 'criteria' => $criteria]);
     * ProposalLine(['ecriture_hash' => $ecriture_hash]); // Pour le hash d'écriture avec clé comme ID et valeur comme image
     */
    public function __construct($data = null) {
        $this->CI = &get_instance();

        // Initialize with data if provided
        if ($data == null) {
            return;
        }

        if (isset($data['ecriture_hash'])) {
            // Cas où on reçoit un hash d'écriture [ID => image]
            foreach ($data['ecriture_hash'] as $ecriture_id => $image) {
                $this->ecriture = $ecriture_id;
                $this->image = $image;
                break; // On prend le premier élément du hash
            }
        } else {
            if (isset($data['ecriture'])) {
                $this->ecriture = $data['ecriture'];
            }
            if (isset($data['image'])) {
                $this->image = $data['image'];
            }
            // Support pour les montants séparés
            if (isset($data['montant'])) {
                $this->montant = $data['montant'];
            }
        }
        
        if (isset($data['confidence'])) {
            $this->confidence = $data['confidence'];
        }
        if (isset($data['criteria'])) {
            $this->criteria = $data['criteria'];
        }
    }

    /**
     * Génère une représentation HTML de la ligne de proposition
     * 
     * @return string Représentation HTML de la ligne de proposition
     */
    public function to_HTML() {
        $html = "";

        if ($this->ecriture || $this->image) {
            $html .= "<div class='proposal-line'>";
            $html .= '<tr class="proposal-row">';

            // ID de l'écriture
            if (is_array($this->ecriture)) {
                $html .= '<td>' . (isset($this->ecriture['id']) ? $this->ecriture['id'] : '') . '</td>';
                $html .= '<td>' . (isset($this->ecriture['description']) ? $this->ecriture['description'] : '') . '</td>';
                $html .= '<td>' . (isset($this->ecriture['montant']) ? number_format($this->ecriture['montant'], 2) . ' €' : '') . '</td>';
            } else {
                $html .= '<td>' . $this->ecriture . '</td>';
                $html .= '<td>' . $this->image . '</td>';
                $html .= '<td></td>';
            }

            // Niveau de confiance
            $html .= '<td>' . $this->confidence . '%</td>';
            
            // Critères
            $html .= '<td>' . implode(', ', $this->criteria) . '</td>';
            
            // Action
            $html .= '<td><button class="btn btn-sm btn-success accept-proposal">Accepter</button></td>';

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
        echo "ProposalLine $title\n";
        echo "from file: " . $caller['file'] . " Line: " . $caller['line'] . "\n";
        
        if ($this->ecriture) {
            echo $tab . "ecriture:\n";
            if (is_array($this->ecriture)) {
                echo $tab . $tab . "id: " . (isset($this->ecriture['id']) ? $this->ecriture['id'] : 'N/A') . "\n";
                echo $tab . $tab . "description: " . (isset($this->ecriture['description']) ? $this->ecriture['description'] : 'N/A') . "\n";
                echo $tab . $tab . "montant: " . (isset($this->ecriture['montant']) ? $this->ecriture['montant'] : 'N/A') . "\n";
            } else {
                echo $tab . $tab . "value: " . $this->ecriture . "\n";
            }
        }
        
        if ($this->image) {
            echo $tab . "image: " . $this->image . "\n";
        }
        
        echo $tab . "confidence: " . $this->confidence . "%\n";
        echo $tab . "criteria: " . implode(', ', $this->criteria) . "\n";

        echo "</pre>";
        if ($exit) {
            exit;
        }
    }
}
