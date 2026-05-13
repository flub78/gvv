# Plan de migration GVV — Sections Avion et ULM

## Contexte

| | |
|---|---|
| Instance existante (planeur) | https://gvv.planeur-abbeville.fr |
| Nouvelle instance (toutes sections) | https://gestion.aeroclub-abbeville.fr |
| Sections à activer | Avion (AVI) et ULM (ULM) |
| Situation initiale | Les comptes utilisateurs existent mais les membres ne connaissent pas leurs identifiants |

La nouvelle instance est déjà déployée avec les 4 sections configurées (Planeur, ULM, Avion, Général). Le mécanisme de première connexion est le lien **Mot de passe oublié** sur la page de login.

---

## Contraintes identifiées

- **16 membres actifs sans adresse email** dans la base : ils ne peuvent pas utiliser "Mot de passe oublié" et nécessitent une intervention manuelle.
- Certains comptes `users` ont un champ `email` vide alors que `membres.memail` est renseigné — à corriger avant l'envoi.
- Le membre fictif `xxx` (Autre pilote Ext) n'a pas de compte et ne doit pas en avoir.

---

## Étapes

### Phase 1 — Vérifications préalables (J-10)

**1.1 Vérifier l'envoi d'emails sur la nouvelle instance**

Se connecter en admin sur https://gestion.aeroclub-abbeville.fr et tester la fonction "Mot de passe oublié" avec votre propre compte. Vérifier que l'email arrive et que le lien de réinitialisation fonctionne.

**1.2 Identifier les membres sans email opérationnel**

Exécuter sur la base de la nouvelle instance :

```sql
-- Membres actifs dont le compte users n'a pas d'email utilisable
SELECT u.username, m.mnom, m.mprenom, m.memail, u.email AS user_email
FROM users u
LEFT JOIN membres m ON m.mlogin = u.username
WHERE (u.email IS NULL OR u.email = '')
  AND m.actif = 1
ORDER BY m.mnom;

-- Membres actifs sans aucune adresse email dans la base
SELECT mlogin, mnom, mprenom
FROM membres
WHERE actif = 1 AND (memail IS NULL OR memail = '');
```

**1.3 Synchroniser les emails membres → comptes users**

Pour les comptes `users` avec `email` vide mais `membres.memail` renseigné :

```sql
UPDATE users u
JOIN membres m ON m.mlogin = u.username
SET u.email = m.memail
WHERE (u.email IS NULL OR u.email = '')
  AND m.memail IS NOT NULL AND m.memail != '';
```

**1.4 Traiter manuellement les membres sans email**

Pour chaque membre sans email identifié en 1.2 :
- Contacter le membre par téléphone pour obtenir une adresse email
- Mettre à jour sa fiche membre et son compte utilisateur
- Ou convenir d'un identifiant et mot de passe à lui communiquer directement

---

### Phase 2 — Sauvegarde (J-3)

**2.1 Sauvegarde de la nouvelle instance**

```bash
# Sur le serveur hébergeant gestion.aeroclub-abbeville.fr
DATE=$(date +%Y%m%d_%H%M)
mysqldump -u <user> -p <database> > backup_gestion_aeroclub_${DATE}.sql
gzip backup_gestion_aeroclub_${DATE}.sql
```

Conserver cette sauvegarde en lieu sûr avant toute modification.

**2.2 Sauvegarde de l'instance planeur existante**

```bash
mysqldump -u <user> -p <database_planeur> > backup_planeur_abbeville_${DATE}.sql
gzip backup_planeur_abbeville_${DATE}.sql
```

**2.3 Vérification des sauvegardes**

```bash
# Vérifier que les sauvegardes sont lisibles
zcat backup_gestion_aeroclub_${DATE}.sql.gz | head -20
```

---

### Phase 3 — Annonce aux membres (J-7)

Envoyer l'**email d'annonce** (voir modèle ci-dessous) aux membres des sections Avion et ULM via la liste d'emails de chaque section ou par export CSV depuis la fiche membres.

---

### Phase 4 — Ouverture et email de connexion (J-0)

**4.1 Vérification finale**

Avant l'envoi de l'email de connexion :
- [ ] La nouvelle instance répond sur https://gestion.aeroclub-abbeville.fr
- [ ] La page "Mot de passe oublié" envoie bien un email
- [ ] Les comptes sans email ont été traités (phase 1.4)
- [ ] La sauvegarde J-3 est disponible
- [ ] Un admin peut se connecter

**4.2 Envoi de l'email de connexion**

Envoyer l'**email de première connexion** (voir modèle ci-dessous) aux membres Avion et ULM.

**4.3 Surveillance**

- Surveiller les tentatives de connexion via les logs GVV (Outils → Historique)
- Rester disponible les 48 premières heures pour aider les membres bloqués
- Traiter les demandes d'aide par email ou téléphone

---

### Phase 5 — Validation (J+7)

- Vérifier que les membres ont bien pu se connecter (colonne `last_login` dans la table `users`)
- Relancer par email les membres qui n'ont toujours pas de `last_login` renseigné
- Documenter les problèmes rencontrés

```sql
-- Membres qui ne se sont toujours pas connectés
SELECT u.username, m.mnom, m.mprenom, m.memail, u.last_login
FROM users u
JOIN membres m ON m.mlogin = u.username
WHERE (u.last_login IS NULL OR u.last_login = '0000-00-00 00:00:00')
  AND m.actif = 1
ORDER BY m.mnom;
```

---

## Procédure de rollback

En cas de problème bloquant sur la nouvelle instance :

```bash
# Restaurer la sauvegarde J-3
zcat backup_gestion_aeroclub_${DATE}.sql.gz | mysql -u <user> -p <database>
```

Communiquer aux membres qu'un problème technique a été détecté et que la mise en service est reportée.

---

## Modèles d'emails

### Email 1 — Annonce (J-7)

> **Objet : GVV — Votre section rejoint le nouvel outil de gestion de l'aéroclub**
>
> Bonjour [Prénom],
>
> L'aéroclub d'Abbeville déploie son outil de gestion GVV sur une nouvelle plateforme commune à toutes les sections : **https://gestion.aeroclub-abbeville.fr**
>
> GVV vous permettra de :
> - Consulter et suivre vos vols
> - Accéder à votre compte pilote et votre solde
> - Consulter votre suivi de formation
> - Régler vos cotisations en ligne
>
> **Dans une semaine**, vous recevrez un email avec les instructions pour créer votre mot de passe et vous connecter pour la première fois.
>
> Si vous avez des questions, contactez-nous à info@aeroclub-abbeville.fr.
>
> Cordialement,
> L'équipe de l'Aéroclub d'Abbeville

---

### Email 2 — Première connexion (J-0)

> **Objet : GVV — Activez votre accès sur gestion.aeroclub-abbeville.fr**
>
> Bonjour [Prénom],
>
> La plateforme de gestion GVV est maintenant ouverte pour votre section.
>
> **Votre identifiant de connexion est : `[mlogin]`**
>
> Pour définir votre mot de passe et accéder à votre espace :
>
> 1. Rendez-vous sur **https://gestion.aeroclub-abbeville.fr**
> 2. Cliquez sur **"Mot de passe oublié"**
> 3. Saisissez l'adresse email à laquelle vous recevez ce message
> 4. Cliquez sur le lien reçu par email et choisissez votre mot de passe
> 5. Connectez-vous avec votre identifiant et votre nouveau mot de passe
>
> Si vous n'avez pas reçu l'email de réinitialisation dans les 10 minutes, vérifiez vos spams ou contactez-nous à info@aeroclub-abbeville.fr en indiquant votre nom et prénom.
>
> Cordialement,
> L'équipe de l'Aéroclub d'Abbeville

---

### Email 3 — Relance pour non-connectés (J+7)

> **Objet : GVV — Rappel : activez votre accès**
>
> Bonjour [Prénom],
>
> Nous n'avons pas encore vu votre première connexion sur https://gestion.aeroclub-abbeville.fr.
>
> **Votre identifiant est : `[mlogin]`**
>
> Si vous avez besoin d'aide, répondez à cet email ou appelez-nous.
>
> Cordialement,
> L'équipe de l'Aéroclub d'Abbeville

---

## Points d'attention spécifiques

| Situation | Action |
|---|---|
| Membre sans adresse email | Contacter par téléphone, mettre à jour la fiche avant J-0 |
| Membre dont l'email a changé | Mettre à jour `membres.memail` ET `users.email` |
| Membre qui ne reçoit pas l'email | Vérifier les spams, puis vérifier l'email dans la base, puis communiquer identifiant+mot de passe par téléphone |
| Compte `users` avec `email` vide | Corriger via le script SQL de la phase 1.3 |

## Statut

- [ ] Phase 1 — Vérifications préalables
- [ ] Phase 2 — Sauvegardes
- [ ] Phase 3 — Email d'annonce envoyé
- [ ] Phase 4 — Email de connexion envoyé
- [ ] Phase 5 — Validation post-lancement
