# Review Checklist

Cette checklist doit être utilisée pour toute Pull Request avant le merge sur la branche `main`.

---

## 📋 Checklist de Pré-Commit (Développeur)

### Code Quality

- [ ] Le code est conforme aux conventions du projet (CodeIgniter 2.x, Bootstrap 5)
- [ ] Pas de duplication de code - réutilisation du code existant vérifiée
- [ ] Les noms de variables/fonctions sont clairs et explicites
- [ ] Pas de code commenté inutile ou de TODO/FIXME non documentés
- [ ] Pas de `var_dump()`, `print_r()`, ou `echo` de debug
- [ ] Pas de warnings PHP en mode développement

### Architecture & Metadata

- [ ] Les métadonnées sont définies dans `Gvvmetadata.php` pour tout nouveau champ
- [ ] Les vues utilisent `$this->gvvmetadata->table()` pour les tableaux
- [ ] Les formulaires utilisent `array_field()` et `input_field()` 
- [ ] Les contrôleurs étendent `CI_Controller` ou `Gvv_Controller`
- [ ] Le pattern MVC de CodeIgniter est respecté

### Database & Migrations

- [ ] Migration créée si changement de schéma (numérotée correctement)
- [ ] `application/config/migration.php` mis à jour avec le nouveau numéro
- [ ] La migration est testable (up/down fonctionnent)
- [ ] Les clés primaires sont incluses dans `select_page()` des modèles
- [ ] Les jointures nécessaires sont implémentées dans les modèles

### Internationalization

- [ ] Tous les textes utilisateur sont dans les fichiers de langue (french/, english/, dutch/)
- [ ] Utilisation de `$this->lang->line('key_name')` pour tous les textes
- [ ] Les clés de langue sont cohérentes et bien nommées

### Testing

- [ ] Environnement configuré (`source setenv.sh` exécuté)
- [ ] Tests PHPUnit créés dans le répertoire approprié (`unit/`, `integration/`, `enhanced/`, `mysql/`)
- [ ] Tous les tests passent : `./run-all-tests.sh`
- [ ] Tests de régression pour les bugs corrigés conservés
- [ ] Validation PHP sans erreur : `php -l <file>`

### Documentation

- [ ] Documentation fonctionnelle ajoutée/mise à jour si nécessaire
- [ ] Notes de design créées/mises à jour pour les nouvelles fonctionnalités (`doc/design_notes/`)
- [ ] PRD mis à jour si changement de requirements
- [ ] README ou guide utilisateur mis à jour si interface modifiée
- [ ] Diagrammes PlantUML créés si architecture complexe

### Security & Permissions

- [ ] Validation des inputs côté serveur (jamais uniquement côté client)
- [ ] Protection CSRF active sur les formulaires
- [ ] Autorisations vérifiées (rôles utilisateur respectés)
- [ ] Pas de données sensibles dans les logs
- [ ] Permissions fichiers correctes (`chmod +wx` pour logs/uploads si nécessaire)

---

## 🔍 Checklist de Review (Reviewer)

### Review Générale

- [ ] La PR a un **titre clair et descriptif**
- [ ] La PR traite **un seul sujet** (principe de responsabilité unique)
- [ ] La description de la PR explique **pourquoi** le changement est nécessaire
- [ ] Les commits sont **logiques et bien décrits**
- [ ] Pas de fichiers non pertinents modifiés (build artifacts, IDE config, etc.)

### Code Review

- [ ] Le code est **lisible et maintenable**
- [ ] Pas de logique métier complexe sans commentaires explicatifs
- [ ] Les patterns existants du projet sont respectés
- [ ] Pas de réinvention de fonctionnalités existantes
- [ ] Le code suit le principe DRY (Don't Repeat Yourself)
- [ ] Les fonctions ont une **responsabilité unique** et limitée
- [ ] Gestion appropriée des erreurs et cas limites

### Architecture & Design

- [ ] L'approche choisie est cohérente avec l'architecture existante
- [ ] Pas de sur-ingénierie (solution simple et efficace)
- [ ] Les dépendances sont appropriées et minimales
- [ ] Le système de métadonnées est utilisé correctement
- [ ] Séparation claire des responsabilités (MVC respecté)

### Database & Performance

- [ ] Les requêtes SQL sont optimisées (pas de N+1)
- [ ] Index définis si nécessaire pour les nouvelles tables
- [ ] Migrations testées (up et down)
- [ ] Pas de chargement de données inutiles
- [ ] Pagination implémentée pour les listes longues

### Testing & Quality

- [ ] Tests unitaires pertinents et suffisants
- [ ] Tests d'intégration pour les interactions complexes
- [ ] Coverage appropriée (≥70% pour nouveau code)
- [ ] Tous les tests passent localement
- [ ] Tests de régression pour bugs corrigés inclus
- [ ] Pas de tests désactivés sans justification

### Documentation

- [ ] Code auto-documenté (noms explicites)
- [ ] Commentaires présents pour logique complexe
- [ ] Documentation technique à jour
- [ ] Documentation utilisateur mise à jour si nécessaire
- [ ] Diagrammes/schémas ajoutés si architecture complexe

### Security Review

- [ ] Pas de failles d'injection SQL (requêtes préparées)
- [ ] Validation et sanitization des inputs
- [ ] Pas de XSS possible (échappement approprié)
- [ ] Gestion appropriée des sessions et autorisations
- [ ] Pas de données sensibles exposées (logs, erreurs, etc.)

### UI/UX (si applicable)

- [ ] Interface cohérente avec le reste de l'application (Bootstrap 5)
- [ ] Responsive design (mobile, tablette, desktop)
- [ ] Messages d'erreur clairs et utilisables
- [ ] Feedback utilisateur pour toutes les actions
- [ ] Pas d'actions silencieuses (succès/échec toujours visible)
- [ ] Accessibilité basique respectée

---

## ✅ Checklist de Merge

### Pré-Merge

- [ ] Tous les commentaires de review sont **résolus ou discutés**
- [ ] Au moins **une approbation** d'un reviewer
- [ ] Branche **à jour avec main** (`git merge main` ou rebase)
- [ ] Pas de conflits de merge
- [ ] CI/CD pipeline **vert** (tous les checks passent)
- [ ] Tests automatiques passent : `./run-all-tests.sh --coverage`
- [ ] Validation manuelle effectuée si nécessaire

### Validation Finale

- [ ] Test smoke manuel effectué sur l'environnement de test
- [ ] Aucune régression détectée sur fonctionnalités existantes
- [ ] Performance acceptable (pas de dégradation visible)
- [ ] Logs vérifiés (pas d'erreurs/warnings nouveaux)

### Type de Merge

- [ ] **Squash and merge** recommandé (historique propre)
- [ ] Message de commit final clair et descriptif
- [ ] Référence au ticket/issue si applicable

### Post-Merge

- [ ] Branche de feature supprimée après merge
- [ ] Issue/ticket fermé ou mis à jour
- [ ] Documentation de release mise à jour si nécessaire
- [ ] Backlog actualisé

---

## 🚨 Critères de Blocage (Ne PAS Merger Si)

- ❌ Tests en échec
- ❌ Erreurs PHP ou warnings non résolus
- ❌ Conflits de merge non résolus
- ❌ Failles de sécurité identifiées
- ❌ Régression sur fonctionnalités existantes
- ❌ Documentation manquante pour fonctionnalité complexe
- ❌ Migration database non testée
- ❌ Pas d'approbation de reviewer
- ❌ Commentaires critiques non résolus

---

## 📚 Références

- [Workflow Contributeurs](new_contributors_workflow.md)
- [Guide Testing](../testing/TESTING.md)
- [Instructions AI](../AI_INSTRUCTIONS.md)
- [Workflow Développement](workflow.md)

---

**Version** : 1.0  
**Dernière mise à jour** : 28 février 2026
