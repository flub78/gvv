# PRD — Page Météo & Préparation des Vols

Date : 9 février 2026

## Contexte
Les pilotes ont besoin d’un accès rapide aux informations météo et de préparation des vols depuis GVV. Aujourd’hui, ces informations sont consultées sur des sites externes, ce qui disperse l’expérience utilisateur.

## Objectifs
- Centraliser l’accès aux informations météo utiles à la préparation des vols.
- Permettre aux administrateurs de configurer la page via des cartes.
- Offrir un rendu simple, lisible et rapide à charger.

## Non-objectifs
- Fournir une prévision météo propriétaire.
- Stocker ou historiser des données météo.
- Remplacer les sites spécialisés.

## Portée
- Une page dédiée “Météo & préparation des vols”.
- Un système de cartes configurables par les administrateurs.
- Deux types de cartes :
  - Cartes “HTML embarqué” (snippet fourni par un site tiers).
  - Cartes “Lien avec miniature” pointant vers un site externe.
> Note : dans ce document, “carte” désigne un widget de type carte de dashboard (bloc UI), et non une carte météo géographique.

## Personae & rôles
- Pilote / membre : consulte la page météo.
- Instructeur : consulte la page pour préparation/briefing.
- Administrateur : gère les cartes (CRUD).

## Parcours clés
1. Un membre ouvre la page météo et consulte les cartes disponibles.
2. Un administrateur ajoute une nouvelle carte HTML.
3. Un administrateur ajoute une carte “miniature + lien”.
4. Un administrateur supprime une carte obsolète.

## Exigences fonctionnelles
### EF1 — Page météo
- La page doit lister des cartes météo configurées.
- Chaque carte doit être présentée sous forme de “carte” (style dashboard) avec un titre.

### EF2 — Carte HTML embarqué
- Une carte peut contenir un fragment HTML (ex. lien + script d’intégration) fourni par un site tiers.
- Le fragment HTML est stocké tel quel et rendu dans la carte.

**Exemple de fragment HTML (source tiers)**

<a href="https://metar-taf.com/metar/LFAT" id="metartaf-FKphmVbg" style="font-size:18px; font-weight:500; color:#000; width:300px; height:435px; display:block">METAR Le Touquet-Côte d&#039;Opale Airport</a>
<script async defer crossorigin="anonymous" src="https://metar-taf.com/embed-js/LFAT?qnh=hPa&rh=rh&target=FKphmVbg"></script>

### EF3 — Carte miniature + lien
- Une carte peut contenir :
  - Un titre.
  - Une image (miniature).
  - Un lien vers un site externe.
- Au clic, l’utilisateur est redirigé vers le site externe.

### EF4 — CRUD administrateur
- Les administrateurs peuvent :
  - Créer une carte.
  - Modifier une carte.
  - Supprimer une carte.
  - Réordonner les cartes (optionnel si faisable sans refonte).

### EF5 — Visibilité
- Seuls les administrateurs peuvent modifier les cartes.
- Tous les utilisateurs autorisés à accéder à la page peuvent voir les cartes.

## Exigences non fonctionnelles
- Performance : la page doit se charger en moins de 2 secondes hors temps de réponse des sites tiers.
- Sécurité : les fragments HTML sont potentiellement dangereux ; la source doit être explicitement administrée.
- Disponibilité : la page doit rester fonctionnelle même si un site externe est indisponible.
- Compatibilité : compatible navigateur moderne (Chrome, Firefox, Edge).

## Risques & recommandations (inclusion HTML)
**Risques identifiés**
- Injection de scripts malveillants si le fragment HTML provient d’une source non maîtrisée.
- Rupture d’affichage si le fournisseur tiers modifie son script ou bloque l’intégration.
- Ralentissement du chargement si plusieurs scripts externes sont chargés.
- Problèmes de conformité (CSP, cookies tiers, RGPD).

**Recommandations**
- Limiter l’ajout de fragments HTML aux administrateurs.
- Documenter et valider la source du snippet (site connu, usage autorisé).
- Prévoir une liste de sources approuvées (whitelist) si nécessaire.
- Afficher un titre clair et une description indiquant la source externe.
- Prévoir un comportement de repli : carte visible même si le script échoue (lien direct).
- N’accepter que des sources HTTPS connues et documentées.
- Vérifier que le snippet ne charge pas de domaines inattendus.
- Éviter les handlers inline et les snippets multi-scripts non nécessaires.
- Tester chaque snippet en environnement de test avant mise en production.
- Mettre en place une CSP restrictive et, si possible, isoler via `iframe` sandbox.
- Tenir un registre des sources approuvées et de leurs dates de validation.

## Contraintes & dépendances
- Dépendances aux services tiers pour les cartes HTML et miniatures.
- Respect des politiques d’intégration des sites tiers (scripts, iframes, etc.).

## Mesures de succès
- Adoption : au moins 60 % des pilotes consultent la page météo chaque semaine.
- Réduction des liens externes partagés par email / messagerie interne.

## Questions ouvertes
- Souhaite-t-on un champ “catégorie” (METAR, TAF, radar pluie, vent, etc.) ? oui, mais cela doit être une chaine de caractères libre, pas une liste prédéfinie (pour éviter les contraintes de maintenance).
- Faut-il un contrôle de validité des URLs d’images et de liens ? Non, mais il faut documenter que les administrateurs sont responsables du contenu qu’ils ajoutent.
- Faut-il une limitation du nombre de cartes ? à priori, non.

## Plan d’implémentation
Voir le plan détaillé dans [doc/plans/meteo_plan.md](doc/plans/meteo_plan.md).
