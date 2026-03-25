# Plan de test GVV

**Date de mise à jour:** 2026-03-25
**Statut:** 🟢 Vert - suites PHPUnit et Playwright au niveau cible

---

## 1. Synthèse

Le plan de test détaillé historique n'est plus adapté à l'état réel du dépôt.

Les deux objectifs structurants sont atteints :

- **PHPUnit** : la pile de tests est complète, stable et exploitable au quotidien.
- **Playwright** : la couverture E2E visée est désormais considérée comme complète pour le périmètre actuel, avec couverture des profils critiques et des parcours sensibles.

Le document se concentre donc désormais sur :

- les résultats obtenus,
- l'état opérationnel actuel,
- le reste à faire réellement utile.

---

## 2. État actuel validé

### PHPUnit

Exécution de référence du 2026-03-25 via `source setenv.sh && ./run-all-tests.sh` :

- **1094 tests**
- **1092 tests passés**
- **0 échec**
- **0 risky**
- **2 skipped** attendus

Suites actives :

- Unit Tests
- URL Helper Tests
- Integration Tests
- Enhanced CI Tests
- MySQL Tests

Conclusion : **la base PHPUnit est à l'objectif de stabilité attendu**.

### Playwright

État de la suite Playwright dans le dépôt :

- **536 tests recensés**
- **73 fichiers de test**
- **objectif de réussite atteint à 100%** sur la base de l'état courant retenu pour le projet

Progrès récents intégrés :

- ajout du test récursif Panoramix,
- validation du profil admin multi-sections,
- couverture récursive des profils critiques d'autorisation.

Profils d'autorisation couverts par les tests récursifs :

- `asterix`
- `obelix`
- `abraracourcix`
- `goudurix`
- `panoramix`

Conclusion : **la couverture Playwright visée pour le projet est complète à ce stade**.

---

## 3. Résultats obtenus

### Acquis structurants

- l'infrastructure PHPUnit est stabilisée et exécutable en routine,
- les suites critiques ne sont plus dans un état transitoire,
- la stratégie d'autorisation est couverte par des tests Playwright ciblés et des crawls récursifs,
- le manque identifié sur `panoramix` est levé,
- le plan de test n'a plus besoin d'une roadmap de création massive de tests ; l'enjeu principal devient le maintien du vert et l'ajout de tests uniquement quand ils répondent à un risque réel.

### Changement de posture

L'objectif n'est plus :

- d'empiler des tests pour augmenter mécaniquement le volume,
- ni de maintenir une longue liste de campagnes théoriques.

L'objectif devient :

- préserver un socle de non-régression fiable,
- ajouter des tests lors des corrections de bugs,
- compléter uniquement les zones métier réellement exposées.

---

## 4. Reste à faire

### Priorité 1 - Maintenir l'état vert

- conserver `./run-all-tests.sh` comme référence PHPUnit,
- conserver `cd playwright && npx playwright test --reporter=line` comme référence E2E,
- continuer à traiter tout bug corrigé avec un test de régression conservé dans la base.

### Priorité 2 - Finaliser le chantier autorisations

- investiguer et activer les contrôleurs encore désactivés côté nouvelle autorisation si ce chantier reste d'actualité,
- préparer l'enrôlement des utilisateurs pilotes en production,
- observer le comportement en exploitation avant bascule globale du flag.

### Priorité 3 - Couvrir uniquement les vrais risques restants

Ne pas rouvrir une roadmap générique de couverture. Ajouter des tests seulement si l'un des cas suivants se présente :

- bug de production ou régression avérée,
- nouvelle fonctionnalité à risque,
- zone métier critique encore non protégée par un scénario automatisé crédible,
- modification transverse sur la comptabilité, les autorisations, les vols ou les documents.

### Priorité 4 - Nettoyage documentaire

- archiver les anciennes sections du plan devenues fausses ou trop détaillées,
- éviter de conserver des chiffres obsolètes de migration Dusk → Playwright,
- garder ce document comme synthèse courte, et déplacer les détails d'exécution dans les artefacts ou rapports techniques si nécessaire.

---

## 5. Commandes de référence

### PHPUnit

```bash
source setenv.sh
./run-all-tests.sh
```

### Playwright

```bash
cd playwright
npx playwright test --reporter=line
```

### Test ciblé récent

```bash
cd playwright
npx playwright test tests/panoramix-recursive-authorizations.spec.js --reporter=line
```

---

## 6. Décision de gestion

Le plan de test entre en **mode maintenance**.

Cela signifie :

- **pas de campagne d'expansion artificielle**,
- **ajout de tests sur justification métier ou régression**, 
- **surveillance continue de la stabilité**, 
- **mise à jour du plan uniquement quand l'état réel change**.
