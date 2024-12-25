#
# TABLE STRUCTURE FOR: migrations
#

CREATE TABLE `migrations` (
  `version` int(3) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#
# TABLE STRUCTURE FOR: historique
#

CREATE TABLE `historique` (
  `id` int(8) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant',
  `machine` varchar(20) NOT NULL COMMENT 'Machine',
  `annee` int(4) NOT NULL COMMENT 'Année',
  `heures` int(4) NOT NULL COMMENT 'Heures',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8 COMMENT='Historique des heures de vol';

#
# TABLE STRUCTURE FOR: mails
#

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

CREATE TABLE `type_ticket` (
  `id` int(11) NOT NULL COMMENT 'Identifiant',
  `nom` varchar(64) NOT NULL COMMENT 'Nom',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Type de tickets';

#
# TABLE STRUCTURE FOR: terrains
#

CREATE TABLE `terrains` (
  `oaci` varchar(10) COLLATE latin1_general_ci NOT NULL COMMENT 'Code OACI',
  `nom` varchar(64) COLLATE latin1_general_ci DEFAULT NULL COMMENT 'Nom du terrain',
  `freq1` decimal(6,3) DEFAULT '0.000' COMMENT 'Fréquence principale',
  `freq2` decimal(6,3) DEFAULT '0.000' COMMENT 'Fréquence secondaire',
  `comment` varchar(256) COLLATE latin1_general_ci DEFAULT NULL COMMENT 'Description',
  PRIMARY KEY (`oaci`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

#
# TABLE STRUCTURE FOR: events
#

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

#
# TABLE STRUCTURE FOR: events_types
#

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

#
# TABLE STRUCTURE FOR: tickets
#

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant',
  `date` date NOT NULL COMMENT 'Date de l''opération',
  `pilote` varchar(25) NOT NULL COMMENT 'Pilote à créditer/débiter',
  `achat` int(11) DEFAULT NULL COMMENT 'Numéro de l''achat',
  `quantite` decimal(11,0) NOT NULL DEFAULT '0' COMMENT 'Incrément',
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

CREATE TABLE `categorie` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identifiant',
  `nom` varchar(32) COLLATE latin1_general_ci NOT NULL COMMENT 'Nom',
  `description` varchar(80) COLLATE latin1_general_ci DEFAULT NULL COMMENT 'Commentaire',
  `parent` int(11) NOT NULL DEFAULT '0' COMMENT 'Catégorie parente',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT 'Type de catégorie',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='Catégorie d écritures pour comptabilité analytique';

#
# TABLE STRUCTURE FOR: planc
#

CREATE TABLE `planc` (
  `pcode` varchar(10) NOT NULL,
  `pdesc` varchar(50) NOT NULL,
  UNIQUE KEY `pcode` (`pcode`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#
# TABLE STRUCTURE FOR: membres
#

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
  PRIMARY KEY (`mlogin`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#
# TABLE STRUCTURE FOR: licences
#

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
) ENGINE=MyISAM AUTO_INCREMENT=292 DEFAULT CHARSET=utf8;

#
# TABLE STRUCTURE FOR: ecritures
#

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
) ENGINE=MyISAM AUTO_INCREMENT=8861 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

#
# TABLE STRUCTURE FOR: machinesp
#

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

#
# TABLE STRUCTURE FOR: machinesa
#

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

#
# TABLE STRUCTURE FOR: volsp
#

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
) ENGINE=MyISAM AUTO_INCREMENT=2897 DEFAULT CHARSET=utf8;

#
# TABLE STRUCTURE FOR: volsa
#

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
) ENGINE=MyISAM AUTO_INCREMENT=768 DEFAULT CHARSET=utf8;

#
# TABLE STRUCTURE FOR: tarifs
#

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

#
# TABLE STRUCTURE FOR: achats
#

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
) ENGINE=MyISAM AUTO_INCREMENT=7217 DEFAULT CHARSET=utf8 COMMENT='Lignes de factures';

#
# TABLE STRUCTURE FOR: pompes
#

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

CREATE TABLE `user_profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `country` varchar(20) COLLATE utf8_bin DEFAULT NULL,
  `website` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=120 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

#
# TABLE STRUCTURE FOR: user_autologin
#

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
  `last_login` datetime NOT NULL DEFAULT '1900-01-01 00:00:00',
  `created` datetime NOT NULL DEFAULT '1900-01-01 00:00:00',
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=118 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

#
# TABLE STRUCTURE FOR: permissions
#

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `data` text COLLATE utf8_bin,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

#
# TABLE STRUCTURE FOR: roles
#

CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(30) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

#
# TABLE STRUCTURE FOR: login_attempts
#

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(40) COLLATE utf8_bin NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=526 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

#
# TABLE STRUCTURE FOR: ci_sessions
#

CREATE TABLE `ci_sessions` (
  `session_id` varchar(40) COLLATE utf8_bin NOT NULL DEFAULT '0',
  `ip_address` varchar(16) COLLATE utf8_bin NOT NULL DEFAULT '0',
  `user_agent` varchar(150) COLLATE utf8_bin NOT NULL,
  `last_activity` int(10) unsigned NOT NULL DEFAULT '0',
  `user_data` text COLLATE utf8_bin,
  PRIMARY KEY (`session_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

