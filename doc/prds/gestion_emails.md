# Product Requirements Document (PRD) - Gestion des Adresses Email dans GVV

## 1. Vue d'ensemble

### 1.1 Objectif

Permettre aux responsables du club d'envoyer un mail aux membres ou à une selection de membres en quelques clics que ce soit à partir d'une ordinateur ou de leur smartphone.

Moderniser le système de gestion des adresses email dans GVV en abandonnant l'envoi direct d'emails au profit d'un système de sélection et d'export d'adresses vers le client de messagerie préféré de l'utilisateur.

### 1.2 Problème à résoudre
- L'ancien système d'envoi direct d'emails est obsolète et n'apporte pas de valeur ajoutée
- Le mécanisme actuel de sélection d'adresses ne permet pas de sélectionner les utilisateurs d'une section
- Impossibilité de gérer des adresses email externes
- Difficulté à maintenir des listes de diffusion à jour manuellement

### 1.3 Valeur ajoutée
- Mise à jour automatique des listes quand un membre change de fonction ou de statut
- Partage des listes entre tous les membres autorisés (secrétaires)
- Facilité d'utilisation : envoi en quelques clics via le client de messagerie habituel
- Gestion unifiée des adresses internes (issues de GVV) et externes

## 2. Périmètre fonctionnel

### 2.1 Dans le périmètre
- Extension du mécanisme de sélection pour inclure les sections et les autorisations
- Import d'adresses email externes (format texte et CSV)
- Création, modification, suppression de listes de diffusion
- Export vers le presse-papier
- Export vers fichier texte/Markdown pour partage
- Ouverture du client de messagerie avec les adresses sélectionnées

### 2.2 Hors périmètre
- Envoi direct d'emails depuis GVV (fonctionnalité obsolète à supprimer)
- Gestion complète d'un client de messagerie
- Historique des envois
- Rédaction assistée par IA, mais j'y pense et je risque de l'ajouter ultérieurement. En plus pour les gens comme moi, cela permettra des envoies sans fautes d'orthographe.

## 3. Utilisateurs et rôles

### 3.1 Rôle : Secrétaire
**Permissions:**
- Sélectionner des adresses selon divers critères
- Créer/modifier/supprimer des listes de diffusion
- Exporter les adresses vers le client de messagerie
- Importer des adresses externes

**Cas d'usage principaux:**
1. Envoyer un courriel à une liste prédéfinie
2. Créer une nouvelle liste par sélection de critères (ex: tous les instructeurs)
3. Créer une nouvelle liste par sélection manuelle de membres (ex: animateurs simulateur - volontaires)
4. Enrichir une liste avec des adresses externes (une liste peut être uniquement externe)
5. Exporter une liste vers fichier pour partage avec personnes n'ayant pas accès à GVV
6. Modifier/supprimer des listes existantes

## 4. Exigences fonctionnelles

### 4.1 Sélection d'adresses

#### 4.1.1 Critères de sélection GVV
Le système doit permettre la sélection selon:
- **Rôles/Droits:** trésoriers, instructeurs, pilotes, administrateurs, etc. (basé sur le système d'autorisations existant de GVV)
- **Sections:** ULM, planeur, avion, général, etc.
- **Statut:** membre actif, inactif
- **Combinaisons multiples** de critères (ET/OU logique)
- **Extensibilité:** Le système doit supporter automatiquement les nouveaux rôles ajoutés au système d'autorisations

#### 4.1.2 Interface de sélection
- Interface visuelle similaire au mécanisme d'attribution des droits utilisateur
- Prévisualisation en temps réel du nombre d'adresses sélectionnées
- Affichage de la liste des destinataires avant export
- Validation des adresses email (format valide)
- **Dédoublonnage automatique:** si un utilisateur est sélectionné par plusieurs critères (ex: instructeur ET membre de la section ULM), son adresse n'apparaît qu'une seule fois dans la liste finale

### 4.2 Gestion des listes de diffusion

#### 4.2.1 Création de liste
- Nommage de la liste (obligatoire, unique)
- Description optionnelle
- **Trois modes de création:**
  1. **Par critères GVV:** sélection automatique selon rôles, sections, statuts (mise à jour automatique)
  2. **Par sélection manuelle de membres:** choix individuel de membres dans une liste (liste statique)
  3. **Par import externe:** ajout d'adresses externes via fichier ou saisie manuelle
- Les trois modes peuvent être combinés dans une même liste
- Sauvegarde de la liste

**Exemples d'utilisation:**
- Liste "Instructeurs actifs": création par critères (rôle=instructeur, statut=actif) → mise à jour automatique
- Liste "Animateurs simulateur": création par sélection manuelle de volontaires → liste statique qui ne change que si modifiée manuellement
- Liste "Auditeurs BIA 2024": création par import externe + ajout manuel éventuel → liste statique

#### 4.2.2 Modification de liste
- Modification du nom/description
- Ajout/suppression d'adresses
- Re-sélection par critères
- Les listes basées sur des critères se mettent à jour automatiquement

#### 4.2.3 Suppression de liste
- Confirmation obligatoire avant suppression
- Impossibilité de supprimer une liste en cours d'utilisation

### 4.3 Import d'adresses externes

#### 4.3.1 Formats supportés
- **Texte brut:** une adresse par ligne
- **CSV:** colonnes configurables (nom, prénom, email, etc.)
- Validation du format lors de l'import
- Rapport d'erreurs en cas d'adresses invalides

#### 4.3.2 Traitement de l'import
- **Détection des doublons:**
  - Entre adresses importées (au sein du fichier)
  - Entre adresses importées et adresses GVV existantes dans la liste
  - Comparaison insensible à la casse (user@example.com = USER@EXAMPLE.COM)
- **Gestion des doublons détectés:**
  - Option de fusion (garder l'existant, ignorer le doublon)
  - Option de remplacement (remplacer par la nouvelle adresse)
  - Rapport détaillé des doublons détectés
- Prévisualisation avant validation finale

### 4.4 Export et envoi

#### 4.4.1 Export vers presse-papier
- Copie des adresses au format standard (séparées par virgules ou points-virgules)
- Notification visuelle de succès
- Gestion des cas d'erreur (liste vide, permissions insuffisantes)

#### 4.4.2 Export vers fichier texte/Markdown
Pour permettre le partage avec des personnes n'ayant pas accès à GVV:

**Formats d'export:**
1. **Format simple (TXT):** liste d'adresses séparées par virgules ou points-virgules, prête pour copier/coller dans un client email
2. **Format Markdown (MD):** fichier structuré avec métadonnées et liste détaillée

**Format TXT - Copier/coller direct:**
```
jean.dupont@example.com, marie.martin@example.com, pierre.durant@example.com
```
ou
```
jean.dupont@example.com; marie.martin@example.com; pierre.durant@example.com
```

**Format Markdown - Partage avec contexte:**
```markdown
# Liste: Animateurs simulateur
**Description:** Volontaires pour animer les sessions simulateur
**Créée le:** 2025-01-15
**Mise à jour:** 2025-01-20
**Nombre de destinataires:** 12

## Adresses (copier/coller)
jean.dupont@example.com, marie.martin@example.com, pierre.durant@example.com, ...

## Détails des membres

| Nom | Prénom | Email |
|-----|--------|-------|
| Dupont | Jean | jean.dupont@example.com |
| Martin | Marie | marie.martin@example.com |
| Durant | Pierre | pierre.durant@example.com |
| ... | ... | ... |
```

**Fonctionnalités:**
- Bouton de téléchargement du fichier (.txt ou .md)
- Choix du format (TXT simple ou Markdown avec détails)
- Choix du séparateur pour format TXT (virgule ou point-virgule)
- Nom de fichier automatique basé sur le nom de la liste (ex: `animateurs_simulateur.txt`)
- Encodage UTF-8 pour compatibilité universelle

**Cas d'usage:**
- Secrétaire exporte "Auditeurs BIA 2024" en .txt et envoie le fichier à l'instructeur BIA externe
- L'instructeur ouvre le fichier, copie les adresses et les colle dans Thunderbird
- Président exporte "Animateurs simulateur" en .md pour garder une trace avec les noms complets

#### 4.4.3 Découpage en sous-listes
Pour s'adapter aux limitations des clients de messagerie:
- **Taille de découpage configurable:** par défaut 20 destinataires maximum par sous-liste
- **Sélection de la partie à exporter:** interface permettant de choisir "Partie 1/5", "Partie 2/5", etc.
- **Indication visuelle:** affichage clair du nombre total de destinataires et du nombre de parties nécessaires
- **Export séquentiel:** possibilité d'exporter toutes les parties successivement
- **Exemples de limitations clients:**
  - Gmail: limite à 500 destinataires par email
  - Outlook: limite d'URL mailto à ~2000 caractères
  - Certains clients: limites de 20-50 destinataires recommandées

**Interface de découpage:**
```
┌─────────────────────────────────────────────────────────┐
│ Liste: Membres actifs (87 destinataires)                │
├─────────────────────────────────────────────────────────┤
│ Taille des sous-listes: [20 ▼] destinataires           │
│                                                          │
│ → Nombre de parties nécessaires: 5                      │
│                                                          │
│ Sélectionner la partie à exporter:                      │
│ ○ Toutes les parties (exports séquentiels)             │
│ ● Partie spécifique: [1 ▼] sur 5                       │
│                                                          │
│ Partie 1: destinataires 1-20                            │
│ Partie 2: destinataires 21-40                           │
│ Partie 3: destinataires 41-60                           │
│ Partie 4: destinataires 61-80                           │
│ Partie 5: destinataires 81-87                           │
│                                                          │
│ [Prévisualiser partie] [Copier] [Ouvrir client mail]   │
└─────────────────────────────────────────────────────────┘
```

#### 4.4.4 Ouverture client de messagerie
- Génération d'un lien `mailto:` avec les adresses de la partie sélectionnée
- Support des limites de taille d'URL (fallback vers presse-papier si trop long)
- **Placement des adresses:** option de choix entre TO, CC, BCC pour les destinataires sélectionnés
- **Titre du message (Subject):** champ de saisie pour définir l'objet du courriel
- **Adresse de retour (Reply-To):** champ optionnel pour définir l'adresse de réponse
- **Mémorisation des préférences:** le navigateur se souvient des choix précédents (TO/CC/BCC, titre, adresse de retour) via localStorage
- Pour les exports de toutes les parties: ouverture séquentielle avec confirmation entre chaque partie

**Interface d'export vers client mail:**
```
┌─────────────────────────────────────────────────────────┐
│ Paramètres d'envoi                                      │
├─────────────────────────────────────────────────────────┤
│ Placer les destinataires en:                            │
│ ● TO (À)    ○ CC (Copie)    ○ BCC (Copie cachée)       │
│                                                          │
│ Titre du message:                                       │
│ [Information importante - Assemblée générale       ]    │
│                                                          │
│ Adresse de retour (Reply-To): (optionnel)              │
│ [secretaire@club-aviation.fr                       ]    │
│                                                          │
│ ℹ️ Vos préférences sont sauvegardées automatiquement    │
│                                                          │
│ [Ouvrir le client de messagerie]                       │
└─────────────────────────────────────────────────────────┘
```

## 5. Exigences non-fonctionnelles

### 5.1 Performance
- Sélection d'adresses: < 2 secondes pour 500 membres
- Import CSV: < 5 secondes pour 1000 lignes
- Affichage liste: < 1 seconde

### 5.2 Sécurité
- Contrôle d'accès basé sur les rôles (seuls les secrétaires accèdent à la fonctionnalité)
- Validation des entrées (injection SQL, XSS)
- Journalisation des actions (création/modification/suppression de listes)

### 5.3 Compatibilité
- Navigateurs: Chrome, Firefox, Edge (versions récentes)
- Clients de messagerie: Outlook, Thunderbird, Gmail, clients web standard

### 5.4 Maintenance
- Tests avec couverture > 70%
- Documentation utilisateur en français, anglais, néerlandais

## 6. Critères de succès

- [ ] Les secrétaires peuvent créer une liste en < 2 minutes
- [ ] Import CSV fonctionne sans erreur pour 95% des fichiers bien formés
- [ ] Export vers client de messagerie fonctionne sur 3 clients différents
- [ ] Export fichier TXT permet copier/coller direct dans client email
- [ ] Export fichier MD contient toutes les métadonnées utiles
- [ ] Couverture de tests > 70%
- [ ] Aucune régression sur les fonctionnalités existantes
- [ ] Documentation traduite dans les 3 langues

## 7. Risques et mitigation

| Risque | Impact | Probabilité | Mitigation |
|--------|--------|-------------|------------|
| Limite de taille URL mailto | Moyen | Élevée | Fallback vers presse-papier + découpage en sous-listes |
| Formats CSV variés | Faible | Moyenne | Configuration flexible, validation claire |
| Résistance utilisateurs | Moyen | Faible | Documentation, formation |
| Performance avec grandes listes (>500 membres) | Moyen | Faible | Optimisation requêtes, tests de charge |
| Compatibilité clients email mobiles | Moyen | Moyenne | Tests sur iOS/Android, fallbacks |

## 8. Documentation requise

- Sections dans les guides utilisateur existants (FR/EN/NL)
- Formation pour les secrétaires
- Mise à jour du README

---

**Version:** 1.1
**Date:** 2025-10-31
**Auteur:** Claude Code sous supervision Fred
**Statut:** Proposition initiale
