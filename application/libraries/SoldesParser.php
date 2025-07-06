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

    public function arrayWithControls($filePath) {
        $CI = &get_instance();
        $CI->load->library('gvvmetadata');
        $CI->load->model('comptes_model');
        // values for the compte selector select
        $compte_selector = $CI->comptes_model->selector_with_null(["codec =" => "411"], TRUE);

        $table = $this->parse($filePath);
        $line = 0;
        $result = [];
        foreach ($table as $row) {
            $checkbox = '<input type="checkbox"'
                . ' id="cb_' . $line . '"' 
                . ' onchange="toggleRowSelection(this)">';
            $id_of = $row[0];
            $nom_of = $row[1];
            $profil = $row[2];
            $type = $row[3];
            $solde = $row[4];

            $compte_gvv = dropdown_field("compte_" . $line, "", $compte_selector, []);
            $result[] = [$checkbox, $id_of, $nom_of, $profil, $compte_gvv, $solde];
            $line++;
        }
        return $result;
    }

}
