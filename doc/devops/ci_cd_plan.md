# Plan DevOps CI/CD pour GVV 

**Objectif** : 

Tests PHPUnit et playwright automatisés sur Jenkins avec base de données anonymisée et chiffrée.

**Date de création** : 2025-12-05
**Dernière mise à jour** : 2025-12-19
**Statut** : Implémentation

---

## Contexte

### Situation actuelle
- ✅ Serveur Jenkins déployé avec job à compléter
- ✅ PHP 7.4 installé sur Jenkins
- ✅ MySQL installé avec credentials de test
- ✅ Tables GVV déjà présentes dans la base
- ✅ Suite PHPUnit complète avec scripts `run-all-tests.sh`

### Workflow
1. Générer une base de données de test /admin/generate_initial_schema. La base est anonymisée et chiffrée avec Openssl.
2. Commiter cette sauvegarde dans Git
3. Commiter des tests ou des modification de GVV
3. Job Jenkins : déchiffrer → restaurer → exécuter tests PHPUnit et playwright
4. Notifications en cas d'échec


### Serveur Jenkins

https://jenkins2.flub78.net:8443/login?from=%2F


### Connextion ssh au serveur Jenkins

```
ssh_jenkins
```

### Job Jenkins