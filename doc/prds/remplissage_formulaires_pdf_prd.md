# PRD — Remplissage de Formulaires PDF

Date : 3 février 2026

## Contexte

Les clubs ULM et de vol à voile doivent régulièrement remplir des formulaires administratifs officiels (DGAC, FFPLUM, etc.) pour leurs pilotes : attestations de début de formation, déclarations d'aptitude, renouvellements de licence, etc.

Actuellement, ces formulaires sont remplis manuellement, ce qui est :
- Fastidieux et source d'erreurs de saisie
- Redondant (les données existent déjà dans GVV)
- Chronophage pour les instructeurs et administrateurs

L'objectif est de permettre le remplissage automatique de formulaires PDF à partir des données existantes dans GVV.

## Objectifs

- Permettre l'analyse automatique de formulaires PDF pour identifier les champs éditables.
- Permettre la définition manuelle d'un mapping entre les champs d'un formulaire et les données GVV.
- Permettre la génération de PDF remplis à partir des données de la base.
- Permettre l'archivage des documents générés dans le système documentaire.

## Non-objectifs

- Création de nouveaux formulaires PDF (seulement remplissage de formulaires existants).
- Signature électronique des documents (hors scope, pourra être ajouté ultérieurement).
- Reconnaissance automatique des champs sans formulaire AcroForm (OCR).
- Envoi automatique des formulaires aux administrations.

## Portée

### Inclus

- Upload de formulaires PDF (templates) par les administrateurs.
- Extraction automatique des champs éditables d'un formulaire PDF.
- Interface de mapping entre champs PDF et sources de données GVV.
- Génération de PDF remplis avec les données d'un pilote.
- Téléchargement des PDF générés.
- Archivage dans le module `archived_documents` (historique et consultation intégrés).

### Exclu

- Modification de la structure des formulaires PDF.
- Formulaires non-AcroForm (formulaires XFA, PDF scannés).
- Workflow d'approbation des documents générés.

## Personae & rôles

- **Administrateur** : peut uploader des templates, définir les mappings, générer des documents pour tous les pilotes.
- **Instructeur** : peut générer des documents pour ses élèves.
- **Pilote** : peut consulter et télécharger les documents le concernant (si archivés).

## Parcours clés

### Parcours 1 : Configuration d'un nouveau formulaire (Administrateur)

1. L'administrateur accède à la gestion des formulaires PDF.
2. Il uploade un nouveau formulaire PDF.
3. Le système analyse le PDF et affiche la liste des champs éditables.
4. L'administrateur définit le mapping pour chaque champ :
   - Source de données (table/colonne, configuration, constante, date)
   - Contexte (candidat, instructeur, etc.)
   - Format de sortie si nécessaire
5. L'administrateur enregistre le template avec son mapping.
6. Le template est disponible pour la génération.

### Parcours 2 : Génération d'un document (Instructeur)

1. L'instructeur accède à la génération de formulaires.
2. Il sélectionne le type de formulaire (ex: "Attestation début de formation ULM").
3. Il sélectionne le pilote concerné selon les contextes requis.
4. Il visualise un aperçu des données qui seront utilisées.
5. Il génère le PDF.
6. Le PDF est archivé automatiquement dans `archived_documents` et peut être téléchargé immédiatement.

### Parcours 3 : Consultation d'un document archivé (Pilote)

1. Le pilote accède à ses documents via le module `archived_documents`.
2. Il voit les formulaires générés le concernant (type `formulaire_pdf`).
3. Il peut télécharger les documents.

## Exigences fonctionnelles

### EF1 : Gestion des templates

1. Le système doit permettre l'upload de fichiers PDF.
2. Le système doit détecter si le PDF contient des champs de formulaire (AcroForm).
3. Le système doit extraire automatiquement la liste des champs avec leur nom et type.
4. Le système doit rejeter les PDF sans champs de formulaire avec un message explicatif.
5. Le système doit permettre de nommer et décrire chaque template.

### EF2 : Mapping des champs

1. Le système doit afficher tous les champs extraits du PDF.
2. Pour chaque champ, l'utilisateur doit pouvoir définir :
   - Le type de source : table, configuration, constante, expression, date courante
   - La valeur source : nom de table.colonne, clé de configuration, valeur fixe
   - Le contexte : quel enregistrement utiliser (candidat, instructeur, etc.)
   - Le format de sortie : format de date, transformation
3. Le système doit permettre de laisser des champs non mappés (resteront vides).
4. Le système doit valider que les colonnes référencées existent.
5. Le système doit permettre de modifier le mapping d'un template existant.

### EF3 : Génération de documents

1. Le système doit afficher les templates disponibles.
2. Le système doit afficher les contextes requis pour chaque template (ex: "nécessite un candidat et un instructeur").
3. Le système doit permettre de sélectionner les enregistrements pour chaque contexte.
4. Le système doit afficher un aperçu des données avant génération.
5. Le système doit générer un PDF avec les champs remplis.
6. Le système doit permettre le téléchargement immédiat du PDF généré.
7. Le système doit conserver les caractères accentués correctement.

### EF4 : Archivage et historique

L'archivage et l'historique des PDF générés sont délégués au module `archived_documents`.

1. Le système doit archiver le PDF généré via le module `archived_documents` avec le type de document `formulaire_pdf`.
2. L'archivage doit associer le document au pilote concerné.
3. Le système doit enregistrer les métadonnées : date de génération, template utilisé, générateur (via les champs `description`, `uploaded_by`, `uploaded_at` d'`archived_documents`).
4. L'historique et le re-téléchargement des documents générés sont accessibles via le module `archived_documents`.

### EF6 : Contrôle d'accès

1. Seuls les administrateurs peuvent gérer les templates et mappings.
2. Les instructeurs peuvent générer des documents pour leurs élèves.
3. Les administrateurs peuvent générer des documents pour tous les pilotes.
4. Les pilotes peuvent consulter leurs documents archivés.

## Exigences non fonctionnelles

- **Compatibilité** : Support des formulaires PDF AcroForm (standard ISO 32000).
- **Performance** : Génération d'un PDF en moins de 5 secondes.
- **Sécurité** : Validation des fichiers uploadés (type MIME, taille max 10 Mo).
- **Traçabilité** : Journalisation des générations de documents.
- **Encodage** : Support complet des caractères UTF-8 (accents, caractères spéciaux).

## Contraintes & dépendances

- Nécessite Python avec PyPDF2 (déjà installé sur le système).
- S'intègre avec le module `archived_documents` pour l'archivage et l'historique des PDF générés.
- Les formulaires PDF doivent être au format AcroForm (pas XFA).

## Mesures de succès

- Réduction du temps de remplissage des formulaires administratifs de 80%.
- Zéro erreur de saisie sur les données provenant de la base.
- 100% des formulaires courants du club configurés dans le système.

## Questions ouvertes

- ~~Faut-il permettre la génération en lot (plusieurs pilotes en une fois) ?~~ Non, hors scope V1.
- Faut-il permettre l'édition manuelle de certains champs avant génération ? Non dans un premier temps.
- Quels sont les formulaires prioritaires à configurer ? Le formulaire 134i-Formlic (attestation début de formation ULM).
