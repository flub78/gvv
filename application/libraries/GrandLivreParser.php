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
            'comptes' => []
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
                $this->parseHeader($fields, $lineNumber, $filePath);
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

            // Si c'est une première ligne de mouvement
            elseif ($this->isMovementFirstLine($fields)) {
                if ($currentCompte) {
                    $currentMovement = $this->parseMovementFirstLine($fields);
                }
            }

            // Si c'est une autre ligne de mouvement
            elseif ($this->isMovementSecondLine($fields)) {
                if ($currentCompte) {
                    if ($currentMovement) {
                        $currentMovement = $this->parseMovementSecondLine($fields, $currentMovement);
                    }
                    if ($currentMovement) {
                        $currentCompte['mouvements'][] = $currentMovement;
                        $currentCompte['flux_of'][] = $currentMovement['numero_flux'] ?? '';
                        $flux_of[] = $currentMovement['numero_flux'] ?? '';
                    }
                }
            }
        }

        // Ajouter le dernier compte
        if ($currentCompte) {
            $this->data['comptes'][] = $currentCompte;
        }
        $this->data['flux_of'] = array_unique($flux_of);

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
    private function parseHeader($fields, $lineNumber, $filename = '') {
        $basename = basename($filename);
        $error = "Format de fichier invalide. Le fichier \"$basename\" n'est pas un export du grand journal OpenFlyers.";
        if ($lineNumber == 1) {
            $this->data['header']['titre'] = $fields[0] ?? '';
            // Check that $fields[0] match Grand livre du 01/01/2025  au  31/01/2025
            if (!preg_match('/^Grand livre du \d{2}\/\d{2}\/\d{4}\s+au\s+\d{2}\/\d{2}\/\d{4}$/', $fields[0])) {

                throw new Exception($error);
            }
        } elseif ($lineNumber == 2) {
            // Check that $fields[0] matches Edité le 13/07/2025
            if (!preg_match('/^Edité le \d{2}\/\d{2}\/\d{4}$/', $fields[0])) {
                throw new Exception($error);
            }

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
            'flux_of' => []
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

    /**
     * Génère le tableau des opérations à partir des données du Grand Livre
     * 
     * @param array $table Données du Grand Livre
     * @return string hash tableau des opérations
     */
    public function OperationsTable($table) {
        // gvv_dump($table, "GrandLivreParser::OperationsTable");
        $CI = &get_instance();
        $CI->load->library('gvvmetadata');
        $CI->load->model('comptes_model');
        $CI->load->model('associations_of_model');
        $CI->load->model('ecritures_model');
        $CI->load->model('sections_model');

        $filter_active = $CI->session->userdata('filter_active');
        $startDate = $CI->session->userdata('startDate');
        $endDate = $CI->session->userdata('endDate');
        $filter_type = $CI->session->userdata('filter_type');
        $current_client = $CI->session->userdata('current_client');

        // values for the compte selector select
        $compte_selector = $CI->comptes_model->selector_with_null(["codec =" => "411"], TRUE);

        // $table = $this->parse($filePath);
        $line = 0;
        $result = [];
        $flux_of = [];

        // Début d'une section pour un compte
        foreach ($table['comptes'] as $row) {
            $id_of = $row['numero_compte_of'];
            // gvv_dump($row, "GrandLivreParser::OperationsTable");
            // D'abort on va chercher les informations relatives au compte
            $mvt_count = count($row['mouvements']); // le nombre de mouvements
            if (!$row['mouvements']) continue; // s'il n'y a pas d'écritures on saute

            $id_of = $row['numero_compte_of'];      // numéro de compte OF
            $nom_of = $row['nom_compte'];           // nom OF
            $result[$id_of] = [];

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
                // JSON encode the flux_of array for the compte
                $flux_of_json = json_encode($row['flux_of'], JSON_UNESCAPED_UNICODE);
                $compte_gvv = anchor_compte(
                    $associated_gvv,
                    [],
                    ['of_synchronized' => $flux_of_json]
                );
                // gvv_dump($row['flux_of']);


                $compte_gvv .= form_hidden('flux_of_' . $id_of, $flux_of_json);
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

            // On ne garde que les comptes clients
            if ($section && ! $is_411) continue;

            if ($filter_active) {
                if ($current_client && $current_client != $associated_gvv && $current_client != 'all') {
                    // Si on a un client actif et qu'il ne correspond pas au compte, on saute
                    continue;
                }
            }

            // Ici les comptes qu'on affiche

            // Affichage de la première ligne du compte
            $lst = [$id_of, $nom_of, $compte_gvv, '', '', '', '', ''];
            $result[$id_of]['header'] = $lst;

            // Affichage du nombre d'opération et du bouton de masquage
            $lst = ["Nombre d'opérations", $mvt_count, '', '', '', '', '', ''];
            $result[$id_of]['numbers'] = $lst;

            // Entête de la ligne des écritures
            $lst = ['Status', 'Date', 'Intitulé', 'Description', 'Débit', 'Crédit', 'Compte OF', 'Compte GVV'];
            $result[$id_of]['title'] = $lst;

            // Affiche les lignes d'écriture
            $nb_lignes = 0;
            $result[$id_of]['lines'] = [];
            foreach ($row['mouvements'] as $mvt) {

                $associated_gvv_compte2 = $CI->associations_of_model->get_gvv_account($mvt['id_compte2'], $section_id);

                $compte2 = $CI->comptes_model->get_by_id('id', $associated_gvv_compte2);

                if ($CI->associations_of_model->associated_to_null($mvt['id_compte2'])) {
                    continue; // on saute les comptes orphelins
                }

                // Si le compte est associé
                if ($associated_gvv_compte2) {
                    // On affiche un lien vers le journal du compte
                    $compte2_gvv = anchor_compte($associated_gvv_compte2);
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
                $encoded = base64_encode($mvt_data_json);
                // $hidden_input = '<input type="hidden" name="import_' . $line . '" value="' . $mvt_data_json . '">';
                $hidden_input = form_hidden('import_' . $line, $encoded);

                if ($associated_gvv_compte2 && $associated_gvv) {
                    $checkbox = '<input type="checkbox"'
                        . ' name="cb_' . $line . '"'
                        . ' >' . $hidden_input;

                    $num_cheque = "OpenFlyers : " . $mvt['numero_flux'];
                    $club = $compte2['club'];
                    $where = ["club" => $club, 'num_cheque' =>  $num_cheque];

                    $ecriture = $CI->ecritures_model->get_first($where);

                    if ($ecriture) $checkbox .= nbs(2) . '<span class="badge text-white bg-success p-1 rounded-pill">synchronisé</span>';
                    // var_dump($ecriture);

                } else {
                    $checkbox = "";
                }

                $id_compte2 = $mvt['id_compte2'] . ' : ' . $mvt['nom_compte2'];
                $lst = [$checkbox, $mvt['date'], $mvt['intitule'], $mvt['numero_flux'], euro($mvt['debit']), euro($mvt['credit']), $id_compte2, $compte2_gvv];

                if ($filter_active) {
                    if ($filter_type && ($filter_type != 'display_all')) {
                        // Si le filtre est actif, on ne veut pas afficher les écritures qui ne correspondent pas au filtre
                        if ($filter_type == 'filter_matched' && !$ecriture) {
                            // Si le filtre est actif et que l'écriture est synchronisée, on ne veut pas afficher
                            continue;
                        } elseif ($filter_type == 'filter_unmatched' && $ecriture) {
                            // Si le filtre est actif et que l'écriture n'est pas synchronisée, on ne veut pas afficher
                            continue;
                        }
                    }

                    $date = date_ht2db($mvt['date']);

                    if ($startDate) {
                        if ($date < $startDate) {
                            // Si la date n'est pas dans l'intervalle, on ne veut pas afficher
                            continue;
                        }
                    }
                    if ($endDate) {
                        if ($date > $endDate) {
                            // Si la date n'est pas dans l'intervalle, on ne veut pas afficher
                            continue;
                        }
                    }
                }

                $result[$id_of]['lines'][] = $lst;

                $nb_lignes++;
                $line++;
            }

            // quand on filtre on affiche pas de comptes qui ont 0 opérations
            if ($filter_active && ($nb_lignes == 0)) {
                unset($result[$id_of]);
                continue;
            }   
            $result[$id_of]['numbers'][1] = $nb_lignes . " / " . $result[$id_of]['numbers'][1];

        }
        return $result;
    }

    /**
     * Convertit une table en HTML
     */
    public function toHTML($table) {

        $html = "";

        // Début d'une section pour un compte
        foreach ($table as $compte) {

            if (!isset($compte['header'])) continue;

            $html .= '<div class="mt-3 mb-3 border border-primary rounded">';
            $html .= '<table class="table operations">';

            // Affichage de la première ligne du compte
            $html .= html_row($compte['header'], ['class' => 'compte row_title']);

            // Affichage du nombre d'opération et du bouton de masquage
            $html .= html_row($compte['numbers'], ['class' => 'number_op']);

            // Entête de la ligne des écritures
            $html .= html_row($compte['title'], ['class' => 'row_title']);

            // Affiche les lignes d'écriture
            foreach ($compte['lines'] as $line) {

                // génère la ligne de tableau HTML
                $class = 'mouvement';
                if ($n % 2 == 0) {
                    $class .= " even";
                } else {
                    $class .= " odd";
                }
                $html .= html_row($line, ['class' => $class]);
            }
            $html .= "</table>";
            $html .= "</div>";
        }
        return $html;
    }
}
