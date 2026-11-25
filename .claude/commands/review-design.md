---
description: Revue de conception produit. Le produit est-il cohérent et naturel à utiliser ? Produit des fiches pour les sections Interface et parcours / Par fonctionnalité de doc/suggestions.md, enregistrées après accord.
argument-hint: "[portée : fonctionnalité ou parcours]"
disable-model-invocation: true
---

# Command: review-design

Revue de conception produit. Portée : $ARGUMENTS (rien : parcours principaux)

## 1. Sources
- PRD de la portée (`doc/prds/`), guide utilisateur (`doc/users/fr/`), prototypes
  (`doc/prototypes/`).
- Textes de l'interface : `application/language/french/` (et cohérence avec english/dutch).
- L'application sur http://gvv.net/ (jamais localhost), avec les utilisateurs de test de
  `bin/create_test_users.sh`, via un navigateur (Playwright MCP) : captures et mesures.
  Pour changer d'utilisateur, se déconnecter d'abord (`/auth/logout`).
- `doc/suggestions.md` (sections 4 et 5) pour ne pas reproposer.

Ne rien modifier dans l'application : seulement naviguer et consulter. Ne pas créer ni
supprimer de données (la base de gvv.net est une copie de production).

## 2. Vérifier
- Cohérence des écrans : vocabulaire, boutons, filtres, navigation, menus.
- Parcours principaux de bout en bout, selon les rôles (membre, instructeur, trésorier,
  administrateur).
- Accessibilité : contraste calculé, clavier, affichage mobile.
- États vides et erreurs : aucune action rejetée en silence, résultat toujours visible
  (règle GUI de `CLAUDE.md`).
- Cohérence entre la documentation utilisateur et le produit.

## 3. Se demander
- L'utilisateur doit-il aller et venir entre deux écrans ou deux supports ?
- Une étape du parcours n'a-t-elle aucune raison d'être ?

## 4. Produire
- Les parcours testés, les frictions classées par gravité, des fiches complètes
  (constat, proposition, bénéfices, coûts, rapport, premier pas), appuyées sur des
  captures plutôt que sur la seule lecture.
- Demander lesquelles enregistrer dans `doc/suggestions.md`. N'écrire qu'après accord.
  Ne rien implémenter.
