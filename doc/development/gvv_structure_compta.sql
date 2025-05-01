-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 13, 2025 at 08:00 AM
-- Server version: 10.11.2-MariaDB-1:10.11.2+maria~ubu2204
-- PHP Version: 8.1.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `frederic_gvvg`
--

-- --------------------------------------------------------

--
-- Table structure for table `comptes`
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
  `saisie_par` varchar(25) NOT NULL DEFAULT '""' COMMENT 'Créateur',
  `club` tinyint(1) DEFAULT 0 COMMENT 'Gestion multi-club'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `ecritures`
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `planc`
--

CREATE TABLE `planc` (
  `pcode` varchar(10) NOT NULL,
  `pdesc` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `comptes`
--
ALTER TABLE `comptes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `codec` (`codec`),
  ADD KEY `pilote` (`pilote`);

--
-- Indexes for table `ecritures`
--
ALTER TABLE `ecritures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `compte1` (`compte1`),
  ADD KEY `saisie_par` (`saisie_par`),
  ADD KEY `compte2` (`compte2`);

--
-- Indexes for table `planc`
--
ALTER TABLE `planc`
  ADD UNIQUE KEY `pcode` (`pcode`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `comptes`
--
ALTER TABLE `comptes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant';

--
-- AUTO_INCREMENT for table `ecritures`
--
ALTER TABLE `ecritures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant';
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
