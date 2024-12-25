#
# TABLE STRUCTURE FOR: migrations
#

DROP TABLE IF EXISTS migrations;

CREATE TABLE `migrations` (
  `version` int(3) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO migrations (`version`) VALUES (17);


#
# TABLE STRUCTURE FOR: historique
#

DROP TABLE IF EXISTS historique;

CREATE TABLE `historique` (
  `id` int(8) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant',
  `machine` varchar(20) NOT NULL COMMENT 'Machine',
  `annee` int(4) NOT NULL COMMENT 'Année',
  `heures` int(4) NOT NULL COMMENT 'Heures',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8 COMMENT='Historique des heures de vol';

INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (0, 'F-CJRG', 2002, 179);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (1, 'F-CJRG', 2001, 12);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (3, 'F-CJRG', 2003, 144);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (4, 'F-CJRG', 2004, 96);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (5, 'F-CJRG', 2005, 102);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (6, 'F-CJRG', 2006, 73);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (7, 'F-CJRG', 2007, 84);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (8, 'F-CJRG', 2008, 70);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (9, 'F-CJRG', 2009, 44);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (10, 'F-CJRG', 2010, 91);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (11, 'F-CICA', 1995, 95);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (12, 'F-CICA', 1996, 75);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (13, 'F-CICA', 1997, 135);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (14, 'F-CICA', 1998, 45);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (15, 'F-CICA', 1999, 75);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (16, 'F-CICA', 2000, 38);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (17, 'F-CICA', 2001, 48);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (18, 'F-CICA', 2002, 28);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (19, 'F-CICA', 2003, 14);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (20, 'F-CICA', 2004, 11);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (21, 'F-CICA', 2005, 9);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (22, 'F-CICA', 2006, 13);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (23, 'F-CICA', 2007, 38);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (24, 'F-CICA', 2008, 37);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (25, 'F-CICA', 2009, 25);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (26, 'F-CICA', 2010, 73);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (27, 'F-CGNP', 1987, 150);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (28, 'F-CGNP', 1988, 170);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (29, 'F-CGNP', 1989, 90);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (30, 'F-CGNP', 1990, 110);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (32, 'F-CGNP', 1991, 60);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (33, 'F-CGNP', 1992, 47);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (34, 'F-CGNP', 1993, 95);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (35, 'F-CGNP', 1994, 70);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (36, 'F-CGNP', 1995, 119);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (37, 'F-CGNP', 1996, 151);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (38, 'F-CGNP', 1997, 166);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (39, 'F-CGNP', 1998, 93);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (40, 'F-CGNP', 1999, 85);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (41, 'F-CGNP', 2000, 20);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (42, 'F-CGNP', 2001, 51);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (43, 'F-CGNP', 2002, 39);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (44, 'F-CGNP', 2003, 91);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (45, 'F-CGNP', 2004, 70);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (46, 'F-CGNP', 2005, 41);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (47, 'F-CGNP', 2006, 7);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (48, 'F-CGNP', 2007, 30);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (49, 'F-CGNP', 2008, 25);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (50, 'F-CGNP', 2009, 74);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (51, 'F-CGNP', 2010, 74);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (52, 'F-CFYD', 1985, 210);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (53, 'F-CFYD', 1986, 210);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (54, 'F-CFYD', 1987, 160);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (55, 'F-CFYD', 1988, 100);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (56, 'F-CFYD', 1989, 110);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (57, 'F-CFYD', 1990, 250);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (58, 'F-CFYD', 1991, 110);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (59, 'F-CFYD', 1992, 103);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (60, 'F-CFYD', 1993, 105);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (61, 'F-CFYD', 1994, 161);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (62, 'F-CFYD', 1995, 206);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (63, 'F-CFYD', 1996, 195);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (64, 'F-CFYD', 1997, 187);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (65, 'F-CFYD', 1998, 139);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (66, 'F-CFYD', 1999, 155);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (67, 'F-CFYD', 2000, 132);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (68, 'F-CFYD', 2001, 126);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (69, 'F-CFYD', 2002, 123);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (70, 'F-CFYD', 2003, 115);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (71, 'F-CFYD', 2004, 63);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (72, 'F-CFYD', 2004, 63);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (73, 'F-CFYD', 2005, 75);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (74, 'F-CFYD', 2006, 116);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (75, 'F-CFYD', 2007, 73);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (76, 'F-CFYD', 2008, 73);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (77, 'F-CFYD', 2009, 100);
INSERT INTO historique (`id`, `machine`, `annee`, `heures`) VALUES (78, 'F-CFYD', 2010, 83);


#
# TABLE STRUCTURE FOR: mails
#

DROP TABLE IF EXISTS mails;

CREATE TABLE `mails` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant',
  `titre` varchar(128) NOT NULL COMMENT 'Titre',
  `destinataires` varchar(2048) NOT NULL,
  `copie_a` varchar(128) DEFAULT NULL COMMENT 'Copie à',
  `selection` tinyint(4) NOT NULL COMMENT 'Selection',
  `individuel` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Individuel',
  `date_envoie` datetime DEFAULT NULL COMMENT 'Date d''envoie',
  `texte` varchar(4096) NOT NULL,
  `debut_facturation` date DEFAULT NULL COMMENT 'Date de début de facturation',
  `fin_facturation` date DEFAULT NULL COMMENT 'Date de fin de facturation',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Courriels';

#
# TABLE STRUCTURE FOR: reports
#

DROP TABLE IF EXISTS reports;

CREATE TABLE `reports` (
  `nom` varchar(64) NOT NULL COMMENT 'Nom du rapport',
  `titre` varchar(64) NOT NULL COMMENT 'Titre du rapport',
  `fields_list` varchar(128) NOT NULL COMMENT 'Titres des champs',
  `align` varchar(128) NOT NULL COMMENT 'Alignement des colonnes',
  `width` varchar(128) NOT NULL COMMENT 'Largeur des colonnes PDF',
  `landscape` tinyint(4) NOT NULL COMMENT 'Orientation du PDF en paysage',
  `sql` varchar(2048) NOT NULL COMMENT 'Requête sql',
  PRIMARY KEY (`nom`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Rapports définis par l''utilisateur';

#
# TABLE STRUCTURE FOR: type_ticket
#

DROP TABLE IF EXISTS type_ticket;

CREATE TABLE `type_ticket` (
  `id` int(11) NOT NULL COMMENT 'Identifiant',
  `nom` varchar(64) NOT NULL COMMENT 'Nom',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Type de tickets';

INSERT INTO type_ticket (`id`, `nom`) VALUES (0, 'Remorqué');
INSERT INTO type_ticket (`id`, `nom`) VALUES (1, 'treuillé');


#
# TABLE STRUCTURE FOR: terrains
#

DROP TABLE IF EXISTS terrains;

CREATE TABLE `terrains` (
  `oaci` varchar(10) COLLATE latin1_general_ci NOT NULL COMMENT 'Code OACI',
  `nom` varchar(64) COLLATE latin1_general_ci DEFAULT NULL COMMENT 'Nom du terrain',
  `freq1` decimal(6,3) DEFAULT '0.000' COMMENT 'Fréquence principale',
  `freq2` decimal(6,3) DEFAULT '0.000' COMMENT 'Fréquence secondaire',
  `comment` varchar(256) COLLATE latin1_general_ci DEFAULT NULL COMMENT 'Description',
  PRIMARY KEY (`oaci`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

INSERT INTO terrains (`oaci`, `nom`, `freq1`, `freq2`, `comment`) VALUES ('LFOI', 'Abbeville', '123.500', '0.000', '');
INSERT INTO terrains (`oaci`, `nom`, `freq1`, `freq2`, `comment`) VALUES ('LFAY', 'Amiens Glisy', '123.400', '0.000', '');
INSERT INTO terrains (`oaci`, `nom`, `freq1`, `freq2`, `comment`) VALUES ('LFQB', 'Troyes - Barberey', '123.725', '0.000', '');
INSERT INTO terrains (`oaci`, `nom`, `freq1`, `freq2`, `comment`) VALUES ('LFQO', 'Lille - Marq en Bareuil', '0.000', '0.000', '');
INSERT INTO terrains (`oaci`, `nom`, `freq1`, `freq2`, `comment`) VALUES ('LFON', 'Dreux', '123.500', '0.000', '');
INSERT INTO terrains (`oaci`, `nom`, `freq1`, `freq2`, `comment`) VALUES ('LFNC', 'montdauphin saint crepin', '123.500', '123.050', 'alt 903 m');
INSERT INTO terrains (`oaci`, `nom`, `freq1`, `freq2`, `comment`) VALUES ('LFYR', 'Romorantin', '119.070', '0.000', '');
INSERT INTO terrains (`oaci`, `nom`, `freq1`, `freq2`, `comment`) VALUES ('LFRI', 'la Roche sur Yon', '0.000', '0.000', '');
INSERT INTO terrains (`oaci`, `nom`, `freq1`, `freq2`, `comment`) VALUES ('LFYG', 'Cambrai', '999.999', '0.000', '');
INSERT INTO terrains (`oaci`, `nom`, `freq1`, `freq2`, `comment`) VALUES ('LFLV', 'Vichy', '121.400', '0.000', '253m');
INSERT INTO terrains (`oaci`, `nom`, `freq1`, `freq2`, `comment`) VALUES ('LFJR', 'Angers', '0.000', '0.000', '');
INSERT INTO terrains (`oaci`, `nom`, `freq1`, `freq2`, `comment`) VALUES ('LFEG', 'Argenton Sur Creuse', '123.500', '0.000', '');


#
# TABLE STRUCTURE FOR: events
#

DROP TABLE IF EXISTS events;

CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emlogin` varchar(25) COLLATE utf8_bin NOT NULL,
  `etype` int(11) NOT NULL,
  `edate` date DEFAULT NULL,
  `evaid` double DEFAULT NULL,
  `evpid` double DEFAULT NULL,
  `ecomment` varchar(128) COLLATE utf8_bin DEFAULT NULL COMMENT 'Commentaire',
  `year` int(11) DEFAULT NULL COMMENT 'Année',
  `date_expiration` date DEFAULT NULL COMMENT 'date expiration',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=304 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO events (`id`, `emlogin`, `etype`, `edate`, `evaid`, `evpid`, `ecomment`, `year`, `date_expiration`) VALUES (300, 'obelix', 15, '2015-04-15', NULL, '2879', '', NULL, NULL);
INSERT INTO events (`id`, `emlogin`, `etype`, `edate`, `evaid`, `evpid`, `ecomment`, `year`, `date_expiration`) VALUES (301, 'bonemine', 15, '2015-04-02', NULL, '2881', '', NULL, NULL);
INSERT INTO events (`id`, `emlogin`, `etype`, `edate`, `evaid`, `evpid`, `ecomment`, `year`, `date_expiration`) VALUES (302, 'bonemine', 16, '2015-04-02', NULL, '2881', '', NULL, NULL);
INSERT INTO events (`id`, `emlogin`, `etype`, `edate`, `evaid`, `evpid`, `ecomment`, `year`, `date_expiration`) VALUES (303, 'bonemine', 18, '2015-04-02', NULL, '2881', '', NULL, NULL);


#
# TABLE STRUCTURE FOR: events_types
#

DROP TABLE IF EXISTS events_types;

CREATE TABLE `events_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Numéro',
  `name` varchar(64) CHARACTER SET utf8 NOT NULL,
  `activite` tinyint(2) NOT NULL DEFAULT '0' COMMENT 'Activité associée',
  `en_vol` tinyint(4) NOT NULL COMMENT 'Associé à un vol',
  `multiple` tinyint(1) DEFAULT NULL COMMENT 'Multiple',
  `expirable` tinyint(1) DEFAULT NULL COMMENT 'a une date d_expiration',
  `ordre` tinyint(2) DEFAULT NULL COMMENT 'ordre d_affichage',
  `annual` tinyint(1) DEFAULT NULL COMMENT 'Evénement annuel',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=50 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (14, 'Déclaration début formation', 1, 0, 0, 0, 2, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (15, 'Laché planeur', 1, 1, 0, 0, 4, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (16, 'Vol 1h', 1, 1, 0, 0, 5, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (17, 'Vol 5h', 4, 1, 0, 0, 2, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (18, 'Gain de 1000m', 4, 1, 0, 0, 1, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (19, 'Gain de 3000m', 4, 1, 0, 0, 5, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (20, 'Gain de 5000m', 4, 1, 0, 0, 8, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (21, 'Distance de 50km', 4, 1, 0, 0, 3, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (22, 'Distance de 300km', 4, 1, 0, 0, 4, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (23, 'Distance de 500km', 4, 1, 0, 0, 7, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (24, 'Distance de 750km', 4, 1, 0, 0, 9, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (25, 'Distance de 1000km', 4, 1, 0, 0, 10, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (26, 'Visite médicale', 0, 0, 1, 1, 2, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (27, 'BPP', 1, 1, 0, 0, 6, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (28, 'BIA', 0, 0, 0, 0, 3, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (29, 'Autorisation campagne', 1, 0, 0, 0, 9, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (30, 'Contôle de compétence', 1, 1, 1, 1, 7, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (31, 'Circuit de 300km FAI', 4, 1, 0, 0, 6, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (33, 'Théorique BPP', 1, 0, 0, 0, 3, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (34, 'Emport passager', 1, 1, 0, 0, 8, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (35, 'Laché avion', 2, 1, NULL, NULL, NULL, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (36, 'BB', 2, 0, NULL, NULL, NULL, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (37, 'PPL', 2, 1, NULL, NULL, NULL, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (38, 'Licence/Assurance', 2, 0, 1, 1, 2, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (39, 'FI Formateur instructeur', 2, 0, NULL, NULL, NULL, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (40, 'FE Formateur examinateur', 2, 0, NULL, NULL, NULL, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (41, 'Autorisation remorquage', 2, 0, NULL, NULL, NULL, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (42, 'Premier vol d\'instruction avion', 2, 1, NULL, NULL, NULL, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (43, 'ITP', 1, 0, 0, 1, 10, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (44, 'ITV', 1, 0, 0, 0, 11, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (45, 'Cotisation', 0, 0, 1, 1, 1, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (46, 'Licence/Assurance', 1, 0, 1, 1, 1, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (47, 'Cotisation', 0, 0, 1, 1, 3, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (48, 'Licence/Assurance planeur', 1, 0, 1, 1, 3, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`) VALUES (49, 'Licence/Assurance avion', 2, 0, 1, 1, 3, NULL);


#
# TABLE STRUCTURE FOR: tickets
#

DROP TABLE IF EXISTS tickets;

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant',
  `date` date NOT NULL COMMENT 'Date de l''opération',
  `pilote` varchar(25) NOT NULL COMMENT 'Pilote à créditer/débiter',
  `achat` int(11) DEFAULT NULL COMMENT 'Numéro de l''achat',
  `quantite` decimal(11,2) NOT NULL DEFAULT '0.00' COMMENT 'Incrément',
  `description` varchar(120) DEFAULT NULL COMMENT 'Commentaire',
  `saisie_par` varchar(25) NOT NULL COMMENT 'Opérateur',
  `club` tinyint(4) NOT NULL COMMENT 'Gestion multi-club',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT 'Type de ticket',
  `vol` int(11) DEFAULT NULL COMMENT 'Vol associé',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=522 DEFAULT CHARSET=utf8 COMMENT='Tickets de remorqué ou treuillé';

#
# TABLE STRUCTURE FOR: categorie
#

DROP TABLE IF EXISTS categorie;

CREATE TABLE `categorie` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant',
  `nom` varchar(32) COLLATE latin1_general_ci NOT NULL COMMENT 'Nom',
  `description` varchar(80) COLLATE latin1_general_ci DEFAULT NULL COMMENT 'Commentaire',
  `parent` int(11) NOT NULL DEFAULT '0' COMMENT 'Catégorie parente',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT 'Type de catégorie',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='Catégorie d''écritures pour comptabilité analytique';

INSERT INTO categorie (`id`, `nom`, `description`, `parent`, `type`) VALUES (0, 'autre', 'Autre catégorie', 0, 0);


#
# TABLE STRUCTURE FOR: planc
#

DROP TABLE IF EXISTS planc;

CREATE TABLE `planc` (
  `pcode` varchar(10) NOT NULL,
  `pdesc` varchar(50) NOT NULL,
  UNIQUE KEY `pcode` (`pcode`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO planc (`pcode`, `pdesc`) VALUES ('706', 'Prestations de services');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('215', 'Matériel');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('218', 'Mobilier.');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('371', 'Marchandises');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('401', 'Fournisseurs');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('411', 'Clients');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('512', 'Banque');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('531', 'Caisse');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('60', 'Achats');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('601', 'Achats stockés - Matières premières et fournitures');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('602', 'Achats stockés - Autres approvisionements');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('606', 'Achats non stockés de matières et fournitures');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('604', 'Achats d\'études et prestations de services');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('605', 'Achat autres.');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('61', 'Services extérieurs');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('611', 'Sous-traitance générale');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('612', 'Redevances de crédit-bail');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('613', 'Locations');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('615', 'Entretien et réparations');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('616', 'Assurances');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('62', 'Autres services extérieurs');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('621', 'Personels extérieur à l\'association');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('622', 'Rémunérations et Honoraires.');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('623', 'Publicité, Publications, Relations publiques');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('624', 'Transport de bien et transport collectif du person');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('625', 'Déplacement, missions et reception');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('626', 'Frais postaux et télécommunications');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('657', 'Subventions versées par l’association');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('628', 'Divers, cotisations');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('629', 'Rabais, ristournes, remises sur services extérieur');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('63', 'Impôts et Taxes');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('631', 'Impots sur rémunération');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('635', 'Autres impôts et Taxes.');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('64', 'Charges de Personnel');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('65', 'Autres Charges de gestion courante');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('651', 'Redevance pour concessions, brevets');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('66', 'Charges financières');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('67', 'Chages Exceptionnelles');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('674', 'Autres.');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('68', 'Dotation aux Amortissements');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('70', 'Ventes');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('74', 'Subventions d\'exploitation');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('75', 'Autres produits de gestion courante');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('753', 'Assurances licences FFVV.');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('754', 'Retour des Fédérations (bourses).');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('708', 'Produit des activités annexes');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('756', 'Cotisations');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('76', 'Produits financiers');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('707', 'Ventes de marchandises');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('701', 'Ventes de produits finis');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('774', 'Autres produits exceptionnels');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('78', 'Reprise sur amortissements');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('110', 'Report à nouveau (solde créditeur)');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('119', 'Report à nouveau (solde débiteur)');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('441', 'Etat - Subventions');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('778', 'Autres produits exceptionnels');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('678', 'Autres charges exceptionnelles');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('654', 'Pertes sur créances irrécouvrables');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('487', 'Produits constatés d\'avance');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('46', 'Débiteurs divers et créditeur divers');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('120', 'Résultat de l’exercice (excédent)');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('129', 'Résultat de l’exercice (déficit)');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('102', 'Fonds associatif (sans droit de reprise)');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('775', 'Produits des cessions d’éléments d’actif');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('781', 'Reprises sur amortissements et provisions');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('281', 'Amortissement des immobilisations corporelles');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('409', 'Fournisseurs débiteurs. Accomptes');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('607', 'Achats de marchandises');


#
# TABLE STRUCTURE FOR: membres
#

DROP TABLE IF EXISTS membres;

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
  `m25ans` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Moins de 25 ans',
  `mlieun` varchar(25) DEFAULT NULL COMMENT 'Lieu de naissance',
  `msexe` char(1) NOT NULL DEFAULT 'M',
  `mniveaux` double NOT NULL COMMENT 'Qualifications du membre',
  `macces` int(11) DEFAULT '0' COMMENT 'Droits d''accés',
  `club` tinyint(1) DEFAULT '0' COMMENT 'Gestion multi-club',
  `ext` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Pilote exterieur',
  `actif` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Pilote actif',
  `username` varchar(25) DEFAULT NULL COMMENT 'Utilisateur autorisé à accéder au compte',
  `photo` varchar(64) DEFAULT NULL COMMENT 'Photo',
  `compte` int(11) DEFAULT NULL COMMENT 'Compte pilote',
  `comment` varchar(2048) DEFAULT NULL COMMENT 'Commentaires',
  `trigramme` varchar(12) DEFAULT NULL COMMENT 'Trigramme',
  `categorie` varchar(12) DEFAULT '' COMMENT 'Cat�gorie du pilote',
  `profession` varchar(64) DEFAULT NULL COMMENT 'Profession',
  `inst_glider` varchar(25) DEFAULT NULL COMMENT 'Instructeur planeur',
  `inst_airplane` varchar(25) DEFAULT NULL COMMENT 'Instructeur avion',
  `licfed` int(11) DEFAULT NULL COMMENT 'Numéro de licence fédérale',
  PRIMARY KEY (`mlogin`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO membres (`mlogin`, `mnom`, `mprenom`, `memail`, `memailparent`, `madresse`, `cp`, `ville`, `pays`, `mtelf`, `mtelm`, `mdaten`, `m25ans`, `mlieun`, `msexe`, `mniveaux`, `macces`, `club`, `ext`, `actif`, `username`, `photo`, `compte`, `comment`, `trigramme`, `categorie`, `profession`, `inst_glider`, `inst_airplane`, `licfed`) VALUES ('asterix', 'Legaulois', 'Astérix', 'asterix@free.fr', '', 'Village Gaulois', 0, 'Bretagne', '', '', '', '1960-01-01', 0, '0', 'M', '0', 0, 0, 0, 1, '0', '', 0, '', '', '0', '', '', '', NULL);
INSERT INTO membres (`mlogin`, `mnom`, `mprenom`, `memail`, `memailparent`, `madresse`, `cp`, `ville`, `pays`, `mtelf`, `mtelm`, `mdaten`, `m25ans`, `mlieun`, `msexe`, `mniveaux`, `macces`, `club`, `ext`, `actif`, `username`, `photo`, `compte`, `comment`, `trigramme`, `categorie`, `profession`, `inst_glider`, `inst_airplane`, `licfed`) VALUES ('obelix', 'Legaulois', 'Obélix', 'obelix@free.fr', '', 'Village Gaulois', 0, 'Bretagne', 'France', '', '0654321012', '1963-01-01', 0, '0', 'M', '0', 0, 0, 0, 1, '0', '', 0, '', '', '0', 'Livreur de Menhirs', '', '', NULL);
INSERT INTO membres (`mlogin`, `mnom`, `mprenom`, `memail`, `memailparent`, `madresse`, `cp`, `ville`, `pays`, `mtelf`, `mtelm`, `mdaten`, `m25ans`, `mlieun`, `msexe`, `mniveaux`, `macces`, `club`, `ext`, `actif`, `username`, `photo`, `compte`, `comment`, `trigramme`, `categorie`, `profession`, `inst_glider`, `inst_airplane`, `licfed`) VALUES ('abraracourcix', 'Chef', 'Abraracourcix', 'abraracourcix@hotmail.fr', '', '1 rue du vol à voile', 75000, 'Lutèce', 'Gaule', '', '', '1980-12-31', 0, '0', 'M', '502016', 0, 0, 0, 1, '0', '', 0, '', '', '0', 'Chef pilote', '', '', NULL);
INSERT INTO membres (`mlogin`, `mnom`, `mprenom`, `memail`, `memailparent`, `madresse`, `cp`, `ville`, `pays`, `mtelf`, `mtelm`, `mdaten`, `m25ans`, `mlieun`, `msexe`, `mniveaux`, `macces`, `club`, `ext`, `actif`, `username`, `photo`, `compte`, `comment`, `trigramme`, `categorie`, `profession`, `inst_glider`, `inst_airplane`, `licfed`) VALUES ('panoramix', 'Druide', 'Panoramix', 'pano@ffvv.org', '', 'Hutte ronde', 4500, 'Village gaulois', 'Gaule', '', '', '1933-12-12', 0, '0', 'M', '8192', 0, 0, 0, 1, '0', '', 0, '', '', '0', 'Druide', '', '', NULL);
INSERT INTO membres (`mlogin`, `mnom`, `mprenom`, `memail`, `memailparent`, `madresse`, `cp`, `ville`, `pays`, `mtelf`, `mtelm`, `mdaten`, `m25ans`, `mlieun`, `msexe`, `mniveaux`, `macces`, `club`, `ext`, `actif`, `username`, `photo`, `compte`, `comment`, `trigramme`, `categorie`, `profession`, `inst_glider`, `inst_airplane`, `licfed`) VALUES ('bonemine', 'Chef', 'Bonemine', 'bonemine@levillage.fr', '', 'Village Gaulois', 47000, 'Village Gaulois', 'Gaulle', '', '', '1963-06-23', 0, '0', 'F', '0', 0, 0, 0, 1, '0', '', 220, '', '', '0', 'Femme du chef', 'abraracourcix', 'abraracourcix', NULL);
INSERT INTO membres (`mlogin`, `mnom`, `mprenom`, `memail`, `memailparent`, `madresse`, `cp`, `ville`, `pays`, `mtelf`, `mtelm`, `mdaten`, `m25ans`, `mlieun`, `msexe`, `mniveaux`, `macces`, `club`, `ext`, `actif`, `username`, `photo`, `compte`, `comment`, `trigramme`, `categorie`, `profession`, `inst_glider`, `inst_airplane`, `licfed`) VALUES ('goudurix', 'Chef', 'Goudurix', 'goudurix@hotmail.fr', 'abraracourcix@free.fr', 'Rue des Thermes Romains', 75000, 'Lutece', 'Gaule', '', '', '2000-01-01', 1, '0', 'M', '524288', 0, 0, 0, 1, '0', '', 220, '', '', '0', 'Etudiant', '', '', NULL);


#
# TABLE STRUCTURE FOR: licences
#

DROP TABLE IF EXISTS licences;

CREATE TABLE `licences` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant',
  `pilote` varchar(25) COLLATE latin1_general_ci NOT NULL COMMENT 'Pilote',
  `type` tinyint(2) NOT NULL DEFAULT '0' COMMENT 'Type de licence',
  `year` int(4) NOT NULL COMMENT 'Année de validité',
  `date` date NOT NULL COMMENT 'Date de souscription',
  `comment` varchar(250) COLLATE latin1_general_ci NOT NULL COMMENT 'Commentaire',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=228 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

#
# TABLE STRUCTURE FOR: comptes
#

DROP TABLE IF EXISTS comptes;

CREATE TABLE `comptes` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant',
  `nom` varchar(48) NOT NULL COMMENT 'Nom du compte',
  `pilote` varchar(25) DEFAULT NULL COMMENT 'Référence du pilote',
  `desc` varchar(80) DEFAULT NULL COMMENT 'Description',
  `codec` varchar(10) NOT NULL COMMENT 'Code comptable',
  `actif` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Actif = 1, passif = 0',
  `debit` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT 'Débit',
  `credit` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT 'Crédit',
  `saisie_par` varchar(25) NOT NULL COMMENT 'Créateur',
  `club` tinyint(1) DEFAULT '0' COMMENT 'Gestion multi-club',
  PRIMARY KEY (`id`),
  KEY `codec` (`codec`),
  KEY `pilote` (`pilote`)
) ENGINE=MyISAM AUTO_INCREMENT=222 DEFAULT CHARSET=utf8;

INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (56, 'Vols d\'intiation', '', 'Vols d\'initiation', '706', 1, '4240.50', '5934.86', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (55, 'Heures de vol + remorqués', '', 'Heures de vol et remorqués', '706', 1, '47627.15', '47684.65', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (57, 'Fonds associatifs', NULL, 'Fonds associatifs', '102', 0, '157569.64', '549008.65', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (60, 'Essence + Huile', '', 'Essence + Huile', '606', 1, '15201.59', '11989.15', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (58, 'Subventions D.R.D.J.S', '', 'Subventions  D.R.D.J.S au titre d u C.N.D.S', '74', 1, '0.00', '0.00', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (61, 'Frais remorqueur', '', 'Frais remorqueur (suivi de nav, OSAC et entretien)', '615', 1, '14900.48', '14826.93', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (9, 'Subvention Conseil Général', NULL, 'Subventions conseil général', '74', 1, '1239.00', '1565.00', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (10, 'Bourses FFVV', NULL, 'Bourses FFVV', '754', 1, '5880.00', '6290.00', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (11, 'C.D.V.V.S', '', 'Aides comité départemental', '74', 1, '1450.00', '2475.00', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (12, 'Encaissement licences FFVV', NULL, 'Licences assurances de la F.F.V.V', '753', 1, '5351.75', '11069.50', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (13, 'Librairie aéro', NULL, 'Manuels de vo à voile, carnets de vol', '75', 1, '327.00', '768.50', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (14, 'Intérêts', NULL, 'Interêts de livrets d\'épargne', '76', 1, '2515.03', '3797.60', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (122, 'Convoyages, transport', '', 'Convoyages, transports, déplacements', '625', 1, '6109.49', '6102.48', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (16, 'Ventes diverses, T-shirts', '', 'Ventes diverses, maillots', '708', 1, '4289.10', '4462.81', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (19, 'Frais de bureau', '', 'Frais de bureau', '606', 1, '677.32', '576.88', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (21, 'Cotisations', NULL, 'Cotisation des membres', '756', 1, '3706.00', '4337.00', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (23, 'Banque vol à voile', NULL, 'Banque compte courant', '512', 1, '576883.59', '562027.86', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (59, 'C.R.V.V.P', '', 'Comité régional de vol à voile Picard', '74', 1, '3021.00', '4151.00', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (62, 'Frais d\'entretien planeurs', '', 'Entretien planeur', '615', 1, '15564.84', '12954.63', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (63, 'Assurances planeur', '', 'Assurance Casse et RC', '616', 1, '8426.31', '7190.08', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (64, 'G-NAV planeurs', '', 'G-NAV planeurs', '615', 1, '6275.80', '4817.80', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (65, 'Entretien remorques', '', 'Frais associés aux remorques', '615', 1, '285.95', '221.63', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (66, 'Parachutes', '', 'Entretien parachutes', '615', 1, '12196.04', '6680.76', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (67, 'Frais d\'atelier', '', 'Frais d\'atelier, documentation, véhicule de piste', '615', 1, '2046.77', '1971.97', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (68, 'Licences F.F.V.V', '', 'Licences et cotisation FFVV', '628', 1, '10522.50', '5950.25', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (112, 'Communications', '', 'Téléphone, Internet, timbres', '626', 1, '7932.62', '7647.97', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (70, 'Librairie aéronautique, carnets de vol', '', 'Manuels de l\'élève pilote, carnets de vol', '607', 1, '668.83', '172.83', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (71, 'Cotisation F.F.V.V', '', 'Cotisation club à la FFVV', '628', 1, '2227.40', '1640.05', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (72, 'Cotisation C.R.V.V.P', '', 'Cotisation au comité régional de vol à voile', '628', 1, '730.00', '730.00', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (219, 'Legaulois Obélix', 'obelix', 'Compte pilote', '411', 1, '13.50', '300.00', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (120, 'Assurances Remorqueur', '', 'Assurance Casse et RC', '616', 1, '17067.23', '12715.83', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (99, 'Report à nouveau créditeur', '', '', '110', 0, '8715.58', '8715.58', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (101, 'Report à nouveau débiteur', '', '', '119', 0, '3495.44', '3495.44', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (102, 'CRVVP - Subventions', '', 'CRVVP - Subventions', '441', 1, '520.00', '520.00', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (106, 'Frais repas', '', 'Frais repas', '623', 1, '11248.59', '9165.36', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (107, 'Recettes repas', '', 'Recettes repas', '708', 1, '13235.85', '16213.85', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (128, 'Remorqueur F-BLIT', '', 'Immobilisation Remorqueur F-BLIT', '215', 1, '24000.00', '24000.00', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (126, 'Heures de vol et lancements', '', 'Achat d\'heures de vols ou de lancements à d\'autres clubs', '611', 1, '14045.91', '13743.91', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (121, 'Achats radios, instruments', '', 'Achat matériel, équipement', '606', 1, '7650.19', '9406.42', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (129, 'Ask21 F-CJRG', '', 'Immobilisation Ask21 F-CJRG', '215', 1, '40000.00', '0.00', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (130, 'Twin F-CFYD', '', 'Immobilisation Twin F-CFYD', '215', 1, '23000.00', '23000.00', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (131, 'WA30 F-CDUC', '', 'Immobilisation WA30 F-CDUC', '215', 1, '500.00', '500.00', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (132, 'C101 F-CGNP', '', 'Immobilisation C101 F-CGNP', '215', 1, '13000.00', '0.00', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (133, 'Caisse', '', 'Caisse', '512', 1, '0.00', '0.00', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (134, 'PIWI F-CICA', '', 'Immobilisation planeur PIWI', '215', 1, '8000.00', '0.00', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (135, 'C101 F-CGHF', '', 'Immobilisation C101 F-CGHF', '215', 1, '18500.00', '0.00', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (136, 'Remorques, outillage', '', 'Immobilisation remorques et outillage', '215', 1, '22000.00', '2000.00', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (137, 'Parachutes', '', 'Immobilisation parachutes', '215', 1, '2500.00', '0.00', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (143, 'C101 F-CGBR', '', 'Immobilisation C101 F-CGBR', '215', 1, '15000.00', '0.00', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (144, 'Redistributions Bourses F.F.V.V', '', 'Redistribution des Bourses F.F.V.V', '657', 1, '4420.00', '3810.00', 'agnes', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (145, 'Opale Aero Services', '', 'Compte fournisseur Opale', '401', 1, '89.00', '89.00', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (147, 'Remorqueur Dynamic F-JUFA', '', 'Immobilisation Dynamic', '215', 1, '118323.87', '0.00', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (153, 'Convoyage', '', 'Facturation aux pilotes de frais de convoyage', '774', 1, '4061.08', '4068.09', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (164, 'Valorisation d\'immobilisations', '', 'Réévaluation à la hausse d\'immobilisation - Améliorations - Hausse du marché', '781', 1, '4500.00', '4500.00', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (156, 'Immobilisation Janus F-CFAJ', '', 'Janus F-CFAJ', '215', 1, '25000.00', '0.00', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (157, 'Dotation aux amortissements et dépréciations', '', 'Dotation aux amortissements et dépréciations de matériel', '68', 1, '40850.00', '32175.00', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (158, 'Subvention C.N.D.S', '', 'Centre National pour le Développement du Sport', '74', 1, '810.00', '810.00', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (159, 'Vol moteur', '', 'Opérations avec le vol moteur', '46', 1, '421.72', '421.72', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (160, 'Résultat de l’exercice (excédent)', '', 'Résultat de l’exercice (excédent)', '120', 1, '74657.42', '129298.54', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (161, 'Résultat de l’exercice (déficit)', '', 'Résultat de l’exercice (déficit)', '129', 1, '160743.96', '106277.34', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (163, 'Ventes de planeurs et avions', '', 'Ventes de planeurs et avions', '775', 1, '6001.00', '8001.00', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (169, 'Heures ULM', '', 'Heures de vol ULM', '706', 1, '3138.00', '6233.00', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (168, 'Remorqués', '', 'Vente de remorqués', '706', 1, '8003.00', '16005.00', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (170, 'Heures planeurs', '', 'Ventes d\'heures de vol planeur', '706', 1, '11008.52', '21638.74', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (197, 'Amortissements Remorqueur', '', 'Amortissements Remorqueur', '281', 1, '0.00', '6350.00', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (198, 'Ammortissement planeurs', '', 'Amortissement et provisions pour remplacements', '281', 1, '0.00', '8500.00', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (200, 'Créances irrécouvrables', '', 'Créances irrécouvrables, factures qui ne seront jamais payées', '654', 1, '76.99', '76.99', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (204, 'Frais financiers', '', 'Frais cartes bleues, agios, intérêts', '66', 1, '187.97', '7.57', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (207, 'Dons divers', '', 'Dons, pourboires', '74', 1, '0.00', '634.50', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (221, 'Druide Panoramix', 'panoramix', 'Compte pilote', '411', 1, '225.00', '0.00', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (220, 'Chef Abraracourcix', 'abraracourcix', 'Compte pilote', '411', 1, '89.00', '100.00', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (215, 'Divers, Cotisations club', '', 'Reversements divers', '628', 1, '175.00', '0.00', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (218, 'Legaulois Astérix', 'asterix', 'Compte pilote', '411', 1, '70.50', '500.00', 'fpeignot', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (217, 'C101 F-CGBC', '', 'Immobilisation C101 F-CGBR', '215', 1, '5001.00', '0.00', 'fpeignot', 0);


#
# TABLE STRUCTURE FOR: ecritures
#

DROP TABLE IF EXISTS ecritures;

CREATE TABLE `ecritures` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant',
  `annee_exercise` int(11) NOT NULL COMMENT 'Année d''exercice',
  `date_creation` date NOT NULL COMMENT 'Date',
  `date_op` date NOT NULL COMMENT 'Date de l''opération',
  `compte1` int(11) NOT NULL COMMENT 'Emploi',
  `compte2` int(11) NOT NULL COMMENT 'Ressource',
  `montant` decimal(8,2) NOT NULL COMMENT 'Montant de l''écriture',
  `description` varchar(80) CHARACTER SET utf8 NOT NULL COMMENT 'Libellé',
  `type` int(11) DEFAULT '0' COMMENT 'Type de paiement',
  `num_cheque` varchar(50) CHARACTER SET utf8 DEFAULT NULL COMMENT 'Numéro de piéce comptable',
  `saisie_par` varchar(25) CHARACTER SET utf8 NOT NULL COMMENT 'Opérateur',
  `gel` int(11) DEFAULT '0' COMMENT 'Vérifié',
  `club` tinyint(1) DEFAULT '0' COMMENT 'Gestion multi-club',
  `achat` int(11) DEFAULT NULL COMMENT 'Achat correspondant',
  `quantite` varchar(11) CHARACTER SET utf8 DEFAULT '0' COMMENT 'Quantitè de l''achat',
  `prix` decimal(8,2) DEFAULT '-1.00' COMMENT 'Prix de l''achat',
  `categorie` int(11) NOT NULL DEFAULT '0' COMMENT 'Catégorie de dépense ou recette',
  PRIMARY KEY (`id`),
  KEY `compte1` (`compte1`),
  KEY `saisie_par` (`saisie_par`),
  KEY `compte2` (`compte2`)
) ENGINE=MyISAM AUTO_INCREMENT=8826 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO ecritures (`id`, `annee_exercise`, `date_creation`, `date_op`, `compte1`, `compte2`, `montant`, `description`, `type`, `num_cheque`, `saisie_par`, `gel`, `club`, `achat`, `quantite`, `prix`, `categorie`) VALUES (8811, 0, '2015-04-15', '2015-04-15', 221, 169, '150.00', '15/04/2015  0.00-1.50 F-JUFA', 0, 'Heure de vol Dynamic', 'fpeignot', 0, 0, 7169, '1.5', '100.00', 0);
INSERT INTO ecritures (`id`, `annee_exercise`, `date_creation`, `date_op`, `compte1`, `compte2`, `montant`, `description`, `type`, `num_cheque`, `saisie_par`, `gel`, `club`, `achat`, `quantite`, `prix`, `categorie`) VALUES (8812, 0, '2015-04-15', '2015-04-15', 221, 169, '75.00', '15/04/2015  1.50-2.25 F-JUFA', 0, 'Heure de vol Dynamic', 'fpeignot', 0, 0, 7170, '0.75', '100.00', 0);
INSERT INTO ecritures (`id`, `annee_exercise`, `date_creation`, `date_op`, `compte1`, `compte2`, `montant`, `description`, `type`, `num_cheque`, `saisie_par`, `gel`, `club`, `achat`, `quantite`, `prix`, `categorie`) VALUES (8813, 0, '2015-04-15', '2015-04-15', 218, 168, '22.00', ' à 16h00 ', 0, 'Remorqué 500m', 'fpeignot', 0, 0, 7171, '1', '22.00', 0);
INSERT INTO ecritures (`id`, `annee_exercise`, `date_creation`, `date_op`, `compte1`, `compte2`, `montant`, `description`, `type`, `num_cheque`, `saisie_par`, `gel`, `club`, `achat`, `quantite`, `prix`, `categorie`) VALUES (8814, 0, '2015-04-15', '2015-04-15', 218, 170, '13.50', ' à 16h00  sur F-CJRG', 0, 'Heure de vol biplace', 'fpeignot', 0, 0, 7172, ' 0h30', '27.00', 0);
INSERT INTO ecritures (`id`, `annee_exercise`, `date_creation`, `date_op`, `compte1`, `compte2`, `montant`, `description`, `type`, `num_cheque`, `saisie_par`, `gel`, `club`, `achat`, `quantite`, `prix`, `categorie`) VALUES (8818, 0, '2015-04-15', '2015-04-15', 219, 170, '13.50', ' à 16h30  sur F-CJRG', 0, 'Heure de vol biplace', 'testadmin', 0, 0, 7175, ' 0h30', '27.00', 0);
INSERT INTO ecritures (`id`, `annee_exercise`, `date_creation`, `date_op`, `compte1`, `compte2`, `montant`, `description`, `type`, `num_cheque`, `saisie_par`, `gel`, `club`, `achat`, `quantite`, `prix`, `categorie`) VALUES (8817, 2015, '2015-04-15', '2015-04-15', 60, 23, '120.00', 'Essence 100 l', 0, 'Chèque 4567', 'fpeignot', 0, 0, 0, '0', '0.00', 0);
INSERT INTO ecritures (`id`, `annee_exercise`, `date_creation`, `date_op`, `compte1`, `compte2`, `montant`, `description`, `type`, `num_cheque`, `saisie_par`, `gel`, `club`, `achat`, `quantite`, `prix`, `categorie`) VALUES (8819, 0, '2015-05-09', '2015-04-01', 218, 168, '8.00', ' à 12h00 ', 0, 'Treuillé', 'testadmin', 0, 0, 7176, '1', '8.00', 0);
INSERT INTO ecritures (`id`, `annee_exercise`, `date_creation`, `date_op`, `compte1`, `compte2`, `montant`, `description`, `type`, `num_cheque`, `saisie_par`, `gel`, `club`, `achat`, `quantite`, `prix`, `categorie`) VALUES (8820, 0, '2015-05-09', '2015-04-01', 218, 170, '27.00', ' à 12h00  sur F-CJRG', 0, 'Heure de vol biplace', 'testadmin', 0, 0, 7177, ' 1h00', '27.00', 0);
INSERT INTO ecritures (`id`, `annee_exercise`, `date_creation`, `date_op`, `compte1`, `compte2`, `montant`, `description`, `type`, `num_cheque`, `saisie_par`, `gel`, `club`, `achat`, `quantite`, `prix`, `categorie`) VALUES (8821, 0, '2015-05-09', '2015-04-02', 220, 168, '8.00', ' à 12h30  Chef Bonemine', 0, 'Treuillé', 'testadmin', 0, 0, 7178, '1', '8.00', 0);
INSERT INTO ecritures (`id`, `annee_exercise`, `date_creation`, `date_op`, `compte1`, `compte2`, `montant`, `description`, `type`, `num_cheque`, `saisie_par`, `gel`, `club`, `achat`, `quantite`, `prix`, `categorie`) VALUES (8822, 0, '2015-05-09', '2015-04-02', 220, 170, '81.00', ' à 12h30  sur F-CJRG Chef Bonemine', 0, 'Heure de vol biplace', 'testadmin', 0, 0, 7179, ' 3h00', '27.00', 0);
INSERT INTO ecritures (`id`, `annee_exercise`, `date_creation`, `date_op`, `compte1`, `compte2`, `montant`, `description`, `type`, `num_cheque`, `saisie_par`, `gel`, `club`, `achat`, `quantite`, `prix`, `categorie`) VALUES (8823, 2015, '2015-05-09', '2015-01-01', 23, 220, '100.00', 'Avance sur vol', 0, 'C1 1234567', 'testadmin', 0, 0, 0, '0', '0.00', 0);
INSERT INTO ecritures (`id`, `annee_exercise`, `date_creation`, `date_op`, `compte1`, `compte2`, `montant`, `description`, `type`, `num_cheque`, `saisie_par`, `gel`, `club`, `achat`, `quantite`, `prix`, `categorie`) VALUES (8824, 2015, '2015-05-09', '2015-02-02', 23, 218, '500.00', 'Avance sur vols', 0, 'CL1234567', 'testadmin', 0, 0, 0, '0', '0.00', 0);
INSERT INTO ecritures (`id`, `annee_exercise`, `date_creation`, `date_op`, `compte1`, `compte2`, `montant`, `description`, `type`, `num_cheque`, `saisie_par`, `gel`, `club`, `achat`, `quantite`, `prix`, `categorie`) VALUES (8825, 2015, '2015-05-09', '2015-03-03', 23, 219, '300.00', 'Avance sur vols', 0, 'CL 9876543', 'testadmin', 0, 0, 0, '0', '0.00', 0);


#
# TABLE STRUCTURE FOR: machinesp
#

DROP TABLE IF EXISTS machinesp;

CREATE TABLE `machinesp` (
  `mpconstruc` varchar(64) NOT NULL,
  `mpmodele` varchar(32) NOT NULL,
  `mpimmat` varchar(10) NOT NULL COMMENT 'immatriculation',
  `mpnumc` varchar(5) DEFAULT NULL COMMENT 'Numéro de concours',
  `mpnbhdv` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT 'Heures hors système',
  `mpbiplace` varchar(1) DEFAULT '1' COMMENT 'Nombre de places',
  `mpautonome` tinyint(1) DEFAULT NULL COMMENT 'Autonome',
  `mptreuil` tinyint(1) DEFAULT NULL COMMENT 'Treuillable',
  `mpprive` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Propriétaire',
  `club` tinyint(1) DEFAULT '0' COMMENT 'Gestion multi-club',
  `mprix` varchar(32) DEFAULT NULL COMMENT 'Prix de l''heure',
  `mprix_forfait` varchar(32) DEFAULT NULL COMMENT 'Prix de l''heure au forfait',
  `mprix_moteur` varchar(32) DEFAULT NULL COMMENT 'Prix de l''heure moteur',
  `mmax_facturation` int(11) NOT NULL DEFAULT '180' COMMENT 'Temps max de facturation en minutes',
  `actif` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Machine active',
  `comment` varchar(250) DEFAULT NULL COMMENT 'Description',
  `horametre_en_minutes` int(11) DEFAULT '0' COMMENT 'Horamètre en heures et minutes',
  `fabrication` int(11) DEFAULT NULL COMMENT 'Année de mise en service',
  `banalise` tinyint(1) DEFAULT NULL COMMENT 'Machine banalisée',
  `proprio` varchar(25) DEFAULT NULL COMMENT 'Propriétaire',
  PRIMARY KEY (`mpimmat`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Alexander Schleicher', 'Asw20', 'F-CERP', 'UP', '1.00', '1', 0, 1, 1, 0, 'Heure de vol Pégase', 'Heure de vol forfait', 'Gratuit', 0, 1, '', 0, 1976, 1, 'abraracourcix');
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Grob', 'G103', 'F-CFYD', 'T83', '0.00', '2', 0, 1, 0, 0, 'Heure de vol biplace', 'Heure de vol forfait', 'Gratuit', 180, 0, '', 0, 1985, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Centrair', 'Pégase', 'F-CGFN', '3S', '0.00', '1', 0, 1, 1, 0, 'Gratuit', 'Gratuit', 'Gratuit', 0, 1, NULL, 0, NULL, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('A.SCHLEICHER S.F.B.', 'Asw20', 'F-CGKS', 'WE', '0.00', '1', 0, 0, 1, 0, 'Gratuit', 'Gratuit', 'Gratuit', 0, 1, '', 0, 1982, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Centrair', 'Pégase', 'F-CGNP', 'Y31', '0.00', '1', 0, 0, 0, 0, 'Heure de vol Pégase', 'Heure de vol forfait', 'Gratuit', 180, 1, '', 0, 1987, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Glaser-Dirks', 'DG400', 'F-CGRD', 'RD', '0.00', '1', 1, 1, 1, 0, 'Gratuit', 'Gratuit', 'Gratuit', 0, 1, NULL, 0, NULL, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Politechnika Warszawska', 'PW-5', 'F-CICA', 'CA', '0.00', '1', 0, 1, 0, 0, 'Heure de vol Piwi', 'Heure de vol forfait', 'Gratuit', 180, 1, '', 0, 1995, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Alexander Schleicher', 'Ask21', 'F-CJRG', 'RG', '0.00', '2', 0, 1, 0, 0, 'Heure de vol biplace', 'Heure de vol forfait', 'Gratuit', 180, 1, '', 0, 2001, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Centrair', 'Pégase', 'F-CGBR', 'SR', '1000.00', '1', 0, 1, 0, 0, 'Heure de vol Pégase', 'Heure de vol forfait', 'Gratuit', 180, 1, '', 0, 1990, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Alexander Schleicher', 'KA6e', 'F-CDRE', 'LA', '0.00', '1', 0, 1, 1, 0, 'Gratuit', 'Gratuit', 'Gratuit', 0, 1, NULL, 0, NULL, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Siren Edelweiss', 'C30S', 'F-CCUI', 'UI', '0.00', '1', 0, 0, 1, 0, 'Gratuit', 'Gratuit', 'Gratuit', 0, 1, '', 0, 1965, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Centrair', 'Pégase', 'F-CGSB', '288', '0.00', '1', 0, 1, 0, 0, 'Heure de vol Pégase', 'Heure de vol forfait', 'Gratuit', 180, 0, 'Pégase de Troyes', 0, 0, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('XXX', 'Extérieur', 'F-CXXX', '', '0.00', '1', 0, 1, 2, 0, 'Gratuit', 'Gratuit', 'Gratuit', 0, 1, NULL, 0, NULL, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Alexander Schleicher', 'Ask13', 'F-CECO', '', '0.00', '2', 0, 1, 0, 0, 'Heure de vol biplace', 'Heure de vol forfait', 'Gratuit', 180, 0, '', 0, 0, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Centrair', 'Pégase', 'F-CGHF', 'E9', '2000.00', '1', 0, 1, 0, 0, 'Heure de vol Pégase', 'Heure de vol forfait', 'Gratuit', 180, 1, '', 0, 1992, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Centrair', 'pégase', 'F-CHDB', '2', '0.00', '1', 0, 1, 0, 0, 'Heure de vol Pégase', 'Heure de vol forfait', 'Gratuit', 180, 0, '', 0, 0, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Glasser-Dirks Flugzeugbau GmbH', 'DG 202', 'D-1085', 'A3', '0.00', '1', 0, 1, 2, 0, 'Heure de vol Pégase', 'Heure de vol forfait', 'Gratuit', 180, 0, '', 0, 0, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('SCHEMPP-HIRTH', 'Janus B', 'F-CFAJ', 'CJ', '7262.00', '2', 0, 1, 0, 0, 'Heure de vol biplace', 'Heure de vol forfait', 'Gratuit', 180, 1, 'N°153 Année 2e T 1982', 0, 1982, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Scheibe', 'SF28', 'F-CEYB', '', '0.00', '2', 1, 0, 0, 0, 'Heure de vol biplace', 'Heure de vol forfait', 'Heure moteur SF28', 180, 1, '', 0, 0, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('LAK - Lietuviškos Aviacinės', 'LAK17AT', 'D-KBDE', 'LC', '0.00', '1', 0, 0, 2, 0, 'Gratuit', 'Gratuit', 'Gratuit', 180, 0, '', 0, 0, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Alexander Schleicher', 'Asw24', 'D-8183', '7H', '0.00', '1', 0, 0, 2, 0, 'Gratuit', 'Gratuit', 'Gratuit', 180, 0, '', 0, 0, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Schempp-Hirth', 'Discus', 'F-KAH', 'XX', '0.00', '1', 0, 0, 2, 0, 'Gratuit', 'Gratuit', 'Gratuit', 180, 0, '', 0, 0, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Schempp-Hirth', 'Discus bT', 'F-CKAH', 'AH', '0.00', '1', 0, 0, 2, 0, 'Gratuit', 'Gratuit', 'Gratuit', 180, 0, '', 0, 0, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Schempp-Hirth', 'Ventus B', 'D5277', 'FP', '0.00', '1', 0, 0, 2, 0, 'Gratuit', 'Gratuit', 'Gratuit', 180, 0, '', 0, 0, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Alisport', 'Silent 2', '59DEC', '7', '0.00', '1', 1, 0, 2, 0, 'Gratuit', 'Gratuit', 'Gratuit', 180, 0, '', 0, 0, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Centrair', 'Marianne', 'F-CGMJ', 'MJ', '0.00', '2', 0, 1, 2, 0, 'Gratuit', 'Gratuit', 'Gratuit', 180, 0, '', 0, 0, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Centrair', 'Pégase', 'F-CGNL', 'NL', '0.00', '1', 0, 1, 2, 0, 'Gratuit', 'Gratuit', 'Gratuit', 180, 0, '', 0, 0, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('SCHEMPP-HIRTH', 'janus CE', 'GCJFE', '16', '0.00', '2', 0, 0, 2, 0, 'Gratuit', 'Gratuit', 'Gratuit', 180, 0, '', 0, 0, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Rolladen-Schneider', 'LS3', 'F-CESF', 'S03', '0.00', '1', 0, 0, 2, 0, 'Gratuit', 'Gratuit', 'Gratuit', 180, 0, '', 0, 0, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Glaser-Dirks', 'DG-500', 'F-CHJD', 'DG1', '0.00', '2', 0, 0, 2, 0, 'Gratuit', 'Gratuit', 'Gratuit', 180, 0, '', 0, 0, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Glaser-Dirks', 'DG 400 17m', 'D-KLPP', 'PP', '0.00', '1', 1, 0, 2, 0, 'Gratuit', 'Gratuit', 'Gratuit', 180, 0, '', 0, 0, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Centrair', 'Pégase', 'F-CGEN', 'EN', '0.00', '1', 0, 0, 2, 0, 'Gratuit', 'Gratuit', 'Gratuit', 180, 0, 'Merville', 0, 0, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Rolladen-Schneider', 'LS6B', 'F-CBET', 'ET', '0.00', '1', 0, 0, 2, 0, 'Gratuit', 'Gratuit', 'Gratuit', 180, 0, 'Lille', 0, 0, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Centrair', 'Pégase', 'F-CHDR', 'DR', '0.00', '1', 0, 1, 2, 0, 'Gratuit', 'Gratuit', 'Gratuit', 180, 0, 'Lille', 0, 0, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Schempp-Hirth', 'Janus CM', 'F-CGQI', 'QI', '0.00', '2', 1, 0, 2, 0, 'Gratuit', 'Gratuit', 'Gratuit', 180, 0, 'Merville', 0, 0, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Alexander Schleicher', 'ASW24E', 'F-CHAH', 'TT', '0.00', '1', 1, 0, 2, 0, 'Gratuit', 'Gratuit', 'Gratuit', 180, 0, '', 0, 0, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Schempp-Hirth', 'JanusB', 'F-CCAA', 'AA', '0.00', '2', 0, 0, 2, 0, 'Gratuit', 'Gratuit', 'Gratuit', 180, 0, 'Lille', 0, 0, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Schempp-Hirth', 'Discus 2ct', 'D-KBBT', 'KT', '0.00', '1', 0, 0, 2, 0, 'Gratuit', 'Gratuit', 'Gratuit', 180, 0, 'Faucheurs de marguerite/Belgique', 0, 0, NULL, NULL);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`, `fabrication`, `banalise`, `proprio`) VALUES ('Centrair', 'Pégase', 'F-CFXR', 'B114', '0.00', '1', 0, 0, 2, 0, 'Gratuit', 'Gratuit', 'Gratuit', 180, 1, '', 0, 0, NULL, NULL);


#
# TABLE STRUCTURE FOR: machinesa
#

DROP TABLE IF EXISTS machinesa;

CREATE TABLE `machinesa` (
  `macconstruc` varchar(64) NOT NULL,
  `macmodele` varchar(24) NOT NULL,
  `macimmat` varchar(10) NOT NULL DEFAULT 'F-B' COMMENT 'Immatriculation',
  `macnbhdv` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT 'Heures hors système',
  `macplaces` tinyint(1) DEFAULT '2' COMMENT 'Nombre de places',
  `macrem` tinyint(1) DEFAULT NULL COMMENT 'Avion remorqueur',
  `maprive` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Machine privée',
  `club` tinyint(1) DEFAULT '0' COMMENT 'Gestion multi-club',
  `actif` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Machine active',
  `comment` varchar(250) DEFAULT NULL COMMENT 'Description',
  `maprix` varchar(32) NOT NULL COMMENT 'prix de l''heure',
  `maprixdc` varchar(32) DEFAULT NULL COMMENT 'Prix double commande',
  `horametre_en_minutes` int(11) DEFAULT '0' COMMENT 'Horamètre en heures et minutes',
  `fabrication` int(11) DEFAULT NULL COMMENT 'Année de mise en service',
  PRIMARY KEY (`macimmat`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO machinesa (`macconstruc`, `macmodele`, `macimmat`, `macnbhdv`, `macplaces`, `macrem`, `maprive`, `club`, `actif`, `comment`, `maprix`, `maprixdc`, `horametre_en_minutes`, `fabrication`) VALUES ('SOCATA', 'MS893-L', 'F-BLIT', '12.00', 2, 1, 0, 0, 0, '0', 'Heure de vol remorqueur', 'Gratuit', 0, 1980);
INSERT INTO machinesa (`macconstruc`, `macmodele`, `macimmat`, `macnbhdv`, `macplaces`, `macrem`, `maprive`, `club`, `actif`, `comment`, `maprix`, `maprixdc`, `horametre_en_minutes`, `fabrication`) VALUES ('SOCATA', 'MSS893', 'F-BSDH', '506.00', 2, 1, 0, 0, 0, '', 'Heure de vol remorqueur', 'Gratuit', 1, NULL);
INSERT INTO machinesa (`macconstruc`, `macmodele`, `macimmat`, `macnbhdv`, `macplaces`, `macrem`, `maprive`, `club`, `actif`, `comment`, `maprix`, `maprixdc`, `horametre_en_minutes`, `fabrication`) VALUES ('AEROSPOOL', 'Dynamic', 'F-JTXF', '0.00', 2, 1, 0, 0, 0, '', 'Heure de vol Dynamic', 'Gratuit', 0, 2013);
INSERT INTO machinesa (`macconstruc`, `macmodele`, `macimmat`, `macnbhdv`, `macplaces`, `macrem`, `maprive`, `club`, `actif`, `comment`, `maprix`, `maprixdc`, `horametre_en_minutes`, `fabrication`) VALUES ('AEROPOOL', 'Dynamic', 'F-JUFA', '6.10', 2, 1, 0, 0, 1, 'DY 454/2012', 'Heure de vol Dynamic', 'Heure de vol Dynamic', 0, 2012);
INSERT INTO machinesa (`macconstruc`, `macmodele`, `macimmat`, `macnbhdv`, `macplaces`, `macrem`, `maprive`, `club`, `actif`, `comment`, `maprix`, `maprixdc`, `horametre_en_minutes`, `fabrication`) VALUES ('AEROSPOOL', 'Dynamic', 'F-FTHT', '0.00', 2, 1, 1, 0, 0, '', 'Gratuit', 'Gratuit', 0, 0);
INSERT INTO machinesa (`macconstruc`, `macmodele`, `macimmat`, `macnbhdv`, `macplaces`, `macrem`, `maprive`, `club`, `actif`, `comment`, `maprix`, `maprixdc`, `horametre_en_minutes`, `fabrication`) VALUES ('MK9', 'MK9', 'MK9', '0.00', 2, 1, 0, 0, 0, '', 'Gratuit', 'Gratuit', 0, 0);


#
# TABLE STRUCTURE FOR: volsp
#

DROP TABLE IF EXISTS volsp;

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
  `vpautonome` tinyint(4) DEFAULT '3' COMMENT 'Lancement',
  `vpnumvi` varchar(20) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL COMMENT 'Numéro de VI',
  `vpnbkm` int(11) DEFAULT '0' COMMENT 'Nombre de Km',
  `vplieudeco` varchar(15) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL COMMENT 'Lieu de décollage',
  `vplieuatt` varchar(15) DEFAULT NULL COMMENT 'Lieu d''atterrissage',
  `vpaltrem` int(11) DEFAULT '500' COMMENT 'Altitude de remorquage',
  `vpinst` varchar(25) DEFAULT NULL COMMENT 'Instructeur',
  `facture` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Vrai quand le vol a été facturé',
  `payeur` varchar(25) DEFAULT NULL COMMENT 'Payeur du vol quand ce n''est pas le pilote',
  `pourcentage` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Pourcentage du vol pour le payeur, unité = 50%',
  `club` tinyint(1) DEFAULT '0' COMMENT 'Gestion multi-club',
  `saisie_par` varchar(25) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL COMMENT 'Planchard',
  `remorqueur` varchar(10) DEFAULT NULL COMMENT 'Avion remorqueur',
  `pilote_remorqueur` varchar(25) DEFAULT NULL COMMENT 'Pilote remorqueur',
  `tempmoteur` decimal(6,2) DEFAULT '0.00' COMMENT 'Temps moteur',
  `reappro` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Ravitaillement',
  `essence` int(11) DEFAULT '0',
  `vpticcolle` tinyint(1) NOT NULL COMMENT 'Si ticket collé ou pas',
  PRIMARY KEY (`vpid`),
  KEY `saisie_par` (`saisie_par`),
  KEY `pilote_remorqueur` (`pilote_remorqueur`),
  KEY `remorqueur` (`remorqueur`)
) ENGINE=MyISAM AUTO_INCREMENT=2882 DEFAULT CHARSET=utf8;

INSERT INTO volsp (`vpid`, `vpdate`, `vppilid`, `vpmacid`, `vpcdeb`, `vpcfin`, `vpduree`, `vpobs`, `vpdc`, `vpcategorie`, `vpautonome`, `vpnumvi`, `vpnbkm`, `vplieudeco`, `vplieuatt`, `vpaltrem`, `vpinst`, `facture`, `payeur`, `pourcentage`, `club`, `saisie_par`, `remorqueur`, `pilote_remorqueur`, `tempmoteur`, `reappro`, `essence`, `vpticcolle`) VALUES (2878, '2015-04-15', 'asterix', 'F-CJRG', '16.00', '16.30', '30.00', '', 1, 0, 3, '', 0, '', '', 500, 'abraracourcix', 0, '', 0, 0, 'fpeignot', 'F-JUFA', 'panoramix', '0.00', 0, 0, 0);
INSERT INTO volsp (`vpid`, `vpdate`, `vppilid`, `vpmacid`, `vpcdeb`, `vpcfin`, `vpduree`, `vpobs`, `vpdc`, `vpcategorie`, `vpautonome`, `vpnumvi`, `vpnbkm`, `vplieudeco`, `vplieuatt`, `vpaltrem`, `vpinst`, `facture`, `payeur`, `pourcentage`, `club`, `saisie_par`, `remorqueur`, `pilote_remorqueur`, `tempmoteur`, `reappro`, `essence`, `vpticcolle`) VALUES (2879, '2015-04-15', 'obelix', 'F-CJRG', '16.30', '17.00', '30.00', '', 0, 0, 3, '', 0, '', '', 500, '', 0, '', 0, 0, 'testadmin', 'F-JUFA', 'panoramix', '0.00', 0, 0, 0);
INSERT INTO volsp (`vpid`, `vpdate`, `vppilid`, `vpmacid`, `vpcdeb`, `vpcfin`, `vpduree`, `vpobs`, `vpdc`, `vpcategorie`, `vpautonome`, `vpnumvi`, `vpnbkm`, `vplieudeco`, `vplieuatt`, `vpaltrem`, `vpinst`, `facture`, `payeur`, `pourcentage`, `club`, `saisie_par`, `remorqueur`, `pilote_remorqueur`, `tempmoteur`, `reappro`, `essence`, `vpticcolle`) VALUES (2880, '2015-04-01', 'asterix', 'F-CJRG', '12.00', '13.00', '60.00', '', 0, 0, 1, '', 0, 'LFQB', 'LFQB', 500, '', 0, '', 0, 0, 'testadmin', '', 'goudurix', '0.00', 0, 0, 0);
INSERT INTO volsp (`vpid`, `vpdate`, `vppilid`, `vpmacid`, `vpcdeb`, `vpcfin`, `vpduree`, `vpobs`, `vpdc`, `vpcategorie`, `vpautonome`, `vpnumvi`, `vpnbkm`, `vplieudeco`, `vplieuatt`, `vpaltrem`, `vpinst`, `facture`, `payeur`, `pourcentage`, `club`, `saisie_par`, `remorqueur`, `pilote_remorqueur`, `tempmoteur`, `reappro`, `essence`, `vpticcolle`) VALUES (2881, '2015-04-02', 'bonemine', 'F-CJRG', '12.30', '16.00', '210.00', '', 0, 0, 1, '', 0, '', '', 500, '', 0, '', 0, 0, 'testadmin', '', 'goudurix', '0.00', 0, 0, 0);


#
# TABLE STRUCTURE FOR: volsa
#

DROP TABLE IF EXISTS volsa;

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
  `facture` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Facturé',
  `payeur` varchar(25) DEFAULT NULL COMMENT 'Payeur si ce n''est pas le pilote',
  `pourcentage` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Pourcentage payé par le payeur, unité = 50%',
  `club` tinyint(1) DEFAULT '0' COMMENT 'Gestion multi-club',
  `gel` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Modification interdites',
  `saisie_par` varchar(25) DEFAULT NULL COMMENT 'Planchard',
  `vaatt` int(11) NOT NULL DEFAULT '1' COMMENT 'Nombre d''atterrissages',
  `local` tinyint(4) DEFAULT '0' COMMENT 'Eloignement',
  `nuit` tinyint(4) DEFAULT '0' COMMENT 'Vol de nuit',
  `reappro` tinyint(4) DEFAULT '0' COMMENT 'Ravitaillement',
  `essence` int(11) DEFAULT '0' COMMENT 'Quantité d''essence',
  `vahdeb` decimal(4,2) DEFAULT NULL COMMENT 'Heure de décollage',
  `vahfin` decimal(4,2) DEFAULT NULL COMMENT 'Heure d''atterrissage',
  PRIMARY KEY (`vaid`),
  KEY `vapilid` (`vapilid`),
  KEY `vamacid` (`vamacid`),
  KEY `saisie_par` (`saisie_par`)
) ENGINE=MyISAM AUTO_INCREMENT=765 DEFAULT CHARSET=utf8;

INSERT INTO volsa (`vaid`, `vadate`, `vapilid`, `vamacid`, `vacdeb`, `vacfin`, `vaduree`, `vaobs`, `vadc`, `vacategorie`, `varem`, `vanumvi`, `vanbpax`, `vaprixvol`, `vainst`, `valieudeco`, `valieuatt`, `facture`, `payeur`, `pourcentage`, `club`, `gel`, `saisie_par`, `vaatt`, `local`, `nuit`, `reappro`, `essence`, `vahdeb`, `vahfin`) VALUES (763, '2015-04-15', 'panoramix', 'F-JUFA', '0.00', '1.50', '1.50', '', 0, 0, 0, '', '', '0.00', '', '', '', 0, '', 0, 0, 0, 'fpeignot', 1, 0, 0, 0, 0, '12.00', '13.00');
INSERT INTO volsa (`vaid`, `vadate`, `vapilid`, `vamacid`, `vacdeb`, `vacfin`, `vaduree`, `vaobs`, `vadc`, `vacategorie`, `varem`, `vanumvi`, `vanbpax`, `vaprixvol`, `vainst`, `valieudeco`, `valieuatt`, `facture`, `payeur`, `pourcentage`, `club`, `gel`, `saisie_par`, `vaatt`, `local`, `nuit`, `reappro`, `essence`, `vahdeb`, `vahfin`) VALUES (764, '2015-04-15', 'panoramix', 'F-JUFA', '1.50', '2.25', '0.75', '', 0, 0, 0, '', '', '0.00', '', '', '', 0, '', 0, 0, 0, 'fpeignot', 1, 0, 0, 0, 0, '14.00', '16.00');


#
# TABLE STRUCTURE FOR: tarifs
#

DROP TABLE IF EXISTS tarifs;

CREATE TABLE `tarifs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant',
  `reference` varchar(32) NOT NULL COMMENT 'Référence du produit',
  `date` date DEFAULT NULL COMMENT 'Date d''application',
  `date_fin` date DEFAULT '2099-12-31' COMMENT 'Date de fin',
  `description` varchar(80) DEFAULT NULL COMMENT 'Description',
  `prix` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT 'Prix unitaire',
  `compte` int(11) NOT NULL DEFAULT '0' COMMENT 'Numéro de compte associé',
  `saisie_par` varchar(25) NOT NULL COMMENT 'Opérateur',
  `club` tinyint(1) DEFAULT '0' COMMENT 'Gestion multi-club',
  `nb_tickets` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT 'Quantité de ticket à créditer',
  `type_ticket` int(11) DEFAULT NULL COMMENT 'Type de ticket à créditer',
  `public` tinyint(4) DEFAULT '1' COMMENT 'Permet le filtrage sur l''impression',
  PRIMARY KEY (`id`),
  KEY `compte` (`compte`)
) ENGINE=MyISAM AUTO_INCREMENT=80 DEFAULT CHARSET=utf8;

INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (14, 'Licence vol d\'initiation', '2010-01-01', '2099-12-31', 'Licence vol d\'initiation', '8.50', 12, 'fpeignot', 0, '0.00', NULL, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (10, 'Heure de vol forfait', '2010-01-01', '2099-12-31', 'Prix de l\'heure pour les titulaires du forfait', '10.00', 170, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (9, 'Forfait heures', '2010-01-01', '2099-12-31', 'Forfait heures, il reste 10€/heure à la charge du pilote', '380.00', 170, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (8, 'Remorqué 300m', '2010-01-01', '2099-12-31', 'Remorqué à 300 m', '21.00', 55, 'fpeignot', 0, '0.00', NULL, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (7, 'Remorqué 100m', '2010-01-01', '2099-12-31', 'Remorqué 100m suplémentaires', '3.00', 55, 'fpeignot', 0, '0.00', NULL, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (5, 'Remorqués par 11', '2010-01-01', '2099-12-31', 'Remorqués par 11', '320.00', 55, 'fpeignot', 0, '11.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (21, 'Gratuit', '2010-01-01', '2099-12-31', 'Prestation non facturée (gratuite)', '0.00', 55, 'fpeignot', 0, '0.00', NULL, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (3, 'Cotisation 3/4  +25 ans', '2010-01-01', '2099-12-31', 'Cotisation 3/4 tarif + 25 ans (deuxième membre ou plus d\'une même famille)', '105.00', 21, 'fpeignot', 0, '0.00', NULL, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (2, 'Cotisation - 25 ans', '2010-01-01', '2099-12-31', 'Cotisation plein tarif - 25 ans', '70.00', 21, 'fpeignot', 0, '0.00', NULL, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (1, 'Cotisation + 25 ans', '2010-01-01', '2099-12-31', 'Cotisation plein tarif + 25 ans (payable au compte générale)', '140.00', 21, 'fpeignot', 0, '0.00', NULL, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (15, 'Carnet de vol', '2010-01-01', '2099-12-31', 'Carnet de vol', '15.00', 13, 'fpeignot', 0, '0.00', NULL, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (16, 'Remorqué 500m', '2010-01-01', '2099-12-31', 'Remorqué à l\'unité', '32.00', 55, 'fpeignot', 0, '0.00', NULL, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (17, 'Heure de vol biplace', '2010-01-01', '2099-12-31', 'Heure de vol en TWIN ou Ask21', '27.00', 170, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (18, 'Heure de vol Pégase', '2010-01-01', '2099-12-31', 'Heure de vol Pégase', '27.00', 170, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (19, 'Heure de vol Piwi', '2010-01-01', '2099-12-31', 'Heure de vol Piwi', '18.00', 170, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (23, 'Manuel Vol à Voile', '2010-01-01', '2099-12-31', 'Manuel de l\'éléve pilote', '37.00', 13, 'fpeignot', 0, '0.00', NULL, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (25, 'Treuillé', '2010-01-01', '2099-12-31', 'Lancement au treuil', '7.00', 55, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (26, 'Heure de vol remorqueur', '2010-01-01', '2099-12-31', 'HDV remorqueur convoyage Amiens Albert 2011', '180.00', 168, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (33, 'Licence 2012 +25 RC', '2010-01-01', '2099-12-31', 'Licence 2012 +25 RC', '140.00', 12, 'fpeignot', 0, '0.00', NULL, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (34, 'Licence 2012 -25 RC', '2010-01-01', '2099-12-31', 'Licence 2012 -25 RC', '60.00', 12, 'fpeignot', 0, '0.00', NULL, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (35, 'Licence 2012 associative', '2010-01-01', '2099-12-31', 'Licence 2012 associative', '63.50', 12, 'fpeignot', 0, '0.00', NULL, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (36, 'Licence 2012 16K 12Jours', '2010-01-01', '2099-12-31', 'Licence 2012 16K 12Jours', '79.00', 12, 'fpeignot', 0, '0.00', NULL, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (37, 'Licence 2012 -25 6 Jours', '2010-01-01', '2099-12-31', 'Licence 2012 -25 6 Jours', '32.50', 56, 'fpeignot', 0, '0.00', NULL, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (38, 'Cotisation Journalière Troyes', '2010-01-01', '2099-12-31', 'Cotisation Journalière Troyes 2012 / jour présence', '2.00', 21, 'fpeignot', 0, '0.00', NULL, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (39, 'Cotisation Convoyage Troyes', '2010-01-01', '2099-12-31', 'Participation aux frais de convoyage / jour de vol', '5.00', 21, 'fpeignot', 0, '0.00', NULL, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (40, 'Heure de vol Dynamic', '2010-01-01', '2099-12-31', 'Heure de vol Dynamic', '110.00', 55, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (41, 'Petit déjeuner', '2010-01-01', '2099-12-31', 'Petit déjeuner organisé par le club', '2.00', 107, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (42, 'Déjeuner', '2010-01-01', '2099-12-31', 'Repas de midi organisé par le club', '3.50', 107, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (43, 'Diner', '2010-01-01', '2099-12-31', 'Repas du soir organisé par le club', '4.50', 107, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (44, 'Boisson', '2010-01-01', '2099-12-31', 'Boisson pilote', '1.00', 107, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (45, 'Heure moteur SF28', '2010-01-01', '2099-12-31', 'Prix horaire de l\'heure moteur SF28', '50.00', 169, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (46, 'Treuillé', '2012-01-01', '2099-12-31', 'Lancement au treuil', '8.00', 168, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (47, 'Remorqué 500m -25ans', '2012-12-29', '2099-12-31', 'Remorqué à l\'unité -25 ans', '16.00', 168, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (48, 'Remorqué 300m -25ans', '2012-12-29', '2099-12-31', 'Remorqué à 300 m -25ans', '10.00', 168, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (49, 'Remorqué 500m', '2012-12-29', '2099-12-31', 'Remorqué à l\'unité', '22.00', 168, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (50, 'Remorqué 300m', '2012-12-29', '2099-12-31', 'Remorqué à 300 m', '14.00', 168, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (51, 'Heure de vol Dynamic', '2012-11-05', '2099-12-31', 'Heure de vol Dynamic', '100.00', 169, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (52, 'Remorqué 100m', '2012-12-29', '2099-12-31', 'Remorqué 100m suplémentaires', '2.00', 168, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (56, 'Planche ticket boisson', '0000-00-00', '2099-12-31', 'Planche de 10 tickets boisson', '12.50', 107, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (57, 'Repas cloture', '0000-00-00', '2099-12-31', 'Repas cloture concours', '25.00', 107, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (58, 'Déjeuner', '2013-01-12', '2099-12-31', 'Repas de midi organisé par le club', '7.00', 107, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (59, 'Diner', '2013-01-12', '2099-12-31', 'Repas du soir organisé par le club', '10.00', 107, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (60, 'Remorqué concours', '0000-00-00', '2099-12-31', 'Remorqué pendant le concours régional', '25.00', 168, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (62, 'Inscription concours', '2013-01-19', '2099-12-31', 'Inscription concours régional', '100.00', 21, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (63, 'Forfait 5 vols -25 ans', '2013-01-26', '2099-12-31', 'Forfait initiation 5 vols -25 ans', '320.00', 55, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (64, 'Forfait initiation 5 vols', '2013-01-26', '2099-12-31', 'Forfait initiation 5 vols', '400.00', 55, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (66, 'Licence Assurance', '2013-01-01', '2099-12-31', 'Licence Assurance FFVV (1 unité)', '1.00', 12, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (68, 'Sweat capuche', '2013-05-10', '2099-12-31', 'Sweats capuche poche kangourou bleu marine >>> réf 276.52', '20.00', 16, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (69, 'T-shirt gris clair', '2013-05-10', '2099-12-31', 'T-shirts gris clair', '8.50', 16, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (70, 'T-shirt femme', '2013-05-10', '2099-12-31', 'T-shirts taille femme moyen >>> réf 134.52', '8.50', 16, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (71, 'Polo blanc liseré bleu', '2013-05-10', '2099-12-31', 'Polos blanc avec lisère bleu marine >>> réf 516.11', '20.00', 16, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (72, 'Petit déjeuner', '2013-07-01', '2099-12-31', 'Petit déjeuner organisé par le club', '1.00', 107, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (73, 'Déjeuner', '2013-07-01', '2099-12-31', 'Repas de midi organisé par le club', '4.00', 107, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (74, 'Diner', '2013-07-01', '2099-12-31', 'Repas du soir organisé par le club', '5.00', 107, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (75, 'Déjeuner', '2014-07-01', '2099-12-31', 'Repas de midi organisé par le club', '5.00', 107, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (76, 'Diner', '2014-07-01', '2099-12-31', 'Repas du soir organisé par le club', '6.00', 107, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (77, 'Nuitée Troyes', '2014-07-01', '2099-12-31', '', '3.00', 21, 'fpeignot', 0, '0.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (78, 'Treuillée', '2015-01-01', '2099-12-31', 'Treuillées de Troyes', '8.00', 168, 'testadmin', 0, '8.00', 0, 1);
INSERT INTO tarifs (`id`, `reference`, `date`, `date_fin`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`, `public`) VALUES (79, 'Treuillées par 10', '2015-01-01', '2099-12-31', 'Treuillées de Troyes', '75.00', 168, 'testadmin', 0, '10.00', 1, 1);


#
# TABLE STRUCTURE FOR: achats
#

DROP TABLE IF EXISTS achats;

CREATE TABLE `achats` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant',
  `date` date NOT NULL COMMENT 'Date',
  `produit` varchar(32) NOT NULL COMMENT 'Produit',
  `quantite` decimal(8,2) NOT NULL COMMENT 'Quantité',
  `prix` decimal(8,2) DEFAULT '0.00' COMMENT 'Prix',
  `description` varchar(80) DEFAULT NULL COMMENT 'Description',
  `pilote` varchar(25) NOT NULL COMMENT 'Pilote',
  `facture` int(11) DEFAULT '0' COMMENT 'Numéro de facture',
  `saisie_par` varchar(25) NOT NULL COMMENT 'Opérateur',
  `club` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Gestion multi-club',
  `machine` varchar(10) DEFAULT NULL COMMENT 'Machine pour stats (planeur ou avion)',
  `vol_planeur` int(11) DEFAULT NULL COMMENT 'Vol planeur',
  `vol_avion` int(11) DEFAULT NULL COMMENT 'Vol avion',
  `mvt_pompe` int(11) DEFAULT NULL COMMENT 'Livraison essence',
  `num_cheque` varchar(50) DEFAULT NULL COMMENT 'Numéro de pièce comptable',
  PRIMARY KEY (`id`),
  KEY `pilote` (`pilote`),
  KEY `saisie_par` (`saisie_par`)
) ENGINE=MyISAM AUTO_INCREMENT=7180 DEFAULT CHARSET=utf8 COMMENT='Lignes de factures';

INSERT INTO achats (`id`, `date`, `produit`, `quantite`, `prix`, `description`, `pilote`, `facture`, `saisie_par`, `club`, `machine`, `vol_planeur`, `vol_avion`, `mvt_pompe`, `num_cheque`) VALUES (7169, '2015-04-15', 'Heure de vol Dynamic', '1.50', '100.00', '15/04/2015  0.00-1.50 F-JUFA', 'panoramix', 0, 'fpeignot', 0, 'F-JUFA', NULL, 763, NULL, NULL);
INSERT INTO achats (`id`, `date`, `produit`, `quantite`, `prix`, `description`, `pilote`, `facture`, `saisie_par`, `club`, `machine`, `vol_planeur`, `vol_avion`, `mvt_pompe`, `num_cheque`) VALUES (7170, '2015-04-15', 'Heure de vol Dynamic', '0.75', '100.00', '15/04/2015  1.50-2.25 F-JUFA', 'panoramix', 0, 'fpeignot', 0, 'F-JUFA', NULL, 764, NULL, NULL);
INSERT INTO achats (`id`, `date`, `produit`, `quantite`, `prix`, `description`, `pilote`, `facture`, `saisie_par`, `club`, `machine`, `vol_planeur`, `vol_avion`, `mvt_pompe`, `num_cheque`) VALUES (7171, '2015-04-15', 'Remorqué 500m', '1.00', '22.00', ' à 16h00 ', 'asterix', 0, 'fpeignot', 0, 'F-CJRG', 2878, NULL, NULL, NULL);
INSERT INTO achats (`id`, `date`, `produit`, `quantite`, `prix`, `description`, `pilote`, `facture`, `saisie_par`, `club`, `machine`, `vol_planeur`, `vol_avion`, `mvt_pompe`, `num_cheque`) VALUES (7172, '2015-04-15', 'Heure de vol biplace', '0.50', '27.00', ' à 16h00  sur F-CJRG', 'asterix', 0, 'fpeignot', 0, 'F-CJRG', 2878, NULL, NULL, NULL);
INSERT INTO achats (`id`, `date`, `produit`, `quantite`, `prix`, `description`, `pilote`, `facture`, `saisie_par`, `club`, `machine`, `vol_planeur`, `vol_avion`, `mvt_pompe`, `num_cheque`) VALUES (7175, '2015-04-15', 'Heure de vol biplace', '0.50', '27.00', ' à 16h30  sur F-CJRG', 'obelix', 0, 'testadmin', 0, 'F-CJRG', 2879, NULL, NULL, NULL);
INSERT INTO achats (`id`, `date`, `produit`, `quantite`, `prix`, `description`, `pilote`, `facture`, `saisie_par`, `club`, `machine`, `vol_planeur`, `vol_avion`, `mvt_pompe`, `num_cheque`) VALUES (7176, '2015-04-01', 'Treuillé', '1.00', '8.00', ' à 12h00 ', 'asterix', 0, 'testadmin', 0, 'F-CJRG', 2880, NULL, NULL, NULL);
INSERT INTO achats (`id`, `date`, `produit`, `quantite`, `prix`, `description`, `pilote`, `facture`, `saisie_par`, `club`, `machine`, `vol_planeur`, `vol_avion`, `mvt_pompe`, `num_cheque`) VALUES (7177, '2015-04-01', 'Heure de vol biplace', '1.00', '27.00', ' à 12h00  sur F-CJRG', 'asterix', 0, 'testadmin', 0, 'F-CJRG', 2880, NULL, NULL, NULL);
INSERT INTO achats (`id`, `date`, `produit`, `quantite`, `prix`, `description`, `pilote`, `facture`, `saisie_par`, `club`, `machine`, `vol_planeur`, `vol_avion`, `mvt_pompe`, `num_cheque`) VALUES (7178, '2015-04-02', 'Treuillé', '1.00', '8.00', ' à 12h30  Chef Bonemine', 'abraracourcix', 0, 'testadmin', 0, 'F-CJRG', 2881, NULL, NULL, NULL);
INSERT INTO achats (`id`, `date`, `produit`, `quantite`, `prix`, `description`, `pilote`, `facture`, `saisie_par`, `club`, `machine`, `vol_planeur`, `vol_avion`, `mvt_pompe`, `num_cheque`) VALUES (7179, '2015-04-02', 'Heure de vol biplace', '3.00', '27.00', ' à 12h30  sur F-CJRG Chef Bonemine', 'abraracourcix', 0, 'testadmin', 0, 'F-CJRG', 2881, NULL, NULL, NULL);


#
# TABLE STRUCTURE FOR: pompes
#

DROP TABLE IF EXISTS pompes;

CREATE TABLE `pompes` (
  `pid` int(11) NOT NULL AUTO_INCREMENT,
  `pnum` int(8) NOT NULL DEFAULT '0' COMMENT 'numéro de la pompe',
  `pdatesaisie` date NOT NULL COMMENT 'Date',
  `pdatemvt` date NOT NULL COMMENT 'Date',
  `ppilid` varchar(25) CHARACTER SET utf8 NOT NULL COMMENT 'Pilote',
  `pmacid` varchar(10) CHARACTER SET utf8 NOT NULL COMMENT 'Machine',
  `ptype` varchar(1) CHARACTER SET utf8 NOT NULL COMMENT 'Type d''opération',
  `pqte` decimal(8,2) NOT NULL COMMENT 'quantité en litres',
  `ppu` decimal(8,2) NOT NULL COMMENT 'prix du litre',
  `pprix` decimal(8,2) NOT NULL COMMENT 'Prix total',
  `pdesc` varchar(200) CHARACTER SET utf8 DEFAULT NULL COMMENT 'commentaires',
  `psaisipar` varchar(25) CHARACTER SET utf8 NOT NULL COMMENT 'Nom de l''opérateur',
  PRIMARY KEY (`pid`)
) ENGINE=MyISAM AUTO_INCREMENT=223 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

#
# TABLE STRUCTURE FOR: user_temp
#

DROP TABLE IF EXISTS user_temp;

CREATE TABLE `user_temp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_bin NOT NULL,
  `password` varchar(34) COLLATE utf8_bin NOT NULL,
  `email` varchar(100) COLLATE utf8_bin NOT NULL,
  `activation_key` varchar(50) COLLATE utf8_bin NOT NULL,
  `last_ip` varchar(40) COLLATE utf8_bin NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO user_temp (`id`, `username`, `password`, `email`, `activation_key`, `last_ip`, `created`) VALUES (1, 'titi', '$1$dt..i13.$16/ETIsyKHAOLc6EDtpF01', 'titi@free.fr', '14361794dc031558dad2f93fcca03868', '127.0.0.1', '2011-03-31 21:08:03');
INSERT INTO user_temp (`id`, `username`, `password`, `email`, `activation_key`, `last_ip`, `created`) VALUES (2, 'titi', '$1$Td2.gm2.$skrfvgKcgXHzg.BT6Cwe.0', 'titi@free.fr', 'f4c7f6a170a4ec634d5855df408200b7', '127.0.0.1', '2011-03-31 21:14:33');


#
# TABLE STRUCTURE FOR: user_profile
#

DROP TABLE IF EXISTS user_profile;

CREATE TABLE `user_profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `country` varchar(20) COLLATE utf8_bin DEFAULT NULL,
  `website` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=120 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (1, 1, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (2, 3, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (3, 4, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (4, 5, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (5, 6, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (6, 4, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (7, 5, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (8, 6, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (9, 7, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (10, 8, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (11, 9, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (12, 10, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (13, 11, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (14, 12, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (15, 13, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (16, 14, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (17, 15, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (18, 16, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (19, 17, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (20, 18, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (21, 19, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (22, 20, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (23, 21, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (24, 22, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (25, 23, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (26, 24, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (27, 25, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (28, 26, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (29, 27, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (30, 28, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (31, 29, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (32, 30, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (33, 31, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (34, 32, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (35, 33, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (36, 34, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (37, 35, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (38, 36, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (39, 37, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (40, 38, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (41, 39, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (42, 40, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (43, 41, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (44, 42, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (45, 43, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (46, 44, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (47, 45, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (48, 46, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (49, 47, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (50, 48, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (51, 49, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (52, 50, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (53, 51, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (54, 52, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (55, 53, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (56, 54, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (57, 55, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (58, 56, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (59, 57, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (60, 58, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (61, 59, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (62, 60, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (63, 61, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (64, 62, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (65, 63, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (66, 64, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (67, 65, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (68, 66, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (69, 67, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (70, 68, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (71, 69, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (72, 70, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (73, 71, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (74, 72, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (75, 73, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (76, 74, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (77, 75, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (78, 76, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (79, 77, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (80, 78, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (81, 79, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (82, 80, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (83, 81, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (84, 82, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (85, 83, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (86, 84, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (87, 85, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (88, 86, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (89, 87, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (90, 88, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (91, 89, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (92, 90, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (93, 91, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (94, 92, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (95, 93, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (96, 94, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (97, 95, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (98, 96, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (99, 97, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (100, 98, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (101, 99, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (102, 100, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (103, 101, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (104, 102, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (105, 103, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (106, 104, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (107, 105, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (108, 106, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (109, 107, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (110, 108, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (111, 109, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (112, 110, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (113, 111, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (114, 112, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (115, 113, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (116, 114, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (117, 115, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (118, 116, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (119, 117, NULL, NULL);


#
# TABLE STRUCTURE FOR: user_autologin
#

DROP TABLE IF EXISTS user_autologin;

CREATE TABLE `user_autologin` (
  `key_id` char(32) COLLATE utf8_bin NOT NULL,
  `user_id` mediumint(8) NOT NULL DEFAULT '0',
  `user_agent` varchar(150) COLLATE utf8_bin NOT NULL,
  `last_ip` varchar(40) COLLATE utf8_bin NOT NULL,
  `last_login` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key_id`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

#
# TABLE STRUCTURE FOR: users
#

DROP TABLE IF EXISTS users;

CREATE TABLE `users` (
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
) ENGINE=MyISAM AUTO_INCREMENT=118 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO users (`id`, `role_id`, `username`, `password`, `email`, `banned`, `ban_reason`, `newpass`, `newpass_key`, `newpass_time`, `last_ip`, `last_login`, `created`, `modified`) VALUES (115, 2, 'testadmin', '$1$.H2.tL5.$lpzdKO8TU5XY09.OGpman1', 'frederic.peignot@free.fr', 0, NULL, NULL, NULL, NULL, '127.0.0.1', '2016-04-21 19:08:58', '2015-04-15 10:22:03', '2016-04-21 19:08:58');
INSERT INTO users (`id`, `role_id`, `username`, `password`, `email`, `banned`, `ban_reason`, `newpass`, `newpass_key`, `newpass_time`, `last_ip`, `last_login`, `created`, `modified`) VALUES (111, 1, 'asterix', '$1$dKinKVb2$Fdlcq7mgJY8CHGokrEBrj1', 'asterix@free.fr', 0, NULL, NULL, NULL, NULL, '46.218.181.98', '2015-04-15 13:09:26', '2015-04-15 09:34:29', '2015-05-14 10:57:07');
INSERT INTO users (`id`, `role_id`, `username`, `password`, `email`, `banned`, `ban_reason`, `newpass`, `newpass_key`, `newpass_time`, `last_ip`, `last_login`, `created`, `modified`) VALUES (112, 8, 'obelix', '$1$Pt2lOAhd$Yx0.oXflxv9t.Gct3BW08/', 'obelix@free.fr', 0, NULL, NULL, NULL, NULL, '127.0.0.1', '0000-00-00 00:00:00', '2015-04-15 09:36:00', '2015-05-14 10:57:17');
INSERT INTO users (`id`, `role_id`, `username`, `password`, `email`, `banned`, `ban_reason`, `newpass`, `newpass_key`, `newpass_time`, `last_ip`, `last_login`, `created`, `modified`) VALUES (113, 1, 'abraracourcix', '$1$oEP4ZdE0$pAFZMoEtzfncKjaZEaC9N/', 'abraracourcix@hotmail.fr', 0, NULL, NULL, NULL, NULL, '127.0.0.1', '0000-00-00 00:00:00', '2015-04-15 09:38:31', '2015-05-14 10:58:23');
INSERT INTO users (`id`, `role_id`, `username`, `password`, `email`, `banned`, `ban_reason`, `newpass`, `newpass_key`, `newpass_time`, `last_ip`, `last_login`, `created`, `modified`) VALUES (114, 9, 'panoramix', '$1$E8b7T7N8$GvEK7U44q03gAy.iPYw6Q0', 'pano@ffvv.org', 0, NULL, NULL, NULL, NULL, '127.0.0.1', '0000-00-00 00:00:00', '2015-04-15 09:40:01', '2015-05-14 10:57:30');
INSERT INTO users (`id`, `role_id`, `username`, `password`, `email`, `banned`, `ban_reason`, `newpass`, `newpass_key`, `newpass_time`, `last_ip`, `last_login`, `created`, `modified`) VALUES (116, 3, 'bonemine', '$1$GOQ70VMf$Uxxgo4UXqa3/XQzmRXlFr/', 'bonemine@levillage.fr', 0, NULL, NULL, NULL, NULL, '127.0.0.1', '0000-00-00 00:00:00', '2015-05-09 19:27:43', '2015-05-14 10:57:44');
INSERT INTO users (`id`, `role_id`, `username`, `password`, `email`, `banned`, `ban_reason`, `newpass`, `newpass_key`, `newpass_time`, `last_ip`, `last_login`, `created`, `modified`) VALUES (117, 7, 'goudurix', '$1$AbcYgnx2$McFaoV0VoS3Fa9zO2KdKY1', 'goudurix@hotmail.fr', 0, NULL, NULL, NULL, NULL, '127.0.0.1', '0000-00-00 00:00:00', '2015-05-09 19:30:10', '2015-05-14 10:58:13');


#
# TABLE STRUCTURE FOR: permissions
#

DROP TABLE IF EXISTS permissions;

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `data` text COLLATE utf8_bin,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO permissions (`id`, `role_id`, `data`) VALUES (1, 1, 'a:1:{s:3:\"uri\";a:26:{i:0;s:8:\"/membre/\";i:1;s:14:\"/planeur/page/\";i:2;s:12:\"/avion/page/\";i:3;s:12:\"/vols_avion/\";i:4;s:14:\"/vols_planeur/\";i:5;s:19:\"/rapports/licences/\";i:6;s:19:\"/factures/en_cours/\";i:7;s:15:\"/factures/page/\";i:8;s:15:\"/factures/view/\";i:9;s:21:\"/factures/ma_facture/\";i:10;s:19:\"/compta/mon_compte/\";i:11;s:23:\"/compta/journal_compte/\";i:12;s:25:\"/compta/filterValidation/\";i:13;s:12:\"/compta/pdf/\";i:14;s:15:\"/compta/export/\";i:15;s:17:\"/compta/new_year/\";i:16;s:18:\"/comptes/new_year/\";i:17;s:17:\"/achats/new_year/\";i:18;s:14:\"/tickets/page/\";i:19;s:13:\"/event/stats/\";i:20;s:12:\"/event/page/\";i:21;s:17:\"/event/formation/\";i:22;s:11:\"/event/fai/\";i:23;s:11:\"/presences/\";i:24;s:10:\"/licences/\";i:25;s:9:\"/welcome/\";}}');
INSERT INTO permissions (`id`, `role_id`, `data`) VALUES (2, 7, 'a:1:{s:3:\"uri\";a:3:{i:0;s:12:\"/vols_avion/\";i:1;s:14:\"/vols_planeur/\";i:2;s:0:\"\";}}');
INSERT INTO permissions (`id`, `role_id`, `data`) VALUES (3, 9, 'a:1:{s:3:\"uri\";a:8:{i:0;s:10:\"/factures/\";i:1;s:8:\"/compta/\";i:2;s:9:\"/comptes/\";i:3;s:10:\"/remorque/\";i:4;s:16:\"/plan_comptable/\";i:5;s:11:\"/categorie/\";i:6;s:8:\"/tarifs/\";i:7;s:0:\"\";}}');
INSERT INTO permissions (`id`, `role_id`, `data`) VALUES (4, 8, 'a:1:{s:3:\"uri\";a:20:{i:0;s:8:\"/membre/\";i:1;s:9:\"/planeur/\";i:2;s:7:\"/avion/\";i:3;s:12:\"/vols_avion/\";i:4;s:14:\"/vols_planeur/\";i:5;s:10:\"/factures/\";i:6;s:8:\"/compta/\";i:7;s:8:\"/compta/\";i:8;s:8:\"/compta/\";i:9;s:9:\"/comptes/\";i:10;s:9:\"/tickets/\";i:11;s:7:\"/event/\";i:12;s:10:\"/rapports/\";i:13;s:10:\"/licences/\";i:14;s:8:\"/achats/\";i:15;s:10:\"/terrains/\";i:16;s:7:\"/admin/\";i:17;s:9:\"/reports/\";i:18;s:7:\"/mails/\";i:19;s:12:\"/historique/\";}}');
INSERT INTO permissions (`id`, `role_id`, `data`) VALUES (5, 3, 'a:1:{s:3:\"uri\";a:2:{i:0;s:23:\"/compta/journal_compte/\";i:1;s:13:\"/compta/view/\";}}');
INSERT INTO permissions (`id`, `role_id`, `data`) VALUES (6, 2, 'a:1:{s:3:\"uri\";a:32:{i:0;s:8:\"/membre/\";i:1;s:9:\"/planeur/\";i:2;s:7:\"/avion/\";i:3;s:17:\"/vols_avion/page/\";i:4;s:29:\"/vols_avion/filterValidation/\";i:5;s:16:\"/vols_avion/pdf/\";i:6;s:23:\"/vols_avion/statistics/\";i:7;s:21:\"/vols_avion/new_year/\";i:8;s:19:\"/vols_planeur/page/\";i:9;s:24:\"/vols_planeur/statistic/\";i:10;s:31:\"/vols_planeur/filterValidation/\";i:11;s:18:\"/vols_planeur/pdf/\";i:12;s:24:\"/vols_planeur/pdf_month/\";i:13;s:26:\"/vols_planeur/pdf_machine/\";i:14;s:25:\"/vols_planeur/export_per/\";i:15;s:21:\"/vols_planeur/export/\";i:16;s:23:\"/vols_planeur/new_year/\";i:17;s:19:\"/factures/en_cours/\";i:18;s:15:\"/factures/page/\";i:19;s:15:\"/factures/view/\";i:20;s:21:\"/factures/ma_facture/\";i:21;s:19:\"/compta/mon_compte/\";i:22;s:23:\"/compta/journal_compte/\";i:23;s:25:\"/compta/filterValidation/\";i:24;s:12:\"/compta/pdf/\";i:25;s:17:\"/compta/new_year/\";i:26;s:18:\"/comptes/new_year/\";i:27;s:14:\"/tickets/page/\";i:28;s:13:\"/event/stats/\";i:29;s:12:\"/event/page/\";i:30;s:17:\"/event/formation/\";i:31;s:11:\"/event/fai/\";}}');


#
# TABLE STRUCTURE FOR: roles
#

DROP TABLE IF EXISTS roles;

CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(30) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO roles (`id`, `parent_id`, `name`) VALUES (9, 3, 'tresorier');
INSERT INTO roles (`id`, `parent_id`, `name`) VALUES (8, 7, 'ca');
INSERT INTO roles (`id`, `parent_id`, `name`) VALUES (7, 1, 'planchiste');
INSERT INTO roles (`id`, `parent_id`, `name`) VALUES (2, 9, 'admin');
INSERT INTO roles (`id`, `parent_id`, `name`) VALUES (1, 0, 'membre');
INSERT INTO roles (`id`, `parent_id`, `name`) VALUES (3, 8, 'bureau');


#
# TABLE STRUCTURE FOR: login_attempts
#

DROP TABLE IF EXISTS login_attempts;

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(40) COLLATE utf8_bin NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=525 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (62, '84.101.169.70', '2011-09-26 09:46:55');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (15, '92.90.19.20', '2011-05-07 12:43:57');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (31, '86.197.64.56', '2011-07-02 09:25:47');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (33, '83.114.114.190', '2011-07-05 08:51:17');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (46, '84.132.82.87', '2011-07-25 23:23:14');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (47, '77.195.167.94', '2011-07-26 18:28:24');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (48, '83.192.57.246', '2011-07-29 22:21:09');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (53, '78.250.254.80', '2011-08-27 08:10:05');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (55, '83.204.7.78', '2011-09-03 15:03:43');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (65, '90.22.141.223', '2011-10-06 13:27:12');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (69, '90.34.2.195', '2011-10-21 23:19:45');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (70, '88.166.92.99', '2011-10-23 08:46:47');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (71, '83.192.171.230', '2011-10-30 19:13:34');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (73, '90.18.132.163', '2011-11-18 09:09:19');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (75, '90.7.89.139', '2011-12-01 15:14:42');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (76, '86.70.144.13', '2011-12-03 18:14:21');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (77, '83.192.45.217', '2011-12-03 20:07:14');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (78, '193.253.141.65', '2011-12-03 23:02:38');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (80, '92.157.190.174', '2011-12-04 15:28:07');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (83, '83.192.82.79', '2011-12-22 11:25:06');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (84, '85.168.60.150', '2011-12-22 21:57:10');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (99, '83.198.33.144', '2012-02-20 19:07:35');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (97, '83.192.75.15', '2012-01-31 18:48:37');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (103, '83.192.32.180', '2012-03-21 16:46:37');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (108, '86.70.144.191', '2012-04-10 14:43:06');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (106, '90.22.20.19', '2012-04-02 20:21:56');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (107, '85.169.35.69', '2012-04-07 15:01:26');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (112, '83.204.68.122', '2012-05-26 14:52:00');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (127, '90.22.29.109', '2012-06-28 11:05:09');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (130, '86.198.171.104', '2012-07-01 19:33:28');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (118, '83.198.41.249', '2012-06-11 15:44:00');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (119, '83.192.79.137', '2012-06-15 13:19:02');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (120, '83.192.31.155', '2012-06-16 13:28:32');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (145, '92.142.60.236', '2012-07-28 16:15:52');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (148, '90.22.46.138', '2012-08-03 11:09:49');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (156, '92.155.250.32', '2012-09-11 19:50:40');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (152, '79.88.103.236', '2012-08-16 17:04:29');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (154, '90.7.68.219', '2012-08-24 21:06:02');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (163, '90.47.54.233', '2012-09-20 11:20:43');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (179, '93.21.188.95', '2012-11-04 18:41:35');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (178, '93.21.188.95', '2012-11-04 18:41:22');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (180, '93.21.188.95', '2012-11-04 18:41:55');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (186, '77.195.167.254', '2012-11-19 09:33:51');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (187, '77.195.167.254', '2012-11-19 09:34:06');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (188, '77.195.167.254', '2012-11-19 09:34:16');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (218, '78.113.175.239', '2013-02-10 19:27:02');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (221, '78.250.117.108', '2013-03-13 21:42:07');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (232, '82.250.68.192', '2013-05-11 09:56:56');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (271, '78.247.249.92', '2013-06-27 19:42:38');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (272, '78.208.224.48', '2013-06-29 10:48:01');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (273, '78.208.224.48', '2013-06-29 10:48:18');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (274, '78.208.224.48', '2013-06-29 10:48:29');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (287, '78.114.231.151', '2013-07-31 22:08:44');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (292, '77.195.167.201', '2013-08-14 09:04:23');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (293, '77.195.167.201', '2013-08-14 09:09:56');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (294, '77.195.167.201', '2013-08-14 09:10:09');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (297, '92.90.20.134', '2013-08-26 18:34:31');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (298, '92.90.20.134', '2013-08-26 18:35:03');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (301, '2a01:e35:8a65:d3d0:ec18:24d6:c60f:8c2e', '2013-08-28 12:26:15');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (302, '2a01:e35:8a65:d3d0:ec18:24d6:c60f:8c2e', '2013-08-28 12:27:41');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (315, '92.155.111.184', '2013-10-11 11:07:53');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (345, '90.23.115.74', '2014-01-07 21:39:19');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (359, '2a01:e35:8a65:c690:6145:73e5:d3e8:b6c0', '2014-02-15 15:45:37');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (360, '2a01:e35:8a65:c690:6145:73e5:d3e8:b6c0', '2014-02-15 15:45:56');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (393, '86.70.144.165', '2014-05-23 16:31:41');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (394, '86.70.144.165', '2014-05-23 16:31:48');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (395, '86.70.144.165', '2014-05-23 16:31:54');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (398, '88.166.92.105', '2014-06-20 11:41:59');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (399, '88.166.92.105', '2014-06-20 11:42:16');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (400, '88.166.92.105', '2014-06-20 11:42:21');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (415, '90.1.40.152', '2014-07-08 13:59:58');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (425, '80.169.246.178', '2014-08-04 22:47:48');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (472, '87.89.40.40', '2014-10-29 21:23:46');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (473, '77.192.50.131', '2014-10-30 21:55:16');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (474, '77.192.50.131', '2014-10-30 21:55:25');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (475, '77.192.50.131', '2014-10-30 21:55:31');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (478, '92.155.81.186', '2014-11-12 18:36:45');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (479, '92.155.81.186', '2014-11-12 18:37:27');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (480, '92.155.81.186', '2014-11-12 18:37:42');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (484, '88.137.127.218', '2014-11-17 16:52:57');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (485, '88.137.127.218', '2014-11-17 16:53:07');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (486, '88.137.127.218', '2014-11-17 16:53:12');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (490, '92.90.16.176', '2014-11-30 14:34:03');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (491, '92.90.16.176', '2014-11-30 14:34:32');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (492, '92.90.16.176', '2014-11-30 14:34:52');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (515, '78.122.179.214', '2015-01-28 15:43:52');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (516, '78.122.179.214', '2015-01-28 15:44:07');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (517, '78.122.179.214', '2015-01-28 15:44:18');
INSERT INTO login_attempts (`id`, `ip_address`, `time`) VALUES (523, '41.226.133.242', '2015-04-28 17:38:39');


#
# TABLE STRUCTURE FOR: ci_sessions
#

DROP TABLE IF EXISTS ci_sessions;

CREATE TABLE `ci_sessions` (
  `session_id` varchar(40) COLLATE utf8_bin NOT NULL DEFAULT '0',
  `ip_address` varchar(16) COLLATE utf8_bin NOT NULL DEFAULT '0',
  `user_agent` varchar(150) COLLATE utf8_bin NOT NULL,
  `last_activity` int(10) unsigned NOT NULL DEFAULT '0',
  `user_data` text COLLATE utf8_bin,
  PRIMARY KEY (`session_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO ci_sessions (`session_id`, `ip_address`, `user_agent`, `last_activity`, `user_data`) VALUES ('647d6d989d6dbf2498f65ca2f3f21adc', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:37.0) Gecko/20100101 Firefox/37.0', 1461258517, 'a:1:{s:9:\"user_data\";s:0:\"\";}');
INSERT INTO ci_sessions (`session_id`, `ip_address`, `user_agent`, `last_activity`, `user_data`) VALUES ('6d98c083bc63950bc019d367cd85ae63', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:37.0) Gecko/20100101 Firefox/37.0', 1461258530, 'a:19:{s:9:\"user_data\";s:0:\"\";s:10:\"DX_user_id\";s:3:\"115\";s:11:\"DX_username\";s:9:\"testadmin\";s:10:\"DX_role_id\";s:1:\"2\";s:12:\"DX_role_name\";s:5:\"admin\";s:18:\"DX_parent_roles_id\";a:5:{i:0;s:1:\"9\";i:1;s:1:\"3\";i:2;s:1:\"8\";i:3;s:1:\"7\";i:4;s:1:\"1\";}s:20:\"DX_parent_roles_name\";a:5:{i:0;s:9:\"tresorier\";i:1;s:6:\"bureau\";i:2;s:2:\"ca\";i:3;s:10:\"planchiste\";i:4;s:6:\"membre\";}s:13:\"DX_permission\";a:1:{s:3:\"uri\";a:32:{i:0;s:8:\"/membre/\";i:1;s:9:\"/planeur/\";i:2;s:7:\"/avion/\";i:3;s:17:\"/vols_avion/page/\";i:4;s:29:\"/vols_avion/filterValidation/\";i:5;s:16:\"/vols_avion/pdf/\";i:6;s:23:\"/vols_avion/statistics/\";i:7;s:21:\"/vols_avion/new_year/\";i:8;s:19:\"/vols_planeur/page/\";i:9;s:24:\"/vols_planeur/statistic/\";i:10;s:31:\"/vols_planeur/filterValidation/\";i:11;s:18:\"/vols_planeur/pdf/\";i:12;s:24:\"/vols_planeur/pdf_month/\";i:13;s:26:\"/vols_planeur/pdf_machine/\";i:14;s:25:\"/vols_planeur/export_per/\";i:15;s:21:\"/vols_planeur/export/\";i:16;s:23:\"/vols_planeur/new_year/\";i:17;s:19:\"/factures/en_cours/\";i:18;s:15:\"/factures/page/\";i:19;s:15:\"/factures/view/\";i:20;s:21:\"/factures/ma_facture/\";i:21;s:19:\"/compta/mon_compte/\";i:22;s:23:\"/compta/journal_compte/\";i:23;s:25:\"/compta/filterValidation/\";i:24;s:12:\"/compta/pdf/\";i:25;s:17:\"/compta/new_year/\";i:26;s:18:\"/comptes/new_year/\";i:27;s:14:\"/tickets/page/\";i:28;s:13:\"/event/stats/\";i:29;s:12:\"/event/page/\";i:30;s:17:\"/event/formation/\";i:31;s:11:\"/event/fai/\";}}s:21:\"DX_parent_permissions\";a:5:{i:1;a:1:{s:3:\"uri\";a:26:{i:0;s:8:\"/membre/\";i:1;s:14:\"/planeur/page/\";i:2;s:12:\"/avion/page/\";i:3;s:12:\"/vols_avion/\";i:4;s:14:\"/vols_planeur/\";i:5;s:19:\"/rapports/licences/\";i:6;s:19:\"/factures/en_cours/\";i:7;s:15:\"/factures/page/\";i:8;s:15:\"/factures/view/\";i:9;s:21:\"/factures/ma_facture/\";i:10;s:19:\"/compta/mon_compte/\";i:11;s:23:\"/compta/journal_compte/\";i:12;s:25:\"/compta/filterValidation/\";i:13;s:12:\"/compta/pdf/\";i:14;s:15:\"/compta/export/\";i:15;s:17:\"/compta/new_year/\";i:16;s:18:\"/comptes/new_year/\";i:17;s:17:\"/achats/new_year/\";i:18;s:14:\"/tickets/page/\";i:19;s:13:\"/event/stats/\";i:20;s:12:\"/event/page/\";i:21;s:17:\"/event/formation/\";i:22;s:11:\"/event/fai/\";i:23;s:11:\"/presences/\";i:24;s:10:\"/licences/\";i:25;s:9:\"/welcome/\";}}i:2;a:1:{s:3:\"uri\";a:3:{i:0;s:12:\"/vols_avion/\";i:1;s:14:\"/vols_planeur/\";i:2;s:0:\"\";}}i:3;a:1:{s:3:\"uri\";a:8:{i:0;s:10:\"/factures/\";i:1;s:8:\"/compta/\";i:2;s:9:\"/comptes/\";i:3;s:10:\"/remorque/\";i:4;s:16:\"/plan_comptable/\";i:5;s:11:\"/categorie/\";i:6;s:8:\"/tarifs/\";i:7;s:0:\"\";}}i:4;a:1:{s:3:\"uri\";a:20:{i:0;s:8:\"/membre/\";i:1;s:9:\"/planeur/\";i:2;s:7:\"/avion/\";i:3;s:12:\"/vols_avion/\";i:4;s:14:\"/vols_planeur/\";i:5;s:10:\"/factures/\";i:6;s:8:\"/compta/\";i:7;s:8:\"/compta/\";i:8;s:8:\"/compta/\";i:9;s:9:\"/comptes/\";i:10;s:9:\"/tickets/\";i:11;s:7:\"/event/\";i:12;s:10:\"/rapports/\";i:13;s:10:\"/licences/\";i:14;s:8:\"/achats/\";i:15;s:10:\"/terrains/\";i:16;s:7:\"/admin/\";i:17;s:9:\"/reports/\";i:18;s:7:\"/mails/\";i:19;s:12:\"/historique/\";}}i:5;a:1:{s:3:\"uri\";a:2:{i:0;s:23:\"/compta/journal_compte/\";i:1;s:13:\"/compta/view/\";}}}s:12:\"DX_logged_in\";b:1;s:13:\"filter_active\";i:1;s:9:\"filter_25\";i:0;s:19:\"filter_membre_actif\";i:2;s:20:\"filter_machine_actif\";i:2;s:4:\"year\";s:4:\"2016\";s:8:\"per_page\";i:50;s:12:\"licence_type\";i:0;s:8:\"back_url\";s:50:\"http://localhost/gvv_dev/index.php/comptes/general\";s:7:\"general\";b:1;}');


