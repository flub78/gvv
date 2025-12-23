# Plan DevOps CI/CD pour GVV 

**Objectif** : 

Tests PHPUnit et playwright automatisés sur Jenkins avec base de données anonymisée et chiffrée.

**Date de création** : 2025-12-05
**Dernière mise à jour** : 2025-12-19
**Statut** : Implémentation

---

## Situation actuelle
- ✅ Serveur Jenkins déployé avec job à compléter
- ✅ PHP 7.4 installé sur Jenkins
- ✅ MySQL installé avec credentials de test
- ✅ Tables GVV déjà présentes dans la base
- ✅ Suite PHPUnit complète avec scripts `run-all-tests.sh`

## Workflow
1. Générer une base de données de test /admin/generate_initial_schema. La base est anonymisée et chiffrée avec Openssl.
2. Commiter cette sauvegarde dans Git
3. Commiter des tests ou des modification de GVV
3. Job Jenkins : déchiffrer → restaurer → exécuter tests PHPUnit et playwright
4. Notifications en cas d'échec


## Serveur Jenkins

https://jenkins2.flub78.net:8443/login?from=%2F

jenkins --version
2.401.1

Une nouvelle version de Jenkins (2.528.3)

apt upgrade jenkins


### Installation de xdebug

```
sudo -i
apt install php7.4-xdebug
```

## Connextion ssh au serveur Jenkins


```
ssh_jenkins
```


## Job Jenkins

### Configuration du Job PHPUnit

**Build Steps:**

```bash
# Source PHP 7.4 environment
source setenv.sh

# Run all tests with coverage
./run-all-tests.sh --coverage
```

**Post-build Actions:**

#### Option 1: JUnit + HTML Coverage Report (Simplest - Recommended)

1. **Publish JUnit test results**
   - Test report XMLs: `build/logs/*.xml`

2. **Publish HTML reports**
   - HTML directory to archive: `build/coverage`
   - Index page: `index.html`
   - Report title: `PHPUnit Code Coverage Report`
   - Keep past HTML reports: Yes

**Avantages:**
- Pas de problème de parsing XML
- Visualisation HTML complète
- Historique des tests via JUnit
- Fonctionne immédiatement

#### Option 2: Coverage Plugin with Clover (Si le plugin est compatible)

1. **Publish JUnit test results**
   - Test report XMLs: `build/logs/*.xml`

2. **Record code coverage results**
   - Coverage format: Clover
   - Report files: `build/logs/clover.xml`
   - Adapter: Auto (Cobertura)

**Note:** Le plugin Coverage peut avoir des problèmes avec le format Clover de PHPUnit 8.x. Si erreur "does not contain data", utiliser l'Option 1.

#### Option 3: PHPUnit Plugin (Si disponible)

1. **Publish PHPUnit test results and coverage**
   - Test results: `build/logs/*.xml`
   - Coverage: `build/logs/clover.xml`

### Diagnostic des Erreurs de Coverage

Si Jenkins affiche l'erreur:
```
java.util.NoSuchElementException: [CoberturaParser] The processed file 
'/path/to/clover.xml' does not contain data.
```

**Causes possibles:**
1. Format Clover XML incompatible avec le plugin Coverage
2. Plugin Coverage attend du Cobertura, pas du Clover PHPUnit
3. Version de PHPUnit 8.x génère un format différent

**Solutions:**
1. ✅ **Recommandé**: Utiliser Option 1 (JUnit + HTML)
2. Installer le plugin "PHPUnit Plugin" (Option 3)
3. Générer un rapport Cobertura au lieu de Clover (nécessite conversion)

### Vérification locale

Tester la génération des rapports:

```bash
# Générer coverage localement
./run-all-tests.sh --coverage

# Vérifier les fichiers générés
ls -lh build/logs/clover.xml
ls -lh build/logs/junit.xml
ls -lh build/coverage/index.html

# Valider le XML Clover
xmllint --noout build/logs/clover.xml

# Ouvrir le rapport HTML
firefox build/coverage/index.html
```

