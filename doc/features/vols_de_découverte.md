# Gestion des vols de découverte

## Cas d'utilisation

* En tant que trésorier je peux générer un vol de découverte
* En tant que trésorier je peux modifier un vol de découverte
* En tant que trésorier je peux imprimer ou envoyer le vol par email
* En tant que pilote je peux modifier les informations de contact
* En tant que pilote je peux signaler que j'ai effectué le vol
* En tant qu’administrateur je peux lister les vols vendus, effectués et non effectués
* En tant qu’administrateur je peux passer un vol de découverte à la date passée en perte et profit.


## Génération du vol

Le vol contient les informations suivantes :
* Numéro unique
* Date de vente
* Section
* Type de produit (donc tarif)
* Destinataire du vol, champ libre
* De la part de, champ libre

## Impression du vol

Au champ ci dessus on ajoute:
* un QR code
* Le nom du contact du club
* Le téléphone du contact
* date de validité

## Prise de rendez-vous

* Téléphone du bénéficiaire pour report éventuel
* Personne à prévenir en cas d'accident, nom et téléphone
* autorisation parentale pour les mineurs
* Date et horaire prévu du vol

## Enregistrement du vol
* Date de réalisation
* Heure
* pilote
* Type et immatriculation de l'avion


# Information de configuration
* nom_du_contact_ulm
* tel_du_contact_ulm
* email_du_contact_ulm
* durée de validité
  
# Mysql


```sql
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
```