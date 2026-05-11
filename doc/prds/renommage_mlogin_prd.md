# PRD — Renommage de l'identifiant membre (mlogin)

## Contexte

Certains comptes membres ont été créés avec des identifiants (`mlogin`) incorrects : identifiants purement numériques, fautes d'orthographe, formats non conformes à la convention du club. Le `mlogin` est la clé primaire de la table `membres` et est référencé comme clé étrangère dans de nombreuses tables de l'application. Le renommage doit propager le changement de manière atomique dans toute la base de données.

Cette fonctionnalité est critique : une propagation partielle laisserait la base dans un état incohérent. Elle est réservée aux utilisateurs déclarés dans `dev_users` dans la configuration.

---

## Objectifs

- Permettre à un utilisateur `dev_user` de modifier le `mlogin` d'un membre existant.
- Propager le changement dans toutes les tables référençant cet identifiant.
- Garantir l'atomicité : soit tous les enregistrements sont mis à jour, soit aucun.
- Ne modifier aucune donnée métier (vols, soldes, cotisations) — seule la valeur de la clé change.

---

## Périmètre fonctionnel

### Sélection et saisie

L'utilisateur choisit :
- Le **membre à renommer** (sélection depuis la liste des membres).
- Le **nouvel identifiant** (`mlogin` cible).

Le nouvel identifiant doit être validé avant soumission :
- Non vide.
- Ne contient que des caractères alphanumériques, tirets et underscores.
- N'existe pas déjà dans la table `membres` ni dans la table `dx_auth` des utilisateurs.

### Analyse préalable (rapport de prévisualisation)

Avant toute modification, le système affiche un rapport complet de tout ce qui sera modifié :
- L'identifiant actuel et le nouvel identifiant, mis en regard.
- La fiche membre complète avec les champs qui changeront (uniquement `mlogin` et les champs qui en dépendent).
- Pour chaque table concernée : le nombre d'enregistrements qui seront mis à jour, avec la colonne impactée.
- Si un compte d'authentification `dx_auth` existe : affichage explicite de l'ancien et du nouveau `username`.
- Le détail des enregistrements les plus significatifs (vols, cotisations, tickets) avec l'ancien et le nouveau login visible, pour permettre à l'utilisateur de vérifier que la bonne personne est ciblée.

L'utilisateur doit confirmer explicitement avant que la modification ne soit appliquée.

### Propagation du renommage

Le renommage met à jour la valeur de la clé dans toutes les tables référençant le `mlogin` :

| Table | Colonnes concernées |
|---|---|
| `membres` | `mlogin` (clé primaire) |
| `events` | `emlogin` |
| `vols_avion` | `vapilid` |
| `vols_planeur` | `vppilid` |
| `tickets` | `pilote` |
| `achats` | `pilote` |
| `pompes` | `ppilid` |
| `calendar` | `mlogin` |
| `reservations` | `pilot_member_id`, `instructor_member_id` |
| `formation_seances` | `pilote_id`, `instructeur_id` |
| `formation_autorisations_solo` | `eleve_id`, `instructeur_id` |
| `formation_seances_theoriques` | colonne membre |
| `acceptance_records` | `user_login`, `linked_pilot_login` |
| `acceptance_items` | `created_by` |
| `archived_documents` | colonne membre |
| `email_list_members` | `membre_id` |
| `paiements_en_ligne` (transactions) | colonne login membre |
| `dx_auth` (users) | `username` |

La mise à jour est effectuée en **une seule transaction SQL atomique**. En cas d'erreur, toute la transaction est annulée.

### Traçabilité

L'opération est enregistrée dans le journal d'audit contenant :
- La date et l'heure.
- L'utilisateur `dev_user` qui a effectué le renommage.
- L'ancien `mlogin` et le nouveau `mlogin`.
- Le nombre d'enregistrements mis à jour par table.

---

## Exigences de tests

Les tests doivent vérifier :

1. **Exhaustivité** : après renommage, aucun enregistrement dans aucune table ne référence l'ancien `mlogin`.
2. **Atomicité** : une erreur simulée pendant la propagation annule l'ensemble de la transaction, laissant les données intactes avec l'ancien identifiant.
3. **Validation de l'identifiant cible** : un identifiant déjà existant, vide ou contenant des caractères invalides est rejeté avec un message d'erreur clair.
4. **Invariance des données métier** : les vols, soldes, cotisations et tous les enregistrements liés conservent leurs valeurs — seule la valeur de la clé change.
5. **Compte dx_auth** : si un compte d'authentification existe, son `username` est également mis à jour.
6. **Accès restreint** : un utilisateur non `dev_user` ne peut pas accéder à la fonctionnalité.

---

## Accès et navigation

- La fonctionnalité est accessible **uniquement aux utilisateurs listés dans `dev_users`** (configuration `program.php`).
- Une carte d'accès est ajoutée dans la section **"Développement & Tests"** du dashboard.
- L'URL est `membres/renommer` (ou similaire dans le contrôleur `membres`).

---

## Hors périmètre

- Renommage en lot (plusieurs membres en une opération).
- Modification d'autres champs d'identification (email, numéro de membre) — couverts par l'édition normale de la fiche membre.
- Annulation (rollback) d'un renommage après validation.
