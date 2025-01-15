-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : mar. 14 jan. 2025 à 20:26
-- Version du serveur : 10.11.8-MariaDB-0ubuntu0.24.04.1
-- Version de PHP : 8.3.14

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
(6, 'ca', 'capacity to see all data for a section including financial data'),
(7, 'tresorer', 'Capacity to edit financial data for one section'),
(8, 'super-tresorer', 'Capacity to see an edit financial data for all sections'),
(9, 'club-admin', 'capacity to access all data and change everything');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `types_roles`
--
ALTER TABLE `types_roles`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `types_roles`
--
ALTER TABLE `types_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
