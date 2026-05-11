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
| `volsa` | `vapilid` | aucun conflit attendu |
| `volsp` | `vppilid` | aucun conflit attendu |
| `tickets` | `pilote` | conflit possible : même type |
| `achats` | `pilote` | aucun conflit attendu |
| `pompes` | `ppilid` | aucun conflit attendu |
| `calendar` | `mlogin` | aucun conflit attendu |
| `reservations` | `pilot_member_id`, `instructor_member_id` | deux colonnes à mettre à jour |
| `formation_seances` | `pilote_id`, `instructeur_id` | deux colonnes |
| `formation_seances_participants` | `pilote_id` | conflit UK `(seance_id, pilote_id)` — supprimer source avant UPDATE |
| `formation_inscriptions` | `pilote_id`, `instructeur_referent_id` | deux colonnes |
| `formation_autorisations_solo` | `eleve_id`, `instructeur_id` | deux colonnes |
| `acceptance_records` | `user_login`, `linked_pilot_login`, `linked_by` | trois colonnes |
| `acceptance_items` | `created_by` | aucun conflit attendu |
| `archived_documents` | `pilot_login` | aucun conflit attendu |
| `email_list_members` | `membre_id` | conflit possible : même liste |
| `comptes` | `pilote` + merge écritures | cas spécial — voir décision ci-dessus |
| `dx_auth` | désactivation seule | pas de réaffectation, signalement à l'utilisateur |

---

## Tâches à réaliser

### Lot 1 — Modèle d'analyse ✅

- [x] Créer `application/models/membres_fusion_model.php`
- [x] Méthode `analyse($source, $destination)`
- [x] Méthode `_detect_conflicts($source, $destination)`
- [x] Méthode `fusionner($source, $destination)` (transaction atomique)
- [x] Correction noms de tables : `volsa` / `volsp` (pas `vols_avion` / `vols_planeur`)

### Lot 2 — Contrôleur ✅

- [x] Créer `application/controllers/membres_fusion.php`
- [x] `_check_dev_user()`, `index()`, `preview()`, `executer()`

### Lot 3 — Vues ✅

- [x] `application/views/membres_fusion/bs_index.php`
- [x] `application/views/membres_fusion/bs_preview.php` (4 sections + confirmation)

### Lot 4 — Fichiers de langue ✅

- [x] Clés `gvv_fusion_*` FR / EN / NL
- [x] Clés dashboard FR / EN / NL

### Lot 5 — Dashboard ✅

- [x] Carte "Fusion membres" dans section "Développement & Tests"

### Lot 6 — Tests ✅

- [x] Créer `application/tests/integration/MembresFusionTest.php`
- [x] Données créées dynamiquement dans chaque test (transaction annulée en tearDown)
- [x] Test : **Invariant comptable** — nombre total d'écritures identique avant/après fusion
- [x] Test : **Conservation des soldes** — compte 411 re-pointé sans conflit de section : solde préservé
- [x] Test : **Merge comptes 411** — les deux membres ont un compte dans la même section : écritures déplacées, compte source supprimé
- [x] Test : **Exhaustivité** — aucune référence à source dans achats/calendar après fusion
- [x] Test : **Fusion fiche membre** — champs vides destination complétés, champs renseignés conservés
- [x] Test : **Atomicité** — fusion avec source inexistant retourne success=false, destination intacte
- [x] Test : **Conflits d'unicité** — `formation_seances_participants` : doublon source supprimé, destination conservée
- [x] Test : **Rapport d'analyse** — analyse() retourne les bonnes comparaisons de champs et les soldes
- [x] Test : **Accès restreint** — structure du contrôleur : `_check_dev_user()`, `show_error(403)`, `config->item('dev_users')`
- [x] Test : **membre_payeur** — propagé vers destination pour les membres tiers
- [x] Test : **Réaffectation events** — cotisations source réaffectées à destination
- [x] Test : **Dashboard** — la vue contient la carte fusion
- [x] Test de smoke Playwright : `playwright/tests/membres-fusion-smoke.spec.js` — 7 tests (6 pass, 1 skip)
