-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : mer. 19 fév. 2025 à 15:27
-- Version du serveur : 10.11.8-MariaDB-0ubuntu0.24.04.1
-- Version de PHP : 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gvv2`
--

-- --------------------------------------------------------

--
-- Structure de la table `achats`
--

CREATE TABLE `achats` (
  `id` int(11) NOT NULL COMMENT 'Identifiant',
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
  `num_cheque` varchar(50) DEFAULT NULL COMMENT 'Numéro de pièce comptable'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Lignes de factures';

--
-- Déchargement des données de la table `achats`
--

INSERT INTO `achats` (`id`, `date`, `produit`, `quantite`, `prix`, `description`, `pilote`, `facture`, `saisie_par`, `club`, `machine`, `vol_planeur`, `vol_avion`, `mvt_pompe`, `num_cheque`) VALUES
(7217, '2023-06-17', 'hdv-ULM', 0.50, 102.00, '17/06/2023 100.00 F-JUFA', 'asterix', 0, 'testadmin', 1, 'F-JUFA', NULL, 768, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `attachments`
--

CREATE TABLE `attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `referenced_table` varchar(128) DEFAULT NULL,
  `referenced_id` varchar(128) DEFAULT NULL,
  `user_id` varchar(25) DEFAULT NULL,
  `filename` varchar(128) DEFAULT NULL,
  `description` varchar(124) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `categorie`
--

CREATE TABLE `categorie` (
  `id` int(11) NOT NULL COMMENT 'Identifiant',
  `nom` varchar(32) NOT NULL COMMENT 'Nom',
  `description` varchar(80) DEFAULT NULL COMMENT 'Commentaire',
  `parent` int(11) NOT NULL DEFAULT 0 COMMENT 'Catégorie parente',
  `type` int(11) NOT NULL DEFAULT 0 COMMENT 'Type de catégorie'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='Catégorie d écritures pour comptabilité analytique';

-- --------------------------------------------------------

--
-- Structure de la table `ci_sessions`
--

CREATE TABLE `ci_sessions` (
  `session_id` varchar(40) NOT NULL DEFAULT '0',
  `ip_address` varchar(16) NOT NULL DEFAULT '0',
  `user_agent` varchar(150) NOT NULL,
  `last_activity` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `user_data` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--
-- Déchargement des données de la table `ci_sessions`
--

INSERT INTO `ci_sessions` (`session_id`, `ip_address`, `user_agent`, `last_activity`, `user_data`) VALUES
('0abcd0fb4bd502fb5836391bdfd9b69f', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/114.0.5735.134 Safari/53', 1686976185, 'a:1:{s:9:\"user_data\";s:0:\"\";}'),
('a2f8e5a14c11efeba9a2c9457131e437', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36', 1739978484, 'a:16:{s:9:\"user_data\";s:0:\"\";s:16:\"section_selector\";a:5:{i:3;s:5:\"Avion\";i:4;s:9:\"Général\";i:1;s:7:\"Planeur\";i:2;s:3:\"ULM\";i:5;s:6:\"Toutes\";}s:10:\"DX_user_id\";s:2:\"16\";s:11:\"DX_username\";s:9:\"testadmin\";s:10:\"DX_role_id\";s:1:\"2\";s:12:\"DX_role_name\";s:5:\"admin\";s:18:\"DX_parent_roles_id\";a:5:{i:0;s:1:\"9\";i:1;s:1:\"3\";i:2;s:1:\"8\";i:3;s:1:\"7\";i:4;s:1:\"1\";}s:20:\"DX_parent_roles_name\";a:5:{i:0;s:9:\"tresorier\";i:1;s:6:\"bureau\";i:2;s:2:\"ca\";i:3;s:10:\"planchiste\";i:4;s:6:\"membre\";}s:13:\"DX_permission\";a:1:{s:3:\"uri\";a:32:{i:0;s:8:\"/membre/\";i:1;s:9:\"/planeur/\";i:2;s:7:\"/avion/\";i:3;s:17:\"/vols_avion/page/\";i:4;s:29:\"/vols_avion/filterValidation/\";i:5;s:16:\"/vols_avion/pdf/\";i:6;s:23:\"/vols_avion/statistics/\";i:7;s:21:\"/vols_avion/new_year/\";i:8;s:19:\"/vols_planeur/page/\";i:9;s:24:\"/vols_planeur/statistic/\";i:10;s:31:\"/vols_planeur/filterValidation/\";i:11;s:18:\"/vols_planeur/pdf/\";i:12;s:24:\"/vols_planeur/pdf_month/\";i:13;s:26:\"/vols_planeur/pdf_machine/\";i:14;s:25:\"/vols_planeur/export_per/\";i:15;s:21:\"/vols_planeur/export/\";i:16;s:23:\"/vols_planeur/new_year/\";i:17;s:19:\"/factures/en_cours/\";i:18;s:15:\"/factures/page/\";i:19;s:15:\"/factures/view/\";i:20;s:21:\"/factures/ma_facture/\";i:21;s:19:\"/compta/mon_compte/\";i:22;s:23:\"/compta/journal_compte/\";i:23;s:25:\"/compta/filterValidation/\";i:24;s:12:\"/compta/pdf/\";i:25;s:17:\"/compta/new_year/\";i:26;s:18:\"/comptes/new_year/\";i:27;s:14:\"/tickets/page/\";i:28;s:13:\"/event/stats/\";i:29;s:12:\"/event/page/\";i:30;s:17:\"/event/formation/\";i:31;s:11:\"/event/fai/\";}}s:21:\"DX_parent_permissions\";a:5:{i:1;a:1:{s:3:\"uri\";a:22:{i:0;s:8:\"/membre/\";i:1;s:14:\"/planeur/page/\";i:2;s:12:\"/avion/page/\";i:3;s:12:\"/vols_avion/\";i:4;s:14:\"/vols_planeur/\";i:5;s:19:\"/rapports/licences/\";i:6;s:19:\"/compta/mon_compte/\";i:7;s:23:\"/compta/journal_compte/\";i:8;s:25:\"/compta/filterValidation/\";i:9;s:12:\"/compta/pdf/\";i:10;s:15:\"/compta/export/\";i:11;s:17:\"/compta/new_year/\";i:12;s:18:\"/comptes/new_year/\";i:13;s:17:\"/achats/new_year/\";i:14;s:14:\"/tickets/page/\";i:15;s:13:\"/event/stats/\";i:16;s:12:\"/event/page/\";i:17;s:17:\"/event/formation/\";i:18;s:11:\"/event/fai/\";i:19;s:11:\"/presences/\";i:20;s:10:\"/licences/\";i:21;s:9:\"/welcome/\";}}i:2;a:1:{s:3:\"uri\";a:3:{i:0;s:12:\"/vols_avion/\";i:1;s:14:\"/vols_planeur/\";i:2;s:0:\"\";}}i:3;a:1:{s:3:\"uri\";a:8:{i:0;s:10:\"/factures/\";i:1;s:8:\"/compta/\";i:2;s:9:\"/comptes/\";i:3;s:10:\"/remorque/\";i:4;s:16:\"/plan_comptable/\";i:5;s:11:\"/categorie/\";i:6;s:8:\"/tarifs/\";i:7;s:0:\"\";}}i:4;a:1:{s:3:\"uri\";a:20:{i:0;s:8:\"/membre/\";i:1;s:9:\"/planeur/\";i:2;s:7:\"/avion/\";i:3;s:12:\"/vols_avion/\";i:4;s:14:\"/vols_planeur/\";i:5;s:10:\"/factures/\";i:6;s:8:\"/compta/\";i:7;s:8:\"/compta/\";i:8;s:8:\"/compta/\";i:9;s:9:\"/comptes/\";i:10;s:9:\"/tickets/\";i:11;s:7:\"/event/\";i:12;s:10:\"/rapports/\";i:13;s:10:\"/licences/\";i:14;s:8:\"/achats/\";i:15;s:10:\"/terrains/\";i:16;s:7:\"/admin/\";i:17;s:9:\"/reports/\";i:18;s:7:\"/mails/\";i:19;s:12:\"/historique/\";}}i:5;a:1:{s:3:\"uri\";a:2:{i:0;s:23:\"/compta/journal_compte/\";i:1;s:13:\"/compta/view/\";}}}s:12:\"DX_logged_in\";b:1;s:13:\"filter_active\";i:1;s:9:\"filter_25\";i:0;s:19:\"filter_membre_actif\";i:2;s:20:\"filter_machine_actif\";i:2;s:7:\"section\";s:1:\"3\";}');

-- --------------------------------------------------------

--
-- Structure de la table `comptes`
--

CREATE TABLE `comptes` (
  `id` int(11) NOT NULL COMMENT 'Identifiant',
  `nom` varchar(48) NOT NULL COMMENT 'Nom du compte',
  `pilote` varchar(25) DEFAULT NULL COMMENT 'Référence du pilote',
  `desc` varchar(80) DEFAULT NULL COMMENT 'Description',
  `codec` varchar(10) NOT NULL COMMENT 'Code comptable',
  `actif` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Actif = 1, passif = 0',
  `debit` decimal(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Débit',
  `credit` decimal(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Crédit',
  `saisie_par` varchar(25) NOT NULL COMMENT 'Créateur',
  `club` tinyint(1) DEFAULT 0 COMMENT 'Gestion multi-club'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `comptes`
--

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
(302, 'Remorqués', '', 'Remorqués', '706', 1, 0.00, 23.00, 'testadmin', 1),
(303, 'Heures de vol ULM', '', 'Heures de vol ULM', '706', 1, 0.00, 51.00, 'testadmin', 1),
(304, 'Subventions', '', 'Subventions', '74', 1, 0.00, 500.00, 'testadmin', 1),
(305, 'Le Gaulois Asterix', 'asterix', 'Compte pilote', '411', 1, 51.00, 100.00, 'testadmin', 1),
(306, 'Le Gaulois Goudurix', 'goudurix', 'Compte pilote', '411', 1, 50.13, 250.47, 'testadmin', 1),
(307, 'Le Gaulois Panoramix', 'panoramix', 'Compte pilote', '411', 1, 0.00, 25.50, 'testadmin', 1),
(308, 'Le Gaulois Abraracourcix', 'abraracourcix', 'Compte pilote', '411', 1, 0.00, 0.00, 'testadmin', 1),
(309, 'Ventes diverses', '', 'Ventes goodies', '707', 1, 0.00, 0.00, 'testadmin', 1),
(310, 'Le Gaulois Asterix', 'asterix', 'Compte pilote ULM', '411', 1, 0.00, 0.00, 'testadmin', 2);

-- Création des comptes du compte ULM, club = 2
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES (343, 'Fonds associatifs ULM', 'Fonds associatifs ULM', '102', '2');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES (311, 'Report à nouveau créditeur ULM', 'Report à nouveau créditeur', '110', '2');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES (312, 'Report à nouveau débiteur ULM', 'Report à nouveau débiteur', '119', '2');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES (313, "Résultat de l'exercice ULM (excédent)", "Résultat de l'exercice (excédent)", '120', '2');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES (314, "Résultat de l'exercice ULM (déficit)", "Résultat de l'exercice (déficit)", '129', '2');

INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES (315, 'Salaires', 'Salaires', '421', '2');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES (316, 'URSSAF', 'URSSAF', '645', '2');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES (317, 'Taxes atterrissage', 'Taxes atterrissage', '651', '2');

INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES ('318', 'Vols de découverte', 'Vols de découverte', '706', '2');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES ('319', 'Compte général', 'Compte général', '181', '2');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES ('320', 'Assurances ULM', 'Assurances ULM', '616', '2');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES ('321', 'Maintenance F-JTVA', 'Maintenance Nynja F-JTVA', '615', '2');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES ('322', 'Maintenance F-JHRV', 'Maintenance CTL F-JHRV', '615', '2');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES ('323', 'Essence F-JTVA', 'Essence Nynja F-JTVA', '606', '2');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES ('324', 'Essence F-JHRV', 'Essence CTL F-JHRV', '606', '2');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES ('325', 'Emprunts CTL', 'Emprunts CTL', '164', '2');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES ('326', 'Heures de vol F-JTVA', 'Heures de vol F-JTVA', '706', '2');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES ('327', 'Heures de vol F-JHRV', 'Heures de vol F-JHRV', '706', '2');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES ('328', 'Intérêts placements', 'Interêts placements', '762', '2');


INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES ('329', 'Subventions', 'Subventions', '74', '2');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES ('330', 'Immobilisation F-JTVA', 'Immobilisation F-JTVA', '215', '2');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES ('331', 'Immobilisation F-JHRV', 'Immobilisation F-JHRV', '215', '2');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES ('332', 'Ventes de produits finis', 'Ventes de produits finis', '701', '2');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES ('333', 'Indemnités assurance F-JHRV', 'Indemnités assurance F-JHRV', '758', '2');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES ('334', 'versement A Fauquembergue', 'versement A Fauquembergue', '792', '2');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES ('335', 'Contributions versées', 'Contributions au compte général', '657', '2');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES ('336', 'Rémunérations du personnel', 'Rémunérations du personnel', '641', '2');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES ('337', 'Contributions reçues', 'Excédents du compte général', '757', '2');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES ('338', 'Compte courant SG ULM', 'FR76 3000 3028 4600 2500 3463 153', '512', '2');

INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES (339, 'Report à nouveau créditeur ULM', 'Report à nouveau créditeur', '110', '1');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES (340, 'Report à nouveau débiteur ULM', 'Report à nouveau débiteur', '119', '1');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES (341, "Résultat de l'exercice ULM (excédent)", "Résultat de l'exercice (excédent)", '120', '1');
INSERT INTO `comptes` (`id`, `nom`, `desc`, `codec`, `club`) VALUES (342, "Résultat de l'exercice ULM (déficit)", "Résultat de l'exercice (déficit)", '129', '1');

-- --------------------------------------------------------

--
-- Structure de la table `ecritures`
--

CREATE TABLE `ecritures` (
  `id` int(11) NOT NULL COMMENT 'Identifiant',
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
  `categorie` int(11) NOT NULL DEFAULT 0 COMMENT 'Catégorie de dépense ou recette'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--
-- Déchargement des données de la table `ecritures`
--

INSERT INTO `ecritures` (`id`, `annee_exercise`, `date_creation`, `date_op`, `compte1`, `compte2`, `montant`, `description`, `type`, `num_cheque`, `saisie_par`, `gel`, `club`, `achat`, `quantite`, `prix`, `categorie`) VALUES
(8861, 2023, '2023-06-17', '2023-06-17', 294, 305, 100.00, 'Avance sur vols', 0, 'AV-1', 'testadmin', 0, 1, 0, '0', 0.00, 0),
(8862, 2023, '2023-06-17', '2023-06-17', 294, 306, 250.47, 'Avance avec décimals', 0, 'Petites pièces', 'testadmin', 0, 1, 0, '0', 0.00, 0),
(8863, 2023, '2023-06-17', '2023-06-17', 306, 302, 23.00, 'Facturation manuelle de remorqués', 0, 'Facture d\'un autre club', 'testadmin', 0, 1, 0, '0', 0.00, 0),
(8864, 2023, '2023-06-17', '2023-06-17', 294, 304, 500.00, 'Subvention d\'aide à la formation', 0, 'Relevé CDN', 'testadmin', 0, 1, 0, '0', 0.00, 0),
(8865, 2023, '2023-06-17', '2023-06-17', 296, 299, 350.00, 'Trop perçu sur facture', 0, 'Facture 4712', 'testadmin', 0, 1, 0, '0', 0.00, 0),
(8866, 2023, '2023-06-17', '2023-06-17', 298, 294, 125.50, 'Achat d\'essence', 0, 'Chèque 413', 'testadmin', 0, 1, 0, '0', 0.00, 0),
(8867, 2023, '2023-06-17', '2023-06-17', 297, 307, 25.50, 'Remboursement fournitures de bureau', 0, 'Facture XX78', 'testadmin', 0, 1, 0, '0', 0.00, 0),
(8868, 2023, '2023-06-17', '2023-06-17', 306, 294, 27.13, 'Remboursement de solde pilote', 0, 'Chèque CDN1027', 'testadmin', 0, 1, 0, '0', 0.00, 0),
(8869, 2023, '2023-06-17', '2023-06-17', 299, 296, 350.00, 'Utilisation avoir fournisseur', 0, 'Facture 4712', 'testadmin', 0, 1, 0, '0', 0.00, 0),
(8870, 0, '2023-06-17', '2023-06-17', 305, 303, 51.00, '17/06/2023 100.00 F-JUFA', 0, 'hdv-ULM', 'testadmin', 0, 1, 7217, '0.5', 102.00, 0);

-- --------------------------------------------------------

--
-- Structure de la table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `emlogin` varchar(25) NOT NULL,
  `etype` int(11) NOT NULL,
  `edate` date DEFAULT NULL,
  `evaid` double DEFAULT NULL,
  `evpid` double DEFAULT NULL,
  `ecomment` varchar(128) DEFAULT NULL COMMENT 'Commentaire',
  `year` int(11) DEFAULT NULL COMMENT 'Année',
  `date_expiration` date DEFAULT NULL COMMENT 'date expiration'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

-- --------------------------------------------------------

--
-- Structure de la table `events_types`
--

CREATE TABLE `events_types` (
  `id` int(11) NOT NULL COMMENT 'Numéro',
  `name` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `activite` tinyint(2) NOT NULL DEFAULT 0 COMMENT 'Activité associée',
  `en_vol` tinyint(4) NOT NULL COMMENT 'Associé à un vol',
  `multiple` tinyint(1) DEFAULT NULL COMMENT 'Multiple',
  `expirable` tinyint(1) DEFAULT NULL COMMENT 'a une date d_expiration',
  `ordre` tinyint(2) DEFAULT NULL COMMENT 'ordre d_affichage',
  `annual` tinyint(1) DEFAULT NULL COMMENT 'Evénement annuel'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--
-- Déchargement des données de la table `events_types`
--

INSERT INTO `events_types` (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES
(14, 'Déclaration début formation', 1, 0, 0, 0, 2, NULL),
(15, 'Laché planeur', 1, 1, 0, 0, 4, NULL),
(16, 'Vol 1h', 1, 1, 0, 0, 5, NULL),
(17, 'Vol 5h', 4, 1, 0, 0, 2, NULL),
(18, 'Gain de 1000m', 4, 1, 0, 0, 1, NULL),
(19, 'Gain de 3000m', 4, 1, 0, 0, 5, NULL),
(20, 'Gain de 5000m', 4, 1, 0, 0, 8, NULL),
(21, 'Distance de 50km', 4, 1, 0, 0, 3, NULL),
(22, 'Distance de 300km', 4, 1, 0, 0, 4, NULL),
(23, 'Distance de 500km', 4, 1, 0, 0, 7, NULL),
(24, 'Distance de 750km', 4, 1, 0, 0, 9, NULL),
(25, 'Distance de 1000km', 4, 1, 0, 0, 10, NULL),
(26, 'Visite médicale', 0, 0, 1, 1, 2, NULL),
(27, 'BPP', 1, 1, 0, 0, 6, NULL),
(28, 'BIA', 0, 0, 0, 0, 3, NULL),
(29, 'Autorisation campagne', 1, 0, 0, 0, 9, NULL),
(30, 'Contôle de compétence', 1, 1, 1, 1, 7, NULL),
(31, 'Circuit de 300km FAI', 4, 1, 0, 0, 6, NULL),
(33, 'Théorique BPP', 1, 0, 0, 0, 3, NULL),
(34, 'Emport passager', 1, 1, 0, 0, 8, NULL),
(35, 'Laché avion', 2, 1, NULL, NULL, NULL, NULL),
(36, 'BB', 2, 0, NULL, NULL, NULL, NULL),
(37, 'PPL', 2, 1, NULL, NULL, NULL, NULL),
(38, 'Licence/Assurance', 2, 0, 1, 1, 2, NULL),
(39, 'FI Formateur instructeur', 2, 0, NULL, NULL, NULL, NULL),
(40, 'FE Formateur examinateur', 2, 0, NULL, NULL, NULL, NULL),
(41, 'Autorisation remorquage', 2, 0, NULL, NULL, NULL, NULL),
(42, 'Premier vol d\'instruction avion', 2, 1, NULL, NULL, NULL, NULL),
(43, 'ITP', 1, 0, 0, 1, 10, NULL),
(44, 'ITV', 1, 0, 0, 0, 11, NULL),
(45, 'Cotisation', 0, 0, 1, 1, 1, NULL),
(46, 'Licence/Assurance', 1, 0, 1, 1, 1, NULL),
(47, 'Cotisation', 0, 0, 1, 1, 3, NULL),
(48, 'Licence/Assurance planeur', 1, 0, 1, 1, 3, NULL),
(49, 'Licence/Assurance avion', 2, 0, 1, 1, 3, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `historique`
--

CREATE TABLE `historique` (
  `id` int(8) NOT NULL COMMENT 'Identifiant',
  `machine` varchar(20) NOT NULL COMMENT 'Machine',
  `annee` int(4) NOT NULL COMMENT 'Année',
  `heures` int(4) NOT NULL COMMENT 'Heures'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Historique des heures de vol';

-- --------------------------------------------------------

--
-- Structure de la table `licences`
--

CREATE TABLE `licences` (
  `id` int(11) NOT NULL COMMENT 'Identifiant',
  `pilote` varchar(25) NOT NULL COMMENT 'Pilote',
  `type` tinyint(2) NOT NULL DEFAULT 0 COMMENT 'Type de licence',
  `year` int(4) NOT NULL COMMENT 'Année de validité',
  `date` date NOT NULL COMMENT 'Date de souscription',
  `comment` varchar(250) NOT NULL COMMENT 'Commentaire'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(40) NOT NULL,
  `time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

-- --------------------------------------------------------

--
-- Structure de la table `machinesa`
--

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
  `fabrication` int(11) DEFAULT NULL COMMENT 'Année de mise en service'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `machinesa`
--

INSERT INTO `machinesa` (`macconstruc`, `macmodele`, `macimmat`, `macnbhdv`, `macplaces`, `macrem`, `maprive`, `club`, `actif`, `comment`, `maprix`, `maprixdc`, `horametre_en_minutes`, `fabrication`) VALUES
('Robin', 'DR400', 'F-GUFB', 0.00, 4, 1, 0, 1, 1, '', 'gratuit', 'gratuit', 0, 0),
('Aeropol', 'Dynamic', 'F-JUFA', 0.00, 2, 1, 0, 1, 1, '', 'hdv-ULM', 'hdv-ULM', 0, 0);

-- --------------------------------------------------------

--
-- Structure de la table `machinesp`
--

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
  `proprio` varchar(25) DEFAULT NULL COMMENT 'Propriétaire'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `machinesp`
--

INSERT INTO `machinesp` (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES
('Alexander Schleicher', 'Ask21', 'F-CGAA', '', 0.00, '2', 0, 0, 0, 1, 'hdv-planeur', 'hdv-planeur-forfait', 'gratuit', 180, 1, '', 0, 0, 0, ''),
('Centrair', 'Pégase', 'F-CGAB', 'EG', 0.00, '1', 0, 0, 0, 1, 'hdv-planeur', 'hdv-planeur-forfait', 'gratuit', 180, 1, '', 0, 0, 0, ''),
('DG', 'DG800', 'F-CGAC', 'AC', 0.00, '1', 0, 0, 0, 1, 'gratuit', 'gratuit', 'gratuit', 180, 1, '', 0, 0, 0, '');

-- --------------------------------------------------------

--
-- Structure de la table `mails`
--

CREATE TABLE `mails` (
  `id` int(11) NOT NULL COMMENT 'Identifiant',
  `titre` varchar(128) NOT NULL COMMENT 'Titre',
  `destinataires` varchar(2048) NOT NULL,
  `copie_a` varchar(128) DEFAULT NULL COMMENT 'Copie à',
  `selection` tinyint(4) NOT NULL COMMENT 'Selection',
  `individuel` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'Individuel',
  `date_envoie` datetime DEFAULT NULL COMMENT 'Date d''envoie',
  `texte` varchar(4096) NOT NULL,
  `debut_facturation` date DEFAULT NULL COMMENT 'Date de début de facturation',
  `fin_facturation` date DEFAULT NULL COMMENT 'Date de fin de facturation'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Courriels';

-- --------------------------------------------------------

--
-- Structure de la table `membres`
--

CREATE TABLE `membres` (
  `mlogin` varchar(25) NOT NULL COMMENT 'identifiant unique',
  `mnom` varchar(25) NOT NULL COMMENT 'Nom',
  `mprenom` varchar(25) NOT NULL COMMENT 'Prénom',
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
  `categorie` varchar(12) DEFAULT '' COMMENT 'Cat�gorie du pilote',
  `profession` varchar(64) DEFAULT NULL COMMENT 'Profession',
  `inst_glider` varchar(25) DEFAULT NULL COMMENT 'Instructeur planeur',
  `inst_airplane` varchar(25) DEFAULT NULL COMMENT 'Instructeur avion',
  `licfed` int(11) DEFAULT NULL COMMENT 'Numéro de licence fédérale'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `membres`
--

INSERT INTO `membres` (`mlogin`, `mnom`, `mprenom`, `memail`, `memailparent`, `madresse`, `cp`, `ville`, `pays`, `mtelf`, `mtelm`, `mdaten`, `m25ans`, `mlieun`, `msexe`, `mniveaux`, `macces`, `club`, `ext`, `actif`, `username`, `photo`, `compte`, `comment`, `trigramme`, `categorie`, `profession`, `inst_glider`, `inst_airplane`, `licfed`) VALUES
('abraracourcix', 'Le Gaulois', 'Abraracourcix', 'abraracourcix@flub78.net', '', '1 rue des menhirs', 0, '', '', '', '', NULL, 0, '0', 'M', 8192, 0, 0, 0, 1, '0', '', 0, 'abraracourcix', '', '0', '', '', '', 0),
('asterix', 'Le Gaulois', 'Asterix', 'asterix@flub78.net', '', '1 rue des menhirs', 0, '', '', '', '', NULL, 0, '0', 'M', 524288, 0, 0, 0, 1, '0', '', 0, 'asterix', '', '0', '', '', '', 0),
('goudurix', 'Le Gaulois', 'Goudurix', 'goudurix@flub78.net', '', '1 rue des menhirs', 0, '', '', '', '', NULL, 0, '0', 'M', 0, 0, 0, 0, 1, '0', '', 0, 'goudurix', '', '0', '', '', '', 0),
('panoramix', 'Le Gaulois', 'Panoramix', 'panoramix@flub78.net', '', '1 rue des menhirs', 0, '', '', '', '', NULL, 0, '0', 'M', 491520, 0, 0, 0, 1, '0', '', 0, 'panoramix', '', '0', '', '', '', 0);

-- --------------------------------------------------------

--
-- Structure de la table `migrations`
--

CREATE TABLE `migrations` (
  `version` int(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `migrations`
--

INSERT INTO `migrations` (`version`) VALUES
(26);

-- --------------------------------------------------------

--
-- Structure de la table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `data` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--
-- Déchargement des données de la table `permissions`
--

INSERT INTO `permissions` (`id`, `role_id`, `data`) VALUES
(1, 1, 'a:1:{s:3:\"uri\";a:22:{i:0;s:8:\"/membre/\";i:1;s:14:\"/planeur/page/\";i:2;s:12:\"/avion/page/\";i:3;s:12:\"/vols_avion/\";i:4;s:14:\"/vols_planeur/\";i:5;s:19:\"/rapports/licences/\";i:6;s:19:\"/compta/mon_compte/\";i:7;s:23:\"/compta/journal_compte/\";i:8;s:25:\"/compta/filterValidation/\";i:9;s:12:\"/compta/pdf/\";i:10;s:15:\"/compta/export/\";i:11;s:17:\"/compta/new_year/\";i:12;s:18:\"/comptes/new_year/\";i:13;s:17:\"/achats/new_year/\";i:14;s:14:\"/tickets/page/\";i:15;s:13:\"/event/stats/\";i:16;s:12:\"/event/page/\";i:17;s:17:\"/event/formation/\";i:18;s:11:\"/event/fai/\";i:19;s:11:\"/presences/\";i:20;s:10:\"/licences/\";i:21;s:9:\"/welcome/\";}}'),
(2, 7, 'a:1:{s:3:\"uri\";a:3:{i:0;s:12:\"/vols_avion/\";i:1;s:14:\"/vols_planeur/\";i:2;s:0:\"\";}}'),
(3, 9, 'a:1:{s:3:\"uri\";a:8:{i:0;s:10:\"/factures/\";i:1;s:8:\"/compta/\";i:2;s:9:\"/comptes/\";i:3;s:10:\"/remorque/\";i:4;s:16:\"/plan_comptable/\";i:5;s:11:\"/categorie/\";i:6;s:8:\"/tarifs/\";i:7;s:0:\"\";}}'),
(4, 8, 'a:1:{s:3:\"uri\";a:20:{i:0;s:8:\"/membre/\";i:1;s:9:\"/planeur/\";i:2;s:7:\"/avion/\";i:3;s:12:\"/vols_avion/\";i:4;s:14:\"/vols_planeur/\";i:5;s:10:\"/factures/\";i:6;s:8:\"/compta/\";i:7;s:8:\"/compta/\";i:8;s:8:\"/compta/\";i:9;s:9:\"/comptes/\";i:10;s:9:\"/tickets/\";i:11;s:7:\"/event/\";i:12;s:10:\"/rapports/\";i:13;s:10:\"/licences/\";i:14;s:8:\"/achats/\";i:15;s:10:\"/terrains/\";i:16;s:7:\"/admin/\";i:17;s:9:\"/reports/\";i:18;s:7:\"/mails/\";i:19;s:12:\"/historique/\";}}'),
(5, 3, 'a:1:{s:3:\"uri\";a:2:{i:0;s:23:\"/compta/journal_compte/\";i:1;s:13:\"/compta/view/\";}}'),
(6, 2, 'a:1:{s:3:\"uri\";a:32:{i:0;s:8:\"/membre/\";i:1;s:9:\"/planeur/\";i:2;s:7:\"/avion/\";i:3;s:17:\"/vols_avion/page/\";i:4;s:29:\"/vols_avion/filterValidation/\";i:5;s:16:\"/vols_avion/pdf/\";i:6;s:23:\"/vols_avion/statistics/\";i:7;s:21:\"/vols_avion/new_year/\";i:8;s:19:\"/vols_planeur/page/\";i:9;s:24:\"/vols_planeur/statistic/\";i:10;s:31:\"/vols_planeur/filterValidation/\";i:11;s:18:\"/vols_planeur/pdf/\";i:12;s:24:\"/vols_planeur/pdf_month/\";i:13;s:26:\"/vols_planeur/pdf_machine/\";i:14;s:25:\"/vols_planeur/export_per/\";i:15;s:21:\"/vols_planeur/export/\";i:16;s:23:\"/vols_planeur/new_year/\";i:17;s:19:\"/factures/en_cours/\";i:18;s:15:\"/factures/page/\";i:19;s:15:\"/factures/view/\";i:20;s:21:\"/factures/ma_facture/\";i:21;s:19:\"/compta/mon_compte/\";i:22;s:23:\"/compta/journal_compte/\";i:23;s:25:\"/compta/filterValidation/\";i:24;s:12:\"/compta/pdf/\";i:25;s:17:\"/compta/new_year/\";i:26;s:18:\"/comptes/new_year/\";i:27;s:14:\"/tickets/page/\";i:28;s:13:\"/event/stats/\";i:29;s:12:\"/event/page/\";i:30;s:17:\"/event/formation/\";i:31;s:11:\"/event/fai/\";}}');

-- --------------------------------------------------------

--
-- Structure de la table `planc`
--

CREATE TABLE `planc` (
  `pcode` varchar(10) NOT NULL,
  `pdesc` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `planc`
--

INSERT INTO `planc` (`pcode`, `pdesc`) VALUES
('102', 'Fonds associatif (sans droit de reprise)'),
('110', 'Report à nouveau (solde créditeur)'),
('119', 'Report à nouveau (solde débiteur)'),
('120', 'Résultat de l’exercice (excédent)'),
('129', 'Résultat de l’exercice (déficit)'),
('164', 'Emprunts auprès des établissements de crédit'),
('181', 'Comptes de liaison des etablissements'),
('215', 'Matériel'),
('218', 'Mobilier.'),
('281', 'Amortissement des immobilisations corporelles'),
('371', 'Marchandises'),
('401', 'Fournisseurs'),
('409', 'Fournisseurs débiteurs. Accomptes'),
('411', 'Clients'),
('421', 'Personnels, rémunérations dues'),
('441', 'Etat - Subventions'),
('46', 'Débiteurs divers et créditeur divers'),
('487', "Produits constatés d'avance"),
('512', 'Banque'),
('531', 'Caisse'),
('60', 'Achats'),
('601', 'Achats stockés - Matières premières et fournitures'),
('602', 'Achats stockés - Autres approvisionements'),
('604', "Achats d'études et prestations de services"),
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
('621', "Personels extérieur à l'association"),
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
('641', 'Rémunération du Personnel'),
('645', 'Charges de sécurité sociale et de prévoyance'),
('65', 'Autres Charges de gestion courante'),
('651', 'Redevance pour concessions, brevets'),
('654', 'Pertes sur créances irrécouvrables'),
('657', 'Subventions versées par l’association'),
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
('74', "Subventions d'exploitation"),
('75', 'Autres produits de gestion courante'),
('753', 'Assurances licences FFVV.'),
('754', 'Retour des Fédérations (bourses).'),
('756', 'Cotisations'),
('757', 'Cotisations code 757'),
('758', 'Produits diverses de gestion courante'),
('76', 'Produits financiers'),
('762', 'Produits des autres immobilisations financieres'),
('774', 'Autres produits exceptionnels'),
('775', 'Produits des cessions d’éléments d’actif'),
('778', 'Autres produits exceptionnels'),
('78', 'Reprise sur amortissements'),
('781', 'Reprises sur amortissements et provisions'),
('792', 'Transfer de charges');

-- --------------------------------------------------------

--
-- Structure de la table `pompes`
--

CREATE TABLE `pompes` (
  `pid` int(11) NOT NULL,
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
  `psaisipar` varchar(25) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Nom de l''opérateur'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

-- --------------------------------------------------------

--
-- Structure de la table `reports`
--

CREATE TABLE `reports` (
  `nom` varchar(64) NOT NULL COMMENT 'Nom du rapport',
  `titre` varchar(64) NOT NULL COMMENT 'Titre du rapport',
  `fields_list` varchar(128) NOT NULL COMMENT 'Titres des champs',
  `align` varchar(128) NOT NULL COMMENT 'Alignement des colonnes',
  `width` varchar(128) NOT NULL COMMENT 'Largeur des colonnes PDF',
  `landscape` tinyint(4) NOT NULL COMMENT 'Orientation du PDF en paysage',
  `sql` varchar(2048) NOT NULL COMMENT 'Requête sql'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Rapports définis par l''utilisateur';

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--
-- Déchargement des données de la table `roles`
--

INSERT INTO `roles` (`id`, `parent_id`, `name`) VALUES
(1, 0, 'membre'),
(2, 9, 'admin'),
(3, 8, 'bureau'),
(7, 1, 'planchiste'),
(8, 7, 'ca'),
(9, 3, 'tresorier');

-- --------------------------------------------------------

--
-- Structure de la table `sections`
--

CREATE TABLE `sections` (
  `id` int(11) NOT NULL,
  `nom` varchar(64) NOT NULL,
  `description` varchar(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `sections`
--

INSERT INTO `sections` (`id`, `nom`, `description`) VALUES
(1, 'Planeur', 'Section planeur de l\'aéroclub d\'Abbeville'),
(2, 'ULM', 'Section ULM de l\'aéroclub d\'Abbeville'),
(3, 'Avion', 'Section avion de l\'aéroclub d\'Abbeville'),
(4, 'Général', 'Compte général de l\'aéroclub d\'Abbeville');

-- --------------------------------------------------------

--
-- Structure de la table `tarifs`
--

CREATE TABLE `tarifs` (
  `id` int(11) NOT NULL COMMENT 'Identifiant',
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
  `public` tinyint(4) DEFAULT 1 COMMENT 'Permet le filtrage sur l''impression'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `tarifs`
--

INSERT INTO `tarifs` (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES
(80, 'remorqué', '2023-01-01', '2099-12-31', 'Remorqué', 25.00, 302, 'testadmin', 1, 0.00, 0, 1),
(81, 'remorqué-25ans', '2023-01-01', '2099-12-31', 'Remorqué moind de 25 ans', 20.00, 302, 'testadmin', 1, 0.00, 0, 1),
(82, 'Treuillé', '2023-01-01', '2099-12-31', 'Treuillée', 8.00, 302, 'testadmin', 1, 0.00, 0, 1),
(83, 'hdv-planeur', '2023-01-01', '2099-12-31', 'Heure de vol planeur', 30.00, 301, 'testadmin', 1, 0.00, 0, 1),
(84, 'hdv-planeur-forfait', '2023-01-01', '2099-12-31', 'Heure de vol planeur au forfait', 10.00, 301, 'testadmin', 1, 0.00, 0, 1),
(85, 'hdv-ULM', '2023-01-01', '2099-12-31', 'Heure de vol ULM', 102.00, 303, 'testadmin', 1, 0.00, 0, 1),
(86, 'gratuit', '2023-01-01', '2099-12-31', 'non facturé', 0.00, 301, 'testadmin', 1, 0.00, 0, 1),
(87, 'Remorqué 500m', '2023-01-01', '2099-12-31', 'Remorqué 500m', 25.00, 302, 'testadmin', 1, 0.00, 0, 1),
(88, 'Remorqué 300m', '2023-01-01', '2099-12-31', 'Remorqué 300m', 15.00, 302, 'testadmin', 1, 0.00, 0, 1),
(89, 'Remorqué 100m', '2023-01-01', '2099-12-31', 'Remorqué 100m', 3.00, 302, 'testadmin', 1, 0.00, 0, 1),
(90, 'bobr', '2023-01-01', '2099-12-31', 'Bob rouge', 20.00, 309, 'testadmin', 1, 0.00, 0, 1),
(91, 'tsb', '2023-01-01', '2099-12-31', 'T-Shirt blanc', 10.00, 309, 'testadmin', 1, 0.00, 0, 1),
(92, 'bobr', '2023-01-01', '2099-12-31', 'Casquette MAGA', 2.50, 309, 'testadmin', 2, 0.00, 0, 1);

-- --------------------------------------------------------

--
-- Structure de la table `terrains`
--

CREATE TABLE `terrains` (
  `oaci` varchar(10) NOT NULL COMMENT 'Code OACI',
  `nom` varchar(64) DEFAULT NULL COMMENT 'Nom du terrain',
  `freq1` decimal(6,3) DEFAULT 0.000 COMMENT 'Fréquence principale',
  `freq2` decimal(6,3) DEFAULT 0.000 COMMENT 'Fréquence secondaire',
  `comment` varchar(256) DEFAULT NULL COMMENT 'Description'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Déchargement des données de la table `terrains`
--

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

-- --------------------------------------------------------

--
-- Structure de la table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL COMMENT 'Identifiant',
  `date` date NOT NULL COMMENT 'Date de l''opération',
  `pilote` varchar(25) NOT NULL COMMENT 'Pilote à créditer/débiter',
  `achat` int(11) DEFAULT NULL COMMENT 'Numéro de l''achat',
  `quantite` decimal(11,0) NOT NULL DEFAULT 0 COMMENT 'Incrément',
  `description` varchar(120) DEFAULT NULL COMMENT 'Commentaire',
  `saisie_par` varchar(25) NOT NULL COMMENT 'Opérateur',
  `club` tinyint(4) NOT NULL COMMENT 'Gestion multi-club',
  `type` int(11) NOT NULL DEFAULT 0 COMMENT 'Type de ticket',
  `vol` int(11) DEFAULT NULL COMMENT 'Vol associé'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Tickets de remorqué ou treuillé';

-- --------------------------------------------------------

--
-- Structure de la table `types_roles`
--

CREATE TABLE `types_roles` (
  `id` int(11) NOT NULL,
  `nom` varchar(64) NOT NULL,
  `description` varchar(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Type de rôle pour les section';

--
-- Déchargement des données de la table `types_roles`
--

INSERT INTO `types_roles` (`id`, `nom`, `description`) VALUES
(1, 'user', 'Capacity to login and see user data'),
(2, 'auto_planchiste', 'Capacity to create, modify and delete the user own data'),
(5, 'planchiste', 'Authorization to create, modify and delete flight data'),
(6, 'ca', 'capacity to see all data for a section including global financial data'),
(7, 'bureau', 'capacity to see all data for a section including personnal financial data'),
(8, 'tresorier', 'Capacity to edit financial data for one section'),
(9, 'super-tresorier', 'Capacity to see an edit financial data for all sections'),
(10, 'club-admin', 'capacity to access all data and change everything');

-- --------------------------------------------------------

--
-- Structure de la table `type_ticket`
--

CREATE TABLE `type_ticket` (
  `id` int(11) NOT NULL COMMENT 'Identifiant',
  `nom` varchar(64) NOT NULL COMMENT 'Nom'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Type de tickets';

--
-- Déchargement des données de la table `type_ticket`
--

INSERT INTO `type_ticket` (`id`, `nom`) VALUES
(0, 'Remorqué'),
(1, 'treuillé');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
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
  `last_login` datetime NOT NULL DEFAULT '1900-01-01 00:00:00',
  `created` datetime NOT NULL DEFAULT '1900-01-01 00:00:00',
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--
-- Déchargement des données de la table `users`
--

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

-- --------------------------------------------------------

--
-- Structure de la table `user_autologin`
--

CREATE TABLE `user_autologin` (
  `key_id` char(32) NOT NULL,
  `user_id` mediumint(8) NOT NULL DEFAULT 0,
  `user_agent` varchar(150) NOT NULL,
  `last_ip` varchar(40) NOT NULL,
  `last_login` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

-- --------------------------------------------------------

--
-- Structure de la table `user_profile`
--

CREATE TABLE `user_profile` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `country` varchar(20) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

--
-- Déchargement des données de la table `user_profile`
--

INSERT INTO `user_profile` (`id`, `user_id`, `country`, `website`) VALUES
(120, 118, NULL, NULL),
(121, 119, NULL, NULL),
(122, 120, NULL, NULL),
(123, 121, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `user_roles_per_section`
--

CREATE TABLE `user_roles_per_section` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `types_roles_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `user_roles_per_section`
--

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

-- --------------------------------------------------------

--
-- Structure de la table `user_temp`
--

CREATE TABLE `user_temp` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(34) NOT NULL,
  `email` varchar(100) NOT NULL,
  `activation_key` varchar(50) NOT NULL,
  `last_ip` varchar(40) NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

-- --------------------------------------------------------

--
-- Structure de la table `volsa`
--

CREATE TABLE `volsa` (
  `vaid` int(11) NOT NULL COMMENT 'Identifiant du vol',
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
  `vahfin` decimal(4,2) NOT NULL COMMENT 'Heure d''atterrissage'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Déchargement des données de la table `volsa`
--

INSERT INTO `volsa` (`vaid`, `vadate`, `vapilid`, `vamacid`, `vacdeb`, `vacfin`, `vaduree`, `vaobs`, `vadc`, `vacategorie`, `varem`, `vanumvi`, `vanbpax`, `vaprixvol`, `vainst`, `valieudeco`, `valieuatt`, `facture`, `payeur`, `pourcentage`, `club`, `gel`, `saisie_par`, `vaatt`, `local`, `nuit`, `reappro`, `essence`, `vahdeb`, `vahfin`) VALUES
(768, '2023-06-17', 'asterix', 'F-JUFA', 100.00, 100.50, 0.50, '', 0, 0, 0, '', '', 0.00, '', '', '', 0, '', 0, 1, 0, 'testadmin', 1, 0, 0, 0, 0, 10.00, 10.30);

-- --------------------------------------------------------

--
-- Structure de la table `volsp`
--

CREATE TABLE `volsp` (
  `vpid` int(11) NOT NULL COMMENT 'Identifiant',
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
  `vpticcolle` tinyint(1) NOT NULL COMMENT 'Si ticket collé ou pas'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `achats`
--
ALTER TABLE `achats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pilote` (`pilote`),
  ADD KEY `saisie_par` (`saisie_par`),
  ADD KEY `vol_planeur` (`vol_planeur`),
  ADD KEY `vol_avion` (`vol_avion`);

--
-- Index pour la table `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `categorie`
--
ALTER TABLE `categorie`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `ci_sessions`
--
ALTER TABLE `ci_sessions`
  ADD PRIMARY KEY (`session_id`);

--
-- Index pour la table `comptes`
--
ALTER TABLE `comptes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `codec` (`codec`),
  ADD KEY `pilote` (`pilote`);

--
-- Index pour la table `ecritures`
--
ALTER TABLE `ecritures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `compte1` (`compte1`),
  ADD KEY `saisie_par` (`saisie_par`),
  ADD KEY `compte2` (`compte2`);

--
-- Index pour la table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `events_types`
--
ALTER TABLE `events_types`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `historique`
--
ALTER TABLE `historique`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `licences`
--
ALTER TABLE `licences`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `machinesa`
--
ALTER TABLE `machinesa`
  ADD PRIMARY KEY (`macimmat`);

--
-- Index pour la table `machinesp`
--
ALTER TABLE `machinesp`
  ADD PRIMARY KEY (`mpimmat`);

--
-- Index pour la table `mails`
--
ALTER TABLE `mails`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `membres`
--
ALTER TABLE `membres`
  ADD PRIMARY KEY (`mlogin`);

--
-- Index pour la table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `planc`
--
ALTER TABLE `planc`
  ADD UNIQUE KEY `pcode` (`pcode`);

--
-- Index pour la table `pompes`
--
ALTER TABLE `pompes`
  ADD PRIMARY KEY (`pid`);

--
-- Index pour la table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`nom`);

--
-- Index pour la table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `tarifs`
--
ALTER TABLE `tarifs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `compte` (`compte`);

--
-- Index pour la table `terrains`
--
ALTER TABLE `terrains`
  ADD PRIMARY KEY (`oaci`);

--
-- Index pour la table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `types_roles`
--
ALTER TABLE `types_roles`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `type_ticket`
--
ALTER TABLE `type_ticket`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `user_autologin`
--
ALTER TABLE `user_autologin`
  ADD PRIMARY KEY (`key_id`,`user_id`);

--
-- Index pour la table `user_profile`
--
ALTER TABLE `user_profile`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `user_roles_per_section`
--
ALTER TABLE `user_roles_per_section`
  ADD PRIMARY KEY (`id`),
  ADD KEY `types_roles_id` (`types_roles_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Index pour la table `user_temp`
--
ALTER TABLE `user_temp`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `volsa`
--
ALTER TABLE `volsa`
  ADD PRIMARY KEY (`vaid`),
  ADD KEY `vapilid` (`vapilid`),
  ADD KEY `vamacid` (`vamacid`),
  ADD KEY `saisie_par` (`saisie_par`);

--
-- Index pour la table `volsp`
--
ALTER TABLE `volsp`
  ADD PRIMARY KEY (`vpid`),
  ADD KEY `saisie_par` (`saisie_par`),
  ADD KEY `pilote_remorqueur` (`pilote_remorqueur`),
  ADD KEY `remorqueur` (`remorqueur`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `achats`
--
ALTER TABLE `achats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant', AUTO_INCREMENT=7218;

--
-- AUTO_INCREMENT pour la table `attachments`
--
ALTER TABLE `attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `categorie`
--
ALTER TABLE `categorie`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant', AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `comptes`
--
ALTER TABLE `comptes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant', AUTO_INCREMENT=310;

--
-- AUTO_INCREMENT pour la table `ecritures`
--
ALTER TABLE `ecritures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant', AUTO_INCREMENT=8871;

--
-- AUTO_INCREMENT pour la table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=304;

--
-- AUTO_INCREMENT pour la table `events_types`
--
ALTER TABLE `events_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Numéro', AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT pour la table `historique`
--
ALTER TABLE `historique`
  MODIFY `id` int(8) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant', AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT pour la table `licences`
--
ALTER TABLE `licences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant', AUTO_INCREMENT=228;

--
-- AUTO_INCREMENT pour la table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=526;

--
-- AUTO_INCREMENT pour la table `mails`
--
ALTER TABLE `mails`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant';

--
-- AUTO_INCREMENT pour la table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `pompes`
--
ALTER TABLE `pompes`
  MODIFY `pid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=223;

--
-- AUTO_INCREMENT pour la table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `tarifs`
--
ALTER TABLE `tarifs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant', AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT pour la table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant', AUTO_INCREMENT=522;

--
-- AUTO_INCREMENT pour la table `types_roles`
--
ALTER TABLE `types_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT pour la table `user_profile`
--
ALTER TABLE `user_profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=124;

--
-- AUTO_INCREMENT pour la table `user_roles_per_section`
--
ALTER TABLE `user_roles_per_section`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `user_temp`
--
ALTER TABLE `user_temp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `volsa`
--
ALTER TABLE `volsa`
  MODIFY `vaid` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant du vol', AUTO_INCREMENT=769;

--
-- AUTO_INCREMENT pour la table `volsp`
--
ALTER TABLE `volsp`
  MODIFY `vpid` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant', AUTO_INCREMENT=2897;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `achats`
--
ALTER TABLE `achats`
  ADD CONSTRAINT `achats_ibfk_1` FOREIGN KEY (`pilote`) REFERENCES `membres` (`mlogin`),
  ADD CONSTRAINT `achats_ibfk_vol_avion` FOREIGN KEY (`vol_avion`) REFERENCES `volsa` (`vaid`),
  ADD CONSTRAINT `achats_ibfk_vol_planeur` FOREIGN KEY (`vol_planeur`) REFERENCES `volsp` (`vpid`);

--
-- Contraintes pour la table `user_roles_per_section`
--
ALTER TABLE `user_roles_per_section`
  ADD CONSTRAINT `section_id` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`),
  ADD CONSTRAINT `types_roles_id` FOREIGN KEY (`types_roles_id`) REFERENCES `types_roles` (`id`),
  ADD CONSTRAINT `user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
