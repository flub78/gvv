# PRD — Configuration des Bons de Vol de Découverte

Date : 3 août 2026

## Contexte
Le club doit pouvoir personnaliser l'apparence des bons cadeaux de vol de découverte (format A5 recto/verso) de la même manière que les cartes de membre : fonds recto/verso téléversables, champs positionnables librement (police, taille, couleur), et pas de mise en page figée dans le code. Le mécanisme actuel (`vols_decouverte.php::generate_pdf` et sa copie quasi identique `paiements_en_ligne.php::_generate_vd_pdf`) génère un PDF A5 dont la mise en page est entièrement câblée en dur (positions HTML, image de fond fixe, liste de contacts fixe avion/planeur/ULM). Le PRD « Impression de Cartes de Membre » anticipait explicitement cette réutilisation : *« le moteur de mise en page configurable est conçu comme un composant indépendant réutilisable pour les bons de vols de découverte »*.

## Objectifs
- Permettre à un administrateur de configurer l'apparence des bons de vol de découverte (recto/verso) avec le même mécanisme de mise en page que les cartes de membre (champs positionnables, police, taille, couleur, fonds téléversables).
- Permettre de définir plusieurs configurations d'apparence (« looks ») distinctes.
- Permettre d'associer un look à une section (club), ou d'utiliser un look commun pour toutes les sections qui n'en définissent pas.
- Générer le PDF A5 recto/verso du bon à partir du look applicable à la section du vol de découverte.
- Faire coexister le nouveau mécanisme avec l'ancien tant que la bascule n'est pas explicitement validée.

## Non-objectifs
- Éditeur graphique intégré (WYSIWYG avec rendu live).
- Modification du workflow métier des vols de découverte (vente, validité, paiement en ligne HelloAsso) — seule la présentation/génération du PDF est concernée.
- Suppression immédiate du mécanisme existant : il reste disponible en parallèle jusqu'à décision explicite de bascule.
- Régénération rétroactive en masse des bons déjà émis avec le nouveau moteur.
- Gestion d'un flux d'impression industriel externe (imprimeur, routage, envoi postal).
- Vérification légale de validité du bon hors données GVV.

## Portée

### Inclus
- Écran de configuration de mise en page (recto/verso) pour les bons de vol de découverte, réutilisant le moteur générique développé pour les cartes de membre.
- Upload et gestion des images de fond recto et verso, par configuration.
- Configuration des champs variables issus de l'enregistrement du vol de découverte (voir EF2).
- Configuration des champs fixes associés à la configuration : nom du club/section, textes libres, contacts (noms/téléphones) en nombre et libellés paramétrables.
- Gestion de plusieurs configurations (« looks »).
- Association d'un look à une section, ou usage d'un look par défaut commun aux sections non associées explicitement.
- Génération du PDF A5 recto/verso du bon à partir du look applicable.
- Export et import d'une configuration au format fichier, pour réutilisation entre sections ou instances.

### Exclu
- Choix d'un look différent selon le type de vol de découverte (avion/planeur/ULM) au sein d'une même section — un seul look actif par section dans cette version.
- Désactivation automatique de l'ancien mécanisme : la bascule est une action explicite, distincte de cette fonctionnalité.
- Gestion multi-modèles avec versioning avancé (historique de looks, planification de changement de look à date future).

## Personae & rôles
- **Administrateur club** : configure fonds, champs, looks, et associations section → look.
- **Secrétariat / bénévole émetteur** : génère, imprime ou envoie par email un bon existant ; ne configure rien.
- **Bénéficiaire** : reçoit et présente le bon ; n'a pas accès au système.

## Parcours clés

### Parcours 1 — Administrateur : création d'un look
1. L'administrateur accède à l'écran de configuration des bons de vol de découverte.
2. Il crée une nouvelle configuration (look) ou en sélectionne une existante à modifier.
3. Il téléverse une image de fond recto et une image de fond verso.
4. Il configure chaque champ variable (numéro, dates, bénéficiaire, occasion, donateur, type de vol, QR code) : activé/désactivé, face, position X/Y, police, taille, couleur.
5. Il ajoute, modifie ou supprime les champs fixes (nom du club, textes libres, contacts).
6. Il enregistre la configuration.

### Parcours 2 — Administrateur : association d'un look à une section
1. L'administrateur accède à la liste des sections.
2. Il associe une configuration (look) existante à une section, ou choisit d'utiliser le look par défaut.
3. Les bons émis ensuite pour cette section utilisent ce look.

### Parcours 3 — Vente d'un vol de découverte
1. Le secrétariat saisit un nouveau vol de découverte (vente).
2. Le système détermine le look applicable à partir de la section du vol de découverte (look associé, sinon look par défaut).
3. Le système génère et stocke le PDF A5 recto/verso avec les champs propres à ce vol de découverte et le look déterminé.

### Parcours 4 — Réimpression ou renvoi d'un bon déjà émis
1. Le secrétariat réimprime, télécharge ou renvoie par email un bon émis antérieurement.
2. Le système sert le PDF stocké lors de la vente (ou de la dernière modification), sans le régénérer : le bon reste visuellement identique à l'original, même si le look de sa section a été modifié depuis.

### Parcours 5 — Transition depuis l'ancien mécanisme
1. Le nouveau mécanisme est disponible en parallèle de l'ancien (`vols_decouverte.php::generate_pdf`, `paiements_en_ligne.php::_generate_vd_pdf`).
2. Après une période de validation par les clubs, l'ancien mécanisme peut être désactivé pour les nouveaux bons, sur décision explicite.

## Exigences fonctionnelles

### EF1 — Génération et stockage du bon
1. Le système doit générer le PDF A5 recto/verso d'un vol de découverte lors de sa création (vente), à partir du look applicable à sa section, et le stocker.
2. Le système doit régénérer et remplacer le PDF stocké chaque fois que l'enregistrement du vol de découverte est modifié (correction de champs), en utilisant le look applicable au moment de la modification.
3. L'impression, le téléchargement et l'envoi par email d'un bon doivent utiliser le PDF stocké, sans le régénérer.
4. Le PDF doit inclure un QR code pointant vers l'action de validation du bon (comportement identique à l'existant).
5. En l'absence de configuration personnalisée pour une section, une configuration par défaut doit être appliquée automatiquement lors de la génération.

### EF2 — Contenu du bon
Les éléments imprimables sont de deux types :

**Champs variables** (valeur différente par bon, issus de l'enregistrement du vol de découverte) — disponibles à la configuration :
1. Numéro du bon
2. Date de vente
3. Date de fin de validité
4. Nom du bénéficiaire
5. Occasion
6. Donateur (« de la part de »)
7. Type / description du vol de découverte (produit et tarif associés)
8. QR code de validation

**Champs fixes** (identiques pour tous les bons d'une configuration donnée) — librement ajoutés par l'administrateur :
1. Nom du club / de la section
2. Textes libres
3. Contacts (nom et téléphone), en nombre et libellés paramétrables — remplace la liste fixe actuelle avion/planeur/ULM

Chaque champ (variable ou fixe) peut être configuré indépendamment : face (recto/verso), activé/désactivé, position X et Y, police, taille de police, couleur.

### EF3 — Fonds personnalisés recto/verso
1. L'administrateur doit pouvoir téléverser une image de fond recto et une image de fond verso par configuration.
2. Le système doit utiliser les fonds de la configuration (look) applicable lors de la génération du PDF.

### EF4 — Gestion de plusieurs configurations (« looks »)
1. Le système doit permettre de créer, modifier et supprimer plusieurs configurations distinctes.
2. Chaque configuration regroupe : fonds recto/verso, champs variables positionnés, champs fixes.

### EF5 — Association section → look
1. Le système doit permettre d'associer une configuration à une section.
2. Une section sans association explicite doit utiliser un look par défaut, commun à toutes les sections non associées.
3. Le système doit permettre de changer l'association section → look sans dupliquer la configuration.

### EF6 — Export / import de configuration
1. La configuration doit être exportable dans un fichier.
2. La configuration doit être importable depuis un fichier exporté, pour réutilisation entre sections ou instances GVV.

### EF7 — Contrôle d'accès
1. Seuls les administrateurs peuvent créer, modifier ou supprimer des configurations et des associations section → look.
2. La génération, l'impression et l'envoi d'un bon restent accessibles aux utilisateurs actuellement autorisés (comportement inchangé).

### EF8 — Coexistence avec l'ancien mécanisme
1. Le nouveau mécanisme de génération doit fonctionner en parallèle de l'ancien sans le modifier, tant que la bascule n'est pas explicitement décidée.
2. La désactivation de l'ancien mécanisme pour les nouveaux bons n'intervient qu'après une période de validation par les clubs, sur décision explicite.

### EF9 — Régénération manuelle
1. Un administrateur doit pouvoir forcer manuellement la régénération du PDF stocké d'un bon existant, en appliquant le look actuellement associé à sa section.

## Exigences non fonctionnelles
- Compatibilité : PDF imprimable depuis navigateurs modernes, y compris via le flux de paiement en ligne existant.
- Qualité d'impression : rendu net en impression couleur et noir & blanc.
- Robustesse : génération possible même si un champ optionnel du vol de découverte n'est pas renseigné.
- Performance : génération d'un bon en moins de 5 secondes.
- Portabilité : une configuration exportée doit pouvoir être importée sur une autre instance GVV.
- Persistance : le PDF stocké d'un bon doit rester accessible pour l'impression, le téléchargement et l'envoi email pendant toute la durée de vie du vol de découverte.

## Contraintes & dépendances
- Réutilise le moteur de mise en page générique (structure de configuration + moteur de rendu) développé pour les cartes de membre.
- Dépend des données du vol de découverte, des produits/tarifs (pour le type de vol) et des sections (clubs) déjà présentes dans GVV.
- Doit coexister transitoirement avec `vols_decouverte.php::generate_pdf` et `paiements_en_ligne.php::_generate_vd_pdf`, qui restent inchangés jusqu'à la bascule.
- Nécessite un espace de stockage persistant pour les PDF générés (un bon par vol de découverte, remplacé à chaque régénération).

## Mesures de succès
- 100 % des bons peuvent être générés, avec configuration personnalisée ou look par défaut.
- Une configuration peut être créée, associée à une section, exportée puis importée, et produit un rendu identique.
- 0 % de régression visuelle sur les bons déjà émis lors de la mise en service du nouveau mécanisme, grâce au stockage du PDF à la vente.
- Adoption du nouveau mécanisme par au moins un club pilote avant bascule.

## Décisions arrêtées
- **Stabilité de l'apparence historique** : le PDF du bon est généré et stocké lors de la vente (et régénéré lors d'une modification ultérieure de l'enregistrement), plutôt que régénéré à chaque impression/envoi. L'impression, le téléchargement et l'email servent le PDF stocké. Un bon déjà émis conserve ainsi son apparence d'origine même si le look de sa section est modifié par la suite.
- **Régénération manuelle** : un administrateur peut forcer la régénération du PDF stocké d'un bon existant (cf. EF9), par exemple pour appliquer un look corrigé après coup.
- **Décision de bascule** : l'ancien mécanisme n'est désactivé pour les nouveaux bons qu'après une période de validation par les clubs, et non dès la mise en service du nouveau mécanisme.
- **Granularité des looks** : un seul look par section, pas de look différencié par type de vol (avion/planeur/ULM) dans l'immédiat.
