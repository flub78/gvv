<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Fournisseur des alarmes documentaires
 *
 * Traduit les documents archivés en occurrences d'alarme (AlarmItem) pour
 * les alarmes du catalogue de source « document » et « missing_document ».
 *
 * AlarmItem (tableau) : definition_id, definition_code, kind, key,
 * entity_type (pilot|section|machine|club), entity_id, pilot_login,
 * section_ids (vide = sans section), label, detail, state (upcoming|active),
 * due_date, link.
 *
 * Règles :
 * - documents courants, approuvés, à alerte non désactivée, avec date d'expiration ;
 * - « à venir » dans le délai d'anticipation de l'alarme, « actif » une fois expiré ;
 * - documents de pilote : seulement les membres actifs ; sections = celles où
 *   le pilote a le rôle user (ou le rôle ciblé par l'alarme) ;
 * - documents manquants : types obligatoires (scope pilot) d'une section où
 *   le pilote est actif, ou de toutes sections ; un type obligatoire pour un
 *   rôle ne s'applique qu'aux pilotes ayant ce rôle.
 *
 * Les documents manquants sont calculés en une passe pour tous les membres
 * (même règle que Archived_documents_model::get_missing_documents()).
 *
 * @package libraries
 * @see doc/design_notes/gestion_alarmes_design.md
 */
class Document_alarm_provider {

    /** @var CI_Controller */
    private $CI;

    public function __construct() {
        $this->CI = &get_instance();
        $this->CI->load->model('alarm_definitions_model');
        $this->CI->load->model('alarm_subscriptions_model');
        $this->CI->lang->load('alarmes');
    }

    /**
     * Occurrences des alarmes documentaires
     *
     * @param array $definitions Lignes du catalogue (toutes sources acceptées, seules
     *                           les sources document et missing_document sont traitées)
     * @return array AlarmItem[]
     */
    public function collect($definitions) {
        $doc_defs = array();
        $missing_defs = array();
        foreach ($definitions as $def) {
            if ($def['source'] === Alarm_definitions_model::SOURCE_DOCUMENT) {
                $doc_defs[] = $def;
            } elseif ($def['source'] === Alarm_definitions_model::SOURCE_MISSING_DOCUMENT) {
                $missing_defs[] = $def;
            }
        }

        $items = array();
        if ($doc_defs) {
            $items = array_merge($items, $this->collect_documents($doc_defs));
        }
        if ($missing_defs) {
            $items = array_merge($items, $this->collect_missing($missing_defs));
        }
        return $items;
    }

    // ------------------------------------------------------------------
    // Documents qui expirent
    // ------------------------------------------------------------------

    private function collect_documents($defs) {
        $type_ids = array();
        $max_lead = 0;
        foreach ($defs as $def) {
            $type_ids[(int) $def['document_type_id']] = true;
            $max_lead = max($max_lead, (int) $def['lead_days']);
        }

        $docs = $this->CI->db->query(
            "SELECT ad.id, ad.document_type_id, ad.pilot_login, ad.section_id, ad.machine_immat,
                    ad.valid_until, ad.description, dt.name AS type_name, dt.scope,
                    dt.section_id AS type_section_id, m.mnom, m.mprenom
             FROM archived_documents ad
             JOIN document_types dt ON dt.id = ad.document_type_id
             LEFT JOIN membres m ON m.mlogin = ad.pilot_login
             WHERE ad.is_current_version = 1
               AND ad.alarm_disabled = 0
               AND ad.validation_status = 'approved'
               AND ad.valid_until IS NOT NULL
               AND ad.valid_until <= DATE_ADD(CURDATE(), INTERVAL " . (int) $max_lead . " DAY)
               AND ad.document_type_id IN (" . implode(',', array_keys($type_ids)) . ")
             ORDER BY ad.valid_until ASC"
        )->result_array();

        $today = date('Y-m-d');
        $items = array();
        foreach ($defs as $def) {
            $limit = date('Y-m-d', strtotime('+' . (int) $def['lead_days'] . ' days'));
            foreach ($docs as $doc) {
                if ((int) $doc['document_type_id'] !== (int) $def['document_type_id']) {
                    continue;
                }
                if ($doc['valid_until'] > $limit) {
                    continue;
                }
                $sections = $this->document_sections($doc, $def);
                if ($sections === null) {
                    continue; // pilote inactif ou hors population ciblée
                }
                if (!empty($def['section_id']) && !in_array((int) $def['section_id'], $sections, true)) {
                    continue;
                }
                $items[] = $this->document_item($def, $doc, $sections,
                    ($doc['valid_until'] < $today) ? 'active' : 'upcoming');
            }
        }
        return $items;
    }

    /**
     * Sections d'un document, ou null s'il ne doit pas produire d'alarme
     */
    private function document_sections($doc, $def) {
        if ($doc['scope'] === 'pilot' && !empty($doc['pilot_login'])) {
            $roles = $this->CI->alarm_subscriptions_model->member_roles($doc['pilot_login']);
            if (empty($roles['user'])) {
                return null;
            }
            $role = !empty($def['target_role_id']) ? $def['target_role_name'] : 'user';
            if (empty($roles[$role])) {
                return null;
            }
            return array_values(array_filter(array_map('intval', array_keys($roles[$role]))));
        }
        $section = !empty($doc['section_id']) ? $doc['section_id'] : $doc['type_section_id'];
        return $section ? array((int) $section) : array();
    }

    private function document_item($def, $doc, $sections, $state) {
        if ($doc['scope'] === 'pilot' && !empty($doc['pilot_login'])) {
            $entity_type = 'pilot';
            $entity_id = $doc['pilot_login'];
            $who = trim($doc['mprenom'] . ' ' . $doc['mnom']);
        } elseif ($doc['scope'] === 'machine' || !empty($doc['machine_immat'])) {
            $entity_type = 'machine';
            $entity_id = $doc['machine_immat'];
            $who = $doc['machine_immat'];
        } elseif ($doc['scope'] === 'section') {
            $entity_type = 'section';
            $entity_id = (string) $doc['section_id'];
            $who = '';
        } else {
            $entity_type = 'club';
            $entity_id = '';
            $who = '';
        }
        return array(
            'definition_id' => (int) $def['id'],
            'definition_code' => $def['code'],
            'kind' => $def['kind'],
            'key' => 'document:' . $doc['id'],
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'pilot_login' => ($entity_type === 'pilot') ? $doc['pilot_login'] : null,
            'section_ids' => $sections,
            'label' => $doc['type_name'],
            'detail' => trim($who . (($who && $doc['description']) ? ' — ' : '') . $doc['description']),
            'state' => $state,
            'due_date' => $doc['valid_until'],
            'link' => 'archived_documents/view/' . $doc['id'],
        );
    }

    // ------------------------------------------------------------------
    // Documents obligatoires manquants
    // ------------------------------------------------------------------

    private function collect_missing($defs) {
        $types = $this->CI->db->query(
            "SELECT dt.*, tr.nom AS required_role_name FROM document_types dt
             LEFT JOIN types_roles tr ON tr.id = dt.required_role_id
             WHERE dt.active = 1 AND dt.scope = 'pilot' AND dt.required = 1"
        )->result_array();
        if (!$types) {
            return array();
        }

        // Types couverts par un document courant, approuvé et non expiré
        $covered = array();
        $rows = $this->CI->db->query(
            "SELECT pilot_login, document_type_id FROM archived_documents
             WHERE is_current_version = 1 AND validation_status = 'approved'
               AND pilot_login IS NOT NULL
               AND (valid_until IS NULL OR valid_until >= CURDATE())"
        )->result_array();
        foreach ($rows as $row) {
            $covered[$row['pilot_login']][(int) $row['document_type_id']] = true;
        }

        $names = array();
        foreach ($this->CI->db->select('mlogin, mnom, mprenom')->get('membres')->result_array() as $m) {
            $names[$m['mlogin']] = trim($m['mprenom'] . ' ' . $m['mnom']);
        }

        $items = array();
        foreach ($this->CI->alarm_subscriptions_model->active_logins() as $login) {
            $roles = $this->CI->alarm_subscriptions_model->member_roles($login);
            $user_sections = array_values(array_filter(array_map('intval', array_keys($roles['user']))));
            foreach ($types as $type) {
                if (isset($covered[$login][(int) $type['id']])) {
                    continue;
                }
                if (!empty($type['section_id']) && !in_array((int) $type['section_id'], $user_sections, true)) {
                    continue;
                }
                if (!empty($type['required_role_name'])) {
                    if (empty($roles[$type['required_role_name']])) {
                        continue;
                    }
                    if (!empty($type['section_id']) && !isset($roles[$type['required_role_name']][(int) $type['section_id']])) {
                        continue;
                    }
                }
                $sections = !empty($type['section_id']) ? array((int) $type['section_id']) : $user_sections;
                foreach ($defs as $def) {
                    if (!empty($def['section_id']) && !in_array((int) $def['section_id'], $sections, true)) {
                        continue;
                    }
                    $personal = ($def['kind'] === Alarm_definitions_model::KIND_PERSONAL);
                    $items[] = array(
                        'definition_id' => (int) $def['id'],
                        'definition_code' => $def['code'],
                        'kind' => $def['kind'],
                        'key' => 'missing:' . $type['id'] . ':' . $login,
                        'entity_type' => 'pilot',
                        'entity_id' => $login,
                        'pilot_login' => $login,
                        'section_ids' => $sections,
                        'label' => sprintf($this->CI->lang->line('alarm_item_missing'), $type['name']),
                        'detail' => isset($names[$login]) ? $names[$login] : $login,
                        'state' => 'active',
                        'due_date' => null,
                        'link' => $personal ? 'archived_documents/my_documents'
                                            : 'archived_documents/pilot_documents/' . rawurlencode($login),
                    );
                }
            }
        }
        return $items;
    }
}
