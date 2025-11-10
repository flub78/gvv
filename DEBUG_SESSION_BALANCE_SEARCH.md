# Contexte de Debug - Régression Recherche Balance Hiérarchique - ✅ RÉSOLU

**Date de session** : 10 novembre 2025  
**Problème** : Régression de la fonctionnalité de recherche dans la balance hiérarchique  
**Status** : **✅ RÉSOLU**

## ✅ Solution Implémentée

### Problème Racine Identifié
- **Pagination DataTables** : "Peignot Frédéric" se trouvait sur la page 2 des DataTables (pagination avec 100 entrées par page)
- La recherche accordéon ne pouvait pas accéder aux données non-chargées dans le DOM
- Classe `searchable_nosort_datatable` avec `"bPaginate": true` causait le problème

### Corrections Appliquées

1. **Nouvelle classe DataTable** dans `application/views/bs_footer.php` :
   ```javascript
   $('.balance_searchable_datatable').dataTable({
       "bFilter": true,      // Recherche activée
       "bPaginate": false,   // PAS de pagination
       "bInfo": false,       // Pas d'affichage du compteur
       // ... autres paramètres
   });
   ```

2. **Helper balance mis à jour** dans `application/helpers/balance_helper.php` :
   ```php
   // Ligne 82 : Remplacé 'searchable_nosort_datatable' par 'balance_searchable_datatable'
   $table_class .= ' balance_searchable_datatable';
   ```

3. **Logique de recherche mise à jour** dans `application/views/comptes/bs_balanceView.php` :
   - Détection des deux classes DataTable (ancienne + nouvelle)
   - Support backward compatibility

### Résultat
✅ **Comportement restauré** : Taper "PEI" affiche maintenant uniquement le groupe "Comptes Membres" (411) avec "Peignot Frédéric" visible et filtré

## Résumé du Problème

### Symptôme Initial
- Quand l'utilisateur tape "PEI" dans la recherche de la balance hiérarchique
- **Comportement actuel** : TOUS les groupes sont filtrés/cachés 
- **Comportement attendu** : Afficher le groupe "Comptes Membres" (411) avec "Peignot Frédéric" visible
- **Status** : C'est une RÉGRESSION - cela fonctionnait avant

### Données de Contexte
- **Nom recherché** : "Peignot Frédéric" 
- **Localisation** : Comptes 411 (Comptes Membres)
- **Terme de recherche** : "PEI" (début de "Peignot")
- **Structure** : Balance hiérarchique avec accordéons Bootstrap

## Diagnostic Effectué

### Tests Réalisés

1. **Test de structure HTML**
   - ✅ 44 accordéons détectés
   - ✅ Tous les sélecteurs fonctionnent pour accéder aux tables
   - ✅ Structure HTML correcte avec `.balance-datatable-wrapper`

2. **Test de recherche "Peignot"**
   - ❌ "Peignot" NON trouvé dans aucun accordéon avec les sélecteurs JavaScript
   - **Hypothèse** : Problème d'accès au contenu des accordéons fermés

### Corrections Appliquées

1. **Sélecteurs mis à jour** dans `application/views/comptes/bs_balanceView.php` ligne ~451:
   ```javascript
   // AVANT
   var accordionBody = item.querySelector('.accordion-collapse .accordion-body table tbody');
   
   // APRÈS (avec wrapper)
   var accordionBody = item.querySelector('.accordion-collapse .accordion-body .balance-datatable-wrapper table tbody');
   ```

2. **Fallbacks ajoutés** pour robustesse

### Hypothèse Principale
**Les accordéons fermés ne permettent pas l'accès à leur contenu via JavaScript**, ce qui empêche la détection de "Peignot" dans l'accordéon 411 qui est fermé par défaut.

## Fichiers Modifiés

### Code Principal
- **Fichier** : `application/views/comptes/bs_balanceView.php` (819 lignes)
- **Fonction principale** : `initializeAccordionSearch()` lignes ~400-540
- **Logique** : Recherche two-level (header vs enfants) avec gestion état accordéons

### Scripts de Debug Créés
1. **`debug_pei_final.js`** - Script complet pour diagnostic en console navigateur
2. **`find_peignot_quick.js`** - Script rapide pour localiser "Peignot"
3. **`test_411_accordion.js`** - Test spécifique accordéon 411 (à exécuter)
4. **`test_structure_check.html`** - Page de test structure HTML

## Prochaines Étapes

### Test à Finaliser
Exécuter `test411Accordion()` depuis `test_411_accordion.js` pour confirmer :
1. L'accordéon 411 existe et contient "Peignot Frédéric"
2. Il est fermé par défaut 
3. Le contenu devient accessible après ouverture forcée

### Solution Probable
Si l'hypothèse se confirme, implémenter dans `bs_balanceView.php` :
```javascript
// Avant de chercher dans les enfants, forcer l'ouverture temporaire
if (!accordionBody && !wasOriginallyOpen) {
    // Ouvrir temporairement l'accordéon
    collapseElement.classList.add('show');
    // Rechercher
    accordionBody = item.querySelector('...');
    // Refermer si pas originalement ouvert
    if (!originalState) {
        collapseElement.classList.remove('show');
    }
}
```

## État des Serveurs
- **PHP Dev Server** : `localhost:8000` (terminal ID: 6f656a00-381c-4ed6-a3c8-e0f39df4bb84)
- **Test Server** : `localhost:8080` (Python HTTP server pour tests)

## Commandes pour Reprendre

1. **Lancer le serveur GVV** (si nécessaire) :
   ```bash
   cd /home/frederic/git/gvv
   source setenv.sh
   php -S localhost:8000
   ```

2. **Accéder à la balance** :
   ```
   http://localhost:8000/comptes/balance
   ```

3. **Tester la théorie de l'accordéon fermé** :
   - Copier le contenu de `test_411_accordion.js` dans la console
   - Exécuter `test411Accordion()`

4. **Reproduction du bug** :
   - Taper "PEI" dans la recherche
   - Observer que tous les groupes disparaissent

## Code de Base pour la Solution

La logique de recherche se trouve dans `initializeAccordionSearch()` vers la ligne 450 :

```javascript
// Si pas trouvé dans le header, rechercher dans les comptes enfants (accordion body)
if (!shouldShow && searchTerm !== '') {
    // PROBLÈME : accordionBody est null pour accordéons fermés
    var accordionBody = item.querySelector('.accordion-collapse .accordion-body .balance-datatable-wrapper table tbody');
    
    // TODO : Implémenter ouverture temporaire si accordionBody est null
}
```

## Fichiers Modifiés - Version Finale

### 1. `application/views/bs_footer.php`
**Ajout** : Nouvelle classe DataTable `balance_searchable_datatable` sans pagination

### 2. `application/helpers/balance_helper.php` 
**Ligne 82** : Remplacé `searchable_nosort_datatable` par `balance_searchable_datatable`

### 3. `application/views/comptes/bs_balanceView.php`
**Lignes 643 et 687** : Ajout support des deux classes DataTable pour backward compatibility

## Leçons Apprises

1. **Pagination DataTables** peut cacher des données du DOM et empêcher les recherches JavaScript
2. **Solution préférée** : Désactiver la pagination plutôt que d'essayer de la contourner
3. **Backward compatibility** importante lors de changements de classes CSS
4. **Tests en isolation** essentiels pour identifier les causes racines (pagination vs sélecteurs vs timing)

---

**🎯 Problème résolu avec succès !** La recherche "PEI" fonctionne maintenant correctement.