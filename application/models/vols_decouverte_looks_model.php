<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Configurations de mise en page ("looks") des bons de vol de découverte.
 *
 * Un look regroupe les fonds recto/verso et une mise en page JSON (même
 * structure que le layout des cartes de membre : variable_fields et
 * static_fields par face, plus un champ dédié qr_field puisque le QR code
 * n'est pas une valeur mais une image générée à la volée). Un look est
 * marqué `is_default` : c'est celui utilisé par les sections sans
 * association explicite (vols_decouverte_look_sections_model).
 *
 * @see doc/plans/configuration_bons_vols_decouverte_plan.md
 * @see doc/prds/configuration_bons_vols_decouverte_prd.md
 */

$CI = &get_instance();
$CI->load->model('common_model');
class Vols_decouverte_looks_model extends Common_Model {
    public $table = 'vols_decouverte_looks';
    protected $primary_key = 'id';

    /**
     * Layout par défaut embarqué, utilisé tant qu'aucun look n'est marqué
     * `is_default` en base. Ses positions sont indicatives ; leur fidélité
     * visuelle avec l'ancien mécanisme (`vols_decouverte::generate_pdf()`)
     * est garantie par le moteur de rendu du Lot 2, pas par ce modèle.
     */
    public function default_layout() {
        return array(
            'version' => 1,
            'recto' => array(
                'variable_fields' => array(),
                'static_fields' => array(),
                'qr_field' => array('enabled' => true, 'x' => 175, 'y' => 5, 'size' => 30),
            ),
            'verso' => array(
                'variable_fields' => array(
                    array('id' => 'numero', 'enabled' => true, 'x' => 5, 'y' => 5, 'font' => 'helvetica', 'bold' => true, 'size' => 10, 'color' => array(0, 0, 0), 'align' => 'L', 'width' => 60),
                    array('id' => 'beneficiaire', 'enabled' => true, 'x' => 5, 'y' => 15, 'font' => 'helvetica', 'bold' => false, 'size' => 10, 'color' => array(0, 0, 0), 'align' => 'L', 'width' => 120),
                    array('id' => 'occasion', 'enabled' => true, 'x' => 5, 'y' => 25, 'font' => 'helvetica', 'bold' => false, 'size' => 10, 'color' => array(0, 0, 0), 'align' => 'L', 'width' => 120),
                    array('id' => 'de_la_part', 'enabled' => true, 'x' => 5, 'y' => 35, 'font' => 'helvetica', 'bold' => false, 'size' => 10, 'color' => array(0, 0, 0), 'align' => 'L', 'width' => 120),
                    array('id' => 'date_validite', 'enabled' => true, 'x' => 5, 'y' => 45, 'font' => 'helvetica', 'bold' => false, 'size' => 10, 'color' => array(0, 0, 0), 'align' => 'L', 'width' => 120),
                    array('id' => 'type_vol', 'enabled' => true, 'x' => 5, 'y' => 60, 'font' => 'helvetica', 'bold' => true, 'size' => 14, 'color' => array(0, 0, 0), 'align' => 'C', 'width' => 190),
                ),
                'static_fields' => array(
                    array('text' => 'Pour prendre rendez-vous et organiser votre vol, contactez le club.', 'x' => 5, 'y' => 90, 'font' => 'helvetica', 'bold' => false, 'size' => 9, 'color' => array(0, 0, 0), 'align' => 'L', 'width' => 190),
                ),
                'qr_field' => null,
            ),
        );
    }

    /**
     * Retourne le look marqué `is_default`, ou un look virtuel non
     * persisté basé sur le layout par défaut embarqué si aucun n'existe
     * encore en base (même principe que `Cartes_membre_model::get_layout()`
     * pour une installation sans configuration personnalisée).
     */
    public function get_default_look() {
        $row = $this->get_first(array('is_default' => 1));
        if (!empty($row)) {
            return $row;
        }
        return array(
            'id' => null,
            'nom' => 'Défaut',
            'layout_json' => json_encode($this->default_layout()),
            'fond_recto_path' => null,
            'fond_verso_path' => null,
            'is_default' => 1,
        );
    }

    /**
     * Retourne le look applicable à une section : celui qui lui est
     * explicitement associé, sinon le look par défaut.
     */
    public function get_look_for_section($section_id) {
        $this->load->model('vols_decouverte_look_sections_model');
        $look_id = $this->vols_decouverte_look_sections_model->get_look_id_for_section($section_id);
        if (!empty($look_id)) {
            $look = $this->get_by_id('id', $look_id);
            if (!empty($look)) {
                return $look;
            }
        }
        return $this->get_default_look();
    }

    /**
     * Retourne le layout décodé d'un look (tableau PHP).
     */
    public function get_layout($look) {
        $decoded = json_decode($look['layout_json'], true);
        return is_array($decoded) ? $decoded : $this->default_layout();
    }

    /**
     * Crée ou met à jour un look. $layout est encodé en JSON avant stockage.
     */
    public function save_look($id, $nom, array $layout, $fond_recto_path = null, $fond_verso_path = null) {
        $data = array(
            'nom' => $nom,
            'layout_json' => json_encode($layout),
            'fond_recto_path' => $fond_recto_path,
            'fond_verso_path' => $fond_verso_path,
        );

        if (empty($id)) {
            return $this->create($data);
        }

        $data['id'] = $id;
        return $this->update('id', $data);
    }

    /**
     * Marque un look comme look par défaut ; retire ce statut à tous les
     * autres (un seul look par défaut à la fois).
     */
    public function set_default($id) {
        $this->db->set('is_default', 0)->update($this->table);
        return $this->update('id', array('id' => $id, 'is_default' => 1));
    }
}

/* End of file */
