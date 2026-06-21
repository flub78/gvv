<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 133: Add reservation_reminders_enabled flag to sections
 *
 * Allows enabling/disabling email/SMS reminders per section independently
 * of the global gestion_reservations flag.
 */
class Migration_Sections_reservation_reminders_enabled extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('reservation_reminders_enabled', 'sections')) {
            $this->dbforge->add_column('sections', array(
                'reservation_reminders_enabled' => array(
                    'type'    => 'TINYINT',
                    'constraint' => 1,
                    'null'    => FALSE,
                    'default' => 0,
                    'after'   => 'show_on_member_card'
                )
            ));
        }
    }

    public function down()
    {
        if ($this->db->field_exists('reservation_reminders_enabled', 'sections')) {
            $this->dbforge->drop_column('sections', 'reservation_reminders_enabled');
        }
    }
}
