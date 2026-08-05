<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Maintenance Programme Model
 *
 * Handles maintenance programs (programmes d'entretien), miroir de
 * Formation_programme_model. A program carries the butee rule (date
 * and/or flight hours) and references its source markdown document
 * (document_id, versioned via the existing document archive system).
 *
 * @see doc/prds/maintenance_aeronefs_prd.md (EF2)
 * @see doc/design_notes/maintenance_aeronefs_design.md
 */
class Maintenance_programme_model extends Common_Model {
    public $table = 'maintenance_programmes';
    protected $primary_key = 'id';

    function __construct() {
        parent::__construct();
    }

    /**
     * Get a program by its ID
     *
     * @param int $id Program ID
     * @return array Program data or empty array if not found
     */
    public function get($id) {
        return $this->get_by_id('id', $id);
    }

    /**
     * Get a program by its code
     *
     * @param string $code Program code
     * @return array Program data or empty array if not found
     */
    public function get_by_code($code) {
        $this->db->where('code', $code);
        $result = $this->db->get($this->table)->row_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result ?: array();
    }

    /**
     * Get all active programs
     *
     * @param bool $active_only If true, only return active programs
     * @return array List of programs
     */
    public function get_all($active_only = false) {
        $this->db->select('*')
            ->from($this->table)
            ->order_by('titre', 'asc');

        if ($active_only) {
            $this->db->where('statut', 'actif');
        }

        return $this->db->get()->result_array();
    }

    /**
     * Get all programs for a section (including "all sections" programs)
     *
     * Reproduit exactement Formation_programme_model::get_by_section() :
     * une section valide voit les programmes globaux (section_id IS NULL)
     * et ceux de sa section ; sinon (NULL/vide/"toutes"), on voit tout.
     *
     * @param int|null|string $section_id Section ID
     * @return array List of programs
     */
    public function get_by_section($section_id = null) {
        $section_exists = false;
        if ($section_id !== null && $section_id !== '') {
            $query = $this->db->where('id', $section_id)->get('sections');
            $section_exists = $query->num_rows() > 0;
        }

        $this->db->select('*');
        $this->db->from($this->table);

        if ($section_exists) {
            $this->db->where("(section_id IS NULL OR section_id = " . (int) $section_id . ")", null, false);
        }

        $this->db->where('statut', 'actif');
        $this->db->order_by('titre', 'asc');

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Get all active programs visible to the current user's section
     *
     * @return array List of visible programs
     */
    public function get_visibles() {
        return $this->get_by_section($this->section_id);
    }

    /**
     * Get all programs (any statut) for the admin/mecano list view, scoped
     * to a section like get_by_section() but including inactive/archived
     * programs, with the section name joined in for display.
     *
     * @param int|null|string $section_id Section ID
     * @return array List of programs
     */
    public function get_by_section_admin($section_id = null) {
        $section_exists = false;
        if ($section_id !== null && $section_id !== '') {
            $query = $this->db->where('id', $section_id)->get('sections');
            $section_exists = $query->num_rows() > 0;
        }

        $this->db->select('p.*, s.nom as section_nom')
            ->from($this->table . ' p')
            ->join('sections s', 'p.section_id = s.id', 'left');

        if ($section_exists) {
            $this->db->where("(p.section_id IS NULL OR p.section_id = " . (int) $section_id . ")", null, false);
        }

        $this->db->order_by('p.titre', 'asc');

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Synchronise la structure d'un programme (sections/taches) a partir
     * d'une nouvelle version de son markdown source (PRD EF2.5).
     *
     * Reconciliation par titre : une section/tache dont le titre est
     * retrouve dans la nouvelle version reutilise sa ligne existante
     * (ordre/description mis a jour, reactivee si necessaire) ; sinon
     * une nouvelle ligne est creee. Une section/tache existante absente
     * de la nouvelle version est supprimee si elle n'est referencee par
     * aucune maintenance_realisation, sinon desactivee (actif = 0) pour
     * que son historique reste consultable (Etape 4.2).
     *
     * @param int $programme_id Program ID
     * @param string $markdown_content New markdown content
     * @param int|null $document_id If provided, updates maintenance_programmes.document_id
     * @return bool Success
     * @throws Exception if parsing/validation fails
     */
    public function synchroniser_structure($programme_id, $markdown_content, $document_id = null) {
        $this->load->library('Maintenance_markdown_parser');
        $this->load->model('maintenance_programme_section_model');
        $this->load->model('maintenance_tache_model');

        $parsed = $this->maintenance_markdown_parser->parse($markdown_content);
        $validation = $this->maintenance_markdown_parser->validate($parsed);
        if ($validation !== true) {
            throw new Exception($validation);
        }

        $this->db->trans_start();

        // Lignes existantes (actives ET inactives) : pool de reconciliation
        $existing_sections = $this->maintenance_programme_section_model->get_by_programme($programme_id, false);
        $sections_by_titre = array();
        foreach ($existing_sections as $section) {
            $sections_by_titre[$section['titre']] = $section;
        }

        $existing_taches = $this->maintenance_tache_model->get_by_programme($programme_id, false);
        $taches_by_section = array();
        foreach ($existing_taches as $tache) {
            $taches_by_section[$tache['programme_section_id']][$tache['titre']] = $tache;
        }

        $seen_section_ids = array();
        $seen_tache_ids = array();

        foreach ($parsed['sections'] as $section_data) {
            if (isset($sections_by_titre[$section_data['titre']])) {
                $section_id = $sections_by_titre[$section_data['titre']]['id'];
                $this->maintenance_programme_section_model->update('id', array(
                    'ordre' => $section_data['ordre'],
                    'actif' => 1,
                ), $section_id);
            } else {
                $section_id = $this->maintenance_programme_section_model->create(array(
                    'programme_id' => $programme_id,
                    'ordre' => $section_data['ordre'],
                    'titre' => $section_data['titre'],
                ));
            }
            $seen_section_ids[] = $section_id;

            $existing_taches_of_section = isset($taches_by_section[$section_id]) ? $taches_by_section[$section_id] : array();

            foreach ($section_data['taches'] as $tache_data) {
                if (isset($existing_taches_of_section[$tache_data['titre']])) {
                    $tache_id = $existing_taches_of_section[$tache_data['titre']]['id'];
                    $this->maintenance_tache_model->update('id', array(
                        'ordre' => $tache_data['ordre'],
                        'description' => $tache_data['description'],
                        'actif' => 1,
                    ), $tache_id);
                } else {
                    $tache_id = $this->maintenance_tache_model->create(array(
                        'programme_section_id' => $section_id,
                        'ordre' => $tache_data['ordre'],
                        'titre' => $tache_data['titre'],
                        'description' => $tache_data['description'],
                    ));
                }
                $seen_tache_ids[] = $tache_id;
            }
        }

        // Taches obsoletes : supprimees si jamais utilisees, sinon desactivees
        foreach ($existing_taches as $tache) {
            if (in_array($tache['id'], $seen_tache_ids)) {
                continue;
            }
            if ($this->maintenance_tache_model->count_realisations($tache['id']) > 0) {
                $this->maintenance_tache_model->desactiver($tache['id']);
            } else {
                $this->db->where('id', $tache['id'])->delete('maintenance_taches');
            }
        }

        // Sections obsoletes : supprimees seulement si plus aucune tache n'y est rattachee
        foreach ($existing_sections as $section) {
            if (in_array($section['id'], $seen_section_ids)) {
                continue;
            }
            $remaining_taches = $this->db->where('programme_section_id', $section['id'])
                ->count_all_results('maintenance_taches');
            if ($remaining_taches > 0) {
                $this->maintenance_programme_section_model->desactiver($section['id']);
            } else {
                $this->db->where('id', $section['id'])->delete('maintenance_programme_sections');
            }
        }

        $updates = array('titre' => $parsed['titre']);
        if ($document_id !== null) {
            $updates['document_id'] = $document_id;
        }
        $this->update('id', $updates, $programme_id);

        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    /**
     * Check if a program code is unique
     *
     * @param string $code Code to check
     * @param int|null $exclude_id ID to exclude from check (for updates)
     * @return bool True if unique
     */
    public function is_code_unique($code, $exclude_id = null) {
        $this->db->where('code', $code);
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        return ($this->db->count_all_results($this->table) == 0);
    }

    /**
     * Archive a program (set status to 'inactif')
     *
     * @param int $id Program ID
     * @return bool Success
     */
    public function archiver($id) {
        return (bool) $this->update('id', array('statut' => 'inactif'), $id);
    }

    /**
     * Reactivate an archived program
     *
     * @param int $id Program ID
     * @return bool Success
     */
    public function reactiver($id) {
        return (bool) $this->update('id', array('statut' => 'actif'), $id);
    }

    /**
     * Get program image for display
     *
     * @param int $id Program ID
     * @return string "code - titre"
     */
    public function image($id) {
        $programme = $this->get($id);
        if ($programme) {
            return $programme['code'] . ' - ' . $programme['titre'];
        }
        return '';
    }
}

/* End of file maintenance_programme_model.php */
/* Location: ./application/models/maintenance_programme_model.php */
