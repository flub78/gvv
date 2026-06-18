<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 129: Add require_cotisation field to email_lists
 *
 * When enabled (default), members selected by criteria must also have
 * a subscription (licence type=0) for the current year to be included.
 * Existing lists keep the default value of 1 (backward compatible).
 */
class Migration_Add_require_cotisation_to_email_lists extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('require_cotisation', 'email_lists')) {
            $this->dbforge->add_column('email_lists', array(
                'require_cotisation' => array(
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'null'       => FALSE,
                    'default'    => 1,
                    'after'      => 'active_member'
                )
            ));
        }
    }

    public function down()
    {
        if ($this->db->field_exists('require_cotisation', 'email_lists')) {
            $this->dbforge->drop_column('email_lists', 'require_cotisation');
        }
    }
}
