<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Migration 144: Relax MOTD "who did this" foreign keys to membres
 *
 * created_by/updated_by (and motd_replies.author_login) can be a pure
 * users/DX_Auth admin account with no matching membres row (e.g. the
 * legacy testadmin-style accounts), which broke every admin action with
 * a foreign key violation. These columns become plain audit-trail
 * VARCHAR values with no FK, matching the convention already used by
 * reservation_reminder_log.created_by/updated_by. target_user_login
 * (an actual message recipient) keeps its FK to membres.
 *
 * @see application/migrations/143_create_motd_tables.php
 */
class Migration_Motd_relax_actor_fk extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 144;
    }

    private $drops = array(
        'motd_messages' => array('fk_motd_messages_created_by', 'fk_motd_messages_updated_by'),
        'motd_media' => array('fk_motd_media_created_by', 'fk_motd_media_updated_by'),
        'motd_replies' => array('fk_motd_replies_author', 'fk_motd_replies_created_by', 'fk_motd_replies_updated_by'),
        'motd_user_message_state' => array('fk_motd_ums_created_by', 'fk_motd_ums_updated_by'),
        'motd_user_prefs' => array('fk_motd_user_prefs_created_by', 'fk_motd_user_prefs_updated_by'),
    );

    private function run_queries($sqls = array()) {
        $errors = 0;
        foreach ($sqls as $sql) {
            gvv_info("Migration sql: " . $sql);
            if (!$this->db->query($sql)) {
                $mysql_msg = $this->db->_error_message();
                $mysql_error = $this->db->_error_number();
                gvv_error("Migration error: code=$mysql_error, msg=$mysql_msg");
                $errors += 1;
            }
        }
        return $errors;
    }

    public function up() {
        $sqls = array();
        foreach ($this->drops as $table => $constraints) {
            foreach ($constraints as $constraint) {
                $sqls[] = "ALTER TABLE `$table` DROP FOREIGN KEY `$constraint`";
            }
        }
        $this->run_queries($sqls);
    }

    public function down() {
        $sqls = array(
            "ALTER TABLE `motd_messages` ADD CONSTRAINT `fk_motd_messages_created_by` FOREIGN KEY (`created_by`) REFERENCES `membres` (`mlogin`) ON DELETE SET NULL ON UPDATE CASCADE",
            "ALTER TABLE `motd_messages` ADD CONSTRAINT `fk_motd_messages_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `membres` (`mlogin`) ON DELETE SET NULL ON UPDATE CASCADE",
            "ALTER TABLE `motd_media` ADD CONSTRAINT `fk_motd_media_created_by` FOREIGN KEY (`created_by`) REFERENCES `membres` (`mlogin`) ON DELETE SET NULL ON UPDATE CASCADE",
            "ALTER TABLE `motd_media` ADD CONSTRAINT `fk_motd_media_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `membres` (`mlogin`) ON DELETE SET NULL ON UPDATE CASCADE",
            "ALTER TABLE `motd_replies` ADD CONSTRAINT `fk_motd_replies_author` FOREIGN KEY (`author_login`) REFERENCES `membres` (`mlogin`) ON DELETE CASCADE ON UPDATE CASCADE",
            "ALTER TABLE `motd_replies` ADD CONSTRAINT `fk_motd_replies_created_by` FOREIGN KEY (`created_by`) REFERENCES `membres` (`mlogin`) ON DELETE SET NULL ON UPDATE CASCADE",
            "ALTER TABLE `motd_replies` ADD CONSTRAINT `fk_motd_replies_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `membres` (`mlogin`) ON DELETE SET NULL ON UPDATE CASCADE",
            "ALTER TABLE `motd_user_message_state` ADD CONSTRAINT `fk_motd_ums_created_by` FOREIGN KEY (`created_by`) REFERENCES `membres` (`mlogin`) ON DELETE SET NULL ON UPDATE CASCADE",
            "ALTER TABLE `motd_user_message_state` ADD CONSTRAINT `fk_motd_ums_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `membres` (`mlogin`) ON DELETE SET NULL ON UPDATE CASCADE",
            "ALTER TABLE `motd_user_prefs` ADD CONSTRAINT `fk_motd_user_prefs_created_by` FOREIGN KEY (`created_by`) REFERENCES `membres` (`mlogin`) ON DELETE SET NULL ON UPDATE CASCADE",
            "ALTER TABLE `motd_user_prefs` ADD CONSTRAINT `fk_motd_user_prefs_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `membres` (`mlogin`) ON DELETE SET NULL ON UPDATE CASCADE",
        );
        $this->run_queries($sqls);
    }
}
