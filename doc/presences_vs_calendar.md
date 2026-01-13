# Séparation des responsabilités : Calendar vs Presences

## Contexte historique

Le projet GVV a géré les présences de pilotes via deux systèmes distincts :

### Système original (avant 2024) : Google Calendar
- **Contrôleur** : `calendar` (avec délégation vers endpoints dans `presences`)
- **Vue** : `bs_calendar.php`
- **JavaScript** : `assets/javascript/calendar.js`
- **Bibliothèque** : `GoogleCal` (intégration Google Calendar API)
- **Stockage** : Google Calendar en ligne

### Système moderne (depuis 2024) : Base de données
- **Contrôleur** : `presences`
- **Vue** : `presences/presences.php`
- **Modèle** : `calendar_model`
- **JavaScript** : Intégré dans la vue (FullCalendar v6)
- **Stockage** : Table `calendar` en base de données MySQL

## Problème identifié

Lors du remplacement du contrôleur `presences` pour le nouveau système, les endpoints nécessaires au JavaScript `calendar.js` ont été supprimés, créant des erreurs 404 :
- `presences/delete`
- `presences/update`
- `presences/ajout`

Ces endpoints ont été temporairement rajoutés dans `presences`, mais cela violait le principe de séparation des responsabilités car le contrôleur `presences` gérait alors deux systèmes distincts.

## Solution appliquée (13 janvier 2026)

### Refactoring effectué

1. **Migration des endpoints Google Calendar**
   - Déplacement de `ajout()`, `delete()`, `update()`, `code()` depuis `presences` vers `calendar`
   - Déplacement des méthodes privées `modification_allowed()` et `creation_allowed()` pour Google Calendar
   - Ces méthodes sont maintenant autonomes dans `calendar`

2. **Mise à jour du JavaScript**
   - `calendar.js` : URLs mises à jour pour pointer vers `calendar/` au lieu de `presences/`
   - Lignes modifiées : 19, 128, 276

3. **Nettoyage du contrôleur presences**
   - Suppression complète de la section "LEGACY ENDPOINTS FOR GOOGLE CALENDAR"
   - Le contrôleur `presences` gère uniquement les présences en base de données

## Architecture résultante

### Contrôleur `calendar` (Google Calendar - Legacy)

**Responsabilité** : Gestion des présences via Google Calendar API

**Endpoints** :
- `index()` : Affichage du calendrier Google
- `ajout($format)` : Créer/modifier un événement Google
- `delete($id, $format)` : Supprimer un événement Google
- `update($format)` : Mettre à jour un événement Google (drag & drop)
- `code()` : Callback OAuth pour Google
- `set_cookie()` : Gestion du cookie de date MOD

**Méthodes privées** :
- `modification_allowed($event_id)` : Vérifier autorisation de modification
- `creation_allowed($mlogin)` : Vérifier autorisation de création

**Vue associée** :
- `application/views/bs_calendar.php`

**JavaScript associé** :
- `assets/javascript/calendar.js`

**Bibliothèques utilisées** :
- `GoogleCal` : Intégration Google Calendar API
- FullCalendar v2 avec plugin gcal

### Contrôleur `presences` (Base de données - Moderne)

**Responsabilité** : Gestion des présences stockées en base de données

**Endpoints** :
- `index()` : Affichage de l'interface FullCalendar v6
- `get_events()` : Récupérer les événements (JSON API)
- `create_presence()` : Créer une présence (JSON API)
- `update_presence()` : Mettre à jour une présence (JSON API)
- `delete_presence()` : Supprimer une présence (JSON API)
- `on_event_drop()` : Gérer le drag & drop (JSON API)
- `on_event_resize()` : Gérer le redimensionnement (JSON API)

**Méthodes privées** :
- `can_modify($event_id)` : Vérifier autorisation de modification
- `can_create($mlogin)` : Vérifier autorisation de création

**Vue associée** :
- `application/views/presences/presences.php`

**Modèle utilisé** :
- `calendar_model` : Gestion des données de présences en BD

**JavaScript** :
- Intégré dans la vue (FullCalendar v6 moderne)

## Séparation des responsabilités

| Aspect | Calendar | Presences |
|--------|----------|-----------|
| **Stockage** | Google Calendar API | Table MySQL `calendar` |
| **Intégration** | GoogleCal library | calendar_model |
| **Framework JS** | FullCalendar v2 + gcal | FullCalendar v6 |
| **Vue** | bs_calendar.php | presences/presences.php |
| **JavaScript** | calendar.js (externe) | Inline dans vue |
| **Statut** | Legacy (maintenance) | Moderne (actif) |

## Autorisation

Les deux systèmes implémentent des règles d'autorisation similaires mais indépendantes :

- **CA et rôles supérieurs** : Peuvent créer/modifier/supprimer toutes les présences
- **Utilisateurs réguliers** : Peuvent uniquement gérer leurs propres présences

## Utilisation

### Utiliser le système Google Calendar (legacy)
```
URL : http://gvv.net/index.php/calendar
```
- Pour les clubs qui utilisent encore Google Calendar
- Nécessite la configuration de `calendar_id` dans `config.php`
- Nécessite l'authentification OAuth Google

### Utiliser le système Base de données (moderne)
```
URL : http://gvv.net/index.php/presences
```
- Système recommandé pour les nouveaux déploiements
- Stockage local, pas de dépendance externe
- Interface FullCalendar v6 moderne

## Migration future

Si un club souhaite migrer de Google Calendar vers le système BD :

1. Exporter les données de Google Calendar
2. Importer dans la table `calendar` via un script de migration
3. Basculer l'URL par défaut vers `presences`
4. Éventuellement désactiver le contrôleur `calendar`

## Fichiers modifiés lors du refactoring

### Code modifié
- `application/controllers/calendar.php` : Ajout des endpoints Google Calendar
- `application/controllers/presences.php` : Suppression des endpoints legacy
- `assets/javascript/calendar.js` : Mise à jour des URLs (presences → calendar)

### Documentation
- `doc/presences_vs_calendar.md` : Ce document

## Bénéfices du refactoring

✅ **Séparation claire** : Chaque contrôleur a une responsabilité unique  
✅ **Maintenance facilitée** : Pas de dépendances croisées  
✅ **Code lisible** : Aucune confusion sur qui fait quoi  
✅ **Migration simplifiée** : Possibilité de retirer `calendar` sans affecter `presences`  
✅ **Tests isolés** : Chaque système peut être testé indépendamment

## Notes techniques

### Différences d'implémentation

**Méthodes d'autorisation** :
- `calendar` : `modification_allowed()` et `creation_allowed()` vérifient les événements Google
- `presences` : `can_modify()` et `can_create()` vérifient les enregistrements BD

**Format de données** :
- Google Calendar : Format Google Event avec `summary`, `start`, `end`, `description`
- Base de données : Format structuré avec `mlogin`, `role`, `commentaire`, `start_datetime`, `end_datetime`

**Gestion des dates** :
- Google Calendar : Gestion des fuseaux horaires (+00:00), dates ISO 8601
- Base de données : Dates MySQL DATETIME, timezone du serveur

## Support

Pour toute question sur l'utilisation de l'un ou l'autre système, consulter :
- `README.md` : Documentation générale
- `doc/development/workflow.md` : Workflow de développement
- Logs applicatifs : `application/logs/` (avec gvv_debug)

---

**Dernière mise à jour** : 13 janvier 2026  
**Auteur du refactoring** : GitHub Copilot (Claude Sonnet 4.5)
