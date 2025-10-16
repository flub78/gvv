# PRD : Amélioration de la Fonctionnalité Photo des Membres

**Produit :** GVV (Gestion Vol à Voile)
**Fonctionnalité :** Gestion Améliorée des Photos de Membres
**Version :** 1.0
**Statut :** Brouillon
**Créé :** 2025-10-16
**Auteur :** Product Owner / Club Admin

---

## 1. Résumé Exécutif

Ce PRD décrit les améliorations du système de photos des membres de GVV pour permettre l'upload, la modification et l'affichage optimal des photos de membres. Ces améliorations amélioreront le flux de travail des administrateurs de club et réduiront les besoins de stockage tout en améliorant l'expérience utilisateur avec des vignettes et des vues plein écran.

---

## 2. Contexte et Arrière-plan

### 2.1 État Actuel

L'application GVV inclut un système de photos de membres qui permet d'associer une photo à chaque membre. L'implémentation actuelle :

- **Base de Données :** Champ `photo` dans la table `membres` (VARCHAR(64))
- **Structure de Stockage :** `./uploads/` (racine, pas de sous-dossier)
- **Mécanisme d'upload :** Utilise `$this->gvvmetadata->upload("membres")` qui :
  - Cherche les champs avec `Subtype == "upload_image"`
  - Utilise la bibliothèque CodeIgniter Upload
  - Génère un nom de fichier crypté (`encrypt_name = TRUE`)
  - Limite : 4MB, types: zip|png|jpeg|jpg|gif
  - Stocke dans `./uploads/`

- **Affichage Actuel :**
  - Formulaire d'édition : Affiche l'image si elle existe (max-width: 200px)
  - Liste des membres : Le champ 'photo' est dans la liste mais ne semble pas rendu correctement
  - Bouton de suppression disponible
  - Upload uniquement en mode édition (pas en création)

### 2.2 Problèmes Identifiés

**P1 : Mécanisme d'Upload Non Fonctionnel**
- Le mécanisme actuel d'upload de photo ne fonctionne pas correctement
- L'upload utilise une méthode obsolète avec `encrypt_name` et des noms de fichiers non prévisibles
- Pas de validation appropriée
- Pas de gestion d'erreurs claire

**P2 : Organisation de Stockage Inadéquate**
- Photos stockées dans `./uploads/` (racine) mélangées avec d'autres fichiers
- Pas de structure organisée par année/membre
- Noms de fichiers cryptés non traçables
- Impossible de retrouver facilement les photos orphelines

**P3 : Inefficacité de Stockage**
- Photos stockées en taille complète sans compression
- Pas d'optimisation automatique des images
- Photos de smartphone (3-8MB) non optimisées
- Pas de redimensionnement

**P4 : Expérience Utilisateur Limitée**
- Pas de vignettes dans la liste des membres (trombinoscope incomplet)
- Pas de vue plein écran sur clic
- Photo visible seulement en mode édition
- Pas de prévisualisation avant upload

**P5 : Incohérence avec le Système d'Attachements**
- Les attachements utilisent une structure moderne : `./uploads/attachments/YYYY/SECTION/random_filename`
- Les attachements ont compression et optimisation
- Les photos de membres utilisent un système obsolète et différent

---

## 3. Objectifs et Buts

### 3.1 Objectifs Métier

1. **Moderniser le Système de Photos :** Aligner avec le système d'attachements moderne
2. **Réduire les Coûts de Stockage :** Obtenir une réduction de 70-85% du stockage des photos
3. **Améliorer l'Expérience Utilisateur :** Vignettes dans les listes, vue plein écran
4. **Maintenir la Qualité :** S'assurer que toutes les photos sont lisibles à l'écran et imprimables

### 3.2 Objectifs Utilisateur

**Administrateur de Club :**
- Uploader facilement une photo pour un membre
- Modifier/remplacer la photo d'un membre existant
- Voir une vignette dans la liste des membres
- Cliquer pour voir la photo en plein écran
- Confiance que les photos sont optimisées automatiquement

**Membre du Club :**
- Voir les photos des autres membres dans la liste (trombinoscope)
- Cliquer sur une vignette pour voir la photo en plein écran
- Expérience fluide et rapide

---

## 4. Utilisateurs Cibles et Personas

### Persona 1 : Claire - Administratrice de Club

**Contexte :**
- Âge : 48 ans, administratrice depuis 3 ans
- Gère les fiches des membres
- Souhaite un trombinoscope à jour pour les réunions

**Points de Douleur :**
- L'upload de photo ne fonctionne pas ou est très compliqué
- Ne voit pas facilement les photos dans la liste des membres
- Doit ouvrir chaque fiche pour voir la photo

**Résultat Souhaité :**
- Upload simple et fiable d'une photo pour chaque membre
- Voir toutes les photos en un coup d'œil dans la liste
- Cliquer pour agrandir quand nécessaire

### Persona 2 : Thomas - Membre du Club

**Contexte :**
- Âge : 35 ans, nouveau membre
- Veut connaître les autres membres du club

**Points de Douleur :**
- Pas de vue d'ensemble des membres avec photos
- Difficile de mettre des visages sur les noms

**Résultat Souhaité :**
- Voir les photos de tous les membres facilement
- Trombinoscope visuel clair et rapide

---

## 5. Exigences Fonctionnelles

### 5.1 EF1 : Réparer et Moderniser l'Upload de Photos (Priorité : CRITIQUE)

**Description :** Réparer le mécanisme d'upload de photos et l'aligner avec le système d'attachements moderne.

**User Stories :**
> En tant qu'administrateur de club, je veux uploader une photo pour un membre, afin que sa fiche soit complète et qu'il soit visible dans le trombinoscope.

> En tant qu'administrateur de club, je veux modifier la photo d'un membre, afin de la mettre à jour si nécessaire.

**Critères d'Acceptation :**
- CA1.1 : Les photos sont stockées dans `./uploads/photos/` (nouveau dossier dédié)
- CA1.2 : Format de nom de fichier : `random_6_digits` + `_` + `mlogin` + `.png`
  - Exemple : `847392_jdupont.png`
  - Le random empêche les collisions si le mlogin change
  - L'extension est toujours `.png` (format unifié après conversion)
- CA1.3 : Upload fonctionnel sur le formulaire d'édition de membre (`membre/edit`)
- CA1.4 : Le champ `photo` dans la table `membres` stocke uniquement le nom de fichier
- CA1.5 : Validation du fichier :
  - Types acceptés : jpg, jpeg, png, gif, webp
  - Taille maximale : 10MB
  - Messages d'erreur clairs en cas de problème
- CA1.6 : Bouton de suppression fonctionnel (déjà implémenté dans `membre/delete_photo`)
- CA1.7 : Remplacement de photo : supprime l'ancienne photo avant d'uploader la nouvelle

---

### 5.2 EF2 : Compression et Optimisation Automatique (Priorité : HAUTE)

**Description :** Compresser automatiquement les photos uploadées en utilisant le même système que les attachements.

**User Story :**
> En tant qu'administrateur système, je veux que les photos de membres soient automatiquement compressées, afin de réduire les besoins de stockage du serveur sans intervention manuelle.

**Critères d'Acceptation :**
- CA2.1 : Utilisation de la bibliothèque `File_compressor` (déjà existante)
- CA2.2 : Stratégie de compression pour photos :
  - Redimensionner aux dimensions maximales (1600x1200 pixels) tout en maintenant le ratio d'aspect
  - Compresser avec qualité 85 (JPEG)
  - Convertir tous les formats en PNG pour uniformité
- CA2.3 : Photos de smartphone (3-8MB) réduites à ~500KB-1MB
- CA2.4 : Compression journalisée dans `application/logs/` avec le format :
  ```
  INFO - Member photo compression: file=847392_jdupont.png, original=5.2MB (3000x2000), compressed=850KB (1600x1067), ratio=84%, method=gd/resize+png
  ```
- CA2.5 : Fichier original préservé si le ratio de compression < 10%
- CA2.6 : Qualité adaptée à l'affichage écran et à l'impression (300 DPI en A4 = ~1600x1200 pixels)

---

### 5.3 EF3 : Vignettes dans la Liste des Membres (Priorité : HAUTE)

**Description :** Afficher des vignettes de photos dans la liste des membres pour un trombinoscope visuel.

**User Stories :**
> En tant que membre du club, je veux voir une vignette de la photo de chaque membre dans la liste, afin de reconnaître visuellement les personnes.

> En tant qu'administrateur, je veux voir rapidement quels membres ont une photo et lesquels n'en ont pas, afin de compléter les fiches manquantes.

**Critères d'Acceptation :**
- CA3.1 : La colonne 'photo' dans `bs_tableView.php` affiche une vignette (50x50px ou 64x64px)
- CA3.2 : Si photo existe : affiche `<img src="uploads/photos/filename.png" class="member-thumbnail">`
- CA3.3 : Si pas de photo : affiche une icône par défaut (FontAwesome `fa-user` ou image placeholder)
- CA3.4 : Vignettes alignées verticalement dans la table
- CA3.5 : Style CSS pour les vignettes :
  - Bordure arrondie (`border-radius: 50%` pour effet cercle)
  - Dimensions fixes (50x50px ou 64x64px)
  - `object-fit: cover` pour maintenir le ratio
- CA3.6 : Performance : pas de ralentissement notable de la liste (images déjà compressées)

---

### 5.4 EF4 : Vue Plein Écran sur Clic (Priorité : MOYENNE)

**Description :** Permettre de cliquer sur une vignette pour voir la photo en plein écran.

**User Story :**
> En tant qu'utilisateur, je veux cliquer sur une vignette pour voir la photo en plein écran, afin de mieux voir les détails.

**Critères d'Acceptation :**
- CA4.1 : Clic sur vignette ouvre une modale Bootstrap 5 avec la photo en grande taille
- CA4.2 : Modale affiche la photo à sa taille optimisée (max 1600x1200)
- CA4.3 : Bouton de fermeture (X) en haut à droite
- CA4.4 : Clic à l'extérieur de la modale ferme la modale
- CA4.5 : Touche Échap ferme la modale
- CA4.6 : Affichage du nom du membre en bas de la modale
- CA4.7 : Navigation possible entre photos (flèches suivant/précédent) - optionnel
- CA4.8 : Compatible mobile : swipe pour fermer

---

## 6. Exigences Non Fonctionnelles

### 6.1 Performance
- L'upload de photo avec compression se termine en 2-3 secondes pour les fichiers <10MB
- Les vignettes dans la liste chargent instantanément (déjà compressées)
- La modale plein écran s'ouvre en <500ms

### 6.2 Stockage
- Réduction de 70-85% pour les photos typiques :
  - **Photos de smartphone (3-8MB) :** Réduction de 80-90% → 500KB-1MB
  - **Photos scannées (1-5MB) :** Réduction de 60-80%
  - **Petites photos (<500KB) :** Pas de compression
- Dossier dédié : `./uploads/photos/` clairement identifié
- Format unifié : tous en PNG après compression

### 6.3 Fiabilité
- Les échecs de compression reviennent au stockage du fichier original
- Fichier original préservé jusqu'à ce que la compression soit confirmée réussie
- Opérations de fichier atomiques (temp → permanent) pour prévenir la perte de données
- Migration des anciennes photos vers le nouveau format

### 6.4 Compatibilité
- Fonctionne avec PHP 7.4
- Compatible avec le framework CodeIgniter 2.x existant
- Pas de changements cassants au schéma de base de données
- Compatible en arrière avec les photos existantes
- Utilise Bootstrap 5 pour les modales

### 6.5 Utilisabilité
- Interface familière (même que les attachements)
- Messages d'erreur clairs en français
- Pas de formation requise pour les administrateurs
- Expérience intuitive pour voir les photos

---

## 7. Design Technique

### 7.1 Structure de Fichiers

```
/home/frederic/git/gvv/
├── uploads/
│   ├── photos/                    # Nouveau dossier pour les photos de membres
│   │   ├── 847392_jdupont.png
│   │   ├── 238475_mmartin.png
│   │   └── ...
│   └── ...
├── application/
│   ├── controllers/
│   │   └── membre.php             # Modification de formValidation()
│   ├── libraries/
│   │   ├── File_compressor.php    # Déjà existant, réutilisé
│   │   └── MetaData.php           # Modification de upload()
│   ├── helpers/
│   │   └── MY_html_helper.php     # Ajout d'une fonction pour vignettes
│   └── views/
│       └── membre/
│           ├── bs_formView.php     # Modification de l'upload
│           └── bs_tableView.php    # Ajout des vignettes
└── assets/
    ├── css/
    │   └── member_photos.css       # Nouveau : styles pour vignettes et modale
    └── js/
        └── member_photos.js         # Nouveau : JavaScript pour modale
```

### 7.2 Modifications du Contrôleur

**`application/controllers/membre.php`:**
- Modifier `formValidation()` pour :
  - Utiliser le nouveau dossier `uploads/photos/`
  - Générer un nom de fichier : `random(100000, 999999) . '_' . $mlogin . '.png'`
  - Appeler `File_compressor->compress()` après l'upload
  - Supprimer l'ancienne photo si elle existe
  - Gérer les erreurs clairement

### 7.3 Modifications des Vues

**`application/views/membre/bs_tableView.php`:**
- Modifier le rendu de la colonne 'photo' dans la métadonnée
- Afficher une vignette cliquable au lieu du texte

**`application/views/membre/bs_formView.php`:**
- Améliorer l'affichage de l'upload
- Ajouter une prévisualisation plus claire

### 7.4 Métadonnées

**`application/libraries/MetaData.php` ou `Gvvmetadata.php`:**
- Définir le champ 'photo' avec `Subtype = 'upload_image'` et `Display = 'thumbnail'`
- Créer une fonction de rendu spécifique pour les vignettes

---

## 8. Plan d'Implémentation

### Phase 1 : Réparer l'Upload (Priorité CRITIQUE)
1. Créer le dossier `uploads/photos/` avec permissions appropriées
2. Modifier `membre.php->formValidation()` :
   - Nouveau chemin de stockage
   - Nouveau format de nom de fichier
   - Intégration avec `File_compressor`
3. Tester l'upload sur un membre test
4. Vérifier la suppression de photo

**Estimation :** 3-4 heures
**Tests :** Upload, remplacement, suppression

### Phase 2 : Compression Automatique (Priorité HAUTE)
1. Intégrer `File_compressor` dans le workflow d'upload
2. Configurer les paramètres de compression pour photos (1600x1200, quality 85, format PNG)
3. Ajouter la journalisation
4. Tester avec différentes tailles d'images

**Estimation :** 2-3 heures
**Tests :** Photos smartphone, scannées, petites

### Phase 3 : Vignettes dans la Liste (Priorité HAUTE)
1. Modifier `bs_tableView.php` pour afficher des vignettes
2. Créer un helper pour le rendu des vignettes
3. Ajouter les styles CSS (bordures rondes, dimensions fixes)
4. Tester l'affichage de la liste

**Estimation :** 2-3 heures
**Tests :** Liste avec/sans photos, performance

### Phase 4 : Vue Plein Écran (Priorité MOYENNE)
1. Créer la modale Bootstrap 5 dans `bs_tableView.php`
2. Ajouter le JavaScript pour ouvrir/fermer la modale
3. Gérer les événements (clic, Échap, swipe)
4. Tester sur desktop et mobile

**Estimation :** 2-3 heures
**Tests :** Ouverture, fermeture, navigation

### Phase 5 : Migration des Photos Existantes (Si applicable)
1. Script pour migrer les anciennes photos vers `uploads/photos/`
2. Renommer selon le nouveau format
3. Compresser les photos existantes
4. Mettre à jour la base de données

**Estimation :** 1-2 heures
**Tests :** Vérification de la migration

---

## 9. Métriques de Succès

| Métrique | Actuel | Cible | Comment Mesurer |
|--------|---------|--------|----------------|
| Taux de succès d'upload | ~0% (non fonctionnel) | >95% | Tests d'upload |
| Taille moyenne des photos | N/A | 500KB-1MB | Analyse du dossier |
| Ratio de compression moyen | N/A | 70-85% | Journaux |
| Temps d'affichage de la liste | N/A | <2 secondes | Tests de performance |
| Satisfaction utilisateur | N/A | >85% satisfait | Enquête |

---

## 10. Risques et Atténuations

| Risque | Impact | Probabilité | Atténuation |
|------|--------|-------------|------------|
| La compression corrompt les photos | ÉLEVÉ | FAIBLE | Tests approfondis ; préserver l'original jusqu'à vérification |
| Migration des anciennes photos échoue | MOYEN | FAIBLE | Backup complet avant migration ; rollback possible |
| Performance de la liste dégradée | MOYEN | FAIBLE | Vignettes déjà compressées ; lazy loading si nécessaire |
| Problèmes de permissions sur uploads/photos/ | MOYEN | MOYEN | Documentation claire ; vérification dans le setup |

---

## 11. Hors Périmètre

Les éléments suivants sont explicitement hors périmètre pour cette version :

1. Upload de photo en mode création (reste uniquement en édition)
2. Crop/rotation d'image dans l'interface
3. Upload par webcam
4. Galerie de photos multiples par membre
5. Historique des photos
6. Reconnaissance faciale
7. Export du trombinoscope en PDF
8. Synchronisation avec sources externes (LDAP, etc.)

---

## 12. Questions Ouvertes

1. **Q :** Faut-il un placeholder par défaut (silhouette) pour les membres sans photo ?
   **R :** Oui, utiliser FontAwesome `fa-user` dans un cercle gris

2. **Q :** Faut-il afficher la date du dernier upload de photo ?
   **R :** Hors périmètre pour cette version

3. **Q :** Faut-il permettre l'upload de photo en mode création ?
   **R :** Non, reste uniquement en édition pour simplifier

4. **Q :** Format de compression : PNG ou JPEG ?
   **R :** PNG pour uniformité et qualité (déjà utilisé dans les exports)

---

## 13. Documents Associés

- **Plan d'Implémentation :** `doc/plans/member_photo_improvement_plan.md` (à créer)
- **PRD Attachements :** `doc/prds/attachments_improvement_prd.md` (référence)
- **Bibliothèque de Compression :** `application/libraries/File_compressor.php`
- **Flux de Développement :** `doc/development/workflow.md`
- **Instructions Projet :** `CLAUDE.md`

---

## 14. Approbation et Validation

| Rôle | Nom | Signature | Date |
|------|------|-----------|------|
| Product Owner | [À déterminer] | | |
| Administrateur Club | [À déterminer] | | |
| Développeur | [À déterminer] | | |

---

**Fin du PRD**
