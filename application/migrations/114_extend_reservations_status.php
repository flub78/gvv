<?php
/**
 * Migration 114: Extend reservations status ENUM with flight type values
 *
 * Adds: vol_local, navigation, vld, convoyage
 * Renames "Statut" concept to "Type de réservation" (UI-only, no DB column rename)
 */
class Migration_Extend_reservations_status extends CI_Migration {

    public function up() {
        $this->db->query("ALTER TABLE `reservations`
            MODIFY COLUMN `status`
            ENUM('reservation','maintenance','unavailable','vol_local','navigation','vld','convoyage')
            NOT NULL DEFAULT 'reservation'
            COMMENT 'Type de réservation'");
    }

    public function down() {
        // Map new types back to 'reservation' before shrinking the ENUM
        $this->db->query("UPDATE `reservations`
            SET `status` = 'reservation'
            WHERE `status` IN ('vol_local','navigation','vld','convoyage')");

        $this->db->query("ALTER TABLE `reservations`
            MODIFY COLUMN `status`
            ENUM('reservation','maintenance','unavailable')
            NOT NULL DEFAULT 'reservation'
            COMMENT 'Reservation status'");
    }
}
