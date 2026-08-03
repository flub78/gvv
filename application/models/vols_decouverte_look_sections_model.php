<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Association section (club) -> look de bon de vol de découverte.
 *
 * Une section n'a jamais plus d'une ligne (contrainte UNIQUE sur
 * `section_id`, migration 153) : "assigner un look" est donc un upsert, pas
 * un insert brut.
 *
 * @see doc/plans/configuration_bons_vols_decouverte_plan.md
 */

$CI = &get_instance();
$CI->load->model('common_model');
class Vols_decouverte_look_sections_model extends Common_Model {
    public $table = 'vols_decouverte_look_sections';
    protected $primary_key = 'id';

    /**
     * Retourne le look_id associé à une section, ou null si aucune
     * association n'existe (la section utilise alors le look par défaut).
     */
    public function get_look_id_for_section($section_id) {
        $row = $this->get_first(array('section_id' => $section_id));
        return !empty($row['look_id']) ? (int) $row['look_id'] : null;
    }

    /**
     * Associe un look à une section (crée ou remplace l'association
     * existante).
     */
    public function assign($section_id, $look_id) {
        $existing = $this->get_first(array('section_id' => $section_id));
        if (!empty($existing)) {
            return $this->update('id', array('id' => $existing['id'], 'look_id' => $look_id));
        }
        return $this->create(array('section_id' => $section_id, 'look_id' => $look_id));
    }

    /**
     * Supprime l'association d'une section : elle revient au look par
     * défaut.
     */
    public function clear($section_id) {
        $this->delete(array('section_id' => $section_id));
    }
}

/* End of file */
