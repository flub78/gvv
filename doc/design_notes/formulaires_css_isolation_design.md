# Design Notes — Isolation CSS entre GVV et les formulaires

Date de création : 7 août 2026

## Contexte

Le rendu d'une soumission de formulaire (`forms_admin/submission_edit`, `forms_public`) partage la même page HTML que le reste de GVV (header, menu, cartes admin) : le CSS spécifique au formulaire (`forms.global_css`, propre à chaque club/formulaire — voir [formulaires_sync_fichiers_design.md](formulaires_sync_fichiers_design.md)) est injecté dans un `<style>` du même document que le CSS global GVV (`assets/css/bs_styles.css`). Constaté sur `http://gvv.net/forms_admin/submission_edit/3/2` : pollution visuelle dans les deux sens.

**Cause A — GVV pollue le formulaire** : `assets/css/bs_styles.css` (L69-89) cible des balises brutes non scopées (`form`, `input[type="text"]`, `textarea`, `select`), qui s'appliquent donc aussi au `<form>` du module formulaires.

**Cause B — le formulaire pollue GVV** : `bs_submission_edit.php` (L36-40) injecte `global_css` tel quel dans un `<style>` du `<head>` — un `<style>` s'applique à tout le document quel que soit son emplacement dans le DOM. Seule la règle `body { }` est réécrite en `.forms-public-root { }` à l'import (forms_admin.php L2270-2272) ; tout autre sélecteur générique (`h1`, `input`, `.container`...) reste global et fuit vers le reste de la page.

Un précédent existe déjà : `Forms_renderer::build_signature_assets()` (Forms_renderer.php L1072-1077) isole le widget signature avec `.gvv-signature-widget { all: initial; ... }`, preuve que ce problème était identifié mais contourné localement, pas résolu pour l'ensemble du formulaire.

## Décisions d'architecture

| Question | Décision |
|---|---|
| Isolation GVV → formulaire | Édition ciblée de `bs_styles.css` (L69-89) : exclure `.forms-public-root` des sélecteurs génériques via `:not(.forms-public-root *)` |
| Isolation formulaire → GVV | Généraliser le rewrite déjà appliqué à `body` : préfixer **tous** les sélecteurs de `global_css` par `.forms-public-root` (ou le `scope_class` complet si `css_scope` est renseigné) |
| Où le rewrite s'exécute | Au rendu (`Forms_renderer`), pas à l'import — évite toute divergence entre CSS stocké en base/fichier et logique de scoping, pas de migration des formulaires existants nécessaire |
| Mécanisme de parsing | Mini-parseur CSS par blocs `sélecteur(s) { déclarations }`, gérant `@media`/`@font-face` (préfixer le contenu), `@keyframes` (ne pas préfixer les étapes `0%`/`from`/`to`), commentaires, sélecteurs groupés par virgule, pseudo-classes/éléments, et exception `:has(.forms-public-root)` (ne pas préfixer — voir Partie B) |
| Point d'entrée commun | Méthode centralisée (ex. `Forms_renderer::scope_css($css, $scope_class)`), réutilisée par `forms_admin` (prévisualisation, édition) et `forms_public` (rendu public), pour éviter la duplication de logique |

## Partie A — GVV n'affecte plus le formulaire

**`all: revert` écarté après test empirique.** L'idée initiale (`.forms-public-root form { all: revert; }`) a été vérifiée avec un cas reproduisant la situation réelle (règle `bs_styles.css` `form input[type="text"] { background:red }`, spécificité (0,1,2), contre la règle propre au formulaire `input[type="text"] { background:blue }`, spécificité (0,1,1), plus une règle de revert `.forms-public-root input { all:revert }`, spécificité (0,1,1)) : **la règle `bs_styles.css` continue de gagner**. Deux raisons :
1. `all: revert` doit d'abord gagner la cascade comme n'importe quelle règle ; à spécificité égale ou inférieure à la règle `bs_styles.css` visée, il perd.
2. Même s'il gagnait, `revert` annule *tout* l'étage « author » pour cette propriété sur cet élément — y compris la règle propre du formulaire, pas seulement celle de `bs_styles.css`. Il ne permet donc pas de restaurer sélectivement le style du formulaire.

**Solution retenue, elle aussi vérifiée empiriquement** : exclure `.forms-public-root` directement dans les sélecteurs de `bs_styles.css` (L69-89), via `:not(.forms-public-root *)` :

```css
form:not(.forms-public-root *) {
  background-color: #e1e4e7; border: 1px solid #7e9bae; border-radius: 4px; padding: 10px;
}
form:not(.forms-public-root *) input[type="text"],
/* ... même garde sur les autres sélecteurs de la L69-89 ... */
```

Testé avec le même scénario : le formulaire natif GVV hors `.forms-public-root` garde son style habituel (règle `bs_styles.css` toujours appliquée), et à l'intérieur de `.forms-public-root` la règle `bs_styles.css` ne matche plus du tout — la règle propre du formulaire s'applique sans concurrence, quelle que soit sa spécificité. C'est une édition chirurgicale de 5 sélecteurs dans `bs_styles.css`, sans toucher au reste du fichier ni aux formulaires GVV natifs.

## Partie B — le formulaire n'affecte plus GVV

**Confirmé empiriquement** : en rejouant le vrai `global_css` du formulaire id 3 (`attestation_de_formation_ulm`) dans une page hôte reconstituant la bannière GVV, sa règle `.header { background:#1a3a5c; color:white; padding:14px 20px 12px; }` (pensée pour son propre bandeau interne) s'applique aussi à la bannière GVV `<h1 class="header">` — collision de nom de classe, exactement le symptôme observé sur `submission_edit/3/2`. Seul `body{}` étant réécrit aujourd'hui, tout le reste du CSS est global et fuit vers la page hôte.

Le rewrite actuel (`body` → `.forms-public-root`) est donc étendu à tous les sélecteurs :

- `h1 { color:red }` → `.forms-public-root h1 { color:red }`
- `h1, .title { ... }` → `.forms-public-root h1, .forms-public-root .title { ... }`
- `body { ... }` reste réécrit en `.forms-public-root { ... }` (cas déjà géré, à conserver)
- Contenu de `@media (...) { ... }` : chaque sélecteur interne est préfixé, la règle `@media` elle-même reste inchangée
- `@keyframes nom { 0% {...} 100% {...} }` : nom de l'animation et étapes non préfixés (ce ne sont pas des sélecteurs DOM)
- Sélecteurs déjà relatifs à `:root`/`html` : convertis en `.forms-public-root` comme `body`

**Exception nécessaire — sélecteurs `:has()` visant un ancêtre, à ne pas préfixer.** Le même formulaire id 3 contient trois règles qui sortent délibérément de `.forms-public-root` via `:has()`, pour obtenir un rendu plein page à l'impression (suppression du padding du `.container` Bootstrap et de la bordure/ombre de la `.card` qui enveloppent le formulaire) :

```css
.container:has(.forms-public-root) { max-width: 100%; padding: 0; ... }
.card:has(.forms-public-root) { border: none; box-shadow: none; }
.mb-4:has(+ .card .forms-public-root) { padding-left: 18mm; }
```

Un préfixage aveugle transformerait `.card:has(.forms-public-root)` en `.forms-public-root .card:has(.forms-public-root)`, qui exige que `.card` soit un **descendant** de `.forms-public-root` — alors qu'en réalité `.card` est l'élément Bootstrap qui **contient** le formulaire (relation inverse). Vérifié : la règle ainsi préfixée ne matche plus jamais rien, ce qui casserait ce rendu plein page volontaire. Le parseur doit donc détecter les sélecteurs dont le premier compound sélecteur porte un `:has(...)` référençant `.forms-public-root` (ou le `scope_class`) et les laisser tels quels, sans préfixe — c'est le seul mécanisme légitime pour qu'un formulaire agisse intentionnellement sur son conteneur GVV (mise en page plein écran, impression), à documenter comme convention pour les auteurs de formulaires.

Ce parsing remplace les deux `preg_replace` actuels de `body` (forms_admin.php L2271-2272), déplacés de la logique d'import vers une méthode de rendu partagée.

## Ce que ça ne fait pas

- Pas d'isolation totale par iframe ou Shadow DOM : le formulaire reste rendu dans le DOM partagé de la page GVV (cohérent avec l'intégration actuelle — session, soumission, widgets dynamiques).
- Pas de modification des formulaires GVV natifs ni de leurs vues (partie A se limite à 5 sélecteurs dans `bs_styles.css`, sans toucher aux autres pages GVV).
- Pas de modification du contenu (`global_css`) des formulaires existants du module : la vérification sur le formulaire réel `attestation_de_formation_ulm` (id 3, celui de `submission_edit/3/2`) montre qu'il définit déjà ses propres règles `input[type="text"]`/`textarea` — le problème est uniquement la spécificité supérieure des règles `bs_styles.css` qui les recouvre, pas une lacune du CSS du formulaire.
- Pas de migration des formulaires déjà importés : le scoping (partie B) s'applique au rendu, pas au contenu stocké.

## Références

- [formulaires_sync_fichiers_design.md](formulaires_sync_fichiers_design.md) — stockage fichier de `global_css`, source de vérité du contenu
- `application/libraries/Forms_renderer.php:1072-1077` — précédent (`all: initial` pour le widget signature)
- `application/controllers/forms_admin.php:2268-2280` — rewrite actuel `body` → `.forms-public-root`
- `application/views/forms_admin/bs_submission_edit.php:12-40` — injection de `global_css` et construction du `scope_class`
- `assets/css/bs_styles.css:69-89` — règles génériques GVV en cause (partie A)
