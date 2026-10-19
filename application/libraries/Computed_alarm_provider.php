<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Fournisseur des alarmes calculées
 *
 * Traduit les règles de conformité en occurrences d'alarme (AlarmItem, cf.
 * Document_alarm_provider) pour les alarmes du catalogue de source
 * « computed ». Règles (alarm_definitions.rule) :
 *
 * Lot 2, sur les vols de l'année civile en cours, pilotes extérieurs exclus ;
 * occurrence toujours active, sans échéance, sections des vols concernés :
 * - vols_sans_cotisation : vols sans cotisation de l'année ;
 * - vols_sans_licence : vols dans une section sans la licence exigée par
 *   la section (sections.licence_type) pour l'année.
 *
 * Lot 3, par section : vols planeur (volsp) pour la section des vols planeur
 * (Licences_model::glider_section_id()), vols volsa de la section pour les
 * autres ; une occurrence par pilote et par section :
 * - emport_passager : pilote ayant le rôle emport_passager dans la section,
 *   moins de 3 atterrissages CDB (ou comme instructeur) en 90 jours ;
 *   échéance : 3e atterrissage le plus récent + 90 jours ;
 * - remorquages : pilote ayant le rôle pilote_rem dans une section qui gère
 *   les planeurs, moins de 10 remorqués en 12 mois (vols planeur dont il est
 *   le pilote remorqueur) ; échéance : 10e remorqué le plus récent + 12 mois ;
 * - inactivite : membre actif non élève de la section, dernier vol (pilote
 *   ou instructeur) il y a plus de 120 jours et moins de 24 mois ;
 *   échéance : dernier vol + 120 jours ;
 * - compte_debiteur : dette de la section supérieure au seuil d'alarme des
 *   relances (Relances_model::soldes_debiteurs_par_section(), même calcul
 *   que la page des relances).
 * Une alarme à échéance est « à venir » dans son délai d'anticipation,
 * « active » une fois l'échéance passée.
 *
 * @package libraries
 * @see application/models/licences_model.php
 * @see doc/design_notes/gestion_alarmes_design.md
 */
class Computed_alarm_provider {

    const RULE_VOLS_SANS_COTISATION = 'vols_sans_cotisation';
    const RULE_VOLS_SANS_LICENCE = 'vols_sans_licence';
    const RULE_EMPORT_PASSAGER = 'emport_passager';
    const RULE_INACTIVITE = 'inactivite';
    const RULE_COMPTE_DEBITEUR = 'compte_debiteur';
    const RULE_REMORQUAGES = 'remorquages';

    const PASSENGER_LANDINGS = 3;
    const PASSENGER_DAYS = 90;
    const INACTIVITY_DAYS = 120;
    const TOWS = 10;
    const TOW_MONTHS = 12;
    const INACTIVITY_WINDOW_MONTHS = 24;

    /** @var CI_Controller */
    private $CI;

    /** @var int Année contrôlée, modifiable pour les tests */
    private $year;

    /** @var string Date de référence (Y-m-d), modifiable pour les tests */
    private $today;

    /** @var array|null section_id => ['nom'] */
    private $sections = null;

    /** @var array|null login => nom affiché */
    private $names = null;

    public function __construct() {
        $this->CI = &get_instance();
        $this->CI->load->model('alarm_definitions_model');
        $this->CI->load->model('licences_model');
        $this->CI->load->model('alarm_subscriptions_model');
        $this->CI->lang->load('alarmes');
        $this->year = (int) date('Y');
        $this->today = date('Y-m-d');
    }

    /**
     * Fixe la date de référence (tests)
     *
     * @param string $date Y-m-d
     */
    public function set_today($date) {
        $this->today = $date;
    }

    /**
     * Revient à la date et à l'année courantes (fin de test)
     */
    public function reset_reference() {
        $this->today = date('Y-m-d');
        $this->year = (int) date('Y');
    }

    /**
     * La règle a-t-elle une échéance (délai d'anticipation réglable) ?
     *
     * @param string $rule
     * @return bool
     */
    public static function has_deadline($rule) {
        return in_array($rule, array(self::RULE_EMPORT_PASSAGER, self::RULE_INACTIVITE, self::RULE_REMORQUAGES), true);
    }

    /**
     * Fixe l'année contrôlée (tests)
     *
     * @param int $year
     */
    public function set_year($year) {
        $this->year = (int) $year;
    }

    /**
     * Occurrences des alarmes calculées
     *
     * @param array $definitions Lignes du catalogue (seule la source computed est traitée)
     * @return array AlarmItem[]
     */
    public function collect($definitions) {
        $items = array();
        foreach ($definitions as $def) {
            if ($def['source'] !== Alarm_definitions_model::SOURCE_COMPUTED) {
                continue;
            }
            if ($def['rule'] === self::RULE_VOLS_SANS_COTISATION) {
                $items = array_merge($items, $this->vols_sans_cotisation($def));
            } elseif ($def['rule'] === self::RULE_VOLS_SANS_LICENCE) {
                $items = array_merge($items, $this->vols_sans_licence($def));
            } elseif ($def['rule'] === self::RULE_EMPORT_PASSAGER) {
                $items = array_merge($items, $this->emport_passager($def));
            } elseif ($def['rule'] === self::RULE_INACTIVITE) {
                $items = array_merge($items, $this->inactivite($def));
            } elseif ($def['rule'] === self::RULE_REMORQUAGES) {
                $items = array_merge($items, $this->remorquages($def));
            } elseif ($def['rule'] === self::RULE_COMPTE_DEBITEUR) {
                $items = array_merge($items, $this->compte_debiteur($def));
            }
        }
        return $items;
    }

    private function vols_sans_cotisation($def) {
        $items = array();
        foreach ($this->CI->licences_model->pilotes_vols_sans_cotisation($this->year) as $row) {
            $vols = (int) $row['planeur'] + (int) $row['avion'] + (int) $row['ulm'];
            $items[] = $this->item($def, $row, 'computed:' . self::RULE_VOLS_SANS_COTISATION . ':' . $row['pilote'] . ':' . $this->year,
                sprintf($this->CI->lang->line('alarm_item_vols_sans_cotisation'), $this->year), $vols,
                'licences/vols_sans_cotisation/' . $this->year, 'paiements_en_ligne/cotisation');
        }
        return $items;
    }

    private function vols_sans_licence($def) {
        $items = array();
        foreach ($this->CI->licences_model->pilotes_vols_sans_licence($this->year) as $row) {
            $items[] = $this->item($def,
                $row, 'computed:' . self::RULE_VOLS_SANS_LICENCE . ':' . $row['pilote'] . ':' . $row['section_id'] . ':' . $this->year,
                sprintf($this->CI->lang->line('alarm_item_vols_sans_licence'), $row['section_nom'], $this->year), (int) $row['vols'],
                'licences/per_year', ''); // la licence s'obtient hors de GVV : pas de lien pour le pilote
        }
        return $items;
    }

    // ------------------------------------------------------------------
    // Lot 3
    // ------------------------------------------------------------------

    private function emport_passager($def) {
        $items = array();
        foreach ($this->role_holders('emport_passager', false) as $section_id => $logins) {
            $landings = $this->cdb_landings($section_id, $logins, $this->history_start());
            foreach ($logins as $login) {
                $items = array_merge($items, $this->window_alarm($def, self::RULE_EMPORT_PASSAGER, $login, $section_id,
                    isset($landings[$login]) ? $landings[$login] : array(), self::PASSENGER_LANDINGS,
                    '+' . self::PASSENGER_DAYS . ' days', 'alarm_item_emport_passager', 'alarm_item_emport_passager_detail',
                    $this->flights_page($section_id)));
            }
        }
        return $items;
    }

    private function remorquages($def) {
        $items = array();
        // Les remorqués sont enregistrés sur les vols planeur
        foreach ($this->role_holders('pilote_rem', true) as $section_id => $logins) {
            $tows = $this->tow_dates($logins, $this->history_start());
            foreach ($logins as $login) {
                $items = array_merge($items, $this->window_alarm($def, self::RULE_REMORQUAGES, $login, $section_id,
                    isset($tows[$login]) ? $tows[$login] : array(), self::TOWS,
                    '+' . self::TOW_MONTHS . ' months', 'alarm_item_remorquages', 'alarm_item_remorquages_detail',
                    'vols_planeur/page'));
            }
        }
        return $items;
    }

    /**
     * Titulaires non révoqués d'un rôle, par section
     *
     * @param string $role
     * @param bool $glider_only Seulement dans la section des vols planeur
     * @return array section_id => [login, ...]
     */
    private function role_holders($role, $glider_only) {
        $holders = array();
        foreach ($this->CI->alarm_subscriptions_model->all_member_roles() as $login => $roles) {
            if (empty($roles[$role])) {
                continue;
            }
            foreach (array_keys($roles[$role]) as $section_id) {
                if (isset($this->sections()[(int) $section_id]) && (!$glider_only || $this->is_glider_section($section_id))) {
                    $holders[(int) $section_id][] = $login;
                }
            }
        }
        return $holders;
    }

    /**
     * Alarme d'expérience « au moins N événements dans une fenêtre glissante » :
     * échéance = N-ième événement le plus récent + fenêtre ; active sans N
     * événements ou après l'échéance, à venir dans le délai d'anticipation
     *
     * @param array $dates Dates des événements, les plus récentes d'abord
     * @param int $required N
     * @param string $window Fenêtre, ex. '+90 days', '+12 months'
     * @return array 0 ou 1 occurrence
     */
    private function window_alarm($def, $rule, $login, $section_id, $dates, $required, $window,
                                  $label_key, $detail_key, $link) {
        $nth = (count($dates) >= $required) ? $dates[$required - 1] : null;
        $due = $nth ? date('Y-m-d', strtotime($nth . ' ' . $window)) : null;
        $state = $this->deadline_state($due, $def);
        if (!$state) {
            return array();
        }
        $window_start = date('Y-m-d', strtotime($this->today . ' ' . str_replace('+', '-', $window)));
        $recent = count(array_filter($dates, function ($d) use ($window_start) { return $d >= $window_start; }));
        return array($this->section_item($def, $rule, $login, $section_id,
            sprintf($this->CI->lang->line($label_key), $this->section_name($section_id)),
            sprintf($this->CI->lang->line($detail_key), $this->member_name($login), $recent),
            $state, $due, $link, $link));
    }

    /** Début de l'historique lu pour les alarmes d'expérience (24 mois) */
    private function history_start() {
        return date('Y-m-d', strtotime($this->today . ' -' . self::INACTIVITY_WINDOW_MONTHS . ' months'));
    }

    private function inactivite($def) {
        $roles_by_login = $this->CI->alarm_subscriptions_model->all_member_roles();
        $population = array();
        foreach ($roles_by_login as $login => $roles) {
            if (empty($roles['user'])) {
                continue;
            }
            foreach (array_keys($roles['user']) as $section_id) {
                if (isset($this->sections()[(int) $section_id]) && !isset($roles['eleve'][(int) $section_id])) {
                    $population[(int) $section_id][] = $login;
                }
            }
        }
        $items = array();
        foreach ($population as $section_id => $logins) {
            foreach ($this->last_flights($section_id, $logins, $this->history_start()) as $login => $last) {
                $due = date('Y-m-d', strtotime($last . ' +' . self::INACTIVITY_DAYS . ' days'));
                $state = $this->deadline_state($due, $def);
                if (!$state) {
                    continue;
                }
                $items[] = $this->section_item($def, self::RULE_INACTIVITE, $login, $section_id,
                    sprintf($this->CI->lang->line('alarm_item_inactivite'), $this->section_name($section_id)),
                    sprintf($this->CI->lang->line('alarm_item_inactivite_detail'), $this->member_name($login),
                        date('d/m/Y', strtotime($last))),
                    $state, $due, $this->flights_page($section_id), $this->flights_page($section_id));
            }
        }
        return $items;
    }

    private function compte_debiteur($def) {
        $this->CI->load->model('relances_model');
        $this->CI->load->model('configuration_model');
        $threshold = $this->CI->configuration_model->get_param('relances.seuil_alarme');
        $threshold = ($threshold === null || $threshold === '') ? Relances_model::DEFAULT_SEUIL_ALARME : (float) $threshold;

        $items = array();
        foreach ($this->CI->relances_model->soldes_debiteurs_par_section($threshold) as $row) {
            $item = $this->section_item($def, self::RULE_COMPTE_DEBITEUR, $row['mlogin'], (int) $row['section_id'],
                sprintf($this->CI->lang->line('alarm_item_compte_debiteur'), $row['section_nom']),
                sprintf($this->CI->lang->line('alarm_item_compte_debiteur_detail'),
                    trim($row['mprenom'] . ' ' . $row['mnom']), number_format((float) $row['solde'], 2, ',', ' ')),
                'active', null, 'relances', 'compta/mon_compte/' . (int) $row['section_id']);
            $item['any_pilot'] = true; // un pilote parti ou inactif doit encore être relancé (cf. Alarm_aggregator)
            $items[] = $item;
        }
        return $items;
    }

    /**
     * État d'une alarme à échéance : active si l'échéance est passée (ou
     * inconnue), à venir dans le délai d'anticipation, null sinon
     */
    private function deadline_state($due, $def) {
        if ($due === null || $due < $this->today) {
            return 'active';
        }
        $limit = date('Y-m-d', strtotime($this->today . ' +' . (int) $def['lead_days'] . ' days'));
        return ($due <= $limit) ? 'upcoming' : null;
    }

    /**
     * Dates des atterrissages CDB (pilote sans double commande, ou
     * instructeur), les plus récentes d'abord, depuis une date
     *
     * @return array login => [Y-m-d, ...] (une date par atterrissage)
     */
    private function cdb_landings($section_id, $logins, $since) {
        $in = implode(',', array_map(array($this->CI->db, 'escape'), $logins));
        $since = $this->CI->db->escape($since);
        if ($this->is_glider_section($section_id)) {
            $sql = "SELECT IF(vpdc = 1, vpinst, vppilid) AS login, vpdate AS d, 1 AS n
                    FROM volsp WHERE vpdate >= $since AND IF(vpdc = 1, vpinst, vppilid) IN ($in)";
        } else {
            $sql = "SELECT IF(vadc = 1, vainst, vapilid) AS login, vadate AS d, GREATEST(COALESCE(vaatt, 1), 1) AS n
                    FROM volsa WHERE club = " . (int) $section_id . " AND vadate >= $since
                      AND IF(vadc = 1, vainst, vapilid) IN ($in)";
        }
        $result = array();
        foreach ($this->CI->db->query($sql . ' ORDER BY d DESC')->result_array() as $row) {
            for ($i = 0; $i < (int) $row['n']; $i++) {
                $result[$row['login']][] = $row['d'];
            }
        }
        return $result;
    }

    /**
     * Dates des remorqués (vols planeur dont le membre est le pilote
     * remorqueur), les plus récentes d'abord, depuis une date
     *
     * @return array login => [Y-m-d, ...]
     */
    private function tow_dates($logins, $since) {
        $in = implode(',', array_map(array($this->CI->db, 'escape'), $logins));
        $rows = $this->CI->db->query(
            "SELECT pilote_remorqueur AS login, vpdate AS d FROM volsp
             WHERE vpdate >= " . $this->CI->db->escape($since) . " AND pilote_remorqueur IN ($in)
             ORDER BY vpdate DESC"
        )->result_array();
        $result = array();
        foreach ($rows as $row) {
            $result[$row['login']][] = $row['d'];
        }
        return $result;
    }

    /**
     * Date du dernier vol (pilote ou instructeur) depuis une date
     *
     * @return array login => Y-m-d
     */
    private function last_flights($section_id, $logins, $since) {
        $in = implode(',', array_map(array($this->CI->db, 'escape'), $logins));
        $since = $this->CI->db->escape($since);
        if ($this->is_glider_section($section_id)) {
            $sql = "SELECT login, MAX(d) AS last FROM (
                        SELECT vppilid AS login, vpdate AS d FROM volsp WHERE vpdate >= $since
                        UNION ALL
                        SELECT vpinst, vpdate FROM volsp WHERE vpdate >= $since AND vpinst IS NOT NULL AND vpinst <> ''
                    ) v WHERE login IN ($in) GROUP BY login";
        } else {
            $club = (int) $section_id;
            $sql = "SELECT login, MAX(d) AS last FROM (
                        SELECT vapilid AS login, vadate AS d FROM volsa WHERE club = $club AND vadate >= $since
                        UNION ALL
                        SELECT vainst, vadate FROM volsa WHERE club = $club AND vadate >= $since
                          AND vainst IS NOT NULL AND vainst <> ''
                    ) v WHERE login IN ($in) GROUP BY login";
        }
        $result = array();
        foreach ($this->CI->db->query($sql)->result_array() as $row) {
            $result[$row['login']] = $row['last'];
        }
        return $result;
    }

    /** Sections : id => ['nom'] */
    private function sections() {
        if ($this->sections === null) {
            $this->sections = array();
            foreach ($this->CI->db->select('id, nom')->get('sections')->result_array() as $row) {
                $this->sections[(int) $row['id']] = $row;
            }
        }
        return $this->sections;
    }

    /**
     * Les vols planeur (volsp, sans section) relèvent de la section des vols
     * planeur (Licences_model::glider_section_id(), règle commune avec les
     * contrôles de licence et de cotisation) ; ses autres vols (remorquages,
     * dans volsa) ne comptent pas pour l'expérience planeur
     */
    private function is_glider_section($section_id) {
        return (int) $section_id === (int) $this->CI->licences_model->glider_section_id();
    }

    private function section_name($section_id) {
        return $this->sections()[(int) $section_id]['nom'];
    }

    private function flights_page($section_id) {
        return $this->is_glider_section($section_id) ? 'vols_planeur/page' : 'vols_avion/page';
    }

    private function member_name($login) {
        if ($this->names === null) {
            $this->names = array();
            foreach ($this->CI->db->select('mlogin, mnom, mprenom')->get('membres')->result_array() as $row) {
                $this->names[$row['mlogin']] = trim($row['mprenom'] . ' ' . $row['mnom']);
            }
        }
        return !empty($this->names[$login]) ? $this->names[$login] : $login;
    }

    private function section_item($def, $rule, $login, $section_id, $label, $detail, $state, $due, $link, $link_own) {
        return array(
            'definition_id' => (int) $def['id'],
            'definition_code' => $def['code'],
            'key' => 'computed:' . $rule . ':' . $login . ':' . $section_id,
            'entity_type' => 'pilot',
            'entity_id' => $login,
            'pilot_login' => $login,
            'section_ids' => array((int) $section_id),
            'label' => $label,
            'detail' => $detail,
            'state' => $state,
            'due_date' => $due,
            'link' => $link,
            'link_own' => $link_own,
        );
    }

    private function item($def, $row, $key, $label, $vols, $link, $link_own) {
        $who = trim($row['prenom'] . ' ' . $row['nom']) ?: $row['pilote'];
        $sections = array();
        foreach (explode(',', (string) $row['sections']) as $section_id) {
            if ((int) $section_id > 0) {
                $sections[] = (int) $section_id;
            }
        }
        return array(
            'definition_id' => (int) $def['id'],
            'definition_code' => $def['code'],
            'key' => $key,
            'entity_type' => 'pilot',
            'entity_id' => $row['pilote'],
            'pilot_login' => $row['pilote'],
            'section_ids' => $sections,
            'label' => $label,
            'detail' => sprintf($this->CI->lang->line('alarm_item_vols_detail'), $who, $vols,
                date('d/m/Y', strtotime($row['dernier_vol']))),
            'state' => 'active',
            'due_date' => null,
            'link' => $link,
            'link_own' => $link_own,
        );
    }
}
