# Plan d'implementation - Messages du jour (MOTD)

Date: 17 juillet 2026 (mise a jour suite aux evolutions du PRD : cible par liste de diffusion/utilisateur unique, reponses aux messages, messages d'alarme generes par GVV)
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
    - Definir la table des messages (contenu, dates, niveau "Urgent/Important/Info/Alerte", cible, audit).
    - Definir la cible d'un message: tous les utilisateurs, liste de diffusion existante (reutilisation des listes email), ou utilisateur unique.
    - Definir une origine de message (cree par un administrateur ou genere automatiquement par GVV pour une alarme), les messages generes restant modifiables par un administrateur.
    - Definir la table media (fichiers image televerses) et la liaison message-media.
    - Definir la table des reponses (message_id, auteur, contenu, date), visibles par les destinataires du message initial et par son editeur.
    - Definir la table d'etat utilisateur (masque individuel, masque global, pris connaissance).
    - Verifier les index pour filtrage performant des messages actifs et des messages par liste de diffusion.
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
    - CRUD administrateur des messages, avec cible tous utilisateurs, liste de diffusion ou utilisateur unique.
    - Requete des messages actifs pour un utilisateur, y compris ceux recus via une liste de diffusion.
    - Filtrage des messages par liste de diffusion.
    - CRUD des reponses a un message (creation par un destinataire ou par un administrateur).
    - Point d'entree pour la creation automatique de messages d'alarme par GVV.
    - Persistance des actions utilisateur (masquer, masquer tous, pris connaissance).
  - Validation:
    - Tests unitaires modeles verts.
    - Cas limites couverts (hors periode, message expire, priorite, message sans destinataire valide).

- [ ] 5. Configurer metadonnees et formulaires admin
  - Actions:
    - Ajouter les definitions metadata des champs.
    - Brancher les composants formulaire/liste existants.
    - Ajouter la selection de la cible du message (tous, liste de diffusion existante, utilisateur unique).
    - Ajouter l'upload d'images et l'insertion de reference Markdown `![alt](url)` depuis l'editeur.
    - Ajouter validations serveur (dates coherentes, contenu non vide, niveau valide, cible valide).
  - Validation:
    - Formulaire admin fonctionnel (creation, edition, suppression).
    - Messages d'erreur explicites et traduisibles.
    - Upload image operationnel avec retour d'URL interne exploitable dans le contenu.
    - Selection de cible (liste de diffusion / utilisateur unique) fonctionnelle.

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
    - Rendre la liste scrollable avec accordeon par message, triable par date de debut ou par niveau.
    - Afficher les reponses existantes apres chaque message.
  - Validation:
    - Comportement conforme sur les 3 scenarios: aucun message, info seule, urgent/important.
    - Affichage correct desktop/mobile.
    - Reponses visibles uniquement par les destinataires du message et son editeur.

- [ ] 8. Implementer les actions utilisateur sur messages
  - Actions:
    - Action "Masquer ce message".
    - Action "Masquer tous les messages".
    - Action "J'ai pris connaissance" (si retenue).
    - Action "Repondre a ce message", avec notification/visibilite de la reponse pour l'editeur et les autres destinataires.
    - Action administrateur "Repondre" a une reponse recue.
  - Validation:
    - Persistences verifiees apres rechargement.
    - Retour utilisateur visible apres chaque action.
    - Une reponse est visible par tous les destinataires du message initial et par son editeur, et par personne d'autre.

- [ ] 9. Implementer la page "Tous mes messages"
  - Actions:
    - Ajouter page dediee listant les messages applicables a l'utilisateur (cible directe, liste de diffusion, ou tous).
    - Implementer accordeon pour lecture detaillee, incluant les reponses associees.
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
    - Interdire HTML arbitraire dans le contenu (messages et reponses).
    - Assurer rendu Markdown compatible avec l'existant.
    - Valider upload image (MIME reel, extension, taille max) et servir les images via endpoint controle.
    - Verifier que seuls les destinataires d'un message (cible directe, liste de diffusion, ou tous) et son editeur peuvent voir/ecrire des reponses.
    - Verifier protections CSRF et controle d'acces.
  - Validation:
    - Tests negatifs passes (XSS simple, image invalide, acces non autorise a un message ou une reponse, requete invalide).
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
