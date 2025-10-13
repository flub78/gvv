# PRD : Correction et Amélioration de la Gestion des Licences Annuelles

**Statut :** Brouillon
**Créé :** 13-10-2025
**Priorité :** Haute
**Auteur :** Analyse Système

## Résumé Exécutif

La fonctionnalité de gestion des licences annuelles (`licences/per_year`) est actuellement cassée suite à la migration Bootstrap. Ce PRD présente l'analyse du système existant, identifie les lacunes, et propose une approche par phases pour restaurer et améliorer la fonctionnalité de suivi des cotisations annuelles des membres, du statut d'assurance, et des certificats médicaux.

---

## 1. Analyse du Système Actuel

### 1.1 Implémentation Existante

**URL :** `licences/per_year`

**Contrôleur :** `application/controllers/licences.php`
- Méthode : `per_year()` - Affiche la matrice des licences
- Méthode : `set($pilote, $year, $type)` - Crée un enregistrement de licence
- Méthode : `switch_it($pilote, $year, $type)` - Supprime un enregistrement de licence
- Méthode : `switch_to($type)` - Change le type de licence affiché

**Modèle :** `application/models/licences_model.php`
- Méthode : `per_year($type)` - Génère une matrice membres × années
- Utilise une jointure avec la table `membres` pour obtenir les membres actifs
- Affiche les années dynamiquement (plage minimale de 10 ans)
- Génère des liens cliquables pour basculer le statut de licence

**Schéma Base de Données :**
```sql
Table: licences
- id (int, auto_increment, primary key)
- pilote (varchar(25), login du membre)
- type (tinyint(2), default 0)
- year (int(4))
- date (date)
- comment (varchar(250))
- Clé primaire : pilote, year, type
```

**Types de Licence Actuels (depuis helper) :**
- Type 0 : "Cotisation" (Cotisation annuelle)
- Type 1 : "Licence/Assurance planeur" (Licence/assurance planeur)
- Type 2 : "Licence/Assurance avion" (Licence/assurance avion)
- Type 3 : "Licence/Assurance ULM" (Licence/assurance ULM)

**Données Base de Données :**
- Actuellement seuls les types 0 et 1 sont utilisés en production
- Les exemples d'enregistrements montrent des valeurs de date invalides '0000-00-00'

**Vues :**
- Version Bootstrap : `application/views/licences/bs_TablePerYear.php`
- Version héritée : `application/views/licences/TablePerYear.php`
- Les deux utilisent la bibliothèque DataTable pour afficher la matrice
- Incluent un sélecteur d'année (commenté) et un sélecteur de type de licence

**JavaScript :** `assets/javascript/gvv.js:268`
- Fonction `new_licence()` gère le changement du menu déroulant de type de licence
- Redirige vers `licences/switch_to/{type}`

---

## 2. Problèmes Identifiés

### 2.1 Problèmes de Migration Bootstrap

**Problème 1 : Éléments d'Interface Cassés**
- Les vues peuvent ne pas s'afficher correctement avec les classes Bootstrap 5
- La compatibilité de la bibliothèque DataTable avec Bootstrap 5 nécessite vérification
- Le menu déroulant de sélection de licence peut avoir des problèmes de style

**Problème 2 : Valeurs de Date Invalides**
- La base de données contient des dates '0000-00-00' qui sont invalides
- Cela peut causer des problèmes d'affichage ou de tri

**Problème 3 : Mécanisme de Basculement Peu Clair**
- L'implémentation actuelle utilise des liens texte ("-" ou numéro d'année) pour basculer
- Ce n'est pas intuitif et ne ressemble pas à une case à cocher
- Le style des cases à cocher Bootstrap serait plus convivial

**Problème 4 : Suivi des Certificats Médicaux Manquant**
- Aucun champ dans la base de données pour le statut du certificat médical
- Aucune interface pour suivre/afficher la validité médicale

**Problème 5 : Gestion de Session**
- Utilise `$this->session->userdata('year')` mais le sélecteur d'année est commenté
- Utilise `$this->session->userdata('licence_type')` pour le type actuel

---

## 3. Analyse des Exigences

### 3.1 Exigences Version Initiale (Phase 1)

**E1.1 : Voir la Matrice de Statut Annuel**
- En tant qu'admin du club, je veux voir une matrice des membres (lignes) × années (colonnes)
- Chaque cellule doit afficher le statut de cotisation/assurance pour ce membre/année
- La matrice doit être filtrable par type de licence (cotisation, assurance, etc.)
- L'affichage doit être clair et responsive sur mobile

**E1.2 : Vérifier le Statut Individuel des Membres**
- En tant qu'admin du club, je veux voir rapidement si un membre a :
  - Payé sa cotisation annuelle pour l'année actuelle/spécifique
  - Une assurance annuelle valide pour l'année actuelle/spécifique
  - Un certificat médical actuel (optionnel)
- Les indicateurs visuels doivent être clairs (cases à cocher ou codage couleur)

**E1.3 : Basculer le Statut des Membres**
- En tant qu'admin du club, je veux changer le statut annuel d'un membre d'un seul clic
- L'action doit être aussi simple que de cliquer sur une case à cocher
- Le changement doit être immédiat et persistant
- Doit fonctionner pour n'importe quelle année (passée, actuelle, future)

**E1.4 : Plusieurs Types de Licence**
- Supporter différents types : cotisation, assurance planeur, assurance avion, assurance ULM
- Chaque type doit être géré indépendamment
- Changement facile entre types via menu déroulant

### 3.2 Exigences Version Future (Phase 2)

**E2.1 : Cotisation Automatique via Achat de Produit**
- Quand un membre achète un produit spécifique (ex: "Cotisation annuelle"), définir automatiquement son statut de cotisation pour l'année

**E2.2 : Intégration API HEVA pour Assurance**
- Interroger l'API HEVA pour déterminer automatiquement si l'assurance planeur est actuelle
- Synchroniser le statut d'assurance depuis la base de données fédérale

**E2.3 : Pièces Jointes de Documents**
- Joindre des documents au statut annuel (certificats médicaux, copies d'assurance)
- Stocker et récupérer des documents par membre/année/type
- Voir/télécharger les documents joints

**E2.4 : Sélection d'Email Basée sur le Statut**
- Sélectionner les emails des membres basés sur le statut actuel
- Exemple : "Tous les membres cotisés l'année dernière mais pas cette année"
- Intégration avec le système d'email existant

**E2.5 : Restrictions d'Autorisation**
- Utiliser le statut annuel pour restreindre les permissions
- Exemple : Les membres non cotisés ne peuvent pas réserver d'aéronefs
- Intégration avec le système d'autorisation existant

---

## 4. Analyse des Lacunes

### 4.1 Lacunes Fonctionnelles

| Exigence | État Actuel | Lacune | Priorité |
|----------|-------------|--------|----------|
| Interface cases à cocher claire | Liens texte ("-" / année) | Besoin cases à cocher Bootstrap | Haute |
| Suivi médical | Non implémenté | Besoin nouveau champ/type | Moyenne |
| Dates valides | Contient '0000-00-00' | Besoin migration données | Haute |
| Responsive mobile | Inconnu | Besoin tests | Haute |
| Cotisation automatique | Non implémenté | Fonctionnalité complète nécessaire | Basse |
| Intégration HEVA | Non implémenté | Fonctionnalité complète nécessaire | Basse |
| Pièces jointes documents | Non implémenté | Fonctionnalité complète nécessaire | Basse |
| Sélection email | Non implémenté | Fonctionnalité complète nécessaire | Basse |
| Restrictions auth | Non implémenté | Fonctionnalité complète nécessaire | Basse |

### 4.2 Lacunes Techniques

**Modèle de Données :**
- Le certificat médical nécessite un suivi séparé (nouveau type ou table séparée ?)
- Le stockage de documents nécessite une conception (utiliser le système de pièces jointes existant ?)
- La validation de date nécessite une correction

**UI/UX :**
- La bibliothèque DataTable nécessite vérification de compatibilité Bootstrap 5
- Implémentation de case à cocher pour basculement (AJAX vs rechargement page ?)
- Navigation plage d'années (ensembles d'années précédentes/suivantes)
- La mise en page mobile nécessite attention

**Intégration :**
- Architecture d'intégration API HEVA
- Hooks d'achat de produit
- Intégration système email
- Intégration système d'autorisation

---

## 5. Solution Proposée - Phase 1 (Corriger Fonctionnalité Existante)

### 5.1 Changements Base de Données

**Migration : Ajouter Type Médical**
- Le suivi médical peut utiliser la structure existante avec type = 4
- Aucun changement de schéma nécessaire, juste ajouter à l'énumération

**Migration : Corriger Dates Invalides**
```sql
UPDATE licences
SET date = CONCAT(year, '-01-01')
WHERE date = '0000-00-00' OR date IS NULL;
```

### 5.2 Changements Backend

**Mettre à jour Helper :** `application/helpers/form_elements_helper.php`
```php
function licence_selector($controller, $type) {
    $licence_selector = array(
        0 => "Cotisation annuelle",
        1 => "Assurance planeur",
        2 => "Assurance avion",
        3 => "Assurance ULM",
        4 => "Certificat médical"  // NOUVEAU
    );
    // ... reste de l'implémentation
}
```

**Mettre à jour Modèle :** `application/models/licences_model.php`
- Modifier `per_year()` pour retourner un format compatible case à cocher
- Chaque cellule doit retourner : `{checked: boolean, url: string, mlogin: string, year: int, type: int}`
- Considérer retourner JSON pour implémentation AJAX

**Mettre à jour Contrôleur :** `application/controllers/licences.php`
- Méthode `set()` : valider date, assurer format correct
- Méthode `switch_it()` : considérer logique de basculement (si existe, supprimer ; sinon, créer)
- Nouvelle méthode : `toggle($pilote, $year, $type)` - point de terminaison de basculement unique
- Ajouter point de terminaison AJAX : `ajax_toggle($pilote, $year, $type)` - retourne JSON

### 5.3 Changements Frontend

**Mettre à jour Vue :** `application/views/licences/bs_TablePerYear.php`

**Option A : Rechargement Page Complète (Plus Simple, correspond au modèle existant)**
```php
// Dans le modèle, générer HTML case à cocher
$checkbox = '<input type="checkbox" ' .
            ($has_licence ? 'checked' : '') .
            ' onclick="window.location.href=\'' . $toggle_url . '\'">';
```

**Option B : AJAX (Meilleure UX, pas de rechargement page)**
```javascript
// Nouvelle fonction dans gvv.js
function toggle_licence(pilote, year, type, checkbox) {
    $.ajax({
        url: base_url + 'licences/ajax_toggle/' + pilote + '/' + year + '/' + type,
        method: 'POST',
        success: function(response) {
            if (!response.success) {
                alert('Erreur : ' + response.message);
                checkbox.checked = !checkbox.checked; // Annuler
            }
        },
        error: function() {
            alert('Erreur réseau');
            checkbox.checked = !checkbox.checked; // Annuler
        }
    });
}
```

**Structure Tableau :**
```html
<div class="table-responsive">
    <table class="table table-striped table-sm table-hover">
        <thead class="table-dark sticky-top">
            <tr>
                <th>Pilote</th>
                <th>2023</th>
                <th>2024</th>
                <th>2025</th>
                <!-- ... -->
            </tr>
        </thead>
        <tbody>
            <!-- Lignes membres avec cases à cocher -->
        </tbody>
        <tfoot class="table-secondary">
            <tr>
                <td><strong>Total</strong></td>
                <td>15</td>
                <td>18</td>
                <!-- ... -->
            </tr>
        </tfoot>
    </table>
</div>
```

**Style Bootstrap :**
- Utiliser `table-responsive` pour défilement horizontal mobile
- Utiliser `table-striped` pour couleurs de lignes alternées
- Utiliser `table-sm` pour affichage compact
- Utiliser `sticky-top` pour en-tête lors du défilement
- Utiliser Bootstrap form-check pour cases à cocher

### 5.4 Exigences de Test

**Tests Unitaires :**
- Tester méthode modèle `per_year()` avec divers ensembles de membres
- Tester logique de basculement (créer/supprimer)
- Tester validation de date

**Tests d'Intégration :**
- Tester workflow complet : voir matrice → basculer statut → vérifier base de données
- Tester avec plusieurs types de licence
- Tester calcul de plage d'années

**Tests Manuels :**
- Tester sur desktop (Chrome, Firefox, Safari)
- Tester sur appareils mobiles (iOS, Android)
- Tester avec grands ensembles de membres (100+ membres)
- Tester défilement horizontal sur écrans étroits
- Tester interactions cases à cocher

---

## 6. Solution Proposée - Phase 2 (Améliorations Futures)

### 6.1 Cotisation Automatique via Achat de Produit

**Exigences :**
- Définir quels produits déclenchent le statut de cotisation
- Table de configuration : `product_id` → `licence_type`
- Hook dans workflow d'achat

**Implémentation :**
```php
// Dans contrôleur d'achat, après achat réussi
$this->load->model('licences_model');
$product_config = $this->config->item('subscription_products');
if (in_array($product_id, $product_config)) {
    $this->licences_model->set_licence(
        $member_login,
        $current_year,
        0 // Cotisation
    );
}
```

### 6.2 Intégration API HEVA

**Exigences :**
- Identifiants API HEVA (déjà en config : `ffvv_pwd`)
- Point de terminaison API pour statut assurance membre
- Tâche de synchronisation planifiée (cron)

**Architecture :**
```
[GVV] → [API HEVA] → [Cache résultats quotidien]
                   ↓
              [Mettre à jour table licences]
```

**Implémentation :**
- Nouveau contrôleur : `FFVV/sync_insurances`
- Tâche cron : quotidienne à 2h du matin
- Journaliser résultats de synchronisation
- Notification admin en cas d'erreurs

### 6.3 Pièces Jointes de Documents

**Utiliser Système de Pièces Jointes Existant :**
- Table existe : `attachments`
- Lier documents à : `entity_type = 'licence'`, `entity_id = licence.id`
- Étendre contrôleur attachments

**Changements UI :**
- Ajouter icône "📎" dans cellule matrice quand document existe
- Cliquer pour voir/uploader documents
- Dialogue modal pour gestion documents

### 6.4 Sélection Email par Statut

**Intégration avec Contrôleur Mails :**
- Étendre `application/controllers/mails.php`
- Nouvelle option de requête : "Membres par statut licence"
- Constructeur de requête :
  - Année : 2024, 2025
  - Type : Cotisation, Assurance
  - Statut : A / N'a pas
  - Booléen : ET / OU

**Exemple Requête :**
"Sélectionner emails des membres qui :
- AVAIENT cotisation en 2024
- ET n'ont PAS cotisation en 2025"

### 6.5 Restrictions d'Autorisation

**Intégration avec Système de Permission :**
- Étendre `application/libraries/Authorization.php`
- Nouveau type de permission : `requires_subscription`
- Nouveau type de permission : `requires_insurance`

**Exemple :**
```php
// Dans contrôleur de réservation aéronef
if (!$this->authorization->has_current_subscription($member)) {
    show_error('Cotisation requise pour réserver aéronef');
}
```

---

## 7. Plan d'Implémentation

### 7.1 Phase 1 - Corriger Fonctionnalité Existante (Priorité : HAUTE)

**Sprint 1 : Corrections de Base (1 semaine)**
- Tâche 1.1 : Corriger dates base de données (migration)
- Tâche 1.2 : Mettre à jour licence_selector avec type médical
- Tâche 1.3 : Créer point de terminaison toggle dans contrôleur
- Tâche 1.4 : Mettre à jour modèle pour retourner données compatibles case à cocher
- Tâche 1.5 : Mettre à jour vue avec style Bootstrap 5 et cases à cocher
- Tâche 1.6 : Tester sur navigateurs desktop

**Sprint 2 : Polissage & Mobile (1 semaine)**
- Tâche 2.1 : Implémenter toggle AJAX (optionnel, pour meilleure UX)
- Tâche 2.2 : Tester mise en page responsive sur mobile
- Tâche 2.3 : Ajouter navigation plage d'années (si nécessaire)
- Tâche 2.4 : Écrire tests unitaires
- Tâche 2.5 : Écrire tests d'intégration
- Tâche 2.6 : Mettre à jour fichiers de langue (FR, EN, NL)

**Livrables :**
- Matrice de gestion de licence fonctionnelle
- Interface compatible Bootstrap 5
- Design responsive mobile
- Basculements de case à cocher pour statut
- Suivi certificat médical
- Tests avec >70% de couverture
- Documentation mise à jour

### 7.2 Phase 2 - Améliorations Futures (Priorité : BASSE)

**Ordre d'implémentation :**
1. Pièces jointes documents (construit sur système existant)
2. Sélection email (utile pour communication)
3. Cotisation automatique (réduit travail admin)
4. Intégration HEVA (nécessite coordination externe)
5. Restrictions autorisation (nécessite tests attentifs)

**Chaque fonctionnalité doit être :**
- Tâche/PR séparée
- Entièrement testée
- Documentée
- Rétrocompatible

---

## 8. Spécifications Techniques

### 8.1 Routes URL

| URL | Méthode | Description |
|-----|---------|-------------|
| `/licences/per_year` | GET | Afficher matrice licence |
| `/licences/switch_to/{type}` | GET | Changer type licence affiché |
| `/licences/toggle/{pilote}/{year}/{type}` | GET | Basculer statut licence (rechargement page) |
| `/licences/ajax_toggle/{pilote}/{year}/{type}` | POST | Basculer statut licence (AJAX) |

### 8.2 Format de Données

**Réponse AJAX :**
```json
{
    "success": true,
    "checked": true,
    "message": "Licence ajoutée avec succès",
    "totals": {
        "2023": 15,
        "2024": 18,
        "2025": 12
    }
}
```

**Sortie Modèle :**
```php
[
    [
        'Pilote' => '<a href="...">Dupont Pierre</a>',
        '2023' => '<input type="checkbox" checked ...>',
        '2024' => '<input type="checkbox" ...>',
        '2025' => '<input type="checkbox" ...>',
    ],
    // ... plus de membres
    [
        'Total' => 'Total',
        '2023' => 15,
        '2024' => 18,
        '2025' => 12
    ]
]
```

### 8.3 Classes CSS

**Tableau Bootstrap 5 :**
```html
<table class="table table-striped table-sm table-hover table-bordered">
```

**Case à cocher :**
```html
<div class="form-check form-switch">
    <input class="form-check-input" type="checkbox"
           id="licence_{pilote}_{year}_{type}"
           onchange="toggle_licence('{pilote}', {year}, {type}, this)">
</div>
```

### 8.4 Contraintes Base de Données

- Ajouter index sur `(pilote, year, type)` pour recherches plus rapides
- Ajouter contrainte CHECK : `year BETWEEN 2010 AND 2050`
- Ajouter contrainte CHECK : `type BETWEEN 0 AND 4`
- Ajouter contrainte UNIQUE sur `(pilote, year, type)`

---

## 9. Critères de Succès

### 9.1 Métriques de Succès Phase 1

**Fonctionnel :**
- ✅ L'admin du club peut voir la matrice de licence pour tous les membres
- ✅ L'admin du club peut basculer le statut d'un seul clic de case à cocher
- ✅ Les changements persistent correctement dans la base de données
- ✅ Le suivi de certificat médical fonctionne
- ✅ Tous les types de licence (0-4) sont supportés
- ✅ L'interface est responsive sur appareils mobiles

**Technique :**
- ✅ Aucune erreur JavaScript dans la console
- ✅ La page se charge en < 2 secondes avec 100 membres
- ✅ Fonctionne sur Chrome, Firefox, Safari (dernières versions)
- ✅ Fonctionne sur iOS Safari et Android Chrome
- ✅ Couverture de test > 70%
- ✅ Aucune erreur SQL ou date invalide

**Expérience Utilisateur :**
- ✅ L'interface est intuitive (aucune formation utilisateur nécessaire)
- ✅ Le retour visuel est immédiat
- ✅ Les messages d'erreur sont clairs
- ✅ Pas de basculements accidentels (confirmation non nécessaire pour case à cocher)

### 9.2 Métriques de Succès Phase 2

**Par Fonctionnalité :**
- Fonctionnalité entièrement documentée
- Fonctionnalité a des tests
- Fonctionnalité est rétrocompatible
- Fonctionnalité a documentation utilisateur
- Fonctionnalité approuvée par admin du club

---

## 10. Risques et Atténuations

| Risque | Impact | Probabilité | Atténuation |
|--------|--------|-------------|-------------|
| Bibliothèque DataTable incompatible avec Bootstrap 5 | Élevé | Moyen | Tester tôt ; utiliser bibliothèque tableau alternative si nécessaire |
| AJAX casse sur certains navigateurs | Moyen | Faible | Fournir repli vers rechargement page |
| Grands ensembles membres causent chargement lent | Moyen | Faible | Implémenter pagination ou chargement paresseux |
| API HEVA peu fiable | Élevé | Moyen | Implémenter logique retry et gestion erreurs |
| Confusion utilisateur avec interface case à cocher | Moyen | Faible | Ajouter texte d'aide et infobulles |
| Basculements accidentels | Moyen | Moyen | Considérer fonction annuler ou confirmation pour certaines actions |

---

## 11. Questions Ouvertes

1. **Q :** Les certificats médicaux doivent-ils être suivis par année ou juste "actuel/expiré" ?
   **R :** Proposer par année pour cohérence, mais nécessite avis admin du club

2. **Q :** Doit-on implémenter basculement AJAX ou rechargement page ?
   **R :** Proposer AJAX pour meilleure UX, avec repli rechargement page

3. **Q :** Que faire avec anciennes données (années avant 2015) ?
   **R :** Garder en base mais ne pas afficher par défaut ; ajouter option "Afficher toutes années"

4. **Q :** La ligne totaux doit-elle afficher pourcentage ou juste compte ?
   **R :** Compte suffit pour Phase 1 ; pourcentage peut être ajouté plus tard

5. **Q :** Comment gérer les membres qui quittent le club ?
   **R :** Garder données historiques ; filtrer par membres actifs seulement dans vue actuelle

6. **Q :** Doit-il y avoir journalisation audit des changements ?
   **R :** Pas en Phase 1 ; considérer pour Phase 2 si nécessaire

---

## 12. Annexe

### A. État Actuel Base de Données

**Nombre de lignes :**
```sql
SELECT type, COUNT(*) FROM licences GROUP BY type;
-- type 0 : ~500 enregistrements
-- type 1 : ~300 enregistrements
```

**Plage d'années :**
```sql
SELECT MIN(year), MAX(year) FROM licences;
-- Min : 2012
-- Max : 2024
```

**Dates invalides :**
```sql
SELECT COUNT(*) FROM licences WHERE date = '0000-00-00';
-- Résultat : ~800 enregistrements (nécessite correction)
```

### B. Fonctionnalités Similaires dans le Code

- `application/controllers/event.php` a une vue matrice similaire pour formation
- `application/models/event_model.php::licences_per_year()` - implémentation similaire
- Peut apprendre de ces implémentations

### C. Fichiers Liés

**Contrôleurs :**
- `application/controllers/licences.php`

**Modèles :**
- `application/models/licences_model.php`

**Vues :**
- `application/views/licences/bs_TablePerYear.php` (Bootstrap)
- `application/views/licences/TablePerYear.php` (Héritée)

**Helpers :**
- `application/helpers/form_elements_helper.php` (licence_selector)

**JavaScript :**
- `assets/javascript/gvv.js` (fonction new_licence)

**Fichiers de Langue :**
- `application/language/french/gvv_lang.php`
- `application/language/english/gvv_lang.php`
- `application/language/dutch/gvv_lang.php`

### D. Références

- Documentation Bootstrap 5 : https://getbootstrap.com/docs/5.0/
- Guide Utilisateur CodeIgniter 2 : https://www.codeigniter.com/userguide2/
- Workflow de Développement GVV : `doc/development/workflow.md`

---

## Historique des Révisions du Document

| Version | Date | Auteur | Changements |
|---------|------|--------|-------------|
| 1.0 | 13-10-2025 | Analyse Système | Création PRD initial |

---

**Fin du PRD**
