# PRD — Gestion Générique des Alarmes

Date : 9 mai 2026 — mis à jour le 30 mai 2026 (portée par section)

## Contexte

GVV gère déjà des cas d'alarme sur plusieurs points :
- validité documentaire (dates d'expiration via `archived_documents`),
- conditions d'expérience récente calculées dynamiquement (page `/alarmes`),
- message du jour (MOTD) comme message global d'information (référence produit : `doc/prds/messages_du_jour_prd.md`).

Ces capacités sont utiles mais fragmentées. Deux familles d'alarmes coexistent sans mécanisme commun :

- **Alarmes à date fixe** : basées sur une échéance connue (expiration d'un document, prochaine révision planifiée). La date est stockée en base.
- **Alarmes calculées** : dérivées dynamiquement des données métier (nombre d'atterrissages dans les 90 derniers jours, heures de vol dans les 24 derniers mois). Aucune date n'est stockée — l'état résulte d'un calcul sur le carnet de vol.

Le club a besoin d'un mécanisme unique et réutilisable permettant de définir, armer, déclencher et notifier des alarmes sur ces deux familles.

### Cas d'utilisation sans document

Certaines alarmes ne peuvent pas s'appuyer sur un document sans créer un document artificiel :

| Situation | Pourquoi pas de document |
|---|---|
| Emport passager : 3 atterrissages CDB dans les 90 derniers jours | Calculé sur le carnet de vol |
| Révision avion par compteur de temps de vol | Déclencheur = seuil d'heures, pas une date |
| Pilote inactif depuis N mois | Calculé sur le dernier vol enregistré |
| Maintien de qualification remorqueur (N lancements sur 12 mois) | Calculé sur logs de vol |
| Contrôle en vol biennal si activité insuffisante | Dérivé de l'activité vol |

## Objectifs
- Fournir un mécanisme commun de gestion des alarmes, utilisable par plusieurs modules.
- Permettre d'armer des alarmes basées sur des échéances (date proche, date dépassée).
- Couvrir aussi les alarmes calculées dérivées des données de vol.
- Notifier les utilisateurs concernés avant expiration et après expiration.
- Garantir la traçabilité des déclenchements et des notifications.
- Réduire la duplication de logique d'alarme dans les modules métier.

## Non-objectifs
- Remplacer toute logique métier existante de calcul de conformité (ex. règles détaillées d'expérience récente).
- Introduire une automatisation de sanction ou de blocage opérationnel en phase 1.
- Remplacer les communications officielles hors GVV.

## Portée
### Inclus
- Alarmes liées à la validité des documents.
- Alarmes liées aux échéances de maintenance (machine, équipement ou autre entité maintenable).
- Alarmes calculées à partir des données de vol (expérience récente, inactivité, conditions d'emport).
- Configuration des règles d'alarme (seuils avant échéance, rappels, niveau de sévérité).
- Portée par section : une règle d'alarme peut être commune à toutes les sections ou restreinte à une section spécifique.
- Notification des destinataires concernés.
- Mécanisme d'abonnement aux alarmes selon rôles et préférences.
- Suivi de l'état des alarmes : armée, active, acquittée, suspendue, expirée/résolue.

### Exclu (phase 1)
- Moteur de règles métier complexe orienté expressions libres.
- Escalade multi-canal avancée (SMS/voix) obligatoire.
- Gestion de planning de maintenance complet (ce PRD ne couvre que l'alarme sur échéance).

## Personae & rôles
- Administrateur club : configure les types d'alarme pour toutes les sections, consulte et supervise l'ensemble des alarmes.
- Responsable de section : configure les types d'alarme propres à sa section, consulte les alarmes de sa section.
- Responsable opérationnel (instruction, maintenance, administratif) : consulte et traite les alarmes de son périmètre.
- Utilisateur/pilote : reçoit les alarmes personnelles pertinentes et peut les acquitter si autorisé.
- Utilisateur/pilote : peut consulter la vue consolidée des alarmes qui le concernent, non acquittées et acquittées.

## Parcours clés
1. Un administrateur active une règle d'alarme pour un type d'échéance.
2. Le système arme automatiquement les alarmes sur les objets concernés.
3. Avant la date d'échéance, l'utilisateur reçoit une notification de pré-expiration.
4. Après la date d'échéance, l'alarme passe en état expiré et une notification de dépassement est envoyée.
5. Après traitement (renouvellement, maintenance réalisée, mise à jour du dossier), l'alarme est résolue.

## Exigences fonctionnelles

1. **Typologie des alarmes**
   - Le système doit gérer plusieurs types d'alarmes réutilisables :
     - **Documentaire** : basée sur `valid_until` d'un document archivé.
     - **Maintenance** : basée sur une date ou un compteur de temps de vol.
     - **Expérience récente calculée** : dérivée dynamiquement du carnet de vol, sans stockage intermédiaire.
   - Ces types sont extensibles à d'autres catégories.

2. **Armement des alarmes**
   - Une alarme doit pouvoir être armée automatiquement à partir d'une date d'échéance connue.
   - Une alarme doit pouvoir être armée manuellement si nécessaire.
   - Les alarmes calculées sont évaluées à la demande — elles ne sont pas stockées en base.

3. **Fenêtres de déclenchement**
   - Le système doit supporter au minimum :
     - pré-expiration (N jours avant échéance),
     - expiration atteinte,
     - post-expiration (rappels tant que non résolue).

4. **Destinataires**
   - Les destinataires doivent être déterminés selon le contexte : propriétaire de l'objet, responsables de section, administrateurs.
   - Les préférences de notification par utilisateur doivent être prises en compte quand applicable.

5. **Canaux de notification**
   - Le système doit notifier au minimum via email et interface GVV.
   - Les notifications doivent être compréhensibles, actionnables et contextualisées (quoi, quand, pourquoi, action attendue).

6. **Vue consolidée des alarmes**
   - Une vue dédiée doit permettre de lister, filtrer et trier les alarmes par statut, sévérité, type et échéance.
   - Cette vue doit agréger les alarmes documentaires et les alarmes calculées.
   - La vue doit être filtrable par section ; un responsable de section ne voit que les alarmes de sa section.

7. **Portée par section**
   - Une règle d'alarme peut être définie avec une portée globale (toutes sections) ou restreinte à une section spécifique.
   - Les règles globales s'appliquent à tous les pilotes et toutes les machines, quelle que soit leur section d'appartenance.
   - Les règles spécifiques à une section ne s'appliquent qu'aux entités de cette section.
   - Cette portée est cohérente avec le modèle existant de `document_types.section_id` (null = toutes sections).

8. **Acquittement et suspension**
   - Selon droits, un utilisateur doit pouvoir acquitter une alarme.
   - Selon droits, un administrateur doit pouvoir suspendre/réactiver une alarme.

9. **Résolution**
   - Une alarme doit être marquée résolue automatiquement lorsque la condition d'alarme disparaît (ex. nouvelle date de validité, condition d'expérience atteinte), ou manuellement si justifié.

10. **Anti-spam**
    - Le système doit éviter les notifications redondantes et conserver un historique des envois.
    - Les alarmes par email doivent être configurables en termes de fréquence et contenir des synthèses de toutes les alarmes actives ou à expiration proche.

11. **Traçabilité**
    - Chaque alarme à date fixe doit conserver son historique minimal : armement, changements d'état, notifications envoyées, acquittements.
    - Les alarmes calculées ne sont pas historisées — elles reflètent l'état courant du carnet de vol.

## Exigences non fonctionnelles
- Fiabilité : aucune alarme ne doit être perdue silencieusement.
- Performance : consultation et filtrage fluides sur un volume club standard.
- Sécurité : contrôle d'accès strict selon rôles et périmètre.
- Lisibilité : état et urgence immédiatement compréhensibles.
- Auditabilité : historique consultable pour diagnostic et conformité.

## Étude MOTD (Message du jour)
### Constat
Le MOTD de configuration historique (champ `mod` dans la configuration club) est embryonnaire et obsolète.
Le référentiel produit à considérer pour MOTD est le PRD dédié : `doc/prds/messages_du_jour_prd.md`.

### Décision produit
Le mécanisme d'alarmes génériques ne doit pas s'appuyer sur MOTD comme moteur principal.

### Justification
- MOTD est global et éditorial, alors qu'une alarme est ciblée, datée, traçable et pilotée par état.
- Le MOTD de configuration legacy n'est pas conçu pour la fréquence, l'historique d'envoi, ni la résolution d'événement.
- Les alarmes ont besoin d'une logique de destinataires et d'échéances que MOTD ne couvre pas nativement.

### Utilisation autorisée de MOTD
Le futur module MOTD peut être utilisé en complément comme canal de synthèse globale (ex. message d'information club), mais pas comme stockage ni orchestration des alarmes.

## Contraintes & dépendances
- Réutiliser au maximum les mécanismes existants de notifications et d'autorisations.
- Assurer la coexistence avec les modules déjà en production (archivage documentaire, alarmes expérience).
- Prévoir une migration progressive des alarmes existantes vers le mécanisme générique.
- Les alarmes calculées réutilisent la logique existante d'`alarmes.php` sans la dupliquer.

## Mesures de succès
- Couverture : au moins 3 domaines branchés au mécanisme commun (documents, maintenance, expérience).
- Réduction de duplication : suppression des logiques d'alarme redondantes entre modules.
- Réactivité : délai moyen de prise en compte des alarmes en baisse.
- Qualité perçue : diminution des oublis d'échéance remontés par les responsables.

## Questions ouvertes
- Quelles échéances de maintenance sont prioritaires pour la phase 1 ?
- Quel niveau de granularité de sévérité est attendu (info, warning, critique) ?
- Quelle politique de rappel par défaut adopter selon les types d'alarme ?
- Faut-il exposer une API interne pour que chaque module puisse publier ses alarmes ?
- Quelles alarmes calculées sont prioritaires pour la phase 1 (emport passager, inactivité pilote, autre) ?
