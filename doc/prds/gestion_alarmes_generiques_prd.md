# PRD — Gestion Générique des Alarmes

Date : 9 mai 2026 — mis à jour le 30 mai 2026 (portée par section), le 18 octobre 2026 (Lot 1 du MVP : catalogue d'alarmes par type de document, canaux, synthèses ; rôle élève et visite médicale des élèves) le 19 octobre 2026 (page unique « Mes alarmes », abonnements par section, alarmes propres toujours affichées) le 19 octobre 2026 (Lot 2 : vols sans cotisation, vols sans licence) et le 19 octobre 2026 (Lot 3 : emport passager, inactivité, compte débiteur ; rôle emport passager)

## Contexte

GVV gère déjà des cas d'alarme sur plusieurs points :
- validité documentaire (dates d'expiration via `archived_documents`),
- conditions d'expérience récente calculées dynamiquement (page `/alarmes`),
- contrôles de conformité ponctuels (ex. pilotes ayant volé sans cotisation),
- message du jour (MOTD) comme message d'information (référence produit : `doc/prds/messages_du_jour_prd.md`).

Ces capacités sont utiles mais fragmentées. Deux familles d'alarmes coexistent sans mécanisme commun :

- **Alarmes à date fixe** : basées sur une échéance connue (expiration d'un document, prochaine révision planifiée). La date est stockée en base.
- **Alarmes calculées** : dérivées dynamiquement des données métier (nombre d'atterrissages dans les 90 derniers jours, heures de vol dans les 24 derniers mois, vols effectués sans licence). Aucune date n'est stockée — l'état résulte d'un calcul sur les données.

Le club a besoin d'un mécanisme unique et réutilisable permettant de définir, déclencher et notifier des alarmes sur ces deux familles, et de laisser chaque membre choisir les alarmes qu'il veut recevoir et comment.

### Cas d'utilisation sans document

Certaines alarmes ne peuvent pas s'appuyer sur un document sans créer un document artificiel :

| Situation | Pourquoi pas de document |
|---|---|
| Emport passager : 3 atterrissages CDB dans les 90 derniers jours | Calculé sur le carnet de vol |
| Pilote ayant volé sans licence ou sans cotisation | Calculé en croisant vols et licences |
| Révision avion par compteur de temps de vol | Déclencheur = seuil d'heures, pas une date |
| Pilote inactif depuis N mois | Calculé sur le dernier vol enregistré |
| Maintien de qualification remorqueur (N lancements sur 12 mois) | Calculé sur logs de vol |
| Contrôle en vol biennal si activité insuffisante | Dérivé de l'activité vol |

## Objectifs
- Fournir un mécanisme commun de gestion des alarmes, utilisable par plusieurs modules.
- Permettre à chaque membre de consulter ses alarmes actives et, s'il en a le droit, de s'abonner section par section aux alarmes des autres membres, des machines et du club ; de choisir ses canaux et sa fréquence.
- Permettre aux administrateurs de configurer les alarmes d'un autre membre et d'abonner une catégorie de membres à une alarme.
- Couvrir les alarmes à date fixe (documents) et les alarmes calculées (vols, licences, expérience).
- Notifier les personnes concernées avant et après l'échéance, sans les submerger.
- Garantir la traçabilité des déclenchements et des notifications.
- Réduire la duplication de logique d'alarme dans les modules métier.

## Non-objectifs
- Remplacer toute logique métier existante de calcul de conformité (ex. règles détaillées d'expérience récente).
- Introduire une automatisation de sanction ou de blocage opérationnel.
- Remplacer les communications officielles hors GVV.

## Découpage du MVP

Le MVP est livré par lots. Les exigences de ce document précisent, quand c'est utile, le lot qui les porte.

| Lot | Contenu |
|---|---|
| **Lot 1** | Catalogue d'alarmes, page « Mes alarmes » réunissant la configuration (abonnements par alarme et par section, canaux, rappel par SMS, fréquence) et la liste des alarmes actives, configuration pour un autre membre et abonnement d'une catégorie par un administrateur, canaux email et message du jour, rappel court par SMS le lundi, synthèses hebdomadaires ou mensuelles. Rôle élève par section. Alarmes couvertes : documents archivés — expiration et documents obligatoires manquants (dont visite médicale, obligatoire pour les élèves). |
| **Lot 2** | Alarmes de conformité calculées : vols sans licence, vols sans cotisation. |
| **Lot 3** | Alarmes calculées d'expérience récente et de compte : emport passager (3 atterrissages en 90 jours), remorquage (10 remorqués en 12 mois), 120 jours sans voler, compte pilote débiteur. Rôle « emport passager » par section. |
| **Lot 4** | Désactivation d'une alarme précise : pour soi par un superviseur, pour tout le monde par un administrateur club, définitive ou jusqu'à une date, avec réactivation automatique et réactivation par un administrateur club. |
| Lots suivants | Alarmes de maintenance, autres alarmes d'expérience récente (règles de `alarmes.php`), vue consolidée avancée. |

## Portée
### Inclus
- Catalogue des alarmes auxquelles un membre peut s'abonner (Lot 1).
- Page « Mes alarmes » de chaque membre : liste des alarmes actives puis configuration (Lot 1).
- Alarmes propres de chaque membre, toujours présentes ; abonnements individuels par alarme et par section, configuration pour un autre membre par un administrateur, abonnements par catégorie de membres (Lot 1).
- Choix des canaux par le membre : email, message du jour ; rappel court optionnel par SMS le lundi (Lot 1).
- Synthèses périodiques des alarmes actives et à venir (Lot 1).
- Rôle « élève » attribuable dans chaque section (Lot 1).
- Alarmes liées aux documents archivés : expiration, documents obligatoires manquants (Lot 1).
- Alarmes de conformité calculées : vols sans licence, vols sans cotisation (Lot 2).
- Obligation de la visite médicale pour les élèves (Lot 1).
- Alarmes liées aux échéances de maintenance (machine, équipement ou autre entité maintenable).
- Alarmes calculées à partir des données de vol (expérience récente, inactivité, conditions d'emport).
- Portée par section : une alarme peut être commune à toutes les sections ou restreinte à une section spécifique.
- Suivi de l'état des alarmes : à venir, active, désactivée (pour soi ou pour tout le monde), résolue.

### Exclu
- Moteur de règles métier orienté expressions libres : le catalogue ne contient que des alarmes prévues par GVV, paramétrables mais non programmables par l'utilisateur.
- Escalade multi-canal (relance automatique d'un canal vers un autre, appel vocal).
- Contenu des alarmes par SMS : le SMS ne contient qu'un message court et le lien vers les alarmes dans GVV.
- Gestion de planning de maintenance complet (ce PRD ne couvre que l'alarme sur échéance).

## Personae & rôles
- Administrateur club : configure les alarmes du catalogue pour toutes les sections, configure les alarmes de n'importe quel membre, gère les abonnements des catégories de membres, supervise l'ensemble des alarmes.
- Responsable de section : configure les alarmes propres à sa section, consulte les alarmes de sa section.
- Responsable opérationnel (instruction, maintenance, administratif) : s'abonne aux alarmes de son périmètre (ex. visites médicales des élèves, vols sans licence) et les traite.
- Utilisateur/pilote : reçoit les alarmes qui le concernent personnellement (ex. expiration de sa visite médicale), choisit ses abonnements, ses canaux et sa fréquence de synthèse, consulte ses alarmes actives.
- Élève : membre ayant le rôle « élève » dans une section ; soumis aux obligations documentaires des élèves (visite médicale).

## Parcours clés
1. Un membre ouvre la page **Mes alarmes** : en haut, la configuration — ses canaux (email, message du jour), le rappel par SMS, la fréquence de sa synthèse (immédiate, hebdomadaire ou mensuelle) et, s'il a des droits de supervision, une matrice alarme × section où il coche les sections qui l'intéressent pour chaque alarme (ex. le vice-président avion ne coche que la section avion pour l'alarme du certificat médical) ; en dessous, la liste de ses alarmes actives et à venir.
2. Un administrateur ouvre la même page pour un autre membre et y définit sa configuration, ou abonne une catégorie de membres (ex. tous les instructeurs d'une section) à une alarme.
3. Quand une alarme se déclenche (ex. visite médicale d'un élève qui expire dans 30 jours), chaque abonné concerné en est informé selon ses choix.
4. Le lundi de la semaine ou le premier lundi du mois selon sa fréquence, l'abonné reçoit une synthèse de toutes ses alarmes encore actives ou qui vont s'activer prochainement ; s'il a activé le rappel par SMS, il reçoit aussi un SMS court avec le lien vers ses alarmes dans GVV.
5. À tout moment, le membre ouvre la page **Mes alarmes** qui liste ses propres alarmes et celles de ses abonnements, actives et à venir, avec un lien vers l'objet concerné.
6. Après traitement (ex. renouvellement du document), l'alarme est résolue et disparaît de la page Mes alarmes et des synthèses suivantes.
7. Un membre abonné à rien voit seulement ses propres alarmes sur la page Mes alarmes, sans pouvoir les désactiver ; il ne les reçoit par email, message du jour ou SMS que s'il a choisi un canal.
8. Un trésorier abonné au compte débiteur sait qu'un pilote a un échéancier de paiement : il désactive cette alarme pour lui jusqu'à la fin de l'échéancier. Un administrateur club constate qu'un pilote licencié dans un autre club apparaît dans « vols sans licence » : il désactive cette alarme pour tout le monde, avec un motif.

## Exigences fonctionnelles

1. **Catalogue des alarmes (Lot 1)**
   - GVV fournit un catalogue d'alarmes. Chaque alarme du catalogue a un nom, une description compréhensible par un membre, une portée (toutes sections ou une section), et un délai d'anticipation (nombre de jours avant l'échéance où elle est considérée « à venir »).
   - Le catalogue du Lot 1 porte sur les documents archivés :
     - une alarme par type de document à expiration (ex. visite médicale, assurance, manuel d'exploitation s'il a une date de validité), et non une alarme par document ;
     - une alarme pour les documents obligatoires manquants ;
     - une alarme des visites médicales des élèves, restreinte aux membres ayant le rôle « élève ».
   - Chaque type de document indique si des alarmes sont possibles sur ce type ; c'est le cas par défaut, y compris pour tous les types existants. Un type sans alarmes possibles, ou sans date d'expiration, n'a pas d'alarme dans le catalogue.
   - Le Lot 2 y ajoute deux alarmes de conformité calculées, sur les vols de l'**année civile en cours** :
     - **vol sans cotisation** : un pilote a volé (planeur, avion ou ULM) sans cotisation enregistrée pour l'année (réutilise le contrôle existant « Vols sans cotisation ») ;
     - **vol sans licence** : un pilote a volé dans une section sans la licence exigée par cette section pour l'année. Chaque section indique, dans sa fiche, le type de licence exigé pour y voler (aucun pour une section sans vols) ; la page « Licences par année » utilise le même paramètre. Un vol relève de la section où il est enregistré : un remorquage, enregistré dans la section planeur, est couvert par la licence planeur, sans règle particulière.
     - Les pilotes extérieurs ne sont pas contrôlés. Une telle alarme est active dès le premier vol concerné et disparaît dès que la cotisation ou la licence est enregistrée.
     - Un vol relève de la section où il est enregistré (vols planeur : la section qui gère les planeurs ; s'il y en a plusieurs, la première) : un abonnement à la section avion ne couvre que les vols de la section avion.
   - Le Lot 3 y ajoute quatre alarmes calculées, par section (dans la section qui gère les planeurs, seuls les vols planeur comptent, pas les remorquages) :
     - **emport passager** : un pilote ayant le rôle « emport passager » dans la section a fait moins de 3 atterrissages comme commandant de bord (y compris comme instructeur) dans cette activité au cours des 90 derniers jours. Elle est **à venir** quand le 3ᵉ atterrissage le plus récent sortira de la fenêtre de 90 jours dans le délai d'anticipation, **active** ensuite ; l'échéance est la date de ce 3ᵉ atterrissage plus 90 jours ;
     - **remorquage** : un pilote ayant le rôle « pilote remorqueur » dans une section qui gère les planeurs a fait moins de 10 remorqués au cours des 12 derniers mois (vols planeur où il est enregistré comme pilote remorqueur). À venir quand le 10ᵉ remorqué le plus récent sortira de la fenêtre de 12 mois dans le délai d'anticipation, active ensuite ; échéance : date de ce 10ᵉ remorqué plus 12 mois ;
     - **120 jours sans voler** : un membre actif de la section, non élève, n'a pas volé dans cette activité depuis plus de 120 jours (un vol avec instructeur compte) ; seuls les membres dont le dernier vol dans la section date de moins de 24 mois sont contrôlés. Échéance : dernier vol plus 120 jours ; à venir dans le délai d'anticipation, active ensuite. L'action attendue est un vol avec instructeur ;
     - **compte débiteur** : la dette du pilote dans la section dépasse le seuil d'alarme réglé sur la page des relances (même calcul que cette page). Active tant que la dette dépasse le seuil.
   - Un administrateur peut activer ou désactiver une alarme du catalogue et régler son délai d'anticipation ; pour une alarme documentaire, ce délai vaut par défaut le délai d'alerte du type de document.
   - Les types d'alarme sont extensibles : documentaire, maintenance, conformité calculée, expérience récente calculée.

2. **Alarmes propres et abonnements (Lot 1)**
   - **Alarmes propres** : tout membre actif a toujours les alarmes qui le concernent personnellement (ex. expiration de son certificat médical, document obligatoire manquant), sans abonnement. Il ne peut pas les désactiver.
   - **Abonnements** : un membre ayant les droits de supervision peut en plus s'abonner aux alarmes des autres — documents des autres membres, des machines, des sections et du club, et catégories de pilotes (ex. visites médicales des élèves). Un abonnement porte sur une alarme du catalogue **et une section** : il ne couvre que les membres actifs dans cette section, ou les machines et documents rattachés à cette section. Les documents qui ne dépendent d'aucune section (documents du club, machine sans section) relèvent d'une colonne **Club**.
   - Droits de supervision : administrateur club pour toutes les sections ; membres du CA et du bureau pour leurs sections ; instructeurs et responsables pédagogiques en plus pour les alarmes portant sur une catégorie de pilotes (ex. élèves) ; trésoriers en plus, dans leurs sections, pour les alarmes de vols sans cotisation ou sans licence (Lot 2) ; instructeurs et responsables pédagogiques en plus pour les alarmes d'emport passager, de remorquage et d'inactivité (Lot 3) ; documents privés réservés au bureau et à l'administrateur club. L'alarme de compte débiteur porte sur des données financières personnelles : elle n'est ouverte qu'à l'administrateur club, au bureau et aux trésoriers (y compris le super-trésorier, pour toutes les sections), pas au CA. La colonne Club est ouverte à tout membre ayant des droits de supervision dans au moins une section.
   - **Pilotes concernés** : une alarme qui porte sur un pilote (document de pilote, document manquant, cotisation, licence, expérience récente, compte) ne concerne que les pilotes actifs dans la section concernée ; les pilotes extérieurs et les sections où le pilote n'est pas actif sont exclus, pour toutes les alarmes. Exception : l'alarme de compte débiteur signale aussi un pilote qui n'est plus actif (un pilote parti doit encore être relancé) ; les pilotes extérieurs en restent exclus.
   - Aucun abonnement n'existe par défaut. Les alarmes propres ne sont envoyées (email, message du jour, SMS) que sur les canaux que le membre ou un administrateur a choisis : sans canal, elles restent seulement affichées dans GVV.

3. **Page Mes alarmes : configuration (Lot 1)**
   - Chaque membre dispose dans GVV d'une page « Mes alarmes », accessible depuis son espace personnel (menu et tableau de bord). La page présente d'abord la liste des alarmes (exigence 4), puis la configuration.
   - La carte « Mes alarmes » du tableau de bord affiche le nombre d'alarmes actives du membre (propres et abonnements), s'il y en a. Quand une section est active dans GVV, ce nombre se limite aux alarmes de cette section et la carte indique aussi le nombre total.
   - Pour un membre ayant des droits de supervision, la configuration présente une **matrice** : une ligne par alarme qu'il peut superviser, une colonne par section où il a ces droits (plus la colonne Club si l'alarme en a une), une case à cocher par croisement. Une case n'existe que si le membre a le droit correspondant. Une ligne, une colonne ou toute la matrice peut être cochée ou décochée en une fois (sauf les cases obligatoires). Un membre sans droit de supervision n'a pas de matrice ; la page lui indique que ses propres alarmes sont toujours suivies.
   - Le membre y choisit ses canaux : email, message du jour, ou les deux. Ce choix est commun à toutes ses alarmes, propres ou par abonnement. Aucun canal n'est présélectionné ni exigé : sans canal, ses alarmes restent seulement affichées dans GVV.
   - Le membre y active ou désactive le rappel par SMS (cf. exigence 6). Ce choix se fait uniquement sur cette page ; cocher l'option vaut consentement à recevoir ces SMS.
   - Le membre y choisit la fréquence de sa synthèse (cf. exigence 7).
   - La matrice indique aussi les cases cochées pour le membre par un administrateur, individuellement ou via une catégorie, avec leur origine ; celles rendues obligatoires ne peuvent pas être décochées.
   - Toute modification est confirmée à l'écran.

4. **Page Mes alarmes : liste des alarmes (Lot 1)**
   - En tête de page, avant la configuration, la page liste les alarmes **actives** et **à venir** du membre : ses alarmes propres, et celles de ses abonnements restreintes aux sections cochées (ex. pour l'alarme du certificat médical cochée en section avion : seulement les pilotes actifs dans la section avion dont le certificat est périmé ou va l'être).
   - Les alarmes sont classées en quatre catégories, distinguées visuellement (couleur et icône) dans la matrice, la liste et l'administration : documents, cotisation et licence, comptes, expérience récente. La matrice les regroupe par catégorie.
   - Pour chaque alarme, la page indique ce qui est concerné (membre, document, machine), l'échéance ou la date de déclenchement, l'état (à venir, active) et l'action attendue, avec un lien vers la page GVV où la traiter.
   - La liste est filtrable par alarme du catalogue et par section.
   - Par défaut, la liste suit la section active dans GVV : seules les alarmes de cette section sont affichées, ainsi que celles qui ne dépendent d'aucune section (colonne Club). Quand « Toutes » les sections sont sélectionnées, toutes les alarmes sont affichées. Le membre peut choisir une autre section ou toutes les sections dans le filtre de la page, sans changer la section active.
   - Quand des alarmes sont masquées par ce filtre, la page l'indique avec leur nombre et un lien pour les afficher toutes.
   - Ce filtrage ne concerne que l'affichage dans GVV : les synthèses (email, message du jour) et les rappels SMS portent toujours sur toutes les sections.
   - Une alarme résolue n'y figure plus.

5. **Configuration par un administrateur (Lot 1)**
   - Un administrateur club utilise la page Mes alarmes exactement comme un membre, pour lui-même.
   - La seule différence : la page lui présente en plus un sélecteur de membre, positionné par défaut sur son propre profil. En choisissant un autre membre, il voit et modifie la configuration de ce membre — cases de la matrice, canaux, rappel par SMS, fréquence — avec les mêmes règles que pour lui-même, et voit la liste de ses alarmes. Le membre voit ensuite ces abonnements sur sa page, avec le nom de l'administrateur qui les a créés.
   - Il peut abonner une **catégorie de membres** à une alarme. Les catégories reposent sur les rôles existants (ex. instructeurs, bureau, CA, mécaniciens), éventuellement restreints à une section : l'abonnement coche, chez chaque titulaire du rôle, la case de la section où il détient le rôle (toutes ses sections si aucune section n'est précisée, plus la colonne Club si l'alarme en a une). Un membre qui obtient le rôle est abonné, un membre qui le perd ne l'est plus, sans autre intervention.
   - Un administrateur peut rendre un abonnement, individuel ou par catégorie, non désabonnable ; sinon, le membre peut décocher les cases correspondantes.
   - Une case n'est prise en compte que si l'abonné a le droit de supervision correspondant (cf. exigence 2) ; un abonnement individuel sans ce droit est refusé avec un message explicite. Les droits sont contrôlés à nouveau à chaque notification, pour qu'un retrait de droit prenne effet immédiatement.

6. **Canaux de notification (Lot 1)**
   - Le membre choisit lui-même ses canaux sur sa page Mes alarmes. Aucun canal n'est exigé, même avec des abonnements : sans canal, ses alarmes restent affichées dans GVV (page Mes alarmes et carte du tableau de bord).
   - **Email** : message contenant la liste des alarmes concernées (quoi, qui, échéance, action attendue), avec un lien vers la page GVV de chacune. Aucun document joint.
   - **Message du jour** : message adressé individuellement à l'abonné, visible à sa prochaine connexion dans GVV, avec les mêmes informations et le même lien.
   - **Rappel par SMS (optionnel)** : envoyé le jour de la synthèse — chaque lundi pour une fréquence immédiate ou hebdomadaire, le premier lundi du mois pour une fréquence mensuelle — si le membre a au moins une alarme active ou à venir. Il est envoyé au numéro de téléphone mobile de la fiche membre, uniquement si le membre a lui-même coché l'option sur sa page Mes alarmes. Il ne contient qu'un message court et le lien vers la page Mes alarmes (ex. « GVV : 3 alarmes vous concernent : <lien> »), jamais le détail des alarmes. Un membre dont la fiche n'a pas de numéro de mobile ne peut pas cocher l'option, et la page le lui explique.
   - Les notifications doivent être compréhensibles, actionnables et contextualisées (quoi, qui, quand, action attendue).

7. **Synthèses (Lot 1)**
   - Chaque membre choisit une fréquence de réception, commune à tous ses abonnements : **immédiate** (une notification à chaque déclenchement), **hebdomadaire** ou **mensuelle**.
   - La synthèse hebdomadaire est envoyée chaque lundi ; la synthèse mensuelle, le premier lundi du mois.
   - En mode hebdomadaire ou mensuel, le membre reçoit une seule synthèse par période, sur chacun des canaux qu'il a choisis.
   - La synthèse contient toutes les alarmes du membre (propres et abonnements) qui sont **encore actives** (ex. document périmé, vol sans licence non régularisé) ou **qui vont s'activer prochainement** (dans le délai d'anticipation de l'alarme), et pas seulement les nouveautés de la période.
   - Une même alarme couverte par plusieurs abonnements d'un même membre n'apparaît qu'une fois.
   - Une synthèse vide n'est pas envoyée.
   - Le rappel par SMS est envoyé le même jour que la synthèse (cf. exigence 6).

8. **Rôle élève et visite médicale des élèves (Lot 1)**
   - GVV dispose d'un rôle « élève », attribuable dans chaque section comme les autres rôles. Un membre en école est un membre qui détient ce rôle dans au moins une section.
   - Le rôle est attribué automatiquement à l'ouverture d'une formation, dans la section du programme de formation, ou dans toutes les sections si le programme n'est rattaché à aucune section.
   - Il est retiré automatiquement à la clôture ou à l'abandon de la formation, dans chaque section concernée, sauf si le membre y a une autre formation ouverte ou suspendue (une formation dont le programme n'a pas de section compte pour toutes les sections).
   - Un administrateur peut aussi l'attribuer ou le retirer à la main, dans n'importe quelle section, y compris quand il a été attribué automatiquement ; un retrait manuel n'est pas annulé tant qu'aucune nouvelle formation n'est ouverte.
   - Le rôle « élève » peut servir de catégorie de membres pour les abonnements (cf. exigence 5) et de critère pour les documents obligatoires.
   - La visite médicale est obligatoire pour tout membre ayant le rôle « élève », dans la ou les sections où il le détient.
   - Un élève sans visite médicale valide apparaît comme ayant un document obligatoire manquant.
   - L'approche puis le dépassement de la date d'expiration de la visite médicale d'un élève déclenchent les alarmes correspondantes : l'élève est informé pour son propre certificat (alarme propre), et les abonnés à l'alarme des visites médicales des élèves dans sa section (ex. instructeurs, responsables pédagogiques) le sont aussi.

8 bis. **Rôle emport passager (Lot 3)**
   - GVV dispose d'un rôle « emport passager », attribuable dans chaque section comme les autres rôles, par un administrateur. Il désigne les pilotes autorisés à emporter des passagers dans l'activité de la section.
   - À sa création, le rôle est attribué en section planeur aux membres ayant l'événement « Emport passager » enregistré.
   - L'alarme d'emport passager ne concerne que les pilotes ayant ce rôle, dans les sections où ils le détiennent.

9. **Déclenchement et fenêtres**
   - Une alarme à date fixe passe à l'état « à venir » au début de son délai d'anticipation, puis « active » à l'échéance.
   - Une alarme calculée est évaluée sur les données courantes ; elle est active tant que la condition est vraie (ex. un vol sans licence non régularisé).
   - Les rappels tant que l'alarme n'est pas résolue suivent la fréquence de synthèse de chaque abonné.

10. **Résolution**
   - Une alarme est résolue automatiquement lorsque sa condition disparaît (nouveau document valide, licence enregistrée, condition d'expérience atteinte), ou manuellement si justifié.
   - Une alarme résolue n'apparaît plus dans la page Mes alarmes ni dans les synthèses suivantes.

11. **Portée par section**
    - Une alarme peut être définie avec une portée globale (toutes sections) ou restreinte à une section spécifique.
    - Les alarmes globales s'appliquent à tous les pilotes et toutes les machines, quelle que soit leur section d'appartenance.
    - Les alarmes spécifiques à une section ne s'appliquent qu'aux entités de cette section.
    - Cette portée est cohérente avec le modèle existant de `document_types.section_id` (null = toutes sections).

12. **Vue consolidée des alarmes**
    - Distincte de la page Mes alarmes (exigence 4), une vue d'administration doit permettre de lister, filtrer et trier toutes les alarmes par statut, sévérité, type et échéance.
    - Cette vue doit agréger les alarmes documentaires et les alarmes calculées.
    - La vue doit être filtrable par section ; un responsable de section ne voit que les alarmes de sa section.

13. **Désactivation d'une alarme (Lot 4)**
    - Une désactivation porte sur une alarme précise (ex. le compte débiteur d'un pilote dans une section), pas sur une alarme du catalogue ni sur toute une section.
    - **Pour soi** : un membre peut désactiver, pour lui seul, une alarme qui lui est affichée par abonnement (CA, bureau, instructeur, trésorier…). Elle disparaît de sa page, de sa carte du tableau de bord, de ses synthèses et de ses SMS ; les autres abonnés et le pilote concerné la voient toujours. Un membre ne peut pas désactiver ses propres alarmes.
    - **Pour tout le monde** : un administrateur club peut désactiver une alarme pour tous, y compris pour le pilote concerné. Elle disparaît alors de toutes les pages, synthèses et SMS, et de la liste des alarmes que personne ne reçoit. Un motif est obligatoire.
    - Une désactivation est **définitive** ou **jusqu'à une date** choisie. Le motif est facultatif pour soi.
    - **Réactivation automatique** : l'alarme réapparaît à la date choisie, ou dès que sa situation change (passage de « à venir » à « active », nouvelle échéance). Une alarme qui a disparu parce que sa condition est résolue n'est plus désactivée si elle se présente de nouveau.
    - **Réactivation manuelle** : un membre peut réactiver les alarmes qu'il a désactivées pour lui. Un administrateur club peut réactiver toute alarme désactivée, pour tout le monde ou pour un membre.
    - Rien n'est masqué en silence : la page Mes alarmes indique le nombre d'alarmes désactivées et permet de les afficher, avec l'auteur, le motif et la date de fin. L'administration liste toutes les désactivations en cours.

14. **Anti-spam (Lot 1)**
    - Le système évite les notifications redondantes : une même alarme n'est pas notifiée deux fois au même abonné dans la même période.
    - Un abonné reçoit au plus un rappel par SMS par semaine.

15. **Traçabilité**
    - Chaque notification envoyée est historisée : abonné, alarme, canal, date.
    - Chaque alarme à date fixe conserve son historique minimal : changements d'état, notifications.
    - Chaque désactivation est historisée : alarme, portée (pour soi ou pour tout le monde), auteur, motif, date de fin, date et auteur de la réactivation (ou réactivation automatique).
    - Les alarmes calculées ne sont pas historisées en tant qu'état — elles reflètent les données courantes —, mais leurs notifications le sont.

16. **Aucune alarme perdue (Lot 1)**
    - Aucun abonnement n'est créé automatiquement, y compris pour les administrateurs (cf. exigence 2).
    - La vue d'administration signale les alarmes actives que personne ne reçoit (ni le membre concerné avec un canal choisi, ni un abonné), pour qu'un administrateur puisse y abonner les bonnes personnes.

## Exigences non fonctionnelles
- Fiabilité : aucune alarme ne doit être perdue silencieusement.
- Performance : consultation et filtrage fluides sur un volume club standard.
- Sécurité : contrôle d'accès strict selon rôles et périmètre ; un abonné ne reçoit jamais d'information qu'il n'a pas le droit de consulter.
- Confidentialité : les SMS ne contiennent aucune information personnelle ou médicale, seulement un message court et un lien vers GVV.
- Lisibilité : état et urgence immédiatement compréhensibles.
- Auditabilité : historique consultable pour diagnostic et conformité.
- Coût : l'envoi de SMS est payant ; il doit pouvoir être désactivé globalement par l'administrateur.

## Étude MOTD (Message du jour)
### Constat
Le MOTD de configuration historique (champ `mod` dans la configuration club) est embryonnaire et obsolète.
Le référentiel produit à considérer pour MOTD est le PRD dédié : `doc/prds/messages_du_jour_prd.md`, qui prévoit des messages à destination d'un utilisateur unique.

### Décision produit
Le message du jour est un **canal de notification** des alarmes, au même titre que l'email : une alarme peut être notifiée à un abonné par un message du jour qui lui est adressé individuellement.

Le mécanisme d'alarmes ne s'appuie pas sur le message du jour pour stocker ni orchestrer les alarmes.

### Justification
- Une alarme est ciblée, datée, traçable et pilotée par état ; le message du jour n'en est que le support d'affichage.
- Le message du jour atteint les membres qui consultent GVV mais lisent peu leurs emails.

## Contraintes & dépendances
- Réutiliser au maximum les mécanismes existants : notifications email, messages du jour individuels, envoi de SMS (adaptateur utilisé par les rappels de réservation), rôles et autorisations.
- Assurer la coexistence avec les modules déjà en production (archivage documentaire, alarmes d'expérience, contrôle des vols sans cotisation).
- Les notifications documentaires du PRD d'archivage documentaire (§10) sont réalisées par ce mécanisme ; ce PRD-là n'en fixe que les règles propres aux documents.
- Prévoir une migration progressive des alarmes existantes vers le mécanisme générique.
- Les alarmes calculées réutilisent la logique existante (`alarmes.php`, contrôle des vols sans cotisation) sans la dupliquer.

## Mesures de succès
- Adoption : part des membres actifs ayant choisi au moins un canal.
- Couverture : aucune alarme active sans abonné.
- Conformité : baisse du nombre d'élèves volant sans visite médicale valide, puis (Lot 2) de vols sans licence.
- Qualité perçue : diminution des oublis d'échéance remontés par les responsables.

## Questions ouvertes
- ~~Lot 2 : quelles licences comptent pour l'alarme « vol sans licence » et à partir de quelle date un vol est-il considéré sans licence ?~~ → **Résolu** : la licence exigée par la section du vol (paramètre de la section), pour l'année du vol ; vols de l'année civile en cours.
- Quel niveau de granularité de sévérité est attendu (info, warning, critique) ?
- Quelles échéances de maintenance et quelles autres alarmes d'expérience sont prioritaires après le Lot 3 ?
