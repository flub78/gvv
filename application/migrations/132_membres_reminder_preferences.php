<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 132: Add reminder preferences to membres table
 *
 * Adds per-user notification channel and lead-time preferences used
 * by the reservation reminder mechanism at send-decision time.
 */
class Migration_Membres_reminder_preferences extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('reminder_channel', 'membres')) {
            $this->dbforge->add_column('membres', array(
                'reminder_channel' => array(
                    'type'       => 'ENUM',
                    'constraint' => "'email','sms','email+sms'",
                    'null'       => FALSE,
                    'default'    => 'email',
                    'after'      => 'updated_by'
                )
            ));
        }

        if (!$this->db->field_exists('reminder_period_hours', 'membres')) {
            $this->dbforge->add_column('membres', array(
                'reminder_period_hours' => array(
                    'type'       => 'SMALLINT',
                    'constraint' => 5,
                    'unsigned'   => TRUE,
                    'null'       => FALSE,
                    'default'    => 24,
                    'after'      => 'reminder_channel'
                )
            ));
        }
    }

    public function down()
    {
        if ($this->db->field_exists('reminder_period_hours', 'membres')) {
            $this->dbforge->drop_column('membres', 'reminder_period_hours');
        }
        if ($this->db->field_exists('reminder_channel', 'membres')) {
            $this->dbforge->drop_column('membres', 'reminder_channel');
        }
    }
}
