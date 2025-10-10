# PRD : Amélioration de la Fonctionnalité des Pièces Jointes

**Produit :** GVV (Gestion Vol à Voile)
**Fonctionnalité :** Gestion Améliorée des Pièces Jointes
**Version :** 1.0
**Statut :** Brouillon
**Créé :** 2025-10-09
**Auteur :** Product Owner / Exigences du Trésorier

---

## 1. Résumé Exécutif

Ce PRD décrit les améliorations du système de pièces jointes de GVV pour permettre la création de pièces jointes en ligne lors de la saisie de lignes comptables et la compression automatique des fichiers pour l'optimisation du stockage. Ces améliorations amélioreront le flux de travail du trésorier et réduiront les besoins de stockage sans sacrifier la qualité des documents.

---

## 2. Contexte et Arrière-plan

### 2.1 État Actuel

L'application GVV inclut un système de pièces jointes qui permet aux utilisateurs d'associer des fichiers (factures, reçus, contrats, etc.) à divers enregistrements de base de données. L'implémentation actuelle :

- **Base de Données :** Table `attachments` avec champs :
  - `id` (BIGINT, clé primaire)
  - `referenced_table` (VARCHAR, ex. 'ecritures')
  - `referenced_id` (VARCHAR, clé étrangère vers l'enregistrement référencé)
  - `user_id` (VARCHAR)
  - `filename` (VARCHAR, nom de fichier original)
  - `description` (VARCHAR)
  - `file` (VARCHAR, chemin vers le fichier téléchargé)
  - `club` (TINYINT, référence section/club)
  - `file_backup` (VARCHAR, chemin de sauvegarde après migration 039)

- **Structure de Stockage :** `./uploads/attachments/YYYY/SECTION/random_filename`
  - Fichiers organisés par année et section (ex. ULM, Avion, Planeur, Général)
  - Migration récente (039) a réorganisé les fichiers en sous-répertoires basés sur les sections

- **Flux de Travail Actuel :**
  1. Créer une ligne comptable (écritures) dans le contrôleur `compta`
  2. Sauvegarder la ligne comptable pour obtenir un ID
  3. Éditer la ligne comptable sauvegardée
  4. Cliquer sur "Ajouter Pièce Jointe" dans la section des pièces jointes 
  5. Télécharger les fichiers via le formulaire séparé `attachments/create`

- **Fichiers Clés :**
  - Contrôleur : `application/controllers/attachments.php`
  - Modèle : `application/models/attachments_model.php`
  - Vues : `application/views/attachments/bs_formView.php`, `bs_tableView.php`
  - Intégration : `application/controllers/compta.php` (lignes 46-48, 82-86)
  - Helper : `application/helpers/MY_html_helper.php` (fonction `attachment()`)

### 2.2 Problèmes Identifiés

**P1 : Inefficacité du Flux de Travail (Trésorier)**
- Impossible d'attacher des documents lors de la création initiale de la ligne comptable
- Nécessite un processus en deux étapes : créer la ligne → éditer la ligne → attacher les fichiers
- Brise le flux de travail naturel de saisie de données
- Résulte en pièces jointes oubliées ou téléchargements retardés

**P2 : Inefficacité de Stockage (Administrateur Système)**
- Tous les fichiers stockés en taille complète sans compression
- Stockage redondant de gros fichiers (factures, reçus scannés)
- Pas d'optimisation automatique des images
- Pas de suivi des économies de stockage
- Pièces jointes historiques (~300MB actuellement) occupant un espace inutile

**P3 : Limitation Technique**
- Le téléchargement de pièce jointe nécessite un `referenced_id` (clé étrangère)
- Cet ID n'existe qu'*après* la sauvegarde de la ligne comptable
- Pas de mécanisme pour les pièces jointes "en attente" attendant la création de l'enregistrement parent

---

## 3. Objectifs et Buts

### 3.1 Objectifs Métier

1. **Améliorer la Productivité du Trésorier :** Réduire de 50% le temps passé à gérer les pièces jointes des lignes comptables
2. **Réduire les Coûts de Stockage :** Obtenir une réduction de 30-50% du stockage des pièces jointes grâce à la compression
3. **Maintenir la Qualité des Documents :** S'assurer que tous les fichiers compressés restent imprimables et lisibles à l'écran
4. **Transparence :** Fournir une visibilité sur l'efficacité de la compression via la journalisation

### 3.2 Objectifs Utilisateur

**Trésorier :**
- Attacher les scans de factures lors de la saisie des lignes comptables (flux de travail unique)
- Photographier les documents avec leur smartphone pour créer des pièces jointes directement (déjà supporté)
- Récupérer rapidement les documents attachés lors de la révision des entrées passées
- Confiance que les documents sont préservés et accessibles

**Administrateur Système :**
- Surveiller l'utilisation du stockage et l'efficacité de la compression
- S'assurer que la compression des fichiers se fait automatiquement
- Maintenir les performances du système
- Récupérer l'espace de stockage des pièces jointes historiques

---

## 4. Utilisateurs Cibles et Personas

### Persona 1 : Marie - Trésorière de Club

**Contexte :**
- Âge : 52 ans, trésorière depuis 5 ans
- Utilise GVV chaque semaine pour la saisie comptable
- Pas très technique mais à l'aise avec les formulaires web
- Entre souvent 10-20 transactions par session avec documents justificatifs

**Points de Douleur :**
- Doit scanner les factures, sauvegarder la ligne comptable, puis revenir pour les attacher
- Oublie parfois d'attacher les documents jusqu'au moment de l'audit
- Trouve le processus en deux étapes frustrant et chronophage

**Résultat Souhaité :**
- Télécharger les PDFs/images de factures directement lors de la création de la ligne comptable
- Photographier les factures et reçus avec son smartphone pour les joindre directement (déjà supporté)
- Voir une confirmation immédiate que les fichiers sont attachés
- Accès rapide aux documents attachés précédemment

### Persona 2 : Jean - Administrateur Système

**Contexte :**
- Âge : 45 ans, gère l'infrastructure IT du club
- Surveille l'utilisation du disque serveur
- Préoccupé par les besoins croissants de stockage de fichiers

**Points de Douleur :**
- Le dossier des pièces jointes croît rapidement (actuellement ~300MB)
- Les scans haute résolution consomment un espace inutile
- Pas de nettoyage ou d'optimisation automatique
- Les pièces jointes historiques occupent de l'espace sans moyen de les compresser

**Résultat Souhaité :**
- Compression automatique des fichiers téléchargés
- Journaux montrant les ratios de compression
- Capacité d'ajuster les paramètres de compression si nécessaire
- Capacité de compresser par lot les pièces jointes existantes pour récupérer l'espace

---

## 5. Exigences Fonctionnelles

### 5.1 EF1 : Pièce Jointe en Ligne lors de la Création (Priorité : HAUTE)

**Description :** Permettre aux utilisateurs de télécharger des fichiers de pièces jointes lors de la création de la ligne comptable, avant que l'enregistrement ne soit sauvegardé et reçoive un ID.

**User Story :**
> En tant que trésorier, je veux attacher les scans de factures pendant que je saisis une ligne comptable, afin de pouvoir compléter toute la saisie de données en une seule session sans basculer entre les formulaires.

**Critères d'Acceptation :**
- CA1.1 : Contrôle de téléchargement de pièce jointe visible sur le formulaire de création de ligne comptable (`compta/create`)
- CA1.2 : L'utilisateur peut sélectionner un ou plusieurs fichiers à télécharger
- CA1.3 : Les fichiers sont téléchargés immédiatement vers un emplacement de stockage temporaire
- CA1.4 : Lors de la soumission du formulaire (sauvegarde ligne comptable) :
  - Si la sauvegarde réussit : associer les pièces jointes au nouvel ID de ligne comptable et déplacer vers le stockage permanent
  - Si la sauvegarde échoue : conserver les fichiers temporaires pour re-soumission du formulaire
- CA1.5 : L'utilisateur peut retirer les fichiers téléchargés avant la soumission finale
- CA1.6 : Le flux de travail d'édition existant continue de fonctionner sans changement

---

### 5.2 EF2 : Compression Automatique des Fichiers (Priorité : HAUTE)

**Description :** Compresser automatiquement les fichiers téléchargés lorsque la compression offre des économies de stockage significatives, tout en maintenant la qualité des documents.

**User Stories :**
> En tant qu'administrateur système, je veux que les pièces jointes téléchargées soient compressées automatiquement, afin de réduire les besoins de stockage du serveur sans intervention manuelle.

> En tant qu'administrateur, je veux compresser les pièces jointes déjà téléchargées pour économiser de l'espace, afin de récupérer l'espace de stockage des fichiers historiques sans perdre de données.

**Critères d'Acceptation :**
- CA2.1 : Le système analyse le type et la taille du fichier lors du téléchargement
- CA2.2 : Stratégie de compression basée sur le type de fichier :
  - **Images (JPEG, PNG, GIF, BMP, WebP) :**
    - Redimensionner aux dimensions maximales (1600x1200 pixels) tout en maintenant le ratio d'aspect
    - Convertir au format JPEG avec qualité 85
    - Appliquer la compression gzip au fichier résultant
    - Stocker comme `filename.jpg.gz`
  - **Tous les autres fichiers (PDF, DOCX, CSV, TXT, etc.) :**
    - Compresser en utilisant la fonction PHP `gzencode()` (niveau 9)
    - Pas de conversion de format
    - Stocker comme `filename.ext.gz` (ex. `invoice.pdf.gz`)
- CA2.3 : Fichier original préservé si le ratio de compression < 10% ou la taille du fichier < 100KB
- CA2.4 : Ratio de compression journalisé dans `application/logs/` avec le format :
  ```
  INFO - Attachment compression: file=invoice.pdf, original=2.5MB, compressed=450KB, ratio=82%, method=gzip
  INFO - Attachment compression: file=scan.jpg, original=5.2MB (3000x2000), compressed=850KB (1600x1067), ratio=84%, method=gd+gzip
  ```
- CA2.5 : Fichiers décompressés et servis de manière transparente lors du téléchargement
- CA2.6 : Résolution d'image adaptée à l'impression (300 DPI en A4 = ~1600x1200 pixels)
- CA2.7 : Photos de smartphone automatiquement optimisées (typiquement 3-8MB → 500KB-1MB)

---

### 5.3 EF3 : Compression par Lot des Pièces Jointes Existantes (Priorité : MOYENNE)

**Description :** Fournir un script CLI pour que les administrateurs puissent compresser les pièces jointes déjà téléchargées.

**User Story :**
> En tant qu'administrateur, je veux compresser les pièces jointes déjà téléchargées pour économiser de l'espace, afin de récupérer l'espace de stockage des fichiers historiques sans perdre de données.

**Critères d'Acceptation :**
- CA3.1 : Script CLI disponible : `scripts/batch_compress_attachments.php`
- CA3.2 : Support du mode dry-run pour tester sans changements
- CA3.3 : Support du filtrage par année, section, type de fichier, taille minimale
- CA3.4 : Affiche une barre de progression et le temps restant estimé
- CA3.5 : Génère un rapport détaillé des résultats de compression
- CA3.6 : Support de la reprise en cas d'interruption
- CA3.7 : Sauvegarde les fichiers originaux avant compression
- CA3.8 : Annule en cas d'échec de compression
- CA3.9 : Journalise toutes les opérations avec statistiques de compression
- CA3.10 : Les trésoriers peuvent toujours voir ou télécharger les pièces jointes précédentes

---

### 5.4 EF4 : Décompression Transparente (Priorité : aussi HAUTE que la compression de fichiers)

**Description :** Décompresser automatiquement les fichiers lorsqu'ils sont affichés ou téléchargés.

**User Story :**
> En tant que trésorier, je veux voir ou télécharger les pièces jointes dans leur format original utilisable, sans avoir besoin de savoir qu'elles ont été compressées pour le stockage.

**Critères d'Acceptation :**
- CA4.1 : Quand l'utilisateur clique sur le lien de pièce jointe, le système détecte si le fichier est compressé (extension `.gz`)
- CA4.2 : Si compressé, décompresser à la volée en utilisant PHP `gzdecode()` avant de servir
- CA4.3 : Nom de fichier original restauré (retirer l'extension `.gz`)
  - Images : Servir comme `filename.jpg` (format converti)
  - Autres fichiers : Servir avec l'extension originale (ex. `invoice.pdf`)
- CA4.4 : En-tête Content-Type correspond au format de fichier servi
- CA4.5 : Pas d'indication dans l'interface que le fichier a été compressé ou redimensionné
- CA4.6 : Performance de téléchargement/visualisation acceptable (<2 secondes de délai pour les fichiers jusqu'à 20MB)
- CA4.7 : Les utilisateurs ignorent que les photos de smartphone ont été redimensionnées (optimisation transparente)

---

## 6. Exigences Non Fonctionnelles

### 6.1 Performance
- Le téléchargement de fichier avec compression se termine en 3 secondes pour les fichiers <10MB
  - Compression d'image (redimensionnement + gzip) : 1-2 secondes
  - Compression de document (gzip seulement) : 0.5-1 seconde
- Les opérations de compression utilisent du PHP pur (pas de processus externes)
- Le nettoyage des fichiers temporaires s'exécute quotidiennement sans impacter les performances système
- La décompression au téléchargement ajoute <1 seconde au temps de service de fichier (opération en mémoire)
- La compression par lot traite ~30-50 fichiers par minute (selon les types et tailles de fichiers)

### 6.2 Stockage
- Obtenir une réduction globale du stockage de 40-70% pour le flux de travail typique du trésorier :
  - **Photos de smartphone (3-8MB) :** Réduction de 80-90% → 500KB-1MB
  - **Images scannées (1-5MB) :** Réduction de 60-80%
  - **Fichiers texte (TXT, CSV) :** Réduction de 80-95%
  - **Documents bureautiques (PDF, DOCX, XLSX) :** Réduction de 10-40%
  - **Déjà compressés (ZIP, RAR) :** Passer la compression
- Stockage de fichiers temporaires plafonné à 500MB
- Nettoyage automatique des fichiers temporaires abandonnés après 24 heures

### 6.3 Fiabilité
- Les échecs de compression reviennent au stockage du fichier original
- Fichier original préservé jusqu'à ce que la compression soit confirmée réussie
- Opérations de fichier atomiques (temp → permanent) pour prévenir la perte de données
- Capacité d'annulation si la compression corrompt le fichier

### 6.4 Compatibilité
- Fonctionne avec PHP 7.4
- Compatible avec le framework CodeIgniter 2.x existant
- Pas de changements cassants au schéma de base de données
- Compatible en arrière avec les pièces jointes non compressées existantes
- Supporte les flux de travail existants sans modification

### 6.5 Utilisabilité
- Pas de changement à la complexité de l'interface utilisateur
- Le téléchargement en ligne ressemble à un flux de travail unique
- Pas de formation requise pour les utilisateurs existants
- Messages d'erreur clairs si le téléchargement/compression échoue

---

## 7. Métriques de Succès

| Métrique | Actuel | Cible | Comment Mesurer |
|--------|---------|--------|----------------|
| Temps moyen pour créer une ligne comptable avec pièce jointe | ~3 min (créer + éditer + attacher) | <1 min (formulaire unique) | Chronométrage du flux de travail utilisateur |
| Utilisation du stockage pour les pièces jointes | 100% (pas de compression) | 30-50% | `du -sh uploads/attachments/` |
| Ratio de compression moyen | N/A | 50-70% (avec optimisation d'image) | Analyse des journaux |
| Taille de photo de smartphone | 3-8 MB | 500KB-1MB | Analyse des journaux |
| Pièces jointes oubliées | ~10% des entrées | <2% | Audit des lignes comptables |
| Satisfaction du trésorier | Enquête de base | >80% satisfait | Enquête utilisateur |

---

## 8. Risques et Atténuations

| Risque | Impact | Probabilité | Atténuation |
|------|--------|-------------|------------|
| La compression corrompt les fichiers | ÉLEVÉ | FAIBLE | Préserver l'original jusqu'à vérification ; tests approfondis |
| Le stockage de fichiers temp remplit le disque | MOYEN | MOYEN | Limites de taille strictes et nettoyage 24h |
| L'approche basée session échoue au redémarrage | MOYEN | FAIBLE | Stocker les métadonnées de fichier temp dans la base de données |
| La compression d'image dégrade la qualité | MOYEN | MOYEN | Paramètres de qualité configurables ; test A/B avec utilisateurs |
| La compression PDF casse les fonctionnalités | ÉLEVÉ | MOYEN | Détecter les fonctionnalités PDF avant compression ; passer si risqué |
| Impact performance lors du téléchargement | FAIBLE | MOYEN | Rendre la compression asynchrone ; indicateur de progression |
| L'utilisateur retire accidentellement des fichiers | FAIBLE | ÉLEVÉ | Ajouter dialogue de confirmation ; permettre annulation |

---

## 9. Dépendances et Prérequis

### 9.1 Exigences Système

**Extensions PHP (Requises) :**
- `zlib` (pour compression/décompression gzip) - Habituellement activé par défaut dans PHP 7.4
- `gd` (pour redimensionnement et optimisation d'images) - Disponible sur le serveur de production

**Commandes de Vérification :**
```bash
php7.4 -m | grep -E 'zlib|gd'
```

**Sortie Attendue :**
```
gd
zlib
```

**Pas d'Outils Externes Requis :**
- ✅ Pas besoin de Ghostscript
- ✅ Pas besoin de CLI ImageMagick
- ✅ Pas besoin de LibreOffice
- ✅ Implémentation PHP pure utilisant uniquement les extensions intégrées

### 9.2 Prérequis de Configuration

- S'assurer que `uploads/attachments/temp/` est accessible en écriture (0777 pendant le développement)
- PHP `upload_max_filesize` configuré de manière appropriée (actuellement 20MB)
- PHP `post_max_size` suffisant pour plusieurs fichiers
- `max_file_uploads` défini à 20 ou plus

---

## 10. Hors Périmètre

Les éléments suivants sont explicitement hors périmètre pour cette version :

1. Interface de téléchargement de fichiers par glisser-déposer
2. Aperçu/miniatures d'images avant téléchargement (existe déjà pour les images et photos)
3. OCR pour documents scannés
4. Versionnage des pièces jointes
5. Partage de pièces jointes entre plusieurs lignes comptables
6. Intégration de stockage cloud (S3/Google Drive)
7. Suppression automatique des anciennes pièces jointes
8. Compression par lot planifiée/automatisée (tâche cron)
9. Tableau de bord d'analytique de compression
10. Téléchargement de pièce jointe en ligne pour d'autres contrôleurs (au-delà de `compta`)

## 11. Améliorations Futures

Les fonctionnalités suivantes pourraient être ajoutées dans de futures itérations :

### 11.1 Paramètres de Qualité d'Image Configurables

**Description :** Permettre aux administrateurs de configurer les paramètres de compression d'images

**Paramètres Potentiels :**
- Dimensions maximales d'image (par défaut : 1600x1200)
- Qualité JPEG (par défaut : 85)
- Profils différents pour reçus vs photos
- Option pour préserver la résolution originale pour des types de fichiers spécifiques

**Estimation d'Effort :** 2-4 heures

**Priorité :** FAIBLE - Les paramètres par défaut devraient fonctionner pour la plupart des cas d'usage

---

## 12. Questions Ouvertes

1. **Q :** Devons-nous fournir une option "désactiver la compression" par type de fichier ?
   **R :** À déterminer - Recueillir les retours utilisateurs après le déploiement initial

2. **Q :** La compression devrait-elle être synchrone ou asynchrone ?
   **R :** Commencer avec synchrone ; passer à asynchrone si des problèmes de performance surviennent

3. **Q :** Le fichier original doit-il être conservé de manière permanente comme sauvegarde ?
   **R :** Non, les économies de stockage sont l'objectif principal ; s'appuyer sur les sauvegardes base de données/fichiers

4. **Q :** Comment gérer la compression pour d'autres tables référencées (pas seulement `ecritures`) ?
   **R :** Le téléchargement de pièce jointe en ligne peut être généralisé à d'autres contrôleurs plus tard

5. **Q :** La compression par lot doit-elle s'exécuter automatiquement selon un planning ?
   **R :** Hors périmètre pour la version initiale ; l'administrateur peut l'exécuter manuellement ou configurer une tâche cron

6. **Q :** Combien de temps les fichiers de sauvegarde de compression par lot doivent-ils être conservés ?
   **R :** À déterminer - Recommandation de 7 jours avec période de rétention configurable

---

## 13. Documents Associés

- **Plan d'Implémentation :** `doc/plans/attachments_improvement_plan.md` (design, architecture, détails d'implémentation)
- **Documentation Système Actuel :** `doc/plans/attachments_directory_reorganization.md` (migration 039)
- **Flux de Développement :** `doc/development/workflow.md`
- **Contexte Projet :** `CLAUDE.md`, `.github/copilot-instructions.md`

---

## 14. Approbation et Validation

| Rôle | Nom | Signature | Date |
|------|------|-----------|------|
| Product Owner | [À déterminer] | | |
| Trésorier (Représentant Utilisateur) | [À déterminer] | | |
| Administrateur Système | [À déterminer] | | |
| Développeur | [À déterminer] | | |

---

**Fin du PRD**
