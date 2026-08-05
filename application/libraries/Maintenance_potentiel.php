<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Maintenance Potentiel Library
 *
 * Centralise le calcul et la mise a jour du potentiel de maintenance
 * d'un dossier d'entretien, sur le modele de Formation_progression.
 *
 * Semantique retenue (cf. doc/design_notes/maintenance_aeronefs_design.md) :
 * - `echeance_courante` est une date de butee calendaire, naturellement
 *   "vivante" puisque la date du jour avance seule.
 * - `heures_restantes_courant` est un instantane du potentiel horaire,
 *   fige au moment de la derniere operation (horametre_releve + seuil_heures
 *   du programme). Il ne decompte pas en continu au fil des vols entre deux
 *   operations -- rester simple (PRD, exigences non fonctionnelles), pas
 *   de lecture live de l'horametre courant de l'aeronef en phase 1.
 *
 * @see doc/prds/maintenance_aeronefs_prd.md (EF5)
 * @see doc/plans/maintenance_aeronefs_plan.md (Etape 3.1)
 */
class Maintenance_potentiel {

    const ETAT_A_JOUR = 'a_jour';
    const ETAT_ECHEANCE_PROCHE = 'echeance_proche';
    const ETAT_DEPASSE = 'depasse';

    const SEUIL_ECHEANCE_PROCHE_JOURS_DEFAUT = 30;
    const CONFIG_KEY_SEUIL_JOURS = 'maintenance_seuil_echeance_proche_jours';

    private $CI;

    public function __construct() {
        $this->CI = &get_instance();
        $this->CI->load->model('maintenance_dossier_model');
        $this->CI->load->model('maintenance_operation_model');
        $this->CI->load->model('maintenance_programme_model');
        $this->CI->load->model('maintenance_equipement_model');
        $this->CI->load->model('configuration_model');
    }

    /**
     * Rang de gravite d'un etat, pour determiner le pire des deux quand
     * plusieurs dimensions (date, heures) ou plusieurs entites sont
     * combinees. Plus le rang est eleve, plus l'etat est degrade.
     *
     * @param string $etat
     * @return int
     */
    private function rang($etat) {
        switch ($etat) {
            case self::ETAT_DEPASSE:
                return 2;
            case self::ETAT_ECHEANCE_PROCHE:
                return 1;
            default:
                return 0;
        }
    }

    /**
     * Retourne le pire des deux etats fournis.
     *
     * @param string $etat_a
     * @param string $etat_b
     * @return string
     */
    private function pire($etat_a, $etat_b) {
        return $this->rang($etat_a) >= $this->rang($etat_b) ? $etat_a : $etat_b;
    }

    /**
     * Seuil global "echeance proche", en jours (PRD EF7, defaut 30).
     *
     * @return int
     */
    public function seuil_echeance_proche_jours() {
        $valeur = $this->CI->configuration_model->get_param(self::CONFIG_KEY_SEUIL_JOURS);
        if ($valeur === null || $valeur === '') {
            return self::SEUIL_ECHEANCE_PROCHE_JOURS_DEFAUT;
        }
        return (int) $valeur;
    }

    /**
     * Calcule l'etat d'un dossier a partir de son echeance_courante et/ou
     * de son heures_restantes_courant.
     *
     * - 'depasse' si l'echeance calendaire est passee, ou le potentiel
     *   horaire est negatif.
     * - 'echeance_proche' si l'echeance calendaire tombe dans les N
     *   prochains jours (seuil global, 30 par defaut).
     * - 'a_jour' sinon, y compris quand aucune des deux dimensions n'est
     *   renseignee (dossier sans historique d'operation).
     *
     * @param array $dossier Ligne maintenance_dossiers (echeance_courante, heures_restantes_courant)
     * @return string 'a_jour' | 'echeance_proche' | 'depasse'
     */
    public function calculer_etat($dossier) {
        $etat = self::ETAT_A_JOUR;

        if (!empty($dossier['echeance_courante'])) {
            $etat = $this->pire($etat, $this->calculer_etat_date($dossier['echeance_courante']));
        }

        if (isset($dossier['heures_restantes_courant']) && $dossier['heures_restantes_courant'] !== null && $dossier['heures_restantes_courant'] !== '') {
            $etat = $this->pire($etat, $this->calculer_etat_heures((float) $dossier['heures_restantes_courant']));
        }

        return $etat;
    }

    /**
     * Sous-etat calendaire.
     *
     * @param string $echeance_courante Date (Y-m-d)
     * @return string
     */
    private function calculer_etat_date($echeance_courante) {
        $jours_restants = (int) floor((strtotime($echeance_courante) - strtotime(date('Y-m-d'))) / 86400);

        if ($jours_restants < 0) {
            return self::ETAT_DEPASSE;
        }
        if ($jours_restants <= $this->seuil_echeance_proche_jours()) {
            return self::ETAT_ECHEANCE_PROCHE;
        }
        return self::ETAT_A_JOUR;
    }

    /**
     * Sous-etat horaire. Pas de notion d'"echeance proche" pour les
     * heures en phase 1 (aucun seuil n'est defini par le PRD) : seul un
     * potentiel negatif est considere depasse.
     *
     * @param float $heures_restantes
     * @return string
     */
    private function calculer_etat_heures($heures_restantes) {
        return $heures_restantes < 0 ? self::ETAT_DEPASSE : self::ETAT_A_JOUR;
    }

    /**
     * Met a jour le potentiel d'un dossier a partir d'une operation
     * enregistree, selon la regle de butee de son programme.
     *
     * - Butee calendaire : `nouvelle_echeance` saisie sur l'operation si
     *   presente, sinon date_operation + periodicite_mois du programme.
     * - Butee horaire : le compteur repart a `seuil_heures` du programme
     *   des lors qu'un horametre_releve est saisi sur l'operation.
     *
     * @param int $operation_id
     * @return bool Succes
     */
    public function appliquer_operation($operation_id) {
        $operation = $this->CI->maintenance_operation_model->get($operation_id);
        if (empty($operation)) {
            gvv_error("MAINTENANCE: appliquer_operation - operation introuvable, id=$operation_id");
            return false;
        }

        $dossier = $this->CI->maintenance_dossier_model->get($operation['dossier_id']);
        if (empty($dossier)) {
            gvv_error("MAINTENANCE: appliquer_operation - dossier introuvable, id=" . $operation['dossier_id']);
            return false;
        }

        $programme = $this->CI->maintenance_programme_model->get($dossier['programme_id']);
        if (empty($programme)) {
            gvv_error("MAINTENANCE: appliquer_operation - programme introuvable, id=" . $dossier['programme_id']);
            return false;
        }

        $updates = array();

        if (!empty($programme['regle_butee_date'])) {
            if (!empty($operation['nouvelle_echeance'])) {
                $updates['echeance_courante'] = $operation['nouvelle_echeance'];
            } elseif (!empty($programme['periodicite_mois'])) {
                $updates['echeance_courante'] = date(
                    'Y-m-d',
                    strtotime('+' . (int) $programme['periodicite_mois'] . ' months', strtotime($operation['date_operation']))
                );
            }
        }

        if (!empty($programme['regle_butee_heures'])) {
            if (isset($operation['horametre_releve']) && $operation['horametre_releve'] !== null && $operation['horametre_releve'] !== ''
                && isset($programme['seuil_heures']) && $programme['seuil_heures'] !== null && $programme['seuil_heures'] !== '') {
                $updates['heures_restantes_courant'] = $programme['seuil_heures'];
            }
        }

        if (empty($updates)) {
            return true;
        }

        return (bool) $this->CI->maintenance_dossier_model->update('id', $updates, $dossier['id']);
    }

    /**
     * Pire etat parmi tous les dossiers ouverts d'une seule entite
     * maintenable (aeronef ou equipement), pour la vue de synthese par
     * aeronef (PRD EF7.1) : un aeronef et chacun de ses equipements y
     * apparaissent comme des entites distinctes.
     *
     * @param string $entite_type 'aeronef' ou 'equipement'
     * @param string $entite_id Identifiant de l'entite
     * @return string 'a_jour' | 'echeance_proche' | 'depasse'
     */
    public function etat_entite($entite_type, $entite_id) {
        $etat = self::ETAT_A_JOUR;

        foreach ($this->CI->maintenance_dossier_model->get_ouverts($entite_type, $entite_id) as $dossier) {
            $etat = $this->pire($etat, $this->calculer_etat($dossier));
        }

        return $etat;
    }

    /**
     * Pire etat parmi un aeronef et ses equipements actifs, pour la vue
     * de synthese flotte (PRD EF7.3).
     *
     * @param string $aeronef_id Immatriculation (machinesa.macimmat)
     * @return string 'a_jour' | 'echeance_proche' | 'depasse'
     */
    public function etat_pire_cas($aeronef_id) {
        $etat = $this->etat_entite('aeronef', $aeronef_id);

        $equipements = $this->CI->maintenance_equipement_model->get_by_aeronef($aeronef_id, true);
        foreach ($equipements as $equipement) {
            $etat = $this->pire($etat, $this->etat_entite('equipement', $equipement['id']));
        }

        return $etat;
    }

    /**
     * Corrige le potentiel d'un dossier hors operation (PRD EF5.3).
     * Journalise avec le marqueur MAINTENANCE pour permettre le filtrage
     * des logs applicatifs.
     *
     * @param int $dossier_id
     * @param array $data Champs a corriger (echeance_courante, heures_restantes_courant, commentaire)
     * @param string $user Utilisateur a l'origine de la correction
     * @return bool Succes
     */
    public function mise_a_jour_manuelle($dossier_id, $data, $user) {
        $champs_autorises = array('echeance_courante', 'heures_restantes_courant', 'commentaire');
        $updates = array_intersect_key($data, array_flip($champs_autorises));

        if (empty($updates)) {
            return false;
        }

        gvv_info("MAINTENANCE: mise_a_jour_manuelle dossier_id=$dossier_id user=$user data=" . json_encode($updates));

        return (bool) $this->CI->maintenance_dossier_model->update('id', $updates, $dossier_id);
    }
}

/* End of file Maintenance_potentiel.php */
/* Location: ./application/libraries/Maintenance_potentiel.php */
