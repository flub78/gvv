# Code Review : Widget horamètre (vols avion)

**Date** : 2026-03-17
**Fichiers** :
- `assets/javascript/form_vols_avion.js`
- `application/views/vols_avion/bs_formView.php`

---

## Problèmes identifiés

### 🔴 Critique

_(aucun)_

---

### 🟠 Majeur

#### 1. Race condition AJAX sur changement de machine

**Fichier** : `form_vols_avion.js` ligne 215
**Description** : `update_machine()` est déclenchée à chaque changement du select `vamacid`. Si l'utilisateur change rapidement de machine plusieurs fois, plusieurs requêtes AJAX sont en vol simultanément. La dernière réponse reçue (pas nécessairement la plus récente) peut écraser l'état courant. Aucun mécanisme d'annulation (`abort()`) ni de debounce.
**Risque** : Affichage d'un horamètre appartenant à la mauvaise machine.
**Mitigation** : Annuler la requête précédente avec `xhr.abort()` ou utiliser un debounce.

#### 2. Dépendances sur des variables globales non déclarées dans le fichier

**Fichier** : `form_vols_avion.js` lignes 263–265, 281
**Description** : `hora_unit_label()` et `update_hora_format()` référencent les variables globales `hm`, `h_100` et `horametre` sans qu'elles soient définies dans ce fichier. Elles proviennent de la vue PHP (via `<script>`). Si elles manquent, le label affiche `"undefined"` sans erreur visible.
**Mitigation** : Documenter explicitement ces dépendances dans le fichier, ou les passer en paramètre.

#### 3. Construction d'URL AJAX fragile

**Fichier** : `form_vols_avion.js` lignes 200–207
**Description** : L'URL de l'endpoint AJAX est construite en cherchant `'create'` ou `'edit'` dans le chemin courant. Si ni l'un ni l'autre n'est trouvé (path inattendu), la boucle vide `splitted` et produit une URL invalide sans aucune gestion d'erreur.
**Mitigation** : Injecter l'URL depuis PHP (attribut `data-` sur le formulaire ou variable JS) plutôt que la construire côté client.

#### 4. `decPart > maxDec` silencieusement réinitialisé à 0

**Fichier** : `form_vols_avion.js` ligne 59
**Description** : Après la normalisation de `decStr`, si `decPart` excède encore `maxDec` (valeur invalide en base ou erreur d'encodage), il est réinitialisé à 0 sans avertissement. La donnée stockée est silencieusement perdue.
**Mitigation** : Conserver la protection mais logguer un warning en mode debug (`console.warn`).

---

### 🟡 Mineur

#### 5. Paramètre `mode` inutilisé dans `formatDuree`

**Fichier** : `form_vols_avion.js` lignes 123–129
**Description** : La signature et la JSDoc mentionnent un paramètre `mode` (0/1/2), mais la fonction convertit systématiquement en `h:mm` quel que soit le mode. Le paramètre est trompeur pour les lecteurs futurs.
**Mitigation** : Supprimer le paramètre `mode` de la signature et des appels.

#### 6. `alert("error")` bloquant sur erreur AJAX

**Fichier** : `form_vols_avion.js` ligne 239
**Description** : En cas d'échec AJAX, un `alert()` bloque l'interface. C'est incohérent avec les autres retours d'erreur du formulaire qui utilisent des éléments inline.
**Mitigation** : Remplacer par un affichage dans `#time_error` ou une notification non bloquante.

#### 7. `duree_display` et `time_error` non effacés au changement de machine

**Fichier** : `form_vols_avion.js` fonction `update_hora_format`
**Description** : Quand la machine change et que les widgets sont reconstruits, les divs `#duree_display` et `#time_error` conservent leurs valeurs précédentes jusqu'à la prochaine interaction. L'utilisateur voit une durée appartenant à la machine précédente.
**Mitigation** : Effacer ces deux éléments dans `buildHoraWidgets()`.

#### 8. Listener `input` redondant sur `<select>`

**Fichier** : `form_vols_avion.js` ligne 92
**Description** : `addEventListener('input', updateHidden)` sur le `<select>` décimal. Les `<select>` déclenchent `change` mais pas `input` de façon fiable selon les navigateurs. La ligne est inoffensive mais constitue du code mort.
**Mitigation** : Supprimer la ligne 92.

#### 9. Double initialisation des widgets au chargement

**Fichier** : `form_vols_avion.js` lignes 297 et 302
**Description** : `buildHoraWidgets(initial_horametre_mode)` est appelé à la ligne 297, puis `update_machine()` à la ligne 302 qui déclenche à son tour `update_hora_format()` → `buildHoraWidgets()`. Le premier appel est donc redondant (les widgets sont construits deux fois à l'initialisation).
**Mitigation** : Supprimer l'appel explicite ligne 297 (laisser `update_machine()` tout gérer). Attention : vérifier que `update_machine()` couvre bien le cas où aucune machine n'est sélectionnée.

#### 10. Mélange jQuery / Vanilla JS

**Fichier** : `form_vols_avion.js`
**Description** : Le code alterne `document.getElementById()` (lignes 33–35, 80–81, 89–96) et `$()` jQuery (lignes 86, 149–162, 287–303) sans cohérence. C'est une dette stylistique qui réduit la lisibilité.
**Mitigation** : Uniformiser vers jQuery (déjà présent comme dépendance) ou vers Vanilla JS si jQuery est abandonné.

#### 11. Commentaires de debug oubliés

**Fichier** : `form_vols_avion.js` lignes 197, 211, 243
**Description** : Trois `alert(...)` commentés et un `// alert("complete")` constituent des artefacts de débogage.
**Mitigation** : Supprimer ces lignes.

---

## Résumé

| Sévérité | Nombre |
|----------|--------|
| 🔴 Critique | 0 |
| 🟠 Majeur   | 4 |
| 🟡 Mineur   | 7 |

---

## TODO (ordre décroissant de criticité)

- [x] 🟠 **#1** Race condition AJAX – annuler la requête précédente avant d'en lancer une nouvelle
- [x] 🟠 **#2** Variables globales implicites (`hm`, `h_100`, `horametre`) – documentées en tête de fichier (définies dans `*_lang.js`)
- [x] 🟠 **#3** URL AJAX construite par parsing du path – dernier horamètre préchargé côté PHP, plus de dépendance AJAX pour l'horamètre
- [ ] 🟠 **#4** `decPart > maxDec` silencieux – ajouter `console.warn` en debug
- [ ] 🟡 **#5** Supprimer le paramètre `mode` inutilisé de `formatDuree`
- [ ] 🟡 **#6** Remplacer `alert("error")` par un affichage inline
- [ ] 🟡 **#7** Effacer `#duree_display` et `#time_error` dans `buildHoraWidgets`
- [ ] 🟡 **#8** Supprimer le listener `input` redondant sur le `<select>` décimal
- [ ] 🟡 **#9** Supprimer le premier `buildHoraWidgets` redondant au chargement
- [ ] 🟡 **#10** Uniformiser jQuery vs Vanilla JS
- [ ] 🟡 **#11** Supprimer les commentaires de debug (`alert` commentés)
