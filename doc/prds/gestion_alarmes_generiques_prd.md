# PRD — Gestion Générique des Alarmes

Date : 9 mai 2026 — mis à jour le 30 mai 2026 (portée par section) et le 18 octobre 2026 (Lot 1 du MVP : catalogue d'alarmes, pages de configuration et de consultation, canaux, synthèses ; rôle élève et visite médicale des élèves)

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
- Permettre à chaque membre de choisir, dans une liste, les alarmes qu'il souhaite recevoir, ses canaux et sa fréquence, et de consulter ses alarmes actives.
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
| **Lot 1** | Catalogue d'alarmes, page de configuration des alarmes (abonnements par cases à cocher, canaux, rappel par SMS, fréquence), page de consultation des alarmes actives, configuration pour un autre membre et abonnement d'une catégorie par un administrateur, canaux email et message du jour, rappel court par SMS le lundi, synthèses hebdomadaires ou mensuelles. Rôle élève par section. Alarmes couvertes : documents archivés — expiration et documents obligatoires manquants (dont visite médicale, obligatoire pour les élèves). |
| **Lot 2** | Alarmes de conformité calculées : vols sans licence, vols sans cotisation. |
| Lots suivants | Alarmes de maintenance, alarmes d'expérience récente et d'inactivité, acquittement et suspension, vue consolidée avancée. |

## Portée
### Inclus
- Catalogue des alarmes auxquelles un membre peut s'abonner (Lot 1).
- Page de configuration des alarmes et page de consultation des alarmes actives, pour chaque membre (Lot 1).
- Abonnements individuels, configuration pour un autre membre par un administrateur, abonnements par catégorie de membres (Lot 1).
- Choix des canaux par le membre : email, message du jour ; rappel court optionnel par SMS le lundi (Lot 1).
- Synthèses périodiques des alarmes actives et à venir (Lot 1).
- Rôle « élève » attribuable dans chaque section (Lot 1).
- Alarmes liées aux documents archivés : expiration, documents obligatoires manquants (Lot 1).
- Alarmes de conformité calculées : vols sans licence, vols sans cotisation (Lot 2).
- Obligation de la visite médicale pour les élèves (Lot 1).
- Alarmes liées aux échéances de maintenance (machine, équipement ou autre entité maintenable).
- Alarmes calculées à partir des données de vol (expérience récente, inactivité, conditions d'emport).
- Portée par section : une alarme peut être commune à toutes les sections ou restreinte à une section spécifique.
- Suivi de l'état des alarmes : à venir, active, acquittée, suspendue, résolue.

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
1. Un membre ouvre la page de **configuration de ses alarmes** : il voit la liste des alarmes auxquelles il peut s'abonner, coche celles qu'il veut recevoir, choisit ses canaux (email, message du jour), active ou non le rappel par SMS et choisit la fréquence de sa synthèse (immédiate, hebdomadaire ou mensuelle).
2. Un administrateur ouvre la même page de configuration pour un autre membre et y définit ses alarmes, ou abonne une catégorie de membres (ex. tous les instructeurs) à une alarme.
3. Quand une alarme se déclenche (ex. visite médicale d'un élève qui expire dans 30 jours), chaque abonné concerné en est informé selon ses choix.
4. Le lundi de la semaine ou le premier lundi du mois selon sa fréquence, l'abonné reçoit une synthèse de toutes ses alarmes encore actives ou qui vont s'activer prochainement ; s'il a activé le rappel par SMS, il reçoit aussi un SMS court avec le lien vers ses alarmes dans GVV.
5. À tout moment, le membre abonné ouvre la page **Mes alarmes** qui liste les alarmes actives et à venir auxquelles il est abonné, avec un lien vers l'objet concerné.
6. Après traitement (ex. renouvellement du document), l'alarme est résolue et disparaît de la page Mes alarmes et des synthèses suivantes.
7. Un membre pour qui personne n'a rien configuré ne reçoit aucune alarme et ne voit pas la page Mes alarmes.

## Exigences fonctionnelles

1. **Catalogue des alarmes (Lot 1)**
   - GVV fournit un catalogue d'alarmes. Chaque alarme du catalogue a un nom, une description compréhensible par un membre, une portée (toutes sections ou une section), et un délai d'anticipation (nombre de jours avant l'échéance où elle est considérée « à venir »).
   - Le catalogue du Lot 1 porte sur les documents archivés :
     - pour chaque type de document à expiration (ex. visite médicale, assurance, brevet), une alarme personnelle et une alarme de supervision ;
     - les documents obligatoires manquants, en alarme personnelle et en alarme de supervision.
   - Le Lot 2 y ajoute les alarmes de conformité calculées : pilotes ayant volé sans licence valide, pilotes ayant volé sans cotisation à jour (réutilise le contrôle existant).
   - Un administrateur peut activer ou désactiver une alarme du catalogue et régler son délai d'anticipation ; pour une alarme documentaire, ce délai vaut par défaut le délai d'alerte du type de document.
   - Les types d'alarme sont extensibles : documentaire, maintenance, conformité calculée, expérience récente calculée.

2. **Alarmes personnelles, alarmes de supervision et absence de configuration (Lot 1)**
   - Une alarme **personnelle** concerne le membre lui-même (ex. sa visite médicale expire). Le membre la reçoit s'il s'y est abonné ou si un administrateur l'y a abonné, individuellement ou via une catégorie.
   - Une alarme de **supervision** concerne d'autres membres ou des machines (ex. les visites médicales des élèves, les vols sans licence). Elle n'est proposée qu'aux membres autorisés à consulter les informations correspondantes.
   - Aucun abonnement n'existe par défaut : un membre pour qui ni lui ni un administrateur n'a rien configuré (aucun abonnement individuel, aucune catégorie abonnée dont il fait partie) ne reçoit aucune alarme, sur aucun canal.

3. **Page de configuration des alarmes (Lot 1)**
   - Chaque membre dispose dans GVV d'une page de configuration de ses alarmes, accessible depuis son espace personnel, qu'il ait déjà des abonnements ou non : c'est par elle qu'il s'abonne.
   - La page présente la liste des alarmes du catalogue auxquelles le membre peut s'abonner, avec une case à cocher par alarme.
   - Le membre y choisit ses canaux : email, message du jour, ou les deux. Ce choix est commun à toutes ses alarmes. Aucun canal n'est présélectionné : au premier abonnement, la page demande d'en choisir au moins un.
   - Le membre y active ou désactive le rappel par SMS (cf. exigence 6). Ce choix se fait uniquement sur cette page ; cocher l'option vaut consentement à recevoir ces SMS.
   - Le membre y choisit la fréquence de sa synthèse (cf. exigence 7).
   - La page indique aussi les abonnements créés pour le membre par un administrateur, individuellement ou via une catégorie, avec leur origine ; ceux rendus obligatoires ne peuvent pas être décochés.
   - Toute modification est confirmée à l'écran.

4. **Page de consultation des alarmes (Lot 1)**
   - Chaque membre abonné à au moins une alarme dispose dans GVV d'une page « Mes alarmes » qui liste les alarmes **actives** et **à venir** auxquelles il est abonné, qu'elles soient personnelles ou de supervision.
   - Un membre sans aucun abonnement n'a pas accès à cette page : elle n'apparaît ni dans ses menus ni dans son tableau de bord. Elle y apparaît dès qu'un abonnement existe pour lui, qu'il l'ait créé ou qu'un administrateur l'ait créé.
   - Pour chaque alarme, la page indique ce qui est concerné (membre, document, machine), l'échéance ou la date de déclenchement, l'état (à venir, active) et l'action attendue, avec un lien vers la page GVV où la traiter.
   - La page est filtrable par alarme du catalogue et par section.
   - Une alarme résolue n'y figure plus.

5. **Configuration par un administrateur (Lot 1)**
   - Un administrateur club utilise la page de configuration des alarmes exactement comme un membre, pour lui-même.
   - La seule différence : la page lui présente en plus un sélecteur de membre, positionné par défaut sur son propre profil. En choisissant un autre membre, il voit et modifie la configuration de ce membre — abonnements, canaux, rappel par SMS, fréquence — avec les mêmes règles que pour lui-même. Le membre voit ensuite ces abonnements sur sa page, avec le nom de l'administrateur qui les a créés.
   - Il peut abonner une **catégorie de membres** à une alarme. Les catégories reposent sur les rôles existants (ex. instructeurs, bureau, CA, mécaniciens), éventuellement restreints à une section. Un membre qui obtient le rôle est abonné, un membre qui le perd ne l'est plus, sans autre intervention.
   - Un administrateur peut rendre un abonnement, individuel ou par catégorie, non désabonnable ; sinon, le membre peut s'en retirer.
   - Un abonnement à une alarme de supervision n'est accepté que si l'abonné est autorisé à consulter les informations correspondantes ; sinon il est refusé avec un message explicite. Le contrôle est refait à chaque notification, pour qu'un retrait de droit prenne effet immédiatement.

6. **Canaux de notification (Lot 1)**
   - Le membre choisit lui-même ses canaux sur sa page de configuration ; au moins un des canaux email et message du jour doit rester actif tant qu'il a un abonnement.
   - **Email** : message contenant la liste des alarmes concernées (quoi, qui, échéance, action attendue), avec un lien vers la page GVV de chacune. Aucun document joint.
   - **Message du jour** : message adressé individuellement à l'abonné, visible à sa prochaine connexion dans GVV, avec les mêmes informations et le même lien.
   - **Rappel par SMS (optionnel)** : envoyé le jour de la synthèse — chaque lundi pour une fréquence immédiate ou hebdomadaire, le premier lundi du mois pour une fréquence mensuelle — si le membre a au moins une alarme active ou à venir. Il est envoyé au numéro de téléphone mobile de la fiche membre, uniquement si le membre a lui-même coché l'option sur sa page de configuration. Il ne contient qu'un message court et le lien vers la page Mes alarmes (ex. « GVV : 3 alarmes vous concernent : <lien> »), jamais le détail des alarmes. Un membre dont la fiche n'a pas de numéro de mobile ne peut pas cocher l'option, et la page le lui explique.
   - Les notifications doivent être compréhensibles, actionnables et contextualisées (quoi, qui, quand, action attendue).

7. **Synthèses (Lot 1)**
   - Chaque membre choisit une fréquence de réception, commune à tous ses abonnements : **immédiate** (une notification à chaque déclenchement), **hebdomadaire** ou **mensuelle**.
   - La synthèse hebdomadaire est envoyée chaque lundi ; la synthèse mensuelle, le premier lundi du mois.
   - En mode hebdomadaire ou mensuel, le membre reçoit une seule synthèse par période, sur chacun des canaux qu'il a choisis.
   - La synthèse contient toutes les alarmes auxquelles le membre est abonné qui sont **encore actives** (ex. document périmé, vol sans licence non régularisé) ou **qui vont s'activer prochainement** (dans le délai d'anticipation de l'alarme), et pas seulement les nouveautés de la période.
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
   - L'approche puis le dépassement de la date d'expiration de la visite médicale d'un élève déclenchent l'alarme correspondante : l'élève est informé (alarme personnelle) et les abonnés à l'alarme de supervision des visites médicales des élèves le sont aussi.

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

13. **Acquittement et suspension** (lots suivants)
    - Selon droits, un utilisateur doit pouvoir acquitter une alarme.
    - Selon droits, un administrateur doit pouvoir suspendre ou réactiver une alarme.

14. **Anti-spam (Lot 1)**
    - Le système évite les notifications redondantes : une même alarme n'est pas notifiée deux fois au même abonné dans la même période.
    - Un abonné reçoit au plus un rappel par SMS par semaine.

15. **Traçabilité**
    - Chaque notification envoyée est historisée : abonné, alarme, canal, date.
    - Chaque alarme à date fixe conserve son historique minimal : changements d'état, notifications, acquittements.
    - Les alarmes calculées ne sont pas historisées en tant qu'état — elles reflètent les données courantes —, mais leurs notifications le sont.

16. **Aucune alarme perdue (Lot 1)**
    - Aucun abonnement n'est créé automatiquement, y compris pour les administrateurs (cf. exigence 2).
    - La vue d'administration signale les alarmes actives auxquelles personne n'est abonné, pour qu'un administrateur puisse y abonner les bonnes personnes.

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
- Adoption : part des membres actifs ayant au moins un abonnement ou une préférence de canal enregistrée.
- Couverture : aucune alarme active de supervision sans abonné.
- Conformité : baisse du nombre d'élèves volant sans visite médicale valide, puis (Lot 2) de vols sans licence.
- Qualité perçue : diminution des oublis d'échéance remontés par les responsables.

## Questions ouvertes
- Lot 2 : quelles licences comptent pour l'alarme « vol sans licence » (licence fédérale, licence de section) et à partir de quelle date un vol est-il considéré sans licence ?
- Quel niveau de granularité de sévérité est attendu (info, warning, critique) ?
- Quelles échéances de maintenance et quelles alarmes d'expérience sont prioritaires après le Lot 2 ?
