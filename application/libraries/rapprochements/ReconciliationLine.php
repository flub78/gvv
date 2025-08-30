<?php

/**
 * Classe pour représenter une ligne de rapprochement bancaire
 * 
 * Cette classe encapsule les données d'une ligne de rapprochement
 * entre une opération bancaire et une écriture comptable.
 */
class ReconciliationLine {
    private $CI;

    public $proposal = null; // Proposition de rapprochement
    public $rapprochements = []; // Liste des rapprochements associés
    /**
     * Constructeur de la classe
     * ReconciliationLine(['rapprochements' => $gvv_ecriture]);
     * ReconciliationLine(['proposal' => ['ecriture' => $line['ecriture'], 'image' => $line['image']]]);
     *    [rapprochements] => Array
        (
            [id] => 37
            [string_releve] => 2025_02_03_VIR_INST_RE_553498577894_100_EUR_2025_02_03_DE_MR_BLEUSE_NICOLAS_DATE_03_02_2025_10_34_MOTIF_VIREMENT_HDV_ULM_DE_MR_BLEUSE_NICOL_AS_
            [id_ecriture_gvv] => 35223
            [ecriture] => Array
                (
                    [id] => 35223
                    [annee_exercise] => 1970
                    [date_creation] => 2025-08-01
                    [date_op] => 2025-02-04
                    [compte1] => 558
                    [compte2] => 1285
                    [montant] => 100.00
                    [description] => BLEUSE ULM Nicolas - Virement - RE 553498577894
                    [type] => 0
                    [num_cheque] => OpenFlyers : 63515
                    [saisie_par] => fpeignot
                    [gel] => 0
                    [club] => 2
                    [achat] => 
                    [quantite] => 0
                    [prix] => -1.00
                    [categorie] => 0
                )
        )
     */
    public function __construct($data = null) {
        $this->CI = &get_instance();

        // Initialize with data if provided
        if ($data == null) {
            return;
        }

        if (isset($data['proposal'])) {
            $this->ecriture = $data['proposal']['ecriture'];
            $this->image = $data['proposal']['image'];
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

        if ($this->proposal) {
            $html .= "<div class='reconciliation-line'>";
            $html .= '<tr>';

            $html .= '<td>' . $this->ecriture . '</td>';
            $html .= '<td>' . $this->image . '</td>';
            $html .= '<td></td>';
            $html .= '<td></td>';
            $html .= '<td></td>';
            $html .= '<td></td>';
            $html .= '</tr>';
            $html .= "</div>";
        } elseif ($this->rapprochements) {
            $html .= "<div class='reconciliation-line'>";
            $html .= '<tr>';
            $html .= '<td>' . $this->rapprochements['ecriture']['id'] . '</td>';
            $html .= '<td>' . $this->rapprochements['ecriture']['description'] . '</td>';
            $html .= '<td></td>';
            $html .= '<td></td>';
            $html .= '<td></td>';
            $html .= '<td></td>';
            $html .= '</tr>';
            $html .= "</div>";
        }
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
        if ($this->proposal) {
            echo $tab . "proposal:\n";
            echo $tab . $tab . "ecriture: " . $this->proposal['ecriture'] . "\n";
            echo $tab . $tab . "image: " . $this->proposal['image'] . "\n";
        } elseif ($this->rapprochements) {
            echo $tab . "rapprochements:\n";
            echo $tab . $tab . "ecriture: " . $this->rapprochements['id_ecriture_gvv'] . "\n";
            echo $tab . $tab . "image: " . $this->rapprochements['ecriture']['description'] . "\n";
        } else {
            echo "empty\n";
        }

        echo "</pre>";
        if ($exit) {
            exit;
        }
    }
}
