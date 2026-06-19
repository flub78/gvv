<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 130: Add autonomie_en_heures field to machinesa
 *
 * Optional field representing aircraft endurance in hours (1.0 to 8.0).
 * When set, prevents recording flights or training sessions exceeding
 * the aircraft's endurance.
 */
class Migration_Add_autonomie_en_heures_to_machinesa extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('autonomie_en_heures', 'machinesa')) {
            $this->dbforge->add_column('machinesa', array(
                'autonomie_en_heures' => array(
                    'type'       => 'DECIMAL',
                    'constraint' => '4,1',
                    'null'       => TRUE,
                    'default'    => NULL,
                    'after'      => 'fabrication'
                )
            ));
        }
    }

    public function down()
    {
        if ($this->db->field_exists('autonomie_en_heures', 'machinesa')) {
            $this->dbforge->drop_column('machinesa', 'autonomie_en_heures');
        }
    }
}
