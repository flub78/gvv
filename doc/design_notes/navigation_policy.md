# Politique de Navigation GVV

## Principe Fondamental

Après toute opération réussie (création, modification, suppression), l'utilisateur retourne à la **page d'origine** — c'est-à-dire la page depuis laquelle il a initié l'action, quelle qu'elle soit.

Ce principe s'applique uniformément :
- Depuis la page d'accueil (dashboard)
- Depuis un journal comptable
- Depuis une page de liste (vols, comptes, membres, etc.)
- Depuis n'importe quelle page principale

## Comportement Attendu

| Page de départ | Action | Retour attendu |
|---|---|---|
| Page d'accueil | Saisir une écriture (menu) | Page d'accueil |
| Page d'accueil | Créer un vol avion/planeur | Page d'accueil |
| Journal de compte | Modifier une écriture | Journal de compte (même position) |
| Grand journal | Créer/modifier une écriture | Grand journal (même position) |
| Liste de vols (page N) | Modifier un vol | Liste de vols (page N) |
| Liste de vols (page N) | Supprimer un vol | Liste de vols (page N) |
| Liste de membres | Modifier un membre | Liste de membres (même position) |

En cas d'**erreur de validation**, le formulaire est rechargé avec les données et les messages d'erreur (pas de redirect). La pile de retour est inchangée. Quand la validation réussit ensuite, on retourne à la page d'origine.

## Mécanisme : Pile de Retour (`return_url_stack`)

### Comment ça marche

Toute **page principale** (page où l'utilisateur consulte et décide d'agir) appelle `push_return_url()` lors de son affichage. Cela enregistre l'URL courante (avec pagination) dans une pile en session.

Après une opération réussie, `pop_return_url()` dépile la dernière URL et y redirige.

### Pages qui doivent pousser leur URL

- `welcome/index()` — Page d'accueil (dashboard)
- `welcome/compta()` — Page d'accueil du comptable
- `Gvv_Controller::page()` — Toutes les listes standard ✅ (déjà implémenté)
- `compta::page()` — Grand journal ✅ (déjà implémenté)
- `compta::journal_compte()` — Journal par compte ✅ (déjà implémenté)
- `compta::mon_compte()` — Compte personnel ✅ (déjà implémenté)

### Pages qui ne poussent PAS leur URL

- Les formulaires de création/modification
- Les pages de résultats ponctuels (exports, rapports)

## Règles d'Implémentation

### Règle 1 : Toute page principale appelle `push_return_url()`

```php
// Dans welcome.php
public function index() {
    $this->push_return_url("welcome dashboard");
    // ... reste du code
}

public function compta() {
    $this->push_return_url("welcome compta");
    // ... reste du code
}
```

### Règle 2 : Après succès, toujours utiliser `pop_return_url()`

```php
// Dans compta.php::formValidation() — bouton "Créer"
// AVANT (incorrect) :
redirect("compta/journal_compte/" . $processed_data['compte1']);

// APRÈS (correct) :
$this->pop_return_url();
```

### Règle 3 : Erreur de validation → pas de redirect

```php
// Préserve les données et affiche les erreurs
$this->form_static_element($action);
load_last_view($this->form_view, $this->data);
```

## Problèmes Actuels

| Problème | Fichier | Ligne | Impact |
|---|---|---|---|
| `welcome/index()` ne pousse pas l'URL | `welcome.php` | ~72 | Retour incorrect depuis dashboard |
| `welcome/compta()` ne pousse pas l'URL | `welcome.php` | ~305 | Retour incorrect depuis page comptable |
| Création d'écriture : redirect hardcodé vers `journal_compte` | `compta.php` | ~490 | Retour toujours vers journal, jamais vers l'origine |

## Tests

Tests Playwright de référence : `playwright/tests/navigation-policy.spec.js`

Ces tests vérifient que le retour après chaque opération correspond à la page d'origine, indépendamment du chemin d'accès utilisé.
