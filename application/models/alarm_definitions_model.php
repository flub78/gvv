<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Catalogue des alarmes (table alarm_definitions)
 *
 * Une ligne par alarme proposée aux membres : personnelle ou de supervision,
 * sur un type de document à expiration ou sur les documents obligatoires
 * manquants. Le catalogue suit les types de documents (sync_document_types).
 *
 * @package models
 * @see application/migrations/177_alarmes_lot1.php
 * @see doc/design_notes/gestion_alarmes_design.md
 */
class Alarm_definitions_model extends Common_Model {
    public $table = 'alarm_definitions';
    protected $primary_key = 'id';

    const KIND_PERSONAL = 'personal';
    const KIND_SUPERVISION = 'supervision';

    const SOURCE_DOCUMENT = 'document';
    const SOURCE_MISSING_DOCUMENT = 'missing_document';
    const SOURCE_COMPUTED = 'computed';

    function __construct() {
        parent::__construct();
        $this->lang->load('alarmes');
    }

    /**
     * Alarmes du catalogue, avec le type de document et le rôle ciblé
     *
     * @param bool $active_only Seulement les alarmes actives
     * @return array
     */
    public function get_all($active_only = true) {
        $this->db->select('ad.*, dt.name as type_name, dt.code as type_code, dt.scope as type_scope,
            dt.is_private as type_private, tr.nom as target_role_name, tr.translation_key as target_role_key, s.nom as section_name');
        $this->db->from($this->table . ' ad');
        $this->db->join('document_types dt', 'ad.document_type_id = dt.id', 'left');
        $this->db->join('types_roles tr', 'ad.target_role_id = tr.id', 'left');
        $this->db->join('sections s', 'ad.section_id = s.id', 'left');
        if ($active_only) {
            $this->db->where('ad.active', 1);
        }
        $this->db->order_by('ad.kind', 'asc');
        $this->db->order_by('ad.source', 'asc');
        $this->db->order_by('ad.label', 'asc');
        $rows = $this->get_to_array($this->db->get());
        foreach ($rows as &$row) {
            $row['display_label'] = $this->display_label($row);
        }
        return $rows;
    }

    /**
     * Une alarme du catalogue par identifiant
     *
     * @param int $id
     * @return array|null
     */
    public function get($id) {
        foreach ($this->get_all(false) as $row) {
            if ((int) $row['id'] === (int) $id) {
                return $row;
            }
        }
        return null;
    }

    /**
     * Libellé traduit d'une alarme du catalogue
     *
     * @param array $def Ligne de get_all()
     * @return string
     */
    public function display_label($def) {
        if ($def['source'] === self::SOURCE_MISSING_DOCUMENT) {
            return $this->lang->line('alarm_label_missing_' . $def['kind']);
        }
        if ($def['source'] === self::SOURCE_DOCUMENT) {
            $name = !empty($def['type_name']) ? $def['type_name'] : $def['label'];
            if (!empty($def['target_role_id'])) {
                $role = ($def['target_role_key'] && $this->lang->line($def['target_role_key']))
                    ? $this->lang->line($def['target_role_key']) : $def['target_role_name'];
                return sprintf($this->lang->line('alarm_label_document_target'), $name, $role);
            }
            return sprintf($this->lang->line('alarm_label_document_' . $def['kind']), $name);
        }
        return $def['label'];
    }

    /**
     * Met le catalogue en accord avec les types de documents : crée les
     * alarmes des types actifs à expiration qui n'en ont pas, désactive
     * celles des types inactifs ou sans expiration. Idempotent.
     *
     * @return int Nombre d'alarmes créées ou modifiées
     */
    public function sync_document_types() {
        $changes = 0;
        $types = $this->db->get('document_types')->result_array();
        foreach ($types as $type) {
            $expiring = !empty($type['active']) && !empty($type['has_expiration']);
            $kinds = ($type['scope'] === 'pilot')
                ? array(self::KIND_PERSONAL, self::KIND_SUPERVISION)
                : array(self::KIND_SUPERVISION);

            foreach ($kinds as $kind) {
                $code = 'document_' . $type['id'] . '_' . $kind;
                $existing = $this->db->where('code', $code)->get($this->table)->row_array();
                if (!$existing) {
                    if ($expiring) {
                        $this->create(array(
                            'code' => $code,
                            'label' => $type['name'],
                            'kind' => $kind,
                            'source' => self::SOURCE_DOCUMENT,
                            'document_type_id' => $type['id'],
                            'section_id' => $type['section_id'],
                            'lead_days' => ($type['alert_days_before'] !== null) ? (int) $type['alert_days_before'] : 30,
                            'active' => 1,
                        ));
                        $changes++;
                    }
                } elseif ((int) $existing['active'] !== ($expiring ? 1 : 0) || $existing['label'] !== $type['name']) {
                    $this->update('id', array(
                        'id' => $existing['id'],
                        'active' => $expiring ? 1 : 0,
                        'label' => $type['name'],
                    ));
                    $changes++;
                }
            }
        }
        if ($changes) {
            gvv_info("ALARM catalogue synchronisé avec les types de documents ($changes changements)");
        }
        return $changes;
    }

    /**
     * Active ou désactive une alarme et règle son délai d'anticipation
     *
     * @param int $id
     * @param bool $active
     * @param int $lead_days
     * @return bool
     */
    public function configure($id, $active, $lead_days) {
        return $this->update('id', array(
            'id' => (int) $id,
            'active' => $active ? 1 : 0,
            'lead_days' => max(0, (int) $lead_days),
        ));
    }
}
