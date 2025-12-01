# Test Manuel - Boutons de Visualisation pour Écritures Gelées

## Objectif

Valider que les boutons edit/delete se comportent correctement quand une écriture est gelée :
- Le bouton edit se transforme en bouton de visualisation (icône œil) et reste actif
- Le bouton delete reste désactivé
- Le formulaire de visualisation affiche les données mais empêche la modification

## Pré-requis

1. Être connecté avec un compte ayant les droits de modification (admin, trésorier, etc.)
2. Avoir accès à un journal de compte avec des écritures

## Procédure de Test

### Test 1 : Bouton de Visualisation sur Écriture Gelée

**Étapes :**
1. Se connecter à http://gvv.net/
2. Naviguer vers Compta > Journal de compte (choisir n'importe quel compte)
3. Trouver une écriture non gelée (checkbox "Gel" non cochée)
4. **État initial :**
   - Vérifier que le bouton edit a l'icône crayon (fa-edit)
   - Vérifier que le bouton est de couleur bleue (btn-primary)
   - Vérifier que le titre du bouton est "Modifier"
5. **Geler l'écriture :**
   - Cocher la checkbox "Gel" pour cette écriture
   - Attendre que l'AJAX se termine (environ 0.5 secondes)
6. **Vérifications après gel :**
   - Le bouton edit doit avoir changé :
     - Icône œil blanche (fa-eye) au lieu du crayon
     - Même couleur bleue (btn-primary) que le bouton d'édition
     - Titre "Visualiser" au lieu de "Modifier"
     - Le bouton reste ACTIF (pas grisé, cliquable)
   - Le bouton delete doit être :
     - Grisé (opacity: 0.4)
     - Non cliquable
     - Titre "Écriture gelée"

### Test 2 : Dégel d'une Écriture

**Étapes :**
1. Utiliser l'écriture gelée du test précédent
2. **Dégeler l'écriture :**
   - Décocher la checkbox "Gel"
   - Attendre que l'AJAX se termine
3. **Vérifications après dégel :**
   - Le bouton de visualisation doit redevenir bouton edit :
     - Icône crayon (fa-edit)
     - Couleur bleue (btn-primary)
     - Titre "Modifier"
   - Le bouton delete doit être :
     - Actif (pas grisé)
     - Cliquable
     - Titre "Supprimer"

### Test 3 : Formulaire en Mode Visualisation

**Étapes :**
1. Geler une écriture (checkbox "Gel" cochée)
2. Cliquer sur le bouton de visualisation (icône œil)
3. **Vérifications dans le formulaire :**
   - Le formulaire doit s'ouvrir
   - Les champs doivent être en lecture seule (disabled ou readonly)
   - Le bouton de validation/submit doit être désactivé (disabled)
   - Un message d'alerte doit s'afficher : "La modification d'une écriture gelée est interdite"
   - La checkbox "Gel" doit être cochée
   - Les données de l'écriture doivent être affichées correctement
4. Tenter de modifier un champ → doit être impossible
5. Le bouton de validation ne doit pas être cliquable

### Test 3bis : Dégelage depuis le Formulaire de Visualisation

**Étapes :**
1. Depuis le formulaire de visualisation (Test 3)
2. Décocher la checkbox "Gel"
3. **Vérifications après décochage :**
   - Un appel AJAX doit être effectué (la checkbox se désactive temporairement)
   - La page doit se recharger automatiquement
   - Après rechargement, le formulaire doit être en mode édition :
     - Champs modifiables
     - Bouton de validation actif
     - Pas de message d'alerte
     - Checkbox "Gel" décochée

### Test 4 : Formulaire en Mode Édition (Normal)

**Étapes :**
1. S'assurer d'avoir une écriture non gelée
2. Cliquer sur le bouton edit (icône crayon)
3. **Vérifications dans le formulaire :**
   - Le formulaire doit s'ouvrir
   - Les champs doivent être modifiables
   - Le bouton de validation/submit doit être actif
4. Modifier un champ → doit être possible
5. Le bouton de validation doit être cliquable

## Résultats Attendus

### Succès
- ✅ Le bouton edit change d'icône quand gelé (garde la même couleur bleue)
- ✅ Le bouton de visualisation reste actif même quand gelé
- ✅ Le bouton delete est désactivé quand gelé
- ✅ Le formulaire en mode visualisation empêche toute modification
- ✅ Le dégel depuis la vue liste restaure les boutons à leur état normal
- ✅ Le dégel depuis le formulaire recharge la page en mode édition
- ✅ Le gel depuis le formulaire est empêché (doit se faire depuis la vue liste)

### Échec
- ❌ Le bouton edit est désactivé quand gelé (au lieu de rester actif)
- ❌ L'icône ou la couleur ne change pas
- ❌ Le bouton delete reste actif quand gelé
- ❌ Le formulaire permet la modification quand gelé

## Notes Techniques

### Fichiers Modifiés
- `application/controllers/compta.php` (lignes 1832-1853) : Génération des boutons avec logique gel/visualisation
- `application/views/compta/bs_journalCompteView.php` (lignes 520-556) : JavaScript pour changement dynamique des boutons
- `application/views/compta/bs_formView.php` (lignes 602-657) : JavaScript pour dégelage depuis le formulaire

### Comportement du Contrôleur
La fonction `edit()` dans `compta.php` détecte automatiquement si une écriture est gelée et appelle `form_static_element(VISUALISATION)` au lieu de `MODIFICATION`, ce qui désactive le formulaire.

### Dégelage depuis le Formulaire
Quand l'utilisateur décoche la checkbox "Gel" dans le formulaire de visualisation :
1. Un appel AJAX est effectué vers `compta/toggle_gel` avec `gel=0`
2. En cas de succès, la page se recharge automatiquement (`location.reload()`)
3. Au rechargement, le contrôleur détecte que `gel=0` et charge le formulaire en mode MODIFICATION
4. Le gel depuis le formulaire (cocher la checkbox) est empêché pour forcer l'utilisation de la vue liste

### Classes CSS et Icônes
- Bouton edit normal : `btn-primary`, icône `fa-edit`, titre "Modifier"
- Bouton visualisation : `btn-primary view-mode`, icône `fa-eye` (blanche), titre "Visualiser"
- Bouton delete désactivé : `btn-danger disabled`, titre "Écriture gelée"

## Date de Test

**Test à effectuer après déploiement de la branche contenant les modifications**

---

*Document créé le 2025-12-01*
