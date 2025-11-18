<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 053: Replace compte field with membre_payeur in membres table
 *
 * This migration replaces the direct account reference (membres.compte)
 * with a member reference (membres.membre_payeur). This allows the system
 * to properly find the correct account based on the current section.
 *
 * Changes:
 * - Add membre_payeur VARCHAR(25) field to membres table
 * - Populate membre_payeur from comptes.pilote where membres.compte is not null
 * - Drop the compte field from membres table
 *
 * The new logic:
 * - When membre_payeur is set, find compte 411 where comptes.pilote = membre_payeur AND comptes.club = current_section
 * - This ensures the correct account is used based on the active section
 */
class Migration_Replace_compte_with_membre_payeur extends CI_Migration
{
    public function up()
    {
        try {
            // Check if membre_payeur already exists (skip if it does)
            if ($this->db->field_exists('membre_payeur', 'membres')) {
                log_message('info', 'Migration 053: membre_payeur field already exists, skipping');
                return;
            }

            log_message('info', 'Migration 053: Starting migration - replacing compte with membre_payeur');

            // Step 1: Add the new membre_payeur field
            $this->dbforge->add_column('membres', [
                'membre_payeur' => [
                    'type' => 'VARCHAR',
                    'constraint' => 25,
                    'null' => TRUE,
                    'after' => 'compte',
                    'comment' => 'Member login whose account should be charged (replaces compte field)'
                ]
            ]);
            log_message('info', 'Migration 053: Added membre_payeur field');

            // Step 2: Populate membre_payeur from comptes.pilote
            // For members who have a compte set, find the pilote of that compte
            $query = "
                UPDATE membres m
                INNER JOIN comptes c ON m.compte = c.id
                SET m.membre_payeur = c.pilote
                WHERE m.compte IS NOT NULL
                AND m.compte > 0
            ";
            $this->db->query($query);
            $affected = $this->db->affected_rows();
            log_message('info', "Migration 053: Populated membre_payeur for $affected members");

            // Step 3: Add index on membre_payeur for better query performance
            if (!$this->index_exists('membres', 'idx_membre_payeur')) {
                $this->db->query('ALTER TABLE membres ADD INDEX idx_membre_payeur (membre_payeur)');
                log_message('info', 'Migration 053: Added index on membre_payeur');
            }

            // Step 4: Add foreign key constraint (optional, wrapped in try-catch)
            try {
                $this->db->query('
                    ALTER TABLE membres
                    ADD CONSTRAINT fk_membres_membre_payeur
                    FOREIGN KEY (membre_payeur)
                    REFERENCES membres(mlogin)
                    ON DELETE SET NULL
                ');
                log_message('info', 'Migration 053: Added FK constraint on membre_payeur');
            } catch (Exception $e) {
                log_message('warning', 'Migration 053: Could not add FK constraint (not critical): ' . $e->getMessage());
            }

            // Step 5: Drop the old compte field
            // We keep it for now to allow rollback, will drop in a future migration
            // Or drop it now if you're sure:
            // $this->dbforge->drop_column('membres', 'compte');
            // log_message('info', 'Migration 053: Dropped compte field');

            log_message('info', 'Migration 053: Successfully completed');

        } catch (Exception $e) {
            log_message('error', 'Migration 053 FAILED: ' . $e->getMessage());
            log_message('error', 'Migration 053 Error trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    public function down()
    {
        try {
            log_message('info', 'Migration 053: Rolling back - restoring compte field');

            // Step 1: Restore membres.compte from membre_payeur
            // Find the compte.id where comptes.pilote = membres.membre_payeur
            $query = "
                UPDATE membres m
                INNER JOIN comptes c ON c.pilote = m.membre_payeur AND c.codec = '411'
                SET m.compte = c.id
                WHERE m.membre_payeur IS NOT NULL
                AND m.membre_payeur != ''
            ";
            $this->db->query($query);
            $affected = $this->db->affected_rows();
            log_message('info', "Migration 053 rollback: Restored compte field for $affected members");

            // Step 2: Drop FK constraint if it exists
            try {
                $this->db->query('ALTER TABLE membres DROP FOREIGN KEY fk_membres_membre_payeur');
                log_message('info', 'Migration 053 rollback: Dropped FK constraint');
            } catch (Exception $e) {
                log_message('info', 'Migration 053 rollback: FK constraint does not exist (normal)');
            }

            // Step 3: Drop index
            try {
                $this->db->query('ALTER TABLE membres DROP INDEX idx_membre_payeur');
                log_message('info', 'Migration 053 rollback: Dropped index');
            } catch (Exception $e) {
                log_message('info', 'Migration 053 rollback: Index does not exist (normal)');
            }

            // Step 4: Drop membre_payeur field
            $this->dbforge->drop_column('membres', 'membre_payeur');
            log_message('info', 'Migration 053 rollback: Dropped membre_payeur field');

            log_message('info', 'Migration 053: Rollback completed successfully');

        } catch (Exception $e) {
            log_message('error', 'Migration 053 rollback FAILED: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if an index exists on a table
     *
     * @param string $table_name Table name
     * @param string $index_name Index name
     * @return bool True if index exists
     */
    private function index_exists($table_name, $index_name)
    {
        $query = $this->db->query("
            SELECT COUNT(*) as count
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
            AND table_name = ?
            AND index_name = ?
        ", array($table_name, $index_name));

        $result = $query->row_array();
        return $result['count'] > 0;
    }
}
