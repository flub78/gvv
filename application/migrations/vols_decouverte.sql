-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : mer. 02 avr. 2025 à 19:34
-- Version du serveur : 10.11.8-MariaDB-0ubuntu0.24.04.1
-- Version de PHP : 8.3.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Base de données : `gvv2`
--

-- --------------------------------------------------------

--
-- Structure de la table `vols_decouverte`
--

CREATE TABLE `vols_decouverte` (
  `id` int(20) NOT NULL,
  `date_vente` date NOT NULL,
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
  `nb_personnes` tinyint(1) NULL,
  `prix` decimal(14,12)  NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `vols_decouverte`
--
ALTER TABLE `vols_decouverte`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `vols_decouverte`
--
ALTER TABLE `vols_decouverte`
  MODIFY `id` int(20) NOT NULL AUTO_INCREMENT;
COMMIT;