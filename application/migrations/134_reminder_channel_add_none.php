<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 134: Add 'none' to reminder_channel ENUM
 *
 * Allows users to opt out of all reservation reminder notifications.
 */
class Migration_Reminder_channel_add_none extends CI_Migration
{
    public function up()
    {
        $this->db->query(
            "ALTER TABLE membres MODIFY COLUMN reminder_channel
             ENUM('email','sms','email+sms','none') NOT NULL DEFAULT 'email'"
        );
    }

    public function down()
    {
        // Reassign 'none' users to 'email' before shrinking the ENUM
        $this->db->query("UPDATE membres SET reminder_channel = 'email' WHERE reminder_channel = 'none'");
        $this->db->query(
            "ALTER TABLE membres MODIFY COLUMN reminder_channel
             ENUM('email','sms','email+sms') NOT NULL DEFAULT 'email'"
        );
    }
}
