# Plan d'implementation - Impression cartes membre

Date: 29 avril 2026  
Source PRD: `doc/prds/impression_cartes_membre_prd.md`

## Strategie de livraison

Priorite produit:
1. Impression des planches A4 recto/verso (usage administration, campagne annuelle)
2. Impression de carte individuelle (membre puis administrateur)

Approche:
- Livraison incrementale en 2 lots fonctionnels, avec validations a chaque etape.
- Chaque etape se termine par une preuve executable (tests + verification manuelle).
- Aucun developpement du lot 2 tant que le lot 1 n'est pas valide en bout en bout.

## Definition of Done globale

- Les exigences EF3, EF4, EF5, EF6, EF7 (partie lot) sont couvertes pour le lot 1.
- Les exigences EF1, EF2, EF7 (partie individuelle) sont couvertes pour le lot 2.
- Les PDF produits sont imprimables sur A4, avec alignement recto/verso coherent.
- Les tests automatises ajoutes passent dans la suite projet.
- Une verification Playwright smoke est disponible pour chaque parcours critique.

## Lot 1 (prioritaire) - Planches A4 recto/verso administrateur

Objectif: permettre a un administrateur de produire des planches A4 cartes membre en lot, avec recto/verso aligne, fonds saisonniers et photo membre.

### Etape 1 - Cadrage technique et mapping des donnees

Implementation pas a pas:
1. Identifier les sources de donnees necessaires: membres, cotisations, statut actif annee N-1, photo, nom association, president, signature.
2. Definir les regles de selection du lot par defaut (actifs annee precedente) et de surcharge manuelle.
3. Definir le contrat de donnees de generation PDF pour une carte (recto + verso) et une planche A4 (positions fixes).
4. Valider les dimensions exactes du gabarit A4 cible (Avery C32016-10) et les marges de securite.

Validation de l'etape:
- Revue technique du mapping (checklist de tous les champs EF5).
- Cas limites listes et approuves: photo absente, membre non actif ajoute manuellement, signature manquante.
- Jeu de donnees de reference prepare pour tests.

### Etape 2 - Gestion des fonds recto/verso par saison (admin)

Implementation pas a pas:
1. Ajouter le stockage de la configuration de fonds actifs par saison (recto, verso).
2. Ajouter l'ecran admin de televersement et activation de la paire de fonds saisonniere.
3. Ajouter les controles d'acces admin sur cette fonctionnalite.
4. Prevoir fallback explicite si un fond est absent (message clair et generation bloquee ou fond neutre selon regle validee).

Validation de l'etape:
- Test unitaire/integre de persistence de configuration saisonniere.
- Test d'autorisation: refus pour non-admin.
- Test manuel: televerser recto+verso, recharger page, verifier fond actif affiche.

### Etape 3 - Moteur de composition carte (recto/verso)

Implementation pas a pas:
1. Construire un service de composition d'une carte logique (donnees + assets + positions).
2. Integrer la photo membre conditionnelle (si absente: carte valide sans erreur).
3. Inserer les contenus obligatoires (association, annee, titulaire, numero, president, signature).
4. Produire recto et verso synchronises sur la meme cle d'ordre.

Validation de l'etape:
- Tests unitaires sur composition pour 3 cas: photo presente, photo absente, signature absente.
- Verification visuelle de 3 cartes echantillon (controle presence/position des champs).

### Etape 4 - Generation des planches A4 en lot

Implementation pas a pas:
1. Implementer la selection de lot par defaut: membres actifs de l'annee precedente.
2. Ajouter selection manuelle et ajout explicite d'un membre actif/non actif.
3. Generer les planches recto et verso en conservant strictement le meme ordre des cartes.
4. Produire un PDF final pret impression recto/verso carton.

Validation de l'etape:
- Tests integration sur la constitution du lot (defaut, manuel, ajout hors actif).
- Test de non-regression sur ordre recto/verso: meme sequence d'identifiants entre faces.
- Test performance: lot representatif dans delai acceptable (seuil a definir en recette).

### Etape 5 - UX admin et verification d'impression

Implementation pas a pas:
1. Finaliser l'ecran admin lot: filtres, liste finale, recapitulatif avant generation.
2. Afficher un resume explicite du rendu genere (annee, nombre de membres, mode recto/verso).
3. Ajouter messages d'erreur/action clairs (jamais d'echec silencieux).
4. Valider une procedure d'impression recommandee (orientation, duplex, type papier).

Validation de l'etape:
- Test Playwright smoke: parcours complet admin lot jusqu'au telechargement PDF.
- Recette manuelle avec impression reelle de controle alignement recto/verso.
- Check UX: l'utilisateur identifie clairement ce qui a ete genere.

### Gate de fin Lot 1

Le lot 1 est valide si:
- EF3, EF4, EF5, EF6, EF7 (partie lot) sont demontrables.
- Un test smoke automatise passe.
- Un test d'impression physique de reference est conforme.

## Lot 2 (second temps) - Cartes individuelles

Objectif: activer l'impression individuelle une fois le flux lot stabilise.

### Etape 6 - Impression individuelle membre

Implementation pas a pas:
1. Ajouter l'entree membre "Imprimer ma carte".
2. Proposer par defaut la derniere annee cotisee.
3. Permettre la selection d'une annee parmi les annees cotisees uniquement.
4. Generer le PDF individuel A4 conforme au meme moteur de composition que le lot.

Validation de l'etape:
- Tests d'autorisation: membre limite a sa propre carte.
- Tests fonctionnels: annee par defaut + changement d'annee autorisee.
- Test Playwright smoke membre jusqu'au telechargement PDF.

### Etape 7 - Impression individuelle administrateur

Implementation pas a pas:
1. Ajouter la recherche/selection de membre cote admin.
2. Autoriser la generation meme sans cotisation payee.
3. Permettre le choix de l'annee de carte.
4. Reutiliser strictement le moteur de composition PDF du lot 1.

Validation de l'etape:
- Tests integration: generation admin avec et sans cotisation.
- Tests d'autorisation: reserve admin.
- Verification manuelle de 2 cas reels (membre cotisant/non cotisant).

### Gate de fin Lot 2

Le lot 2 est valide si:
- EF1, EF2, EF7 (partie individuelle) sont demontrables.
- Les parcours membre et admin individuels passent en smoke test.

## Plan de tests transverse

Pour chaque etape, appliquer les niveaux suivants:
1. Tests unitaires sur logique metier (selection, composition, ordre recto/verso).
2. Tests integration sur donnees reelles (modeles/controllers/services).
3. Tests E2E Playwright sur parcours critiques.
4. Validation manuelle d'impression (au minimum a la fin des lots 1 et 2).

Commandes cibles:
- `source setenv.sh`
- `./run-all-tests.sh`
- `cd playwright && npx playwright test --reporter=line`

## Risques et parades

- Risque: decalage recto/verso a l'impression.
  - Parade: verrouiller ordre carte, imprimer page test de calibrage, recette physique obligatoire.
- Risque: donnees president/signature incompletes.
  - Parade: validation pre-generation + message explicite cote admin.
- Risque: volume lot eleve.
  - Parade: pagination interne, instrumentation du temps de generation, seuil d'alerte UX.

## Questions a trancher avant implementation

1. Signature du president: image, texte, ou les deux (regle prioritaire)?
2. En cas de fond saisonnier absent: bloquer la generation ou utiliser un fond neutre par defaut?
3. Seuil de performance accepte pour un lot standard (ex: 100, 300, 500 cartes)?