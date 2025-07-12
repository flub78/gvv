<?php

/**
 * Classe pour parser le fichier CSV du Grand Livre comptable
 * 
 * Parsing 
 * 
 * 
 * 
 * @startuml
 * [*] --> Lecture_Date : Grand livre
 * Lecture_Date -> Lecture_Compte : Date édition
 * Lecture_Compte -> Lecture_Export : Nom de compte
 * Lecture_Export -> Lecture_compte_OF : Compte d'export
 * Lecture_compte_OF -> Lecture_contrepartie : Numéro de compte OF
 * Lecture_contrepartie -> Lecture_Compte_Contrepartie : Compte de contrepartie
 * @enduml
 * 
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
        ];

        $currentCompte = null;
        $currentMovement = null;

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
            elseif ($this->isMovementFirstLine($fields)) {
                if ($currentCompte) {
                    $currentMovement = $this->parseMovementFirstLine($fields);
                    // if ($currentMovement) {
                    //     $currentCompte['mouvements'][] = $currentMovement;
                    // }
                }
            }

            // Si c'est une ligne de mouvement
            elseif ($this->isMovementSecondLine($fields)) {
                if ($currentCompte) {
                    if ($currentMovement) {
                        $currentMovement = $this->parseMovementSecondLine($fields, $currentMovement);
                    }
                    if ($currentMovement) {
                        $currentCompte['mouvements'][] = $currentMovement;
                    }
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
        if (!isset($fields[0])) return false;
        if (!isset($fields[1])) return false;
        if (trim($fields[0] != "Nom de compte")) return false;
        if (trim($fields[1] == "")) return false;

        $field1 = trim($fields[1]);
        if ($field1 == "Numéro de compte OF") return false;

        return true;
    }

    /**
     * Initialise un nouveau compte
     */
    private function initializeAccount($fields) {
        $nomCompte = $fields[1];

        return [
            'nom_compte' => $nomCompte,
            'compte_export' => '',
            'numero_compte_of' => '',
            'mouvements' => [],
        ];
    }

    /**
     * Vérifie si c'est une ligne de détails du compte
     */
    private function isAccountDetails($fields) {
        return (isset($fields[0]) && ($fields[0] === 'Compte d\'export' || $fields[0] === 'Numéro de compte OF'));
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
    private function isMovementFirstLine($fields) {

        if (!isset($fields[4])) return false;
        if (trim($fields[0]) != "") return false;
        if (trim($fields[1]) != "") return false;

        if (trim($fields[4]) == "") return false;
        if (trim($fields[4]) == "Mouvements") return false;

        return true;
    }

    /**
     * Vérifie si c'est une ligne de mouvement
     */
    private function isMovementSecondLine($fields) {

        foreach ([0, 1, 2, 4] as $index) {
            if (!isset($fields[$index]) || trim($fields[$index]) === "") {
                return false;
            }
        }
        return true;
    }

    /**
     * Parse une ligne de mouvement
     */
    private function parseMovementFirstLine($fields) {
        $movement = [
            'intitule' => '',
            'numero_flux' => '',
            'date' => '',
            'description' => '',
            'debit' => 0.0,
            'credit' => 0.0
        ];

        $dateIndex = 6;
        if ($dateIndex !== -1) {
            $movement['date'] = $fields[$dateIndex];

            $movement['intitule'] = ($fields[7]) ? $fields[7] : $fields[3];

            if ($dateIndex > 1) {
                $movement['numero_flux'] = $fields[$dateIndex - 1] ?? '';
            }
            $movement['debit'] = str_replace(',', '.', $fields[8]);
            $movement['credit'] = str_replace(',', '.', $fields[9]);
        }

        return $movement['date'] ? $movement : null;
    }

    /**
     * Parse une ligne de mouvement
     */
    private function parseMovementSecondLine($fields, $movement) {

        $movement['nom_compte2'] = $fields[0];
        $movement['id_compte2'] = $fields[1];
        $movement['export_compte2'] = $fields[2];

        return $movement;
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

    public function OperationsTableToHTML($table) {
        $CI = &get_instance();
        $CI->load->library('gvvmetadata');
        $CI->load->model('comptes_model');
        $CI->load->model('associations_of_model');
        $CI->load->model('ecritures_model');
        $CI->load->model('sections_model');

        // values for the compte selector select
        $compte_selector = $CI->comptes_model->selector_with_null(["codec =" => "411"], TRUE);

        // $table = $this->parse($filePath);
        $line = 0;
        $result = [];
        $html = "";

        // Début d'une section pour un compte
        foreach ($table['comptes'] as $row) {


            // D'abort on va chercher les informations relatives au compte
            $mvt_count = count($row['mouvements']); // le nombre de mouvements
            if (!$row['mouvements']) continue; // s'il n'y a pas d'écritures on saute

            $id_of = $row['numero_compte_of'];      // numéro de compte OF
            $nom_of = $row['nom_compte'];           // nom OF

            // Quel est la section courante?
            $section = $CI->sections_model->section();
            $section_id = ($section) ? $section['id'] : 0;

            /**
             * Quand une section est active on ne veut pas afficher les comptes d'autres sections
             * Attention, le compte de banque OF peut-être associé à plusieurs comptes GVV avec différentes sections.
             * 
             * Si un compte est associé et que tous les comptes d'écriture sont associés OK
             */

            $associated_gvv = $CI->associations_of_model->get_gvv_account($id_of, $section_id);
            if ($section) {
                $associated_gvv_all = $CI->associations_of_model->get_gvv_account($id_of);
                if ($associated_gvv_all && ! $associated_gvv) {
                    // le compte est associé à une autre section on saute le compte
                    continue;
                }
            }

            // Si le compte est associé
            if ($associated_gvv) {
                // On affiche un lien vers le journal du compte
                $compte_gvv = $associated_gvv;
                $image = $CI->comptes_model->image($compte_gvv);
                $compte_gvv = anchor(controller_url("compta/journal_compte/" . $associated_gvv), $image);
                $compte = $CI->comptes_model->get_by_id('id', $associated_gvv);
                $is_411 = ($compte['codec'] == "411");
            } else {
                // On affiche un sélecteur
                $attrs = 'class="form-control big_select" onchange="updateRow(this, '
                    . $id_of . ',\'' . $nom_of  . '\')"';
                $compte_gvv = dropdown_field(
                    "compte_" . $line,
                    $associated_gvv,
                    $compte_selector,
                    $attrs
                );
                // On veut aussi afficher les comptes non assignés
                $is_411 = true;
            }

            if ($section && ! $is_411) continue;

            // Ici les comptes qu'on affiche

            $html .= '<div class="mt-3 mb-3 border border-primary rounded">';
            $html .= '<table class="table operations">';

            // Affichage de la première ligne du compte
            $lst = [$id_of, $nom_of, $compte_gvv, '', '', '', '', ''];
            $result[] = $lst;
            $html .= html_row($lst, ['class' => 'compte']);

            // Affichage du nombre d'opération et du bouton de masquage
            $lst = ["Nombre d'opérations", $mvt_count, '', '', '', '', '', ''];
            $result[] = $lst;
            $html .= html_row($lst, ['class' => 'number_op']);

            // Entête de la ligne des écritures
            $lst = ['', 'Date', 'Intitule', 'Description', 'Débit', 'Crédit', 'ID_compte2', 'Nom_compte2'];
            $result[] = $lst;
            $html .= html_row($lst, ['class' => 'row_title']);

            // Affiche les lignes d'écriture
            $n = 1;
            foreach ($row['mouvements'] as $mvt) {

                $associated_gvv_compte2 = $CI->associations_of_model->get_gvv_account($mvt['id_compte2'], $section_id);

                $compte2 = $CI->comptes_model->get_by_id('id', $associated_gvv_compte2);

                // Si le compte est associé
                if ($associated_gvv_compte2) {
                    // On affiche un lien vers le journal du compte
                    $compte2_gvv = $associated_gvv_compte2;
                    $image = $CI->comptes_model->image($compte2_gvv);
                    $compte2_gvv = anchor(controller_url("compta/journal_compte/" . $associated_gvv_compte2), $image);
                } else {
                    // On affiche un sélecteur
                    $attrs = 'class="form-control big_select" onchange="updateRow(this, '
                        . $id_of . ',\'' . $nom_of  . '\')"';
                    $compte2_gvv = dropdown_field(
                        "compte_" . $line,
                        $associated_gvv,
                        $compte_selector,
                        $attrs
                    );
                }

                $mvt_data = array(
                    'date' => date_ht2db($mvt['date']),
                    'intitule' => $mvt['intitule'],
                    'description' => $mvt['numero_flux'],
                    'debit' => $mvt['debit'],
                    'credit' => $mvt['credit'],
                    'compte1' => $associated_gvv,
                    'compte2' => $associated_gvv_compte2
                );
                $mvt_data_json = json_encode($mvt_data, JSON_UNESCAPED_UNICODE);

                // $hidden_input = '<input type="hidden" name="import_' . $line . '" value="' . $mvt_data_json . '">';
                $hidden_input = form_hidden('import_' . $line, $mvt_data_json);

                if ($associated_gvv_compte2 && $associated_gvv) {
                    $checkbox = '<input type="checkbox"'
                        . ' name="cb_' . $line . '"'
                        . ' onchange="toggleRowSelection(this)">' . $hidden_input;

                    $num_cheque = "OpenFlyers : " . $mvt['numero_flux'];
                    $club = $compte2['club'];
                    $where = ["club" => $club, 'num_cheque' =>  $num_cheque];

                    $ecriture = $CI->ecritures_model->get_first($where);

                    if ($ecriture) $checkbox .= nbs(2) . "synchronisé"; 
                    // var_dump($ecriture);

                } else {
                    $checkbox = "";
                }

                $id_compte2 = $mvt['id_compte2'] . ' ' . $compte2_gvv;
                $lst = [$checkbox, $mvt['date'], $mvt['intitule'], $mvt['numero_flux'], euro($mvt['debit']), euro($mvt['credit']), $id_compte2, $mvt['nom_compte2']];
                $result[] = $lst;

                // génère la ligne de tableau HTML
                $class = 'mouvement';
                if ($n % 2 == 0) {
                    $class .= " even";
                } else {
                    $class .= " odd";
                }
                $html .= html_row($lst, ['class' => $class]);
                $n++;
                $line++;
            }
            $html .= "</table>";
            $html .= "</div>";
        }
        return $html;
        return $result;
    }
}
