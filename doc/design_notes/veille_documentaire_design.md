# Design Notes — Veille documentaire

Date : 23 octobre 2026

Document de référence : [PRD_Veille_Securite_Automatisee.md](../prds/PRD_Veille_Securite_Automatisee.md)

---

## Contexte et objectif

Le système de veille documentaire génère automatiquement une newsletter périodique à partir d'un ensemble de sources configurées (boîte mail, flux RSS, sites web). L'agent IA rédige un brouillon, des relecteurs humains le corrigent par email ou via GVV, un validateur l'approuve, puis la newsletter est archivée et diffusée aux abonnés.

---

## Architecture

![Architecture composants](diagrams/veille_composants.png)

### Composants principaux

| Composant | Rôle |
|---|---|
| `newsletter_controller` | Interface GVV : administration, archive membres, abonnements, retours de relecture, approbation |
| `newsletter_model` | Gestion des newsletters (états, versions, fichiers) |
| `newsletter_subscribers_model` | Gestion des abonnements externes |
| `newsletter_agent_runner` | Script CLI qui déclenche l'agent IA et lui passe le fichier de contexte |
| Agent IA (`claude -p`) | Génère les brouillons, intègre les retours de relecture, nettoie la version finale |
| Fichier de contexte | Fichier maintenu par l'administrateur : liste des sources, critères de sélection, directives de rédaction, description du club |
| Collecteur de retours | Script déclenché quotidiennement : lit les réponses email des relecteurs, les enregistre en base, déclenche l'agent IA si des retours sont présents |
| Cron de surveillance | Vérifie qu'un brouillon a bien été créé après le déclenchement mensuel ; alerte l'administrateur sinon |

### Séparation des responsabilités

L'agent IA est un processus externe au serveur web. Il lit les sources, crée des fichiers de brouillon sur le serveur, et ne peut en aucun cas déclencher d'envoi d'email. L'envoi est toujours initié par le code PHP applicatif (contrôleur ou script).

Le fichier de contexte n'est jamais exposé à l'agent au-delà de ce que le script `newsletter_agent_runner` lui transmet explicitement. Les identifiants IMAP et SMTP restent dans la configuration GVV.

---

## Cycle de vie d'une newsletter

![Workflow](diagrams/veille_workflow.png)

Les états sont stockés dans `newsletters.status`. Les transitions sont :

| De → Vers | Déclencheur |
|---|---|
| → `draft` | Agent IA crée le fichier brouillon ; `newsletter_agent_runner` enregistre l'entrée en base |
| `draft` → `reviewing` | Envoi du brouillon aux relecteurs par le contrôleur |
| `reviewing` → `reviewing` | Retours collectés → nouvelle version intégrée par l'agent → `review_iteration` incrémenté → renvoi aux relecteurs |
| `reviewing` → `validating` | Action manuelle d'un relecteur ou de l'administrateur via GVV |
| `validating` → `approved` | Approbation du validateur (email ou GVV) |
| `approved` → `published` | Agent IA génère le PDF final → archivage → envoi aux abonnés |

### Itérations de relecture

Chaque retour de relecteur est enregistré dans `newsletter_reviews` avec son numéro d'itération. L'agent IA reçoit tous les retours de l'itération courante lors de la consolidation quotidienne et crée une nouvelle version du fichier brouillon. L'historique des versions est conservé en base.

---

## Schéma de base de données

![Schéma BDD](diagrams/veille_schema.png)

### Tables

**`newsletters`** : une ligne par édition. `draft_path` pointe vers le fichier Markdown de travail sur le serveur ; `pdf_path` vers le PDF publié. Le champ `review_iteration` trace la version courante du cycle de relecture.

**`newsletter_reviews`** : retours des relecteurs et des validateurs. `submitted_via` distingue les retours par email (collectés automatiquement) des retours saisis dans GVV. Les pièces jointes email sont stockées dans un répertoire associé à l'entrée.

**`newsletter_subscribers`** : abonnés extérieurs au club. `unsubscribe_token` est généré à l'inscription et inclus dans chaque email envoyé pour permettre la désinscription en un clic. Le double opt-in est géré par `confirmation_token` et `confirmed`.

**`newsletter_sendings`** : journal des envois par abonné, pour éviter les doublons en cas de re-run et tracer la diffusion.

---

## Abonnements

Le mécanisme d'abonnement est indépendant du cycle de génération automatique. Il peut servir des newsletters créées manuellement en publiant directement un PDF via l'interface d'administration.

La page d'abonnement publique (sans authentification GVV) expose deux actions : `subscribe` (formulaire nom/prénom/email → envoi du lien de confirmation) et `unsubscribe` (via le token de l'email ou formulaire email).

---

## Alertes immédiates (optionnelles)

Si l'administrateur active cette option, l'agent IA peut identifier dans les sources une information urgente (consigne de navigabilité urgente, NOTAM local) et la signaler. Le script `newsletter_agent_runner` lit ce signal dans la sortie de l'agent et envoie immédiatement une notification aux validateurs par email ou SMS, selon le canal configuré pour chacun.

Ces alertes sont indépendantes du cycle de newsletter et ne déclenchent pas de brouillon.

---

## Intégration de l'agent IA (`claude -p`)

### Contraintes et problèmes de sécurité

**Blocage sur une demande d'autorisation**

Sans précaution, `claude -p` peut bloquer indéfiniment sur stdin si l'agent tente une action nécessitant une confirmation (créer un fichier, exécuter une commande). Pour un script lancé par un timer systemd, ce blocage est fatal : le processus ne se termine jamais.

Solution : lancer claude avec `--permission-mode bypassPermissions` (ou `--dangerously-skip-permissions` en environnement isolé). Ces drapeaux suppriment toute demande interactive. En combinaison avec `--allowedTools` qui limite les outils autorisés, le comportement de l'agent est déterministe et sans surprise. `--bare` est également recommandé pour désactiver les hooks, la découverte de CLAUDE.md, la mémoire automatique et les prefetches en arrière-plan — seul l'API key est utilisé, le contexte étant fourni explicitement.

```bash
timeout 300 claude \
  --bare \
  --print \
  --permission-mode bypassPermissions \
  --allowedTools "Write,Read,Bash(ls *)" \
  --max-budget-usd 3.00 \
  --system-prompt "$(cat /srv/newsletter/context.md)" \
  "Génère le brouillon de la newsletter pour la période …"
```

**Passage du fichier de contexte**

Le fichier de contexte (sources, critères, directives) est passé via `--system-prompt "$(cat …)"`. Cette approche est limitée par la taille maximale d'un argument shell (~2 Mo sur Linux, suffisant pour un fichier de directives). Pour un contenu plus volumineux (sources brutes), le script prépare un fichier temporaire dans le répertoire de travail que l'agent lira avec ses outils (`Read`).

Les identifiants IMAP et SMTP ne sont jamais inclus dans le fichier de contexte. Le script les lit lui-même depuis la configuration GVV et les utilise directement en PHP pour pré-alimenter le répertoire de travail.

**Récupération des fichiers de résultats**

L'agent écrit ses fichiers dans le répertoire courant du processus (`cwd`). Le script positionne ce répertoire sur un chemin dédié avant de lancer claude :

```bash
mkdir -p /srv/newsletter/output/$(date +%Y-%m)
cd /srv/newsletter/output/$(date +%Y-%m)
claude --print --bare … "Écris le brouillon dans draft.md"
```

Le script PHP lit ensuite `draft.md` depuis ce répertoire. La sortie stdout de claude (résumé de ce que l'agent a fait) est capturée et journalisée, mais n'est pas le résultat principal : c'est le fichier écrit sur disque qui fait foi.

**Isolation et sécurité du processus**

- Le timer systemd exécute les scripts sous un utilisateur dédié (`newsletter-agent`) sans accès au répertoire de l'application web ni à la base de données.
- `ANTHROPIC_API_KEY` est défini dans le fichier d'environnement du service systemd (`/etc/systemd/system/newsletter-generate.service`, protégé en `0600`), jamais dans un fichier accessible par le serveur web.
- Le répertoire de travail de l'agent (`/srv/newsletter/`) appartient à `newsletter-agent` ; www-data y a accès en lecture seule pour récupérer les brouillons.
- `--max-budget-usd` coupe le processus si le coût dépasse le plafond configuré (erreur non-zero exit code, capturée par le script PHP).
- La commande est enveloppée dans `timeout 300` pour garantir une durée maximale quelle que soit la réponse de l'API.

---

## API Anthropic directe vs `claude -p`

Deux approches permettent d'appeler le modèle depuis le script PHP.

| Critère | API Anthropic directe (HTTP/CURL) | `claude -p` (CLI) |
|---|---|---|
| Requêtes de permission | Aucune | Bloque sans `--permission-mode bypassPermissions` |
| Gestion des outils (fichiers, bash) | Implémentation manuelle en PHP | Gérée par Claude Code |
| Passage du contexte | Tableau `messages[]` JSON, taille illimitée | Argument shell ou fichier lu par l'agent |
| Récupération du résultat | Réponse JSON dans la même requête HTTP | Stdout + fichiers écrits par l'agent |
| Version du modèle | Explicite à chaque appel | Contrôlée par la config du compte système |
| Dépendances | CURL PHP (standard) | CLI `claude` installée sur le serveur |
| Confidentialité du prompt | Totale (payload HTTP maîtrisé) | Logs possibles dans `~/.claude/` |
| Coût visible | Par token, dashboard API | Via `--max-budget-usd`, moins granulaire |
| Prompt caching | Configurable explicitement | Automatique (bêta) |
| Complexité d'intégration | Élevée (tool_use, boucle de tours) | Faible (shell_exec + lecture de fichier) |
| Mise à jour du modèle | Contrôlée (modification du code) | Peut changer au upgrade du CLI |
| Maturité en production | Élevée | Plus récent en mode non-interactif |

**Recommandation pour la V1** : `claude -p` avec `--bare --permission-mode bypassPermissions --max-budget-usd`. La complexité d'intégration est moindre et les outils natifs de l'agent (lecture de fichiers, écriture) évitent d'implémenter ce mécanisme en PHP. La migration vers l'API directe est envisageable en V2 si la robustesse industrielle ou le contrôle fin des tokens le justifient.

---

## Configuration de la veille

Les paramètres opérationnels de la veille (compte IMAP, liste des relecteurs et validateurs, périodicité, chemin du fichier de contexte) sont configurables par un `club-admin` depuis la page d'administration de la newsletter dans GVV (`newsletter_admin/configuration`). Cette page écrit un fichier `application/config/newsletter.php` selon le même mécanisme que `config.php` → `club.php`.

Le mot de passe IMAP est stocké obfusqué dans ce fichier (encodage base64 ou chiffrement symétrique avec une clé dérivée du `encryption_key` de CodeIgniter). Il n'est jamais affiché en clair dans l'interface après la première saisie.

La clé API Anthropic (`ANTHROPIC_API_KEY`) n'est pas stockée dans GVV. Elle est définie dans l'environnement du service systemd.

---

## Intégration dans GVV

### Feature flag

La fonctionnalité est entièrement protégée par le flag `$config['veille']` défini dans `program.php` (et `program.example.php`) :

```php
$config['veille'] = false;  // Newsletter de veille documentaire
```

Comportement quand `veille` est `false` ou absent :
- Les contrôleurs `newsletter_admin`, `newsletter`, `newsletter_public` retournent une erreur 404.
- Les entrées de menu correspondantes sont masquées.
- Les scripts CLI (`bin/newsletter_*.php`) vérifient le flag et s'arrêtent proprement s'il est désactivé.

Le flag est `false` par défaut dans `program.example.php`. Il est activé manuellement par l'administrateur système dans `program.php` pour les clubs qui utilisent la fonctionnalité.

### Autres points d'intégration

- Les contrôleurs `newsletter_admin`, `newsletter` et `newsletter_public` vérifient le flag dans leur constructeur, avant toute logique métier.
- L'accès à l'archive est réservé aux membres actifs connectés (`$this->require_role('user')`).
- L'administration (configuration, gestion des abonnés, déclenchement manuel) est réservée aux `club-admin`.
- Les scripts CLI (`newsletter_agent_runner`, collecteur de retours, cron de surveillance) sont placés dans `bin/` et configurés comme timers systemd sur le serveur.

---

## Hors périmètre (V1)

- Synchronisation des abonnés avec les membres GVV.
- Sources avec authentification (espaces licenciés des fédérations).
- Isolation réseau stricte de l'agent IA (envisagée en V2 si nécessaire).
- Archivage du journal de veille pour audit DSAC (log des décisions de l'agent).
