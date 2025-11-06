-- Create missing email_list tables
-- Bug: migration 049 only created email_lists table

-- Table: email_list_roles
CREATE TABLE IF NOT EXISTS `email_list_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_list_id` int(11) NOT NULL COMMENT 'FK to email_lists',
  `types_roles_id` int(11) NOT NULL COMMENT 'FK to types_roles',
  `section_id` int(11) NOT NULL COMMENT 'FK to sections',
  `granted_by` int(11) DEFAULT NULL COMMENT 'User ID who granted this role',
  `granted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When role was granted',
  `revoked_at` datetime DEFAULT NULL COMMENT 'When role was revoked (NULL if active)',
  `notes` text COMMENT 'Optional notes',
  PRIMARY KEY (`id`),
  KEY `idx_email_list_id` (`email_list_id`),
  KEY `idx_types_roles_id` (`types_roles_id`),
  KEY `idx_section_id` (`section_id`),
  CONSTRAINT `fk_elr_email_list_id` FOREIGN KEY (`email_list_id`) REFERENCES `email_lists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_elr_types_roles_id` FOREIGN KEY (`types_roles_id`) REFERENCES `types_roles` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_elr_section_id` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: email_list_members
CREATE TABLE IF NOT EXISTS `email_list_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_list_id` int(11) NOT NULL COMMENT 'FK to email_lists',
  `membre_id` varchar(25) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'FK to membres',
  `added_by` int(11) DEFAULT NULL COMMENT 'User ID who added this member',
  `added_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When member was added',
  `revoked_at` datetime DEFAULT NULL COMMENT 'When member was removed (NULL if active)',
  `notes` text COMMENT 'Optional notes',
  PRIMARY KEY (`id`),
  KEY `idx_email_list_id` (`email_list_id`),
  KEY `idx_membre_id` (`membre_id`),
  CONSTRAINT `fk_elm_email_list_id` FOREIGN KEY (`email_list_id`) REFERENCES `email_lists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_elm_membre_id` FOREIGN KEY (`membre_id`) REFERENCES `membres` (`mlogin`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: email_list_external
CREATE TABLE IF NOT EXISTS `email_list_external` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_list_id` int(11) NOT NULL COMMENT 'FK to email_lists',
  `external_email` varchar(255) NOT NULL COMMENT 'External email address',
  `name` varchar(100) DEFAULT NULL COMMENT 'Optional name for the external contact',
  `added_by` int(11) DEFAULT NULL COMMENT 'User ID who added this external email',
  `added_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When email was added',
  `revoked_at` datetime DEFAULT NULL COMMENT 'When email was removed (NULL if active)',
  `notes` text COMMENT 'Optional notes',
  `source_file` varchar(255) DEFAULT NULL COMMENT 'Source file if imported',
  PRIMARY KEY (`id`),
  KEY `idx_email_list_id` (`email_list_id`),
  KEY `idx_external_email` (`external_email`),
  CONSTRAINT `fk_ele_email_list_id` FOREIGN KEY (`email_list_id`) REFERENCES `email_lists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
