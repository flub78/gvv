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
            // Cette méthode doit maintenant générer une ligne complète avec checkbox et badge
            $html .= '<tr>';
            
            // Colonne 1: Checkbox avec badge "Rapproché" cliquable et champ caché
            $line_number = $this->rapprochements['line'] ?? '';
            $str_releve = $this->rapprochements['str_releve'] ?? '';
            
            $status = '<input type="checkbox" name="cbdel_' . $line_number . '" value="1">';
            
            // Badge "Rapproché" cliquable pour supprimer le rapprochement
            $status .= '<button type="button" class="badge bg-success text-white rounded-pill ms-2 border-0 auto-unreconcile-btn" 
                        data-string-releve="' . htmlspecialchars($str_releve) . '" 
                        data-line="' . $line_number . '"
                        title="Cliquer pour supprimer le rapprochement">
                        Rapproché
                        </button>';
            
            $status .= '<input type="hidden" name="string_releve_' . $line_number . '" value="' . $str_releve . '">';
            
            $html .= '<td>' . $status . '</td>';

            // Colonne 2: Lien vers l'écriture ou message d'erreur si elle n'existe plus
            $id_ecriture_gvv = $this->rapprochements['id_ecriture_gvv'] ?? '';

            // Check if ecriture_exists flag is set and false
            if (isset($this->rapprochements['ecriture_exists']) && !$this->rapprochements['ecriture_exists']) {
                // L'écriture n'existe plus - afficher message d'erreur en rouge
                $id_display = $id_ecriture_gvv ? htmlspecialchars($id_ecriture_gvv) : 'inconnu';
                $ecriture_display = '<span class="text-danger fw-bold">Rapproché à une écriture qui n\'existe plus (ID: ' . $id_display . ')</span>';
            } else {
                // L'écriture existe - afficher le lien normal
                $ecriture_display = anchor_ecriture($id_ecriture_gvv);
            }

            $html .= '<td>' . $ecriture_display . '</td>';
            
            // Colonnes 3-5: vides
            $html .= '<td></td>';
            $html .= '<td></td>';
            $html .= '<td></td>';
            $html .= '<td></td>';
            $html .= '<td></td>';

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
