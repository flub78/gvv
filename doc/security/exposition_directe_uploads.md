# Faille critique : accès direct aux fichiers de `uploads/` et `backups/`

**Gravité : critique** — divulgation de données personnelles (dont données de santé et
pièces d'identité) et, selon la configuration, de sauvegardes complètes de la base.

Ce document est destiné à **tous les administrateurs ayant déployé GVV**. Il décrit la
vulnérabilité, comment vérifier si votre installation est concernée, et les correctifs à
appliquer. Il ne contient aucune information propre à un club particulier.

---

## 1. Résumé

Le répertoire `uploads/` se trouve à l'intérieur de la racine web (`DocumentRoot`). Le
serveur web (Apache ou nginx) sert **directement** tout fichier existant sous cette
racine, sans passer par l'application. Le routeur de CodeIgniter (`index.php`) ne prend en
charge que les URL qui **ne correspondent pas** à un fichier réel.

Conséquence : tous les fichiers déposés dans `uploads/` sont téléchargeables par leur URL,
**sans authentification**, quels que soient les contrôles d'accès implémentés dans les
contrôleurs PHP.

La fonctionnalité de **gestion documentaire** (`$config['gestion_documentaire']`,
contrôleur `archived_documents`) est la plus exposée : elle stocke sous
`uploads/documents/` des certificats médicaux, des pièces d'identité, des licences et des
attestations de formation, en s'appuyant uniquement sur les vérifications d'accès des
méthodes `preview()` / `download()` — vérifications entièrement contournées par un accès
direct au fichier.

Si le **listing de répertoire** est activé sur le serveur, n'importe qui peut en plus
parcourir `uploads/documents/pilots/<login>/<type>/` et énumérer l'ensemble des documents
de tous les membres.

La fonctionnalité de **sauvegarde/restauration** est également concernée. Elle utilise
deux répertoires, tous deux dans la racine web :

- `backups/` — bibliothèque **persistante** de sauvegardes (`admin::backup()`,
  `backup2()`, `backup_media()` y écrivent ; `admin::restore()` la liste pour choisir
  quoi restaurer). Les archives `.zip` / `.tar.gz` et les dumps `.sql` en clair y restent
  indéfiniment. Noms prévisibles : `<nom_club>_..._<date>_migration_<n>.zip`.
- `uploads/restore/` — zone de transit lors d'une restauration ; peut contenir un dump
  `.sql` **déchiffré** après restauration d'une sauvegarde chiffrée, et n'est pas
  nettoyée de façon fiable.

Le contenu de ces répertoires (sauvegarde complète de la base : données de tous les
membres, empreintes de mots de passe, comptabilité) est téléchargeable sans
authentification.

---

## 2. Composants concernés

- Toute installation où `uploads/` et `backups/` sont sous la racine web (cas par défaut).
- Impact maximal si `$config['gestion_documentaire'] = true` (documents personnels
  sensibles) et/ou si des archives de sauvegarde sont présentes dans `backups/` ou
  `uploads/restore/`.
- Indépendant de la version de PHP et du serveur web ; concerne aussi bien les
  déploiements Apache + mod_php que les déploiements nginx en frontal (reverse proxy)
  servant les fichiers statiques directement.

---

## 3. Détail technique

1. `uploads/` et `backups/` sont physiquement dans le `DocumentRoot` (la lib de
   sauvegarde écrit dans `getcwd() . "/backups/"`, soit `<racine_web>/backups/`).
2. La règle de réécriture fournie (`.htaccess` racine) ne redirige vers `index.php` que
   les requêtes dont la cible **n'est ni un fichier ni un répertoire existant**
   (`!-f !-d`). Un fichier réel est donc servi tel quel par le serveur web.
3. Les noms de fichiers stockés suivent le schéma
   `<timestamp>_<rand(1000,9999)>_<nom_original>` : entropie faible (le nombre aléatoire
   ne couvre que 9000 valeurs), et le nom original est souvent prévisible ou affiché dans
   l'interface. Un listing de répertoire actif supprime même cette barrière.
4. Les vignettes PDF sont générées à côté du fichier sous le nom `thumb_<nom>.jpg` et
   suivent la même logique d'exposition.

### Pourquoi les contrôleurs PHP ne protègent pas

`archived_documents::preview()` et `::download()` vérifient bien les droits (propriétaire,
rôle, caractère privé du type de document) **avant** de lire le fichier depuis le disque.
Mais ces méthodes ne sont appelées que si l'URL passe par `index.php`. L'URL directe
`/uploads/documents/...` court-circuite entièrement l'application.

---

## 4. Impact

- Téléchargement non authentifié de **données personnelles** : certificats médicaux
  (donnée de santé — catégorie particulière au sens du RGPD), pièces d'identité,
  licences, attestations.
- Si listing actif : **énumération exhaustive** des documents de tous les membres.
- Si des sauvegardes sont présentes dans `backups/` ou `uploads/restore/` :
  **compromission totale de la base** (PII, empreintes de mots de passe, données
  financières). Sur un déploiement nginx, `.zip` / `.tar.gz` / `.gz` sont servis
  directement par nginx ; les dumps `.sql` en clair le sont via le backend Apache.
- En cas d'accès avéré par un tiers non autorisé à des données de santé ou des pièces
  d'identité : obligation de **notification à la CNIL sous 72 h** et, potentiellement,
  information des personnes concernées.

---

## 5. Suis-je vulnérable ?

Depuis une machine **non connectée** à GVV (ou en navigation privée / avec `curl`) :

```bash
HOST="https://VOTRE-HOTE-GVV"

# a) listing de répertoire
curl -sI "$HOST/uploads/documents/" | head -1

# b) accès direct à un document réel
#    Récupérez un chemin dans la base :
#      SELECT file_path FROM archived_documents WHERE is_current_version = 1 LIMIT 1;
#    puis, en retirant le "./" initial :
curl -sI "$HOST/uploads/documents/pilots/.../....pdf" | head -1

# c) archives de sauvegarde — répertoire de transit
curl -sI "$HOST/uploads/restore/" | head -1

# d) bibliothèque de sauvegardes persistante (le point le plus grave)
curl -sI "$HOST/backups/" | head -1
#    puis un fichier réel, nom visible dans admin/restore ou dans le répertoire :
curl -sI "$HOST/backups/VOTRE_CLUB_..._migration_NN.zip" | head -1
```

**Vous êtes vulnérable si l'un de ces appels renvoie `200` ou un listing HTML.**
Le résultat attendu après correctif est `403` (ou `404`).

Un `403` sur le **répertoire** (a, c, d) ne suffit pas : c'est la réponse sur un
**fichier** (b, d) qui fait foi. Beaucoup de configurations désactivent le listing tout en
servant les fichiers.

---

## 6. Correctifs

### 6.1 Immédiat — sortir les sauvegardes de la racine web

Déplacez toute archive de sauvegarde hors du `DocumentRoot`.

```bash
mkdir -p /chemin/hors/racine/gvv-backups
mv <racine_web>/uploads/restore/*.zip <racine_web>/uploads/restore/*.sql \
   <racine_web>/backups/*            /chemin/hors/racine/gvv-backups/ 2>/dev/null
```

Note : GVV écrit ses sauvegardes dans `<racine_web>/backups/` (chemin non configurable
sans modification de code). Videz ce répertoire régulièrement et/ou appliquez le blocage
ci-dessous.

### 6.2 Serveur nginx (y compris nginx en frontal servant les statiques)

Ajoutez au bloc `server { … }` du domaine, **avant** toute règle servant les fichiers
statiques :

```nginx
location ^~ /uploads/documents/ { return 403; }
location ^~ /uploads/restore/   { return 403; }
location ^~ /backups/           { return 403; }
```

Le préfixe `^~` est indispensable : il fait primer ces règles sur la `location` en
expression régulière qui sert les extensions statiques (`\.(pdf|jpg|png|…)$`), quelle que
soit sa position dans la configuration.

Si vous utilisez un panneau de gestion qui régénère la configuration nginx depuis un
gabarit (HestiaCP, etc.), placez ces directives dans le mécanisme d'**include
personnalisé** du domaine afin qu'elles survivent aux régénérations, et non directement
dans le fichier généré.

Rechargez : `nginx -t && systemctl reload nginx`

### 6.3 Serveur Apache

Créez un fichier `.htaccess` contenant :

```apache
Require all denied
```

dans **chacun** des répertoires à protéger : `uploads/documents/`, `uploads/restore/`,
`backups/` (Apache 2.2 : `Deny from all`).

Les accès légitimes aux fichiers (via `preview()` / `download()`) passent par PHP qui lit
le fichier **sur le disque** : ils ne sont pas affectés par ces directives.

### 6.4 Désactiver le listing de répertoire

- nginx : `autoindex off;` (valeur par défaut — vérifiez qu'aucun gabarit ne l'active).
- Apache : `Options -Indexes` (dans la configuration du vhost ou un `.htaccess` à la
  racine de `uploads/`).

### 6.5 Pérenniser le correctif dans le dépôt

Pour qu'un redéploiement ne réintroduise pas la faille, versionnez dans le dépôt :

- `uploads/.htaccess` → `Options -Indexes`
- `uploads/documents/.htaccess` → `Require all denied`
- `uploads/restore/.htaccess` → `Require all denied`
- `backups/.htaccess` → `Require all denied`

Ces fichiers étant doublement ignorés par `.gitignore` (`uploads/*`, `backups/*` et
`.htaccess`), ajoutez les règles de négation correspondantes.

(sans effet sur les déploiements nginx, mais inoffensif ; le correctif nginx de la
section 6.2 reste à appliquer côté serveur).

---

## 7. Régression connue après correctif

Les listes de la gestion documentaire (`archived_documents/my_documents`,
`archived_documents/page`) affichent les **vignettes** via des balises `<img>` pointant
directement sur le fichier (`uploads/documents/.../thumb_*.jpg`). Après blocage, ces
vignettes ne s'affichent plus (icône d'image cassée).

- Les boutons **Télécharger** et **Prévisualiser** continuent de fonctionner (ils passent
  par PHP).
- Correctif propre à prévoir côté code : servir les vignettes et aperçus images via une
  action de contrôleur soumise aux mêmes contrôles d'accès, au lieu d'URL directes.

---

## 8. Durcissement complémentaire

- **Autres sous-répertoires de `uploads/`** susceptibles de contenir des données
  personnelles selon les fonctionnalités activées : `email_lists/`, `forms_submissions/`,
  `reponses/`, `configuration/`. À protéger de la même manière après vérification qu'ils
  ne servent pas de contenu public.
- **Principe cible** : rien sous `uploads/` ne devrait être accessible par URL directe,
  hormis une liste explicite d'actifs réellement publics. À terme, faire transiter tous
  les téléchargements par l'application.
- **Type de fichiers acceptés à l'upload** : la liste `allowed_types` de
  `archived_documents` autorise `html|htm`. Un fichier HTML déposé puis rendu par
  `preview()` avec `Content-Type: text/html` et `Content-Disposition: inline` s'exécute
  dans l'origine du site (XSS stockée). Correctif séparé, côté code : retirer `html|htm`
  de `allowed_types` et forcer `Content-Disposition: attachment` pour tout ce qui n'est
  pas image ou PDF.
- **Permissions** : `uploads/` et ses sous-répertoires sont créés en `0777` par
  l'application. À restreindre.

---

## 9. Vérification post-correctif

```bash
HOST="https://VOTRE-HOTE-GVV"

# doivent renvoyer 403 (ou 404)
curl -sI "$HOST/uploads/documents/" | head -1
curl -sI "$HOST/uploads/documents/pilots/.../....pdf" | head -1     # un chemin réel
curl -sI "$HOST/uploads/restore/" | head -1
curl -sI "$HOST/backups/" | head -1
curl -sI "$HOST/backups/UN_FICHIER_REEL.zip" | head -1

# doit NE PAS renvoyer 403 (redirection login ou 200) — l'application fonctionne toujours
curl -sI "$HOST/index.php/archived_documents/preview/<id_document_existant>" | head -1
```

En cas de doute sur une exfiltration passée, analysez les journaux d'accès du serveur
web : requêtes `GET /uploads/documents/...` **sans `Referer`** provenant de l'application,
et toute requête `2xx` sur `/uploads/restore/` ou `/backups/`. Les chargements légitimes
de vignettes
portent un `Referer` vers une page `archived_documents/…` et un `User-Agent` de
navigateur.

---

## 10. Checklist

- [ ] Contenu de `backups/` et `uploads/restore/` déplacé hors de la racine web
- [ ] Blocage `uploads/documents/`, `uploads/restore/` **et** `backups/` (nginx `^~` ou Apache `Require all denied`)
- [ ] Listing de répertoire désactivé
- [ ] Vérification : accès direct à un fichier (document **et** sauvegarde) → `403` ; `preview()` via PHP → OK
- [ ] `.htaccess` de protection versionnés dans le dépôt (+ règles `.gitignore`)
- [ ] Journaux d'accès examinés ; décision de notification CNIL prise si nécessaire
- [ ] Suivi : vignettes servies via contrôleur ; `html|htm` retiré de `allowed_types` ; `backups/` hors racine web (config)
- [ ] Autres sous-répertoires `uploads/` sensibles passés en revue
