<?php

/**
 * SmartAdjustor - Classe pour gérer les ajustements intelligents des rapprochements
 * 
 * Cette classe duplique les fonctions de corrélation de ReleveOperation
 * pour les utiliser dans le nouveau système de rapprochement objet.
 */
class SmartAdjustor {
    private $CI;

    public function __construct() {
        $this->CI = &get_instance();
    }

    /**
     * Fonction principale d'ajustement intelligent
     *
     * @param array $sel Sélection d'écritures (key => ecriture)
     * @param StatementOperation $statement_operation L'opération de relevé
     * @return array écritures filtrées selon leur corrélation
     */
    public function smart_adjust($sel, $statement_operation) {

        $threshold = 0.5;
        $filtered_sel = [];
        $verbose = false;
        $operation_type = $statement_operation->type();

        // Première passe pour voir si on a une corrélation très forte
        foreach ($sel as $key => $ecriture) {
            // Calcule le coefficient de corrélation entre l'écriture et l'opération
            $correlation = $this->correlation($statement_operation, $key, $ecriture, $operation_type);
            $statement_operation->set_correlation($key, $correlation, $ecriture);
            if ($correlation >= 0.9) {
                $threshold = 0.9;
                break;
            }
        }

        // Deuxième passe pour filtrer les écritures
        foreach ($sel as $key => $ecriture) {
            // Calcule le coefficient de corrélation entre l'écriture et l'opération
            $correlation = $this->correlation($statement_operation, $key, $ecriture, $operation_type);

            if ($correlation >= $threshold) {
                // Si le coefficient de corrélation est supérieur au seuil, on garde l'écriture
                $filtered_sel[$key] = $ecriture;
                $ignored = "";
            } else {
                // Sinon, on l'ignore
                $ignored = "Ignored";
            }
            $msg = "Correlation: $key => $ecriture : $correlation $ignored<br>";
            gvv_debug($msg);
        }

        if ($verbose) {
            echo '</pre>';
            echo '<hr style="border: 1px solid #ccc; margin: 20px 0;">';
        }
        return $filtered_sel;
    }


    /**
     * Calcule le coefficient de corrélation entre l'écriture comptable et l'opération
     *
     * @param StatementOperation $statement_operation L'opération de relevé
     * @param string $key Clé de l'écriture comptable GVV
     * @param mixed $ecriture Image de l'écriture
     * @param string $operation_type Type d'opération
     * @return float coefficient de corrélation (0.0 à 1.0)
     */
    public function correlation($statement_operation, $key, $ecriture, $operation_type) {
        // Corrélation de base
        $correlation = 0.5;

        // Appeler la fonction de corrélation appropriée selon le type
        switch ($operation_type) {
            case 'cheque_debite':
                $correlation = $this->correlateCheque($statement_operation, $key, $ecriture);
                break;

            case 'frais_bancaire':
                $correlation = $this->correlateFraisBancaire($statement_operation, $key, $ecriture);
                break;

            case 'paiement_cb':
                $correlation = $this->correlatePaiementCB($statement_operation, $key, $ecriture);
                break;

            case 'prelevement':
            case 'prelevement_pret':
                $correlation = $this->correlatePrelevement($statement_operation, $key, $ecriture);
                break;

            case 'virement_emis':
                $correlation = $this->correlateVirementEmis($statement_operation, $key, $ecriture);
                break;

            case 'virement_recu':
                $correlation = $this->correlateVirementRecu($statement_operation, $key, $ecriture);
                break;

            case 'encaissement_cb':
                $correlation = $this->correlateEncaissementCB($statement_operation, $key, $ecriture);
                break;

            case 'remise_cheque':
                $correlation = $this->correlateRemiseCheque($statement_operation, $key, $ecriture);
                break;

            case 'remise_especes':
                $correlation = $this->correlateRemiseEspeces($statement_operation, $key, $ecriture);
                break;

            case 'regularisation_frais':
                $correlation = $this->correlateRegularisationFrais($statement_operation, $key, $ecriture);
                break;

            case 'inconnu':
                $correlation = $this->correlateInconnu($statement_operation, $key, $ecriture);
                break;

            default:
                $correlation = 0.5;
                break;
        }

        return $correlation;
    }

    /**
     * Corrélation pour les opérations de chèque
     *
     * @param StatementOperation $statement_operation L'opération de relevé
     * @param string $key Clé de l'écriture comptable GVV
     * @param mixed $ecriture Image de l'écriture
     * @return float coefficient de corrélation
     */
    private function correlateCheque($statement_operation, $key, $ecriture) {
        $libelle = $statement_operation->interbank_label();

        // Rechercher le numéro de chèque dans le libellé interbancaire
        if (preg_match('/cheque.*?(\d+)/i', $libelle, $matches)) {
            return 0.9; // Plus élevé si numéro de chèque trouvé
        }

        // Vérifier si la description de l'écriture contient des termes liés au chèque
        if (stripos($ecriture, 'cheque') !== false || stripos($ecriture, 'chèque') !== false) {
            return 0.8;
        }

        return 0.7;
    }

    /**
     * Corrélation pour les frais bancaires
     *
     * @param StatementOperation $statement_operation L'opération de relevé
     * @param string $key Clé de l'écriture comptable GVV
     * @param mixed $ecriture Image de l'écriture
     * @return float coefficient de corrélation
     */
    private function correlateFraisBancaire($statement_operation, $key, $ecriture) {
        $nature = $statement_operation->nature();
        $libelle = $statement_operation->interbank_label();

        // Corrélation élevée si "frais" trouvé dans la nature ou le libellé
        if (stripos($nature, 'frais') !== false || stripos($libelle, 'frais') !== false) {
            return 0.8;
        }

        // Vérifier si la description de l'écriture contient des termes liés aux frais
        if (stripos($ecriture, 'frais') !== false || stripos($ecriture, 'commission') !== false) {
            return 0.7;
        }

        return 0.6;
    }

    /**
     * Corrélation pour les paiements par carte bancaire
     *
     * @param StatementOperation $statement_operation L'opération de relevé
     * @param string $key Clé de l'écriture comptable GVV
     * @param mixed $ecriture Image de l'écriture
     * @return float coefficient de corrélation
     */
    private function correlatePaiementCB($statement_operation, $key, $ecriture) {
        $nature = $statement_operation->nature();

        // Corrélation élevée si "carte" trouvé dans la nature
        if (stripos($nature, 'carte') !== false) {
            return 0.8;
        }

        // Vérifier si la description de l'écriture contient des termes liés à la carte
        if (stripos($ecriture, 'carte') !== false || stripos($ecriture, 'cb') !== false) {
            return 0.7;
        }

        return 0.5;
    }

    /**
     * Corrélation pour les prélèvements
     *
     * @param StatementOperation $statement_operation L'opération de relevé
     * @param string $key Clé de l'écriture comptable GVV
     * @param mixed $ecriture Image de l'écriture
     * @return float coefficient de corrélation
     */
    private function correlatePrelevement($statement_operation, $key, $ecriture) {
        $nature = $statement_operation->nature();

        // Corrélation élevée si "prelevement" trouvé dans la nature
        if (stripos($nature, 'prelevement') !== false || stripos($nature, 'prélèvement') !== false) {
            return 0.8;
        }

        // Vérifier si la description de l'écriture contient des termes liés au prélèvement
        if (stripos($ecriture, 'prelevement') !== false || stripos($ecriture, 'prélèvement') !== false) {
            return 0.7;
        }

        return 0.6;
    }

    /**
     * Corrélation pour les virements émis
     *
     * @param StatementOperation $statement_operation L'opération de relevé
     * @param string $key Clé de l'écriture comptable GVV
     * @param mixed $ecriture Image de l'écriture
     * @return float coefficient de corrélation
     */
    private function correlateVirementEmis($statement_operation, $key, $ecriture) {
        $nature = $statement_operation->nature();

        // Corrélation élevée si "virement" trouvé dans la nature
        if (stripos($nature, 'virement') !== false) {
            return 0.8;
        }

        // Vérifier si la description de l'écriture contient des termes liés au virement
        if (stripos($ecriture, 'virement') !== false || stripos($ecriture, 'transfer') !== false) {
            return 0.7;
        }

        return 0.6;
    }

    private function cleanup_string($str) {
        // Convertir en minuscules
        $str = strtolower($str);

        // Nettoyer les préfixes communs
        $str = str_replace([
            'de: m ou mme',
            'de: mr ou mme',
            'de: mr',
            'de: monsieur',
            'de: m ',
            'de: mme',
            'ou m',
            'de:',
            'epoux',
            'date:',
            'motif:',
            'et',
            'vir inst re',
            'vir recu',
            'virement',
            '&nbsp;',
            'compte',
            'eur'
        ], '', $str);

        $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);


        $str = preg_replace('/\b\d{1,2}\/\d{1,2}\/\d{4}\b/', '', $str);
        $str = preg_replace('/\b\d{1,2}:\d{2}\b/', '', $str);
        // Supprimer la ponctuation
        $str = preg_replace('/[^\w\s]/', ' ', $str);
        // Supprimer les espaces multiples
        $str = preg_replace('/\s+/', ' ', $str);
        // Trim les espaces en début et fin
        $str = trim($str);
        return $str;
    }

    /**
     * Corrélation pour les virements reçus
     *
     * @param StatementOperation $statement_operation L'opération de relevé
     * @param string $key Clé de l'écriture comptable GVV
     * @param mixed $ecriture Image de l'écriture
     * @return float coefficient de corrélation
     */
    private function correlateVirementRecu($statement_operation, $key, $ecriture) {
        $nature = $statement_operation->nature();
        $comments = $statement_operation->comments();

        // Nettoyer la nature de l'opération
        $nature = $this->cleanup_string($nature);
        $ecriture = $this->cleanup_string($ecriture);

        // Analyser les informations de l'expéditeur depuis les commentaires
        $comment = "";
        foreach ($comments as $c) {
            $comment .= $c . " ";
        }
        $cmt = $this->cleanup_string($comment);

        // Rechercher la nature de l'opération dans la description de l'écriture
        if (!empty($nature) && stripos(strtolower($ecriture), $nature) !== false) {
            return 0.96; // Corrélation élevée si référence trouvée
        }

        $cmt_list = explode(' ', $cmt);

        $score = 0;
        $word_count = 0;
        $matches = [];
        foreach ($cmt_list as $word) {
            $word = trim($word);
            if (strlen($word) > 1) { // Ne considérer que les mots de plus de 1 caractère
                $word_count++;
                if (stripos($ecriture, $word) !== false) {
                    $score++;
                    $matches[] = $word;
                }
            }
        }

        if ($score == 1) {
            // Un seul mot correspondant donne une corrélation de 0.51
            return 0.51;
        } elseif ($score > 1) {
            $res = 0.51 + ($score - 1) / $word_count;
            return $res;
        }

        // Corrélation de base pour les opérations de virement
        // if (stripos($nature, 'virement') !== false || stripos($nature, 'vir') !== false) {
        //     return 0.7;
        // }

        return 0.1;
    }

    /**
     * Corrélation pour les encaissements par carte bancaire
     *
     * @param StatementOperation $statement_operation L'opération de relevé
     * @param string $key Clé de l'écriture comptable GVV
     * @param mixed $ecriture Image de l'écriture
     * @return float coefficient de corrélation
     */
    private function correlateEncaissementCB($statement_operation, $key, $ecriture) {
        $nature = $statement_operation->nature();

        // Vérifier les termes liés à la carte dans la nature de l'opération
        if (stripos($nature, 'carte') !== false || stripos($nature, 'cb') !== false) {
            return 0.8;
        }

        // Vérifier si la description de l'écriture contient des termes d'encaissement par carte
        if (
            stripos($ecriture, 'encaissement') !== false &&
            (stripos($ecriture, 'carte') !== false || stripos($ecriture, 'cb') !== false)
        ) {
            return 0.8;
        }

        return 0.7;
    }

    /**
     * Corrélation pour les remises de chèques
     *
     * @param StatementOperation $statement_operation L'opération de relevé
     * @param string $key Clé de l'écriture comptable GVV
     * @param mixed $ecriture Image de l'écriture
     * @return float coefficient de corrélation
     */
    private function correlateRemiseCheque($statement_operation, $key, $ecriture) {
        $nature = $statement_operation->nature();
        $libelle = $statement_operation->interbank_label();

        // Corrélation élevée si remise ou cheque trouvé
        if (
            stripos($nature, 'remise') !== false || stripos($libelle, 'remise') !== false ||
            stripos($nature, 'cheque') !== false || stripos($libelle, 'cheque') !== false
        ) {
            return 0.9;
        }

        // Vérifier la description de l'écriture pour les termes de remise de chèque
        if (
            stripos($ecriture, 'remise') !== false &&
            (stripos($ecriture, 'cheque') !== false || stripos($ecriture, 'chèque') !== false)
        ) {
            return 0.8;
        }

        return 0.8;
    }

    /**
     * Corrélation pour les remises d'espèces
     *
     * @param StatementOperation $statement_operation L'opération de relevé
     * @param string $key Clé de l'écriture comptable GVV
     * @param mixed $ecriture Image de l'écriture
     * @return float coefficient de corrélation
     */
    private function correlateRemiseEspeces($statement_operation, $key, $ecriture) {
        $nature = $statement_operation->nature();
        $libelle = $statement_operation->interbank_label();

        // Corrélation élevée si des termes de dépôt d'espèces sont trouvés
        if (
            stripos($nature, 'espèces') !== false || stripos($libelle, 'espèces') !== false ||
            stripos($nature, 'remise') !== false || stripos($libelle, 'remise') !== false
        ) {
            return 0.9;
        }

        // Vérifier la description de l'écriture pour les termes de dépôt d'espèces
        if (stripos($ecriture, 'espèces') !== false || stripos($ecriture, 'liquide') !== false) {
            return 0.8;
        }

        return 0.9;
    }

    /**
     * Corrélation pour les régularisations de frais
     *
     * @param StatementOperation $statement_operation L'opération de relevé
     * @param string $key Clé de l'écriture comptable GVV
     * @param mixed $ecriture Image de l'écriture
     * @return float coefficient de corrélation
     */
    private function correlateRegularisationFrais($statement_operation, $key, $ecriture) {
        $nature = $statement_operation->nature();
        $libelle = $statement_operation->interbank_label();

        // Vérifier les termes de régularisation ou frais
        if (
            stripos($nature, 'regularisation') !== false || stripos($libelle, 'regularisation') !== false ||
            stripos($nature, 'régularisation') !== false || stripos($libelle, 'régularisation') !== false
        ) {
            return 0.8;
        }

        // Vérifier la description de l'écriture pour les termes de régularisation
        if (stripos($ecriture, 'regularisation') !== false || stripos($ecriture, 'régularisation') !== false) {
            return 0.7;
        }

        return 0.5;
    }

    /**
     * Corrélation pour les opérations inconnues
     *
     * @param StatementOperation $statement_operation L'opération de relevé
     * @param string $key Clé de l'écriture comptable GVV
     * @param mixed $ecriture Image de l'écriture
     * @return float coefficient de corrélation
     */
    private function correlateInconnu($statement_operation, $key, $ecriture) {
        // Pour les opérations inconnues, nous avons une faible confiance
        // mais nous pouvons encore essayer de faire correspondre basé sur le montant et la proximité de date
        return 0.3;
    }

    /**
     * Retourne capital et intérêts pour les prêts
     *
     * @param StatementOperation $statement_operation L'opération de relevé
     * @return array Tableau avec 'capital' et 'interets' ou tableau vide
     */
    public function remboursement($statement_operation) {
        // Vérifier si l'opération est un remboursement
        if ($statement_operation->type() === 'prelevement_pret') {

            // Extraire les montants de capital amorti et intérêts des commentaires
            $capital = 0.0;
            $interets = 0.0;
            $comments = $statement_operation->comments();

            // Vérifier si nous avons des commentaires et que le premier correspond à 'CAPITAL AMORTI'
            if (!empty($comments[0]) && strpos($comments[0], 'CAPITAL AMORTI') !== false) {
                // Extraire la valeur numérique après ': '
                $parts = explode(': ', $comments[0]);
                if (count($parts) == 2) {
                    $capital = str_replace(',', '.', trim($parts[1]));
                }

                // Vérifier le montant des intérêts dans les commentaires
                if (!empty($comments[1]) && strpos($comments[1], 'INTERETS') !== false) {
                    $parts = explode(': ', $comments[1]);
                    if (count($parts) == 2) {
                        $interets = str_replace(',', '.', trim($parts[1]));
                    }

                    // Retourner un tableau avec les montants de capital et intérêts
                    return [
                        'capital' => $capital,
                        'interets' => $interets
                    ];
                }
            }
        }
        return [];
    }
}
