<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Migration 088 — Create briefing_tokens table
 *
 * Stores one-time tokens for digital passenger briefing signature (UC2).
 * Each token is linked to a VLD and can only be used once.
 */
class Migration_Briefing_tokens extends CI_Migration {

    public function up()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `briefing_tokens` (
                `id`         INT AUTO_INCREMENT PRIMARY KEY,
                `vld_id`     INT NOT NULL,
                `token`      VARCHAR(64) NOT NULL,
                `created_at` DATETIME NOT NULL,
                `expires_at` DATETIME NULL DEFAULT NULL,
                `used_at`    DATETIME NULL DEFAULT NULL,
                `ip_address` VARCHAR(45) NULL DEFAULT NULL,
                UNIQUE KEY `uk_briefing_tokens_token` (`token`),
                CONSTRAINT `fk_briefing_tokens_vld`
                    FOREIGN KEY (`vld_id`) REFERENCES `vols_decouverte`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        log_message('info', 'Migration 088: briefing_tokens table created');
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS `briefing_tokens`");
        log_message('info', 'Migration 088: briefing_tokens table dropped');
    }
}

/* End of file 088_briefing_tokens.php */
/* Location: ./application/migrations/088_briefing_tokens.php */
