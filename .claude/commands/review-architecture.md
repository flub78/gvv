---
description: Revue d'architecture. Le découpage tient-il face à la croissance prévue ? Produit des fiches pour la section Architecture de doc/suggestions.md, enregistrées après accord.
argument-hint: "[portée : dossier ou fonctionnalité]"
disable-model-invocation: true
---

# Command: review-architecture

Revue d'architecture. Portée : $ARGUMENTS (rien : tout le projet)

## 1. Sources
- Arborescence de `application/` (contrôleurs, modèles, bibliothèques, helpers, vues) :
  noms et taille des fichiers.
- Points d'entrée : `index.php`, `application/config/routes.php`, `application/core/`.
- Graphe des dépendances, pas le contenu : `$this->load->model/library/helper(...)`,
  `extends`, appels entre bibliothèques (obtenus par `grep`).
- Documentation d'architecture : section « High-Level Architecture » de `CLAUDE.md`,
  `doc/design_notes/`, `doc/drawbacks.md`.
- `doc/suggestions.md` (section Architecture) pour ne pas reproposer.

Ne pas faire de revue du code ligne à ligne : c'est le rôle de `/code-review`.

## 2. Vérifier
- Responsabilités des modules (contrôleur, modèle, bibliothèque, `Gvvmetadata`).
- Dépendances circulaires ou inversées, couplage fort.
- Points de configuration (fichiers de config, `parametres`, constantes) et leurs doublons.
- Logique dupliquée entre modules ou entre sections du club.
- Cohérence avec les décisions documentées.

## 3. Se demander
- Que se passe-t-il quand on ajoute un club, une section, un type d'aéronef, un rôle,
  une source de données ? Où faudrait-il toucher à plusieurs endroits ?
- Quelle partie bloque l'évolution prévue (voir `doc/drawbacks.md`, `doc/todo.md`) ?

## 4. Produire
- Un schéma court de l'existant (texte ou plantuml), les écarts avec l'architecture
  visée, des fiches complètes (constat, proposition, bénéfices, coûts, rapport, premier pas).
- Demander lesquelles enregistrer dans la section Architecture de `doc/suggestions.md`.
  N'écrire qu'après accord. Ne rien implémenter.
