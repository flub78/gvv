<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 108 — Auto-incrément pour mnumero
 *
 * Affecte un numéro unique séquentiel à chaque membre qui n'en a pas,
 * puis configure mnumero en AUTO_INCREMENT avec une clé UNIQUE.
 */
class Migration_Mnumero_autoincrement extends CI_Migration {

    private function keyExists($table, $key_name)
    {
        $t = $this->db->escape_str($table);
        $k = $this->db->escape_str($key_name);
        $query = $this->db->query(
            "SELECT COUNT(*) as cnt FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND INDEX_NAME = '$k'"
        );
        $row = $query ? $query->row_array() : null;
        return isset($row['cnt']) && (int)$row['cnt'] > 0;
    }

    public function up() {
        // 1. Affecter des numéros séquentiels aux membres sans mnumero
        $res = $this->db->query("SELECT COALESCE(MAX(mnumero), 0) AS max_num FROM membres");
        $offset = (int)$res->row()->max_num;
        $members = $this->db->query("SELECT mlogin FROM membres WHERE mnumero IS NULL ORDER BY mlogin")->result_array();
        foreach ($members as $i => $member) {
            $new_num = $offset + $i + 1;
            $login = $this->db->escape($member['mlogin']);
            $this->db->query("UPDATE membres SET mnumero = $new_num WHERE mlogin = $login");
        }

        // 2. Ajouter clé UNIQUE sur mnumero (pré-requis pour AUTO_INCREMENT)
        // Vérifier que la clé n'existe pas déjà
        if (!$this->keyExists('membres', 'uk_membres_mnumero')) {
            $this->db->query(
                "ALTER TABLE membres ADD UNIQUE KEY uk_membres_mnumero (mnumero)"
            );
        }

        // 3. Modifier la colonne pour être NOT NULL et configurer AUTO_INCREMENT
        $this->db->query(
            "ALTER TABLE membres MODIFY COLUMN mnumero INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Numéro de membre pour impression carte'"
        );

        // 4. Définir le prochain AUTO_INCREMENT au-delà du max existant
        $max_mnumero = $this->db->query("SELECT MAX(mnumero) as max_num FROM membres")->row()->max_num;
        $next_auto_increment = (int)$max_mnumero + 1;
        
        $this->db->query(
            "ALTER TABLE membres AUTO_INCREMENT = " . $next_auto_increment
        );
    }

    public function down() {
        // Retirer AUTO_INCREMENT et UNIQUE KEY, revenir à NULL par défaut
        
        // 1. D'abord retirer AUTO_INCREMENT (en le changeant en colonne ordinaire)
        $this->db->query(
            "ALTER TABLE membres MODIFY COLUMN mnumero INT UNSIGNED NULL DEFAULT NULL COMMENT 'Numéro de membre pour impression carte'"
        );
        
        // 2. Ensuite retirer la clé UNIQUE si elle existe
        if ($this->keyExists('membres', 'uk_membres_mnumero')) {
            $this->db->query(
                "ALTER TABLE membres DROP KEY uk_membres_mnumero"
            );
        }
    }
}
