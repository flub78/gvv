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
    public $line_number = null; // Numéro de ligne dans le relevé
    public $str_releve = null; // Chaîne unique identifiant l'opération
    public $multiple_count = 0; // Nombre de combinaisons multiples disponibles
    public $type_string = null; // Type d'opération en texte
    public $correlations = []; // Corrélations depuis StatementOperation

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

        if (isset($data['line_number'])) {
            $this->line_number = $data['line_number'];
        }
        if (isset($data['str_releve'])) {
            $this->str_releve = $data['str_releve'];
        }
        if (isset($data['multiple_count'])) {
            $this->multiple_count = $data['multiple_count'];
        }
        if (isset($data['type_string'])) {
            $this->type_string = $data['type_string'];
        }
        if (isset($data['correlations'])) {
            $this->correlations = $data['correlations'];
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
    public function to_HTML($show_manual_buttons = true) {
        $html = "";

        if (!empty($this->combination_data)) {
            // Générer une ligne par écriture dans la combinaison
            foreach ($this->combination_data as $index => $ecriture_data) {
                $html .= '<tr>';

                // Colonne 1: Checkbox pour sélection multiple avec champs cachés si c'est la première ligne
                if ($index === 0) {
                    $line_number = $this->line_number ?? '';
                    $str_releve = $this->str_releve ?? '';
                    $checkbox = '<input type="checkbox" name="cb_' . $line_number . '" value="1">';
                    $hidden = '<input type="hidden" name="string_releve_' . $line_number . '" value="' . $str_releve . '">';

                    // Add hidden checkboxes for each ecriture in the multiple combination
                    foreach ($this->combination_data as $ecriture) {
                        $ecriture_id = $ecriture['ecriture'] ?? '';
                        $hidden .= '<input type="hidden" name="cbmulti_' . $line_number . '_' . $ecriture_id . '" value="1">';
                    }

                    // Créer la liste des IDs d'écritures pour le rapprochement multiple
                    $ecriture_ids = [];
                    foreach ($this->combination_data as $ecriture) {
                        if (isset($ecriture['ecriture'])) {
                            $ecriture_ids[] = $ecriture['ecriture'];
                        }
                    }
                    $ecriture_ids_json = json_encode($ecriture_ids);

                    $badge = '<button type="button" ' .
                        'class="badge bg-primary text-white rounded-pill ms-1 border-0 auto-reconcile-combination-btn" ' .
                        'data-string-releve="' . htmlspecialchars($str_releve) . '" ' .
                        'data-line="' . $line_number . '" ' .
                        'data-ecriture-ids="' . htmlspecialchars($ecriture_ids_json) . '" ' .
                        'title="Cliquer pour rapprocher automatiquement avec toutes les écritures de la combinaison">' .
                        'Rapprocher' .
                        '</button>';

                    // Bouton pour rapprochement manuel
                    $manual_button = '<button type="button" class="badge bg-warning text-dark rounded-pill ms-1 border-0 manual-reconcile-btn" 
                                     data-string-releve="' . htmlspecialchars($str_releve) . '" 
                                     data-line="' . $line_number . '"
                                     title="Cliquer pour effectuer un rapprochement manuel">
                                     Rapprochement manuel
                                     </button>';
                    if ($show_manual_buttons) {
                        $status = $checkbox . $badge . $manual_button . $hidden;
                    } else {
                        $status = $badge . $hidden;
                    }
                    $html .= '<td>' . $status . '</td>';
                } else {
                    $html .= '<td></td>';
                }

                // Colonne 2: Description de l'écriture avec lien vers l'écriture
                $description = $ecriture_data['image'];
                $ecriture_id = isset($ecriture_data['ecriture']) ? $ecriture_data['ecriture'] : '';

                if ($ecriture_id) {
                    // Créer un lien vers l'écriture
                    $ecriture_url = site_url('compta/edit/' . $ecriture_id);

                    // Récupérer le coefficient de corrélation pour afficher en tooltip
                    $tooltip = '';
                    if (isset($this->correlations[$ecriture_id])) {
                        $correlation = $this->correlations[$ecriture_id]['correlation'];
                        $confidence_percent = round($correlation * 100, 1);
                        $tooltip = ' title="Indice de confiance: ' . $confidence_percent . '%"';
                    }

                    $html .= '<td><a href="' . $ecriture_url . '" class="text-decoration-none"' . $tooltip . '>' . $description . '</a></td>';
                } else {
                    $html .= '<td>' . $description . '</td>';
                }

                // Colonnes 3-7: vides
                $html .= '<td></td>';
                $html .= '<td></td>';
                $html .= '<td></td>';
                $html .= '<td></td>';
                $html .= '<td></td>';

                $html .= '</tr>';
            }
        }

        return $html;
    }

    function ecritures_count() {
        return count($this->combination_data);
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
        echo $tab . "ecritures count: " . $this->ecritures_count() . "\n";

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
