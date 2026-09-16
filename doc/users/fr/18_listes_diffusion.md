# 18. Listes de Diffusion Email

## Vue d'ensemble

GVV permet de constituer des **listes de diffusion email** réutilisables : ensembles d'adresses email destinés à envoyer des communications au club (newsletter, convocations, information ciblée à une section ou un rôle). Une liste peut mélanger plusieurs sources — membres correspondant à des critères, membres ajoutés à la main, adresses externes, fichiers importés, autres listes incluses en sous-liste — et propose ensuite des outils d'export (copier-coller, fichier, client email).

Une liste de diffusion peut aussi être utilisée comme **destinataire d'un message du jour** (voir [15. Gestion des Messages](15_gestion_messages.md)).

**Rôle requis** : Secrétaire ou membre du CA (`secretaire` ou `ca`)
**Menu** : `Administration > Listes de diffusion`
**URL** : `/email_lists`

---

## 1. Créer une liste

Cliquez sur **Nouvelle liste** et renseignez :

| Champ | Description |
|-------|--------------|
| **Nom de la liste** | Obligatoire, doit être unique |
| **Description** | Facultative |
| **Filtrer membres** | Membres actifs seulement / inactifs seulement / tous |
| **Cotisation à jour requise** | Si coché, les membres sélectionnés par critère doivent être à jour de cotisation pour être inclus |
| **Liste publique** | Si coché, la liste est visible par tous les utilisateurs habilités ; si décoché, elle n'est visible que par vous et les administrateurs |

Après l'enregistrement, vous êtes redirigé vers la page de modification où les adresses peuvent être ajoutées.

---

## 2. Construire le contenu de la liste

La page de modification propose quatre onglets, combinables librement — le total de destinataires (dédoublonné) s'affiche en temps réel dans le panneau **Liste en construction**.

### 2.1 Par critères

Sélectionnez un ou plusieurs **rôles**, globalement ou pour une **section** donnée (ex. tous les instructeurs planeur de la section Planeur). Tous les membres correspondants sont inclus automatiquement, et la liste se met à jour si de nouveaux membres obtiennent ce rôle par la suite.

### 2.2 Sélection manuelle

Ajoutez des membres individuellement, indépendamment de leur rôle.

### 2.3 Import de fichiers

Importez un fichier **.txt** ou **.csv** contenant des adresses email (une par ligne, ou en colonnes pour un CSV).

> **Informations conservées à l'import :** si une ligne contient autre chose que la seule adresse email — un nom, un numéro de téléphone, toute autre indication — GVV conserve ce texte et l'affiche à côté de l'adresse dans la liste des destinataires. Pour un CSV, toutes les colonnes autres que celle de l'email sont conservées de la même façon (pas seulement les colonnes prénom/nom).

Un fichier déjà importé peut être supprimé (bouton **Supprimer**) : les adresses qu'il a apportées sont alors retirées de la liste.

### 2.4 Sous-listes

Incluez le contenu d'une autre liste de diffusion existante comme **sous-liste**. Une liste publique ne peut pas contenir de sous-liste privée.

---

## 3. Adresses externes

Dans l'onglet **Sélection manuelle**, un champ libre permet de coller directement une ou plusieurs adresses, une par ligne. Sur une même ligne, tout ce qui n'est pas reconnu comme une adresse email (nom, numéro de téléphone, etc.) est conservé et associé à l'adresse — y compris quand plusieurs adresses partagent la même ligne (ex. `jean@ex.fr, paul@ex.fr Jean et Paul Dupont`).

---

## 4. Ordre d'affichage

Les adresses email d'une liste sont triées par ordre alphabétique : sur le nom et le prénom quand ils sont connus, sinon sur l'adresse email elle-même.

---

## 5. Consulter et exporter une liste

Depuis la liste des listes, **Utiliser cette liste** ouvre la vue de consultation, qui affiche tous les destinataires (adresse, nom si connu, source) et propose :

- **Ouvrir client email** — génère un lien `mailto:` (champ À/CC/CCI configurable, sujet et répondre-à facultatifs)
- **Copier dans le presse-papier** — avec choix du séparateur (virgule ou point-virgule)
- **Découpage en lots** — pour respecter une limite de destinataires par envoi (certains clients email plafonnent le nombre de destinataires)
- **Exporter TXT** / **Exporter Markdown** — fichiers téléchargeables

---

## 6. Visibilité et suppression

- Une liste **privée** n'est visible que par son créateur et les administrateurs.
- Une liste **publique** est visible par tous les utilisateurs habilités à accéder au module ; elle ne peut pas contenir de sous-liste privée (GVV le signale si c'est le cas).
- La suppression d'une liste (bouton **Supprimer**, avec confirmation) retire aussi ses adresses externes et ses critères associés ; les membres eux-mêmes ne sont bien sûr pas affectés.
