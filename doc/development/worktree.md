# Git Worktree — Synthèse

## Principe

`git worktree` permet d'avoir plusieurs répertoires de travail sur des branches différentes depuis **un seul dépôt** — sans double clone.

---

## Commandes essentielles

```bash
# Créer un worktree sur une branche existante
git worktree add ../gvv-doc main

# Créer un worktree sur une nouvelle branche
git worktree add -b fix/urgent ../gvv-hotfix main

# Lister les worktrees actifs
git worktree list

# Supprimer un worktree (après merge)
git worktree remove ../gvv-hotfix
```

---

## Structure résultante

```
gvv/              ← dépôt principal  (branche courante)
gvv-hotfix/       ← worktree         (branche main)
gvv-feature/      ← worktree         (branche feature/xxx)
```

Un seul `.git` partagé — commits et historique visibles depuis tous les worktrees immédiatement.

---

## Règle importante

**Une branche ne peut être checkoutée que dans un seul worktree à la fois.**

---

## Cas d'usage typique pour GVV

| Situation | Worktree principal | Worktree secondaire |
|---|---|---|
| Hotfix urgent pendant feature en cours | `feature/xxx` | `main` → hotfix |
| Revue d'une PR en parallèle | branche courante | branche PR |

---

## Différenciation visuelle dans VSCode

Pour éviter toute confusion entre deux fenêtres VSCode ouvertes sur des worktrees différents, personnaliser la couleur de la barre de titre par workspace.

### Méthode

Dans chaque worktree, créer ou modifier `.vscode/settings.json` :

**Worktree principal / feature** (bleu) :
```json
{
  "workbench.colorCustomizations": {
    "titleBar.activeBackground": "#1a3a5c",
    "titleBar.activeForeground": "#ffffff",
    "titleBar.inactiveBackground": "#122840"
  }
}
```

**Worktree hotfix** (rouge) :
```json
{
  "workbench.colorCustomizations": {
    "titleBar.activeBackground": "#5c1a1a",
    "titleBar.activeForeground": "#ffffff",
    "titleBar.inactiveBackground": "#401212"
  }
}
```

### Palette suggérée

| Contexte | Couleur | Hex |
|---|---|---|
| Feature en cours | Bleu | `#1a3a5c` |
| Hotfix / urgence | Rouge | `#5c1a1a` |
| Branche de review | Vert | `#1a5c2a` |
| Main / stable | Gris | `#3a3a3a` |

> ⚠️ Ajouter `.vscode/` au `.gitignore` si ces settings ne doivent pas être commités, ou les commiter pour partager la convention avec les collaborateurs.
