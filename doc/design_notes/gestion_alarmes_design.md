# Design Notes — Gestion Générique des Alarmes

Date : 30 mai 2026 — mis à jour le 18 octobre 2026 (Lot 1 du MVP : alarmes sur les documents archivés, catalogue, abonnements, canaux, synthèses, rôle élève ; reprend les notifications documentaires prévues au Lot 5 de l'archivage documentaire)

## Contexte et objectif

GVV dispose de plusieurs sources d'alarme non unifiées :

- **`archived_documents_model`** : alarmes documentaires basées sur `valid_until`. Statuts : active, expiring_soon, expired, missing.
- **`alarmes.php`** : contrôleur calculant les conditions d'expérience à la volée depuis le carnet de vol (heures, décollages, emport passager, médical, instructeur).
- **`licences_model`** : contrôle des pilotes ayant volé sans cotisation.

L'objectif est d'unifier ces sources sous un contrat commun, sans réécrire les implémentations existantes, et de livrer au Lot 1 :
- un catalogue d'alarmes auxquelles les membres s'abonnent,
- l'envoi des notifications sur les canaux choisis par chaque membre (email, message du jour) et d'un rappel hebdomadaire ou mensuel par SMS,
- les synthèses hebdomadaires et mensuelles,
- les pages de configuration et de consultation.

Document de référence : [gestion_alarmes_generiques_prd.md](../prds/gestion_alarmes_generiques_prd.md). Les notifications documentaires du [PRD d'archivage documentaire](../prds/archivage_documentaire_prd.md) (§10) sont réalisées par ce mécanisme.

---

## Deux familles d'alarmes

| Famille | Source | Stockage | Exemple |
|---------|--------|----------|---------|
| Alarme à date fixe | `archived_documents` | En base — champ `valid_until` | Visite médicale expire le 01/06/2026 |
| Alarme calculée | Carnet de vol, licences | Aucun — évaluée à la demande | Pilote ayant volé sans licence |

Les alarmes calculées ne sont pas stockées en base : elles reflètent l'état courant des données et deviendraient obsolètes dès qu'un vol ou une licence est enregistré. Seules leurs notifications sont historisées.

---

## Architecture

![Architecture alarmes](diagrams/gestion_alarmes_composants.png)

### AlarmItem — structure commune

Toute alarme déclenchée, quelle que soit sa source, est représentée par un `AlarmItem` :

| Champ | Type | Description |
|-------|------|-------------|
| `definition_code` | string | Alarme du catalogue qui l'a produite |
| `key` | string | Identifiant stable de l'occurrence : `document:{id}`, `missing:{type}:{login}`, `computed:{règle}:{login}` |
| `entity_type` | string | `pilot` \| `machine` \| `section` \| `club` |
| `entity_id` | string | Login pilote, immatriculation, etc. |
| `section_ids` | int[] | Sections de rattachement — vide = alarme globale |
| `label` | string | Texte descriptif de l'alarme |
| `state` | string | `upcoming` (à venir) \| `active` |
| `due_date` | date\|null | Échéance — null pour les alarmes calculées sans date fixe |
| `link` | string | Page GVV où traiter l'alarme |

### Providers

Chaque provider traduit une source existante en `AlarmItem[]`, sans modifier le code qu'il lit.

- **`DocumentAlarmProvider`** — lit `archived_documents_model` : expirés et proches de l'expiration (état `upcoming` dans le délai d'anticipation), documents obligatoires manquants. `alarm_disabled = 1` et documents non approuvés sont exclus. Sections d'un document :

  | Portée du document | Sections retenues |
  |---|---|
  | `pilot` | Sections où le pilote détient le rôle `user` (règle « actif par section ») |
  | `section`, `machine` | `archived_documents.section_id`, à défaut `document_types.section_id` |
  | `club` | Aucune — alarme globale |

- **`ComputedAlarmProvider`** (Lot 2) — règles calculées : vols sans cotisation (`licences_model::pilotes_vols_sans_cotisation()`), vols sans licence (même principe sur les licences de section). Lots suivants : extraction des règles d'`alarmes.php` (médical, expérience récente, emport passager, instructeur).

Au Lot 1, seul `DocumentAlarmProvider` est branché sur l'agrégateur ; le contrat `AlarmItem` et la colonne `source` du catalogue prévoient déjà les alarmes calculées.

### AlarmAggregator

Service central. Interroge les providers pour les alarmes actives du catalogue et renvoie les `AlarmItem` correspondants, éventuellement restreints à une section.

```php
AlarmAggregator::collect(array $definitions, ?int $section_id = null) : AlarmItem[]
```

---

## Lot 1 — Catalogue, abonnements et notifications

![Schéma des abonnements](diagrams/abonnements_alarmes.png)

### Catalogue

**`alarm_definitions`** : une ligne par alarme proposée aux membres. Elle porte le type (`personal` ou `supervision`), la source (`document`, `missing_document`, `computed`), le type de document ou la règle calculée visés, la portée (section ou globale), le délai d'anticipation et l'état actif.

- La migration crée, pour chaque type de document à expiration, une alarme personnelle et une alarme de supervision, ainsi que les alarmes personnelle et de supervision des documents manquants. Les règles calculées sont ajoutées au catalogue par la migration du Lot 2.
- La création d'un type de document à expiration crée ses deux alarmes ; sa désactivation désactive ses alarmes.
- Le délai d'anticipation est initialisé depuis `document_types.alert_days_before`.

Une alarme **personnelle** ne retient, pour un abonné, que les `AlarmItem` dont il est l'entité. Une alarme de **supervision** retient tous les `AlarmItem` que l'abonné a le droit de consulter.

### Abonnements

- **`alarm_subscriptions`** : un abonnement relie une alarme du catalogue soit à un membre (`subscriber_login`), soit à une catégorie (`role_id` de `types_roles`, éventuellement restreint à `role_section_id`). Il porte un indicateur `mandatory` et les champs d'audit ; `created_by` ≠ abonné signale un abonnement créé par un administrateur.
- **`alarm_subscription_optouts`** : retrait d'un membre d'un abonnement de catégorie non obligatoire.
- **`alarm_member_settings`** : réglages par membre, communs à toutes ses alarmes — canaux email et message du jour, rappel SMS, fréquence (`immediate`, `weekly`, `monthly`).

**Abonnements effectifs** d'un membre = abonnements individuels ∪ abonnements des catégories dont il détient le rôle, non révoqué, dans la section visée (`user_roles_per_section`) − ses retraits. Ils sont recalculés à chaque usage : un changement de rôle prend effet sans modifier les abonnements.

**Aucune configuration, aucune alarme** : sans abonnement effectif, le membre ne reçoit rien et la page « Mes alarmes » est masquée. Un réglage de canaux sans abonnement n'a aucun effet.

**Contrôle des droits** : porte toujours sur l'abonné. Appliqué à la création d'un abonnement de supervision et à nouveau à chaque calcul, pour qu'un retrait de rôle prenne effet immédiatement. Mêmes règles de consultation que la liste des documents (`club-admin` toutes sections ; `ca` et `bureau` leurs sections ; types privés réservés au bureau).

### Notifications

**`Notification_manager`** — consommateur de l'agrégateur, exécuté chaque jour :

1. collecte une fois les `AlarmItem` de toutes les alarmes actives du catalogue ;
2. pour chaque membre ayant au moins un abonnement effectif, retient les `AlarmItem` couverts par ses abonnements et autorisés par ses droits, sans doublon ;
3. selon sa fréquence :
   - `immediate` : notifie les occurrences pas encore notifiées dans leur état courant ;
   - `weekly` (le lundi) ou `monthly` (le premier lundi du mois) : envoie la synthèse de toutes ses occurrences actives et à venir ; rien si elle est vide ;
4. envoie sur ses canaux : email (`Email`) listant les occurrences (quoi, qui, échéance, action, lien), message du jour individuel (`motd_model::generate_system_message()`, `source_type = 'alarm'`, visible jusqu'à la synthèse suivante) ;
5. le jour de synthèse — lundi pour `immediate` et `weekly`, premier lundi du mois pour `monthly` — envoie un rappel SMS (`Brevo_sms_adapter`) au mobile de la fiche membre (`membres.mtelm`) si l'option est activée, le mobile renseigné et au moins une occurrence active ou à venir ; le texte se limite à un message court et au lien vers `mes_alarmes` ; désactivable globalement (`alarm_sms_enabled`).

**`notification_history`** : un enregistrement par envoi — destinataire, alarme, clé d'occurrence et état, canal, type (déclenchement ou synthèse), clé de période. Il sert à l'idempotence (une seconde exécution le même jour n'envoie rien), à l'anti-spam (un SMS par période, pas de double notification immédiate) et à la traçabilité.

**`alarm_scheduler`** — déclencheur sur le modèle de `reservation_scheduler` : `cron()` en CLI pour la tâche quotidienne, `run($secret)` par URL protégée par un secret.

### Pages

| Page | Contrôleur | Accès |
|---|---|---|
| Configuration des alarmes : cases à cocher, canaux, rappel SMS, fréquence | `alarmes_config` | Tout membre pour lui-même ; `club-admin` pour n'importe quel membre |
| Mes alarmes : occurrences actives et à venir des abonnements effectifs | `mes_alarmes` | Membre ayant au moins un abonnement effectif |
| Catalogue (activation, délai), abonnements de catégories, alarmes actives sans abonné | `alarmes_admin` | `club-admin` |

La page de configuration est une seule vue, paramétrée par le membre concerné. Pour un `club-admin`, elle affiche en plus un sélecteur de membre, positionné par défaut sur lui-même ; choisir un autre membre recharge la même vue pour ce membre, avec les mêmes règles de validation (le contrôle des droits porte sur le membre configuré).

`alarmes.php` (expérience récente) est conservé tel quel au Lot 1.

### Rôle élève

- Nouveau rôle `eleve` dans `types_roles`, attribuable par section via `user_roles_per_section` comme les autres rôles.
- Ouverture d'une formation (`formation_inscriptions`) : le rôle est accordé dans la section du programme (`formation_programmes.section_id`), ou dans toutes les sections si elle est nulle, là où il n'est pas déjà détenu.
- Clôture ou abandon : le rôle est révoqué dans chaque section concernée où le membre n'a plus de formation ouverte ou suspendue ; un programme sans section couvre toutes les sections.
- Ces automatismes n'agissent qu'à l'ouverture, à la clôture et à l'abandon : une attribution ou un retrait manuel par un administrateur (gestion des rôles existante) reste en place jusqu'au prochain de ces événements.
- Les attributions automatiques sont tracées comme les autres (`granted_by`, `revoked_at`).

### Documents obligatoires par rôle

`document_types.required_role_id` (facultatif) restreint l'obligation d'un type de document aux membres détenant ce rôle dans la section concernée ; sans valeur, l'obligation s'applique à tous les pilotes, comme aujourd'hui. La visite médicale est rendue obligatoire pour le rôle `eleve` ; la détection des documents manquants (`get_missing_documents()`) applique ce critère.

---

## Séparation des responsabilités

| Responsabilité | Composant |
|---|---|
| Alarmes documentaires (expiration, manquant) | `DocumentAlarmProvider` → `archived_documents_model` |
| Alarmes calculées — Lot 2 (licences, cotisations ; puis expérience) | `ComputedAlarmProvider` → `licences_model` (puis `vols_planeur_model`, `event_model`) |
| Agrégation | `AlarmAggregator` |
| Catalogue, abonnements, réglages, abonnements effectifs | `alarm_definitions_model`, `alarm_subscriptions_model`, `alarm_member_settings_model` |
| Destinataires, droits, fréquences, composition, envoi, historique | `Notification_manager` |
| Déclenchement | `alarm_scheduler` |
| Pages membre et administration | `alarmes_config`, `mes_alarmes`, `alarmes_admin` |
| Rôle élève | `formation_inscription_model` (ouverture, clôture, abandon) |

---

## Relation avec le code existant

- **`archived_documents_model`** : lu par `DocumentAlarmProvider` ; seule la détection des manquants prend en compte `required_role_id`.
- **`licences_model`** : lu par `ComputedAlarmProvider` à partir du Lot 2.
- **`motd_model`** : `generate_system_message()` est le point d'entrée existant des messages générés par GVV.
- **`Brevo_sms_adapter`** : réutilisé tel quel pour les SMS.
- **`alarmes.php`** : inchangé au Lot 1 ; ses règles migreront vers `ComputedAlarmProvider` dans un lot suivant.

---

## Hors périmètre

- Moteur de règles métier orienté expressions libres.
- Stockage de l'état des alarmes calculées en base.
- Refactoring d'`alarmes.php` (lot suivant).
- Escalade d'un canal vers un autre.
