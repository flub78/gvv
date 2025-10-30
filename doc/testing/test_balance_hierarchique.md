# Guide de Test - Balance Hiérarchique

## Accès à la nouvelle fonctionnalité

URL principale : `http://gvv.net/comptes/balance`

## Scénarios de test

### Test 1 : Affichage initial
**Objectif** : Vérifier que la page s'affiche correctement

**Étapes** :
1. Se connecter à GVV avec un compte ayant accès à la comptabilité
2. Naviguer vers `http://gvv.net/comptes/balance`
3. Vérifier que le titre "Balance hiérarchique" s'affiche
4. Vérifier que les sections générales sont visibles (fond bleu clair)
5. Vérifier que les comptes détaillés sont cachés par défaut
6. Vérifier que les icônes de flèche (▶) sont présentes

**Résultat attendu** : Page affichée avec sections générales visibles, détails cachés

---

### Test 2 : Développer/Réduire une section
**Objectif** : Vérifier l'interaction de développement d'une section

**Étapes** :
1. Cliquer sur une ligne de section générale (ex: "512 Banque")
2. Observer les comptes détaillés qui apparaissent
3. Vérifier que l'icône pivote (▶ devient rotée)
4. Cliquer à nouveau sur la même section
5. Observer que les détails se cachent

**Résultat attendu** : Développement/réduction fluide avec animation de l'icône

---

### Test 3 : Boutons "Tout développer" / "Tout réduire"
**Objectif** : Vérifier les boutons de contrôle global

**Étapes** :
1. Cliquer sur "Tout développer"
2. Vérifier que toutes les sections sont développées
3. Vérifier que toutes les icônes sont pivotées
4. Cliquer sur "Tout réduire"
5. Vérifier que toutes les sections sont réduites

**Résultat attendu** : Contrôle global fonctionne sur toutes les sections

---

### Test 4 : Filtres
**Objectif** : Vérifier que les filtres fonctionnent

**Étapes** :
1. Ouvrir l'accordéon "Filtre"
2. Sélectionner "Soldes débiteurs" uniquement
3. Cliquer sur "Appliquer"
4. Vérifier que seuls les comptes avec solde débiteur s'affichent
5. Tester avec d'autres filtres (créditeur, masqués, etc.)

**Résultat attendu** : Filtrage correct des données

---

### Test 5 : Recherche DataTables
**Objectif** : Vérifier la recherche intégrée

**Étapes** :
1. Utiliser le champ de recherche en haut à droite du tableau
2. Taper un numéro de compte ou un nom
3. Vérifier que seules les lignes correspondantes s'affichent
4. Effacer la recherche
5. Vérifier que toutes les lignes réapparaissent

**Résultat attendu** : Recherche fonctionne sur toutes les colonnes

---

### Test 6 : Actions d'édition (droits trésorier)
**Objectif** : Vérifier les actions d'édition et suppression

**Prérequis** : Se connecter avec un compte ayant le rôle "trésorier"

**Étapes** :
1. Développer une section
2. Vérifier que les icônes d'édition et suppression sont présentes sur les lignes détaillées
3. Cliquer sur l'icône d'édition d'un compte
4. Vérifier la redirection vers la page d'édition
5. Revenir à la balance

**Résultat attendu** : Actions disponibles uniquement pour les comptes détaillés, pas pour les sections générales

---

### Test 7 : Export CSV
**Objectif** : Vérifier l'export CSV hiérarchique

**Étapes** :
1. Sur la page balance hiérarchique
2. Cliquer sur le bouton "Excel" en bas
3. Ouvrir le fichier CSV téléchargé
4. Vérifier que les sections générales sont présentes
5. Vérifier que les comptes détaillés sont indentés (2 espaces au début)
6. Vérifier que les soldes sont corrects

**Résultat attendu** : Export CSV contient la structure hiérarchique

---

### Test 8 : Export PDF
**Objectif** : Vérifier l'export PDF hiérarchique

**Étapes** :
1. Sur la page balance hiérarchique
2. Cliquer sur le bouton "Pdf" en bas
3. Ouvrir le fichier PDF généré
4. Vérifier que les sections générales sont présentes
5. Vérifier que les comptes détaillés sont indentés
6. Vérifier la mise en page et les totaux

**Résultat attendu** : Export PDF lisible avec structure hiérarchique

---

### Test 9 : Filtrage par codec
**Objectif** : Vérifier les paramètres d'URL

**Étapes** :
1. Naviguer vers `http://gvv.net/comptes/balance/512`
2. Vérifier que seuls les comptes de classe 512 s'affichent
3. Naviguer vers `http://gvv.net/comptes/balance/5/6`
4. Vérifier que les comptes entre 5xx et 6xx s'affichent

**Résultat attendu** : Filtrage par codec fonctionne via URL

---

### Test 10 : Multi-langue
**Objectif** : Vérifier les traductions

**Étapes** :
1. Changer la langue en anglais (EN)
2. Vérifier que "Hierarchical Balance", "Expand All", "Collapse All" s'affichent
3. Changer la langue en néerlandais (NL)
4. Vérifier que "Hiërarchische balans", "Alles uitvouwen", "Alles inklappen" s'affichent
5. Revenir en français

**Résultat attendu** : Toutes les traductions sont présentes et correctes

---

### Test 11 : Date de balance
**Objectif** : Vérifier le changement de date

**Étapes** :
1. Changer la date dans le champ "Date"
2. Observer que les soldes se recalculent
3. Vérifier que la structure hiérarchique reste cohérente

**Résultat attendu** : Date de balance fonctionne correctement

---

### Test 12 : Droits en lecture seule
**Objectif** : Vérifier l'affichage pour un utilisateur sans droits de modification

**Prérequis** : Se connecter avec un compte NON trésorier

**Étapes** :
1. Naviguer vers la balance hiérarchique
2. Développer une section
3. Vérifier que les icônes d'édition/suppression ne sont PAS présentes
4. Vérifier que le bouton "Créer" n'est PAS présent

**Résultat attendu** : Vue en lecture seule sans actions de modification

---

## Vérifications techniques

### Syntaxe PHP
```bash
source setenv.sh
php -l application/controllers/comptes.php
php -l application/views/comptes/bs_balanceView.php
php -l application/language/french/comptes_lang.php
php -l application/language/english/comptes_lang.php
php -l application/language/dutch/comptes_lang.php
```

Résultat attendu : "No syntax errors detected" pour tous les fichiers

---

## Checklist de validation

- [ ] Affichage initial correct
- [ ] Développer/réduire une section fonctionne
- [ ] Boutons "Tout développer"/"Tout réduire" fonctionnent
- [ ] Filtres de solde fonctionnent
- [ ] Filtre des comptes masqués fonctionne
- [ ] Recherche DataTables fonctionne
- [ ] Actions d'édition visibles pour trésorier uniquement
- [ ] Actions d'édition absentes pour utilisateur normal
- [ ] Export CSV produit un fichier avec hiérarchie
- [ ] Export PDF produit un fichier avec hiérarchie
- [ ] Filtrage par codec via URL fonctionne
- [ ] Traductions françaises correctes
- [ ] Traductions anglaises correctes
- [ ] Traductions néerlandaises correctes
- [ ] Date de balance modifiable
- [ ] Totaux affichés correctement
- [ ] Style Bootstrap cohérent avec le reste de l'application
- [ ] Compatibilité : pages existantes (/general, /detail) toujours fonctionnelles

---

## Problèmes connus / Limitations

1. **DataTables paging désactivé** : Toutes les lignes sont affichées sur une seule page pour éviter les problèmes avec les lignes cachées/visibles.

2. **Recherche DataTables** : La recherche peut afficher des lignes détaillées même si leur section parente est réduite. C'est un comportement acceptable car l'utilisateur cherche probablement ce compte spécifique.

3. **Tri par colonnes** : Le tri peut mélanger les sections générales et les comptes détaillés. Si cela pose problème, il est possible de désactiver le tri.

---

## Notes pour le développeur

- Les exports CSV et PDF utilisent leurs propres fonctions (`balance_hierarchical_csv`, `balance_hierarchical_pdf`) pour ne pas affecter les exports existants
- La vue utilise un tableau HTML manuel au lieu de `gvvmetadata->table()` pour avoir un contrôle total sur les classes CSS et les événements JavaScript
- Les lignes générales et détaillées sont distinguées par les attributs `is_general` et `is_detail` dans le tableau de données
