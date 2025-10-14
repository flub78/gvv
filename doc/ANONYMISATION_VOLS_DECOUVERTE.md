# Fonctions d'anonymisation des vols de découverte

## Vue d'ensemble

Deux nouvelles fonctions ont été ajoutées au contrôleur `vols_decouverte.php` pour permettre l'anonymisation des données personnelles des vols de découverte.

## Fonctions ajoutées

### 1. `anonymize($id)`

**URL d'accès :** `/vols_decouverte/anonymize/[ID]`

**Description :** Anonymise un vol de découverte spécifique en remplaçant les données personnelles par des valeurs aléatoires.

**Paramètres :**
- `$id` : ID du vol de découverte à anonymiser

**Champs anonymisés :**
- `beneficiaire` : Remplacé par un nom complet aléatoire (prénom + nom)
- `de_la_part` : Remplacé aléatoirement par "", "Ses enfants", "Son épouse", "Son assureur", "Ses parents"
- `occasion` : Remplacé aléatoirement par "", "Son anniversaire", "Son mariage", "Sa retraite"
- `beneficiaire_email` : Généré automatiquement à partir du nom (ex: jean.martin@gmail.com)
- `beneficiaire_tel` : Numéro de téléphone français aléatoire (format 0X XX XX XX XX)
- `urgence` : Contact d'urgence aléatoire avec nom et téléphone

**Sécurité :** Nécessite les droits 'ca' (Conseil d'Administration)

### 2. `anonymize_all()`

**URL d'accès :** `/vols_decouverte/anonymize_all`

**Description :** Anonymise TOUS les vols de découverte de la base de données.

**Paramètres :** Aucun

**Fonctionnement :** Applique la même logique d'anonymisation que `anonymize($id)` à tous les enregistrements de la table `vols_decouverte`.

**Sécurité :** Nécessite les droits 'ca' (Conseil d'Administration)

## Données générées

### Prénoms utilisés
Jean, Marie, Pierre, Sophie, Michel, Catherine, Philippe, Isabelle, François, Nathalie, Patrick, Sylvie, Alain, Martine, Bernard, Monique, André, Françoise, Daniel, Christine

### Noms de famille utilisés
Martin, Bernard, Durand, Moreau, Laurent, Simon, Michel, Lefebvre, Leroy, Roux, David, Bertrand, Morel, Fournier, Girard, Bonnet, Dupont, Lambert, Fontaine, Rousseau

### Domaines email utilisés
gmail.com, yahoo.fr, orange.fr, free.fr, hotmail.com, outlook.fr, laposte.net

### Préfixes téléphoniques
01, 02, 03, 04, 05, 06, 07, 09

## Exemples d'utilisation

### Anonymiser un vol spécifique
```
GET /vols_decouverte/anonymize/23001
```

### Anonymiser tous les vols
```
GET /vols_decouverte/anonymize_all
```

## Notes importantes

1. **Irréversible :** L'anonymisation est définitive, les données originales sont perdues
2. **Sécurité :** Seuls les utilisateurs avec le rôle 'ca' peuvent exécuter ces fonctions
3. **Données réalistes :** Les données générées sont cohérentes (emails dérivés des noms, téléphones français valides)
4. **Performance :** `anonymize_all()` peut prendre du temps sur de grandes bases de données (1067 enregistrements dans le cas actuel)
5. **Correction appliquée :** Utilisation correcte de la méthode `update()` du Common_Model avec le nom de la colonne clé

## Implémentation technique

- Utilise `array_rand()` pour la sélection aléatoire
- Génère des emails cohérents en dérivant du nom complet
- Respecte les formats français pour les numéros de téléphone
- Utilise le modèle existant (`$this->gvv_model->update('id', $data_update)`) pour les mises à jour
- Affiche des messages de confirmation via le système de messages GVV
- **Format de mise à jour :** `$data_update` contient `'id' => $id` plus les champs à mettre à jour