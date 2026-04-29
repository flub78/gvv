# PRD — Impression de Cartes de Membre

Date : 29 avril 2026

## Contexte
Le club doit pouvoir produire des cartes de membre physiques à partir des données GVV, soit à l’unité, soit en planche d’impression. Les besoins couvrent l’auto-service membre, la production anticipée par l’administration, et des formats adaptés à des supports d’impression spécifiques (A4 détachable Avery C32016-10, recto-verso sur carton).

## Objectifs
- Permettre à un membre d’imprimer sa carte de membre au format PDF, prête à l’impression.
- Permettre à un administrateur d’imprimer une carte individuelle pour n’importe quel membre, y compris sans cotisation payée.
- Permettre à un administrateur de générer des planches recto-verso pour impression en lot.
- Intégrer automatiquement la photo du membre lorsqu’elle est disponible dans GVV.
- Permettre à un administrateur de personnaliser l’arrière-plan recto et verso des cartes.

## Non-objectifs
- Gestion d’un flux d’impression industriel externe (imprimeur, routage, envoi postal).
- Contrôle matériel des imprimantes (calibrage, marge physique, duplex matériel).
- Workflow de signature électronique.
- Vérification légale de validité de la carte hors données GVV.
- Export de contrôle séparé des cartes générées (hors PDF d’impression).

## Portée

### Inclus
- Génération PDF d’une carte individuelle membre.
- Sélection de l’année de carte parmi les années de cotisation du membre.
- Valeur par défaut sur la dernière année disponible pour le membre.
- Impression administrateur d’une carte individuelle sans contrainte de paiement.
- Génération de planches d’impression recto-verso pour lot de membres.
- Sélection de lot :
  - Membres actifs de l’année précédente (filtre par défaut).
  - Sélection manuelle de membres à inclure.
  - Ajout manuel d’un membre à la liste, actif ou non.
- Insertion de la photo du membre si présente dans GVV.
- Inclusion des informations obligatoires sur la carte.
- Upload et gestion d’images de fond recto/verso par l’administrateur.

### Exclu
- Création/retouche avancée d’images (éditeur graphique intégré).
- Gestion multi-modèles complexes avec versioning avancé.
- Génération automatique de QR code ou code-barres (non demandé).

## Personae & rôles
- Membre : imprime sa propre carte de membre.
- Administrateur club : imprime des cartes individuelles ou en lot, configure les fonds recto/verso.

## Parcours clés

### Parcours 1 — Membre : impression de sa carte
1. Le membre accède à l’option d’impression de carte.
2. Le système propose par défaut la dernière année avec cotisation.
3. Le membre peut sélectionner une autre année parmi ses années cotisées.
4. Le membre génère un PDF A4 compatible feuille détachable format carte de crédit.
5. Le membre imprime le PDF.

### Parcours 2 — Administrateur : carte individuelle
1. L’administrateur sélectionne un membre.
2. L’administrateur choisit l’année de la carte.
3. Le système autorise la génération même sans cotisation payée.
4. L’administrateur génère et imprime le PDF individuel.

### Parcours 3 — Administrateur : planches recto-verso
1. L’administrateur ouvre l’impression en lot.
2. Il choisit le mode de sélection des membres :
  - Membres actifs de l’année précédente (par défaut).
  - Sélection manuelle d’une liste de membres.
3. L’administrateur peut ajouter manuellement à la liste un membre actif ou non actif.
4. Le système génère les planches recto et verso alignées pour impression sur carton épais.
5. L’administrateur imprime en recto-verso puis découpe/plastifie si souhaité.

### Parcours 4 — Administrateur : personnalisation visuelle
1. L’administrateur charge une image de fond pour le recto.
2. L’administrateur charge une image de fond pour le verso.
3. Les nouveaux fonds sont appliqués aux générations suivantes.

## Exigences fonctionnelles

### EF1 — Impression individuelle membre
1. Le système doit permettre à un membre authentifié de générer sa propre carte en PDF.
2. Le système doit proposer par défaut la dernière année pour laquelle le membre a une cotisation.
3. Le système doit permettre de sélectionner toute année pour laquelle le membre a une cotisation.
4. Le PDF généré doit être au format A4 et positionné pour une feuille Avery C32016-10 (format carte bancaire détachable).

### EF2 — Impression individuelle administrateur
1. Le système doit permettre à un administrateur de générer une carte pour n’importe quel membre.
2. Le système doit autoriser la génération indépendamment de l’état de paiement de la cotisation.
3. Le système doit permettre le choix de l’année de carte.

### EF3 — Impression en lot recto-verso administrateur
1. Le système doit permettre la génération de planches de cartes en lot.
2. Le système doit proposer par défaut le lot « membres actifs de l’année précédente ».
3. Le système doit permettre une sélection manuelle de membres à inclure.
4. Le système doit permettre d’ajouter explicitement un membre non actif à la liste de lot.
5. Le système doit générer un recto et un verso cohérents pour impression recto-verso sur carton.
6. Le système doit préserver l’ordre et l’alignement nécessaires entre recto et verso.

### EF4 — Photo membre
1. Si une photo membre existe dans GVV, le système doit l’insérer sur la carte.
2. Si aucune photo n’existe, la carte doit rester générable sans erreur.

### EF5 — Contenu obligatoire de la carte
Chaque carte doit contenir :
1. Le nom de l’association.
2. L’année de validité.
3. Le nom du titulaire.
4. Le numéro du titulaire.
5. Le nom du président.
6. La signature du président.

### EF6 — Fonds personnalisés recto/verso
1. L’administrateur doit pouvoir téléverser une image de fond recto.
2. L’administrateur doit pouvoir téléverser une image de fond verso.
3. Le système doit gérer un seul modèle de fond par saison (paire recto/verso active pour la saison).
4. Le système doit utiliser les fonds de la saison sélectionnée lors de la génération PDF.
5. Le système doit conserver une configuration active claire (fonds actuellement utilisés par saison).

### EF7 — Contrôle d’accès
1. Les membres ne peuvent imprimer que leur propre carte.
2. Les administrateurs peuvent imprimer pour tout membre et en lot.
3. La gestion des fonds de carte est réservée aux administrateurs.

## Exigences non fonctionnelles
- Compatibilité : PDF imprimables depuis navigateurs modernes.
- Qualité d’impression : rendu net en impression couleur et noir & blanc.
- Robustesse : génération possible même si photo absente.
- Performance : génération individuelle en moins de 5 secondes, génération lot dans un délai acceptable pour l’utilisateur.
- Utilisabilité : l’utilisateur doit comprendre clairement ce qui a été généré (année, membre(s), mode recto/verso).

## Contraintes & dépendances
- Dépend des données membres, cotisations, photo et informations de l’association présentes dans GVV.
- Dépend de la disponibilité du nom et de la signature du président dans les données de configuration exploitables.
- Nécessite des gabarits d’impression compatibles avec le support physique visé (Avery C32016-10 et carton recto-verso).

## Mesures de succès
- 100 % des membres peuvent générer leur carte pour une année éligible.
- Réduction du temps de préparation des cartes en lot pour l’administration.
- Taux d’erreur d’impression (mauvais membre/année) inférieur à 1 %.
- Adoption par les clubs pour les campagnes annuelles de cartes.

## Questions ouvertes
- La signature du président est-elle une image, un texte, ou les deux ?
- En lot, faut-il proposer des filtres complémentaires (catégorie, section) en plus du filtre par membres actifs de l’année précédente ?
