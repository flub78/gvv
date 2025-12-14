# Contrôleur Oneshot - Opérations ponctuelles sur la base de données

## Description

Le contrôleur `Oneshot` est un contrôleur réservé aux administrateurs pour effectuer des opérations ponctuelles (one-shot) sur la base de données. Ces opérations sont généralement destinées à l'analyse ou à la correction ponctuelle de données.

**Fichier:** `application/controllers/oneshot.php`

## Sécurité

- ✅ Réservé aux administrateurs uniquement
- ✅ Vérification de connexion dans le constructeur
- ✅ Vérification du rôle admin via `$this->dx_auth->is_admin()`
- ✅ Protection XSS avec `htmlspecialchars()`
- ✅ Requêtes SQL via Query Builder CodeIgniter

## URL d'accès

**Base:** `http://gvv.net/index.php/oneshot`

### Page d'index

`http://gvv.net/index.php/oneshot`

Liste toutes les méthodes disponibles avec liens directs.

## Méthodes disponibles

### 1. `cotisation_775_766()`

**URL:** `http://gvv.net/index.php/oneshot/cotisation_775_766`

Affiche les écritures entre les comptes 775 et 766 (dans les deux sens).

### 2. `cotisation_776_766()`

**URL:** `http://gvv.net/index.php/oneshot/cotisation_776_766`

Affiche les écritures entre les comptes 776 et 766 (dans les deux sens).

### 3. `regulariser_initialisations_2024()`

**URL:** `http://gvv.net/index.php/oneshot/regulariser_initialisations_2024`

Prévisualise puis exécute la régularisation des écritures d'initialisation 2024 (ULM/Avion/Services généraux) impliquant 102 contre comptes de charges/produits :
- remplace 102 par 512 (30/12/2024),
- passe une compensation 512/102 (30/12/2024) pour neutraliser la banque,
- clôture le compte 6xx/7xx contre 102 (31/12/2024),
- supprime l'écriture initiale.

## Affichage

Pour chaque méthode, l'affichage comprend:

1. **Tableau HTML** avec:
   - ID de l'écriture
   - Date d'opération
   - Compte 1 (code + nom)
   - Compte 2 (code + nom)
   - Description
   - Montant
   - Référence (num_cheque)
   - État gel
   - **Ligne de total** avec somme des montants

2. **Var_dump complet** pour analyse détaillée de toutes les données

3. **Liens de navigation** vers l'index et l'accueil

## Méthode utilitaire privée

### `afficher_ecritures_entre_comptes($compte_id1, $compte_id2, $titre)`

Méthode privée réutilisable qui:
- Récupère les écritures entre deux comptes (dans les deux sens)
- Effectue des jointures avec la table `comptes` pour obtenir noms et codes
- Trie par date décroissante
- Affiche le tableau HTML et le var_dump
- Calcule le total des montants

**Paramètres:**
- `$compte_id1` (int): ID du premier compte
- `$compte_id2` (int): ID du second compte
- `$titre` (string): Titre de la page

**Requête SQL générée:**
```sql
SELECT ecritures.*,
       c1.nom as compte1_nom, c1.codec as compte1_codec,
       c2.nom as compte2_nom, c2.codec as compte2_codec
FROM ecritures
LEFT JOIN comptes as c1 ON ecritures.compte1 = c1.id
LEFT JOIN comptes as c2 ON ecritures.compte2 = c2.id
WHERE (compte1 = $compte_id1 AND compte2 = $compte_id2)
   OR (compte1 = $compte_id2 AND compte2 = $compte_id1)
ORDER BY date_op DESC
```

## Ajout de nouvelles méthodes

Pour ajouter une nouvelle opération one-shot:

1. Créer une nouvelle méthode publique dans le contrôleur
2. Ajouter le lien dans la méthode `index()`
3. Pour analyser des écritures entre comptes, réutiliser `afficher_ecritures_entre_comptes()`
4. Pour d'autres opérations, créer une méthode personnalisée

**Exemple:**
```php
function nouvelle_operation() {
    // Votre code ici
    // Utiliser echo ou var_dump pour l'affichage
}
```

## Modèles utilisés

- `ecritures_model` - Gestion des écritures comptables
- `comptes_model` - Gestion des comptes

Ces modèles sont chargés automatiquement dans le constructeur.

## Notes importantes

⚠️ **ATTENTION:**
- Ces méthodes ne doivent être exécutées qu'occasionnellement
- Ne pas utiliser en production régulière
- Toujours vérifier les résultats avant de faire des modifications
- Conserver une sauvegarde avant toute modification de données

## Date de création

16 novembre 2025
