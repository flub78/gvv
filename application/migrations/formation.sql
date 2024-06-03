-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Client: localhost
-- Généré le: Ven 20 Mai 2016 à 22:55
-- Version du serveur: 5.5.43-0ubuntu0.14.04.1
-- Version de PHP: 5.5.9-1ubuntu4.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données: `gvv2`
--

-- --------------------------------------------------------

--
-- Structure de la table `formation_chapitres`
--

CREATE TABLE IF NOT EXISTS `formation_chapitres` (
  `name` varchar(64) COLLATE utf8_bin NOT NULL,
  `description` varchar(128) COLLATE utf8_bin NOT NULL,
  `ordre` int(4) NOT NULL DEFAULT '9999',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Contenu de la table `formation_chapitres`
--

INSERT INTO `formation_chapitres` (`name`, `description`, `ordre`) VALUES
('phase1', 'Phase 1', 1),
('phase10', 'Phase10', 10),
('phase2', 'Phase 2', 2),
('phase3', 'Phase 3', 3),
('phase4', 'Phase 4', 4),
('phase5', 'Phase 5', 5),
('phase6', 'Phase 6', 6),
('phase7', 'Phase7', 7),
('phase8', 'Phase 8', 8),
('phase9', 'Phase 9', 9);

-- --------------------------------------------------------

--
-- Structure de la table `formation_item`
--

CREATE TABLE IF NOT EXISTS `formation_item` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `description` varchar(128) COLLATE utf8_bin NOT NULL,
  `phase` varchar(64) COLLATE utf8_bin NOT NULL,
  `ordre` int(8) NOT NULL DEFAULT '9999',
  PRIMARY KEY (`id`),
  KEY `to_phase` (`phase`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=51 ;

--
-- Contenu de la table `formation_item`
--

INSERT INTO `formation_item` (`id`, `description`, `phase`, `ordre`) VALUES
(1, 'Accoutumance', 'phase1', 1),
(2, 'Effets primaires', 'phase1', 2),
(3, 'Assiette', 'phase2', 1),
(4, 'Inclinaison', 'phase2', 2),
(5, 'Conjugaison', 'phase2', 3),
(6, 'Mise en virage', 'phase2', 4),
(7, 'Stabilisation', 'phase2', 5),
(8, 'Sortie de virage', 'phase2', 6),
(9, 'Sécurité', 'phase2', 7),
(10, 'Assiette, trajectoire, vitesse', 'phase2', 8),
(11, 'Compensateur', 'phase2', 9),
(12, 'Symétrie du vol', 'phase2', 10),
(13, 'Check list', 'phase3', 1),
(14, 'Décollage Rem ou Treuil', 'phase3', 2),
(15, 'Montée remorqué', 'phase3', 3),
(16, 'Montée treuil', 'phase3', 4),
(17, 'Largage R ou T', 'phase3', 5),
(18, 'Visualisation aboutissement', 'phase3', 6),
(19, 'Étude aérofreins', 'phase3', 7),
(20, 'Approche finale', 'phase3', 8),
(21, 'Atterrissage', 'phase3', 9),
(22, 'PTL', 'phase4', 1),
(23, 'Vent travers décollage', 'phase4', 2),
(24, 'Vent travers atterrissage', 'phase4', 3),
(25, 'Vol fin. maxi / vitesses MC', 'phase4', 4),
(26, 'Orientation, compas', 'phase5', 1),
(27, 'Local visuel', 'phase5', 2),
(28, 'Lecture de carte', 'phase5', 3),
(29, 'Local finesse 10', 'phase5', 4),
(30, 'Radio', 'phase5', 5),
(31, 'Vol lent', 'phase6', 1),
(32, 'Décrochage en ligne droite', 'phase6', 2),
(33, 'Décrochage en virage', 'phase6', 3),
(34, 'Détection thermiques ', 'phase7', 1),
(35, 'Centrage', 'phase7', 2),
(36, 'Maintien VI et inclinaison', 'phase7', 3),
(37, 'Anticollision', 'phase7', 4),
(38, 'Maintien du local', 'phase7', 5),
(39, 'Virage grande inclinaison', 'phase8', 1),
(40, 'Virage engagé', 'phase8', 2),
(41, 'Vol de pente, dérive', 'phase8', 3),
(42, 'Vitesse, distance', 'phase8', 4),
(43, 'Virages, piorité', 'phase8', 5),
(44, 'Sous onde', 'phase8', 6),
(45, 'Laminaire', 'phase8', 7),
(46, 'Autorotation', 'phase9', 1),
(47, 'Incidents au décollage', 'phase9', 2),
(48, 'Largage impossible', 'phase9', 3),
(49, 'P T inhabituelles', 'phase9', 4),
(50, 'Espaces aériens locaux', 'phase9', 5);

-- --------------------------------------------------------

--
-- Structure de la table `formation_progres`
--

CREATE TABLE IF NOT EXISTS `formation_progres` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `pilote` varchar(25) COLLATE utf8_bin NOT NULL,
  `instructeur` varchar(25) COLLATE utf8_bin NOT NULL,
  `date` date NOT NULL,
  `subject` int(8) NOT NULL,
  `niveau` int(4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pilote` (`pilote`),
  UNIQUE KEY `instructeur` (`instructeur`),
  UNIQUE KEY `subject` (`subject`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;

--
-- Contraintes pour les tables exportées
--

--
-- Contraintes pour la table `formation_item`
--
ALTER TABLE `formation_item`
  ADD CONSTRAINT `belongs_to` FOREIGN KEY (`phase`) REFERENCES `formation_chapitres` (`name`);

--
-- Contraintes pour la table `formation_progres`
--
ALTER TABLE `formation_progres`
  ADD CONSTRAINT `formation_progres_ibfk_1` FOREIGN KEY (`subject`) REFERENCES `formation_item` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
