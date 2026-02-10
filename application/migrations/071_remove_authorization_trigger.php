<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 071: Remove trigger, use DEFAULT CURRENT_TIMESTAMP instead
 *
 * The trigger trg_use_new_authorization_created_at was created in migration 048
 * for compatibility with MySQL < 5.6.5. Since all current deployments support
 * DEFAULT CURRENT_TIMESTAMP on DATETIME columns, the trigger is unnecessary.
 *
 * Removing it also eliminates DELIMITER statements in mysqldump output,
 * which cause errors during restore via the GVV admin interface
 * (PHP database drivers do not support the DELIMITER command).
 */
class Migration_Remove_authorization_trigger extends CI_Migration
{
    public function up()
    {
        // Drop the trigger
        $this->db->query("DROP TRIGGER IF EXISTS trg_use_new_authorization_created_at");

        // Set DEFAULT CURRENT_TIMESTAMP on the column
        $this->db->query("ALTER TABLE use_new_authorization MODIFY created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'When user was added to migration list'");

        log_message('info', 'Migration 071: Replaced trigger with DEFAULT CURRENT_TIMESTAMP on use_new_authorization.created_at');
    }

    public function down()
    {
        // Remove the default
        $this->db->query("ALTER TABLE use_new_authorization MODIFY created_at DATETIME NULL COMMENT 'When user was added to migration list'");

        // Recreate the trigger
        $this->db->query("
            CREATE TRIGGER trg_use_new_authorization_created_at
            BEFORE INSERT ON use_new_authorization
            FOR EACH ROW
            SET NEW.created_at = IFNULL(NEW.created_at, NOW())
        ");

        log_message('info', 'Migration 071: Restored trigger on use_new_authorization.created_at');
    }
}
