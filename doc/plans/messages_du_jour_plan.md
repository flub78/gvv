# Plan d'implementation - Messages du jour (MOTD)

Date: 19 juin 2026
Reference PRD: `doc/prds/messages_du_jour_prd.md`

## Objectif
Implementer le module "Messages du jour" de bout en bout avec validation a chaque etape.

## Suivi d'avancement

- [ ] 1. Cadrer les decisions fonctionnelles ouvertes
  - Actions:
    - Confirmer si le titre est obligatoire ou optionnel.
    - Definir la politique d'affichage des messages expires sur "Tous mes messages".
    - Definir la regle de tri par defaut (date, priorite, ou combinee).
  - Validation:
    - Decisions documentees et approuvees.
    - Aucune ambiguite restante dans le PRD.

- [ ] 2. Concevoir le modele de donnees
  - Actions:
    - Definir la table des messages (contenu, dates, niveau, cible, audit).
    - Definir la table media (fichiers image televerses) et la liaison message-media.
    - Definir la table d'etat utilisateur (masque individuel, masque global, pris connaissance).
    - Verifier les index pour filtrage performant des messages actifs.
  - Validation:
    - Schema valide techniquement.
    - Champs d'audit presents (`created_at`, `updated_at`, `created_by`, `updated_by`).

- [ ] 3. Ecrire et tester les migrations
  - Actions:
    - Creer la migration SQL/PHP pour les nouvelles tables.
    - Mettre a jour la version de migration.
    - Tester migration up/down en environnement local.
  - Validation:
    - Migration appliquee sans erreur.
    - Rollback fonctionnel.

- [ ] 4. Implementer la couche modele
  - Actions:
    - CRUD administrateur des messages.
    - Requete des messages actifs pour un utilisateur.
    - Persistance des actions utilisateur (masquer, masquer tous, pris connaissance).
  - Validation:
    - Tests unitaires modeles verts.
    - Cas limites couverts (hors periode, message expire, priorite).

- [ ] 5. Configurer metadonnees et formulaires admin
  - Actions:
    - Ajouter les definitions metadata des champs.
    - Brancher les composants formulaire/liste existants.
    - Ajouter l'upload d'images et l'insertion de reference Markdown `![alt](url)` depuis l'editeur.
    - Ajouter validations serveur (dates coherentes, contenu non vide, niveau valide).
  - Validation:
    - Formulaire admin fonctionnel (creation, edition, suppression).
    - Messages d'erreur explicites et traduisibles.
    - Upload image operationnel avec retour d'URL interne exploitable dans le contenu.

- [ ] 6. Implementer les controleurs d'administration
  - Actions:
    - Creer routes/actions de gestion (liste, creer, modifier, supprimer).
    - Appliquer controle d'acces administrateur.
    - Journaliser les actions critiques.
  - Validation:
    - Utilisateur non admin bloque proprement.
    - Parcours CRUD admin complet valide.

- [ ] 7. Implementer la section repliable dashboard
  - Actions:
    - Afficher les messages actifs dans une section repliable.
    - Definir l'etat par defaut: deplie si urgent/important non consulte, sinon replie.
    - Rendre la liste scrollable avec accordeon par message.
  - Validation:
    - Comportement conforme sur les 3 scenarios: aucun message, info seule, urgent/important.
    - Affichage correct desktop/mobile.

- [ ] 8. Implementer les actions utilisateur sur messages
  - Actions:
    - Action "Masquer ce message".
    - Action "Masquer tous les messages".
    - Action "J'ai pris connaissance" (si retenue).
  - Validation:
    - Persistences verifiees apres rechargement.
    - Retour utilisateur visible apres chaque action.

- [ ] 9. Implementer la page "Tous mes messages"
  - Actions:
    - Ajouter page dediee listant les messages applicables a l'utilisateur.
    - Implementer accordeon pour lecture detaillee.
    - Respecter la politique active/passee definie a l'etape 1.
  - Validation:
    - Navigation accessible depuis dashboard.
    - Contenu affiche conforme au role et a la politique de visibilite.

- [ ] 10. Ajouter les traductions FR/EN/NL
  - Actions:
    - Ajouter toutes les cles de labels, boutons, erreurs et confirmations.
    - Verifier coherence de vocabulaire entre dashboard et page dediee.
  - Validation:
    - Aucun texte en dur restant dans l'UI.
    - Bascule de langue sans regression visible.

- [ ] 11. Verrouiller securite et rendu contenu
  - Actions:
    - Interdire HTML arbitraire dans le contenu.
    - Assurer rendu Markdown compatible avec l'existant.
    - Valider upload image (MIME reel, extension, taille max) et servir les images via endpoint controle.
    - Verifier protections CSRF et controle d'acces.
  - Validation:
    - Tests negatifs passes (XSS simple, image invalide, acces non autorise, requete invalide).
    - Aucun comportement dangereux observe.

- [ ] 12. Tester performances et ergonomie
  - Actions:
    - Mesurer impact au chargement du dashboard.
    - Verifier lisibilite des messages et clarte des actions utilisateur.
    - Verifier usage mobile/tablette.
  - Validation:
    - Temps d'affichage acceptable.
    - UX validee par un test manuel cible.

- [ ] 13. Ecrire et executer les tests automatiques
  - Actions:
    - Ajouter tests unitaires (modeles/regles metier).
    - Ajouter tests integration (CRUD + visibilite).
    - Ajouter smoke test UI du parcours principal.
  - Validation:
    - Suite PHPUnit ciblee verte.
    - Smoke UI vert.

- [ ] 14. Recette fonctionnelle finale
  - Actions:
    - Executer les parcours: creation admin, affichage dashboard, masquage, consultation historique.
    - Documenter les ecarts eventuels.
  - Validation:
    - Tous les criteres d'acceptation valides.
    - Ecarts corriges ou explicitement deferes.

- [ ] 15. Preparation de mise en production
  - Actions:
    - Verifier checklist de deploiement et rollback.
    - Planifier fenetre de deploiement.
    - Preparer verification post-deploiement.
  - Validation:
    - Checklist signee.
    - Aucune action bloquante restante.

## Definition de fini
- Toutes les etapes ci-dessus sont cochees.
- Les validations associees sont confirmees.
- Le module est conforme au PRD et stable en environnement cible.
