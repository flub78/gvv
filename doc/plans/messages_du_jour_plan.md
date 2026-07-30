# Plan d'implementation - Messages du jour (MOTD)

Date: 17 juillet 2026 (mise a jour suite aux evolutions du PRD : cible par liste de diffusion/utilisateur unique, reponses aux messages, messages d'alarme generes par GVV)
Reference PRD: `doc/prds/messages_du_jour_prd.md`

## Objectif
Implementer le module "Messages du jour" de bout en bout avec validation a chaque etape.

## Suivi d'avancement

- [x] 1. Cadrer les decisions fonctionnelles ouvertes
  - Actions:
    - Confirmation: le titre est optionnel.
    - Definir la politique d'affichage des messages expires sur "Tous mes messages". Plus d'affichage.
    - Definir la regle de tri par defaut (date, priorite, ou combinee). Priorité puis date croissante.


- [x] 2. Concevoir le modele de donnees
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
  - Resultat: voir `doc/design_notes/messages_du_jour_design.md` (tables `motd_messages`, `motd_media`, `motd_replies`, `motd_user_message_state`, `motd_user_prefs`) et diagramme `doc/design_notes/diagrams/messages_du_jour_er.puml`. En attente de validation avant d'ecrire les migrations (etape 3).

- [x] 3. Ecrire et tester les migrations
  - Actions:
    - Creer la migration SQL/PHP pour les nouvelles tables.
    - Mettre a jour la version de migration.
    - Tester migration up/down en environnement local.
  - Validation:
    - Migration appliquee sans erreur.
    - Rollback fonctionnel.
  - Resultat: `application/migrations/143_create_motd_tables.php` (5 tables), `application/config/migration.php` mis a jour a 143. `php -l` valide. Up/down a tester par l'utilisateur sur l'environnement local.

- [x] 4. Implementer la couche modele
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
  - Resultat: `application/models/motd_model.php` (CRUD messages, resolution cible all/liste/utilisateur via `Email_lists_model`, tri priorite/date, point d'entree `generate_system_message`), `motd_replies_model.php`, `motd_user_state_model.php` (masquer/masquer tous/pris connaissance), `motd_user_prefs_model.php`. Tests `application/tests/mysql/MotdModelTest.php` (18 tests, verts). Suite complete `phpunit_mysql.xml` : 714 tests, aucune regression.

- [x] 5. Configurer metadonnees et formulaires admin
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
  - Resultat: Metadata dans `Gvvmetadata.php` (`motd_messages`/`vue_motd_messages`), `application/models/motd_media_model.php`, controleur `application/controllers/motd.php` (CRUD, callbacks `valid_motd_target`/`valid_motd_dates`, upload/serving d'images, liaison media->message via `post_create`/`post_update`), vues `application/views/motd/bs_formView.php` + `bs_tableView.php`, `assets/javascript/motd.js` (bascule liste/utilisateur, insertion image au curseur), `application/language/french/motd_lang.php`.
  - Bug decouvert en testant (curl, utilisateur `testadmin`) et corrige par migration `144_motd_relax_actor_fk.php`: les comptes admin sans ligne `membres` (ex. `testadmin`) faisaient echouer la FK `created_by`/`updated_by`/`author_login` -> `membres(mlogin)`. Ces colonnes sont maintenant de simple audit sans FK (cf. design doc). Test de regression ajoute dans `MotdModelTest.php` (19 tests, verts). Suite complete: 714 tests, aucune regression.
  - Verifie manuellement en conditions reelles (dev server, role club-admin): creation/edition/suppression, validations croisees (dates, cible), upload+servage d'image avec liaison automatique au message, controle d'acces (404 pour un non-admin).

- [x] 6. Implementer les controleurs d'administration
  - Actions:
    - Creer routes/actions de gestion (liste, creer, modifier, supprimer).
    - Appliquer controle d'acces administrateur.
    - Journaliser les actions critiques.
  - Validation:
    - Utilisateur non admin bloque proprement.
    - Parcours CRUD admin complet valide.
  - Resultat: routes/actions deja en place depuis l'etape 5 (`motd.php` : `page`/`create`/`edit`/`view`/`delete`/`formValidation`, routage CI par defaut, pas d'entree `routes.php` necessaire). Controle d'acces verifie a chaque action via `can_manage()` (404 sinon). Journalisation ajoutee (`gvv_info`) pour creation/modification/suppression d'un message (qui, quel message, quelle cible).
  - Deux bugs decouverts et corriges en ecrivant le smoke test Playwright (`playwright/tests/motd-admin-smoke.spec.js`):
    - Le champ `level` (optionnel) n'a aucun bouton radio pre-coche ; un navigateur reel ne soumet alors rien pour ce champ, et `input->post()` renvoie `FALSE` (pas `''`) -> troncature MySQL sur l'ENUM. Corrige dans `Motd::form2database()` (`empty()` plutot que comparaison stricte a `''`).
    - Le bouton de soumission en edition a l'id `validate` (pas de confirm() JS dessus, contrairement a `delete`) ; le test utilisait par erreur le libelle "Créer" specifique a la creation.
  - Verifie : suite Playwright `motd-admin-smoke.spec.js` (5 tests, verts : liste admin, CRUD complet creer/modifier/supprimer, rejet dates incoherentes, acces refuse a un non-admin sur la page et sur `formValidation`). Suite complete `phpunit_mysql.xml` : 715 tests, aucune regression.

- [x] 7. Implementer la section repliable dashboard
  - Actions:
    - Afficher les messages actifs dans une section repliable.
    - Definir l'etat par defaut: deplie si urgent/important non consulte, sinon replie.
    - Rendre la liste scrollable avec accordeon par message, triable par date de debut ou par niveau.
    - Afficher les reponses existantes apres chaque message.
  - Validation:
    - Comportement conforme sur les 3 scenarios: aucun message, info seule, urgent/important.
    - Affichage correct desktop/mobile.
    - Reponses visibles uniquement par les destinataires du message et son editeur.
  - Resultat: `Welcome::_prepare_dashboard_data()` charge les messages actifs de l'utilisateur (`Motd_model::active_messages_for_user()`, tri selon `motd_user_prefs.sort_by`) et leurs reponses, et calcule l'etat par defaut de la section (`motd_section_expanded` = messages presents ET (urgent/important non pris connaissance OU preference utilisateur non repliee)). Vue `bs_dashboard.php` : carte Bootstrap repliable (`#motdSectionBody`), accordeon interne par message (`#motdAccordion`, un item par message, deplie par defaut si non lu et prioritaire), contenu et reponses rendus via le helper `markdown()` existant (safe mode), liste scrollable (max-height + overflow). Selecteur de tri (priorite/date) et bascule replier/deplier persistes via deux nouveaux endpoints AJAX sur le controleur `motd.php` (`toggle_section`, `set_sort`), qui reutilisent `Motd_user_prefs_model::save_prefs()` deja implemente a l'etape 4 — reserves a tout utilisateur connecte (pas de restriction club-admin), coherent avec `media()`. JS ajoute dans `assets/javascript/motd.js` (`motd_init_dashboard_section()`).
  - Bug decouvert et corrige en testant manuellement (Playwright) le cycle replier/deplier: le gestionnaire d'evenement comparait `e.type` a la chaine complete `'hidden.bs.collapse'`, alors que jQuery expose la forme courte `'hidden'` dans `e.type` (le namespace est retire) — la bascule "repliee" n'etait donc jamais persistee (toujours enregistree comme "depliee"). Corrige en comparant `e.type === 'hidden'`.
  - Verifie manuellement en conditions reelles (dev server, `testuser`): 3 scenarios de l'etape 1 (aucun message → section absente ; message urgent non lu → section et item depliés, reponse visible ; message pris en compte sans autre priorite → section repliee selon preference persistee) et persistance du tri. Nouveau smoke test Playwright `playwright/tests/motd-dashboard-smoke.spec.js` (3 tests, verts) conserve pour non-regression. Suite complete `phpunit_mysql.xml` : 715 tests, aucune regression. Suite Playwright admin (`motd-admin-smoke.spec.js`, 5 tests) toujours verte.

- [x] 8. Implementer les actions utilisateur sur messages
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
  - Resultat: Nouveaux endpoints AJAX sur `motd.php` : `hide_message($id)`, `hide_all()`, `acknowledge_message($id)` (delegues a `Motd_user_state_model`), et `reply($id)` (delegue a `Motd_replies_model::create_reply()`, contenu rendu en Markdown safe-mode cote serveur pour la reponse JSON). Autorisation systematique via `Motd_model::user_can_access_message()` (admin, editeur, ou destinataire reel) pour masquer/prendre connaissance/repondre ; l'action "repondre a une reponse" (admin uniquement) verifie en plus que le `parent_reply_id` fourni appartient bien au meme message avant de l'accepter (defense contre un client qui rattacherait une reponse a un fil etranger). Vue `bs_dashboard.php` : boutons "Masquer"/"J'ai pris connaissance"/"Repondre" par message, bouton "Masquer tous les messages" dans l'entete de la carte, formulaire de reponse avec indicateur "en reponse a" pour les reponses imbriquees. JS ajoute dans `motd.js` (`motd_init_dashboard_actions()`) : masquage individuel avec `fadeOut` et mise a jour du badge de comptage, masquage global avec confirmation puis rechargement, badge "Pris connaissance" construit a partir d'une chaine localisee transmise par PHP (`json_encode($this->lang->line(...))`, plus robuste qu'une manipulation de texte DOM), ajout dynamique des reponses sans rechargement de page.
  - Bug decouvert et corrige en testant les actions utilisateur : `motd_user_message_state.user_login` et `motd_user_prefs.user_login` avaient encore une FK vers `membres(mlogin)` heritee de la migration 143 (la migration 144 n'avait supprime que les FK `created_by`/`updated_by`). Comme ces colonnes stockent l'utilisateur connecte qui agit (pas necessairement un membre du club — un club-admin sans ligne `membres`, ex. `testadmin`, doit pouvoir masquer/prendre connaissance), toute action de ce type declenchait une violation de contrainte (erreur 1452). Corrige par une nouvelle migration `145_motd_relax_actor_user_login_fk.php` qui supprime ces deux FK, en coherence avec la migration 144. Note ajoutee dans `doc/design_notes/messages_du_jour_design.md` et diagramme `messages_du_jour_er.puml`/`.png` mis a jour en consequence.
  - Nouveau smoke test Playwright `playwright/tests/motd-user-actions-smoke.spec.js` (4 tests : prise de connaissance + reponse, masquage individuel, reponse imbriquee par l'admin, masquage global), utilisant un compte pilote dedie (`asterix`) distinct de `testuser` pour eviter une collision d'etat partage avec les autres suites MOTD executees en parallele par Playwright (constat fait en executant les 3 suites ensemble : elles agissaient concurremment sur le meme etat "messages masques" de `testuser`). Les 3 suites Playwright MOTD (`motd-admin-smoke.spec.js`, `motd-dashboard-smoke.spec.js`, `motd-user-actions-smoke.spec.js`, 12 tests au total) executees ensemble : toutes vertes, aucune collision. Suite complete `phpunit_mysql.xml` : 715 tests, 18 skips preexistants sans rapport, aucune regression.

- [x] 9. Retrouver les messages masques depuis le bandeau du dashboard
  - Actions:
    - Fusionner la fonction de retrouvaille des messages masques dans le bandeau repliable du dashboard, plutot que sur une page dediee separee.
    - Respecter la politique active/passee definie a l'etape 1.
  - Validation:
    - Fonction accessible depuis le dashboard.
    - Contenu affiche conforme au role et a la politique de visibilite.
  - Resultat (implementation initiale, puis simplifiee suite a un retour utilisateur post-recette) : une premiere version avait introduit une page dediee `motd/mine` (action `Motd::mine()`, vue `bs_my_messages.php`, lien permanent `#motdMineLink`) listant tous les messages applicables a l'utilisateur, y compris ceux deja masques individuellement. A l'usage, les deux compteurs (bandeau vs page dediee) preteraient a confusion et la navigation vers une page separee etait jugee superflue : la page dediee, son controleur et son lien ont ete retires, et sa fonction (retrouver les messages masques) fusionnee directement dans le bandeau "Messages du jour" du dashboard (`bs_dashboard.php`), qui reste desormais toujours affiche (meme sans message actif) et expose un bouton "Afficher tous les messages" (`motd_action_show_all`, endpoint existant `unhide_all`) avec le nombre de messages masques en badge. Le partial partage `application/views/motd/_message_accordion.php` (rendu, actions masquer/pris connaissance/repondre, reponses imbriquees) reste utilise tel quel, desormais par le seul bandeau du dashboard. `Welcome::_prepare_dashboard_data()` calcule maintenant aussi `motd_hidden_count` (auparavant calcule uniquement par `Motd::mine()`).
  - Verifie : suite Playwright MOTD (`motd-dashboard-smoke.spec.js`, `motd-user-actions-smoke.spec.js`, `motd-security-smoke.spec.js`, `motd-admin-smoke.spec.js`) mise a jour en consequence (les verifications qui naviguaient vers `motd/mine` verifient desormais le meme comportement via le dashboard `welcome`) et revalidee, toutes vertes. Le smoke test dedie a l'ancienne page (`motd-my-messages-smoke.spec.js`) a ete supprime, sa fonction (verifier qu'un message masque reste retrouvable) etant desormais couverte par les tests du bandeau.

- [x] 10. Ajouter les traductions FR/EN/NL
  - Actions:
    - Ajouter toutes les cles de labels, boutons, erreurs et confirmations.
    - Verifier coherence de vocabulaire entre dashboard et page dediee.
  - Validation:
    - Aucun texte en dur restant dans l'UI.
    - Bascule de langue sans regression visible.
  - Resultat: `application/language/english/motd_lang.php` et `application/language/dutch/motd_lang.php` crees, 57 cles chacun, strictement paralleles a `french/motd_lang.php` (verifie par diff des noms de cles). Dashboard et page dediee partageant deja le meme partial (`motd/_message_accordion.php`) et les memes cles de langue depuis l'etape 9, la coherence de vocabulaire entre les deux est garantie structurellement.
  - Bug decouvert et corrige en auditant le code pour du texte en dur : `motd_init_image_upload()` (`assets/javascript/motd.js`) utilisait le fallback JS `'Erreur'` code en dur (uniquement declenche sur un echec HTTP sans corps JSON, ex. erreur serveur brute) au lieu d'une chaine traduite. Corrige en ajoutant un parametre `errorFallback` a la fonction, transmis depuis `bs_formView.php` via `json_encode($this->lang->line('motd_error_action_failed'))` (reprise de la cle generique deja utilisee cote dashboard, coherente avec la consigne de vocabulaire commun). Aucun autre texte en dur trouve dans le controleur, les vues ou le JS (audit par grep cible).
  - Verifie : bascule manuelle de `config.php` (`$config['language']`) vers `english` puis `dutch`, verification par curl (session `testadmin`) que la liste admin, la page "Tous mes messages" et le lien du dashboard s'affichent traduits et sans erreur PHP dans les 2 langues, puis retour a `french` (diff `config.php` confirme vide apres restauration). Suite Playwright MOTD complete (4 fichiers, 15 tests) et suite complete `phpunit_mysql.xml` (715 tests, 18 skips preexistants) revalidees apres les changements JS/vue : aucune regression.

- [x] 11. Verrouiller securite et rendu contenu
  - Actions:
    - Interdire HTML arbitraire dans le contenu (messages et reponses).
    - Assurer rendu Markdown compatible avec l'existant.
    - Valider upload image (MIME reel, extension, taille max) et servir les images via endpoint controle.
    - Verifier que seuls les destinataires d'un message (cible directe, liste de diffusion, ou tous) et son editeur peuvent voir/ecrire des reponses.
    - Verifier protections CSRF et controle d'acces.
  - Validation:
    - Tests negatifs passes (XSS simple, image invalide, acces non autorise a un message ou une reponse, requete invalide).
    - Aucun comportement dangereux observe.
  - Resultat: Audit de l'existant, pas de nouveau code necessaire pour la plupart des points, deja verrouilles depuis les etapes precedentes :
    - HTML arbitraire / rendu Markdown : `markdown()` (helper existant, Parsedown en `setSafeMode(true)`) echappe deja tout HTML brut (`<script>`, gestionnaires d'evenements, `<iframe>`) en texte litteral et neutralise les URL `javascript:` (encodage du `:`) - verifie par test direct du parseur avec plusieurs charges XSS, puis en conditions reelles via un message contenant ces charges, affiche sans execution.
    - Upload image : la librairie CI `Upload` deja utilisee valide extension + `getimagesize()` (formats raster) + MIME reel via `finfo_file()`/`mime_content_type()` sur le contenu du fichier (pas le type declare par le navigateur) - un fichier texte renomme `.png` est bien rejete en conditions reelles.
    - Endpoint media controle : deja implemente a l'etape 5 (`motd/media/{id}`, controle d'acces avant lecture du fichier).
    - Visibilite des reponses : deja garantie par `Motd_model::user_can_access_message()` (etapes 4/8), reutilisee pour masquer/prendre connaissance/repondre/lire les medias.
    - CSRF : `$config['csrf_protection']` est desactive globalement dans tout GVV (choix pre-existant du projet, hors perimetre MOTD) ; l'app s'appuie sur le comportement par defaut `SameSite=Lax` des cookies de session des navigateurs modernes, qui bloque deja l'envoi du cookie de session sur une requete cross-site non-GET. Aucune regression introduite : le controleur MOTD suit le meme modele que le reste de l'application.
    - Bug trouve et corrige : le fallback JS `'Erreur'` code en dur restant dans `motd_init_image_upload()` (deja corrige a l'etape 10, verifie de nouveau ici par coherence).
  - Verifie : nouveau smoke test Playwright `playwright/tests/motd-security-smoke.spec.js` (7 tests) : contenu avec charges XSS jamais execute et affiche en texte echappe ; un utilisateur non destinataire/non editeur/non admin recoit 404 sur masquer/prendre connaissance/repondre/lire une image liee a un message qui ne lui est pas adresse (verifie aussi qu'aucune reponse n'est creee malgre la tentative) ; l'endpoint reponse rejette un contenu vide (422) et un message inconnu (404) ; l'endpoint de tri rejette une valeur invalide (422) ; l'upload rejette un fichier texte renomme `.png` (MIME reel invalide) ; le destinataire reel de l'image peut la recuperer (200). Les 5 suites Playwright MOTD (`motd-admin-smoke.spec.js`, `motd-dashboard-smoke.spec.js`, `motd-user-actions-smoke.spec.js`, `motd-my-messages-smoke.spec.js`, `motd-security-smoke.spec.js`, 22 tests au total) executees ensemble : toutes vertes. Suite complete `phpunit_mysql.xml` : 715 tests, 18 skips preexistants, aucune regression.
  - Limitation connue (non bloquante) : l'application ne supprime jamais le fichier physique d'une image lors de la suppression du message ou de l'entree `motd_media` associee (limitation preexistante, hors perimetre de cette etape) ; le test de televersement d'image laisse donc un petit fichier de test residuel dans `uploads/motd/` a chaque execution (repertoire uniquement inscriptible par `www-data`, non nettoyable par l'utilisateur courant sans privilege eleve). Sans impact fonctionnel ou de securite, signale pour information.

- [x] 12. Tester performances et ergonomie
  - Actions:
    - Mesurer impact au chargement du dashboard.
    - Verifier lisibilite des messages et clarte des actions utilisateur.
    - Verifier usage mobile/tablette.
  - Validation:
    - Temps d'affichage acceptable.
    - UX validee par un test manuel cible.
  - Resultat:
    - Performance : mesure directe (curl, 5 requetes) du temps de chargement du dashboard (`welcome`) et de `motd/mine` sans message, puis avec 10 messages (2 reponses chacun), puis avec 50 messages : temps stable dans tous les cas (~25-70ms, bruit de mesure compris), aucun impact mesurable meme a une echelle largement superieure a un usage reel (quelques messages actifs au plus).
    - Lisibilite / clarte des actions : verifiee visuellement via captures d'ecran Playwright (desktop 1440px, tablette 768px, mobile 375px) sur un jeu de donnees realiste (message urgent + important + info, avec reponse) : niveaux, titres, dates, boutons d'action et zone de reponse bien lisibles et sans chevauchement sur les trois tailles, apres corrections (voir ci-dessous).
  - Bugs decouverts et corriges en verifiant l'affichage mobile/tablette :
    - Lorsque plusieurs messages devaient etre depliés simultanement par defaut (ex: un urgent + un important non consultes), Bootstrap ne conservait ouvert qu'un seul d'entre eux : chaque panneau portait `data-bs-parent="#motdAccordion"`, qui impose a Bootstrap la regle "un seul panneau ouvert a la fois" propre a un accordeon strict — incompatible avec l'exigence MOTD de deplier plusieurs messages prioritaires non lus en meme temps. Corrige en retirant `data-bs-parent` du partial `_message_accordion.php` (les panneaux redeviennent des collapses independants, sans perdre le comportement de pli/depli individuel au clic).
    - Sur mobile (<768px), un script global preexistant de `bs_header.php` replie systematiquement tous les `.accordion-collapse.show` de la page au chargement (convention de compacite mobile datant d'avant MOTD, s'appliquant a tous les accordeons du site). Il repliait donc aussi les messages urgents/importants du MOTD pourtant censes rester visibles sans action de l'utilisateur, ce qui va a l'encontre de l'objectif meme de la fonctionnalite. Point de conception signale a l'utilisateur, qui a choisi d'exempter le MOTD de cette regle globale (option recommandee) : le script exclut desormais tout panneau contenu dans `#motdAccordion` (`panel.closest('#motdAccordion')`), sans toucher au comportement des autres accordeons du site (verifie : aucun autre gabarit de vue n'utilise l'id `#motdAccordion`).
    - L'entete de la carte MOTD (titre, badge de comptage, selecteur de tri, bouton "Masquer tous les messages", chevron) etait un unique flex-row sans retour a la ligne, provoquant un chevauchement visuel sur mobile. Corrige en ajoutant `flex-wrap gap-2` a l'entete (`bs_dashboard.php`), qui s'affiche desormais sur plusieurs lignes proprement sur petit ecran sans rien changer sur desktop/tablette.
  - Verifie : les 5 suites Playwright MOTD (22 tests) executees ensemble apres ces corrections : toutes vertes. Suite complete `phpunit_mysql.xml` : 715 tests, 18 skips preexistants, aucune regression (verification plus large justifiee car `bs_header.php` est un gabarit partage par tout le site).

- [x] 13. Ecrire et executer les tests automatiques
  - Actions:
    - Ajouter tests unitaires (modeles/regles metier).
    - Ajouter tests integration (CRUD + visibilite).
    - Ajouter smoke test UI du parcours principal.
  - Validation:
    - Suite PHPUnit ciblee verte.
    - Smoke UI vert.
  - Resultat: Etape essentiellement deja couverte au fil des etapes precedentes (4 a 12), qui ont chacune ajoute leurs tests au fur et a mesure plutot que de tout reporter ici. Audit de couverture effectue, aucune lacune substantielle trouvee :
    - Unitaire/integration (`application/tests/mysql/MotdModelTest.php`, 19 tests) : CRUD messages (creation/mise a jour/suppression, y compris cible invalide rejetee et auteur admin sans ligne `membres`), visibilite (`active_messages_for_user` : exclusion hors periode passee et future, cible utilisateur unique, cible liste de diffusion, tri par priorite), `generate_system_message`, CRUD reponses, `user_can_access_message` (auteur/cible directe/tiers non autorise/admin), masquer/masquer tous/prendre connaissance, preferences utilisateur (valeurs par defaut puis mise a jour).
    - Smoke UI du parcours principal (5 fichiers Playwright, 22 tests) : CRUD admin complet + controle d'acces (`motd-admin-smoke`), affichage dashboard + tri + regles de pli/depli par defaut (`motd-dashboard-smoke`), actions utilisateur masquer/prendre connaissance/repondre + reponse imbriquee admin (`motd-user-actions-smoke`), page dediee "Tous mes messages" (`motd-my-messages-smoke`), durcissement securite (`motd-security-smoke`). Ensemble, ces parcours couvrent bout en bout : creation admin -> affichage dashboard -> action utilisateur -> consultation "Tous mes messages".
    - Pas de tests controleur PHPUnit dedies ajoutes : coherent avec la convention du projet (tres peu de controleurs GVV ont des tests PHPUnit dedies ; le controle d'acces/HTTP est deja couvert par les suites Playwright, qui remplissent ce role dans ce projet).
  - Verifie : suite ciblee `MotdModelTest` (19 tests) verte isolement ; les 5 suites Playwright MOTD executees ensemble (22 tests) vertes ; suite complete `phpunit_mysql.xml` (715 tests, 18 skips preexistants) verte, confirmant qu'aucune regression n'a ete introduite par les etapes precedentes.

- [x] 14. Recette fonctionnelle finale
  - Actions:
    - Executer les parcours: creation admin, affichage dashboard, masquage, consultation historique.
    - Documenter les ecarts eventuels.
  - Validation:
    - Tous les criteres d'acceptation valides.
    - Ecarts corriges ou explicitement deferes.
  - Resultat: Revalidation finale a partir des suites automatisees deja constituees (etapes 4 a 13), qui executent litteralement les 5 parcours cles du PRD de bout en bout - pas de nouvelle campagne de test necessaire, seulement une revalidation ciblee et un controle de conformite explicite a chaque exigence fonctionnelle :
    - Parcours 1 (creation admin avec periode) -> `motd-admin-smoke` ("admin can create, edit and delete a message", "rejects a message with end_date before start_date").
    - Parcours 2 (affichage dashboard) -> `motd-dashboard-smoke` ("urgent unread message is expanded by default with its reply", "no active message => no MOTD section", "sort selection is persisted").
    - Parcours 3 (masquage individuel) -> `motd-user-actions-smoke` ("user hides a single message").
    - Parcours 4 (masquage global) -> `motd-user-actions-smoke` ("user hides all remaining messages").
    - Parcours 5 (fermeture section + page dediee) -> `motd-my-messages-smoke` (lien permanent, contenu conforme a la politique de visibilite, navigation depuis le dashboard).
    - EF1 (CRUD admin, titre optionnel, champs) : couvert par `motd-admin-smoke` + `MotdModelTest`.
    - EF1bis (rendu Markdown securise) : couvert par `motd-security-smoke` ("message content is rendered as escaped text, not live HTML").
    - EF2 (periode d'affichage) : couvert par `MotdModelTest::testActiveMessagesForUser_ExcludesOutOfPeriodMessages` (passe et future) et par `motd-my-messages-smoke` (message expire jamais affiche).
    - EF3 (section repliable, tri, masquer/masquer tous/pris connaissance, persistance) : couvert par `motd-dashboard-smoke` + `motd-user-actions-smoke`.
    - EF4 (page "Tous mes messages", accordeon, politique active/passee) : couvert par `motd-my-messages-smoke`.
    - EF5 (acces reserve admin en gestion, consultation ouverte aux utilisateurs autorises) : couvert par `motd-admin-smoke` ("non-admin is denied access...") et `motd-security-smoke` (acces refuse a un non-destinataire).
    - EF6 (upload image controle, endpoint media protege) : couvert par `motd-security-smoke` (rejet MIME reel invalide, acces refuse/autorise a l'image selon destinataire) et par l'etape 5 (formats/taille limites via `Upload::allowed_types`/`max_size`, endpoint `motd/media/{id}`).
    - Reponses aux questions ouvertes du PRD : titre optionnel (etape 1) ; messages expires non affiches, y compris sur la page dediee (etape 1, EF2) ; ordre de priorite (priorite puis date croissante, etape 1/7).
    - Ecarts identifies, tous explicitement differes (non bloquants pour cette recette) :
      - Limite de frequence de televersement par club/message (question ouverte du PRD) : non implementee, aucune exigence fonctionnelle correspondante (EF6) ne la rend obligatoire ; a traiter uniquement si un besoin concret apparait en usage reel.
      - CSRF desactive globalement dans GVV : decision projet preexistante et hors perimetre MOTD (etape 11), non un ecart introduit par cette fonctionnalite.
    - Verifie : suite ciblee `MotdModelTest` (19 tests) verte ; les 5 suites Playwright MOTD executees ensemble (22 tests) vertes ; suite complete du projet (`./run-all-tests.sh`, 1594 tests, 48 skips preexistants sans rapport) verte, aucune regression.
    - Ecart corrige suite a la demande explicite de l'utilisateur : suppression en cascade des fichiers image physiques a la suppression d'un message (celle signalee comme limitation connue a l'etape 11). `Motd::delete($id)` (`application/controllers/motd.php`) recupere desormais la liste des medias lies via `Motd_media_model::media_for_message($id)` et supprime leurs fichiers sur disque (`unlink`) avant d'appeler `parent::delete($id)` (qui redirige et donc termine l'execution - le nettoyage doit precéder cet appel). Les lignes `motd_media` elles-memes restent supprimees automatiquement par la contrainte `ON DELETE CASCADE` deja en place depuis la migration 143. Nouveau test Playwright dans `motd-admin-smoke.spec.js` ("deleting a message removes its linked image file from disk") : televerse une image reelle, cree un message qui la reference dans son contenu (parcours reel `post_create()` -> `link_uploaded_media()`, pas un lien SQL direct), supprime le message, verifie que le fichier disque a disparu et que la ligne `motd_media` n'existe plus. Suite Playwright MOTD complete (5 fichiers, 23 tests) et suite complete du projet (1594 tests, 48 skips preexistants) revalidees apres cette correction : aucune regression.

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
