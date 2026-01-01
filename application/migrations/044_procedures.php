<?php

/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *	  Script de migration de la base - Gestion des procédures
 */

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * Migration pour la création de la table procedures
 * 
 * Table pour gérer les procédures du club avec support markdown
 * et fichiers attachés
 *    
 * @author frederic
 */
class Migration_Procedures extends CI_Migration {

	protected $migration_number;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->migration_number = 44;
	}

	/*
	 * Execute an array of sql requests
	 */
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

	/**
	 * Apply the migration
	 */
	public function up() {
		$errors = 0;

		$sqls = array(
			"CREATE TABLE IF NOT EXISTS `procedures` (
  				`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  				`name` varchar(128) NOT NULL COMMENT 'Nom unique de la procédure',
  				`title` varchar(255) NOT NULL COMMENT 'Titre affiché de la procédure', 
  				`description` text DEFAULT NULL COMMENT 'Description courte de la procédure',
  				`markdown_file` varchar(255) DEFAULT NULL COMMENT 'Chemin vers le fichier markdown',
  				`section_id` int(11) DEFAULT NULL COMMENT 'Section associée (NULL = globale)',
  				`status` enum('draft','published','archived') DEFAULT 'draft' COMMENT 'Statut de la procédure',
  				`version` varchar(20) DEFAULT '1.0' COMMENT 'Version de la procédure',
  				`created_by` varchar(25) DEFAULT NULL COMMENT 'Utilisateur créateur',
  				`created_at` timestamp DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
  				`updated_by` varchar(25) DEFAULT NULL COMMENT 'Dernier utilisateur modificateur',
  				`updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Date de dernière modification',
  				PRIMARY KEY (`id`),
  				UNIQUE KEY `unique_name` (`name`),
  				KEY `idx_section` (`section_id`),
  				KEY `idx_status` (`status`),
  				KEY `idx_created_by` (`created_by`),
  				CONSTRAINT `fk_procedures_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Gestion des procédures du club'",
			
			"INSERT IGNORE INTO `procedures` (`name`, `title`, `description`, `section_id`, `status`, `version`, `created_by`) VALUES 
				('example_procedure', 'Procédure d\\'exemple', 'Exemple de procédure pour tester le système', NULL, 'draft', '1.0', 'admin'),
				('maintenance_planeur', 'Maintenance des planeurs', 'Procédures de maintenance préventive des planeurs', NULL, 'published', '2.1', 'admin')"
		);

		$errors += $this->run_queries($sqls);
		
		// Créer la structure de dossiers pour les procédures
		$this->create_procedures_directory_structure();
		
		gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");

		return !$errors;
	}

	/**
	 * Reverse the migration
	 */
	public function down() {
		$errors = 0;
		$sqls = array(
			"DROP TABLE IF EXISTS `procedures`"
		);

		$errors += $this->run_queries($sqls);
		gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");

		return !$errors;
	}
	
	/**
	 * Créer la structure de dossiers pour les procédures
	 */
	private function create_procedures_directory_structure() {
		$base_dir = './uploads/procedures/';
		
		// Créer le dossier principal
		if (!is_dir($base_dir)) {
			if (!mkdir($base_dir, 0755, true)) {
				gvv_error("Impossible de créer le dossier " . $base_dir);
				return false;
			}
		}
		
		// Créer les dossiers d'exemple
		$example_dirs = ['example_procedure', 'maintenance_planeur'];
		foreach ($example_dirs as $dir) {
			$full_path = $base_dir . $dir . '/';
			if (!is_dir($full_path)) {
				if (!mkdir($full_path, 0755, true)) {
					gvv_error("Impossible de créer le dossier " . $full_path);
				} else {
					// Créer un fichier markdown d'exemple
					$this->create_example_markdown($full_path, $dir);
				}
			}
		}
		
		// Créer un fichier .htaccess pour sécuriser les procédures
		$htaccess_content = "# Protection des procédures GVV\n";
		$htaccess_content .= "# Seuls les utilisateurs authentifiés peuvent accéder aux procédures\n";
		$htaccess_content .= "Require valid-user\n";
		$htaccess_content .= "\n# Interdire l'accès direct aux fichiers PHP\n";
		$htaccess_content .= "<Files \"*.php\">\n";
		$htaccess_content .= "    Require all denied\n";
		$htaccess_content .= "</Files>\n";
		
		file_put_contents($base_dir . '.htaccess', $htaccess_content);
		
		gvv_info("Structure de dossiers procedures créée");
		return true;
	}
	
	/**
	 * Créer des fichiers markdown d'exemple
	 */
	private function create_example_markdown($dir_path, $procedure_name) {
		$markdown_content = '';
		
		if ($procedure_name === 'example_procedure') {
			$markdown_content = "# Procédure d'exemple\n\n";
			$markdown_content .= "Cette procédure sert d'exemple pour démontrer les fonctionnalités du système.\n\n";
			$markdown_content .= "## Objectif\n\n";
			$markdown_content .= "Montrer comment créer et utiliser une procédure dans GVV.\n\n";
			$markdown_content .= "## Étapes\n\n";
			$markdown_content .= "1. **Création** : Utiliser l'interface web pour créer une nouvelle procédure\n";
			$markdown_content .= "2. **Rédaction** : Écrire le contenu en markdown\n";
			$markdown_content .= "3. **Publication** : Valider et publier la procédure\n\n";
			$markdown_content .= "## Fonctionnalités supportées\n\n";
			$markdown_content .= "- **Markdown** : Formatage riche du texte\n";
			$markdown_content .= "- **Images** : Support des images intégrées\n";
			$markdown_content .= "- **Fichiers** : Attachement de documents PDF, etc.\n";
			$markdown_content .= "- **Versions** : Gestion des versions de procédures\n\n";
			$markdown_content .= "## Notes\n\n";
			$markdown_content .= "> Cette procédure peut être modifiée ou supprimée sans impact.\n\n";
			$markdown_content .= "---\n";
			$markdown_content .= "*Dernière mise à jour : " . date('d/m/Y') . "*\n";
		} else {
			$markdown_content = "# Maintenance des planeurs\n\n";
			$markdown_content .= "Procédures de maintenance préventive pour la flotte de planeurs.\n\n";
			$markdown_content .= "## Contrôles quotidiens\n\n";
			$markdown_content .= "### Avant le premier vol\n\n";
			$markdown_content .= "- [ ] Inspection visuelle de la structure\n";
			$markdown_content .= "- [ ] Vérification des commandes de vol\n";
			$markdown_content .= "- [ ] Contrôle de l'état des surfaces\n";
			$markdown_content .= "- [ ] Test des instruments\n\n";
			$markdown_content .= "### Après le dernier vol\n\n";
			$markdown_content .= "- [ ] Nettoyage de la verrière\n";
			$markdown_content .= "- [ ] Rangement et bâchage\n";
			$markdown_content .= "- [ ] Mise à jour du carnet de vol\n\n";
			$markdown_content .= "## Contrôles périodiques\n\n";
			$markdown_content .= "### Hebdomadaire\n\n";
			$markdown_content .= "- Contrôle des pneumatiques\n";
			$markdown_content .= "- Vérification du niveau d'huile\n";
			$markdown_content .= "- Test des freins\n\n";
			$markdown_content .= "### Mensuel\n\n";
			$markdown_content .= "- Inspection détaillée de la structure\n";
			$markdown_content .= "- Contrôle des câbles et commandes\n";
			$markdown_content .= "- Vérification des instruments\n\n";
			$markdown_content .= "## Documents de référence\n\n";
			$markdown_content .= "- Manuel de maintenance constructeur\n";
			$markdown_content .= "- Consignes de navigabilité DGAC\n";
			$markdown_content .= "- Procédures internes du club\n\n";
			$markdown_content .= "---\n";
			$markdown_content .= "**Version 2.1** - *Dernière révision : " . date('d/m/Y') . "*\n";
		}
		
		$file_path = $dir_path . 'procedure_' . $procedure_name . '.md';
		if (file_put_contents($file_path, $markdown_content)) {
			gvv_info("Fichier markdown d'exemple créé : " . $file_path);
		} else {
			gvv_error("Impossible de créer le fichier : " . $file_path);
		}
	}
}