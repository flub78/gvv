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
            if ($correlation >= 0.91) {
                $threshold = 0.91;
                break;
            }
        }

        // Deuxième passe pour filtrer les écritures
        foreach ($sel as $key => $ecriture) {
            // Calcule le coefficient de corrélation entre l'écriture et l'opération
            $correlation = $this->correlation($statement_operation, $key, $ecriture, $operation_type);
            $statement_operation->set_correlation($key, $correlation, $ecriture);

            if ($correlation >= $threshold) {
                // Si le coefficient de corrélation est supérieur au seuil, on garde l'écriture
                $filtered_sel[$key] = $ecriture;
                $ignored = "";
            } else {
                // Sinon, on l'ignore
                $ignored = "Ignored";
            }
            $ecriture_display = is_array($ecriture) ? $ecriture['image'] : $ecriture;
            $msg = "Correlation: $key => $ecriture_display : $correlation $ignored<br>";
            gvv_debug($msg);
        }

        if ($verbose) {
            echo '</pre>';
            echo '<hr style="border: 1px solid #ccc; margin: 20px 0;">';
        }
        return $filtered_sel;
    }

    /**
     * Vérifie si l'écriture matche des mots clé pour des types différents. C'est un indice de faible corrélation. Par exemple si on trouve chèque dans une écriture ce n'est surement pas un virement, etc.
     */
    public function match_other_types($ecriture_image, $operation_type) {

        $all_keywords = ['cheque', 'frais', 'commission', 'carte', 'cb', 'prelevement', 'vir', 'virement', 'transfer', 'encaissement', 'remise', 'espèces', 'liquide', 'regularisation'];

        $keywords_by_type = [
            'cheque_debite' => ['cheque'],
            'frais_bancaire' => ['frais', 'commission'],
            'paiement_cb' => ['carte', 'cb'],
            'prelevement' => ['prelevement'],
            'prelevement_pret' => ['prelevement'],
            'virement_emis' => ['virement', 'vir', 'transfer'],
            'virement_recu' => ['virement', 'vir', 'transfer'],
            'encaissement_cb' => ['encaissement', 'carte', 'cb'],
            'remise_cheque' => ['remise', 'cheque'],
            'remise_especes' => ['remise', 'especes', 'liquide'],
            'regularisation_frais' => ['regularisation']
        ];

        // si on trouve dans l'écriture des mots clés définis dans $all_keywords mais pas dans ceux du type courant, on retourne true, sinon false
        $type_keywords = isset($keywords_by_type[$operation_type]) ? $keywords_by_type[$operation_type] : [];
        foreach ($all_keywords as $keyword) {
            if (in_array($keyword, $type_keywords)) {
                continue;
            }
            if (strpos($ecriture_image, $keyword) !== false) {
                return true;
            }
        }

        return false;
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

        $nature = $statement_operation->nature();
        // Nettoyer la nature de l'opération
        $nature = $this->cleanup_string($nature);
        $ecriture_image = $this->cleanup_string($ecriture);

        if ($this->match_other_types($ecriture_image, $operation_type)) {
            return 0.1; // Faible corrélation si des mots clés d'autres types sont trouvés dans l'image
        }

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
     * Corrélation pour les opérations de chèque débité
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
        $ecriture_image = $this->getEcritureImage($ecriture);
        if (stripos($ecriture_image, 'cheque') !== false || stripos($ecriture_image, 'chèque') !== false) {
            return 0.8;
        }

        // élimine les virements
        if (stripos($ecriture_image, 'vir') !== false) {
            return 0.6;
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
        $ecriture_image = $this->getEcritureImage($ecriture);
        if (stripos($ecriture_image, 'frais') !== false || stripos($ecriture_image, 'commission') !== false) {
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
        $ecriture_image = $this->getEcritureImage($ecriture);
        if (stripos($ecriture_image, 'carte') !== false || stripos($ecriture_image, 'cb') !== false) {
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
        $ecriture_image = $this->getEcritureImage($ecriture);
        if (stripos($ecriture_image, 'prelevement') !== false || stripos($ecriture_image, 'prélèvement') !== false) {
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
        $ecriture_image = $this->getEcritureImage($ecriture);
        $image = $this->cleanup_string($ecriture_image);
        if (stripos($image, 'virement') !== false || stripos($image, 'transfer') !== false) {
            return 0.7;
        }

        return 0.6;
    }

    /**
     * Extrait de manière sécurisée le texte de l'écriture
     *
     * @param mixed $ecriture L'écriture (doit être un array avec clé 'image')
     * @return string Le texte de l'écriture ou chaîne vide si non trouvé
     */
    private function getEcritureImage($ecriture) {
        if (is_array($ecriture) && isset($ecriture['image']) && is_string($ecriture['image'])) {
            return $ecriture['image'];
        }
        return '';
    }

    /**
     * Extraire des tokens assimilables à des noms/prénoms depuis une chaîne.
     * Filtre les mots très courts et un petit jeu de mots vides.
     */
    private function extract_name_tokens($str) {
        $clean = $this->cleanup_string($str);
        if ($clean === '') return [];
        $tokens = preg_split('/\s+/', $clean);
        $stop = [
            'vol','voile','virement','vir','transfer','releve','rc','ref','cumulus','bnp','bpop','vi','bia','rem','net','autres','emis','recu','recus','reçus',
            'le','la','les','des','du','de','d','a','au','aux','et','ou','sur','compte','cdn','gvv','assurance','contrat','autre','releve','relevé','euro','euros'
        ];
        $res = [];
        foreach ($tokens as $t) {
            if (strlen($t) <= 2) continue;
            if (in_array($t, $stop, true)) continue;
            $res[] = $t;
        }
        return array_values(array_unique($res));
    }

    /**
     * Nettoie une chaîne de caractères pour la corrélation
     * 
     * Supprime les éléments non pertinents, passe tout en minuscules, enlève la ponctuation et les espaces multiples.
     * @param string $str La chaîne à nettoyer
     * @return string La chaîne nettoyée
     */
    private function cleanup_string($str) {
        // Safety check: ensure we have a string
        if (!is_string($str)) {
            return '';
        }

        // Convertir en minuscules
        $str = strtolower($str);

        // Nettoyer les préfixes communs
        // On ne supprime que les informations qui ne servent à rien pour la corrélation
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

        // Nettoyer
        $nature = $this->cleanup_string($nature);
        $ecriture_image = $this->cleanup_string($ecriture);

        if ($this->match_other_types($ecriture_image, 'virement_recu')) {
            return 0.1;
        }

        // Concat commentaires
        $comment = "";
        foreach ($comments as $c) { $comment .= $c . " "; }
        $cmt = $this->cleanup_string($comment);

        // Références explicites (inchangé)
        if (preg_match('/(\d{5,})\w?/', $nature, $matches)) {
            $id = $matches[1];
            if (!empty($id) && stripos($ecriture_image, $id) !== false) {
                return 0.98;
            }
        }
        if (!empty($nature) && stripos($ecriture_image, $nature) !== false) {
            return 0.96;
        }

        // Amélioration: recouper nom/prénom expéditeur vs image
        $sender_tokens   = $this->extract_name_tokens($cmt);
        $ecriture_tokens = $this->extract_name_tokens($ecriture_image);
        $overlap = count(array_intersect($sender_tokens, $ecriture_tokens));
        $foreign_tokens = array_diff($ecriture_tokens, $sender_tokens);

        if ($overlap >= 2) {
            // Nom + prénom retrouvés
            return 0.97;
        }
        if ($overlap === 1) {
            // Un seul token (nom OU prénom)
            return 0.88;
        }
        if ($overlap === 0 && count($sender_tokens) > 0 && count($foreign_tokens) >= 2) {
            // L'image mentionne d'autres personnes -> faible
            return 0.1;
        }

        // Matching générique des commentaires (logique existante)
        $cmt_list = explode(' ', $cmt);
        $score = 0; $word_count = 0;
        foreach ($cmt_list as $word) {
            $word = trim($word);
            if (strlen($word) > 1) {
                $word_count++;
                if (stripos($ecriture_image, $word) !== false) { $score++; }
            }
        }
        if ($score == 1) { return 0.51; }
        if ($score > 1 && $word_count > 0) {
            $res = 0.51 + 0.49 * (($score - 1) / $word_count);
            if (count($sender_tokens) > 0 && count($foreign_tokens) >= 2) { $res = min($res, 0.15); }
            return $res;
        }

        // Corrélation de base pour les virements
        if (stripos($nature, 'virement') !== false || stripos($nature, 'vir') !== false) {
            if (count($sender_tokens) > 0 && count($foreign_tokens) >= 2) { return 0.1; }
            return 0.7;
        }

        return 0.1;
    }

    /**
     * Corrélation pour les encaissements par carte bancaire (CB)
     */
    private function correlateEncaissementCB($statement_operation, $key, $ecriture) {
        $nature = $this->cleanup_string($statement_operation->nature());
        $ecriture_image = $this->cleanup_string($ecriture);

        if ($this->match_other_types($ecriture_image, 'encaissement_cb')) {
            return 0.1;
        }

        if (strpos($nature, 'encaissement') !== false || strpos($nature, 'carte') !== false || strpos($nature, 'cb') !== false) {
            return 0.8;
        }
        if (strpos($ecriture_image, 'encaissement') !== false || strpos($ecriture_image, 'carte') !== false || strpos($ecriture_image, 'cb') !== false) {
            return 0.7;
        }
        return 0.6;
    }

    /**
     * Corrélation pour les remises de chèques
     */
    private function correlateRemiseCheque($statement_operation, $key, $ecriture) {
        $nature = $this->cleanup_string($statement_operation->nature());
        $ecriture_image = $this->cleanup_string($ecriture);

        if ($this->match_other_types($ecriture_image, 'remise_cheque')) {
            return 0.1;
        }

        $hasRemiseNature = (strpos($nature, 'remise') !== false);
        $hasChequeNature = (strpos($nature, 'cheque') !== false || strpos($nature, 'chq') !== false);
        if ($hasRemiseNature && $hasChequeNature) {
            return 0.85;
        }

        $hasRemiseImage = (strpos($ecriture_image, 'remise') !== false);
        $hasChequeImage = (strpos($ecriture_image, 'cheque') !== false || strpos($ecriture_image, 'chq') !== false);
        if ($hasRemiseNature || $hasChequeNature || $hasRemiseImage || $hasChequeImage) {
            return 0.75;
        }

        return 0.6;
    }

    /**
     * Corrélation pour les remises d'espèces (liquide)
     */
    private function correlateRemiseEspeces($statement_operation, $key, $ecriture) {
        $nature = $this->cleanup_string($statement_operation->nature());
        $ecriture_image = $this->cleanup_string($ecriture);

        if ($this->match_other_types($ecriture_image, 'remise_especes')) {
            return 0.1;
        }

        $hasEspecesNature = (strpos($nature, 'especes') !== false || strpos($nature, 'liquide') !== false || strpos($nature, 'cash') !== false);
        $hasRemiseNature = (strpos($nature, 'remise') !== false);
        if ($hasRemiseNature && $hasEspecesNature) {
            return 0.85;
        }

        $hasEspecesImage = (strpos($ecriture_image, 'especes') !== false || strpos($ecriture_image, 'liquide') !== false || strpos($ecriture_image, 'cash') !== false);
        $hasRemiseImage = (strpos($ecriture_image, 'remise') !== false);
        if ($hasRemiseNature || $hasEspecesNature || $hasEspecesImage || $hasRemiseImage) {
            return 0.7;
        }

        return 0.6;
    }

    /**
     * Corrélation pour les régularisations de frais
     */
    private function correlateRegularisationFrais($statement_operation, $key, $ecriture) {
        $nature = $this->cleanup_string($statement_operation->nature());
        $ecriture_image = $this->cleanup_string($ecriture);

        if ($this->match_other_types($ecriture_image, 'regularisation_frais')) {
            return 0.1;
        }

        if (strpos($nature, 'regularisation') !== false || strpos($ecriture_image, 'regularisation') !== false) {
            return 0.8;
        }
        if (strpos($nature, 'frais') !== false || strpos($ecriture_image, 'frais') !== false || strpos($ecriture_image, 'commission') !== false) {
            return 0.7;
        }
        return 0.6;
    }

    /**
     * Corrélation par défaut pour type inconnu
     */
    private function correlateInconnu($statement_operation, $key, $ecriture) {
        $nature = $this->cleanup_string($statement_operation->nature());
        $ecriture_image = $this->cleanup_string($ecriture);

        // léger matching par mots communs
        $nature_tokens = preg_split('/\s+/', $nature);
        $score = 0; $count = 0;
        foreach ($nature_tokens as $tok) {
            $tok = trim($tok);
            if (strlen($tok) < 3) { continue; }
            $count++;
            if (strpos($ecriture_image, $tok) !== false) { $score++; }
        }
        if ($count > 0 && $score > 0) {
            return 0.55 + 0.4 * ($score / $count);
        }
        return 0.5;
    }
}