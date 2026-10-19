<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Catalogue des alarmes (table alarm_definitions)
 *
 * Une ligne par alarme proposée aux membres : une par type de document à
 * expiration, éventuellement restreinte à une population (target_role_id,
 * ex. les élèves), et une pour les documents obligatoires manquants. Un
 * abonné reçoit ses propres documents et ceux qu'il a le droit de
 * superviser. Le catalogue suit les types de documents (sync_document_types).
 *
 * @package models
 * @see application/migrations/177_alarmes_lot1.php
 * @see doc/design_notes/gestion_alarmes_design.md
 */
class Alarm_definitions_model extends Common_Model {
    public $table = 'alarm_definitions';
    protected $primary_key = 'id';

    const SOURCE_DOCUMENT = 'document';
    const SOURCE_MISSING_DOCUMENT = 'missing_document';
    const SOURCE_COMPUTED = 'computed';

    /** Catégories d'alarmes, dans leur ordre d'affichage */
    const CATEGORY_DOCUMENTS = 'documents';
    const CATEGORY_ADHESION = 'adhesion';
    const CATEGORY_COMPTES = 'comptes';
    const CATEGORY_EXPERIENCE = 'experience';

    /** Auteur des activations et désactivations faites par sync_document_types() */
    const SYNC_USER = 'alarm_sync';

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
        $this->db->order_by('ad.source', 'asc');
        $this->db->order_by('ad.label', 'asc');
        $rows = $this->get_to_array($this->db->get());
        foreach ($rows as &$row) {
            $row['display_label'] = $this->display_label($row);
            $row['category'] = self::category($row);
        }
        return $rows;
    }

    /**
     * Catégories, dans leur ordre d'affichage
     *
     * @return array
     */
    public static function categories() {
        return array(self::CATEGORY_DOCUMENTS, self::CATEGORY_ADHESION, self::CATEGORY_COMPTES, self::CATEGORY_EXPERIENCE);
    }

    /**
     * Catégorie d'une alarme : documents, cotisation et licence, comptes,
     * expérience récente
     *
     * @param array $def
     * @return string
     */
    public static function category($def) {
        if ($def['source'] !== self::SOURCE_COMPUTED) {
            return self::CATEGORY_DOCUMENTS;
        }
        switch ($def['rule']) {
            case 'compte_debiteur':
                return self::CATEGORY_COMPTES;
            case 'emport_passager':
            case 'inactivite':
            case 'remorquages':
                return self::CATEGORY_EXPERIENCE;
            default:
                return self::CATEGORY_ADHESION;
        }
    }

    /**
     * Trie des alarmes par catégorie, puis par libellé
     *
     * @param array $defs Lignes de get_all()
     * @return array
     */
    public static function sort_by_category($defs) {
        $order = array_flip(self::categories());
        usort($defs, function ($a, $b) use ($order) {
            $ca = $order[$a['category']];
            $cb = $order[$b['category']];
            return ($ca !== $cb) ? $ca - $cb : strcasecmp($a['display_label'], $b['display_label']);
        });
        return $defs;
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
            return $this->lang->line('alarm_label_missing');
        }
        if ($def['source'] === self::SOURCE_DOCUMENT) {
            $name = !empty($def['type_name']) ? $def['type_name'] : $def['label'];
            if (!empty($def['target_role_id'])) {
                $role = ($def['target_role_key'] && $this->lang->line($def['target_role_key']))
                    ? $this->lang->line($def['target_role_key']) : $def['target_role_name'];
                return sprintf($this->lang->line('alarm_label_document_target'), $name, $role);
            }
            return $name;
        }
        if ($def['source'] === self::SOURCE_COMPUTED && $def['rule']
            && $this->lang->line('alarm_label_rule_' . $def['rule'])) {
            return $this->lang->line('alarm_label_rule_' . $def['rule']);
        }
        return $def['label'];
    }

    /**
     * Met le catalogue en accord avec les types de documents. Idempotent.
     *
     * - Type actif, à expiration et à alarmes possibles : crée son alarme si
     *   elle n'existe pas ; réactive les alarmes du type que la
     *   synchronisation avait désactivées (y compris celles restreintes à
     *   une population, ex. les élèves) ; suit le nom du type.
     * - Autre type : désactive toutes ses alarmes, en les marquant
     *   (updated_by = SYNC_USER).
     *
     * Une alarme désactivée par un administrateur dans le catalogue n'est
     * jamais réactivée par la synchronisation.
     *
     * @return int Nombre d'alarmes créées ou modifiées
     */
    public function sync_document_types() {
        $changes = 0;
        foreach ($this->db->get('document_types')->result_array() as $type) {
            $eligible = !empty($type['active']) && !empty($type['has_expiration']) && !empty($type['alarm_enabled']);
            $code = 'document_' . $type['id'];
            $existing = $this->db->where('code', $code)->get($this->table)->row_array();

            if (!$eligible) {
                $this->db->where('document_type_id', $type['id'])->where('active', 1)
                    ->update($this->table, array('active' => 0, 'updated_by' => self::SYNC_USER,
                        'updated_at' => date('Y-m-d H:i:s')));
                $changes += $this->db->affected_rows();
                continue;
            }

            if (!$existing) {
                $this->create(array(
                    'code' => $code,
                    'label' => $type['name'],
                    'source' => self::SOURCE_DOCUMENT,
                    'document_type_id' => $type['id'],
                    'section_id' => $type['section_id'],
                    'lead_days' => ($type['alert_days_before'] !== null) ? (int) $type['alert_days_before'] : 30,
                    'active' => 1,
                ));
                $changes++;
            } elseif ($existing['label'] !== $type['name']) {
                $this->update('id', array('id' => $existing['id'], 'label' => $type['name']));
                $changes++;
            }

            $this->db->where('document_type_id', $type['id'])->where('active', 0)->where('updated_by', self::SYNC_USER)
                ->update($this->table, array('active' => 1, 'updated_by' => self::SYNC_USER,
                    'updated_at' => date('Y-m-d H:i:s')));
            $changes += $this->db->affected_rows();
        }
        if ($changes) {
            gvv_info("ALARM catalogue synchronisé avec les types de documents ($changes changements)");
        }
        return $changes;
    }

    /**
     * Active ou désactive une alarme et règle son délai d'anticipation.
     * updated_by ne vaut jamais SYNC_USER après ce réglage : la
     * synchronisation ne revient pas sur un choix de l'administrateur.
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
            'updated_by' => $this->current_username() ?: 'catalogue',
        ));
    }
}
