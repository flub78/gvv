# PRD : Sauvegarde Hors-Site des Backups (Base de Données + Médias)

## 1. Contexte

Suite à un incident où le serveur d'exploitation a failli être perdu, il est apparu que GVV dispose déjà de sauvegardes automatiques locales de la base de données (`tools/autobackup.py`) et des fichiers média (`tools/autobackup_media.py`, voir `doc/features/Backup.md`), mais que ces sauvegardes restent stockées sur la même machine que l'application. En cas de perte totale du serveur, ces sauvegardes locales sont perdues avec lui.

Ce document définit les exigences produit pour l'ajout d'un envoi périodique de ces sauvegardes vers un espace de stockage distant (Google Drive du club), avec une politique de rétention automatique côté stockage distant, et une alerte destinée au club en cas d'échec de cet envoi — afin qu'une panne du mécanisme hors-site ne passe pas inaperçue comme cela semble avoir été le cas pour le cron de sauvegarde locale avant la présente investigation.

Ce PRD pourra donner lieu à une implémentation, ou à un guide de mise en place s'il s'avère que la fonctionnalité peut être mise en place avec des outils externe.

## 2. Objectifs

* Garantir qu'une copie récente de la base de données et des médias existe en dehors du serveur d'exploitation.
* Permettre une restauration même en cas de perte totale du serveur (matériel, hébergeur, incident de sécurité).
* Limiter l'espace de stockage distant consommé par une politique de rétention automatique.
* S'assurer qu'un échec de l'envoi hors-site soit détecté et signalé, et non découvert a posteriori lors d'un incident.

## 3. Périmètre

### 3.1 Inclus

* Envoi périodique (quotidien) des sauvegardes locales existantes vers le Google Drive du club.
* Envoi de la sauvegarde de la base de données ET de la sauvegarde des médias (les deux types existants).
* Le mécanisme d'envoi hors-site réutilise les sauvegardes locales déjà produites par les mécanismes existants ; il ne régénère pas de nouvelle sauvegarde.
* Suppression automatique, côté stockage distant, des sauvegardes de plus de trois jours.
* Notification email au club en cas d'échec de l'envoi hors-site.
* Outil utilisé : rclone.

### 3.2 Hors périmètre

* Génération des sauvegardes locales elles-mêmes (déjà couverte par `tools/autobackup.py` et `tools/autobackup_media.py`).
* Sauvegarde des fichiers de configuration du serveur (déjà documentée comme procédure manuelle dans `doc/features/Backup.md`).
* Restauration automatisée depuis le stockage distant.
* Support d'un autre fournisseur de stockage distant que Google Drive.
* Intégration à l'API Google native de GVV (`application/libraries/GoogleCal.php`) : le mécanisme hors-site reste découplé de l'application PHP.
* Alerte ou notification autre qu'email (SMS, tableau de bord, etc.).

## 4. Parties Prenantes

* Administrateur système / trésorier du club (opère et supervise les sauvegardes)
* Club (destinataire de l'alerte en cas d'échec)

## 5. User Stories

| En tant que... | Je veux... | Afin de... |
| :--- | :--- | :--- |
| Administrateur système | qu'une copie des sauvegardes soit envoyée automatiquement hors du serveur chaque jour | pouvoir restaurer les données même en cas de perte totale du serveur |
| Administrateur système | que les sauvegardes distantes de plus de trois jours soient supprimées automatiquement | ne pas avoir à gérer manuellement l'espace de stockage distant |
| Administrateur système / club | être averti par email si l'envoi hors-site échoue | pouvoir intervenir avant qu'une absence de sauvegarde distante ne devienne critique |

## 6. Exigences Fonctionnelles

### 6.1 Envoi hors-site

* EF-001 : Le système doit envoyer une copie de la dernière sauvegarde locale de la base de données vers le Google Drive du club.
* EF-002 : Le système doit envoyer une copie de la dernière sauvegarde locale des médias vers le Google Drive du club.
* EF-003 : L'envoi hors-site doit être déclenché automatiquement selon une fréquence quotidienne.
* EF-004 : L'envoi hors-site ne doit pas déclencher la génération d'une nouvelle sauvegarde locale ; il s'appuie sur les sauvegardes déjà produites par les mécanismes existants.
* EF-005 : L'outil utilisé pour l'envoi vers Google Drive est rclone.

### 6.2 Rétention

* EF-006 : Les sauvegardes présentes sur le Google Drive du club et âgées de plus de trois jours doivent être supprimées automatiquement.
* EF-007 : La politique de rétention s'applique indépendamment aux sauvegardes de base de données et aux sauvegardes de médias.

### 6.3 Alerte en cas d'échec

* EF-008 : Si l'envoi hors-site échoue (base de données, médias, ou les deux), le club doit recevoir une notification par email.
* EF-009 : La notification d'échec doit permettre d'identifier quel type de sauvegarde (base de données et/ou médias) n'a pas pu être envoyé.
* EF-010 : Une exécution réussie de l'envoi hors-site ne doit pas générer de notification.

## 7. Exigences Non Fonctionnelles

* ENF-001 : Le mécanisme d'envoi hors-site doit rester découplé du fonctionnement de l'application GVV : une panne ou une indisponibilité du mécanisme hors-site ne doit pas affecter la disponibilité de l'application ni des sauvegardes locales.
* ENF-002 : Aucun identifiant d'accès au stockage distant ne doit être stocké dans le dépôt de code source.
* ENF-003 : Le mécanisme doit rester exploitable sans compétence technique avancée pour le suivi courant (vérification qu'un envoi a eu lieu, lecture d'une alerte d'échec).

## 8. Cas Limites

* CL-001 : Aucune sauvegarde locale récente n'existe au moment de l'envoi hors-site prévu.
* CL-002 : L'envoi de la sauvegarde de base de données réussit mais celui des médias échoue (ou inversement).
* CL-003 : Le quota de stockage distant disponible est insuffisant pour accueillir la nouvelle sauvegarde.
* CL-004 : L'accès au Google Drive du club devient invalide (droits révoqués, authentification expirée).
* CL-005 : Deux exécutions quotidiennes successives échouent toutes les deux.

## 9. Critères d'Acceptation

* CA-001 : Chaque jour, une copie de la dernière sauvegarde locale de la base de données est présente sur le Google Drive du club.
* CA-002 : Chaque jour, une copie de la dernière sauvegarde locale des médias est présente sur le Google Drive du club.
* CA-003 : Les sauvegardes présentes sur le Google Drive du club depuis plus de trois jours sont supprimées automatiquement.
* CA-004 : En cas d'échec de l'envoi hors-site, une notification email est reçue par le club, indiquant le ou les types de sauvegarde concernés.
* CA-005 : Aucune notification n'est envoyée lorsque l'envoi hors-site se déroule normalement.
* CA-006 : Aucun identifiant d'accès au Google Drive n'est présent dans le dépôt de code source.

## 10. Dépendances Produit

* Ce PRD complète `doc/features/Backup.md`, qui documente les mécanismes de sauvegarde locale existants (`tools/autobackup.py`, `tools/autobackup_media.py`) sur lesquels s'appuie l'envoi hors-site.

## 11. Questions Ouvertes

* QO-001 : Quelle adresse ou liste de diffusion du club doit recevoir l'alerte d'échec ? Réponse, il faut pouvoir définir une liste d'adresse email qui recevront les alertes.
* QO-002 : Un compte Google Drive dédié doit-il être créé pour cet usage, ou le compte Google existant du club (`gvv.abbeville@gmail.com`, utilisé par l'intégration calendrier) est-il réutilisé ? on va utileser info@planeur-abbeville.fr qui identifie un compte Google.
* 
* QO-003 : Le quota de stockage du compte Google Drive cible est-il suffisant à moyen terme pour la rétention de trois jours définie (base de données + médias, plusieurs clubs) ? Ca suffira très largement : avec une rétention aussi courte, seules quelques sauvegardes quotidiennes sont conservées simultanément.
