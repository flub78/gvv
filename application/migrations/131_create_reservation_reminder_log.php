<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 131: Create reservation_reminder_log table
 *
 * Stores every activation and send attempt by the reminder mechanism.
 * The idempotency_key (unique constraint) prevents duplicate sends for
 * the same logical reminder (reservation + deadline + type triplet).
 */
class Migration_Create_reservation_reminder_log extends CI_Migration
{
    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `reservation_reminder_log` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `idempotency_key` VARCHAR(255) NOT NULL,
            `reservation_id` BIGINT(20) UNSIGNED NOT NULL,
            `trigger_source` ENUM('event_create','event_update','event_cancel','cron','public_url') NOT NULL,
            `action_type` ENUM('scheduled_reminder','event_notification') NOT NULL,
            `notification_type` VARCHAR(50) DEFAULT NULL,
            `recipients` TEXT DEFAULT NULL,
            `channel` ENUM('email','sms','email+sms') DEFAULT NULL,
            `provider` VARCHAR(50) DEFAULT NULL,
            `sent_at` DATETIME DEFAULT NULL,
            `status` ENUM('success','failure','skipped') NOT NULL DEFAULT 'skipped',
            `message_body` TEXT DEFAULT NULL,
            `error_message` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `created_by` VARCHAR(255) DEFAULT NULL,
            `updated_by` VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_idempotency_key` (`idempotency_key`),
            KEY `idx_reservation_id` (`reservation_id`),
            KEY `idx_status` (`status`),
            KEY `idx_sent_at` (`sent_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";

        $this->db->query($sql);
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS `reservation_reminder_log`");
    }
}
