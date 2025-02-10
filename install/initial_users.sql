-- phpMyAdmin SQL Dump
-- version 4.1.14
-- http://www.phpmyadmin.net
--
-- Client :  127.0.0.1
-- Généré le :  Mar 23 Décembre 2014 à 21:44
-- Version du serveur :  5.6.17
-- Version de PHP :  5.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données :  `gvv2`
--

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL DEFAULT '1',
  `username` varchar(25) COLLATE utf8_bin NOT NULL,
  `password` varchar(34) COLLATE utf8_bin NOT NULL,
  `email` varchar(100) COLLATE utf8_bin NOT NULL,
  `banned` tinyint(1) NOT NULL DEFAULT '0',
  `ban_reason` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `newpass` varchar(34) COLLATE utf8_bin DEFAULT NULL,
  `newpass_key` varchar(32) COLLATE utf8_bin DEFAULT NULL,
  `newpass_time` datetime DEFAULT NULL,
  `last_ip` varchar(40) COLLATE utf8_bin NOT NULL,
  `last_login` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=95 ;

--
-- Contenu de la table `users`
--

INSERT INTO `users` (`id`, `role_id`, `username`, `password`, `email`, `banned`, `ban_reason`, `newpass`, `newpass_key`, `newpass_time`, `last_ip`, `last_login`, `created`, `modified`) VALUES
(15, 1, 'testuser', '$1$wu3.3t2.$Wgk43dHPPi3PTv5atdpnz0', 'testuser@free.fr', 0, NULL, NULL, NULL, NULL, '127.0.0.1', '2012-01-25 20:59:12', '2011-04-21 15:21:13', '2014-12-23 20:37:35'),
(16, 2, 'testadmin', '$1$uM1.f95.$AnUHH1W/xLS9fxDbt8RPo0', 'frederic.peignot@free.fr', 0, NULL, NULL, NULL, NULL, '::1', '2014-12-23 21:36:15', '2011-04-21 15:21:40', '2014-12-23 20:36:46'),
(58, 7, 'testplanchiste', '$1$DT0.QJ1.$yXqRz6gf/jWC4MzY2D05Y.', 'testplanchiste@free.fr', 0, NULL, NULL, NULL, NULL, '127.0.0.1', '0000-00-00 00:00:00', '2012-01-25 21:00:23', '2014-12-23 20:38:02'),
(59, 8, 'testca', '$1$9h..cY3.$NzkeKkCoSa2oxL7bQCq4v1', 'testca@free.fr', 0, NULL, NULL, NULL, NULL, '127.0.0.1', '0000-00-00 00:00:00', '2012-01-25 21:00:58', '2014-12-23 20:38:30'),
(60, 3, 'testbureau', '$1$NC0.SN5.$qwnSUxiPbyh6v2JrhA1fH1', 'testbureau@free.fr', 0, NULL, NULL, NULL, NULL, '127.0.0.1', '2012-01-25 21:03:01', '2012-01-25 21:01:36', '2014-12-23 20:39:00'),
(61, 9, 'testtresorier', '$1$KiPMl0ho$/E3NBaprpM5Xcv.z40zjK0', 'testresorier@free.fr', 0, NULL, NULL, NULL, NULL, '127.0.0.1', '0000-00-00 00:00:00', '2012-01-25 21:02:36', '2012-01-25 20:02:36');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
