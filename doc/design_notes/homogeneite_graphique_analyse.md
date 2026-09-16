# Homogénéité graphique de GVV — analyse et plan d'unification

Date : 2026-09-14
Statut : analyse, aucune modification de code effectuée. À valider avant implémentation.

## Méthodologie

Revue des vues (`application/views/**/*.php`) et du moteur de rendu
`application/libraries/Gvvmetadata.php` / `MetaData.php`, sur quatre axes :

1. Boutons d'action (éditer / supprimer / voir / créer, colonne actions des tableaux)
2. Bandeaux de filtre des listes
3. Messages de validation / flash et confirmations de suppression
4. Mise en page générale des listes/tableaux (titre, classes de tableau, pagination)

Le moteur `Gvvmetadata`/`MetaData.php` génère déjà un style cohérent pour les
~36 pages qui l'utilisent pleinement via `$this->gvvmetadata->table()`.
L'essentiel des incohérences vient des vues qui recodent leur propre HTML au
lieu de passer par le moteur, et du décalage entre deux « générations » de
modules : l'ancien fonds de l'appli (vols, membres, comptabilité) et les
modules récents (`formation_seances*`, `maintenance_operations`,
`procedures`, `forms_admin`).

---

## 1. Colonne actions (éditer / supprimer / voir)

### Styles identifiés

| Style | Description | Exemples (fichier:ligne) |
|---|---|---|
| **A — canonique** | `btn btn-sm btn-primary`+`fa-edit` (éditer), `btn btn-sm btn-danger`+`fa-trash` (supprimer, avec `confirm()`), `btn btn-sm btn-success`+`fa-plus` (créer). Généré par le moteur. | `application/libraries/MetaData.php:1324-1444` — utilisé par ~36 vues (`vols_planeur`, `avion`, `membre`, journaux, etc.) |
| **B — recodé, couleurs différentes** | Même vocabulaire visuel mais convention primary=voir / warning=éditer / danger=supprimer | `formation_autorisations_solo/index.php:141-151`, `email_lists/index.php:89-102`, `programmes/index.php:88-121`, `maintenance_equipements/index.php:75-93` (variantes `btn-outline-*`) |
| **B — icône + texte** | Seul cas avec libellé texte à côté de l'icône (`Modifier`) | `maintenance_operations/index.php:52-56` |
| **C — modale Bootstrap** | Confirmation via une vraie modale plutôt qu'un `confirm()` JS | `procedures/bs_tableView.php:207-248`, `bs_view.php:283`, `bs_attachments.php:266` |
| **D — page de confirmation dédiée** | Navigation vers une page de confirmation au lieu d'un dialogue JS | `formation_autorisations_solo/delete.php` |
| **E — sans confirmation (anomalie)** | Action destructive sans aucun garde-fou | `membre/bs_formView.php:63-64` (suppression photo), lien de suppression de `formation_autorisations_solo/index.php:148-151` |

### Propositions

1. Remplacer les boutons d'édition/suppression de `formation_autorisations_solo/index.php`,
   `email_lists/index.php`, `programmes/index.php`, `maintenance_equipements/index.php`
   pour adopter le style de `vols_planeur/bs_tableView.php` (généré par
   `MetaData.php::action()`) : `btn-primary`/éditer, `btn-danger`+`confirm()`/supprimer,
   icône seule.
2. Retirer le libellé texte (« Modifier ») du bouton d'édition de
   `maintenance_operations/index.php` pour revenir à l'icône seule, comme
   partout ailleurs.
3. **Corriger indépendamment de l'homogénéisation** : ajouter une confirmation
   (a minima `confirm()`) sur `membre/bs_formView.php:63-64` et sur le lien de
   suppression de `formation_autorisations_solo/index.php:148-151` — action
   destructive sans garde-fou.
4. Les modales Bootstrap de `procedures/*` sont une amélioration UX par
   rapport au `confirm()` natif. Plutôt que de les aligner sur A, envisager
   dans un chantier séparé de généraliser *cette* modale à toute l'application
   plutôt que le `confirm()` natif — décision à prendre à part, hors périmètre
   d'un simple ravalement de façade.

---

## 2. Filtres

### Styles identifiés

| Style | Description | Exemples |
|---|---|---|
| **A — accordéon + helper `filter_buttons()`** (dominant, ~15 vues) | Bouton d'application `btn-secondary`/`btn-warning` libellé « Sélectionner »/« Afficher », champs date via un `datepicker` JS non natif, filtre stocké en session (POST) | `vols_planeur`, `vols_avion`, `avion`, `comptes`, `compta`, `tarifs`, `tickets`, `pompes`, `openflyers`, `rapprochements`, `vols_decouverte`, `membre`, `planeur` |
| **A′ — variantes mineures** | Même idée, id/attributs différents | `licences/bs_TablePerYear.php` (accordéon `#filtersAccordion`, toujours ouvert, titre en dur), `membre/bs_tableView.php` (id d'accordéon différent) |
| **B — carte Bootstrap 5 moderne** | `card`+`card-header` avec icône `fa-filter`, grille `row g-2 align-items-end`, champs `type="date"` natifs, `btn-sm btn-primary`+`fa-search` « Filtrer », requête en GET | `formation_seances/index.php:51-153`, `formation_seances_theoriques/index.php:39-112` |
| **C — aucun filtre** | — | `maintenance_operations/index.php`, `licences/bs_tableView.php` |

### Propositions

1. Remplacer le bandeau de filtre de `licences/bs_TablePerYear.php` pour
   adopter le style de `vols_planeur/bs_tableView.php` (accordéon +
   `filter_buttons()`) : seul écart dans la famille A, à résorber sans
   changer de paradigme.
2. Le style B (`formation_seances/index.php`) est le plus propre en
   Bootstrap 5 pur (grille, dates natives, GET). Le généraliser à la place
   de A impliquerait de réécrire ~15 vues et d'abandonner le helper
   `filter_buttons()` et le stockage en session — chantier lourd, pas un
   simple remplacement de classes CSS. Recommandation : garder A comme cible
   pragmatique à court terme, n'envisager B que si une refonte plus large des
   filtres est décidée séparément.
3. Ajouter un filtre minimal à `maintenance_operations/index.php` (style B,
   cohérent avec le reste de la page) et à `licences/bs_tableView.php`
   (style A, cohérent avec `licences/bs_TablePerYear.php` une fois aligné).

---

## 3. Messages de validation / flash

### Styles identifiés

| Style | Description | Exemples | Prévalence |
|---|---|---|---|
| **1 — alerte Bootstrap dismissible (quasi-cible)** | `alert alert-success/danger alert-dismissible fade show` + icône Bootstrap Icons + bouton de fermeture | `planeur/bs_tableView.php:43-50`, `avion/bs_tableView.php:37-51`, `membre/bs_tableView.php:38-51` | 68 fichiers |
| **2 — alerte Bootstrap non-dismissible, icônes FontAwesome** | Même classes `alert-success/danger`, sans `fade show`/bouton de fermeture | `formation_seances/index.php:37-46`, `formation_inscriptions/detail.php:71-80` | ~108 fichiers |
| **3 — legacy, non-Bootstrap** | `<div class="error">` | `compta/bs_journalCompteView.php:374-375` | 1 fichier |
| **4 — validation CodeIgniter par champ** | `is-invalid`/`invalid-feedback` | `email_lists/create.php`, `authorization/bs_role_form.php`, `procedures/bs_formView.php` | 6 fichiers |
| **5 — validation AJAX en modale** | Erreurs peuplées côté client après appel AJAX | `terrains/bs_ajax_modal.php:39-46` | 1 fichier |

**Constat d'architecture** : il n'existe aucun rendu centralisé du
flashdata — `bs_header.php`/`bs_footer.php` ne s'en chargent pas, chaque vue
recopie son propre bloc HTML (416 appels à `set_flashdata()` à travers les
contrôleurs, chacun affiché à la main). Ce n'est donc pas seulement un
problème de choix de style, mais d'architecture.

**Confirmations de suppression** — mécanisme séparé mais lié :
- Majorité : `window.confirm()`, soit inline dans les vues (46 fichiers),
  soit généré par les helpers partagés `balance_helper.php:118` et
  `form_elements_helper.php:717-735` (celui-ci traduit via
  `$this->lang->line('gvv_button_confirm')`).
- 26 fichiers utilisent une vraie modale Bootstrap (`modal fade`) à la place
  du `confirm()` natif : `compta/bs_journalView.php`, `forms_admin/*`,
  `archived_documents/bs_view.php`, `procedures/bs_view.php`,
  `reservations/bs_reservations_v6.php`, `presences/presences.php`.
- De nombreux `confirm('Êtes-vous sûr...')` sont codés en dur en français,
  sans passer par les fichiers de langue, contrairement au reste de
  l'interface.

### Propositions

1. Remplacer les alertes de `formation_seances/index.php`,
   `formation_inscriptions/detail.php`, `formation_autorisations_solo/index.php`
   (style 2) pour adopter le style de `planeur/bs_tableView.php` (style 1,
   dismissible) — simple changement de classes/icônes.
2. Remplacer le bloc `<div class="error">` de
   `compta/bs_journalCompteView.php:374-375` pour adopter le style de
   `membre/bs_tableView.php:38-51` (style 1) — écart le plus net, à corriger
   en priorité.
3. **Recommandation d'architecture** (chantier plus large, à valider
   séparément) : introduire un helper unique (ex. `render_flash()`, appelé
   depuis `bs_header.php`) pour que le style 1 devienne la seule
   implémentation, au lieu d'être copié-collé dans 68+ fichiers. Élimine
   durablement toute dérive future, contrairement à un remplacement page par
   page.
4. Remplacer les `confirm('Êtes-vous sûr...')` codés en dur (46 fichiers)
   pour adopter le style du helper centralisé déjà utilisé par les
   formulaires générés par métadonnées (`form_elements_helper.php:717`) —
   gain double : cohérence visuelle *et* traduction FR/EN/NL correcte.

---

## 4. Mise en page des listes / tableaux

### Styles identifiés

| Style | Description | Exemples |
|---|---|---|
| **A — page gvvmetadata classique** (dominant, ~34 vues) | Titre `<h3>` nu, bouton « + » recodé à la main au-dessus du tableau, classes de tableau mixtes `datatable ... table table-striped` (jamais `table-hover`/`table-responsive`), pagination gérée par le plugin jQuery DataTables (visuellement distincte de Bootstrap) | `vols_planeur/bs_tableView.php:345-349`, `membre/bs_tableView.php:158-163` |
| **B — carte moderne** | Titre + bouton « + » sur une même ligne flex, tableau dans une `card`, `table table-striped table-hover`, `<thead class="table-dark">` ; `formation_seances` est le seul cas avec `table-responsive` | `maintenance_operations/index.php`, `formation_seances/index.php` |
| **C — table nue sans chrome** | Classe `table` seule, titre en `<caption>` | `reports.php` |
| **D — pages entièrement recodées hors gvvmetadata** (~60 vues) | Aucune convention commune de titre/classes/emplacement du bouton — plus grosse source réelle de disparité | `authorization/*`, `admin/*`, `archived_documents/*`, `compta/*`, `email_lists`, etc. |

**Constat** : `MetaData.php` dispose d'un support Bootstrap natif pour le
bouton de création (`$attrs['create']`, lignes 555-565) et pour la
pagination (lignes 505-536), mais **aucune vue échantillonnée ne l'utilise**
— code mort en pratique, chaque vue réécrit son propre bouton.

### Propositions

1. Remplacer le tableau de `reports.php` (style C) pour adopter le style de
   `vols_planeur/bs_tableView.php` (style A) : ajouter `table-striped`, un
   vrai titre `<h3>`, retirer le `<caption>`.
2. Pour les ~60 vues du style D, prioriser les modules les plus visités
   (ex. `compta/*`, `email_lists`) et les aligner sur le style A (titre
   `<h3>`, bouton `+` au-dessus du tableau, classes `table table-striped`),
   standard majoritaire de l'application.
3. **Chantier plus profond** (à ne faire qu'avec accord explicite, car il
   touche `MetaData.php` et donc les 34 vues du style A) : activer
   `table-hover` et une enveloppe `table-responsive` par défaut dans
   `MetaData.php::table()`, et faire réellement servir le support natif de
   bouton de création (`$attrs['create']`) au lieu de le laisser mort — cela
   unifierait automatiquement toute la famille A sans toucher chaque vue
   individuellement.

---

## Plan de priorisation suggéré

| Priorité | Action | Ampleur | Statut |
|---|---|---|---|
| 1 (rapide, sécurité) | Ajouter confirmation manquante sur suppression photo membre et sur `formation_autorisations_solo` | 2 fichiers | ✅ Fait (2026-09-15) |
| 2 (rapide, visuel) | Aligner `compta/bs_journalCompteView.php` (suppression du bloc erreur redondant avec `checkalert()`), `reports.php` (`table-striped`), `licences/bs_TablePerYear.php` (titre de filtre traduit + `accordion-flush`) sur le style dominant | 3 fichiers | ✅ Fait (2026-09-15) |
| 3 (moyen) | Uniformiser boutons d'action de `formation_autorisations_solo`, `email_lists`, `programmes`, `maintenance_equipements`, `maintenance_operations` sur le style A | 5 fichiers | ✅ Fait (2026-09-15) |
| 4 (moyen) | Remplacer les `confirm()` codés en dur par des clés de langue FR/EN/NL dédiées (texte préservé, pas de généricisation) | 30 fichiers (18 vues, 2 JS, 12 fichiers de langue), branche `fix/homogeneisation-confirm-dialogs` | ✅ Fait (2026-09-15) |
| 5A (structurel) | Centraliser le rendu des messages flash success/error (`render_flash()` dans `form_elements_helper.php`) | 46 vues + helper, branche `refactoring/render-flash-helper` | ✅ Fait (2026-09-16) |
| 5B (structurel, à valider séparément) | Activer `table-hover`/`table-responsive` par défaut dans `MetaData.php::table()` | Touche `MetaData.php` + ~34-36 vues appelantes (effet automatique) — risque d'interaction avec DataTables à tester | À faire |
| 5C (structurel, à valider séparément) | Faire servir le bouton de création natif (`$attrs['create']`) au lieu de le laisser mort | `MetaData.php` + ~34 vues à modifier — déplace le bouton de création (aujourd'hui au-dessus du tableau) dans l'en-tête du tableau, à revalider comme choix UX avant implémentation | À faire |
| 6 (gros chantier, à décider) | Étendre le style de filtre moderne (carte + GET) au-delà de `formation_seances*` | ~15 vues, changement de paradigme (session → GET) | À faire |

Aucune modification de code n'a été effectuée — ce document est l'analyse et
le plan demandés. Selon la politique du projet, chaque item touchant plus de
5 fichiers (items 4, 5, 6) justifierait une branche/PR dédiée (`/branch`) si
l'implémentation est décidée.
