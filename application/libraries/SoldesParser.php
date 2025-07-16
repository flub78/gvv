<?php

/**
 * Classe pour parser le fichier CSV des soldes client
 * 
 * Parsing 
 * 
 */
class SoldesParser {
    private $data = [];

    /**
     * Parse le fichier CSV du Grand Livre
     * 
     * @param string $filePath Chemin vers le fichier CSV
     * @return array Structure de données parsée
     */
    public function parse($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("Le fichier {$filePath} n'existe pas.");
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new Exception("Impossible d'ouvrir le fichier {$filePath}.");
        }

        $this->data = [];

        $lineNumber = 0;

        while (($line = fgets($handle)) !== false) {
            $lineNumber++;
            $line = trim($line);

            // Ignorer les lignes vides
            if (empty($line)) {
                continue;
            }

            // Ignorer l'entête
            if ($lineNumber < 2) {

                // Vérifier que l'en-tête correspond au format attendu
                if ($lineNumber === 1 && $line !== "ID;Nom;Profil;Type de compte;Balance") {
                    throw new Exception("Format de fichier incorrect. Ce n'est pas un export de solde OpenFlyers");
                }

                continue;
            }

            $fields = $this->parseCsvLine($line);

            // Ignore les soldes null
            if (!$fields[4]) {
                continue;
            }

            $this->data[] = $fields;
        }

        fclose($handle);
        return $this->data;
    }

    /**
     * Parse une ligne CSV en tenant compte des points-virgules
     */
    private function parseCsvLine($line) {
        return array_map('trim', explode(';', $line));
    }


    /**
     * Retourne les données parsées sous forme de JSON
     */
    public function toJson() {
        return json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Converts parsed table data into an array with additional controls and metadata
     *
     * @param array $table The parsed table data to process
     * @param mixed $compare_date Optional date for comparing account balances
     * @return array Processed table data with additional metadata and controls
     */
    public function arrayWithControls($table, $compare_date = false) {
        $CI = &get_instance();
        $CI->load->library('gvvmetadata');
        $CI->load->model('comptes_model');
        $CI->load->model('associations_of_model');
        $CI->load->model('ecritures_model');
        $CI->load->model('sections_model');

        // values for the compte selector select
        $compte_selector = $CI->comptes_model->selector_with_null(["codec =" => "411"], TRUE);

        $section = $CI->sections_model->section();

        // $table = $this->parse($filePath);
        $line = 0;
        $result = [];
        foreach ($table as $row) {
            $checkbox = '<input type="checkbox"'
                . ' name="cb_' . $line . '"'
                . ' onchange="toggleRowSelection(this)">';
            $id_of = $row[0];
            $nom_of = $row[1];
            $profil = $row[2];
            $type = $row[3];
            $solde = euro($row[4]);
            $associated_gvv = $CI->associations_of_model->get_gvv_account($id_of);
            $initialized = $CI->ecritures_model->is_account_initialized($associated_gvv);
            if ($associated_gvv && $section) {
                $gvv_cpt = $CI->comptes_model->get_by_id('id', $associated_gvv);
                if ($section['id'] != $gvv_cpt['club']) {
                    continue;
                }
            }
            if ($associated_gvv && !$initialized) {
                $checkbox = '<input type="checkbox"'
                    . ' name="cb_' . $line . '"'
                    . ' onchange="toggleRowSelection(this)">';
            } else {
                $checkbox = ($initialized) ? "Initialisé" : "";
            }

            if ($initialized) {
                $compte_gvv = $associated_gvv;
                $image = $CI->comptes_model->image($compte_gvv);
                $compte_gvv = anchor(controller_url("compta/journal_compte/" . $associated_gvv), $image);
            } else {
                $attrs = 'class="form-control big_select" onchange="updateRow(this, '
                    . $id_of . ',\'' . $nom_of  . '\')"';
                $compte_gvv = dropdown_field(
                    "compte_" . $line,
                    $associated_gvv,
                    $compte_selector,
                    $attrs

                );
            }

            if ($compare_date) {
                if ($associated_gvv) {
                    $solde_gvv = euro($CI->ecritures_model->solde_compte($associated_gvv, date_db2ht($compare_date)));
                } else {
                    $solde_gvv = "0.00 €";
                }

                if ($solde != $solde_gvv) {
                    $solde = '<div class="text-danger">' . $solde . '</div>';
                    $solde_gvv = '<div class="text-danger">' . $solde_gvv . '</div>';
                    $id_of = "diff " . $id_of;
               }
                
                $result[] = [$id_of, $nom_of, $profil, $compte_gvv, $solde, $solde_gvv];
            } else {
                $result[] = [$checkbox, $id_of, $nom_of, $profil, $compte_gvv, $solde];
            }
            $line++;
        }
        return $result;
    }
}
