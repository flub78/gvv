<?php

/**
 * Classe pour parser le fichier CSV du Grand Livre comptable
 */
class GrandLivreParser {
    private $data = [];

    /**
     * Parse le fichier CSV du Grand Livre
     * 
     * @param string $filePath Chemin vers le fichier CSV
     * @return array Structure de données parsée
     */
    public function parseGrandLivre($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("Le fichier {$filePath} n'existe pas.");
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new Exception("Impossible d'ouvrir le fichier {$filePath}.");
        }

        $this->data = [
            'header' => [],
            'comptes' => [],
            'totaux' => []
        ];

        $currentCompte = null;
        $lineNumber = 0;

        while (($line = fgets($handle)) !== false) {
            $lineNumber++;
            $line = trim($line);

            // Ignorer les lignes vides
            if (empty($line)) {
                continue;
            }

            $fields = $this->parseCsvLine($line);

            // Traitement de l'en-tête du document
            if ($lineNumber <= 2) {
                $this->parseHeader($fields, $lineNumber);
                continue;
            }

            // Si c'est une ligne de nom de compte principal
            if ($this->isNewAccount($fields)) {

                if ($currentCompte) {
                    $this->data['comptes'][] = $currentCompte;
                }
                $currentCompte = $this->initializeAccount($fields);
            }

            // Si c'est une ligne de détails du compte
            elseif ($this->isAccountDetails($fields)) {
                if ($currentCompte) {
                    $currentCompte = array_merge($currentCompte, $this->parseAccountDetails($fields));
                }
            }

            // Si c'est une ligne de mouvement
            elseif ($this->isMovementLine($fields)) {
                if ($currentCompte) {
                    $movement = $this->parseMovement($fields);
                    if ($movement) {
                        $currentCompte['mouvements'][] = $movement;
                    }
                }
            }

            // Si c'est une ligne de total
            elseif ($this->isTotalLine($fields)) {
                $total = $this->parseTotal($fields);
                if ($currentCompte && $total) {
                    $currentCompte['totaux'][] = $total;
                } elseif ($total && $this->isGlobalTotal($fields)) {
                    $this->data['totaux'][] = $total;
                }
            }
        }

        // Ajouter le dernier compte
        if ($currentCompte) {
            $this->data['comptes'][] = $currentCompte;
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
     * Parse l'en-tête du document
     */
    private function parseHeader($fields, $lineNumber) {
        if ($lineNumber == 1) {
            $this->data['header']['titre'] = $fields[0] ?? '';
        } elseif ($lineNumber == 2) {
            $this->data['header']['date_edition'] = $fields[0] ?? '';
        }
    }

    /**
     * Vérifie si c'est une nouvelle ligne de compte
     */
    private function isNewAccount($fields) {
        return isset($fields[0]) && strpos($fields[0], 'Nom de compte') === 0;
    }

    /**
     * Initialise un nouveau compte
     */
    private function initializeAccount($fields) {
        $nomCompte = str_replace('Nom de compte;', '', $fields[0]);

        return [
            'nom_compte' => $nomCompte,
            'compte_export' => '',
            'numero_compte_of' => '',
            'mouvements' => [],
            'totaux' => []
        ];
    }

    /**
     * Vérifie si c'est une ligne de détails du compte
     */
    private function isAccountDetails($fields) {
        return (isset($fields[0]) && ($fields[0] === 'Compte d\'export' || $fields[0] === 'Numéro de compte OF')) ||
            (isset($fields[1]) && is_numeric($fields[1]) && count(array_filter($fields)) <= 3);
    }

    /**
     * Parse les détails du compte
     */
    private function parseAccountDetails($fields) {
        $details = [];

        if (isset($fields[0])) {
            if ($fields[0] === 'Compte d\'export' && isset($fields[1])) {
                $details['compte_export'] = $fields[1];
            } elseif ($fields[0] === 'Numéro de compte OF' && isset($fields[1])) {
                $details['numero_compte_of'] = $fields[1];
            }
        }

        return $details;
    }

    /**
     * Vérifie si c'est une ligne de mouvement
     */
    private function isMovementLine($fields) {
        // Une ligne de mouvement a généralement une date au format dd/mm/yyyy
        foreach ($fields as $field) {
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $field)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Parse une ligne de mouvement
     */
    private function parseMovement($fields) {
        $movement = [
            'intitule' => '',
            'numero_flux' => '',
            'date' => '',
            'description' => '',
            'debit' => 0.0,
            'credit' => 0.0,
            'solde' => 0.0,
            'compte_contrepartie' => ''
        ];

        // Recherche de la date pour identifier la structure
        $dateIndex = -1;
        foreach ($fields as $index => $field) {
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $field)) {
                $dateIndex = $index;
                break;
            }
        }

        if ($dateIndex !== -1) {
            $movement['date'] = $fields[$dateIndex];

            // Les champs avant la date
            if ($dateIndex > 0) {
                $movement['intitule'] = $fields[0] ?? '';
            }
            if ($dateIndex > 1) {
                $movement['numero_flux'] = $fields[$dateIndex - 1] ?? '';
            }

            // Les champs après la date
            if (isset($fields[$dateIndex + 1])) {
                $movement['description'] = $fields[$dateIndex + 1];
            }

            // Montants (généralement les 3 derniers champs numériques)
            $numericFields = [];
            for ($i = $dateIndex + 2; $i < count($fields); $i++) {
                if (is_numeric(str_replace(',', '.', $fields[$i]))) {
                    $numericFields[] = floatval(str_replace(',', '.', $fields[$i]));
                }
            }

            if (count($numericFields) >= 3) {
                $movement['debit'] = $numericFields[0];
                $movement['credit'] = $numericFields[1];
                $movement['solde'] = $numericFields[2];
            }
        }

        return $movement['date'] ? $movement : null;
    }

    /**
     * Vérifie si c'est une ligne de total
     */
    private function isTotalLine($fields) {
        $firstField = $fields[0] ?? '';
        return strpos($firstField, 'Total') !== false ||
            strpos($firstField, 'Solde au') !== false ||
            strpos($firstField, 'Cumul') !== false;
    }

    /**
     * Vérifie si c'est un total global
     */
    private function isGlobalTotal($fields) {
        $firstField = $fields[0] ?? '';
        return strpos($firstField, 'Cumul des mouvements') !== false;
    }

    /**
     * Parse une ligne de total
     */
    private function parseTotal($fields) {
        $total = [
            'type' => $fields[0] ?? '',
            'debit' => 0.0,
            'credit' => 0.0,
            'solde' => 0.0
        ];

        // Recherche des montants numériques
        $numericFields = [];
        foreach ($fields as $field) {
            if (is_numeric(str_replace(',', '.', $field))) {
                $numericFields[] = floatval(str_replace(',', '.', $field));
            }
        }

        if (count($numericFields) >= 3) {
            $total['debit'] = $numericFields[count($numericFields) - 3];
            $total['credit'] = $numericFields[count($numericFields) - 2];
            $total['solde'] = $numericFields[count($numericFields) - 1];
        }

        return $total;
    }

    /**
     * Retourne les données parsées sous forme de JSON
     */
    public function toJson() {
        return json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Affiche un résumé des comptes
     */
    public function getSummary() {
        $summary = [
            'nombre_comptes' => count($this->data['comptes']),
            'total_mouvements' => 0,
            'comptes_resume' => []
        ];

        foreach ($this->data['comptes'] as $compte) {
            $nbMouvements = count($compte['mouvements']);
            $summary['total_mouvements'] += $nbMouvements;

            $summary['comptes_resume'][] = [
                'nom' => $compte['nom_compte'],
                'numero_of' => $compte['numero_compte_of'],
                'nb_mouvements' => $nbMouvements
            ];
        }

        return $summary;
    }
}
