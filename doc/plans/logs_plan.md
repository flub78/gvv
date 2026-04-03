# Plan d'implémentation : Accès et visualisation des fichiers journaux

**Fonctionnalité :** Consultation, visualisation et téléchargement des fichiers de log depuis le dashboard admin  
**Statut :** Phase 1 terminée, Phase 2 à implémenter

---

## Phase 1 — Liste des fichiers (terminée)

Carte "Fichiers journaux" dans le dashboard Administration système. Datatable listant tous les fichiers de `application/logs/` avec téléchargement.

| Fichier | Statut |
|---------|--------|
| `application/controllers/admin.php` — méthodes `logs()`, `download_log()` | ✅ |
| `application/views/admin/bs_logs.php` — datatable fichiers | ✅ |
| `application/views/bs_dashboard.php` — carte Administration système | ✅ |
| Traductions FR/EN/NL (`admin_lang`, `tableaux_de_bord_lang`) | ✅ |

---

## Phase 2 — Visualiseur de log

### Périmètre

Ajouter une icône œil (`fa-eye`) dans la liste des fichiers. Un clic ouvre une page de visualisation du fichier avec parsing des entrées, filtrage, recherche et navigation.

---

## Formats de log reconnus

Deux formats coexistent dans `application/logs/` :

| Format | Exemple |
|--------|---------|
| CodeIgniter | `DEBUG - 2026-04-03 00:03:18 --> message` |
| HelloAsso | `[2026-04-02 19:05:00] [HELLOASSO] message` |

Une **entrée de log** commence à la ligne correspondant à l'un de ces patterns et s'étend jusqu'à la ligne précédant la prochaine entrée (support multiligne).

Niveaux reconnus : `DEBUG`, `INFO`, `ERROR`, `HELLOASSO` (traité comme INFO).

---

## Fichiers concernés

| Fichier | Modification |
|---------|-------------|
| `application/controllers/admin.php` | Ajouter méthode `view_log($filename)` |
| `application/views/admin/bs_logs.php` | Ajouter colonne icône œil |
| `application/views/admin/bs_view_log.php` | Nouvelle vue visualiseur |

Pas de nouvelles clés de langue — l'interface du visualiseur est en français fixe (composant technique admin uniquement).

---

## Étapes d'implémentation

### Étape 1 — Contrôleur : méthode `view_log($filename)`

Dans `admin.php` :

- Même validation sécurité que `download_log()` : admin requis, pas de path traversal
- Lit le contenu du fichier avec `file_get_contents()`
- Passe le contenu brut et le nom du fichier à la vue `admin/view_log`
- Limite : si le fichier dépasse 5 Mo, affiche un message d'avertissement et propose uniquement le téléchargement

### Étape 2 — Vue : `bs_view_log.php`

Architecture entièrement côté client (JavaScript). Le PHP fournit le contenu brut dans une variable JS, tout le reste est traité en JS.

#### Barre d'outils (en haut, position fixe)

```
[← Retour]  [nom du fichier]
[DEBUG ☑] [INFO ☑] [ERROR ☑]   |   [hh:mm] → [hh:mm]   |   [⊞ Tout développer] [⊟ Tout réduire]   |   [🔍 recherche___] [◀ Préc] [▶ Suiv] [N/M]
```

- **Filtres de niveau** : cases à cocher (pas radio) — indépendantes par niveau
- **Filtre horaire** : deux champs `time` (heure début / heure fin), format `HH:MM`
- **Tout développer / Tout réduire** : deux boutons qui développent ou réduisent toutes les entrées visibles en un clic
- **Recherche** : champ texte libre + boutons Précédent/Suivant + compteur `N sur M`

#### Zone de log

- Fenêtre scrollable (hauteur : `calc(100vh - hauteur toolbar)`)
- Chaque entrée de log = un bloc `<div class="log-entry">` cliquable
- **État réduit** : première ligne uniquement + indicateur `[+N lignes]` si multiligne
- **État développé** : toutes les lignes, avec `white-space: pre-wrap`
- Couleur de fond selon le niveau :
  - `ERROR` → rouge clair (`#fff0f0`, texte `#c00`)
  - `INFO` / `HELLOASSO` → bleu clair (`#f0f4ff`, texte `#00c`)
  - `DEBUG` → vert clair (`#f0fff0`, texte `#060`)

#### Parsing JS

```
Regex CI     : /^(DEBUG|INFO|ERROR) - (\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) --> (.*)/
Regex HELLOASSO : /^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \[HELLOASSO\] (.*)/
```

Algorithme :
1. Découper le fichier ligne par ligne
2. Détecter les lignes qui commencent une nouvelle entrée (match regex)
3. Regrouper les lignes suivantes dans l'entrée courante jusqu'à la prochaine
4. Construire un tableau d'objets `{level, timestamp, firstLine, lines[]}`

#### Filtrage (temps réel, sans rechargement)

- Par niveau : masquer/afficher les `div.log-entry` dont `data-level` ne correspond pas aux cases cochées
- Par heure : masquer les entrées dont `data-time` est hors de `[début, fin]`
- Les deux filtres sont combinés (ET logique)

#### Recherche et navigation

- À chaque frappe : parcourir les entrées **visibles**, chercher la chaîne (insensible à la casse)
- Surligher toutes les occurrences via `<mark>` dans le texte affiché
- Maintenir un tableau des occurrences (entrée + position dans le texte)
- Boutons Précédent/Suivant font défiler (`scrollIntoView`) vers l'occurrence courante
- Compteur `N sur M` mis à jour en temps réel
- Si l'entrée cible est réduite, la développer automatiquement avant de scroller

### Étape 3 — Colonne œil dans `bs_logs.php`

Ajouter une icône `fa-eye` dans la colonne Actions, à côté du bouton téléchargement :

```html
<a href="admin/view_log/{filename}" class="btn btn-sm btn-outline-secondary" title="Visualiser">
    <i class="fas fa-eye"></i>
</a>
```

---

## Sécurité

- Même validation que `download_log()` : admin, pas de path traversal
- Le contenu est affiché via `htmlspecialchars()` côté PHP avant injection dans JS
- Limite de taille (5 Mo) pour éviter de saturer le navigateur

---

## Tests

### PHPUnit

- `test_view_log_requires_admin()` : accès refusé pour non-admin
- `test_view_log_rejects_path_traversal()` : `view_log('../config/database')` → 403
- `test_view_log_returns_content()` : la méthode retourne le contenu du fichier
- `test_view_log_rejects_large_file()` : fichier > 5 Mo → message d'avertissement

### Playwright (smoke test)

- Connexion admin → liste des logs → clic icône œil sur un fichier existant
- Vérifier que la page visualiseur s'affiche avec au moins une entrée de log
- Saisir un terme dans la recherche → vérifier que le compteur affiche "1 sur N"
- Décocher "DEBUG" → vérifier que les entrées DEBUG disparaissent
- Clic sur une entrée multiligne → vérifier qu'elle se développe

---

## Suivi des tâches

| # | Tâche | Statut |
|---|-------|--------|
| 1 | Méthode `logs()` dans admin.php | ✅ |
| 2 | Méthode `download_log()` dans admin.php | ✅ |
| 3 | Vue `bs_logs.php` — datatable fichiers | ✅ |
| 4 | Carte dans dashboard Administration système | ✅ |
| 5 | Traductions (FR/EN/NL) | ✅ |
| 6 | Méthode `view_log()` dans admin.php | ✅ |
| 7 | Vue `bs_view_log.php` — visualiseur | ✅ |
| 8 | Colonne œil dans `bs_logs.php` | ✅ |
| 9 | Tests PHPUnit | ⬜ |
| 10 | Smoke test Playwright | ⬜ |
