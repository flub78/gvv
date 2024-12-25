#
# TABLE STRUCTURE FOR: migrations
#

DROP TABLE IF EXISTS migrations;

CREATE TABLE `migrations` (
  `version` int(3) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO migrations (`version`) VALUES (15);


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
# TABLE STRUCTURE FOR: permissions
#

DROP TABLE IF EXISTS permissions;

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `data` text COLLATE utf8_bin,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO permissions (`id`, `role_id`, `data`) VALUES (1, 1, 'a:1:{s:3:\"uri\";a:22:{i:0;s:8:\"/membre/\";i:1;s:14:\"/planeur/page/\";i:2;s:12:\"/avion/page/\";i:3;s:12:\"/vols_avion/\";i:4;s:14:\"/vols_planeur/\";i:5;s:19:\"/rapports/licences/\";i:6;s:19:\"/compta/mon_compte/\";i:7;s:23:\"/compta/journal_compte/\";i:8;s:25:\"/compta/filterValidation/\";i:9;s:12:\"/compta/pdf/\";i:10;s:15:\"/compta/export/\";i:11;s:17:\"/compta/new_year/\";i:12;s:18:\"/comptes/new_year/\";i:13;s:17:\"/achats/new_year/\";i:14;s:14:\"/tickets/page/\";i:15;s:13:\"/event/stats/\";i:16;s:12:\"/event/page/\";i:17;s:17:\"/event/formation/\";i:18;s:11:\"/event/fai/\";i:19;s:11:\"/presences/\";i:20;s:10:\"/licences/\";i:21;s:9:\"/welcome/\";}}');
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


