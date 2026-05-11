# Plan d'implémentation — Fusion de comptes membres en doublon

Date : 11 mai 2026
Source PRD : `doc/prds/fusion_membres_prd.md`

---

## Décisions de conception

| Point | Décision |
|---|---|
| Contrôleur | Nouveau contrôleur `application/controllers/membres_fusion.php` |
| Modèle | Nouveau modèle `application/models/membres_fusion_model.php` |
| Accès | Vérifié en tête de chaque action via `dev_users` (même pattern que `comptes::decloture()`) |
| Transaction | `$this->db->trans_start()` / `$this->db->trans_complete()` englobant toute la réaffectation |
| Comptes 411 | Si les deux membres ont un compte 411 dans la même section : les écritures du compte source sont déplacées vers le compte destination, puis le compte source est supprimé. Si seul le source a un compte 411 : `comptes.pilote` est mis à jour vers la destination. |
| `membres.membre_payeur` | Si d'autres membres ont `membre_payeur = source` : mis à jour vers destination |
| `paiements_en_ligne` | La table référence le membre via `dx_auth.username`, pas directement `mlogin`. La désactivation du compte `dx_auth` source est suffisante (pas de réaffectation directe). |
| Conflits d'unicité | Détectés lors de l'analyse préalable. Enregistrements source en conflit : supprimés dans la transaction après signalement dans le rapport. |
| Audit | Écrit dans le log applicatif CI (`log_message()`) avec détail par table. |
| Navigation | POST → page de prévisualisation (formulaire caché source/destination) → POST de confirmation → exécution → redirection avec message flash |

---

## Cartographie des réaffectations

| Table | Colonne | Cas particulier |
|---|---|---|
| `membres` | fusion des champs vides | destination conservée pour `mlogin`, `created_at`, `created_by` |
| `membres` | `membre_payeur` | UPDATE sur autres membres qui pointent vers source |
| `events` | `emlogin` | conflit possible : deux cotisations même année |
| `vols_avion` | `vapilid` | aucun conflit attendu |
| `vols_planeur` | `vppilid` | aucun conflit attendu |
| `tickets` | `pilote` | conflit possible : même type |
| `achats` | `pilote` | aucun conflit attendu |
| `pompes` | `ppilid` | aucun conflit attendu |
| `calendar` | `mlogin` | aucun conflit attendu |
| `reservations` | `pilot_member_id`, `instructor_member_id` | deux colonnes à mettre à jour |
| `formation_seances` | `pilote_id`, `instructeur_id` | deux colonnes |
| `formation_seances_theoriques_participations` | `pilote_id` | conflit possible : même séance |
| `formation_autorisations_solo` | `eleve_id`, `instructeur_id` | deux colonnes |
| `acceptance_records` | `user_login`, `linked_pilot_login` | deux colonnes |
| `acceptance_items` | `created_by` | aucun conflit attendu |
| `archived_documents` | `pilot_login` | aucun conflit attendu |
| `email_list_members` | `membre_id` | conflit possible : même liste |
| `comptes` | `pilote` + merge écritures | cas spécial — voir décision ci-dessus |
| `dx_auth` | désactivation seule | pas de réaffectation, signalement à l'utilisateur |

---

## Tâches à réaliser

### Lot 1 — Modèle d'analyse

- [ ] Créer `application/models/membres_fusion_model.php`
- [ ] Méthode `analyse($source, $destination)` : retourne un rapport structuré contenant :
  - Données complètes des deux fiches membres (comparaison champ par champ)
  - Pour chaque table de la cartographie : `['table', 'colonne', 'count', 'conflicts']`
  - Solde 411 du membre source (`comptes_model::solde_pilote`)
  - Solde 411 du membre destination avant fusion
  - Solde prévu après fusion (somme)
  - Existence d'un compte `dx_auth` pour le membre source
- [ ] Méthode `get_conflicts($source, $destination)` : détecte les doublons sur les tables à contrainte d'unicité (cotisations même année, même liste mail, même séance théorique, même type de ticket)
- [ ] Méthode `fusionner($source, $destination)` : exécute la fusion dans une transaction atomique
  - Fusion des champs `membres` (source → destination si vide)
  - Mise à jour `membres.membre_payeur` pour les membres tiers pointant vers source
  - UPDATE sur chaque table de la cartographie
  - Suppression des enregistrements source en conflit (avant UPDATE)
  - Gestion des comptes 411 : merge écritures si les deux ont un compte, sinon UPDATE `comptes.pilote`
  - Suppression de la fiche `membres` source
  - Désactivation du compte `dx_auth` source si présent (`active = 0`)
  - Log de l'opération (`log_message`)

### Lot 2 — Contrôleur

- [ ] Créer `application/controllers/membres_fusion.php`
- [ ] Méthode privée `_check_dev_user()` : vérifie que l'utilisateur est dans `dev_users`, sinon `show_error(403)`
- [ ] Action `index()` : affiche le formulaire de sélection des deux membres (GET)
- [ ] Action `preview()` : reçoit source et destination (POST), appelle `analyse()`, affiche la page de prévisualisation
  - Validation : source ≠ destination, les deux membres existent
  - Passe le rapport à la vue, formulaire caché avec source/destination pour confirmation
- [ ] Action `executer()` : reçoit source et destination (POST), appelle `fusionner()`, redirige avec message flash (succès ou erreur)

### Lot 3 — Vues

- [ ] Créer `application/views/membres_fusion/bs_index.php` : formulaire de sélection source/destination (deux `<select>` membres)
- [ ] Créer `application/views/membres_fusion/bs_preview.php`
  - Section 1 : tableau côte à côte des champs de la fiche membre (source | destination), champs qui seront copiés mis en évidence (badge « sera copié »)
  - Section 2 : tableau des données liées (table, colonne, nombre d'enregistrements affectés)
  - Section 3 : conflits détectés (enregistrements qui seront supprimés)
  - Section 4 : récapitulatif financier (solde source, solde destination avant, solde destination après)
  - Avertissement `dx_auth` si compte actif côté source
  - Bouton de confirmation (formulaire POST vers `executer`) + bouton Annuler

### Lot 4 — Fichiers de langue

- [ ] Ajouter les clés `gvv_fusion_*` dans `application/language/french/gvv_lang.php`
- [ ] Ajouter les clés correspondantes dans `english/gvv_lang.php` et `dutch/gvv_lang.php`

### Lot 5 — Dashboard

- [ ] Ajouter une carte dans la section "Développement & Tests" de `application/views/bs_dashboard.php` (vers `membres_fusion/index`)
- [ ] Ajouter les clés de langue pour le titre et la description de la carte

### Lot 6 — Tests

- [ ] Créer `application/tests/integration/MembresFusionTest.php`
- [ ] Jeu de données de test : deux membres src/dst avec vols, cotisations, tickets, comptes 411 avec écritures
- [ ] Test : **Invariant comptable** — bilan et comptes de résultat identiques avant/après fusion
- [ ] Test : **Conservation des soldes** — solde destination après = solde destination avant + solde source
- [ ] Test : **Exhaustivité** — aucune référence à `src` dans les tables de la cartographie après fusion
- [ ] Test : **Fusion fiche membre** — champs vides destination complétés, champs renseignés conservés
- [ ] Test : **Atomicité** — simulation d'erreur en cours de transaction, vérification rollback complet
- [ ] Test : **Conflits d'unicité** — cotisation en doublon : enregistrement source supprimé, destination conservé
- [ ] Test : **Merge comptes 411** — les deux membres ont un compte, les écritures sont déplacées, le compte source est supprimé
- [ ] Test : **Rapport d'analyse** — les comptages par table et les soldes prévus sont corrects
- [ ] Test : **Accès restreint** — utilisateur non `dev_user` reçoit 403
- [ ] Test de smoke Playwright : accéder à `membres_fusion`, sélectionner source/destination, vérifier affichage de la prévisualisation
