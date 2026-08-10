<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 167: create dashboard_shortcuts table
 *
 * Data-driven navigation shortcuts (cards) that club-admins can inject into
 * any welcome.php dashboard section without development. Each shortcut is
 * scoped to a dashboard section (welcome.php's `section($name)` values) and
 * optionally to a club/section (club_id) and a minimum role.
 */
class Migration_Dashboard_shortcuts extends CI_Migration {

    public function up() {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `dashboard_shortcuts` (
                `id`              INT(11)      NOT NULL AUTO_INCREMENT,
                `dashboard`       VARCHAR(50)  NOT NULL COMMENT 'valeur welcome.php section(): user/flights/treasurer/formation/maintenance/admin_club/admin_sys/dev',
                `section`         VARCHAR(100) NULL DEFAULT NULL COMMENT 'sous-titre de regroupement dans le dashboard, NULL = non catégorisé',
                `title_key`       VARCHAR(100) NULL DEFAULT NULL,
                `title`           VARCHAR(100) NOT NULL,
                `description_key` VARCHAR(255) NULL DEFAULT NULL,
                `description`     TEXT         NULL DEFAULT NULL,
                `url`             VARCHAR(255) NOT NULL,
                `icon`            VARCHAR(50)  NULL DEFAULT NULL COMMENT 'classe Font Awesome, ex. fa-file-signature',
                `color`           VARCHAR(20)  NULL DEFAULT NULL COMMENT 'classe Bootstrap text-*, ex. text-primary',
                `role_required`   VARCHAR(50)  NULL DEFAULT NULL COMMENT 'NULL = tous ; sinon types_roles.nom',
                `sort_order`      INT(11)      NOT NULL DEFAULT 0,
                `active`          TINYINT(1)   NOT NULL DEFAULT 1,
                `club_id`         INT(11)      NULL DEFAULT NULL COMMENT 'NULL = toutes sections',
                `created_at`      DATETIME     NULL DEFAULT NULL,
                `updated_at`      DATETIME     NULL DEFAULT NULL,
                `created_by`      VARCHAR(50)  NULL DEFAULT NULL,
                `updated_by`      VARCHAR(50)  NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_dashboard_shortcuts_lookup` (`dashboard`, `active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
    }

    public function down() {
        $this->db->query("DROP TABLE IF EXISTS `dashboard_shortcuts`");
    }
}
