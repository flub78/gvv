# PRD — Fusion de comptes membres en doublon

## Contexte

Il arrive que plusieurs comptes soient créés pour un même adhérent (doublon de login, erreur de saisie initiale, création manuelle après import, etc.). La coexistence de deux comptes pour une même personne entraîne une dispersion de l'historique de vols, des cotisations, des achats et des soldes comptables. La fusion permet de consolider l'ensemble des données du compte source vers le compte de destination, puis de supprimer le compte source.

Cette fonctionnalité est critique : une erreur peut corrompre la comptabilité ou l'historique de vols. Elle est réservée aux utilisateurs déclarés dans `dev_users` dans la configuration.

---

## Objectifs

- Permettre à un utilisateur `dev_user` de fusionner un compte membre source dans un compte membre de destination.
- Garantir que la fusion ne modifie pas le bilan ni les comptes de résultat de l'association.
- Garantir que les soldes du compte de destination après fusion sont égaux à ses soldes avant fusion additionnés des soldes du compte source.
- Tracer l'opération pour pouvoir l'auditer.

---

## Périmètre fonctionnel

### Sélection

L'utilisateur choisit :
- Le **membre source** (qui sera supprimé après fusion).
- Le **membre de destination** (qui conserve son identité et reçoit toutes les données).

### Analyse préalable (rapport de prévisualisation)

Avant toute modification, le système affiche un rapport exhaustif composé de deux sections :

**Comparaison des profils membres** : les champs de la fiche membre (`membres`) sont affichés côte à côte (source | destination) pour chaque champ. Les champs vides chez la destination mais renseignés chez la source sont mis en évidence : ils seront copiés vers la destination. Les champs renseignés des deux côtés restent en valeur destination (aucune modification).

**Récapitulatif des données liées** :
- Pour chaque table référençant le membre source : le nombre d'enregistrements concernés et la colonne clé.
- Les soldes comptables actuels du membre source et du membre de destination.
- Les soldes prévus du membre de destination après fusion (somme des deux).
- Un avertissement explicite si le membre source possède un compte d'authentification (`dx_auth`) actif.
- Les conflits d'unicité détectés (enregistrements qui seront supprimés).

L'utilisateur doit valider explicitement avant que la fusion ne soit exécutée.

### Fusion de la fiche membre

Les champs de la table `membres` du compte source sont fusionnés dans le compte de destination selon la règle suivante :
- Si un champ est renseigné chez la source et vide chez la destination → la valeur source est copiée dans la destination.
- Si un champ est renseigné chez les deux membres → la valeur de la destination est conservée, sans modification.
- `mlogin` et les champs d'audit (`created_at`, `created_by`) de la destination sont toujours conservés.

### Réaffectation des données liées

Les enregistrements suivants doivent être réaffectés du membre source vers le membre de destination :

| Table | Colonnes concernées |
|---|---|
| `events` (vols planeur, cotisations) | `emlogin` |
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

La réaffectation est effectuée en **une seule transaction SQL atomique**. En cas d'erreur, toute la transaction est annulée.

### Gestion des conflits d'unicité

Certaines tables peuvent imposer une contrainte d'unicité sur la combinaison (membre, année) ou (membre, objet). Si le membre de destination possède déjà un enregistrement en conflit avec un enregistrement du membre source (ex : deux cotisations pour la même année), la règle est :
- Conserver l'enregistrement du membre de destination.
- Supprimer l'enregistrement en doublon du membre source (après l'avoir signalé dans le rapport de prévisualisation).

### Suppression du compte source

Après la réaffectation réussie de tous les enregistrements :
1. Le compte `membres` du membre source est supprimé.
2. Si un compte `dx_auth` existe pour le membre source, il est désactivé (non supprimé) et l'utilisateur est informé qu'une action manuelle supplémentaire est requise pour le supprimer définitivement.

### Traçabilité

La fusion est enregistrée dans un journal d'audit (table ou fichier de log applicatif) contenant :
- La date et l'heure.
- L'utilisateur `dev_user` qui a effectué l'opération.
- Le `mlogin` source et le `mlogin` destination.
- Le nombre d'enregistrements réaffectés par table.

---

## Exigences de tests

Les tests doivent vérifier :

1. **Intégrité comptable** : après fusion, le bilan et les comptes de résultat sont identiques à ceux calculés avant fusion.
2. **Conservation des soldes** : le solde comptable du membre de destination après fusion est égal à son solde avant fusion plus le solde du membre source.
3. **Exhaustivité de la réaffectation** : aucun enregistrement ne référence plus le membre source après la fusion.
4. **Atomicité** : une erreur simulée pendant la fusion annule l'ensemble de la transaction, laissant les données intactes.
5. **Gestion des doublons** : les enregistrements en conflit d'unicité sont traités conformément à la règle définie.
6. **Rapport de prévisualisation** : le rapport liste correctement tous les enregistrements concernés et les soldes prévus.
7. **Accès restreint** : un utilisateur non `dev_user` ne peut pas accéder à la fonctionnalité.

---

## Accès et navigation

- La fonctionnalité est accessible **uniquement aux utilisateurs listés dans `dev_users`** (configuration `program.php`).
- Une carte d'accès est ajoutée dans la section **"Développement & Tests"** du dashboard.
- L'URL est `membres/fusion` (ou similaire dans le contrôleur `membres`).

---

## Hors périmètre

- Fusion de plus de deux membres en une seule opération.
- Annulation (rollback) d'une fusion après validation.
- Interface de détection automatique des doublons.
