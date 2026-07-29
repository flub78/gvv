<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Migration 145: Relax the remaining MOTD "acting user" foreign keys to membres
 *
 * motd_user_message_state.user_login and motd_user_prefs.user_login identify
 * whoever is interacting with their own dashboard (hide/acknowledge a
 * message, sort/collapse preference) - any logged-in user, not necessarily
 * a club member (e.g. the legacy testadmin-style accounts with no membres
 * row). Same rationale as migration 144: these become plain audit-trail
 * VARCHAR values with no FK. motd_messages.target_user_login (an actual
 * message recipient) keeps its FK to membres, since that one really must
 * resolve to a member.
 *
 * @see application/migrations/144_motd_relax_actor_fk.php
 */
class Migration_Motd_relax_actor_user_login_fk extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 145;
    }

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
        $this->run_queries(array(
            "ALTER TABLE `motd_user_message_state` DROP FOREIGN KEY `fk_motd_ums_user`",
            "ALTER TABLE `motd_user_prefs` DROP FOREIGN KEY `fk_motd_user_prefs_user`",
        ));
    }

    public function down() {
        $this->run_queries(array(
            "ALTER TABLE `motd_user_message_state` ADD CONSTRAINT `fk_motd_ums_user` FOREIGN KEY (`user_login`) REFERENCES `membres` (`mlogin`) ON DELETE CASCADE ON UPDATE CASCADE",
            "ALTER TABLE `motd_user_prefs` ADD CONSTRAINT `fk_motd_user_prefs_user` FOREIGN KEY (`user_login`) REFERENCES `membres` (`mlogin`) ON DELETE CASCADE ON UPDATE CASCADE",
        ));
    }
}
