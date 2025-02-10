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
INSERT INTO type_ticket (`id`, `nom`) VALUES (1, 'Treuillé');
INSERT INTO type_ticket (`id`, `nom`) VALUES (2, 'Repas');


#
# TABLE STRUCTURE FOR: terrains
#

DROP TABLE IF EXISTS terrains;

CREATE TABLE `terrains` (
  `oaci` varchar(10) COLLATE utf8_bin NOT NULL COMMENT 'Code OACI',
  `nom` varchar(64) COLLATE utf8_bin DEFAULT NULL COMMENT 'Nom du terrain',
  `freq1` decimal(6,0) DEFAULT '0.000' COMMENT 'Fréquence principale',
  `freq2` decimal(6,0) DEFAULT '0.000' COMMENT 'Fréquence secondaire',
  `comment` varchar(256) COLLATE utf8_bin DEFAULT NULL COMMENT 'Description',
  PRIMARY KEY (`oaci`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

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
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=113 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

#
# TABLE STRUCTURE FOR: events_types
#

DROP TABLE IF EXISTS events_types;

CREATE TABLE `events_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) COLLATE utf8_bin NOT NULL,
  `activite` tinyint(2) NOT NULL DEFAULT '0' COMMENT '0=Autre,1=planeur,2=avion,3=ULM,4=FAI',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=45 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO events_types (`id`, `name`, `activite`) VALUES (14, 'Premier vol', 1);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (15, 'Laché planeur', 1);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (16, 'Vol 1h', 1);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (17, 'Vol 5h', 4);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (18, 'Gain de 1000m', 4);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (19, 'Gain de 3000m', 4);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (20, 'Gain de 5000m', 4);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (21, 'Distance de 50km', 4);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (22, 'Distance de 300km', 4);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (23, 'Distance de 500km', 4);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (24, 'Distance de 750km', 4);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (25, 'Distance de 1000km', 4);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (26, 'Visite médical', 0);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (27, 'BPP', 1);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (28, 'BIA', 0);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (29, 'Campagne', 1);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (30, 'Contôle de compétence', 1);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (31, 'Circuit de 300km FAI', 4);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (33, 'Théorique BPP', 1);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (34, 'Emport passager', 1);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (35, 'Laché avion', 2);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (36, 'BB', 2);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (37, 'PPL', 2);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (38, 'Validité licence avion', 2);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (39, 'FI Formateur instructeur', 2);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (40, 'FE Formateur examinateur', 2);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (41, 'Autorisation remorquage', 2);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (42, 'Premier vol avion', 1);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (43, 'ITP', 1);
INSERT INTO events_types (`id`, `name`, `activite`) VALUES (44, 'ITV', 1);


#
# TABLE STRUCTURE FOR: tickets
#

DROP TABLE IF EXISTS tickets;

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant',
  `date` date NOT NULL COMMENT 'Date de l''opération',
  `pilote` varchar(25) NOT NULL COMMENT 'Pilote à créditer/débiter',
  `achat` int(11) NOT NULL COMMENT 'Numéro de l''achat',
  `quantite` int(11) NOT NULL DEFAULT '0' COMMENT 'Incrément',
  `description` varchar(120) NOT NULL COMMENT 'Commentaire',
  `saisie_par` varchar(25) NOT NULL COMMENT 'Opérateur',
  `club` tinyint(4) NOT NULL COMMENT 'Gestion multi-club',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT 'Remorqué=0, treuillé=1, repas=2',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=519 DEFAULT CHARSET=utf8 COMMENT='Tickets de remorqué ou treuillé';

INSERT INTO tickets (`id`, `date`, `pilote`, `achat`, `quantite`, `description`, `saisie_par`, `club`, `type`) VALUES (516, '2012-12-23', 'panoramix', 3822, 11, '', 'testadmin', 0, 0);
INSERT INTO tickets (`id`, `date`, `pilote`, `achat`, `quantite`, `description`, `saisie_par`, `club`, `type`) VALUES (517, '2012-12-23', 'panoramix', 3823, -1, ' à 13h00  decompté', 'testadmin', 0, 0);
INSERT INTO tickets (`id`, `date`, `pilote`, `achat`, `quantite`, `description`, `saisie_par`, `club`, `type`) VALUES (518, '2012-12-23', 'panoramix', 3825, -1, ' à 15h00  decompté', 'testadmin', 0, 0);


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
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='Catégorie d''écritures pour comptabilité analytique';

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

INSERT INTO planc (`pcode`, `pdesc`) VALUES ('101', 'Capital');
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
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('623', 'Publicité, Publications.');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('624', 'Transport de bien et transport collectif du person');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('625', 'Déplacement, missions et reception');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('626', 'Frais postaux et télécommunications');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('627', 'Licences, Cotisations Fédérales FFVV.');
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
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('701', 'Vente d\'essence.');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('702', 'Vols d\'initiation planeur');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('703', 'Vols d\'initiation Avion');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('704', 'Recettes autres manifestations');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('705', 'Sponsoring');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('74', 'Subventions d\'exploitation');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('741', 'Etat.');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('742', 'Région.');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('743', 'Département.');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('744', 'communes.');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('745', 'CCSRC (communauté de commune)');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('746', 'Autres subventions.');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('75', 'Autres produits de gestion courante');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('751', 'Cotisations membres');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('752', 'Assurances licences FFA.');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('753', 'Assurances licences FFVV.');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('754', 'Retour des Fédérations (bourses).');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('755', 'tickets de remorquage des planeurs.');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('756', 'Heures planeurs.');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('757', 'Heures avion.');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('758', 'Compensation sur tickets Argenton.');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('76', 'Produits financiers');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('77', 'Produits exceptionnels');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('771', 'Gestion de la plateforme.');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('772', 'ANEPVV des privés.');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('773', 'Argenton (indem. d\'hébergement)');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('774', 'Autres.');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('78', 'Reprise sur amortissements');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('110', 'Report à nouveau (solde créditeur)');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('119', 'Report à nouveau (solde débiteur)');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('441', 'Etat - Subventions');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('778', 'Autres produits exceptionnels');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('678', 'Autres charges exceptionnelles');
INSERT INTO planc (`pcode`, `pdesc`) VALUES ('654', 'Redistribution des Bourses ou Subventions');


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
  `ville` varchar(32) DEFAULT NULL COMMENT 'Ville',
  `mtelf` varchar(14) DEFAULT NULL COMMENT 'Téléphone fixe',
  `mtelm` varchar(14) DEFAULT NULL COMMENT 'Mobile',
  `mdaten` date DEFAULT NULL COMMENT 'Date de naissance',
  `m25ans` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Moins de 25 ans',
  `mlieun` varchar(25) DEFAULT NULL COMMENT 'Lieu de naissance',
  `msexe` char(1) NOT NULL DEFAULT 'M',
  `mniveaux` double NOT NULL COMMENT 'Qualifications du membre',
  `mbranum` varchar(20) DEFAULT NULL COMMENT 'Numéro brevet avion',
  `mbradat` date DEFAULT NULL COMMENT 'Date brevet avion',
  `mbraval` date DEFAULT NULL COMMENT 'Validité brevet avion',
  `mbrpnum` varchar(20) DEFAULT NULL COMMENT 'Numéro brevet planeur',
  `mbrpdat` date DEFAULT NULL COMMENT 'dat ebrevet planeur',
  `mbrpval` date DEFAULT NULL COMMENT 'Validité brevet planeur',
  `numinstavion` varchar(20) DEFAULT NULL COMMENT 'Numéro instructeur avion',
  `dateinstavion` date DEFAULT NULL COMMENT 'Validité instructeur avion',
  `numivv` varchar(20) DEFAULT NULL COMMENT 'Numéro instructeur planeur',
  `dateivv` date DEFAULT NULL COMMENT 'Date instructeur planeur',
  `medical` date DEFAULT NULL COMMENT 'Vavilité visite médicale',
  `numlicencefed` varchar(20) DEFAULT NULL,
  `vallicencefed` date DEFAULT NULL,
  `manneeins` smallint(5) unsigned NOT NULL,
  `manneeffvv` smallint(5) unsigned NOT NULL,
  `manneeffa` smallint(5) unsigned NOT NULL,
  `msolde` decimal(5,2) DEFAULT '0.00' COMMENT 'Soled, deprecated',
  `mforfvv` char(1) DEFAULT NULL COMMENT 'I:illimité T:30h D:10vols 2:2ans',
  `macces` int(11) DEFAULT '0' COMMENT 'Droits d''accés',
  `club` tinyint(1) DEFAULT '0' COMMENT 'Gestion multi-club',
  `ext` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Pilote exterieur',
  `actif` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Pilote actif',
  `username` varchar(25) DEFAULT NULL COMMENT 'Utilisateur autorisé à accéder au compte',
  `photo` varchar(64) DEFAULT NULL COMMENT 'Photo',
  `compte` int(11) DEFAULT NULL COMMENT 'Compte pilote',
  `profil` int(11) DEFAULT NULL COMMENT 'Profil de facturation',
  `comment` varchar(256) DEFAULT NULL COMMENT 'Commentaires',
  `trigramme` varchar(12) DEFAULT NULL COMMENT 'Trigramme',
  `categorie` varchar(12) DEFAULT '' COMMENT 'Cat�gorie du pilote',
  PRIMARY KEY (`mlogin`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO membres (`mlogin`, `mnom`, `mprenom`, `memail`, `memailparent`, `madresse`, `cp`, `ville`, `mtelf`, `mtelm`, `mdaten`, `m25ans`, `mlieun`, `msexe`, `mniveaux`, `mbranum`, `mbradat`, `mbraval`, `mbrpnum`, `mbrpdat`, `mbrpval`, `numinstavion`, `dateinstavion`, `numivv`, `dateivv`, `medical`, `numlicencefed`, `vallicencefed`, `manneeins`, `manneeffvv`, `manneeffa`, `msolde`, `mforfvv`, `macces`, `club`, `ext`, `actif`, `username`, `photo`, `compte`, `profil`, `comment`, `trigramme`, `categorie`) VALUES ('first', 'Pierre', 'Jean', 'jean@free.fr', '', 'Sans domicile fixe', 80000, 'Abbeville', '01 23 45 67 89', '', '1960-01-01', 0, '0', 'M', '302893', '', NULL, NULL, '', '2011-01-01', NULL, '', NULL, '', NULL, NULL, '', NULL, 0, 0, 0, '0.00', '0', 0, 0, 0, 1, '0', '', 0, 0, '', '', '0');
INSERT INTO membres (`mlogin`, `mnom`, `mprenom`, `memail`, `memailparent`, `madresse`, `cp`, `ville`, `mtelf`, `mtelm`, `mdaten`, `m25ans`, `mlieun`, `msexe`, `mniveaux`, `mbranum`, `mbradat`, `mbraval`, `mbrpnum`, `mbrpdat`, `mbrpval`, `numinstavion`, `dateinstavion`, `numivv`, `dateivv`, `medical`, `numlicencefed`, `vallicencefed`, `manneeins`, `manneeffvv`, `manneeffa`, `msolde`, `mforfvv`, `macces`, `club`, `ext`, `actif`, `username`, `photo`, `compte`, `profil`, `comment`, `trigramme`, `categorie`) VALUES ('asterix', 'Legaulois', 'Astérix', 'asterix@free.fr', '', 'hutte d\'astérix', 56340, 'Village Gaulois', '', '', NULL, 0, '0', 'M', '0', '', NULL, NULL, '', NULL, NULL, '', NULL, '', NULL, NULL, '', NULL, 0, 0, 0, '0.00', '0', 0, 0, 0, 1, '0', '', 0, 0, '', '', '0');
INSERT INTO membres (`mlogin`, `mnom`, `mprenom`, `memail`, `memailparent`, `madresse`, `cp`, `ville`, `mtelf`, `mtelm`, `mdaten`, `m25ans`, `mlieun`, `msexe`, `mniveaux`, `mbranum`, `mbradat`, `mbraval`, `mbrpnum`, `mbrpdat`, `mbrpval`, `numinstavion`, `dateinstavion`, `numivv`, `dateivv`, `medical`, `numlicencefed`, `vallicencefed`, `manneeins`, `manneeffvv`, `manneeffa`, `msolde`, `mforfvv`, `macces`, `club`, `ext`, `actif`, `username`, `photo`, `compte`, `profil`, `comment`, `trigramme`, `categorie`) VALUES ('bon emine', 'Abraracourcix', 'Bonemine', 'bonemine@free.fr', '', 'Hutte d\'abraracourcix', 0, 'Village d\'Astérix', '', '', NULL, 0, '0', 'F', '0', '', NULL, NULL, '', NULL, NULL, '', NULL, '', NULL, NULL, '', NULL, 0, 0, 0, '0.00', '0', 0, 0, 0, 1, '0', '', 162, 0, '', '', '0');
INSERT INTO membres (`mlogin`, `mnom`, `mprenom`, `memail`, `memailparent`, `madresse`, `cp`, `ville`, `mtelf`, `mtelm`, `mdaten`, `m25ans`, `mlieun`, `msexe`, `mniveaux`, `mbranum`, `mbradat`, `mbraval`, `mbrpnum`, `mbrpdat`, `mbrpval`, `numinstavion`, `dateinstavion`, `numivv`, `dateivv`, `medical`, `numlicencefed`, `vallicencefed`, `manneeins`, `manneeffvv`, `manneeffa`, `msolde`, `mforfvv`, `macces`, `club`, `ext`, `actif`, `username`, `photo`, `compte`, `profil`, `comment`, `trigramme`, `categorie`) VALUES ('abraracourcix', 'Abraracourcix', 'Abraracourcix', 'abraracourcix@free.fr', '', 'Hutte d\'Abraracourcix', 0, 'Village d\'Astérix', '', '', NULL, 0, '0', 'M', '12288', '', NULL, NULL, '', NULL, NULL, '', NULL, '', NULL, NULL, '', NULL, 0, 0, 0, '0.00', '0', 0, 0, 0, 1, '0', '', 0, 0, '', '', '0');
INSERT INTO membres (`mlogin`, `mnom`, `mprenom`, `memail`, `memailparent`, `madresse`, `cp`, `ville`, `mtelf`, `mtelm`, `mdaten`, `m25ans`, `mlieun`, `msexe`, `mniveaux`, `mbranum`, `mbradat`, `mbraval`, `mbrpnum`, `mbrpdat`, `mbrpval`, `numinstavion`, `dateinstavion`, `numivv`, `dateivv`, `medical`, `numlicencefed`, `vallicencefed`, `manneeins`, `manneeffvv`, `manneeffa`, `msolde`, `mforfvv`, `macces`, `club`, `ext`, `actif`, `username`, `photo`, `compte`, `profil`, `comment`, `trigramme`, `categorie`) VALUES ('panoramix', 'Panoramix', 'Panoramix', 'panoramix@free.fr', '', 'Hutte de Panoramix', 0, 'Village d\'Astérix', '', '', NULL, 0, '0', 'M', '4', '', NULL, NULL, '', NULL, NULL, '', NULL, '', NULL, NULL, '', NULL, 0, 0, 0, '0.00', '0', 0, 0, 0, 1, '0', '', 0, 0, '', '', '0');
INSERT INTO membres (`mlogin`, `mnom`, `mprenom`, `memail`, `memailparent`, `madresse`, `cp`, `ville`, `mtelf`, `mtelm`, `mdaten`, `m25ans`, `mlieun`, `msexe`, `mniveaux`, `mbranum`, `mbradat`, `mbraval`, `mbrpnum`, `mbrpdat`, `mbrpval`, `numinstavion`, `dateinstavion`, `numivv`, `dateivv`, `medical`, `numlicencefed`, `vallicencefed`, `manneeins`, `manneeffvv`, `manneeffa`, `msolde`, `mforfvv`, `macces`, `club`, `ext`, `actif`, `username`, `photo`, `compte`, `profil`, `comment`, `trigramme`, `categorie`) VALUES ('goudurix', 'Goudurix', 'Goudurix', 'goudurix@free.fr', '', 'Hutte d\'Abraracourcix', 0, 'Village d\'Astérix', '', '', NULL, 1, '0', 'M', '0', '', NULL, NULL, '', NULL, NULL, '', NULL, '', NULL, NULL, '', NULL, 0, 0, 0, '0.00', '0', 0, 0, 0, 1, '0', '', 162, 0, '', '', '0');


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
) ENGINE=MyISAM AUTO_INCREMENT=121 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

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
) ENGINE=MyISAM AUTO_INCREMENT=173 DEFAULT CHARSET=utf8;

INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (1, 'Heures de vol et remorqués', '', 'Heures de vol et remorqués', '756', 1, '0.00', '1126.50', '0', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (159, 'Pierre Jean', 'first', 'Compte pilote', '411', 1, '0.00', '0.00', 'testadmin', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (160, 'Legaulois Astérix', 'asterix', 'Compte pilote', '411', 1, '0.00', '0.00', 'testadmin', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (161, 'Abraracourcix Bonemine', 'bon emine', 'Compte pilote', '411', 1, '0.00', '0.00', 'testadmin', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (162, 'Abraracourcix Abraracourcix', 'abraracourcix', 'Compte pilote', '411', 1, '480.00', '500.00', 'testadmin', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (163, 'Panoramix Panoramix', 'panoramix', 'Compte pilote', '411', 1, '646.50', '1000.00', 'testadmin', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (164, 'Capital', '', 'Capital', '101', 1, '0.00', '0.00', 'testadmin', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (165, 'Compte courant', '', 'Compte courant', '512', 1, '1500.00', '100.00', 'testadmin', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (166, 'Fournisseur de ruban adésif', '', 'Fournisseur de ruban adésif', '401', 1, '0.00', '0.00', 'testadmin', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (167, 'Entretien', '', 'Entretien', '615', 1, '0.00', '0.00', 'testadmin', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (168, 'Assurance', '', 'Assurance', '616', 1, '0.00', '0.00', 'testadmin', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (169, 'Subvention', '', 'Subvention', '746', 1, '0.00', '0.00', 'testadmin', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (170, 'Essence et Huile', '', 'Essence et Huile', '601', 1, '100.00', '0.00', 'testadmin', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (171, 'Vente librairie', '', 'Vente librairie', '75', 1, '0.00', '0.00', 'testadmin', 0);
INSERT INTO comptes (`id`, `nom`, `pilote`, `desc`, `codec`, `actif`, `debit`, `credit`, `saisie_par`, `club`) VALUES (172, 'Moi Frédéric', 'testuser', 'Compte pilote', '411', 1, '0.00', '0.00', 'testadmin', 0);


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
) ENGINE=MyISAM AUTO_INCREMENT=4452 DEFAULT CHARSET=latin1;

INSERT INTO ecritures (`id`, `annee_exercise`, `date_creation`, `date_op`, `compte1`, `compte2`, `montant`, `description`, `type`, `num_cheque`, `saisie_par`, `gel`, `club`, `achat`, `quantite`, `prix`, `categorie`) VALUES (4443, 2011, '2012-12-23', '2012-12-23', 165, 162, '500.00', 'Avance sur vol', 0, 'CH123456', 'testadmin', 0, 0, 0, '0', '0.00', 0);
INSERT INTO ecritures (`id`, `annee_exercise`, `date_creation`, `date_op`, `compte1`, `compte2`, `montant`, `description`, `type`, `num_cheque`, `saisie_par`, `gel`, `club`, `achat`, `quantite`, `prix`, `categorie`) VALUES (4444, 2011, '2012-12-23', '2012-12-23', 170, 165, '100.00', 'Achat d\'essence', 0, 'CHXYZT', 'testadmin', 0, 0, 0, '0', '0.00', 0);
INSERT INTO ecritures (`id`, `annee_exercise`, `date_creation`, `date_op`, `compte1`, `compte2`, `montant`, `description`, `type`, `num_cheque`, `saisie_par`, `gel`, `club`, `achat`, `quantite`, `prix`, `categorie`) VALUES (4445, 2011, '2012-12-23', '2012-12-23', 162, 1, '300.00', '', 0, 'Forfait heures', 'testadmin', 0, 0, 3819, '1', '300.00', 0);
INSERT INTO ecritures (`id`, `annee_exercise`, `date_creation`, `date_op`, `compte1`, `compte2`, `montant`, `description`, `type`, `num_cheque`, `saisie_par`, `gel`, `club`, `achat`, `quantite`, `prix`, `categorie`) VALUES (4446, 2011, '2012-12-23', '2012-12-23', 162, 1, '180.00', '23/12/2012  0.00-1.50 F-BERK Goudurix Goudurix', 0, 'Heure de vol avion', 'testadmin', 0, 0, 3820, '1.5', '120.00', 0);
INSERT INTO ecritures (`id`, `annee_exercise`, `date_creation`, `date_op`, `compte1`, `compte2`, `montant`, `description`, `type`, `num_cheque`, `saisie_par`, `gel`, `club`, `achat`, `quantite`, `prix`, `categorie`) VALUES (4447, 2011, '2012-12-23', '2012-12-23', 165, 163, '1000.00', 'Avance sur vol', 0, 'X1234', 'testadmin', 0, 0, 0, '0', '0.00', 0);
INSERT INTO ecritures (`id`, `annee_exercise`, `date_creation`, `date_op`, `compte1`, `compte2`, `montant`, `description`, `type`, `num_cheque`, `saisie_par`, `gel`, `club`, `achat`, `quantite`, `prix`, `categorie`) VALUES (4448, 2011, '2012-12-23', '2012-12-23', 163, 1, '300.00', '', 0, 'Forfait heures', 'testadmin', 0, 0, 3821, '1', '300.00', 0);
INSERT INTO ecritures (`id`, `annee_exercise`, `date_creation`, `date_op`, `compte1`, `compte2`, `montant`, `description`, `type`, `num_cheque`, `saisie_par`, `gel`, `club`, `achat`, `quantite`, `prix`, `categorie`) VALUES (4449, 2011, '2012-12-23', '2012-12-23', 163, 1, '270.00', '', 0, 'Pack remorqués', 'testadmin', 0, 0, 3822, '1', '270.00', 0);
INSERT INTO ecritures (`id`, `annee_exercise`, `date_creation`, `date_op`, `compte1`, `compte2`, `montant`, `description`, `type`, `num_cheque`, `saisie_par`, `gel`, `club`, `achat`, `quantite`, `prix`, `categorie`) VALUES (4450, 2011, '2012-12-23', '2012-12-23', 163, 1, '49.50', ' à 13h00  sur F-CJRG', 0, 'Heure de vol biplace', 'testadmin', 0, 0, 3824, ' 1h50', '27.00', 0);
INSERT INTO ecritures (`id`, `annee_exercise`, `date_creation`, `date_op`, `compte1`, `compte2`, `montant`, `description`, `type`, `num_cheque`, `saisie_par`, `gel`, `club`, `achat`, `quantite`, `prix`, `categorie`) VALUES (4451, 2011, '2012-12-23', '2012-12-23', 163, 1, '27.00', ' à 15h00  sur F-CJRG', 0, 'Heure de vol biplace', 'testadmin', 0, 0, 3826, ' 1h00', '27.00', 0);


#
# TABLE STRUCTURE FOR: machinesp
#

DROP TABLE IF EXISTS machinesp;

CREATE TABLE `machinesp` (
  `mpconstruc` varchar(40) NOT NULL COMMENT 'Constructeur',
  `mpmodele` varchar(10) NOT NULL COMMENT 'modèle',
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
  PRIMARY KEY (`mpimmat`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`) VALUES ('Alexander Schleicher', 'Asw20', 'F-CERP', 'UP', '0.00', '1', 0, 1, 1, 0, 'Gratuit', 'Gratuit', 'Gratuit', 180, 1, '', 0);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`) VALUES ('Grob', 'G103', 'F-CFYD', 'T83', '0.00', '2', 0, 1, 0, 0, 'Heure de vol biplace', 'Heure de vol au forfait', 'Gratuit', 180, 1, '', 0);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`) VALUES ('Centrair', 'Pégase', 'F-CGFN', '3S', '0.00', '1', 0, 1, 1, 0, 'Gratuit', 'Gratuit', 'Gratuit', 180, 1, '', 0);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`) VALUES ('Centrair', 'Asw20', 'F-CGKS', 'WE', '0.00', '1', 0, 0, 1, 0, 'Gratuit', 'Gratuit', 'Gratuit', 180, 1, '', 0);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`) VALUES ('Centrair', 'Pégase', 'F-CGNP', 'Y31', '0.00', '1', 0, 1, 0, 0, 'Heure de vol monoplace', 'Heure de vol au forfait', 'Gratuit', 180, 1, '', 0);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`) VALUES ('Glaser-Dirks', 'DG400', 'F-CGRD', 'RD', '0.00', '1', 1, 1, 0, 0, 'Heure de vol monoplace', 'Heure de vol au forfait', 'Heure moteur', 180, 1, '', 0);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`) VALUES ('Politechnika Warszawska', 'PW-5', 'F-CICA', 'CA', '0.00', '1', 0, 1, 0, 0, 'Heure de vol monoplace', 'Heure de vol au forfait', 'Gratuit', 180, 1, '', 0);
INSERT INTO machinesp (`mpconstruc`, `mpmodele`, `mpimmat`, `mpnumc`, `mpnbhdv`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `club`, `mprix`, `mprix_forfait`, `mprix_moteur`, `mmax_facturation`, `actif`, `comment`, `horametre_en_minutes`) VALUES ('Alexander Schleicher', 'Ask21', 'F-CJRG', 'RG', '0.00', '2', 0, 1, 0, 0, 'Heure de vol biplace', 'Heure de vol au forfait', 'Gratuit', 180, 1, '', 0);


#
# TABLE STRUCTURE FOR: machinesa
#

DROP TABLE IF EXISTS machinesa;

CREATE TABLE `machinesa` (
  `macconstruc` varchar(40) NOT NULL COMMENT 'Constructeur',
  `macmodele` varchar(10) NOT NULL COMMENT 'Modèle',
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
  PRIMARY KEY (`macimmat`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO machinesa (`macconstruc`, `macmodele`, `macimmat`, `macnbhdv`, `macplaces`, `macrem`, `maprive`, `club`, `actif`, `comment`, `maprix`, `maprixdc`, `horametre_en_minutes`) VALUES ('Robin', 'DR400', 'F-BERK', '0.00', 4, 1, 0, 0, 1, '', 'Heure de vol avion', 'Forfait heures', 0);
INSERT INTO machinesa (`macconstruc`, `macmodele`, `macimmat`, `macnbhdv`, `macplaces`, `macrem`, `maprive`, `club`, `actif`, `comment`, `maprix`, `maprixdc`, `horametre_en_minutes`) VALUES ('Socata', 'MS893-L', 'F-BLIT', '0.00', 2, 1, 0, 0, 1, '', 'Heure de vol avion', 'Forfait heures', 0);


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
  `vpobs` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL COMMENT 'Observation',
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
  `essence` int(11) NOT NULL DEFAULT '0' COMMENT 'Essence',
  `vpticcolle` tinyint(1) NOT NULL COMMENT 'Si ticket collé ou pas',
  PRIMARY KEY (`vpid`),
  KEY `saisie_par` (`saisie_par`),
  KEY `pilote_remorqueur` (`pilote_remorqueur`),
  KEY `remorqueur` (`remorqueur`)
) ENGINE=MyISAM AUTO_INCREMENT=1663 DEFAULT CHARSET=utf8;

INSERT INTO volsp (`vpid`, `vpdate`, `vppilid`, `vpmacid`, `vpcdeb`, `vpcfin`, `vpduree`, `vpobs`, `vpdc`, `vpcategorie`, `vpautonome`, `vpnumvi`, `vpnbkm`, `vplieudeco`, `vplieuatt`, `vpaltrem`, `vpinst`, `facture`, `payeur`, `pourcentage`, `club`, `saisie_par`, `remorqueur`, `pilote_remorqueur`, `tempmoteur`, `reappro`, `essence`, `vpticcolle`) VALUES (1660, '2012-12-23', 'bon emine', 'F-CERP', '12.00', '13.30', '90.00', '', 0, 0, 3, '', 0, '', '', 500, '', 0, '', 0, 0, 'testadmin', 'F-BERK', 'abraracourcix', '0.00', 0, 0, 0);
INSERT INTO volsp (`vpid`, `vpdate`, `vppilid`, `vpmacid`, `vpcdeb`, `vpcfin`, `vpduree`, `vpobs`, `vpdc`, `vpcategorie`, `vpautonome`, `vpnumvi`, `vpnbkm`, `vplieudeco`, `vplieuatt`, `vpaltrem`, `vpinst`, `facture`, `payeur`, `pourcentage`, `club`, `saisie_par`, `remorqueur`, `pilote_remorqueur`, `tempmoteur`, `reappro`, `essence`, `vpticcolle`) VALUES (1661, '2012-12-23', 'panoramix', 'F-CJRG', '13.00', '14.50', '110.00', '', 0, 0, 3, '', 0, '', '', 500, '', 0, '', 0, 0, 'testadmin', 'F-BERK', 'abraracourcix', '0.00', 0, 0, 0);
INSERT INTO volsp (`vpid`, `vpdate`, `vppilid`, `vpmacid`, `vpcdeb`, `vpcfin`, `vpduree`, `vpobs`, `vpdc`, `vpcategorie`, `vpautonome`, `vpnumvi`, `vpnbkm`, `vplieudeco`, `vplieuatt`, `vpaltrem`, `vpinst`, `facture`, `payeur`, `pourcentage`, `club`, `saisie_par`, `remorqueur`, `pilote_remorqueur`, `tempmoteur`, `reappro`, `essence`, `vpticcolle`) VALUES (1662, '2012-12-23', 'panoramix', 'F-CJRG', '15.00', '16.00', '60.00', '', 0, 0, 3, '', 0, '', '', 500, '', 0, '', 0, 0, 'testadmin', 'F-BERK', 'abraracourcix', '0.00', 0, 0, 0);


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
  `vaobs` varchar(50) DEFAULT NULL COMMENT 'Observation',
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
) ENGINE=MyISAM AUTO_INCREMENT=353 DEFAULT CHARSET=utf8;

INSERT INTO volsa (`vaid`, `vadate`, `vapilid`, `vamacid`, `vacdeb`, `vacfin`, `vaduree`, `vaobs`, `vadc`, `vacategorie`, `varem`, `vanumvi`, `vanbpax`, `vaprixvol`, `vainst`, `valieudeco`, `valieuatt`, `facture`, `payeur`, `pourcentage`, `club`, `gel`, `saisie_par`, `vaatt`, `local`, `nuit`, `reappro`, `essence`, `vahdeb`, `vahfin`) VALUES (352, '2012-12-23', 'goudurix', 'F-BERK', '0.00', '1.50', '1.50', '', 0, 0, 0, '', '', '0.00', 'first', '', '', 0, '', 0, 0, 0, 'testadmin', 1, 0, 0, 0, 0, '0.00', '0.00');


#
# TABLE STRUCTURE FOR: tarifs
#

DROP TABLE IF EXISTS tarifs;

CREATE TABLE `tarifs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant',
  `reference` varchar(32) NOT NULL COMMENT 'Référence du produit',
  `date` date DEFAULT NULL COMMENT 'Date d''application',
  `description` varchar(80) DEFAULT NULL COMMENT 'Description',
  `prix` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT 'Prix unitaire',
  `compte` int(11) NOT NULL DEFAULT '0' COMMENT 'Numéro de compte associé',
  `saisie_par` varchar(25) NOT NULL COMMENT 'Opérateur',
  `club` tinyint(1) DEFAULT '0' COMMENT 'Gestion multi-club',
  `nb_tickets` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT 'Quantité de ticket à créditer',
  `type_ticket` int(11) DEFAULT NULL COMMENT 'Type de ticket à créditer',
  PRIMARY KEY (`id`),
  KEY `compte` (`compte`)
) ENGINE=MyISAM AUTO_INCREMENT=67 DEFAULT CHARSET=utf8;

INSERT INTO tarifs (`id`, `reference`, `date`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`) VALUES (1, 'Gratuit', NULL, 'Heure de vol gratuite, (VI, privée, etc.)', '0.00', 1, 'admin', 0, '0.00', NULL);
INSERT INTO tarifs (`id`, `reference`, `date`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`) VALUES (56, 'Heure de vol monoplace', '0000-00-00', 'Heure de vol monoplace', '18.00', 1, 'testadmin', 0, '0.00', 0);
INSERT INTO tarifs (`id`, `reference`, `date`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`) VALUES (57, 'Heure de vol biplace', '0000-00-00', 'Heure de vol biplace', '27.00', 1, 'testadmin', 0, '0.00', 0);
INSERT INTO tarifs (`id`, `reference`, `date`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`) VALUES (58, 'Heure de vol au forfait', '0000-00-00', 'Heure de vol au forfait', '10.00', 1, 'testadmin', 0, '0.00', 0);
INSERT INTO tarifs (`id`, `reference`, `date`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`) VALUES (59, 'Heure de vol avion', '0000-00-00', 'Heure de vol avion', '120.00', 1, 'testadmin', 0, '0.00', 0);
INSERT INTO tarifs (`id`, `reference`, `date`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`) VALUES (60, 'Forfait heures', '0000-00-00', 'Forfait heures', '300.00', 1, 'testadmin', 0, '0.00', 0);
INSERT INTO tarifs (`id`, `reference`, `date`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`) VALUES (61, 'Remorqué 500m', '0000-00-00', 'Remorqué', '30.00', 1, 'testadmin', 0, '0.00', 0);
INSERT INTO tarifs (`id`, `reference`, `date`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`) VALUES (62, 'Treuillé', '0000-00-00', 'Treuillé', '7.00', 1, 'testadmin', 0, '0.00', 0);
INSERT INTO tarifs (`id`, `reference`, `date`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`) VALUES (63, 'Carnet de vol', '0000-00-00', 'Carnet de vol', '15.00', 171, 'testadmin', 0, '0.00', 0);
INSERT INTO tarifs (`id`, `reference`, `date`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`) VALUES (64, 'Pack remorqués', '0000-00-00', 'Pack 11 remorqué', '270.00', 1, 'testadmin', 0, '11.00', 0);
INSERT INTO tarifs (`id`, `reference`, `date`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`) VALUES (65, 'Remorqué 300m', '0000-00-00', 'Remorqué 300', '20.00', 1, 'testadmin', 0, '0.00', 0);
INSERT INTO tarifs (`id`, `reference`, `date`, `description`, `prix`, `compte`, `saisie_par`, `club`, `nb_tickets`, `type_ticket`) VALUES (66, 'Heure moteur', '0000-00-00', 'Heure moteur motoplaneur', '60.00', 1, 'testadmin', 0, '0.00', 0);


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
  PRIMARY KEY (`id`),
  KEY `pilote` (`pilote`),
  KEY `saisie_par` (`saisie_par`)
) ENGINE=MyISAM AUTO_INCREMENT=3827 DEFAULT CHARSET=utf8 COMMENT='Lignes de factures';

INSERT INTO achats (`id`, `date`, `produit`, `quantite`, `prix`, `description`, `pilote`, `facture`, `saisie_par`, `club`, `machine`, `vol_planeur`, `vol_avion`, `mvt_pompe`) VALUES (3819, '2012-12-23', 'Forfait heures', '1.00', '300.00', '', 'abraracourcix', 0, 'testadmin', 0, '0', 0, 0, 0);
INSERT INTO achats (`id`, `date`, `produit`, `quantite`, `prix`, `description`, `pilote`, `facture`, `saisie_par`, `club`, `machine`, `vol_planeur`, `vol_avion`, `mvt_pompe`) VALUES (3820, '2012-12-23', 'Heure de vol avion', '1.50', '120.00', '23/12/2012  0.00-1.50 F-BERK Goudurix Goudurix', 'abraracourcix', 0, 'testadmin', 0, 'F-BERK', NULL, 352, NULL);
INSERT INTO achats (`id`, `date`, `produit`, `quantite`, `prix`, `description`, `pilote`, `facture`, `saisie_par`, `club`, `machine`, `vol_planeur`, `vol_avion`, `mvt_pompe`) VALUES (3821, '2012-12-23', 'Forfait heures', '1.00', '300.00', '', 'panoramix', 0, 'testadmin', 0, '0', 0, 0, 0);
INSERT INTO achats (`id`, `date`, `produit`, `quantite`, `prix`, `description`, `pilote`, `facture`, `saisie_par`, `club`, `machine`, `vol_planeur`, `vol_avion`, `mvt_pompe`) VALUES (3822, '2012-12-23', 'Pack remorqués', '1.00', '270.00', '', 'panoramix', 0, 'testadmin', 0, '0', 0, 0, 0);
INSERT INTO achats (`id`, `date`, `produit`, `quantite`, `prix`, `description`, `pilote`, `facture`, `saisie_par`, `club`, `machine`, `vol_planeur`, `vol_avion`, `mvt_pompe`) VALUES (3823, '2012-12-23', 'rem', '0.00', NULL, ' à 13h00  decompté', 'panoramix', 0, 'testadmin', 0, 'F-CJRG', 1661, NULL, NULL);
INSERT INTO achats (`id`, `date`, `produit`, `quantite`, `prix`, `description`, `pilote`, `facture`, `saisie_par`, `club`, `machine`, `vol_planeur`, `vol_avion`, `mvt_pompe`) VALUES (3824, '2012-12-23', 'Heure de vol biplace', '1.83', '27.00', ' à 13h00  sur F-CJRG', 'panoramix', 0, 'testadmin', 0, 'F-CJRG', 1661, NULL, NULL);
INSERT INTO achats (`id`, `date`, `produit`, `quantite`, `prix`, `description`, `pilote`, `facture`, `saisie_par`, `club`, `machine`, `vol_planeur`, `vol_avion`, `mvt_pompe`) VALUES (3825, '2012-12-23', 'rem', '0.00', NULL, ' à 15h00  decompté', 'panoramix', 0, 'testadmin', 0, 'F-CJRG', 1662, NULL, NULL);
INSERT INTO achats (`id`, `date`, `produit`, `quantite`, `prix`, `description`, `pilote`, `facture`, `saisie_par`, `club`, `machine`, `vol_planeur`, `vol_avion`, `mvt_pompe`) VALUES (3826, '2012-12-23', 'Heure de vol biplace', '1.00', '27.00', ' à 15h00  sur F-CJRG', 'panoramix', 0, 'testadmin', 0, 'F-CJRG', 1662, NULL, NULL);


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
) ENGINE=MyISAM AUTO_INCREMENT=76 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (69, 67, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (70, 68, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (71, 69, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (72, 70, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (73, 71, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (74, 72, NULL, NULL);
INSERT INTO user_profile (`id`, `user_id`, `country`, `website`) VALUES (75, 73, NULL, NULL);


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
) ENGINE=MyISAM AUTO_INCREMENT=74 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO users (`id`, `role_id`, `username`, `password`, `email`, `banned`, `ban_reason`, `newpass`, `newpass_key`, `newpass_time`, `last_ip`, `last_login`, `created`, `modified`) VALUES (15, 1, 'testuser', '$1$PnMH2ByX$ElBRQJ.CDecgw2N59l/ki.', 'testuser@free.fr', 0, NULL, NULL, NULL, NULL, '::1', '2012-12-23 15:21:19', '2011-04-21 15:21:13', '2012-12-23 15:21:19');
INSERT INTO users (`id`, `role_id`, `username`, `password`, `email`, `banned`, `ban_reason`, `newpass`, `newpass_key`, `newpass_time`, `last_ip`, `last_login`, `created`, `modified`) VALUES (16, 2, 'testadmin', '$1$VJBlBegK$kLUOjkOdl6hkrq1M5vc1e0', 'frederic.peignot@free.fr', 0, NULL, NULL, NULL, NULL, '127.0.0.1', '2012-12-24 18:33:11', '2011-04-21 15:21:40', '2012-12-24 18:33:11');
INSERT INTO users (`id`, `role_id`, `username`, `password`, `email`, `banned`, `ban_reason`, `newpass`, `newpass_key`, `newpass_time`, `last_ip`, `last_login`, `created`, `modified`) VALUES (58, 7, 'testplanchiste', '$1$6WTcBSWq$AUfidm8nDf3mjYSGLp.Zu/', 'testplanchiste@free.fr', 0, NULL, NULL, NULL, NULL, '::1', '2012-12-23 15:21:26', '2012-01-25 21:00:23', '2012-12-23 15:21:26');
INSERT INTO users (`id`, `role_id`, `username`, `password`, `email`, `banned`, `ban_reason`, `newpass`, `newpass_key`, `newpass_time`, `last_ip`, `last_login`, `created`, `modified`) VALUES (59, 8, 'testca', '$1$cqNgYIs9$8Rbi1tBA3.okpfxAkkpYb/', 'testca@free.fr', 0, NULL, NULL, NULL, NULL, '::1', '2012-12-23 15:21:33', '2012-01-25 21:00:58', '2012-12-23 15:21:33');
INSERT INTO users (`id`, `role_id`, `username`, `password`, `email`, `banned`, `ban_reason`, `newpass`, `newpass_key`, `newpass_time`, `last_ip`, `last_login`, `created`, `modified`) VALUES (60, 3, 'testbureau', '$1$surTQ3S3$YtBi94Oo2rrceOQV9.j4h/', 'testbureau@free.fr', 0, NULL, NULL, NULL, NULL, '::1', '2012-12-23 15:21:40', '2012-01-25 21:01:36', '2012-12-23 15:21:40');
INSERT INTO users (`id`, `role_id`, `username`, `password`, `email`, `banned`, `ban_reason`, `newpass`, `newpass_key`, `newpass_time`, `last_ip`, `last_login`, `created`, `modified`) VALUES (61, 9, 'testtresorier', '$1$KiPMl0ho$/E3NBaprpM5Xcv.z40zjK0', 'testresorier@free.fr', 0, NULL, NULL, NULL, NULL, '127.0.0.1', '0000-00-00 00:00:00', '2012-01-25 21:02:36', '2012-01-25 21:02:36');
INSERT INTO users (`id`, `role_id`, `username`, `password`, `email`, `banned`, `ban_reason`, `newpass`, `newpass_key`, `newpass_time`, `last_ip`, `last_login`, `created`, `modified`) VALUES (67, 1, 'first', '$1$lXARJZw5$x4h.eHjdbpE9h.u7mQmFg0', 'jean@free.fr', 0, NULL, NULL, NULL, NULL, '::1', '0000-00-00 00:00:00', '2012-12-23 15:18:34', '2012-12-23 15:18:34');
INSERT INTO users (`id`, `role_id`, `username`, `password`, `email`, `banned`, `ban_reason`, `newpass`, `newpass_key`, `newpass_time`, `last_ip`, `last_login`, `created`, `modified`) VALUES (68, 1, 'asterix', '$1$PlVuZygv$o7CDddhdZTBPWEvRmodgk1', 'asterix@free.fr', 0, NULL, NULL, NULL, NULL, '::1', '0000-00-00 00:00:00', '2012-12-23 15:18:45', '2012-12-23 15:18:45');
INSERT INTO users (`id`, `role_id`, `username`, `password`, `email`, `banned`, `ban_reason`, `newpass`, `newpass_key`, `newpass_time`, `last_ip`, `last_login`, `created`, `modified`) VALUES (69, 1, 'bon emine', '$1$zwDs2Tkd$jU3E1E2eJosREm0zMUCpL.', 'bonemine@free.fr', 0, NULL, NULL, NULL, NULL, '::1', '0000-00-00 00:00:00', '2012-12-23 15:18:54', '2012-12-23 15:18:54');
INSERT INTO users (`id`, `role_id`, `username`, `password`, `email`, `banned`, `ban_reason`, `newpass`, `newpass_key`, `newpass_time`, `last_ip`, `last_login`, `created`, `modified`) VALUES (70, 1, 'abraracourcix', '$1$6JfPBpi2$BhdX7cRpfx5cxQLldqgug/', 'abraracourcix@free.fr', 0, NULL, NULL, NULL, NULL, '::1', '0000-00-00 00:00:00', '2012-12-23 15:18:55', '2012-12-23 15:18:55');
INSERT INTO users (`id`, `role_id`, `username`, `password`, `email`, `banned`, `ban_reason`, `newpass`, `newpass_key`, `newpass_time`, `last_ip`, `last_login`, `created`, `modified`) VALUES (71, 1, 'panoramix', '$1$lku6eIOq$aCOPjEyqwN/vO8CRwpGiU.', 'panoramix@free.fr', 0, NULL, NULL, NULL, NULL, '::1', '0000-00-00 00:00:00', '2012-12-23 15:18:56', '2012-12-23 15:18:56');
INSERT INTO users (`id`, `role_id`, `username`, `password`, `email`, `banned`, `ban_reason`, `newpass`, `newpass_key`, `newpass_time`, `last_ip`, `last_login`, `created`, `modified`) VALUES (72, 1, 'goudurix', '$1$A2pTtutb$.DoVzqj2V0zTEA2hShqq7.', 'goudurix@free.fr', 0, NULL, NULL, NULL, NULL, '::1', '0000-00-00 00:00:00', '2012-12-23 15:18:59', '2012-12-23 15:18:59');
INSERT INTO users (`id`, `role_id`, `username`, `password`, `email`, `banned`, `ban_reason`, `newpass`, `newpass_key`, `newpass_time`, `last_ip`, `last_login`, `created`, `modified`) VALUES (73, 1, 'titi', '$1$JUiYOJS4$VXQuJXU9DiRVkMoevyDyO0', 'titi@free.fr', 0, NULL, NULL, NULL, NULL, '::1', '0000-00-00 00:00:00', '2012-12-23 15:22:24', '2012-12-23 15:22:24');


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

INSERT INTO permissions (`id`, `role_id`, `data`) VALUES (1, 1, 'a:1:{s:3:\"uri\";a:33:{i:0;s:8:\"/membre/\";i:1;s:9:\"/planeur/\";i:2;s:7:\"/avion/\";i:3;s:17:\"/vols_avion/page/\";i:4;s:29:\"/vols_avion/filterValidation/\";i:5;s:16:\"/vols_avion/pdf/\";i:6;s:23:\"/vols_avion/statistics/\";i:7;s:21:\"/vols_avion/new_year/\";i:8;s:19:\"/vols_planeur/page/\";i:9;s:24:\"/vols_planeur/statistic/\";i:10;s:31:\"/vols_planeur/filterValidation/\";i:11;s:18:\"/vols_planeur/pdf/\";i:12;s:24:\"/vols_planeur/pdf_month/\";i:13;s:26:\"/vols_planeur/pdf_machine/\";i:14;s:25:\"/vols_planeur/export_per/\";i:15;s:21:\"/vols_planeur/export/\";i:16;s:23:\"/vols_planeur/new_year/\";i:17;s:19:\"/factures/en_cours/\";i:18;s:15:\"/factures/page/\";i:19;s:15:\"/factures/view/\";i:20;s:21:\"/factures/ma_facture/\";i:21;s:19:\"/compta/mon_compte/\";i:22;s:23:\"/compta/journal_compte/\";i:23;s:25:\"/compta/filterValidation/\";i:24;s:12:\"/compta/pdf/\";i:25;s:17:\"/compta/new_year/\";i:26;s:18:\"/comptes/new_year/\";i:27;s:17:\"/achats/new_year/\";i:28;s:14:\"/tickets/page/\";i:29;s:13:\"/event/stats/\";i:30;s:12:\"/event/page/\";i:31;s:17:\"/event/formation/\";i:32;s:11:\"/event/fai/\";}}');
INSERT INTO permissions (`id`, `role_id`, `data`) VALUES (2, 7, 'a:1:{s:3:\"uri\";a:3:{i:0;s:12:\"/vols_avion/\";i:1;s:14:\"/vols_planeur/\";i:2;s:0:\"\";}}');
INSERT INTO permissions (`id`, `role_id`, `data`) VALUES (3, 9, 'a:1:{s:3:\"uri\";a:8:{i:0;s:10:\"/factures/\";i:1;s:8:\"/compta/\";i:2;s:9:\"/comptes/\";i:3;s:10:\"/remorque/\";i:4;s:16:\"/plan_comptable/\";i:5;s:11:\"/categorie/\";i:6;s:8:\"/tarifs/\";i:7;s:0:\"\";}}');
INSERT INTO permissions (`id`, `role_id`, `data`) VALUES (4, 8, 'a:1:{s:3:\"uri\";a:17:{i:0;s:8:\"/membre/\";i:1;s:9:\"/planeur/\";i:2;s:7:\"/avion/\";i:3;s:12:\"/vols_avion/\";i:4;s:14:\"/vols_planeur/\";i:5;s:10:\"/factures/\";i:6;s:8:\"/compta/\";i:7;s:8:\"/compta/\";i:8;s:8:\"/compta/\";i:9;s:9:\"/comptes/\";i:10;s:9:\"/tickets/\";i:11;s:7:\"/event/\";i:12;s:10:\"/rapports/\";i:13;s:10:\"/licences/\";i:14;s:8:\"/achats/\";i:15;s:10:\"/terrains/\";i:16;s:7:\"/admin/\";}}');
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
) ENGINE=MyISAM AUTO_INCREMENT=194 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

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

INSERT INTO ci_sessions (`session_id`, `ip_address`, `user_agent`, `last_activity`, `user_data`) VALUES ('0cf87c1c3e61a23dc6349b08c42d65e6', '127.0.0.1', 'Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.2.24) Gecko/20111108 Mandriva Linux/1.9.2.24-0.1mdv2010.2 (2010.2) Fire', 1356370678, 'a:14:{s:9:\"user_data\";s:0:\"\";s:10:\"DX_user_id\";s:2:\"16\";s:11:\"DX_username\";s:9:\"testadmin\";s:10:\"DX_role_id\";s:1:\"2\";s:12:\"DX_role_name\";s:5:\"admin\";s:18:\"DX_parent_roles_id\";a:5:{i:0;s:1:\"9\";i:1;s:1:\"3\";i:2;s:1:\"8\";i:3;s:1:\"7\";i:4;s:1:\"1\";}s:20:\"DX_parent_roles_name\";a:5:{i:0;s:9:\"tresorier\";i:1;s:6:\"bureau\";i:2;s:2:\"ca\";i:3;s:10:\"planchiste\";i:4;s:6:\"membre\";}s:13:\"DX_permission\";a:1:{s:3:\"uri\";a:32:{i:0;s:8:\"/membre/\";i:1;s:9:\"/planeur/\";i:2;s:7:\"/avion/\";i:3;s:17:\"/vols_avion/page/\";i:4;s:29:\"/vols_avion/filterValidation/\";i:5;s:16:\"/vols_avion/pdf/\";i:6;s:23:\"/vols_avion/statistics/\";i:7;s:21:\"/vols_avion/new_year/\";i:8;s:19:\"/vols_planeur/page/\";i:9;s:24:\"/vols_planeur/statistic/\";i:10;s:31:\"/vols_planeur/filterValidation/\";i:11;s:18:\"/vols_planeur/pdf/\";i:12;s:24:\"/vols_planeur/pdf_month/\";i:13;s:26:\"/vols_planeur/pdf_machine/\";i:14;s:25:\"/vols_planeur/export_per/\";i:15;s:21:\"/vols_planeur/export/\";i:16;s:23:\"/vols_planeur/new_year/\";i:17;s:19:\"/factures/en_cours/\";i:18;s:15:\"/factures/page/\";i:19;s:15:\"/factures/view/\";i:20;s:21:\"/factures/ma_facture/\";i:21;s:19:\"/compta/mon_compte/\";i:22;s:23:\"/compta/journal_compte/\";i:23;s:25:\"/compta/filterValidation/\";i:24;s:12:\"/compta/pdf/\";i:25;s:17:\"/compta/new_year/\";i:26;s:18:\"/comptes/new_year/\";i:27;s:14:\"/tickets/page/\";i:28;s:13:\"/event/stats/\";i:29;s:12:\"/event/page/\";i:30;s:17:\"/event/formation/\";i:31;s:11:\"/event/fai/\";}}s:21:\"DX_parent_permissions\";a:5:{i:1;a:1:{s:3:\"uri\";a:33:{i:0;s:8:\"/membre/\";i:1;s:9:\"/planeur/\";i:2;s:7:\"/avion/\";i:3;s:17:\"/vols_avion/page/\";i:4;s:29:\"/vols_avion/filterValidation/\";i:5;s:16:\"/vols_avion/pdf/\";i:6;s:23:\"/vols_avion/statistics/\";i:7;s:21:\"/vols_avion/new_year/\";i:8;s:19:\"/vols_planeur/page/\";i:9;s:24:\"/vols_planeur/statistic/\";i:10;s:31:\"/vols_planeur/filterValidation/\";i:11;s:18:\"/vols_planeur/pdf/\";i:12;s:24:\"/vols_planeur/pdf_month/\";i:13;s:26:\"/vols_planeur/pdf_machine/\";i:14;s:25:\"/vols_planeur/export_per/\";i:15;s:21:\"/vols_planeur/export/\";i:16;s:23:\"/vols_planeur/new_year/\";i:17;s:19:\"/factures/en_cours/\";i:18;s:15:\"/factures/page/\";i:19;s:15:\"/factures/view/\";i:20;s:21:\"/factures/ma_facture/\";i:21;s:19:\"/compta/mon_compte/\";i:22;s:23:\"/compta/journal_compte/\";i:23;s:25:\"/compta/filterValidation/\";i:24;s:12:\"/compta/pdf/\";i:25;s:17:\"/compta/new_year/\";i:26;s:18:\"/comptes/new_year/\";i:27;s:17:\"/achats/new_year/\";i:28;s:14:\"/tickets/page/\";i:29;s:13:\"/event/stats/\";i:30;s:12:\"/event/page/\";i:31;s:17:\"/event/formation/\";i:32;s:11:\"/event/fai/\";}}i:2;a:1:{s:3:\"uri\";a:3:{i:0;s:12:\"/vols_avion/\";i:1;s:14:\"/vols_planeur/\";i:2;s:0:\"\";}}i:3;a:1:{s:3:\"uri\";a:8:{i:0;s:10:\"/factures/\";i:1;s:8:\"/compta/\";i:2;s:9:\"/comptes/\";i:3;s:10:\"/remorque/\";i:4;s:16:\"/plan_comptable/\";i:5;s:11:\"/categorie/\";i:6;s:8:\"/tarifs/\";i:7;s:0:\"\";}}i:4;a:1:{s:3:\"uri\";a:17:{i:0;s:8:\"/membre/\";i:1;s:9:\"/planeur/\";i:2;s:7:\"/avion/\";i:3;s:12:\"/vols_avion/\";i:4;s:14:\"/vols_planeur/\";i:5;s:10:\"/factures/\";i:6;s:8:\"/compta/\";i:7;s:8:\"/compta/\";i:8;s:8:\"/compta/\";i:9;s:9:\"/comptes/\";i:10;s:9:\"/tickets/\";i:11;s:7:\"/event/\";i:12;s:10:\"/rapports/\";i:13;s:10:\"/licences/\";i:14;s:8:\"/achats/\";i:15;s:10:\"/terrains/\";i:16;s:7:\"/admin/\";}}i:5;a:1:{s:3:\"uri\";a:2:{i:0;s:23:\"/compta/journal_compte/\";i:1;s:13:\"/compta/view/\";}}}s:12:\"DX_logged_in\";b:1;s:10:\"return_url\";s:43:\"http://localhost/gvv2/index.php/membre/page\";s:4:\"year\";s:4:\"2012\";s:8:\"per_page\";i:50;s:12:\"licence_type\";i:0;}');

--
-- Structure de la table `reports`
--
DROP TABLE IF EXISTS reports;

CREATE TABLE IF NOT EXISTS `reports` (
  `nom` varchar(64) NOT NULL COMMENT 'Nom du rapport',
  `titre` varchar(64) NOT NULL COMMENT 'Titre du rapport',
  `fields_list` varchar(128) NOT NULL,
  `align` varchar(128) NOT NULL COMMENT 'Alignement des colonnes',
  `width` varchar(128) NOT NULL COMMENT 'Largeur des colonnes PDF',
  `landscape` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'PDF en paysage',
  `sql` varchar(2048) NOT NULL COMMENT 'Requête sql',
  PRIMARY KEY (`nom`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Rapports définis par l''utilisateur';

--
-- Contenu de la table `reports`
--

INSERT INTO `reports` (`nom`, `titre`, `fields_list`, `align`, `width`, `landscape`, `sql`) VALUES
('utilisateurs', 'Liste des utilisateurs', 'Nom,Prénom,+/-25,Naissance', 'left,left,right,right', '32,32,8,24', 1, 'select mnom, mprenom, m25ans, date_format(mdaten, "%d/%m/%Y") from membres;'),
('tarifs', 'Liste des tarifs', 'Référence,Description,Date,Prix,Compte', 'left,left,right,right,left', '32,40,24,24,40', 0, 'select reference, description, date_format(date, "%d/%m/%Y"), prix, comptes.nom  from tarifs, comptes where tarifs.compte=comptes.id order by reference,date;');



