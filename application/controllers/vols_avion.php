<?php

/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @filesource vols_avion.php
 * @package controllers
 *
 * Playwright tests:
 *   - npx playwright test tests/bugfix-payeur-selector.spec.js
 */
include('./application/libraries/Gvv_Controller.php');

/**
 *
 * Controleur de gestion des vols avion
 *
 * @author Frédéric
 *
 *         TODO Interdire la saisie aux personnes non autorisées.
 *
 */
class Vols_avion extends Gvv_Controller {
    const HORAMETRE_CENTIEME = 0;
    const HORAMETRE_MINUTES = 1;
    const HORAMETRE_DIXIEME = 2;

    protected $controller = 'vols_avion';
    protected $back_dashboard = 'welcome/section/flights';
    protected $model = 'vols_avion_model';
    protected $kid = 'vaid';
    protected $modification_level = 'planchiste';

    // Headers and first colomns
    protected $title_row;
    protected $first_col;
    protected $pm_first_row;

    // régles de validation
    protected $rules = array(
        'vanbpax' => "is_natural|max_length[1]",
        'vaatt' => "is_natural"
    );

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();

        $this->require_roles(['user']);

        // remplit les selecteurs depuis la base
        $this->load->model('membres_model');
        $this->load->model('comptes_model');
        $this->load->model('avions_model');
        $this->load->model('terrains_model');
        $this->load->model('configuration_model');
        $this->load->model('events_types_model');
        $this->lang->load('vols_avion');

        $this->load->helper('statistic');

        // prépare les entêtes pour les stats
        $this->title_row = array_merge(array(
            $this->lang->line("gvv_total")
        ), $this->lang->line("gvv_months"));

        $this->first_col = $this->lang->line("gvv_vols_avion_stats_col");

        $this->pm_first_row = array_merge(array(
            $this->lang->line("gvv_vue_vols_avion_short_field_type"),
            $this->lang->line("gvv_vue_vols_avion_short_field_vamacid")
        ), $this->title_row);
    }

    /**
     * Retourne true si la section courante gère des planeurs (gestion_planeurs = 1).
     * Les vols avions dans ces sections sont des remorqueurs.
     */
    private function is_glider_section() {
        $section = $this->gvv_model->section();
        return !empty($section['gestion_planeurs']);
    }

    /**
     * Retourne true si l'utilisateur a le droit d'écriture complet sur les vols avion.
     * - planchiste : toujours
     * - pilote_rem : uniquement dans les sections gérant des planeurs
     */
    private function can_write_airplane_flights() {
        if ($this->user_has_role('planchiste')) {
            return true;
        }
        if ($this->user_has_role('pilote_rem') && $this->is_glider_section()) {
            return true;
        }
        return false;
    }

    /**
     * Un auto_planchiste ne peut modifier/supprimer un de ses vols que dans les
     * AUTO_PLANCHISTE_EDIT_WINDOW_DAYS jours suivant sa saisie (created_at).
     * Au-delà, seul un planchiste peut corriger le vol.
     */
    const AUTO_PLANCHISTE_EDIT_WINDOW_DAYS = 7;

    private function auto_planchiste_can_still_edit($created_at) {
        if (empty($created_at)) {
            return false;
        }
        $limite = strtotime('-' . self::AUTO_PLANCHISTE_EDIT_WINDOW_DAYS . ' days');
        return strtotime($created_at) >= $limite;
    }

    /**
     * Surcharge de has_modification_rights() pour inclure pilote_rem dans les sections planeurs.
     *
     * @param int|null $section_id Non utilisé (hérité), la section est déduite du modèle.
     * @return bool
     */
    protected function has_modification_rights($section_id = NULL) {
        if ($this->dx_auth->is_admin()) {
            return TRUE;
        }
        // Honore le mécanisme de bypass pour auto_planchiste (modification_level temporairement vidé)
        if (!isset($this->modification_level) || $this->modification_level === '') {
            return TRUE;
        }
        return $this->can_write_airplane_flights();
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::form_static_element()
     */
    function form_static_element($action) {
        parent::form_static_element($action);
        $pilote_selector = $this->membres_model->section_pilots(0, true);
        $this->data['saisie_par'] = $this->dx_auth->get_username();
        $is_fresh_creation = ($action == CREATION && $this->input->post('vacdeb') === false);
        $this->data['is_new_vol'] = $is_fresh_creation;
        // Pour une création fraîche, le JS initialise les horamètres via horametres_last_data
        // (par machine sélectionnée). On ne force pas de valeur par défaut globale ici
        // afin que le widget reste vide tant qu'aucune machine n'est choisie.
        if ($this->input->post('vacdeb') !== false) {
            // Ré-affichage après échec de validation : $this->data['vacdeb/vacfin'] contient
            // la valeur saisie par l'utilisateur (format heures.minutes pour mode MINUTES).
            // Le widget JS attend toujours des centièmes d'heure (format DB).
            // → Convertir en centièmes pour que le widget reconstitue correctement les minutes.
            $machine = isset($this->data['vamacid']) ? $this->data['vamacid'] : null;
            if ($machine) {
                $mode = $this->get_horametre_mode($machine);
                if ($mode == self::HORAMETRE_MINUTES) {
                    foreach (['vacdeb', 'vacfin'] as $field) {
                        if (isset($this->data[$field]) && is_numeric($this->data[$field])) {
                            $this->data[$field] = round(
                                $this->horametre_to_decimal_hours(floatval($this->data[$field]), $mode),
                                4
                            );
                        }
                    }
                }
            }
        }

        if ($action == CREATION) {
            $defaut_aerodrome = $this->configuration_model->get_param('defaut.aerodrome');
            if ($defaut_aerodrome) {
                $terrain = $this->terrains_model->get_by_id('oaci', $defaut_aerodrome);
                if (!empty($terrain)) {
                    $this->data['valieudeco'] = $defaut_aerodrome;
                    $this->data['valieuatt'] = $defaut_aerodrome;
                }
            }
        }

        $this->config->load('facturation');
        $payeur_selector = $this->comptes_model->section_client_accounts(0, true);
        $this->data['payeur_selector'] = $payeur_selector;
        $this->data['payeur_non_pilote'] = $this->config->item('payeur_non_pilote');
        $this->data['partage'] = $this->config->item('partage');

        $this->data['default_user'] = $this->membres_model->default_id();
        if (! $this->can_write_airplane_flights() && $this->user_has_role('auto_planchiste')) {
            $this->data['auto_planchiste'] = true;
            $this->data['payeur_non_pilote'] = false;
            $this->data['partage'] = false;
            $this->data['pilote_name'] = $this->membres_model->image($this->data['default_user']);
            $pilote_selector = array(
                $this->data['default_user'] => $this->data['pilote_name']
            );
        } else {
            $this->data['auto_planchiste'] = false;
        }

        // Avec les méta-données
        $this->gvvmetadata->set_selector('machine_selector', $this->avions_model->selector_with_null(array(
            'actif' => 1
        )));
        $this->gvvmetadata->set_selector('pilote_selector', $pilote_selector);
        $this->gvvmetadata->set_selector('inst_selector', $this->membres_model->inst_selector());
        $this->gvvmetadata->set_selector('payeur_selector', $payeur_selector);
        $this->gvvmetadata->set_selector('terrains_selector', $this->terrains_model->selector_with_null());

        // Checkboxes formation
        $section = $this->gvv_model->section();

        $certificats = array();
        $select = !empty($section) ? $this->events_types_model->select_all(array(
            'activite' => $section['id'],
            'en_vol' => 1
        )) : array();

        $date_values = array();
        foreach ($select as $row) {
            $id = $row['id'];
            $certificats[] = array(
                'label' => $row['name'],
                'id' => $id
            );
        }

        $this->data['certificats'] = $certificats;
        $this->data['certificat_values'] = $date_values;
        $this->data['section'] = $section;

        $this->data['machines'] = $this->avions_model->machine_list(array(
            'actif' => 1
        ));
        $this->data['horametres_mode'] = $this->avions_model->machine_list(array(
            'actif' => 1
        ), false);
        $this->data['horametres_last'] = $this->gvv_model->latest_horametre_per_machine();
        $this->data['remorqueurs'] = $this->avions_model->remorqueurs_list();

        // Catégories de vol filtrées selon le rôle
        $allowed = $this->get_allowed_categories();
        $this->gvvmetadata->field['volsa']['vacategorie']['Enumerate'] = $allowed;

        // Données JS pour le filtrage dynamique (machine → proprio, remorqueur)
        // pilote_rem dans les sections planeurs voit toutes les machines (comme planchiste)
        $is_privileged = $this->can_write_airplane_flights()
                      || $this->user_has_role('club-admin') || $this->user_has_role('ca')
                      || $this->user_has_role('bureau')     || $this->user_has_role('admin')
                      || $this->user_has_role('instructeur');
        $this->data['proprio_machines']  = $is_privileged ? array() : $this->get_proprio_machines();
        $this->data['is_privileged_user'] = $is_privileged;

        // ici la $this->data['vaduree'] contient la valuer en 1/100 eme
        // var_dump($this->data); exit;
    }

    /**
     * Retourne la liste filtrée des catégories de vol accessibles à l'utilisateur connecté.
     * - planchiste / club-admin / ca / bureau : toutes les catégories
     * - instructeur : Standard, VD, Essai, Propriétaire, PO, BIA
     * - pilote_vd   : Standard, VD, PO, BIA
     * - pilote_rem  : Standard, Remorquage (JS contrôle la visibilité selon la machine)
     * - proprio     : Standard + Propriétaire (JS contrôle selon la machine)
     * - auto_planchiste seul : Standard uniquement
     */
    public function get_allowed_categories() {
        $all = $this->config->item('categories_vol_avion');

        $is_admin = $this->user_has_role('club-admin') || $this->user_has_role('ca')
                 || $this->user_has_role('bureau')     || $this->user_has_role('admin');
        $username    = $this->dx_auth->get_username();
        $owns_machine = !$is_admin && !$this->user_has_role('instructeur')
                     && !$this->user_has_role('planchiste')
                     && $this->db->where('proprio', $username)->from('machinesa')
                            ->count_all_results() > 0;

        $this->load->helper('vols_avion_categories');
        return compute_vols_avion_categories($all, array(
            'admin'           => $is_admin,
            'planchiste'      => $this->user_has_role('planchiste'),
            'instructeur'     => $this->user_has_role('instructeur'),
            'pilote_vd'       => $this->user_has_role('pilote_vd'),
            'pilote_rem'      => $this->user_has_role('pilote_rem'),
            'auto_planchiste' => $this->user_has_role('auto_planchiste'),
            'owns_machine'    => $owns_machine,
            'mecano'          => $this->user_has_role('mecano'),
        ));
    }

    /**
     * Retourne les machines dont l'utilisateur connecté est propriétaire.
     * @return array [macimmat => true]
     */
    public function get_proprio_machines() {
        $username = $this->dx_auth->get_username();
        if (!$username) return array();
        $this->db->select('macimmat')->from('machinesa')
            ->where('proprio', $username)->where('actif', 1);
        $rows = $this->db->get()->result_array();
        $result = array();
        foreach ($rows as $row) {
            $result[$row['macimmat']] = true;
        }
        return $result;
    }

    /*
     * Transforme une valeur HEURE.MINUTE en HEURE.CENTIEME
     */
    private function to_hundredth($hm) {
        $hours = intval($hm);
        $minutes = ($hm - $hours) * 100;
        $centiemes = $minutes / 60;
        $result = $hours + $centiemes;
        return $result;
    }

    /**
     * Normalise une valeur centième vers la valeur canonique de la minute entière la plus proche.
     * Corrige la perte de précision centième→minutes→centième en mode HORAMETRE_MINUTES.
     * Exemple : 10634.71 (42.6 min → 43 min → 43/60 → round 2) = 10634.72
     */
    private function normalize_to_minutes($centieme) {
        $hours   = intval($centieme);
        $minutes = round(($centieme - $hours) * 60);
        return round($hours + $minutes / 60, 2);
    }

    private function get_horametre_mode($machine) {
        if (!$machine) {
            return self::HORAMETRE_CENTIEME;
        }

        $avion = $this->avions_model->get_by_id('macimmat', $machine);
        if (!$avion) {
            return self::HORAMETRE_CENTIEME;
        }

        if (isset($avion['horametre_mode'])) {
            return intval($avion['horametre_mode']);
        }

        if (isset($avion['horametre_en_minutes'])) {
            return intval($avion['horametre_en_minutes']);
        }

        return self::HORAMETRE_CENTIEME;
    }

    private function horametre_to_decimal_hours($value, $mode) {
        $horametre = floatval($value);
        if ($mode == self::HORAMETRE_MINUTES) {
            return $this->to_hundredth($horametre);
        }

        return $horametre;
    }

    /**
     * Transforme les données brutes en base en données affichables
     * Default implementation returns the data attribute
     *
     * @param $action CREATION
     *            | MODIFICATION | VISUALISATION
     */
    function form2database($action = '') {
        $processed_data = parent::form2database($action);
        
        // Convert account ID to member ID for payeur field
        if (!empty($processed_data['payeur']) && is_numeric($processed_data['payeur'])) {
            $account = $this->comptes_model->get_by_id('id', $processed_data['payeur']);
            if ($account && !empty($account['pilote'])) {
                $processed_data['payeur'] = $account['pilote'];
            }
        }
        
        // Server-side enforcement: auto_planchiste can only create flights for themselves
        // (pilote_rem avec droits complets est exclu de cette restriction)
        if (!$this->can_write_airplane_flights() && $this->user_has_role('auto_planchiste')) {
            $processed_data['vapilid'] = $this->dx_auth->get_username();
        }

        // Server-side enforcement: mecano can only create Vol d'essai (category 2) for themselves
        if (!$this->can_write_airplane_flights() && $this->user_has_role('mecano')) {
            $processed_data['vapilid'] = $this->dx_auth->get_username();
            $processed_data['vacategorie'] = 2;
        }

        if (
            isset($processed_data['vamacid']) &&
            isset($processed_data['vacdeb']) &&
            isset($processed_data['vacfin']) &&
            is_numeric($processed_data['vacdeb']) &&
            is_numeric($processed_data['vacfin'])
        ) {
            $mode = $this->get_horametre_mode($processed_data['vamacid']);
            $debut = round($this->horametre_to_decimal_hours($processed_data['vacdeb'], $mode), 4);
            $fin   = round($this->horametre_to_decimal_hours($processed_data['vacfin'], $mode), 4);
            $processed_data['vaduree'] = round($fin - $debut, 2);
            $processed_data['vacdeb'] = $debut;
            $processed_data['vacfin'] = $fin;
        }

        return $processed_data;
    }

    /**
     * Rend vanumvi obligatoire quand le type est VD (vacategorie = 1)
     * et vérifie la non-superposition des horamètres.
     */
    public function formValidation($action, $return_on_success = false) {
        // Vérifier que la catégorie soumise est dans la liste autorisée
        $submitted_cat = $this->input->post('vacategorie');
        if ($submitted_cat !== false) {
            $allowed = $this->get_allowed_categories();
            if (!array_key_exists((int)$submitted_cat, $allowed)) {
                $this->form_validation->set_rules('vacategorie', 'vacategorie',
                    'callback_valid_categorie_access');
            }
        }

        if ($this->input->post('vacategorie') == 1) {
            $this->rules['vanumvi'] = isset($this->rules['vanumvi'])
                ? $this->rules['vanumvi'] . '|required'
                : 'required';
        }
        $vacdeb = $this->input->post('vacdeb');
        $vacfin = $this->input->post('vacfin');
        if (is_numeric($vacdeb) && is_numeric($vacfin) && floatval($vacdeb) > 0 && floatval($vacfin) > 0) {
            $this->rules['vacfin'] = isset($this->rules['vacfin'])
                ? $this->rules['vacfin'] . '|callback_valid_horametre_range'
                : 'callback_valid_horametre_range';
        }

        // Vérifications de conflits (pilote/instructeur/machine déjà en vol) si les heures sont renseignées
        $vahdeb = floatval($this->input->post('vahdeb'));
        $vahfin = floatval($this->input->post('vahfin'));
        if ($vahdeb > 0 && $vahfin > 0) {
            $this->rules['vapilid'] = isset($this->rules['vapilid'])
                ? $this->rules['vapilid'] . '|callback_pilote_au_sol'
                : 'callback_pilote_au_sol';

            $vainst = $this->input->post('vainst');
            if (!empty($vainst)) {
                $this->rules['vainst'] = isset($this->rules['vainst'])
                    ? $this->rules['vainst'] . '|callback_instructeur_au_sol'
                    : 'callback_instructeur_au_sol';
            }

            $this->rules['vamacid'] = isset($this->rules['vamacid'])
                ? $this->rules['vamacid'] . '|callback_machine_au_sol'
                : 'callback_machine_au_sol';
        }

        // Vérification durée maximale 8 heures
        $vaduree = floatval($this->input->post('vaduree'));
        if ($vaduree > 0) {
            $this->rules['vaduree'] = isset($this->rules['vaduree'])
                ? $this->rules['vaduree'] . '|callback_valid_vol_duration'
                : 'callback_valid_vol_duration';
        }

        // Vérification autonomie machine
        $vamacid = $this->input->post('vamacid');
        if (!empty($vamacid) && $vaduree > 0) {
            $this->rules['vaduree'] = isset($this->rules['vaduree'])
                ? $this->rules['vaduree'] . '|callback_valid_vol_autonomie'
                : 'callback_valid_vol_autonomie';
        }

        return parent::formValidation($action, $return_on_success);
    }

    /**
     * Callback CI : vérifie la cohérence de l'horamètre par rapport aux vols
     * adjacents sur la même machine.
     * - Le vol immédiatement précédant (plus grand vacdeb < vacdeb courant)
     *   doit avoir vacfin <= vacdeb courant.
     * - Le vol immédiatement suivant (plus petit vacdeb > vacdeb courant)
     *   doit avoir vacdeb >= vacfin courant.
     * Permet la re-saisie de vols oubliés entre des vols existants.
     */
    public function valid_horametre_range($vacfin) {
        $vamacid = $this->input->post('vamacid');
        $vaid    = intval($this->input->post('vaid'));

        // Convertir les valeurs soumises en centièmes d'heure (comme form2database le fait),
        // afin de comparer avec les valeurs stockées en centièmes dans la base.
        // Arrondir à 2 décimales (précision de la colonne decimal(8,2)) pour éviter les
        // erreurs de virgule flottante dans to_hundredth() (ex: 1776.4999... au lieu de 1776.80).
        $mode    = $this->get_horametre_mode($vamacid);
        $vacdeb  = round($this->horametre_to_decimal_hours(floatval($this->input->post('vacdeb')), $mode), 2);
        $vacfin  = round($this->horametre_to_decimal_hours(floatval($vacfin), $mode), 2);

        // Vol immédiatement précédant : plus grand vacdeb < vacdeb courant
        $this->db->select('vacdeb, vacfin')
            ->from('volsa')
            ->where('vamacid', $vamacid)
            ->where('vacdeb <', $vacdeb);
        if ($vaid > 0) {
            $this->db->where('vaid !=', $vaid);
        }
        $prev = $this->db->order_by('vacdeb', 'DESC')->limit(1)->get()->row_array();

        if ($prev) {
            $prev_vacfin = floatval($prev['vacfin']);
            if ($mode == self::HORAMETRE_MINUTES) {
                $prev_vacfin = $this->normalize_to_minutes($prev_vacfin);
            }
            if ($prev_vacfin > $vacdeb) {
                $this->form_validation->set_message('valid_horametre_range',
                    $this->lang->line('gvv_vols_avion_error_horametre_prev'));
                return false;
            }
        }

        // Vol immédiatement suivant : plus petit vacdeb > vacdeb courant
        $this->db->select('vacdeb, vacfin')
            ->from('volsa')
            ->where('vamacid', $vamacid)
            ->where('vacdeb >', $vacdeb);
        if ($vaid > 0) {
            $this->db->where('vaid !=', $vaid);
        }
        $next = $this->db->order_by('vacdeb', 'ASC')->limit(1)->get()->row_array();

        if ($next) {
            $next_vacdeb = floatval($next['vacdeb']);
            if ($mode == self::HORAMETRE_MINUTES) {
                $next_vacdeb = $this->normalize_to_minutes($next_vacdeb);
            }
            if ($next_vacdeb < $vacfin) {
                $this->form_validation->set_message('valid_horametre_range',
                    $this->lang->line('gvv_vols_avion_error_horametre_next'));
                return false;
            }
        }

        return true;
    }

    public function valid_categorie_access($value) {
        $allowed = $this->get_allowed_categories();
        if (!array_key_exists((int)$value, $allowed)) {
            $this->form_validation->set_message('valid_categorie_access',
                $this->lang->line('gvv_vols_avion_error_categorie_access'));
            return false;
        }
        return true;
    }

    /**
     * Callback CI : vérifie que le pilote n'est pas déjà en vol à ce moment.
     */
    public function pilote_au_sol($vapilid) {
        $vadate = date_ht2db($this->input->post('vadate'));
        $vahdeb = floatval($this->input->post('vahdeb'));
        $vahfin = floatval($this->input->post('vahfin'));
        $vaid   = intval($this->input->post('vaid'));

        if (!$vapilid || !$vahdeb || !$vahfin) {
            return true;
        }

        if ($this->gvv_model->is_person_in_flight($vapilid, $vadate, $vahdeb, $vahfin, $vaid)) {
            $this->form_validation->set_message('pilote_au_sol',
                $this->lang->line('gvv_vols_avion_error_pilote_au_sol'));
            return false;
        }
        return true;
    }

    /**
     * Callback CI : vérifie que l'instructeur n'est pas déjà en vol à ce moment.
     */
    public function instructeur_au_sol($vainst) {
        if (empty($vainst)) {
            return true;
        }

        $vadate = date_ht2db($this->input->post('vadate'));
        $vahdeb = floatval($this->input->post('vahdeb'));
        $vahfin = floatval($this->input->post('vahfin'));
        $vaid   = intval($this->input->post('vaid'));

        if (!$vahdeb || !$vahfin) {
            return true;
        }

        if ($this->gvv_model->is_person_in_flight($vainst, $vadate, $vahdeb, $vahfin, $vaid)) {
            $this->form_validation->set_message('instructeur_au_sol',
                $this->lang->line('gvv_vols_avion_error_instructeur_au_sol'));
            return false;
        }
        return true;
    }

    /**
     * Callback CI : vérifie que la machine n'est pas déjà en vol à ce moment.
     */
    public function machine_au_sol($vamacid) {
        if (empty($vamacid)) {
            return true;
        }

        $vadate = date_ht2db($this->input->post('vadate'));
        $vahdeb = floatval($this->input->post('vahdeb'));
        $vahfin = floatval($this->input->post('vahfin'));
        $vaid   = intval($this->input->post('vaid'));

        if (!$vahdeb || !$vahfin) {
            return true;
        }

        if ($this->gvv_model->is_machine_in_flight($vamacid, $vadate, $vahdeb, $vahfin, $vaid)) {
            $this->form_validation->set_message('machine_au_sol',
                $this->lang->line('gvv_vols_avion_error_machine_au_sol'));
            return false;
        }
        return true;
    }

    /**
     * Callback CI : vérifie que la durée du vol ne dépasse pas 8 heures.
     * vaduree est en centièmes d'heure (8.0 = 8h00).
     */
    public function valid_vol_duration($vaduree) {
        $duration = floatval($vaduree);
        if ($duration <= 0) {
            return true;
        }

        if ($duration > 8.0) {
            $this->form_validation->set_message('valid_vol_duration',
                $this->lang->line('gvv_vols_avion_error_vol_trop_long'));
            return false;
        }
        return true;
    }

    /**
     * Callback CI : vérifie que la durée du vol ne dépasse pas l'autonomie de la machine.
     * vaduree est en heures décimales (ex : 3.5 = 3h30).
     */
    public function valid_vol_autonomie($vaduree) {
        $duration = floatval($vaduree);
        if ($duration <= 0) {
            return true;
        }

        $vamacid = $this->input->post('vamacid');
        if (empty($vamacid)) {
            return true;
        }

        $avion = $this->avions_model->get_by_id('macimmat', $vamacid);
        if (!$avion || !isset($avion['autonomie_en_heures']) || $avion['autonomie_en_heures'] === null || $avion['autonomie_en_heures'] === '') {
            return true;
        }

        $autonomie = floatval($avion['autonomie_en_heures']);
        if ($duration > $autonomie) {
            $this->form_validation->set_message('valid_vol_autonomie',
                sprintf($this->lang->line('gvv_vols_avion_error_autonomie_depassee'), $vamacid, $autonomie));
            return false;
        }
        return true;
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::create()
     */
    function create() {
        if (! $this->can_write_airplane_flights() && ! $this->user_has_role('auto_planchiste')
                && ! $this->user_has_role('mecano')) {
            $this->dx_auth->deny_access();
            return;
        }
        parent::create(TRUE);
        $this->data['vaid'] = 0;
        $this->data['vadate'] = date("Y-m-d");


        // et affiche le formulaire
        load_last_view('vols_avion/formView', $this->data);
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::edit()
     */
    function edit($id = '', $load_view = true, $action = MODIFICATION) {
        // Access rules:
        //   planchiste / pilote_rem  → full edit
        //   auto_planchiste + own flight → edit own
        //   mecano + own Vol d'essai flight → edit own
        //   all other logged-in users → view-only (any flight)
        $is_planchiste = $this->can_write_airplane_flights();
        $is_auto_planchiste = $this->user_has_role('auto_planchiste');
        $is_mecano = !$is_planchiste && $this->user_has_role('mecano');
        $bypass_modification_level = FALSE;

        if ($is_planchiste) {
            $action = MODIFICATION;
        } else {
            $flight = $this->gvv_model->get_by_id('vaid', $id);
            $mlogin = $this->dx_auth->get_username();
            $is_own_flight = (!empty($flight) && $flight['vapilid'] == $mlogin);
            if ($is_own_flight && $is_auto_planchiste && $this->auto_planchiste_can_still_edit($flight['created_at'])) {
                // auto_planchiste editing own flight, within the edit window: bypass level check
                $action = MODIFICATION;
                $bypass_modification_level = TRUE;
            } elseif ($is_own_flight && $is_mecano && !empty($flight) && (int)$flight['vacategorie'] === 2) {
                // mecano editing own Vol d'essai flight
                $action = MODIFICATION;
                $bypass_modification_level = TRUE;
            } else {
                // Non-planchiste: read-only for own flight or any other flight
                $action = VISUALISATION;
            }
        }

        $this->load->model('ecritures_model');
        if ($bypass_modification_level) {
            // Temporarily clear modification_level so ensure_modification_rights() in parent::edit()
            // skips the planchiste check — ownership was already verified above.
            $saved_level = $this->modification_level;
            $this->modification_level = '';
            parent::edit($id, FALSE, $action);
            $this->modification_level = $saved_level;
        } else {
            parent::edit($id, FALSE, $action);
        }
        
        // Convert member ID to account ID for payeur field (for form display)
        if (!empty($this->data['payeur'])) {
            $account = $this->comptes_model->pilot_account($this->data['payeur']);
            if ($account && !empty($account['id'])) {
                $this->data['payeur'] = $account['id'];
            }
        }

        // Détermine le mode horamètre initial pour la machine sélectionnée
        $horametres_mode = isset($this->data['horametres_mode']) ? $this->data['horametres_mode'] : array();
        $vamacid = isset($this->data['vamacid']) ? $this->data['vamacid'] : '';
        $this->data['initial_horametre_mode'] = isset($horametres_mode[$vamacid]) ? (int)$horametres_mode[$vamacid] : 0;

        // Recharge les evénements de formation
        $events = $this->event_model->flight_events(array(
            'evaid' => $id,
            'en_vol' => 1,
            'activite' => 3
        ));
        $date_values = array();
        foreach ($events as $event) {
            $date_values[$event['etype']] = 1;
        }
        $this->data['certificat_values'] = $date_values;

        // affiche le formulaire
        load_last_view('vols_avion/formView', $this->data);
    }

    /**
     * Supprime un élèment
     */
    function delete($id) {
        if (! $this->can_write_airplane_flights()) {
            $flight = $this->gvv_model->get_by_id($this->kid, $id);
            $mlogin = $this->dx_auth->get_username();
            $is_own_flight = (!empty($flight) && $flight['vapilid'] == $mlogin);
            if ($is_own_flight && $this->user_has_role('auto_planchiste') && $this->auto_planchiste_can_still_edit($flight['created_at'])) {
                // auto_planchiste can delete own flights, within the edit window
            } elseif ($is_own_flight && $this->user_has_role('mecano') && !empty($flight) && (int)$flight['vacategorie'] === 2) {
                // mecano can delete own Vol d'essai flights
            } else {
                $this->dx_auth->deny_access();
                return;
            }
        }

        $this->load->model('ecritures_model');
        if (count($this->ecritures_model->select_flight_frozen_lines($id, "vol_avion"))) {
            // Il y a des lignes gelées la suppression est interdite
            $this->session->set_flashdata('popup', "Suppression interdite, écriture vérouillée par le comptable.");
        } else {
            // détruit en base
            $this->pre_delete($id);
            $this->gvv_model->delete(array(
                $this->kid => $id
            ));
        }
        $this->pop_return_url();
    }

    /**
     * Selectionne les éléments à afficher sur une page
     *
     * @param unknown_type $premier
     * @param unknown_type $message
     * @param unknown_type $per_page
     */
    function select_page($premier = 0, $message = '', $per_page = NULL, $order = "desc") {
        if (! isset($per_page))
            $per_page = $this->session->userdata('per_page');

        $this->data['action'] = VISUALISATION;
        $this->data['section'] = $this->gvv_model->section();

        $this->data['filter_active'] = $this->session->userdata('filter_active');
        $this->data['filter_date'] = '';
        $this->data['date_end'] = '';
        $this->data['filter_pilote'] = '';
        $this->data['filter_machine'] = '';
        $this->data['filter_aero'] = 'all';
        $this->data['filter_25'] = 0;
        $this->data['filter_dc'] = 0;
        $this->data['filter_vi'] = 0;
        $this->data['filter_prive'] = 0;
        $this->data['planchiste'] = $this->user_has_role('planchiste');
        $year = $this->session->userdata('year');
        $date25 = date_m25ans($year);
        $selection = "YEAR(vadate) = \"$year\"";

        $this->data['machine_selector'] = '';
        $pilote_selector = $this->membres_model->section_pilots(0, false);
        $this->data['pilote_selector'] = $pilote_selector;

        $machine_selector = $this->avions_model->selector_with_null();
        $this->data['machine_selector'] = $machine_selector;

        $aero_selector = $this->terrains_model->selector_with_all();
        $this->data['aero_selector'] = $aero_selector;

        $this->data['year_selector'] = $this->gvv_model->getYearSelector("vadate");
        $this->data['year'] = $this->session->userdata('year');

        if ($this->session->userdata('filter_active')) {
            $order = "asc";

            $filter_pilote = $this->session->userdata('filter_pilote');
            if ($filter_pilote) {
                $this->data['filter_pilote'] = $filter_pilote;
                $selection .= " and (vapilid = \"$filter_pilote\" or vainst = \"$filter_pilote\" )";
            }

            $filter_machine = $this->session->userdata('filter_machine');
            if ($filter_machine) {
                $this->data['filter_machine'] = $filter_machine;
                if ($selection != '')
                    $selection .= " and ";
                $selection .= "vamacid = \"$filter_machine\" ";
            }

            $filter_aero = $this->session->userdata('filter_aero');
            if ($filter_aero == "all") {
                $filter_aero = '';
            }
            if ($filter_aero) {
                $this->data['filter_aero'] = $filter_aero;
                if ($selection != '')
                    $selection .= " and ";
                $selection .= "valieudeco = \"$filter_aero\" ";
            }

            $filter_25 = $this->session->userdata('filter_25');
            if ($filter_25 == 1) {
                $this->data['filter_25'] = $filter_25;
                $selection .= " and (mdaten >= \"$date25\" )";
            } else if ($filter_25 == 2) {
                $this->data['filter_25'] = $filter_25;
                $selection .= " and (mdaten < \"$date25\" )";
            }

            $filter_dc = $this->session->userdata('filter_dc');
            if ($filter_dc) {
                $this->data['filter_dc'] = $filter_dc;
                $selection .= " and (vadc = \"$filter_dc\" )";
            }

            $filter_vi = $this->session->userdata('filter_vi');
            if ($filter_vi) {
                $this->data['filter_vi'] = $filter_vi;
                $categorie = $filter_vi - 1;
                $selection .= " and (vacategorie = \"$categorie\" )";
            }

            $filter_prive = $this->session->userdata('filter_prive');
            if ($filter_prive) {
                $this->data['filter_prive'] = $filter_prive;
                $filter_prive--;
                $selection .= " and (machinesa.maprive = \"$filter_prive\" )";
            }

            $filter_date = $this->session->userdata('filter_date');
            $date_end = $this->session->userdata('date_end');
            if ($filter_date) {
                if ($selection != '')
                    $selection .= " and ";
                $this->data['filter_date'] = $filter_date;
                if ($date_end) {
                    $selection .= "vadate >= \"" . date_ht2db($filter_date) . "\" ";
                } else {
                    $selection .= "vadate = \"" . date_ht2db($filter_date) . "\" ";
                }
            }

            if ($date_end) {
                if ($selection != '')
                    $selection .= " and ";
                $this->data['date_end'] = $date_end;
                $selection .= "vadate <= \"" . date_ht2db($date_end) . "\" ";
            }

            if ($selection == "")
                $selection = array();
        }

        // calcul des consommations
        // Doit être appelé avant le select_page
        $this->data['conso'] = $this->gvv_model->conso($year, $selection);
        $this->data['by_category'] = $this->gvv_model->stats_by_category($selection);

        $this->data['select_result'] = $this->gvv_model->select_page($year, $per_page, $premier, $selection, $order);
        $this->data['kid'] = $this->kid;
        $this->data['controller'] = $this->controller;
        $this->data['lines'] = $this->gvv_model->count($selection);
        $this->data['count'] = $this->gvv_model->sum('vaatt', $selection);
        $this->data['total'] = $this->gvv_model->sum('vaduree', $selection);
        $this->data['m25ans'] = $this->gvv_model->sum('vaduree', $selection, array(
            'mdaten >' => $date25
        ));
        $this->data['count_m25ans'] = $this->gvv_model->sum('vaatt', $selection, array(
            'mdaten >' => $date25
        ));
        $this->data['remorquage'] = $this->gvv_model->sum('vaduree', $selection, array(
            'vacategorie' => 3
        ));
        $this->data['count_remorquage'] = $this->gvv_model->sum('vaatt', $selection, array(
            'vacategorie' => 3
        ));
        $this->data['premier'] = $premier;
        $this->data['message'] = $message;

        if ($this->session->userdata('filter_active') && $filter_pilote) {
            // Calcul aussi les heures CDB, et instructeurs
            $this->data['by_pilote'] = 1;
            $this->data['dc'] = $this->gvv_model->sum('vaduree', $selection, array(
                'vadc' => 1,
                'vapilid' => $filter_pilote
            ));
            $this->data['count_dc'] = $this->gvv_model->sum('vaatt', $selection, array(
                'vadc' => 1,
                'vapilid' => $filter_pilote
            ));
            $this->data['inst'] = $this->gvv_model->sum('vaduree', $selection, array(
                'vadc' => 1,
                'vainst' => $filter_pilote
            ));
            $this->data['cdb'] = $this->data['inst'] + $this->gvv_model->sum('vaduree', $selection, array(
                'vadc' => 0,
                'vapilid' => $filter_pilote
            ));
        } else {
            $this->data['by_pilote'] = 0;
            $this->data['dc'] = $this->gvv_model->sum('vaduree', $selection, array(
                'vadc' => 1
            ));
            $this->data['count_dc'] = $this->gvv_model->sum('vaatt', $selection, array(
                'vadc' => 1
            ));
            $this->data['inst'] = 0;
            $this->data['cdb'] = 0;
        }
        $this->data['has_modification_rights'] = $this->has_modification_rights();

        $this->data['default_user'] = $this->membres_model->default_id();
        if (! $this->can_write_airplane_flights() && $this->user_has_role('auto_planchiste')) {
            $this->data['auto_planchiste'] = true;
            $this->data['payeur_non_pilote'] = false;
            $this->data['partage'] = false;
            $this->data['pilote_name'] = $this->membres_model->image($this->data['default_user']);
            $pilote_selector = array(
                $this->data['default_user'] => $this->data['pilote_name']
            );
        } else {
            $this->data['auto_planchiste'] = false;
        }

        if ($this->data['auto_planchiste']) {
            // Marque les vols que l'auto_planchiste peut encore modifier/supprimer
            // (les siens, saisis il y a moins de AUTO_PLANCHISTE_EDIT_WINDOW_DAYS jours)
            // pour permettre à la vue d'afficher les actions modifier/supprimer sur ces lignes.
            $mlogin = $this->dx_auth->get_username();
            foreach ($this->data['select_result'] as &$row) {
                $row['auto_planchiste_editable'] = ($row['vapilid'] == $mlogin
                        && $this->auto_planchiste_can_still_edit($row['created_at']));
            }
            unset($row);
            $this->gvvmetadata->store_table('vue_vols_avion', $this->data['select_result']);
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::page()
     */
    function page($premier = 0, $message = '', $selection = array()) {
        $this->push_return_url("vols avion page");
        $this->select_page($premier, $message);
        return load_last_view($this->table_view, $this->data, $this->unit_test);
    }

    /**
     * Export des planches au format CSV
     */
    function csv() {
        $this->select_page(0, "", 100000);
        $this->gvvmetadata->csv("vue_vols_avion");
    }

    /**
     * Export des planches au format PDF
     */
    function pdf() {
        $this->select_page(0, "", 100000, null, "asc");

        $year = $this->session->userdata('year');

        $this->load->library('Pdf');
        $pdf = new Pdf();

        $titre = $this->lang->line("gvv_vols_avion_title_list") . " $year";
        $pdf->set_title($titre);
        $pdf->AddPage('L');
        $pdf->title($titre, 1);

        $tab = array();

        $tab[0] = $this->lang->line("gvv_vols_avion_pdf_header");

        $data = $this->data;
        $results = $this->data['select_result'];
        $pilots = $this->data['pilote_selector'];
        // print_r($data);
        $line = 1;
        foreach ($results as $row) {

            $row['vadate'] = date_db2ht($row['vadate']);
            if (isset($row['vainst'])) {
                $row['vainst'] = substr($pilots[$row['vainst']], 0, 12);
            }
            foreach (
                array(
                    'vadc',
                    'm25ans'
                ) as $field
            ) {
                $row[$field] = ($row[$field]) ? 'X' : '';
            }
            $categories = $this->config->item('categories_vol_avion_short');
            $row['vacategorie'] = $categories[$row['vacategorie']];

            $fld = 0;
            $fields = array(
                'vadate',
                'vacdeb',
                'vacfin',
                'vaduree',
                'vamacid',
                'vaatt',
                'pilote',
                'instructeur',
                'vacategorie',
                'vadc',
                'prive',
                'm25ans',
                'vaobs'
            );
            foreach ($fields as $field) {
                $tab[$line][$fld++] = $row[$field];
            }
            $line++;
            foreach (
                array(
                    'vadate',
                    'vacdeb',
                    'vacfin',
                    'vaduree',
                    'vamacid',
                    'pilote',
                    'instructeur',
                    'vacategorie',
                    'vadc',
                    'prive',
                    'm25ans'
                ) as $field
            ) {
                // $backup .= $row[$field] . ";";
            }
            // $backup .= $row['vaobs'] . "\n";
        }
        $w = array(
            18,
            15,
            15,
            15,
            15,
            16,
            35,
            16,
            8,
            8,
            8,
            10,
            60
        );
        $align = array(
            'L',
            'R',
            'R',
            'R',
            'R',
            'R',
            'L',
            'L',
            'L',
            'C',
            'C',
            'C',
            'L'
        );
        $pdf->table($w, 8, $align, $tab);
        $pdf->Output('I', pdf_filename($titre));
    }

    /**
     * Active ou désactive le filtrage
     */
    public function filterValidation($action) {
        $button = $this->input->post('button');

        if ($button == $this->lang->line("gvv_str_select")) {
            // Enable filtering
            $session['filter_date'] = $this->input->post('filter_date');
            $session['date_end'] = $this->input->post('date_end');
            $session['filter_pilote'] = $this->input->post('filter_pilote');
            $session['filter_machine'] = $this->input->post('filter_machine');
            $session['filter_aero'] = $this->input->post('filter_aero');

            $session['filter_25'] = $this->input->post('filter_25');
            $session['filter_dc'] = $this->input->post('filter_dc');
            $session['filter_prive'] = $this->input->post('filter_prive');
            $session['filter_vi'] = $this->input->post('filter_vi');

            $session['filter_active'] = 1;
            $this->session->set_userdata($session);
        } else {
            // Disable filtering
            foreach (
                array(
                    'filter_date',
                    'date_end',
                    'filter_pilote',
                    'filter_machine',
                    'filter_aero',
                    'filter_active',
                    'filter_25',
                    'filter_dc',
                    'filter_prive',
                    'filter_vi'
                ) as $field
            ) {
                $this->session->unset_userdata($field);
            }
        }
        redirect($this->controller . '/page');
    }

    /**
     * Affiche les vols de la machine
     */
    public function vols_de_la_machine($machine) {
        // Enable filtering
        $session['filter_date'] = '';
        $session['date_end'] = '';
        $session['filter_pilote'] = '';
        $session['filter_machine'] = $machine;
        $session['filter_aero'] = 'all';
        $session['filter_active'] = 1;
        $this->session->set_userdata($session);
        $this->page();
    }

    /**
     * Affiche les vols du pilote
     */
    public function vols_du_pilote($pilote) {
        // Enable filtering
        $session['filter_date'] = '';
        $session['date_end'] = '';
        $session['filter_pilote'] = $pilote;
        $session['filter_machine'] = '';
        $session['filter_aero'] = 'all';
        $session['filter_active'] = 1;
        $this->session->set_userdata($session);
        $this->page();
    }

    /**
     * Statistiques
     */
    public function stat_per_month($year) {
        $selection = array(
            'year(vadate)' => $year
        );
        $date25 = date_m25ans($year);

        $pm = array();
        $where = $selection;
        $pm[] = $this->gvv_model->line_monthly('centiemes', $where);
        $pm[] = $this->gvv_model->line_monthly('count', $where);

        $where = array_merge($selection, array(
            'mdaten >=' => $date25
        ));
        $pm[] = $this->gvv_model->line_monthly('centiemes', $where);
        $pm[] = $this->gvv_model->line_monthly('centiemes', $where, $pm[1]);
        $pm[] = $this->gvv_model->line_monthly('count', $where);
        $pm[] = $this->gvv_model->line_monthly('count', $where, $pm[2]);

        $where = array_merge($selection, array(
            'msexe' => 'F'
        ));
        $pm[] = $this->gvv_model->line_monthly('centiemes', $where);
        $pm[] = $this->gvv_model->line_monthly('centiemes', $where, $pm[1]);
        $pm[] = $this->gvv_model->line_monthly('count', $where);
        $pm[] = $this->gvv_model->line_monthly('count', $where, $pm[2]);

        $where = array_merge($selection, array(
            'vadc' => 1
        ));
        $pm[] = $this->gvv_model->line_monthly('centiemes', $where);
        $pm[] = $this->gvv_model->line_monthly('centiemes', $where, $pm[1]);
        $pm[] = $this->gvv_model->line_monthly('count', $where);
        $pm[] = $this->gvv_model->line_monthly('count', $where, $pm[2]);

        $where = array_merge($selection, array(
            'vacategorie' => 1
        ));
        $pm[] = $this->gvv_model->line_monthly('count', $where);
        return $pm;
    }

    /**
     * Calcul les statistiques par machine
     */
    public function stat_per_machine($year, $type = 'centiemes') {
        $selection = array(
            'year(vadate)' => $year
        );

        $pm = array();

        $machines = $this->avions_model->select_all(array(), "macmodele");
        foreach ($machines as $machine) {
            $immat = $machine['macimmat'];
            $modele = $machine['macmodele'];
            $line = array(
                $modele,
                $immat
            );
            $where = array_merge($selection, array(
                'vamacid' => $immat
            ));
            $line = array_merge($line, $this->gvv_model->line_monthly($type, $where));
            if ($line[2] > 0) {
                $pm[] = $line;
            }
        }
        return $pm;
    }

    /**
     * Affiche la page de statistique
     */
    public function statistic() {
        $this->load->helper('Statistic');
        $year = $this->session->userdata('year');

        $data['per_month'] = $this->stat_per_month($year);
        $data['per_machine'] = $this->stat_per_machine($year);
        $data['machines'] = $this->avions_model->list_of();
        $data['year'] = $year;
        $data['year_selector'] = $this->gvv_model->getYearSelector("vadate");
        $this->push_return_url("vols avion statistiques");

        $data['latest_flight'] = $this->gvv_model->latest_flight(array(
            'year(vadate)' => $year
        ));
        $flight_exist = (count($data['latest_flight']) > 0);

        if ($flight_exist) {
            $latest_date = $data['latest_flight'][0]['vadate'];
            $latest_time = $data['latest_flight'][0]['vacdeb'];
            $latest_epoch = strtotime($latest_date) + (int) ($latest_time * 3600);

            $filename = image_dir() . "avion_mois_$year.png";
            if (no_file_or_file_too_old($filename, $latest_epoch)) {
                month_chart($filename, $data['per_month'], array(
                    1,
                    3,
                    7,
                    11
                ), "Heures de vol");
            }
        }
        load_last_view('vols_avion/statistic', $data);
    }

    /**
     * Export des stats par mois en CSV
     */
    function csv_month($year) {
        $this->load->helper('csv');

        $this->load->helper('statistic');

        $data = $this->stat_per_month($year);
        $title = $this->lang->line("gvv_vols_avion_header_airplane_activity") . " " . $this->lang->line("gvv_vols_avion_header_per_month") . " " . $this->lang->line("gvv_vols_avion_header_in") . " $year";

        add_first_row($data, $this->title_row);
        add_first_col($data, $this->first_col);

        csv_file($title, $data, true);
    }

    /**
     *
     * Export des stats par mois en pdf
     *
     * @param unknown_type $year
     */
    function pdf_month($year) {
        $this->load->helper('statistic');

        $data = $this->stat_per_month($year);

        $this->load->library('Pdf');
        $pdf = new Pdf();

        $title = "Répartition des heures de vol avion $year par mois";
        pdf_per_month_page($pdf, $year, $data, $title, "avion");

        $pdf->Output('I', pdf_filename($title));
    }

    /**
     *
     * Export des stats par machine en CSV
     *
     * @param unknown_type $year
     */
    function csv_machine($year) {
        $this->load->helper('csv');

        $this->load->helper('statistic');

        $data = $this->stat_per_machine($year);

        $title = $this->lang->line("gvv_vols_avion_header_airplane_activity") . " " . $this->lang->line("gvv_vols_avion_header_per_aircraft") . " " . $this->lang->line("gvv_vols_avion_header_in") . " $year";
        add_first_row($data, $this->pm_first_row);

        csv_file($title, $data, true);
    }

    /**
     * Export des stats par machine en PDF
     * Enter description here .
     *
     * ..
     *
     * @param unknown_type $year
     */
    function pdf_machine($year) {
        $this->load->helper('statistic');

        $data = $this->stat_per_machine($year);
        $vols = $this->stat_per_machine($year, 'count');
        add_first_row($data, $this->pm_first_row);
        add_first_row($vols, $this->pm_first_row);

        $this->load->library('Pdf');
        $pdf = new Pdf();

        // pdf_per_machine_page($pdf, $year, $data, $vols, "avion");

        $pdf->AddPage();
        $title1 = $this->lang->line("gvv_vols_avion_header_hours") . " $year " . $this->lang->line("gvv_vols_avion_header_per_aircraft");

        $pdf->title($title1);

        $w = array(
            15,
            18,
            12,
            12,
            12,
            12,
            12,
            12,
            12,
            12,
            12,
            12,
            12,
            12,
            12,
            12,
            12,
            12,
            12
        );
        $align = array(
            'L',
            'L',
            'R',
            'R',
            'R',
            'R',
            'R',
            'R',
            'R',
            'R',
            'R',
            'R',
            'R',
            'R',
            'R',
            'R',
            'R',
            'R',
            'R'
        );

        $pdf->table($w, 8, $align, $data);

        if (count($vols)) {
            $pdf->AddPage();
            $title2 = $this->lang->line("gvv_vols_avion_header_flights") . " $year " . $this->lang->line("gvv_vols_avion_header_per_aircraft");

            $pdf->title($title2);
            $pdf->table($w, 8, $align, $vols);
        }

        $pdf->AddPage();
        $pdf->title($title1);
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->Image(image_dir() . "avion_machine_$year.png", $x + 10, $y, 170);

        $pdf->Output('I', pdf_filename($title1));
    }

    /**
     * Retourne des informations sur la machine selectionnée
     */
    function ajax_machine_info() {
        $machine = $this->input->post('machine');

        gvv_debug("machine=$machine");
        $avion = $this->avions_model->get_by_id('macimmat', $machine);
        $id = $avion['macimmat'];
        $places = $avion['macplaces'];
        if (isset($avion['horametre_mode'])) {
            $horametre_mode = intval($avion['horametre_mode']);
        } elseif (isset($avion['horametre_en_minutes'])) {
            $horametre_mode = intval($avion['horametre_en_minutes']);
        } else {
            $horametre_mode = self::HORAMETRE_CENTIEME;
        }
        if ($horametre_mode == self::HORAMETRE_MINUTES) {
            $unit = 'min';
        } elseif ($horametre_mode == self::HORAMETRE_DIXIEME) {
            $unit = 'tenth';
        } else {
            $unit = 'cent';
        }
        $hora = $this->gvv_model->latest_horametre(array(
            'vamacid' => $id
        ));
        gvv_debug("latest_horametre($id)=$hora");

        $json = '{';
        $json .= "\"machine\": \"$id\"";
        $json .= ", \"places\": \"$places\"";
        $json .= ", \"unit\": \"$unit\"";
        $json .= ", \"hora\": \"$hora\"";
        $json .= "}";
        echo $json;
    }

    /**
     * Hook activé après la création d'un élément
     * Ce mécanisme permet de laisser le contrôleur parent faire la majeur
     * partie de boulot mais également de réaliser des traitements spécifiques
     * dans les enfants.
     *
     * @param $data enregistrement
     *            crée
     */
    function post_create($data = array()) {
        gvv_debug($this->controller . " overwritten creation " . var_export($data, true));

        $certificats = $this->input->post('certificat_values');

        $vaid = $data['vaid'];

        if ($vaid < 1) {
            var_dump("Erreur vols_avion.post_create vaid = 0");
            var_dump($_POST);
            exit();
        }

        $event = array(
            'emlogin' => $data['vapilid'],
            'edate' => $data['vadate'],
            'evaid' => $data['vaid'],
            'ecomment' => $data['vaobs']
        );

        if ($certificats)
            foreach ($certificats as $etype) {
                $event['etype'] = $etype;
                $this->event_model->replace($event);
            }
    }

    /**
     * Hook activé avant la destruction
     *
     * @param $id clé
     *            de l'élément à détruire
     */
    function pre_delete($id) {
        gvv_debug($this->controller . " overwritten delete $id");
        $this->event_model->delete(array(
            'evaid' => $id
        ));
    }

    /**
     * Hook activé après la mise à jour
     *
     * @param $data enregistrement
     *            modifié
     */
    function post_update($data = array()) {
        gvv_debug($this->controller . " overwritten post modification " . var_export($data, true));
        $this->post_create($data);
    }

    /**
     * Hook activé avant la mise à jour
     */
    function pre_update($id, &$data = array()) {
        gvv_debug($this->controller . " overwritten pre modification $id " . var_export($data, true));
        $this->event_model->delete(array(
            'evaid' => $data[$id]
        ));
    }

    /**
     * Affiche la page de cumuls annuel
     */
    public function cumuls() {
        $year = date("Y");
        $first_flight = $this->gvv_model->latest_flight(array(), "asc");
        if (count($first_flight) < 1) {
            $data['title'] = $this->lang->line("gvv_error");
            $data['text'] = $this->lang->line("gvv_no_flights");
            return load_last_view('message', $data);
        }
        $first_year = $first_flight[0]['year'];

        $data = array();
        $data['controller'] = $this->controller;
        $data['jsonurl'] = site_url($this->controller . '/ajax_cumuls');

        $data['year'] = $year;
        $data['first_year'] = $first_year;
        $data['title_key'] = "gvv_vols_avion_title_cumul";

        return load_last_view('vols_planeur/cumuls', $data);
    }

    /**
     * Retourne les informations pour le cumul
     */
    function ajax_cumuls() {
        $year = date("Y");
        $first_flight = $this->gvv_model->latest_flight(array(), "asc");
        $first_year = $first_flight[0]['year'];
        $json = $this->gvv_model->cumul_heures($year, $first_year);

        echo $json;
    }

}
