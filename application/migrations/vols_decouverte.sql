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
  `product` varchar(32) NOT NULL,
  `destinataire` varchar(64) DEFAULT NULL,
  `de_la_part` varchar(64) DEFAULT NULL,
  `dest_email` varchar(64) DEFAULT NULL,
  `qr_code` varchar(64) NOT NULL,
  `beneficiaire_tel` varchar(64) DEFAULT NULL,
  `accident_tel` varchar(64) DEFAULT NULL,
  `accident_name` varchar(64) DEFAULT NULL,
  `parental` varchar(64) DEFAULT NULL,
  `date_plannig` date DEFAULT NULL,
  `time_planning` time DEFAULT NULL,
  `date_vol` date DEFAULT NULL,
  `time_vol` time DEFAULT NULL,
  `pilote` varchar(64) DEFAULT NULL,
  `airplane_type` varchar(64) DEFAULT NULL,
  `airplaine_immat` varchar(10) DEFAULT NULL,
  `cancelled` tinyint(1) DEFAULT 0
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