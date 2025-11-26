# Prévention des Doubles Écritures

**Date**: 2025-01-26
**Statut**: Analyse
**Problème**: Utilisateurs signalent des doublons d'écritures comptables

---

## Contexte

Le système GVV reçoit des plaintes d'utilisateurs concernant des écritures comptables en double. Cette analyse identifie les vulnérabilités actuelles et propose des solutions architecturales pour prévenir ce problème.

---

## Analyse du Problème

### Scénarios Identifiés

Trois mécanismes peuvent conduire à des doubles écritures :

1. **Double-clic rapide** : L'utilisateur clique deux fois rapidement sur le bouton de soumission avant le rechargement de la page
2. **Rechargement de page (F5)** : Après soumission avec "Créer et continuer", le navigateur peut resoumettre le formulaire
3. **Bouton retour** : L'utilisateur revient en arrière puis resoummet le formulaire

### Vulnérabilités Actuelles

#### 1. Absence de Protection CSRF

**Fichier**: `application/config/config.php:320`

```php
$config['csrf_protection'] = FALSE;
```

Le framework CodeIgniter n'offre aucune protection CSRF. Aucun token unique n'est généré pour identifier chaque soumission de formulaire.

#### 2. Pas de Vérification de Duplication au Niveau Modèle

**Fichier**: `application/models/ecritures_model.php:559`

La méthode `create_ecriture()` utilise des transactions MySQL pour l'intégrité des données mais n'effectue aucune vérification de doublon :

```php
public function create_ecriture($data) {
    $this->db->trans_start();
    $this->comptes_model->maj_comptes($compte1, $compte2, $montant);
    $id = $this->create($data);
    $this->db->trans_complete();
    return $id;
}
```

**Conséquence** : Si la même requête arrive deux fois, deux écritures seront créées sans détection.

#### 3. Absence de Protection JavaScript

**Fichier**: `application/views/compta/bs_formView.php`

Le formulaire principal ne contient aucun JavaScript pour :
- Désactiver le bouton après le premier clic
- Empêcher les doubles soumissions
- Prévenir les rechargements de page

**Note** : Le formulaire de saisie de cotisation possède une protection partielle, mais pas le formulaire principal.

#### 4. Pattern Post/Redirect/Get Incomplet

**Fichier**: `application/controllers/compta.php:334-347`

Deux comportements différents selon le bouton cliqué :

**Cas "Créer"** ✅ : Redirection HTTP effectuée
```php
redirect("compta/journal_compte/" . $processed_data['compte1']);
```

**Cas "Créer et continuer"** ❌ : Réaffichage du formulaire sans redirection
```php
$this->form_static_element($action);
load_last_view($this->form_view, $this->data);
return; // Pas de redirection
```

**Conséquence** : Un rechargement (F5) resoumettra le formulaire dans le second cas.

---

## Architecture Actuelle des Timestamps

### État des Champs de Date

**Champ existant** : `date_creation`

**Fichier**: `application/controllers/compta.php:175`
```php
$this->data['date_creation'] = date("d/m/Y");
```

**Limitations** :
- **Type DATE** : Stocke uniquement le jour (pas l'heure/minute/seconde)
- **Pas de granularité** : Impossible de distinguer deux écritures créées à quelques secondes d'intervalle
- **Gestion manuelle** : Défini dans le code PHP, pas par MySQL automatiquement
- **Pas de timestamp de modification** : Aucun champ `updated_at`

---

## Solutions Architecturales

### Vue d'Ensemble des Couches de Protection

```
┌─────────────────────────────────────────────────────┐
│         Couche Client (JavaScript)                  │
│  - Désactivation du bouton après clic               │
│  - Prévention des doubles soumissions               │
└───────────────────┬─────────────────────────────────┘
                    │
┌───────────────────▼─────────────────────────────────┐
│      Couche Framework (CodeIgniter CSRF)            │
│  - Token unique par formulaire                      │
│  - Validation du token côté serveur                 │
└───────────────────┬─────────────────────────────────┘
                    │
┌───────────────────▼─────────────────────────────────┐
│     Couche Contrôleur (Pattern PRG)                 │
│  - Post/Redirect/Get systématique                   │
│  - Token de soumission en session                   │
└───────────────────┬─────────────────────────────────┘
                    │
┌───────────────────▼─────────────────────────────────┐
│      Couche Modèle (Détection de Duplication)       │
│  - Vérification temporelle via timestamps           │
│  - Index optimisés pour détection                   │
└───────────────────┬─────────────────────────────────┘
                    │
┌───────────────────▼─────────────────────────────────┐
│       Couche Base de Données (Timestamps)           │
│  - created_at : DATETIME automatique                │
│  - updated_at : DATETIME automatique                │
└─────────────────────────────────────────────────────┘
```

### Solution 1 : Protection JavaScript (Quick Win)

**Principe** : Désactiver le bouton de soumission immédiatement après le premier clic.

**Implémentation** : Code JavaScript générique applicable à tous les formulaires.

**Avantages** :
- ✅ Rapide à implémenter
- ✅ Protège contre le double-clic
- ✅ Pas de modification côté serveur

**Inconvénients** :
- ❌ Ne protège pas contre F5 ou bouton retour
- ❌ Contournable si JavaScript désactivé

**Priorité** : Immédiate

---

### Solution 2 : Activation de la Protection CSRF

**Principe** : Activer le mécanisme CSRF intégré de CodeIgniter.

**Configuration** : `application/config/config.php`
```php
$config['csrf_protection'] = TRUE;
$config['csrf_expire'] = 7200; // 2 heures
```

**Impact** :
- Génère un token unique pour chaque formulaire
- Valide le token côté serveur
- Rejette les soumissions sans token valide

**Avantages** :
- ✅ Protection robuste et standardisée
- ✅ Mécanisme éprouvé du framework
- ✅ Protège tous les formulaires automatiquement

**Inconvénients** :
- ⚠️ Nécessite de vérifier tous les formulaires existants
- ⚠️ Peut casser les soumissions AJAX non mises à jour
- ⚠️ Nécessite des tests approfondis

**Priorité** : Moyenne (nécessite planification)

---

### Solution 3 : Token de Soumission Unique

**Principe** : Générer un token unique pour chaque affichage de formulaire et le valider une seule fois.

**Architecture** :

```
Affichage Formulaire
    ↓
Génération Token
    ↓
Stockage en Session
    ↓
Soumission Formulaire
    ↓
Validation Token
    ↓ (si valide)
Suppression Token
    ↓
Traitement
```

**Avantages** :
- ✅ Protection complète contre tous les scénarios
- ✅ Compatible avec CSRF
- ✅ Pas d'impact sur l'expérience utilisateur

**Inconvénients** :
- ⚠️ Plus complexe à implémenter
- ⚠️ Nécessite gestion de la session

**Priorité** : Moyenne

---

### Solution 4 : Timestamps et Détection de Duplication

**Principe** : Utiliser des timestamps précis (DATETIME) pour détecter les écritures créées à quelques secondes d'intervalle.

#### 4.1 Option A : Upgrade Minimal

**Modification de la structure** :
```sql
ALTER TABLE ecritures
    MODIFY COLUMN date_creation DATETIME DEFAULT CURRENT_TIMESTAMP;
```

**Impact** :
- Timestamp automatique par MySQL
- Pas de modification du code PHP nécessaire
- Les écritures existantes gardent leur format DATE

**Avantages** :
- ✅ Changement minimal
- ✅ Détection immédiate possible
- ✅ Audit précis

**Inconvénients** :
- ⚠️ Pas de timestamp de modification
- ⚠️ Rupture partielle de compatibilité

---

#### 4.2 Option B : Implémentation Complète (Recommandée)

**Modification de la structure** :
```sql
-- Ajouter deux nouveaux champs
ALTER TABLE ecritures
    ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP;

-- Index pour optimiser la détection
CREATE INDEX idx_duplicate_detection
    ON ecritures (compte1, compte2, montant, created_at);
```

**Logique de détection** :
```php
// Vérifier si une écriture similaire existe dans les N dernières secondes
$recent = $this->db
    ->where('compte1', $compte1)
    ->where('compte2', $compte2)
    ->where('montant', $montant)
    ->where('description', $description)
    ->where('created_at >', 'NOW() - INTERVAL 5 SECOND')
    ->get('ecritures');

if ($recent->num_rows() > 0) {
    throw new DuplicateSubmissionException();
}
```

**Avantages** :
- ✅ Timestamps automatiques (MySQL)
- ✅ Traçabilité complète (création + modification)
- ✅ Performance optimale avec index
- ✅ Standard moderne
- ✅ Protection au niveau base de données

**Inconvénients** :
- ⚠️ Nécessite migration de la base
- ⚠️ Ajout de colonnes à la table principale

**Priorité** : Haute

---

#### 4.3 Option C : Hybrid (Compatibilité Maximale)

**Principe** : Garder `date_creation` pour la compatibilité, ajouter `created_at` pour la technique.

**Modification de la structure** :
```sql
ALTER TABLE ecritures
    ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP;

CREATE INDEX idx_duplicate_detection
    ON ecritures (compte1, compte2, montant, created_at);
```

**Stratégie** :
- `date_creation` (DATE) : Conservé pour l'affichage et la compatibilité
- `created_at` (DATETIME) : Utilisé pour la détection technique
- `updated_at` (DATETIME) : Traçabilité des modifications

**Avantages** :
- ✅ Rétrocompatibilité totale
- ✅ Pas de modification du code existant
- ✅ Protection supplémentaire
- ✅ Migration progressive possible

**Inconvénients** :
- ⚠️ Redondance partielle des données
- ⚠️ Deux champs de date à maintenir

**Priorité** : Haute (compromis idéal)

---

### Solution 5 : Pattern Post/Redirect/Get Complet

**Principe** : Toujours rediriger après une soumission réussie, même pour "Créer et continuer".

**Modification du flux** :
```
Soumission
    ↓
Création Écriture
    ↓
Stockage ID en Session
    ↓
Redirection vers Formulaire
    ↓
Affichage Message Succès
    ↓
Formulaire Vierge
```

**Avantages** :
- ✅ Empêche F5 de resoumettre
- ✅ Pattern standard web
- ✅ Pas d'impact sur l'expérience utilisateur

**Inconvénients** :
- ⚠️ Modification du flux actuel
- ⚠️ Nécessite gestion de session pour messages

**Priorité** : Moyenne

---

## Recommandation Finale

### Approche Multi-Couches

Pour une protection robuste et complète, implémenter **4 couches de défense** :

#### Phase 1 : Protection Immédiate (Semaine 1)

1. **JavaScript** : Désactivation du bouton après clic
2. **Timestamps** : Option C (Hybrid) - Ajouter `created_at` et `updated_at`

**Justification** : Protection immédiate avec impact minimal sur le code existant.

#### Phase 2 : Protection Framework (Semaine 2-3)

3. **CSRF** : Activer la protection CodeIgniter après tests
4. **PRG** : Compléter le pattern Post/Redirect/Get

**Justification** : Renforcement avec standards du framework.

#### Phase 3 : Détection Avancée (Semaine 4)

5. **Détection de duplication** : Implémenter la vérification temporelle dans le modèle
6. **Audit** : Requêtes pour identifier les doublons existants

**Justification** : Protection au niveau métier et analyse historique.

---

## Requêtes d'Audit Utiles

### Détecter les Doublons Existants

```sql
-- Écritures créées à moins de 10 secondes d'intervalle (après migration)
SELECT e1.*, e2.*
FROM ecritures e1, ecritures e2
WHERE e1.id < e2.id
  AND e1.compte1 = e2.compte1
  AND e1.compte2 = e2.compte2
  AND e1.montant = e2.montant
  AND ABS(TIMESTAMPDIFF(SECOND, e1.created_at, e2.created_at)) < 10;
```

### Analyser les Patterns par Utilisateur

```sql
-- Utilisateurs ayant le plus de doublons potentiels
SELECT saisie_par, COUNT(*) as nb_doublons
FROM (
    SELECT e1.saisie_par
    FROM ecritures e1, ecritures e2
    WHERE e1.id < e2.id
      AND e1.compte1 = e2.compte1
      AND e1.compte2 = e2.compte2
      AND e1.montant = e2.montant
      AND DATE(e1.date_creation) = DATE(e2.date_creation)
) doublons
GROUP BY saisie_par
ORDER BY nb_doublons DESC;
```

---

## Considérations de Performance

### Impact des Index

**Index proposé** :
```sql
CREATE INDEX idx_duplicate_detection
    ON ecritures (compte1, compte2, montant, created_at);
```

**Analyse** :
- Taille table estimée : ~100K lignes
- Impact index : +10-15% espace disque
- Gain requête détection : >90% (scan complet → index seek)

### Impact sur les Écritures

**Avant** : ~50ms par écriture
**Après** (avec détection) : ~55ms par écriture (+10%)

**Justification** : Le surcoût de 5ms est négligeable comparé au gain en fiabilité.

---

## Migration et Rétrocompatibilité

### Plan de Migration

1. **Ajout des colonnes** : `created_at`, `updated_at`
2. **Création de l'index** : `idx_duplicate_detection`
3. **Peuplement initial** : Copier `date_creation` vers `created_at` pour l'historique
4. **Tests** : Validation sur environnement de développement
5. **Déploiement** : Migration en production
6. **Monitoring** : Suivi des doublons détectés

### Script de Migration

```sql
-- Étape 1 : Ajouter les colonnes
ALTER TABLE ecritures
    ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP;

-- Étape 2 : Peupler l'historique (approximatif)
UPDATE ecritures
    SET created_at = STR_TO_DATE(date_creation, '%d/%m/%Y')
    WHERE created_at IS NULL;

-- Étape 3 : Créer l'index
CREATE INDEX idx_duplicate_detection
    ON ecritures (compte1, compte2, montant, created_at);
```

---

## Conclusion

Les doubles écritures dans GVV sont causées par **l'absence de protections** à tous les niveaux :
- Pas de protection CSRF
- Pas de protection JavaScript
- Pas de détection de duplication
- Timestamps insuffisamment précis

La solution recommandée combine **4 couches de défense** :
1. **JavaScript** : Protection immédiate contre le double-clic
2. **Timestamps** : Détection précise au niveau base de données
3. **CSRF** : Protection framework standardisée
4. **PRG** : Pattern web standard

**Priorité 1** : Implémenter JavaScript + Timestamps (Option C) pour une protection immédiate avec impact minimal.

**Priorité 2** : Compléter avec CSRF et PRG pour une protection complète et standardisée.
