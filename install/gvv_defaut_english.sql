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

INSERT INTO reports (`nom`, `titre`, `fields_list`, `align`, `width`, `landscape`, `sql`) VALUES ('utilisateurs', 'Liste des utilisateurs', 'Nom,Prénom,+/-25,Naissance', 'left,left,right,right', '', 0, 'select mnom, mprenom, m25ans, date_format(mdaten, \"%d/%m/%Y\") from membres;');
INSERT INTO reports (`nom`, `titre`, `fields_list`, `align`, `width`, `landscape`, `sql`) VALUES ('tarifs', 'Liste des tarifs', 'Référence,Description,Date,Prix,Compte', 'left,left,right,right,left', '32,32,32,32,32', 0, 'select reference, description, date_format(date, \"%d/%m/%Y\"), prix, comptes.nom  from tarifs, comptes where tarifs.compte=comptes.id order by reference,date;');
INSERT INTO reports (`nom`, `titre`, `fields_list`, `align`, `width`, `landscape`, `sql`) VALUES ('instructeurs_double', 'Vols des instructeurs en DC 2013', 'Id,Prénom,Nom,Heures,Minutes,Vols', 'right,left,left,right,right,right', '16,40,40,16,16,16', 0, 'select mlogin, mprenom, mnom,  truncate(sum(vpduree) / 60, 0) as heures, truncate(sum(vpduree), 0) % 60 as minutes, count(vpduree) as vols  from membres, volsp where ((mniveaux & 65536 + 32768) <> 0) and membres.mlogin = volsp.vpinst and vpdate >= \'2013-01-01\' and vpdate <= \'2013-12-31\' group by mlogin');
INSERT INTO reports (`nom`, `titre`, `fields_list`, `align`, `width`, `landscape`, `sql`) VALUES ('instructeurs_solo', 'Vols des instructeurs en solo 2013', 'Id,Prénom,Nom,Heures,Minutes,Vols', 'right,left,left,right,right,right', '16,40,40,16,16,16', 0, 'select mlogin, mprenom, mnom,  truncate(sum(vpduree) / 60, 0) as heures, truncate(sum(vpduree), 0) % 60 as minutes, count(vpduree) as vols  from membres, volsp where ((mniveaux & 65536 + 32768) <> 0) and vpdc = 0 and membres.mlogin = volsp.vppilid and vpdate >= \'2013-01-01\' and vpdate <= \'2013-12-31\' group by mlogin');
INSERT INTO reports (`nom`, `titre`, `fields_list`, `align`, `width`, `landscape`, `sql`) VALUES ('tarifs_inutiles', 'Liste des tarifs inutiles', 'Référence,Description,Date,Prix,Compte', 'left,left,right,right,left', '32,32,32,32,32', 0, 'select reference, description, date_format(date, \"%d/%m/%Y\"), prix, comptes.nom  from tarifs, comptes \nwhere tarifs.compte=comptes.id \norder by reference,date;');


#
# TABLE STRUCTURE FOR: type_ticket
#

DROP TABLE IF EXISTS type_ticket;

CREATE TABLE `type_ticket` (
  `id` int(11) NOT NULL COMMENT 'Identifiant',
  `nom` varchar(64) NOT NULL COMMENT 'Nom',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Type de tickets';

INSERT INTO type_ticket (`id`, `nom`) VALUES (0, 'Towing');
INSERT INTO type_ticket (`id`, `nom`) VALUES (1, 'Winch');


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


#
# TABLE STRUCTURE FOR: events_types
#

DROP TABLE IF EXISTS events_types;

CREATE TABLE `events_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Numéro',
  `name` varchar(32) COLLATE utf8_bin NOT NULL COMMENT 'Nom de la date ou du certificat',
  `activite` tinyint(2) NOT NULL DEFAULT '0' COMMENT 'Activité associée',
  `en_vol` tinyint(4) NOT NULL COMMENT 'Associé à un vol',
  `multiple` tinyint(1) DEFAULT NULL COMMENT 'Multiple',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=45 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (14, 'Beginning of training', 1, 0, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (15, 'Glider first solo', 1, 1, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (16, '1h flight', 1, 1, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (17, '5h flight', 4, 1, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (18, 'Gain of 1000m', 4, 1, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (19, 'Gain of 3000m', 4, 1, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (20, 'Gain of 5000m', 4, 1, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (21, '50km distance', 4, 1, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (22, '300km distance', 4, 1, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (23, '500km distance', 4, 1, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (24, '750km distance', 4, 1, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (25, '1000km distance', 4, 1, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (26, 'Medical', 0, 0, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (27, 'Glider license', 1, 1, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (0, 'Ground training certificate', 0, 0, 0);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (29, 'Cross country', 1, 0, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (30, 'Check flight', 1, 1, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (31, 'Predefined goal 300km FAI', 4, 1, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (33, 'Glider writen exam', 1, 0, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (34, 'Passanger authorization', 1, 1, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (35, 'Airplane first solo', 2, 1, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (36, 'Airplane basic license', 2, 0, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (37, 'PPL', 2, 1, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (38, 'PPL validity', 2, 0, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (39, 'Flight instructor', 2, 0, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (40, 'Flight examinator', 2, 0, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (41, 'Towing autorization', 2, 0, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (42, 'First training flight', 2, 1, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (43, 'Glider instructor', 1, 0, NULL);
INSERT INTO events_types (`id`, `name`, `activite`, `en_vol`, `multiple`) VALUES (44, 'Glider examinator', 1, 0, NULL);


#
# TABLE STRUCTURE FOR: planc
#

DROP TABLE IF EXISTS planc;

CREATE TABLE `planc` (
  `pcode` varchar(10) NOT NULL,
  `pdesc` varchar(50) NOT NULL,
  UNIQUE KEY `pcode` (`pcode`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO planc (`pcode`, `pdesc`) VALUES ('706', 'Professional services');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('215', 'Hardware');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('218', 'Furniture');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('371', 'Goods');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('401', 'Suppliers');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('411', 'Customers');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('512', 'Bank');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('531', 'Cash');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('60', 'Purchases');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('601', 'Stocked purchases - raw material');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('602', 'Stocked purchases - others');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('606', 'Non-stocked purchases');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('604', 'Purchase of studies and professional services');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('605', 'Others services');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('61', 'External services');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('611', 'Subcontracting');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('612', 'Redevances de crédit-bail');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('613', 'Renting');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('615', 'Entretien et réparations');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('616', 'Insurances');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('62', 'Autres services extérieurs');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('621', 'Personels extérieur à l\'association');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('622', 'Rémunérations et Honoraires.');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('623', 'Publicité, Publications.');
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


