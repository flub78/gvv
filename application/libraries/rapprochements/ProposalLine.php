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
    public $line_number = null; // Numéro de ligne dans le relevé
    public $str_releve = null; // Chaîne unique identifiant l'opération
    public $choices_count = 0; // Nombre de choix disponibles
    public $type_string = null; // Type d'opération en texte

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
        if (isset($data['line_number'])) {
            $this->line_number = $data['line_number'];
        }
        if (isset($data['str_releve'])) {
            $this->str_releve = $data['str_releve'];
        }
        if (isset($data['choices_count'])) {
            $this->choices_count = $data['choices_count'];
        }
        if (isset($data['type_string'])) {
            $this->type_string = $data['type_string'];
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
            $html .= '<tr>';

            // Colonne 1: Checkbox avec badge "Non rapproché" et champs cachés
            $line_number = $this->line_number ?? '';
            $str_releve = $this->str_releve ?? '';
            
            $checkbox = '<input type="checkbox" class="unique" name="cb_' . $line_number . '" value="1">';
            $hidden = '<input type="hidden" name="string_releve_' . $line_number . '" value="' . $str_releve . '">';
            $hidden .= '<input type="hidden" name="op_' . $line_number . '" value="' . $this->ecriture . '">';
            $badge = '<div class="badge bg-danger text-white rounded-pill ms-1">Non rapproché</div>';
            
            $status = $checkbox . $badge . $hidden;
            $html .= '<td>' . $status . '</td>';

            // Colonne 2: Description de l'écriture (en vert pour proposition unique)
            if (is_array($this->ecriture)) {
                $description = isset($this->ecriture['description']) ? $this->ecriture['description'] : '';
            } else {
                $description = $this->image;
            }
            $html .= '<td><span class="text-success">' . $description . '</span></td>';
            
            // Colonnes 3-5: vides
            $html .= '<td></td>';
            $html .= '<td></td>';
            $html .= '<td></td>';
            
            // Colonne 6: Numéro de ligne avec info sur le nombre de choix
            $choices_info = $this->choices_count > 0 ? "Choix: " . $this->choices_count . "." : "";
            $html .= '<td>' . $choices_info . ' Ligne:' . $line_number . '</td>';
            
            // Colonne 7: Type d'opération
            $html .= '<td>' . ($this->type_string ?? '') . '</td>';

            $html .= '</tr>';
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
