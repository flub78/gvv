# PRD — Gestion Générique des Alertes

Date : 9 mai 2026

## Contexte
GVV gère déjà des cas d'alerte sur plusieurs points :
- validité documentaire (dates d'expiration),
- conditions d'expérience récente, légacy, à revoir.
- contrôles de conformité affichés via la page /alarmes, légacy, à revoir.
- message du jour (MOTD) comme message global d'information (référence produit : doc/prds/messages_du_jour_prd.md).

Ces capacités sont utiles mais fragmentées. Le club a besoin d'un mécanisme unique et réutilisable permettant de définir, armer, déclencher et notifier des alertes sur différents domaines métier.

## Objectifs
- Fournir un mécanisme commun de gestion des alertes, utilisable par plusieurs modules.
- Permettre d'armer des alertes basées sur des échéances (date proche, date dépassée).
- Notifier les utilisateurs concernés avant expiration et après expiration.
- Garantir la traçabilité des déclenchements et des notifications.
- Réduire la duplication de logique d'alerte dans les modules métier.

## Non-objectifs
- Remplacer toute logique métier existante de calcul de conformité (ex. règles détaillées d'expérience récente).
- Introduire une automatisation de sanction ou de blocage opérationnel en phase 1.
- Remplacer les communications officielles hors GVV.

## Portée
### Inclus
- Alertes liées à la validité des documents.
- Alertes liées aux échéances de maintenance (machine, équipement ou autre entité maintenable).
- Alertes liées à des échéances d'expérience récente exprimables par une date limite métier.
- Configuration des règles d'alerte (seuils avant échéance, rappels, niveau de sévérité).
- Notification des destinataires concernés.
- Mécanisme d'abonnement aux alertes selon rôles et préférences.
- Suivi de l'état des alertes : armée, active, acquittée, suspendue, expirée/résolue.

### Exclu (phase 1)
- Moteur de règles métier complexe orienté expressions libres.
- Escalade multi-canal avancée (SMS/voix) obligatoire.
- Gestion de planning de maintenance complet (ce PRD ne couvre que l'alerte sur échéance).

## Personae & rôles
- Administrateur club : configure les types d'alerte, consulte et supervise.
- Responsable opérationnel (instruction, maintenance, administratif) : consulte et traite les alertes de son périmètre.
- Utilisateur/pilote : reçoit les alertes personnelles pertinentes et peut les acquitter si autorisé.
- Utilisateur/pilote : peut consulter la vue consolidée des alertes qui le concernent, non acquittées et acquittées.

## Parcours clés
1. Un administrateur active une règle d'alerte pour un type d'échéance.
2. Le système arme automatiquement les alertes sur les objets concernés.
3. Avant la date d'échéance, l'utilisateur reçoit une notification de pré-expiration.
4. Après la date d'échéance, l'alerte passe en état expiré et une notification de dépassement est envoyée.
5. Après traitement (renouvellement, maintenance réalisée, mise à jour du dossier), l'alerte est résolue.

## Exigences fonctionnelles
1. Typologie des alertes
   - Le système doit gérer plusieurs types d'alertes réutilisables : documentaire, maintenance, expérience récente, extensibles à d'autres catégories.

2. Armement des alertes
   - Une alerte doit pouvoir être armée automatiquement à partir d'une date d'échéance connue.
   - Une alerte doit pouvoir être armée manuellement si nécessaire.

3. Fenêtres de déclenchement
   - Le système doit supporter au minimum :
     - pré-expiration (N jours avant échéance),
     - expiration atteinte,
     - post-expiration (rappels tant que non résolue).

4. Destinataires
   - Les destinataires doivent être déterminés selon le contexte : propriétaire de l'objet, responsables de section, administrateurs.
   - Les préférences de notification par utilisateur doivent être prises en compte quand applicable.

5. Canaux de notification
   - Le système doit notifier au minimum via email et interface GVV.
   - Les notifications doivent être compréhensibles, actionnables et contextualisées (quoi, quand, pourquoi, action attendue).

6. Vue consolidée des alertes
   - Une vue dédiée doit permettre de lister, filtrer et trier les alertes par statut, sévérité, type et échéance.

7. Acquittement et suspension
   - Selon droits, un utilisateur doit pouvoir acquitter une alerte.
   - Selon droits, un administrateur doit pouvoir suspendre/réactiver une alerte.

8. Résolution
   - Une alerte doit être marquée résolue automatiquement lorsque la condition d'alerte disparaît (ex. nouvelle date de validité), ou manuellement si justifié.

9. Anti-spam
   - Le système doit éviter les notifications redondantes et conserver un historique des envois.
   - Les alertes par email doivent être configurables en termes de fréquence et contenir des synthèses de toutes les alertes actives ou a expiration proche.

10. Traçabilité
   - Chaque alerte doit conserver son historique minimal : armement, changements d'état, notifications envoyées, acquittements.

## Exigences non fonctionnelles
- Fiabilité : aucune alerte ne doit être perdue silencieusement.
- Performance : consultation et filtrage fluides sur un volume club standard.
- Sécurité : contrôle d'accès strict selon rôles et périmètre.
- Lisibilité : état et urgence immédiatement compréhensibles.
- Auditabilité : historique consultable pour diagnostic et conformité.

## Étude MOTD (Message du jour)
### Constat
Le MOTD de configuration historique (champ mod dans la configuration club) est embryonnaire et obsolète.
Le référentiel produit à considérer pour MOTD est le PRD dédié : doc/prds/messages_du_jour_prd.md.

### Décision produit
Le mécanisme d'alertes génériques ne doit pas s'appuyer sur MOTD comme moteur principal.

### Justification
- MOTD est global et éditorial, alors qu'une alerte est ciblée, datée, traçable et pilotée par état.
- Le MOTD de configuration legacy n'est pas conçu pour la fréquence, l'historique d'envoi, ni la résolution d'événement.
- Les alertes ont besoin d'une logique de destinataires et d'échéances que MOTD ne couvre pas nativement.

### Utilisation autorisée de MOTD
Le futur module MOTD (tel que décrit dans doc/prds/messages_du_jour_prd.md) peut être utilisé en complément comme canal de synthèse globale (ex. message d'information club), mais pas comme stockage ni orchestration des alertes.

## Contraintes & dépendances
- Réutiliser au maximum les mécanismes existants de notifications et d'autorisations.
- Assurer la coexistence avec les modules déjà en production (archivage documentaire, alarmes expérience).
- Prévoir une migration progressive des alertes existantes vers le mécanisme générique.

## Mesures de succès
- Couverture : au moins 3 domaines branchés au mécanisme commun (documents, maintenance, expérience).
- Réduction de duplication : suppression des logiques d'alerte redondantes entre modules.
- Réactivité : délai moyen de prise en compte des alertes en baisse.
- Qualité perçue : diminution des oublis d'échéance remontés par les responsables.

## Questions ouvertes
- Quelles échéances de maintenance sont prioritaires pour la phase 1 ?
- Quel niveau de granularité de sévérité est attendu (info, warning, critique) ?
- Quelle politique de rappel par défaut adopter selon les types d'alerte ?
- Faut-il exposer une API interne pour que chaque module puisse publier ses alertes ?
