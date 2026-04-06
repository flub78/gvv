# PRD : Page Publique d'Achat de Vol de Découverte

**Produit :** GVV (Gestion Vol à Voile)
**Fonctionnalité :** Page publique de commande et paiement de vols de découverte
**Version :** 1.0
**Statut :** Proposition
**Créé :** 2026-04-05

---

## 1. Résumé Exécutif

Ce PRD décrit les exigences pour une page publique permettant à toute personne extérieure au club d'acheter un bon de vol de découverte en ligne, sans compte GVV, via HelloAsso. À la confirmation du paiement, GVV crée automatiquement le bon de découverte et envoie un email de confirmation à l'acheteur avec copie à l'aéroclub.

Cette fonctionnalité permet aux aéroclubs de proposer leurs vols de découverte sur leur site internet ou via un QR Code, sans intervention manuelle du personnel.

---

## 2. Contexte

### 2.1 Système existant

GVV gère les bons de vol de découverte (`vols_decouverte`) avec les champs suivants : bénéficiaire, de la part de, occasion, email, téléphone, contact urgence, produit, nombre de personnes, prix.

Les produits VD sont définis dans la table `tarifs` avec `type_ticket = 1`, rattachés à une section (`club`). Chaque section peut activer les paiements HelloAsso pour les VD via le flag `has_vd_par_cb`. La configuration HelloAsso (client_id, secret, slug) est stockée dans `paiements_en_ligne_config`.

Un flux de paiement HelloAsso existe déjà pour les membres connectés (`vols_decouverte/create`). La page publique réutilise le même mécanisme de paiement côté serveur, sans authentification préalable.

### 2.2 Motivation

Les aéroclubs souhaitent vendre des bons de découverte en dehors des heures d'ouverture, via leur site web ou des supports physiques (affiches, cartes de visite avec QR Code), sans mobiliser de personnel.

---

## 3. Périmètre

### 3.1 Inclus

- Page publique accessible sans authentification
- Sélection de la section puis du produit VD
- Saisie des informations du bon (bénéficiaire, acheteur, urgence, poids)
- Paiement via HelloAsso
- Création automatique du bon à réception du webhook de confirmation
- Envoi email de confirmation à l'acheteur avec copie à l'aéroclub
- Texte d'accueil configurable par section (Markdown)
- Paramètre URL pour forcer une section
- Bouton d'envoi du lien par email depuis l'interface gestionnaire
- Route QR Code vers la page publique
- Protection contre les abus (rate limiting, validation stricte)
- Quota mensuel de vols vendus par section (fenêtre glissante 30 jours)

### 3.2 Exclus

- Gestion d'un compte utilisateur pour l'acheteur
- Paiement par un autre moyen qu'HelloAsso
- Modification ou annulation du bon après achat
- Remboursement en ligne
- Envoi de rappels automatiques

---

## 4. Utilisateurs Cibles

| Acteur | Description |
|--------|-------------|
| Acheteur public | Toute personne souhaitant offrir ou acheter un vol de découverte, sans compte GVV |
| Gestionnaire VD | Membre du club ayant accès à `vols_decouverte` — partage le lien ou le QR Code |
| Aéroclub | Reçoit la copie email et voit le bon créé dans GVV |

---

## 5. Exigences Fonctionnelles

### 5.1 Accès et URL

**EF-01** La page est accessible sans authentification à l'URL `/index.php/vols_decouverte/public`.

**EF-02** Un paramètre `section` (identifiant numérique) permet de pré-sélectionner une section et masquer le sélecteur de catégorie. Exemple : `/index.php/vols_decouverte/public?section=3`.

**EF-03** Si la section forcée n'a pas `has_vd_par_cb = 1` ou n'a aucun produit VD actif, la page affiche un message d'erreur explicite et ne montre pas de formulaire.

### 5.2 Sélection de la catégorie

**EF-04** Quand aucune section n'est forcée, la page affiche une carte par section ayant `has_vd_par_cb = 1` et au moins un produit VD actif à la date courante dans `tarifs` (`type_ticket = 1`).

**EF-05** Les cartes sont regroupées par type d'aéronef (`Avion`, `Planeur`, `ULM`) selon l'acronyme ou la configuration de la section.

**EF-06** La sélection d'une carte met à jour le sélecteur de produit sans recharger la page.

### 5.3 Sélection du produit

**EF-07** Le sélecteur de produit affiche les tarifs VD (`type_ticket = 1`) de la section sélectionnée, actifs à la date du jour (`date <= today <= date_fin`).

**EF-08** Chaque option affiche la description, le prix et le nombre maximum de passagers du produit.

**EF-09** Lorsque l'utilisateur change de section, les données saisies dans le formulaire sont conservées et seul le sélecteur de produit est mis à jour.

### 5.4 Formulaire de saisie

**EF-10** Le formulaire comprend les champs suivants :

| Champ | Obligatoire | Description |
|-------|-------------|-------------|
| Nom et prénom du bénéficiaire | Oui | Destinataire du vol |
| De la part de | Non | Offrant |
| Occasion | Non | Motif du cadeau |
| Email de l'acheteur | Oui | Pour envoyer le bon et les confirmations |
| Téléphone de l'acheteur | Oui | Pour organiser le vol et prévenir en cas de météo défavorable |
| Nom et téléphone contact urgence | Non | Personne à prévenir en cas d'accident |
| Poids cumulé des passagers (kg) | Non | Information opérationnelle pour la préparation du vol |
| Nombre de passagers | Oui si produit multi-places | Limité au maximum configuré sur le produit |

**EF-11** La validation côté serveur vérifie la présence des champs obligatoires, la validité du format email, que le produit sélectionné existe et appartient à la section, et que le nombre de passagers saisi ne dépasse pas la capacité maximale du produit.

**EF-11b** Lorsque l'utilisateur change de produit, le champ « nombre de passagers » est mis à jour dynamiquement pour refléter la capacité maximale du nouveau produit sélectionné (maximum affiché, saisie bloquée au-delà).

**EF-12** En cas d'erreur de validation, le formulaire est réaffiché avec les données saisies et un message d'erreur explicite pour chaque champ invalide.

### 5.5 Paiement HelloAsso

**EF-13** Le bouton de paiement initie une session de checkout HelloAsso pour le montant du produit sélectionné, identique au flux existant dans `vols_decouverte/create` pour les membres connectés.

**EF-14** Les informations du bon (bénéficiaire, produit, acheteur, section) sont stockées temporairement entre la soumission du formulaire et la confirmation du paiement, associées à une référence de transaction unique.

**EF-15** L'acheteur est redirigé vers la page HelloAsso pour le paiement, puis renvoyé vers une page de confirmation GVV après paiement.

### 5.6 Traitement du webhook et création du bon

**EF-16** À réception du webhook HelloAsso confirmant le paiement, GVV crée automatiquement l'entrée `vols_decouverte` avec les données du formulaire et le statut de paiement correspondant.

**EF-17** Le bon est associé à la section (champ `club`) et au produit (`product`, `prix`). Le champ `saisie_par` est renseigné avec une valeur conventionnelle identifiant l'origine publique (ex. `'public'`).

**EF-18** Un email de confirmation est envoyé à l'adresse fournie par l'acheteur, contenant les informations du bon (bénéficiaire, produit, prix, numéro de référence).

**EF-19** Une copie de l'email de confirmation est envoyée à l'adresse email de contact de l'aéroclub configurée dans GVV.

### 5.7 Texte d'accueil configurable

**EF-20** Un texte d'accueil est affiché en haut de la page, avant le formulaire. Ce texte est configurable par section dans l'interface d'administration (`paiements_en_ligne/admin_config`), stocké en Markdown dans `paiements_en_ligne_config` sous la clé `vd_accueil_text`.

**EF-21** Si aucun texte n'est configuré pour la section, un texte par défaut est affiché.

**EF-22** Le rendu Markdown est effectué côté serveur. Le HTML produit est échappé pour prévenir les injections XSS.

### 5.8 Partage du lien par email

**EF-23** Dans la liste `vols_decouverte/page`, un bouton « Partager la page publique » est accessible aux gestionnaires VD.

**EF-24** Ce bouton ouvre un dialogue permettant de saisir une adresse email et d'envoyer le lien vers la page publique de la section active.

**EF-25** L'email envoyé contient le lien complet avec le paramètre `section` de la section active.

### 5.9 QR Code

**EF-26** La route `/index.php/vols_decouverte/qrcode/{section_id}` génère et retourne un QR Code (image PNG) encodant l'URL de la page publique avec le paramètre `section` correspondant.

**EF-27** Le QR Code est généré avec la bibliothèque `phpqrcode` déjà présente dans `application/third_party/`.

### 5.10 Quota mensuel par section

**EF-28** Chaque section dispose d'un quota mensuel configurable : le nombre maximum de bons de vol de découverte pouvant être vendus sur une fenêtre glissante de 30 jours. Le décompte porte sur tous les bons non annulés (`cancelled = 0`) de la table `vols_decouverte` dont la `date_vente` est dans les 30 derniers jours, quelle que soit l'origine (public ou interne).

**EF-29** Lorsqu'une section a atteint son quota, la page publique n'affiche pas le formulaire pour cette section. Elle affiche à la place un message informatif indiquant :
- Que le quota de vols de découverte pour cette section est atteint pour la période en cours
- Dans combien de jours le quota sera réarmé (calculé comme : 30 − âge en jours du plus ancien bon vendu dans la fenêtre glissante)
- La liste des autres sections encore disponibles (quota non atteint ou sans quota), avec un lien direct vers chacune

**EF-30** Dans le sélecteur de section (cartes), une section ayant atteint son quota est affichée avec un indicateur visuel « Complet » et la date de réarmement, mais reste visible pour informer l'utilisateur.

**EF-31** Un quota à `0` ou absent de la configuration signifie aucune limite pour la section.

**EF-32** La vérification du quota est effectuée côté serveur à la fois au chargement de la page (GET) et à la soumission du formulaire (POST), afin de prévenir les conditions de course lors de soumissions simultanées.

---

## 6. Exigences de Sécurité

**ES-01** Tous les champs du formulaire public sont assainis côté serveur avant toute utilisation (OWASP Top 10 : injection, XSS). Aucune donnée soumise n'est affichée sans échappement HTML.

**ES-02** Le token de session temporaire associant les données du formulaire à la transaction HelloAsso est généré cryptographiquement (non-devinable), stocké côté serveur, et expiré après 2 heures ou après utilisation.

**ES-03** Un mécanisme de rate limiting est appliqué sur les soumissions du formulaire public, par adresse IP : maximum 10 tentatives par heure. Les tentatives excédentaires reçoivent un message d'erreur HTTP 429.

**ES-04** Le webhook HelloAsso est authentifié par signature HMAC (mécanisme existant dans `paiements_en_ligne`). Les webhooks non authentifiés sont rejetés silencieusement.

**ES-05** Le paramètre `section` de l'URL est validé contre la liste des sections autorisées (`has_vd_par_cb = 1`) avant tout traitement.

---

## 7. Exigences Non-Fonctionnelles

**ENF-01** La page publique est fonctionnelle sans JavaScript pour la saisie du formulaire. La mise à jour dynamique du sélecteur de produit lors du changement de section peut utiliser JavaScript, avec un rechargement de page en fallback.

**ENF-02** La page respecte le style Bootstrap 5 du reste de l'application et s'inspire visuellement de la page de création interne des vols de découverte.

**ENF-03** La page est utilisable sur mobile (responsive).

**ENF-04** Les textes de l'interface sont définis dans les fichiers de langue (`french`, `english`, `dutch`).

---

## 8. Exigences de Configuration

**EC-01** La fonctionnalité est activée par section via le flag existant `has_vd_par_cb` dans `sections`.

**EC-02** Le texte d'accueil Markdown par section est configurable depuis `paiements_en_ligne/admin_config`, clé `vd_accueil_text`.

**EC-03** L'adresse email de copie pour les confirmations correspond à l'email de contact de l'aéroclub existant dans la configuration GVV.

**EC-04** Chaque produit VD (`tarifs`, `type_ticket = 1`) dispose d'un champ configurable `nb_personnes_max` indiquant le nombre maximum de passagers autorisé pour ce produit. Une valeur de 1 indique un vol solo (un seul bénéficiaire), une valeur supérieure indique un vol multi-places. Ce champ est modifiable depuis l'interface de gestion des tarifs. La valeur par défaut est 1.

**EC-05** Le quota mensuel de chaque section est configurable depuis `paiements_en_ligne/admin_config`, stocké dans `paiements_en_ligne_config` sous la clé `vd_quota_mensuel` (entier, 0 = illimité). La valeur par défaut est 0 (pas de limite).

---

## 9. Critères d'Acceptation

| ID | Critère |
|----|---------|
| CA-01 | Un visiteur non connecté peut accéder à la page publique et voir les sections disponibles |
| CA-02 | La sélection d'une section met à jour le sélecteur de produit |
| CA-03 | Un formulaire incomplet (champ obligatoire vide) affiche une erreur et conserve les données saisies |
| CA-04 | Un paiement confirmé par HelloAsso crée un bon dans `vols_decouverte` avec les bonnes données |
| CA-05 | L'acheteur reçoit un email de confirmation après paiement |
| CA-06 | L'aéroclub reçoit une copie de l'email de confirmation |
| CA-07 | Un paramètre `section` valide masque le sélecteur de catégorie |
| CA-08 | Un paramètre `section` invalide ou sans produit affiche un message d'erreur |
| CA-09 | Plus de 10 soumissions par heure depuis la même IP sont rejetées |
| CA-10 | Un gestionnaire peut envoyer le lien par email depuis la liste des bons |
| CA-11 | La route QR Code retourne une image PNG du QR Code encodant l'URL publique |
| CA-12 | Le texte d'accueil configuré en Markdown est rendu correctement sur la page |
| CA-13 | Le champ nombre de passagers reflète le `nb_personnes_max` du produit sélectionné et bloque toute valeur supérieure |
| CA-14 | La saisie d'un nombre de passagers supérieur au maximum du produit est rejetée avec un message d'erreur |
| CA-15 | Quand une section atteint son quota, le formulaire est remplacé par un message indiquant le délai de réarmement |
| CA-16 | Le message de quota atteint indique les autres sections encore disponibles avec leurs liens |
| CA-17 | La carte de la section saturée affiche l'indicateur « Complet » et la date de réarmement |
| CA-18 | Un quota à 0 désactive la limite — le formulaire est toujours accessible |
| CA-19 | Une soumission POST vers une section dont le quota vient d'être atteint est rejetée avec un message d'erreur |
