/*M!999999\- enable the sandbox mode */
-- MariaDB dump 10.19  Distrib 10.11.13-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: gvv2
-- ------------------------------------------------------
-- Server version	10.11.13-MariaDB-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `achats`
--

DROP TABLE IF EXISTS `achats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `achats` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant',
  `date` date NOT NULL COMMENT 'Date',
  `produit` varchar(32) NOT NULL COMMENT 'Produit',
  `quantite` decimal(8,2) NOT NULL COMMENT 'Quantité',
  `prix` decimal(8,2) DEFAULT 0.00 COMMENT 'Prix',
  `description` varchar(80) DEFAULT NULL COMMENT 'Description',
  `pilote` varchar(25) NOT NULL COMMENT 'Pilote',
  `facture` int(11) DEFAULT 0 COMMENT 'Numéro de facture',
  `saisie_par` varchar(25) NOT NULL COMMENT 'Opérateur',
  `club` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Gestion multi-club',
  `machine` varchar(10) DEFAULT NULL COMMENT 'Machine pour stats (planeur ou avion)',
  `vol_planeur` int(11) DEFAULT NULL COMMENT 'Vol planeur',
  `vol_avion` int(11) DEFAULT NULL COMMENT 'Vol avion',
  `mvt_pompe` int(11) DEFAULT NULL COMMENT 'Livraison essence',
  `num_cheque` varchar(50) DEFAULT NULL COMMENT 'Numéro de pièce comptable',
  PRIMARY KEY (`id`),
  KEY `pilote` (`pilote`),
  KEY `saisie_par` (`saisie_par`),
  KEY `vol_planeur` (`vol_planeur`),
  KEY `vol_avion` (`vol_avion`)
) ENGINE=InnoDB AUTO_INCREMENT=26630 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC COMMENT='Lignes de factures';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `associations_ecriture`
--

DROP TABLE IF EXISTS `associations_ecriture`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `associations_ecriture` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `string_releve` varchar(256) NOT NULL,
  `id_ecriture_gvv` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_associations_ecriture_ecritures` (`id_ecriture_gvv`),
  CONSTRAINT `fk_associations_ecriture_ecritures` FOREIGN KEY (`id_ecriture_gvv`) REFERENCES `ecritures` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1439 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `associations_of`
--

DROP TABLE IF EXISTS `associations_of`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `associations_of` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_compte_of` int(11) NOT NULL,
  `nom_of` varchar(60) NOT NULL,
  `id_compte_gvv` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_associations_of_comptes` (`id_compte_gvv`),
  CONSTRAINT `fk_associations_of_comptes` FOREIGN KEY (`id_compte_gvv`) REFERENCES `comptes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `associations_releve`
--

DROP TABLE IF EXISTS `associations_releve`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `associations_releve` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `string_releve` varchar(128) NOT NULL,
  `type` varchar(60) DEFAULT NULL,
  `id_compte_gvv` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_associations_releve_comptes` (`id_compte_gvv`),
  CONSTRAINT `fk_associations_releve_comptes` FOREIGN KEY (`id_compte_gvv`) REFERENCES `comptes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `attachments`
--

DROP TABLE IF EXISTS `attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `referenced_table` varchar(128) DEFAULT NULL,
  `referenced_id` varchar(128) DEFAULT NULL,
  `user_id` varchar(25) DEFAULT NULL,
  `filename` varchar(128) DEFAULT NULL,
  `description` varchar(124) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `club` tinyint(4) DEFAULT 0 COMMENT 'Commentaire gestion multi-section',
  `file_backup` varchar(255) DEFAULT NULL COMMENT 'Backup of original file path before section reorganization',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=308 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `authorization_audit_log`
--

DROP TABLE IF EXISTS `authorization_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `authorization_audit_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `action_type` enum('grant_role','revoke_role','modify_permission','access_denied','access_granted') NOT NULL,
  `actor_user_id` int(11) DEFAULT NULL,
  `target_user_id` int(11) DEFAULT NULL,
  `types_roles_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `controller` varchar(64) DEFAULT NULL,
  `action` varchar(64) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_actor` (`actor_user_id`),
  KEY `idx_target` (`target_user_id`),
  KEY `idx_timestamp` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `authorization_comparison_log`
--

DROP TABLE IF EXISTS `authorization_comparison_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `authorization_comparison_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `controller` varchar(100) NOT NULL,
  `action` varchar(100) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `new_system_result` tinyint(1) NOT NULL,
  `legacy_system_result` tinyint(1) NOT NULL,
  `new_system_details` text DEFAULT NULL,
  `legacy_system_details` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_controller_action` (`controller`,`action`),
  KEY `idx_mismatch` (`new_system_result`,`legacy_system_result`),
  CONSTRAINT `fk_comparison_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `authorization_migration_status`
--

DROP TABLE IF EXISTS `authorization_migration_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `authorization_migration_status` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `migration_status` enum('pending','in_progress','completed','failed','rolled_back') NOT NULL DEFAULT 'pending',
  `use_new_system` tinyint(1) NOT NULL DEFAULT 0,
  `migrated_by` int(11) DEFAULT NULL,
  `migrated_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user` (`user_id`),
  KEY `fk_auth_migration_migrator` (`migrated_by`),
  KEY `idx_migration_status` (`migration_status`,`use_new_system`),
  CONSTRAINT `fk_auth_migration_migrator` FOREIGN KEY (`migrated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_auth_migration_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `calendar`
--

DROP TABLE IF EXISTS `calendar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `mlogin` varchar(64) NOT NULL,
  `role` varchar(64) NOT NULL,
  `commentaire` varchar(256) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `categorie`
--

DROP TABLE IF EXISTS `categorie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorie` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant',
  `nom` varchar(32) NOT NULL COMMENT 'Nom',
  `description` varchar(80) DEFAULT NULL COMMENT 'Commentaire',
  `parent` int(11) NOT NULL DEFAULT 0 COMMENT 'Catégorie parente',
  `type` int(11) NOT NULL DEFAULT 0 COMMENT 'Type de catégorie',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ROW_FORMAT=DYNAMIC COMMENT='Catégorie d''écritures pour comptabilité analytique';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ci_sessions`
--

DROP TABLE IF EXISTS `ci_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ci_sessions` (
  `session_id` varchar(40) NOT NULL DEFAULT '0',
  `ip_address` varchar(16) NOT NULL DEFAULT '0',
  `user_agent` varchar(150) NOT NULL,
  `last_activity` int(10) unsigned NOT NULL DEFAULT 0,
  `user_data` text DEFAULT NULL,
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clotures`
--

DROP TABLE IF EXISTS `clotures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clotures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `section` tinyint(1) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `description` varchar(124) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `comptes`
--

DROP TABLE IF EXISTS `comptes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comptes` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant',
  `nom` varchar(48) NOT NULL COMMENT 'Nom du compte',
  `pilote` varchar(25) DEFAULT NULL COMMENT 'Référence du pilote',
  `desc` varchar(80) DEFAULT NULL COMMENT 'Description',
  `codec` varchar(10) NOT NULL COMMENT 'Code comptable',
  `actif` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Actif = 1, passif = 0',
  `debit` decimal(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Débit',
  `credit` decimal(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Crédit',
  `saisie_par` varchar(25) NOT NULL DEFAULT '""' COMMENT 'Créateur',
  `club` tinyint(1) DEFAULT 0 COMMENT 'Gestion multi-club',
  `masked` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `codec` (`codec`),
  KEY `pilote` (`pilote`),
  CONSTRAINT `fk_comptes_planc` FOREIGN KEY (`codec`) REFERENCES `planc` (`pcode`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1407 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `configuration`
--

DROP TABLE IF EXISTS `configuration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `configuration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cle` varchar(128) NOT NULL,
  `valeur` varchar(255) DEFAULT NULL,
  `lang` varchar(10) DEFAULT NULL,
  `categorie` varchar(64) DEFAULT NULL,
  `club` tinyint(1) DEFAULT NULL,
  `description` varchar(128) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL COMMENT 'Fichier de configuration',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_cle_lang_club` (`cle`,`lang`,`club`),
  KEY `idx_cle` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `data_access_rules`
--

DROP TABLE IF EXISTS `data_access_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `data_access_rules` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `types_roles_id` int(11) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `access_scope` enum('own','section','all') NOT NULL DEFAULT 'own',
  `field_name` varchar(64) DEFAULT NULL,
  `section_field` varchar(64) DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_rule` (`types_roles_id`,`table_name`,`access_scope`),
  CONSTRAINT `fk_data_access_rules_role` FOREIGN KEY (`types_roles_id`) REFERENCES `types_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ecritures`
--

DROP TABLE IF EXISTS `ecritures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecritures` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant',
  `annee_exercise` int(11) NOT NULL COMMENT 'Année d''exercice',
  `date_creation` date NOT NULL COMMENT 'Date',
  `date_op` date NOT NULL COMMENT 'Date de l''opération',
  `compte1` int(11) NOT NULL COMMENT 'Emploi',
  `compte2` int(11) NOT NULL COMMENT 'Ressource',
  `montant` decimal(14,2) NOT NULL COMMENT 'Montant de l''écriture',
  `description` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Libellé',
  `type` int(11) DEFAULT 0 COMMENT 'Type de paiement',
  `num_cheque` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Numéro de piéce comptable',
  `saisie_par` varchar(25) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Opérateur',
  `gel` int(11) DEFAULT 0 COMMENT 'Vérifié',
  `club` tinyint(1) DEFAULT 0 COMMENT 'Gestion multi-club',
  `achat` int(11) DEFAULT NULL COMMENT 'Achat correspondant',
  `quantite` varchar(11) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT '0' COMMENT 'Quantitè de l''achat',
  `prix` decimal(8,2) DEFAULT -1.00 COMMENT 'Prix de l''achat',
  `categorie` int(11) NOT NULL DEFAULT 0 COMMENT 'Catégorie de dépense ou recette',
  PRIMARY KEY (`id`),
  KEY `compte1` (`compte1`),
  KEY `saisie_par` (`saisie_par`),
  KEY `compte2` (`compte2`)
) ENGINE=InnoDB AUTO_INCREMENT=37704 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `email_list_external`
--

DROP TABLE IF EXISTS `email_list_external`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_list_external` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_list_id` int(11) NOT NULL,
  `external_email` varchar(255) NOT NULL,
  `external_name` varchar(100) DEFAULT NULL,
  `source_file` varchar(255) DEFAULT NULL COMMENT 'Filename source if imported from file (NULL if manually added)',
  `added_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'When email was added',
  PRIMARY KEY (`id`),
  KEY `idx_email_list_id` (`email_list_id`),
  CONSTRAINT `fk_ele_email_list_id` FOREIGN KEY (`email_list_id`) REFERENCES `email_lists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=117 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `email_list_members`
--

DROP TABLE IF EXISTS `email_list_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_list_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_list_id` int(11) NOT NULL,
  `membre_id` varchar(25) NOT NULL,
  `added_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'When member was added',
  PRIMARY KEY (`id`),
  KEY `idx_email_list_id` (`email_list_id`),
  KEY `idx_membre_id` (`membre_id`),
  CONSTRAINT `fk_elm_email_list_id` FOREIGN KEY (`email_list_id`) REFERENCES `email_lists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_elm_membre_id` FOREIGN KEY (`membre_id`) REFERENCES `membres` (`mlogin`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `email_list_roles`
--

DROP TABLE IF EXISTS `email_list_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_list_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_list_id` int(11) NOT NULL,
  `types_roles_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `granted_by` int(11) DEFAULT NULL,
  `granted_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'When role was granted',
  `revoked_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_email_list_id` (`email_list_id`),
  KEY `idx_types_roles_id` (`types_roles_id`),
  KEY `idx_section_id` (`section_id`),
  CONSTRAINT `fk_elr_email_list_id` FOREIGN KEY (`email_list_id`) REFERENCES `email_lists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_elr_section_id` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`),
  CONSTRAINT `fk_elr_types_roles_id` FOREIGN KEY (`types_roles_id`) REFERENCES `types_roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=122 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `email_list_sublists`
--

DROP TABLE IF EXISTS `email_list_sublists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_list_sublists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_list_id` int(11) NOT NULL COMMENT 'La liste parente qui contient des sous-listes',
  `child_list_id` int(11) NOT NULL COMMENT 'La liste simple incluse comme sous-liste',
  `added_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_parent_child` (`parent_list_id`,`child_list_id`),
  KEY `idx_parent` (`parent_list_id`),
  KEY `idx_child` (`child_list_id`),
  CONSTRAINT `fk_email_list_sublists_child` FOREIGN KEY (`child_list_id`) REFERENCES `email_lists` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_email_list_sublists_parent` FOREIGN KEY (`parent_list_id`) REFERENCES `email_lists` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `email_lists`
--

DROP TABLE IF EXISTS `email_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_lists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `description` text DEFAULT NULL,
  `active_member` enum('active','inactive','all') NOT NULL DEFAULT 'active',
  `visible` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL COMMENT 'User ID who created the list',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Creation timestamp',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Last update timestamp',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_name` (`name`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_email_lists_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emlogin` varchar(25) NOT NULL,
  `etype` int(11) NOT NULL,
  `edate` date DEFAULT NULL,
  `evaid` double DEFAULT NULL,
  `evpid` double DEFAULT NULL,
  `ecomment` varchar(128) DEFAULT NULL COMMENT 'Commentaire',
  `year` int(11) DEFAULT NULL COMMENT 'Année',
  `date_expiration` date DEFAULT NULL COMMENT 'date expiration',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=574 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `events_types`
--

DROP TABLE IF EXISTS `events_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `events_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Numéro',
  `name` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `activite` tinyint(2) NOT NULL DEFAULT 0 COMMENT 'Activité associée',
  `en_vol` tinyint(4) NOT NULL COMMENT 'Associé à un vol',
  `multiple` tinyint(1) DEFAULT NULL COMMENT 'Multiple',
  `expirable` tinyint(1) DEFAULT NULL COMMENT 'a une date d_expiration',
  `ordre` tinyint(2) DEFAULT NULL COMMENT 'ordre d_affichage',
  `annual` tinyint(1) DEFAULT NULL COMMENT 'Evénement annuel',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `formation_chapitres`
--

DROP TABLE IF EXISTS `formation_chapitres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `formation_chapitres` (
  `name` varchar(64) NOT NULL,
  `description` varchar(128) NOT NULL,
  `ordre` int(4) NOT NULL DEFAULT 9999,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `formation_item`
--

DROP TABLE IF EXISTS `formation_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `formation_item` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `description` varchar(128) NOT NULL,
  `phase` varchar(64) NOT NULL,
  `ordre` int(8) NOT NULL DEFAULT 9999,
  PRIMARY KEY (`id`),
  KEY `to_phase` (`phase`),
  CONSTRAINT `belongs_to` FOREIGN KEY (`phase`) REFERENCES `formation_chapitres` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `formation_progres`
--

DROP TABLE IF EXISTS `formation_progres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `formation_progres` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `pilote` varchar(25) NOT NULL,
  `instructeur` varchar(25) NOT NULL,
  `date` date NOT NULL,
  `subject` int(8) NOT NULL,
  `niveau` int(4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pilote` (`pilote`),
  UNIQUE KEY `instructeur` (`instructeur`),
  UNIQUE KEY `subject` (`subject`),
  CONSTRAINT `formation_progres_ibfk_1` FOREIGN KEY (`subject`) REFERENCES `formation_item` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `historique`
--

DROP TABLE IF EXISTS `historique`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `historique` (
  `id` int(8) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant',
  `machine` varchar(20) NOT NULL COMMENT 'Machine',
  `annee` int(4) NOT NULL COMMENT 'Année',
  `heures` int(4) NOT NULL COMMENT 'Heures',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Historique des heures de vol';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `licences`
--

DROP TABLE IF EXISTS `licences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `licences` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant',
  `pilote` varchar(25) NOT NULL COMMENT 'Pilote',
  `type` tinyint(2) NOT NULL DEFAULT 0 COMMENT 'Type de licence',
  `year` int(4) NOT NULL COMMENT 'Année de validité',
  `date` date NOT NULL COMMENT 'Date de souscription',
  `comment` varchar(250) NOT NULL COMMENT 'Commentaire',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=308 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(40) NOT NULL,
  `time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1850 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `machinesa`
--

DROP TABLE IF EXISTS `machinesa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `machinesa` (
  `macconstruc` varchar(64) NOT NULL,
  `macmodele` varchar(24) NOT NULL,
  `macimmat` varchar(10) NOT NULL DEFAULT 'F-B' COMMENT 'Immatriculation',
  `macnbhdv` decimal(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Heures hors système',
  `macplaces` tinyint(1) DEFAULT 2 COMMENT 'Nombre de places',
  `macrem` tinyint(1) DEFAULT NULL COMMENT 'Avion remorqueur',
  `maprive` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Machine privée',
  `club` tinyint(1) DEFAULT 0 COMMENT 'Gestion multi-club',
  `actif` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'Machine active',
  `comment` varchar(250) DEFAULT NULL COMMENT 'Description',
  `maprix` varchar(32) NOT NULL COMMENT 'prix de l''heure',
  `maprixdc` varchar(32) DEFAULT NULL COMMENT 'Prix double commande',
  `horametre_en_minutes` int(11) DEFAULT 0 COMMENT 'Horamètre en heures et minutes',
  `fabrication` int(11) DEFAULT NULL COMMENT 'Année de mise en service',
  PRIMARY KEY (`macimmat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `machinesp`
--

DROP TABLE IF EXISTS `machinesp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `machinesp` (
  `mpconstruc` varchar(64) NOT NULL,
  `mpmodele` varchar(32) NOT NULL,
  `mpimmat` varchar(10) NOT NULL COMMENT 'immatriculation',
  `mpnumc` varchar(5) DEFAULT NULL COMMENT 'Numéro de concours',
  `mpnbhdv` decimal(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Heures hors système',
  `mpbiplace` varchar(1) DEFAULT '1' COMMENT 'Nombre de places',
  `mpautonome` tinyint(1) DEFAULT NULL COMMENT 'Autonome',
  `mptreuil` tinyint(1) DEFAULT NULL COMMENT 'Treuillable',
  `mpprive` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Propriétaire',
  `club` tinyint(1) DEFAULT 0 COMMENT 'Gestion multi-club',
  `mprix` varchar(32) DEFAULT NULL COMMENT 'Prix de l''heure',
  `mprix_forfait` varchar(32) DEFAULT NULL COMMENT 'Prix de l''heure au forfait',
  `mprix_moteur` varchar(32) DEFAULT NULL COMMENT 'Prix de l''heure moteur',
  `mmax_facturation` int(11) NOT NULL DEFAULT 180 COMMENT 'Temps max de facturation en minutes',
  `actif` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'Machine active',
  `comment` varchar(250) DEFAULT NULL COMMENT 'Description',
  `horametre_en_minutes` int(11) DEFAULT 0 COMMENT 'Horamètre en heures et minutes',
  `fabrication` int(11) DEFAULT NULL COMMENT 'Année de mise en service',
  `banalise` tinyint(1) DEFAULT NULL COMMENT 'Machine banalisée',
  `proprio` varchar(25) DEFAULT NULL COMMENT 'Propriétaire',
  PRIMARY KEY (`mpimmat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mails`
--

DROP TABLE IF EXISTS `mails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mails` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant',
  `titre` varchar(128) NOT NULL COMMENT 'Titre',
  `destinataires` varchar(2048) NOT NULL,
  `copie_a` varchar(128) DEFAULT NULL COMMENT 'Copie à',
  `selection` tinyint(4) NOT NULL COMMENT 'Selection',
  `individuel` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'Individuel',
  `date_envoie` datetime DEFAULT NULL COMMENT 'Date d''envoie',
  `texte` varchar(4096) NOT NULL,
  `debut_facturation` date DEFAULT NULL COMMENT 'Date de début de facturation',
  `fin_facturation` date DEFAULT NULL COMMENT 'Date de fin de facturation',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Courriels';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `membres`
--

DROP TABLE IF EXISTS `membres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `membres` (
  `mlogin` varchar(25) NOT NULL COMMENT 'identifiant unique',
  `mnom` varchar(80) NOT NULL COMMENT 'Nom du membre',
  `mprenom` varchar(80) NOT NULL COMMENT 'Prénom du membre',
  `memail` varchar(50) DEFAULT NULL COMMENT 'Email',
  `memailparent` varchar(50) DEFAULT NULL COMMENT 'Email des parents',
  `madresse` varchar(80) NOT NULL COMMENT 'Adresse',
  `cp` int(5) DEFAULT NULL COMMENT 'Code postal',
  `ville` varchar(64) DEFAULT NULL,
  `pays` varchar(64) DEFAULT NULL COMMENT 'Pays',
  `mtelf` varchar(14) DEFAULT NULL COMMENT 'Téléphone fixe',
  `mtelm` varchar(14) DEFAULT NULL COMMENT 'Mobile',
  `mdaten` date DEFAULT NULL COMMENT 'Date de naissance',
  `m25ans` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Moins de 25 ans',
  `mlieun` varchar(25) DEFAULT NULL COMMENT 'Lieu de naissance',
  `msexe` char(1) NOT NULL DEFAULT 'M',
  `mniveaux` double NOT NULL COMMENT 'Qualifications du membre',
  `macces` int(11) DEFAULT 0 COMMENT 'Droits d''accés',
  `club` tinyint(1) DEFAULT 0 COMMENT 'Gestion multi-club',
  `ext` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Pilote exterieur',
  `actif` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'Pilote actif',
  `username` varchar(25) DEFAULT NULL COMMENT 'Utilisateur autorisé à accéder au compte',
  `photo` varchar(64) DEFAULT NULL COMMENT 'Photo',
  `compte` int(11) DEFAULT NULL COMMENT 'Compte pilote',
  `comment` varchar(2048) DEFAULT NULL COMMENT 'Commentaires',
  `trigramme` varchar(12) DEFAULT NULL COMMENT 'Trigramme',
  `categorie` varchar(12) DEFAULT '' COMMENT 'Cat?gorie du pilote',
  `profession` varchar(64) DEFAULT NULL COMMENT 'Profession',
  `inst_glider` varchar(25) DEFAULT NULL COMMENT 'Instructeur planeur',
  `inst_airplane` varchar(25) DEFAULT NULL COMMENT 'Instructeur avion',
  `licfed` int(11) DEFAULT NULL COMMENT 'Numéro de licence fédérale',
  `place_of_birth` varchar(128) DEFAULT NULL COMMENT 'Lieu de naissance',
  `inscription_date` date DEFAULT NULL COMMENT 'Date d''inscription',
  `validation_date` date DEFAULT NULL COMMENT 'Date de validation de l''adhésion',
  `membre_payeur` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`mlogin`),
  KEY `idx_membre_payeur` (`membre_payeur`),
  CONSTRAINT `fk_membres_membre_payeur` FOREIGN KEY (`membre_payeur`) REFERENCES `membres` (`mlogin`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `version` int(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `data` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `planc`
--

DROP TABLE IF EXISTS `planc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `planc` (
  `pcode` varchar(10) NOT NULL,
  `pdesc` varchar(128) NOT NULL,
  UNIQUE KEY `pcode` (`pcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pompes`
--

DROP TABLE IF EXISTS `pompes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pompes` (
  `pid` int(11) NOT NULL AUTO_INCREMENT,
  `pnum` int(8) NOT NULL DEFAULT 0 COMMENT 'numéro de la pompe',
  `pdatesaisie` date NOT NULL COMMENT 'Date',
  `pdatemvt` date NOT NULL COMMENT 'Date',
  `ppilid` varchar(25) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Pilote',
  `pmacid` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Machine',
  `ptype` varchar(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Type d''opération',
  `pqte` decimal(8,2) NOT NULL COMMENT 'quantité en litres',
  `ppu` decimal(8,2) NOT NULL COMMENT 'prix du litre',
  `pprix` decimal(8,2) NOT NULL COMMENT 'Prix total',
  `pdesc` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'commentaires',
  `psaisipar` varchar(25) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Nom de l''opérateur',
  PRIMARY KEY (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `procedures`
--

DROP TABLE IF EXISTS `procedures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `procedures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL COMMENT 'Nom unique de la procédure',
  `title` varchar(255) NOT NULL COMMENT 'Titre affiché de la procédure',
  `description` text DEFAULT NULL COMMENT 'Description courte de la procédure',
  `markdown_file` varchar(255) DEFAULT NULL COMMENT 'Chemin vers le fichier markdown',
  `section_id` int(11) DEFAULT NULL COMMENT 'Section associée (NULL = globale)',
  `status` enum('draft','published','archived') DEFAULT 'draft' COMMENT 'Statut de la procédure',
  `version` varchar(20) DEFAULT '1.0' COMMENT 'Version de la procédure',
  `created_by` varchar(25) DEFAULT NULL COMMENT 'Utilisateur créateur',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Date de création',
  `updated_by` varchar(25) DEFAULT NULL COMMENT 'Dernier utilisateur modificateur',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Date de dernière modification',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`),
  KEY `idx_section` (`section_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_procedures_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Gestion des procédures du club';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reports`
--

DROP TABLE IF EXISTS `reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reports` (
  `nom` varchar(64) NOT NULL COMMENT 'Nom du rapport',
  `titre` varchar(64) NOT NULL COMMENT 'Titre du rapport',
  `fields_list` varchar(128) NOT NULL COMMENT 'Titres des champs',
  `align` varchar(128) NOT NULL COMMENT 'Alignement des colonnes',
  `width` varchar(128) NOT NULL COMMENT 'Largeur des colonnes PDF',
  `landscape` tinyint(4) NOT NULL COMMENT 'Orientation du PDF en paysage',
  `sql` varchar(2048) NOT NULL COMMENT 'Requête sql',
  PRIMARY KEY (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC COMMENT='Rapports définis par l''utilisateur';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `types_roles_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `controller` varchar(64) NOT NULL,
  `action` varchar(64) DEFAULT NULL,
  `permission_type` enum('view','create','edit','delete','admin') NOT NULL DEFAULT 'view',
  `created` datetime NOT NULL,
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_role_section` (`types_roles_id`,`section_id`),
  KEY `idx_controller_action` (`controller`,`action`),
  KEY `idx_permission_lookup` (`types_roles_id`,`controller`,`action`),
  KEY `fk_role_permissions_section` (`section_id`),
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`types_roles_id`) REFERENCES `types_roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_role_permissions_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=117 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sections`
--

DROP TABLE IF EXISTS `sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(64) NOT NULL,
  `description` varchar(128) DEFAULT NULL,
  `acronyme` varchar(10) DEFAULT NULL,
  `couleur` varchar(7) DEFAULT NULL,
  `ordre_affichage` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tarifs`
--

DROP TABLE IF EXISTS `tarifs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tarifs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant',
  `reference` varchar(32) NOT NULL COMMENT 'Référence du produit',
  `date` date DEFAULT NULL COMMENT 'Date d''application',
  `date_fin` date DEFAULT '2099-12-31' COMMENT 'Date de fin',
  `description` varchar(80) DEFAULT NULL COMMENT 'Description',
  `prix` decimal(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Prix unitaire',
  `compte` int(11) NOT NULL DEFAULT 0 COMMENT 'Numéro de compte associé',
  `saisie_par` varchar(25) NOT NULL COMMENT 'Opérateur',
  `club` tinyint(1) DEFAULT 0 COMMENT 'Gestion multi-club',
  `nb_tickets` decimal(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Quantité de ticket à créditer',
  `type_ticket` int(11) DEFAULT NULL COMMENT 'Type de ticket à créditer',
  `public` tinyint(4) DEFAULT 1 COMMENT 'Permet le filtrage sur l''impression',
  PRIMARY KEY (`id`),
  KEY `compte` (`compte`)
) ENGINE=InnoDB AUTO_INCREMENT=163 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `terrains`
--

DROP TABLE IF EXISTS `terrains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `terrains` (
  `oaci` varchar(10) NOT NULL COMMENT 'Code OACI',
  `nom` varchar(64) DEFAULT NULL COMMENT 'Nom du terrain',
  `freq1` decimal(6,3) DEFAULT 0.000 COMMENT 'Fréquence principale',
  `freq2` decimal(6,3) DEFAULT 0.000 COMMENT 'Fréquence secondaire',
  `comment` varchar(256) DEFAULT NULL COMMENT 'Description',
  PRIMARY KEY (`oaci`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant',
  `date` date NOT NULL COMMENT 'Date de l''opération',
  `pilote` varchar(25) NOT NULL COMMENT 'Pilote à créditer/débiter',
  `achat` int(11) DEFAULT NULL COMMENT 'Numéro de l''achat',
  `quantite` decimal(11,2) NOT NULL DEFAULT 0.00 COMMENT 'Incrément',
  `description` varchar(120) DEFAULT NULL COMMENT 'Commentaire',
  `saisie_par` varchar(25) NOT NULL COMMENT 'Opérateur',
  `club` tinyint(4) NOT NULL COMMENT 'Gestion multi-club',
  `type` int(11) NOT NULL DEFAULT 0 COMMENT 'Type de ticket',
  `vol` int(11) DEFAULT NULL COMMENT 'Vol associé',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=522 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC COMMENT='Tickets de remorqué ou treuillé';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `type_ticket`
--

DROP TABLE IF EXISTS `type_ticket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `type_ticket` (
  `id` int(11) NOT NULL COMMENT 'Identifiant',
  `nom` varchar(64) NOT NULL COMMENT 'Nom',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC COMMENT='Type de tickets';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `types_roles`
--

DROP TABLE IF EXISTS `types_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `types_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(64) NOT NULL,
  `description` varchar(128) DEFAULT NULL,
  `scope` enum('global','section') NOT NULL DEFAULT 'section',
  `is_system_role` tinyint(1) NOT NULL DEFAULT 0,
  `display_order` int(11) NOT NULL DEFAULT 100,
  `translation_key` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Type de rôle pour les section';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `use_new_authorization`
--

DROP TABLE IF EXISTS `use_new_authorization`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `use_new_authorization` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_autologin`
--

DROP TABLE IF EXISTS `user_autologin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_autologin` (
  `key_id` char(32) NOT NULL,
  `user_id` mediumint(8) NOT NULL DEFAULT 0,
  `user_agent` varchar(150) NOT NULL,
  `last_ip` varchar(40) NOT NULL,
  `last_login` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`key_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_profile`
--

DROP TABLE IF EXISTS `user_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `country` varchar(20) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=324 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_roles_per_section`
--

DROP TABLE IF EXISTS `user_roles_per_section`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_roles_per_section` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `types_roles_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `granted_by` int(11) DEFAULT NULL,
  `granted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `revoked_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `types_roles_id` (`types_roles_id`),
  KEY `user_id` (`user_id`),
  KEY `section_id` (`section_id`),
  KEY `fk_user_roles_granted_by` (`granted_by`),
  KEY `idx_user_section_active` (`user_id`,`section_id`,`revoked_at`),
  CONSTRAINT `fk_user_roles_granted_by` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `section_id` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `types_roles_id` FOREIGN KEY (`types_roles_id`) REFERENCES `types_roles` (`id`),
  CONSTRAINT `user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=804 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_temp`
--

DROP TABLE IF EXISTS `user_temp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_temp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(34) NOT NULL,
  `email` varchar(100) NOT NULL,
  `activation_key` varchar(50) NOT NULL,
  `last_ip` varchar(40) NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL DEFAULT 1,
  `username` varchar(25) NOT NULL,
  `password` varchar(34) NOT NULL,
  `email` varchar(100) NOT NULL,
  `banned` tinyint(1) NOT NULL DEFAULT 0,
  `ban_reason` varchar(255) DEFAULT NULL,
  `newpass` varchar(34) DEFAULT NULL,
  `newpass_key` varchar(32) DEFAULT NULL,
  `newpass_time` datetime DEFAULT NULL,
  `last_ip` varchar(40) NOT NULL,
  `last_login` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=322 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vols_decouverte`
--

DROP TABLE IF EXISTS `vols_decouverte`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vols_decouverte` (
  `id` int(20) NOT NULL AUTO_INCREMENT,
  `date_vente` date NOT NULL,
  `date_validite` date DEFAULT NULL,
  `club` tinyint(1) NOT NULL,
  `product` varchar(64) NOT NULL,
  `saisie_par` varchar(32) NOT NULL,
  `beneficiaire` varchar(64) DEFAULT NULL,
  `de_la_part` varchar(64) DEFAULT NULL,
  `occasion` varchar(64) DEFAULT NULL,
  `paiement` varchar(64) DEFAULT NULL,
  `participation` varchar(64) DEFAULT NULL,
  `beneficiaire_email` varchar(64) DEFAULT NULL,
  `beneficiaire_tel` varchar(64) DEFAULT NULL,
  `urgence` varchar(128) DEFAULT NULL,
  `date_planning` date DEFAULT NULL,
  `time_planning` time DEFAULT NULL,
  `date_vol` date DEFAULT NULL,
  `time_vol` time DEFAULT NULL,
  `pilote` varchar(64) DEFAULT NULL,
  `airplane_immat` varchar(10) DEFAULT NULL,
  `cancelled` tinyint(1) DEFAULT 0,
  `nb_personnes` tinyint(1) DEFAULT NULL,
  `prix` decimal(14,12) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=250114 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `volsa`
--

DROP TABLE IF EXISTS `volsa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `volsa` (
  `vaid` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant du vol',
  `vadate` date NOT NULL COMMENT 'Date',
  `vapilid` varchar(25) NOT NULL COMMENT 'Pilote',
  `vamacid` varchar(10) NOT NULL COMMENT 'Machine',
  `vacdeb` decimal(8,2) NOT NULL COMMENT 'Horamètre début',
  `vacfin` decimal(8,2) NOT NULL COMMENT 'Horamètre fin',
  `vaduree` decimal(8,2) NOT NULL COMMENT 'Durée du vol',
  `vaobs` varchar(200) DEFAULT NULL COMMENT 'Observations',
  `vadc` tinyint(1) NOT NULL COMMENT 'Double commande',
  `vacategorie` tinyint(1) NOT NULL COMMENT 'Catégorie',
  `varem` tinyint(4) DEFAULT NULL COMMENT 'Remorqué',
  `vanumvi` varchar(20) DEFAULT NULL COMMENT 'Numéro du vol d''initiation',
  `vanbpax` varchar(1) DEFAULT NULL COMMENT 'Nombre de passagers',
  `vaprixvol` decimal(6,2) DEFAULT NULL COMMENT 'Prix du vol',
  `vainst` varchar(25) DEFAULT NULL COMMENT 'Instructeur',
  `valieudeco` varchar(15) DEFAULT NULL COMMENT 'Lieu de décollage',
  `valieuatt` varchar(15) DEFAULT NULL COMMENT 'Lieu d''atterrissage',
  `facture` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Facturé',
  `payeur` varchar(25) DEFAULT NULL COMMENT 'Payeur si ce n''est pas le pilote',
  `pourcentage` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Pourcentage payé par le payeur, unité = 50%',
  `club` tinyint(1) DEFAULT 0 COMMENT 'Gestion multi-club',
  `gel` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Modification interdites',
  `saisie_par` varchar(25) DEFAULT NULL COMMENT 'Planchard',
  `vaatt` int(11) NOT NULL DEFAULT 1 COMMENT 'Nombre d''atterrissages',
  `local` tinyint(4) DEFAULT 0 COMMENT 'Eloignement',
  `nuit` tinyint(4) DEFAULT 0 COMMENT 'Vol de nuit',
  `reappro` tinyint(4) DEFAULT 0 COMMENT 'Ravitaillement',
  `essence` int(11) DEFAULT 0 COMMENT 'Quantité d''essence',
  `vahdeb` decimal(4,2) NOT NULL COMMENT 'Heure de décollage',
  `vahfin` decimal(4,2) NOT NULL COMMENT 'Heure d''atterrissage',
  PRIMARY KEY (`vaid`),
  KEY `vapilid` (`vapilid`),
  KEY `vamacid` (`vamacid`),
  KEY `saisie_par` (`saisie_par`)
) ENGINE=InnoDB AUTO_INCREMENT=2522 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `volsp`
--

DROP TABLE IF EXISTS `volsp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `volsp` (
  `vpid` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant',
  `vpdate` date NOT NULL COMMENT 'Date du vol',
  `vppilid` varchar(25) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Pilote',
  `vpmacid` varchar(10) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Planeur',
  `vpcdeb` decimal(4,2) NOT NULL COMMENT 'Heure début',
  `vpcfin` decimal(4,2) NOT NULL COMMENT 'Heure fin',
  `vpduree` decimal(8,2) NOT NULL COMMENT 'Durée en minutes',
  `vpobs` varchar(200) DEFAULT NULL COMMENT 'Observations',
  `vpdc` tinyint(1) NOT NULL COMMENT 'Double commande',
  `vpcategorie` tinyint(1) NOT NULL COMMENT 'Catégorie',
  `vpautonome` tinyint(4) DEFAULT 3 COMMENT 'Lancement',
  `vpnumvi` varchar(20) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL COMMENT 'Numéro de VI',
  `vpnbkm` int(11) DEFAULT 0 COMMENT 'Nombre de Km',
  `vplieudeco` varchar(15) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL COMMENT 'Lieu de décollage',
  `vplieuatt` varchar(15) DEFAULT NULL COMMENT 'Lieu d''atterrissage',
  `vpaltrem` int(11) DEFAULT 500 COMMENT 'Altitude de remorquage',
  `vpinst` varchar(25) DEFAULT NULL COMMENT 'Instructeur',
  `facture` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Vrai quand le vol a été facturé',
  `payeur` varchar(25) DEFAULT NULL COMMENT 'Payeur du vol quand ce n''est pas le pilote',
  `pourcentage` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Pourcentage du vol pour le payeur, unité = 50%',
  `club` tinyint(1) DEFAULT 0 COMMENT 'Gestion multi-club',
  `saisie_par` varchar(25) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL COMMENT 'Planchard',
  `remorqueur` varchar(10) DEFAULT NULL COMMENT 'Avion remorqueur',
  `pilote_remorqueur` varchar(25) DEFAULT NULL COMMENT 'Pilote remorqueur',
  `tempmoteur` decimal(6,2) DEFAULT 0.00 COMMENT 'Temps moteur',
  `reappro` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Ravitaillement',
  `essence` int(11) DEFAULT 0,
  `vpticcolle` tinyint(1) NOT NULL COMMENT 'Si ticket collé ou pas',
  PRIMARY KEY (`vpid`),
  KEY `saisie_par` (`saisie_par`),
  KEY `pilote_remorqueur` (`pilote_remorqueur`),
  KEY `remorqueur` (`remorqueur`)
) ENGINE=InnoDB AUTO_INCREMENT=9125 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-20 19:22:55
-- ========================================
-- Données de test minimales
-- ========================================

-- Migration version
INSERT INTO `migrations` (`version`) VALUES (57);

-- Membres de test (utilisateurs Gaulois)
INSERT INTO `membres` (`mlogin`, `mnom`, `mprenom`, `memail`, `memailparent`, `madresse`, `cp`, `ville`, `pays`, `mtelf`, `mtelm`, `mdaten`, `m25ans`, `mlieun`, `msexe`, `mniveaux`, `macces`, `club`, `ext`, `actif`, `username`, `photo`, `compte`, `comment`, `trigramme`, `categorie`, `profession`, `inst_glider`, `inst_airplane`, `licfed`) VALUES
('abraracourcix', 'Le Gaulois', 'Abraracourcix', 'abraracourcix@flub78.net', '', '1 rue des menhirs', 0, '', '', '', '', NULL, 0, '0', 'M', 8192, 0, 0, 0, 1, '0', '', 0, 'abraracourcix', '', '0', '', '', '', 0),
('asterix', 'Le Gaulois', 'Asterix', 'asterix@flub78.net', '', '1 rue des menhirs', 0, '', '', '', '', NULL, 0, '0', 'M', 524288, 0, 0, 0, 1, '0', '', 0, 'asterix', '', '0', '', '', '', 0),
('goudurix', 'Le Gaulois', 'Goudurix', 'goudurix@flub78.net', '', '1 rue des menhirs', 0, '', '', '', '', NULL, 0, '0', 'M', 0, 0, 0, 0, 1, '0', '', 0, 'goudurix', '', '0', '', '', '', 0),
('panoramix', 'Le Gaulois', 'Panoramix', 'panoramix@flub78.net', '', '1 rue des menhirs', 0, '', '', '', '', NULL, 0, '0', 'M', 491520, 0, 0, 0, 1, '0', '', 0, 'panoramix', '', '0', '', '', '', 0);

-- Permissions
INSERT INTO `permissions` (`id`, `role_id`, `data`) VALUES
(1, 1, 'a:1:{s:3:"uri";a:22:{i:0;s:8:"/membre/";i:1;s:14:"/planeur/page/";i:2;s:12:"/avion/page/";i:3;s:12:"/vols_avion/";i:4;s:14:"/vols_planeur/";i:5;s:19:"/rapports/licences/";i:6;s:19:"/compta/mon_compte/";i:7;s:23:"/compta/journal_compte/";i:8;s:25:"/compta/filterValidation/";i:9;s:12:"/compta/pdf/";i:10;s:15:"/compta/export/";i:11;s:17:"/compta/new_year/";i:12;s:18:"/comptes/new_year/";i:13;s:17:"/achats/new_year/";i:14;s:14:"/tickets/page/";i:15;s:13:"/event/stats/";i:16;s:12:"/event/page/";i:17;s:17:"/event/formation/";i:18;s:11:"/event/fai/";i:19;s:11:"/presences/";i:20;s:10:"/licences/";i:21;s:9:"/welcome/";}}'),
(2, 7, 'a:1:{s:3:"uri";a:3:{i:0;s:12:"/vols_avion/";i:1;s:14:"/vols_planeur/";i:2;s:0:"";}}'),
(3, 9, 'a:1:{s:3:"uri";a:8:{i:0;s:10:"/factures/";i:1;s:8:"/compta/";i:2;s:9:"/comptes/";i:3;s:10:"/remorque/";i:4;s:16:"/plan_comptable/";i:5;s:11:"/categorie/";i:6;s:8:"/tarifs/";i:7;s:0:"";}}'),
(4, 8, 'a:1:{s:3:"uri";a:20:{i:0;s:8:"/membre/";i:1;s:9:"/planeur/";i:2;s:7:"/avion/";i:3;s:12:"/vols_avion/";i:4;s:14:"/vols_planeur/";i:5;s:10:"/factures/";i:6;s:8:"/compta/";i:7;s:8:"/compta/";i:8;s:8:"/compta/";i:9;s:9:"/comptes/";i:10;s:9:"/tickets/";i:11;s:7:"/event/";i:12;s:10:"/rapports/";i:13;s:10:"/licences/";i:14;s:8:"/achats/";i:15;s:10:"/terrains/";i:16;s:7:"/admin/";i:17;s:9:"/reports/";i:18;s:7:"/mails/";i:19;s:12:"/historique/";}}'),
(5, 3, 'a:1:{s:3:"uri";a:2:{i:0;s:23:"/compta/journal_compte/";i:1;s:13:"/compta/view/";}}'),
(6, 2, 'a:1:{s:3:"uri";a:32:{i:0;s:8:"/membre/";i:1;s:9:"/planeur/";i:2;s:7:"/avion/";i:3;s:17:"/vols_avion/page/";i:4;s:29:"/vols_avion/filterValidation/";i:5;s:16:"/vols_avion/pdf/";i:6;s:23:"/vols_avion/statistics/";i:7;s:21:"/vols_avion/new_year/";i:8;s:19:"/vols_planeur/page/";i:9;s:24:"/vols_planeur/statistic/";i:10;s:31:"/vols_planeur/filterValidation/";i:11;s:18:"/vols_planeur/pdf/";i:12;s:24:"/vols_planeur/pdf_month/";i:13;s:26:"/vols_planeur/pdf_machine/";i:14;s:25:"/vols_planeur/export_per/";i:15;s:21:"/vols_planeur/export/";i:16;s:23:"/vols_planeur/new_year/";i:17;s:19:"/factures/en_cours/";i:18;s:15:"/factures/page/";i:19;s:15:"/factures/view/";i:20;s:21:"/factures/ma_facture/";i:21;s:19:"/compta/mon_compte/";i:22;s:23:"/compta/journal_compte/";i:23;s:25:"/compta/filterValidation/";i:24;s:12:"/compta/pdf/";i:25;s:17:"/compta/new_year/";i:26;s:18:"/comptes/new_year/";i:27;s:14:"/tickets/page/";i:28;s:13:"/event/stats/";i:29;s:12:"/event/page/";i:30;s:17:"/event/formation/";i:31;s:11:"/event/fai/";}}');

-- Roles
INSERT INTO `roles` (`id`, `parent_id`, `name`) VALUES
(1, 0, 'membre'),
(2, 9, 'admin'),
(3, 8, 'bureau'),
(7, 1, 'planchiste'),
(8, 7, 'ca'),
(9, 3, 'tresorier');

-- Sections
INSERT INTO `sections` (`id`, `nom`, `description`) VALUES
(1, 'Planeur', 'Section planeur de l\'aéroclub d\'Abbeville');

-- Types de rôles
INSERT INTO `types_roles` (`id`, `nom`, `description`) VALUES
(1, 'user', 'Capacity to login and see user data'),
(2, 'auto_planchiste', 'Capacity to create, modify and delete the user own data'),
(5, 'planchiste', 'Authorization to create, modify and delete flight data'),
(6, 'ca', 'capacity to see all data for a section including global financial data'),
(7, 'bureau', 'capacity to see all data for a section including personnal financial data'),
(8, 'tresorier', 'Capacity to edit financial data for one section'),
(9, 'super-tresorier', 'Capacity to see an edit financial data for all sections'),
(10, 'club-admin', 'capacity to access all data and change everything');

-- Type de tickets
INSERT INTO `type_ticket` (`id`, `nom`) VALUES
(0, 'Remorqué'),
(1, 'treuillé');

-- Utilisateurs de test (login=username, password=username)
INSERT INTO `users` (`id`, `role_id`, `username`, `password`, `email`, `banned`, `ban_reason`, `newpass`, `newpass_key`, `newpass_time`, `last_ip`, `last_login`, `created`, `modified`) VALUES
(15, 1, 'testuser', '$1$wu3.3t2.$Wgk43dHPPi3PTv5atdpnz0', 'testuser@free.fr', 0, NULL, NULL, NULL, NULL, '::1', '2023-06-17 06:34:23', '2011-04-21 15:21:13', '2023-06-17 04:34:23'),
(16, 2, 'testadmin', '$1$uM1.f95.$AnUHH1W/xLS9fxDbt8RPo0', 'frederic.peignot@free.fr', 0, NULL, NULL, NULL, NULL, '127.0.0.1', '2025-02-19 16:21:28', '2011-04-21 15:21:40', '2025-02-19 15:21:28'),
(58, 7, 'testplanchiste', '$1$DT0.QJ1.$yXqRz6gf/jWC4MzY2D05Y.', 'testplanchiste@free.fr', 0, NULL, NULL, NULL, NULL, '::1', '2023-06-17 06:30:44', '2012-01-25 21:00:23', '2023-06-17 04:30:44'),
(59, 8, 'testca', '$1$9h..cY3.$NzkeKkCoSa2oxL7bQCq4v1', 'testca@free.fr', 0, NULL, NULL, NULL, NULL, '127.0.0.1', '0000-00-00 00:00:00', '2012-01-25 21:00:58', '2014-12-23 20:38:30'),
(60, 3, 'testbureau', '$1$NC0.SN5.$qwnSUxiPbyh6v2JrhA1fH1', 'testbureau@free.fr', 0, NULL, NULL, NULL, NULL, '127.0.0.1', '2012-01-25 21:03:01', '2012-01-25 21:01:36', '2014-12-23 20:39:00'),
(61, 9, 'testtresorier', '$1$KiPMl0ho$/E3NBaprpM5Xcv.z40zjK0', 'testresorier@free.fr', 0, NULL, NULL, NULL, NULL, '127.0.0.1', '0000-00-00 00:00:00', '2012-01-25 21:02:36', '2012-01-25 20:02:36'),
(118, 1, 'asterix', '$1$178.XGif$uv3FdWy4uSb4hURObhQaU1', 'asterix@flub78.net', 0, NULL, NULL, NULL, NULL, '::1', '1900-01-01 00:00:00', '2023-06-17 06:32:07', '2023-06-17 04:32:07'),
(119, 1, 'goudurix', '$1$TgWj4h2S$O.t2stMILkVwqeV5xC/Ky.', 'goudurix@flub78.net', 0, NULL, NULL, NULL, NULL, '::1', '1900-01-01 00:00:00', '2023-06-17 06:32:11', '2023-06-17 04:32:11'),
(120, 1, 'panoramix', '$1$Ih02twmD$BnsuIlxHH62qF41/puKs30', 'panoramix@flub78.net', 0, NULL, NULL, NULL, NULL, '::1', '1900-01-01 00:00:00', '2023-06-17 06:32:16', '2023-06-17 04:32:16'),
(121, 1, 'abraracourcix', '$1$B0U6TBCD$Mcx76FTA.ulT.TO.sX2HZ1', 'abraracourcix@flub78.net', 0, NULL, NULL, NULL, NULL, '::1', '1900-01-01 00:00:00', '2023-06-17 06:32:20', '2023-06-17 04:32:20');

-- Profils utilisateurs
INSERT INTO `user_profile` (`id`, `user_id`, `country`, `website`) VALUES
(120, 118, NULL, NULL),
(121, 119, NULL, NULL),
(122, 120, NULL, NULL),
(123, 121, NULL, NULL);

-- Rôles par section
INSERT INTO `user_roles_per_section` (`id`, `user_id`, `types_roles_id`, `section_id`) VALUES
(1, 15, 1, 1),
(2, 16, 10, 1),
(3, 58, 5, 1),
(4, 59, 6, 1),
(5, 60, 7, 1),
(6, 61, 8, 1),
(7, 118, 1, 1),
(8, 119, 1, 1),
(9, 120, 1, 1),
(10, 121, 1, 1);

-- Plan comptable de base
INSERT INTO `planc` (`pcode`, `pdesc`) VALUES
('102', 'Fonds associatif (sans droit de reprise)'),
('110', 'Report à nouveau (solde créditeur)'),
('119', 'Report à nouveau (solde débiteur)'),
('120', 'Résultat de l\'exercice (excédent)'),
('129', 'Résultat de l\'exercice (déficit)'),
('164', 'Emprunts auprès des établissements de crédit'),
('215', 'Matériel'),
('218', 'Mobilier.'),
('281', 'Amortissement des immobilisations corporelles'),
('371', 'Marchandises'),
('401', 'Fournisseurs'),
('409', 'Fournisseurs débiteurs. Accomptes'),
('411', 'Clients'),
('441', 'Etat - Subventions'),
('46', 'Débiteurs divers et créditeur divers'),
('487', 'Produits constatés d\'avance'),
('512', 'Banque'),
('531', 'Caisse'),
('60', 'Achats'),
('601', 'Achats stockés - Matières premières et fournitures'),
('602', 'Achats stockés - Autres approvisionements'),
('604', 'Achats d\'études et prestations de services'),
('605', 'Achat autres.'),
('606', 'Achats non stockés de matières et fournitures'),
('607', 'Achats de marchandises'),
('61', 'Services extérieurs'),
('611', 'Sous-traitance générale'),
('612', 'Redevances de crédit-bail'),
('613', 'Locations'),
('615', 'Entretien et réparations'),
('616', 'Assurances'),
('62', 'Autres services extérieurs'),
('621', 'Personels extérieur à l\'association'),
('622', 'Rémunérations et Honoraires.'),
('623', 'Publicité, Publications, Relations publiques'),
('624', 'Transport de bien et transport collectif du person'),
('625', 'Déplacement, missions et reception'),
('626', 'Frais postaux et télécommunications'),
('628', 'Divers, cotisations'),
('629', 'Rabais, ristournes, remises sur services extérieur'),
('63', 'Impôts et Taxes'),
('631', 'Impots sur rémunération'),
('635', 'Autres impôts et Taxes.'),
('64', 'Charges de Personnel'),
('65', 'Autres Charges de gestion courante'),
('651', 'Redevance pour concessions, brevets'),
('654', 'Pertes sur créances irrécouvrables'),
('657', 'Subventions versées par l\'association'),
('66', 'Charges financières'),
('67', 'Chages Exceptionnelles'),
('674', 'Autres.'),
('678', 'Autres charges exceptionnelles'),
('68', 'Dotation aux Amortissements'),
('70', 'Ventes'),
('701', 'Ventes de produits finis'),
('706', 'Prestations de services'),
('707', 'Ventes de marchandises'),
('708', 'Produit des activités annexes'),
('74', 'Subventions d\'exploitation'),
('75', 'Autres produits de gestion courante'),
('753', 'Assurances licences FFVV.'),
('754', 'Retour des Fédérations (bourses).'),
('756', 'Cotisations'),
('76', 'Produits financiers'),
('774', 'Autres produits exceptionnels'),
('775', 'Produits des cessions d\'éléments d\'actif'),
('778', 'Autres produits exceptionnels'),
('78', 'Reprise sur amortissements'),
('781', 'Reprises sur amortissements et provisions');

-- Comptes de test
INSERT INTO `comptes` (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES
(292, 'Immobilisations', '', 'Immobilisations', '215', 1, 0.00, 0.00, 'testadmin', 1),
(293, 'Fonds associatifs', '', 'Fonds associatifs', '102', 1, 0.00, 0.00, 'testadmin', 1),
(294, 'Banque', '', 'Banque', '512', 1, 850.47, 152.63, 'testadmin', 1),
(295, 'Emprunt', '', 'Emprunt', '164', 1, 0.00, 0.00, 'testadmin', 1),
(296, 'Atelier de la Somme', '', 'Fournisseur', '401', 1, 350.00, 350.00, 'testadmin', 1),
(297, 'Frais de bureau', '', 'Frais de bureau', '606', 1, 25.50, 0.00, 'testadmin', 1),
(298, 'Essence plus huile', '', 'Essence plus huile', '606', 1, 125.50, 0.00, 'testadmin', 1),
(299, 'Entretien', '', 'Entretien', '615', 1, 350.00, 350.00, 'testadmin', 1),
(300, 'Assurances', '', 'Assurances', '616', 1, 0.00, 0.00, 'testadmin', 1),
(301, 'Heures de vol planeur', '', 'Heures de vol planeur', '706', 1, 0.00, 0.00, 'testadmin', 1),
(302, 'Heures de vol avion', '', 'Heures de vol avion', '706', 1, 0.00, 0.00, 'testadmin', 1),
(303, 'Heures de vol ULM', '', 'Heures de vol ULM', '706', 1, 0.00, 0.00, 'testadmin', 1),
(304, 'Subventions', '', 'Subventions', '74', 1, 0.00, 0.00, 'testadmin', 1),
(305, '(411) Test User', 'testuser', '', '411', 1, 0.00, 0.00, 'testadmin', 1),
(306, '(411) Test Admin', 'testadmin', '', '411', 1, 0.00, 0.00, 'testadmin', 1),
(307, '(411) Test CA', 'testca', '', '411', 1, 0.00, 0.00, 'testadmin', 1),
(308, '(411) Test Bureau', 'testbureau', '', '411', 1, 0.00, 0.00, 'testadmin', 1),
(309, 'Boutique', '', 'Boutique', '707', 1, 0.00, 0.00, 'testadmin', 1);

-- Planeurs de test
INSERT INTO `machinesp` (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES
('Alexander Schleicher', 'Ask21', 'F-CGAA', '', 0.00, '2', 0, 0, 0, 1, 'hdv-planeur', 'hdv-planeur-forfait', 'gratuit', 180, 1, '', 0, 0, 0, ''),
('Centrair', 'Pégase', 'F-CGAB', 'EG', 0.00, '1', 0, 0, 0, 1, 'hdv-planeur', 'hdv-planeur-forfait', 'gratuit', 180, 1, '', 0, 0, 0, ''),
('DG', 'DG800', 'F-CGAC', 'AC', 0.00, '1', 0, 0, 0, 1, 'gratuit', 'gratuit', 'gratuit', 180, 1, '', 0, 0, 0, '');

-- Avions remorqueurs de test
INSERT INTO `machinesa` (`macconstruc`, `macmodele`, `macimmat`, `macnbhdv`, `macplaces`, `macrem`, `maprive`, `club`, `actif`, `comment`, `maprix`, `maprixdc`, `horametre_en_minutes`, `fabrication`) VALUES
('Robin', 'DR400', 'F-GUFB', 0.00, 4, 1, 0, 1, 1, '', 'gratuit', 'gratuit', 0, 0),
('Aeropol', 'Dynamic', 'F-JUFA', 0.00, 2, 1, 0, 1, 1, '', 'hdv-ULM', 'hdv-ULM', 0, 0);

-- Terrains
INSERT INTO `terrains` (`oaci`, `nom`, `freq1`, `freq2`, `comment`) VALUES
('LFAY', 'Amiens Glisy', 123.400, 0.000, ''),
('LFEG', 'Argenton Sur Creuse', 123.500, 0.000, ''),
('LFJR', 'Angers', 0.000, 0.000, ''),
('LFLV', 'Vichy', 121.400, 0.000, '253m'),
('LFNC', 'montdauphin saint crepin', 123.500, 123.050, 'alt 903 m'),
('LFOI', 'Abbeville', 123.500, 0.000, ''),
('LFON', 'Dreux', 123.500, 0.000, ''),
('LFQB', 'Troyes - Barberey', 123.725, 0.000, ''),
('LFQO', 'Lille - Marq en Bareuil', 0.000, 0.000, ''),
('LFRI', 'la Roche sur Yon', 0.000, 0.000, ''),
('LFYG', 'Cambrai', 999.999, 0.000, ''),
('LFYR', 'Romorantin', 119.070, 0.000, '');
