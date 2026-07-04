# Plan d'audit de sécurité GVV (version publique sanitisée)

Date : 2026-07-01  
Portée : méthode d'audit sécurité pour application web, code, déploiement et exploitation.  
Classification : PUBLIC - version sanitisée, sans détails exploitables.

## 1. Objectif

Définir un cadre d'audit de sécurité pragmatique pour :
- identifier les risques majeurs ;
- vérifier les contrôles techniques et organisationnels ;
- prioriser les remédiations ;
- suivre les progrès de mise en oeuvre.

Cette version publique ne contient ni preuves d'exploitation, ni détails techniques sensibles, ni état de vulnérabilités non corrigées.

## 2. Principes de confidentialité

- Le plan de méthode peut être public.
- Le bilan détaillé, les preuves, la liste de vulnérabilités exploitables et le suivi de correction détaillé restent privés.
- Les artefacts sensibles doivent être gérés hors Git public ou sous forme chiffrée.

## 3. Menaces principales prises en compte

### 3.1 Menaces externes
- Attaques sur les entrées applicatives (injections, XSS, CSRF, uploads malveillants).
- Tentatives de contournement d'authentification et d'autorisation.
- Exploitation de composants ou configurations obsolètes.
- Exposition de secrets ou de sauvegardes.

### 3.2 Menaces internes
- Escalade de privilèges horizontale et verticale par utilisateurs légitimes.
- Accès non autorisé à des ressources sensibles.
- Abus de fonctionnalités métier (exports, téléchargements, actions sensibles).

### 3.3 Utilisateur légitime devenant malveillant
- Exfiltration de données par usage abusif des droits.
- Sabotage de données ou de configuration.
- Contournement des contrôles de traçabilité.

## 4. Analyse de risques (modèle)

Échelles recommandées :
- Probabilité : 1 à 5
- Impact : 1 à 5
- Criticité : Probabilité x Impact

| Menace | Probabilité | Impact | Criticité | Actions préventives |
|---|---:|---:|---:|---|
| Contrôle d'accès insuffisant | 4 | 5 | 20 | deny-by-default, tests d'autorisation |
| XSS | 4 | 4 | 16 | échappement en sortie, CSP, revue UI |
| CSRF | 3 | 4 | 12 | POST + token CSRF + confirmation |
| Upload/path traversal | 3 | 5 | 15 | validation stricte, stockage sécurisé |
| Secrets/sauvegardes exposés | 3 | 5 | 15 | chiffrement, ACL, rotation |
| Abus interne de droits | 3 | 4 | 12 | ségrégation, journalisation, alertes |

## 5. Découpage des opérations d'analyse (AI-friendly)

### Phase A - Cadrage
- [ ] Définir le scope et les actifs critiques.
- [ ] Classifier les données et prioriser les zones sensibles.
- [ ] Formaliser les rôles et les attentes d'autorisation.

### Phase B - Cartographie
- [ ] Recenser endpoints et surfaces d'attaque.
- [ ] Cartographier les flux de données sensibles.
- [ ] Lister les dépendances et intégrations externes.

### Phase C - Audit code
- [ ] Revue des validations d'entrée et échappements de sortie (corrections initiales appliquées sur attributs HTML à risque, revue exhaustive restante).
- [ ] Revue des contrôles d'accès serveur par action.
- [ ] Revue des mécanismes sessions/authentification.
- [ ] Revue des traitements de fichiers (upload/download).

### Phase D - Audit de déploiement
- [ ] Vérifier hardening OS et accès admin.
- [ ] Vérifier configuration web/PHP sécurisée.
- [ ] Vérifier segmentation réseau et exposition des ports.
- [ ] Vérifier journalisation, alertes et rétention.

### Phase E - Vérification dynamique
- [ ] Tests de contournement d'autorisation.
- [ ] Tests de robustesse sur entrées critiques.
- [ ] Tests d'abus fonctionnel interne.

### Phase F - Remédiation
- [ ] Prioriser les corrections par criticité et effort.
- [ ] Planifier les actions et responsabilités.
- [ ] Définir et automatiser les tests de non-régression sécurité.
- [ ] Ré-auditer après correction.

## 6. Conseils de vérification de déploiement

Contexte d'hébergement : VM Ubuntu Oracle tier, sauvegardes quotidiennes, archives périodiques.

Checklist minimale :
- [ ] SSH durci (clés, pas de login root, accès restreint)
- [ ] Mises à jour sécurité appliquées régulièrement
- [ ] Pare-feu restrictif et ports minimaux
- [ ] HTTPS strict et headers de sécurité actifs
- [ ] Permissions minimales sur fichiers/répertoires
- [ ] Comptes DB à privilège minimal
- [ ] Sauvegardes chiffrées, contrôle d'accès, tests de restauration

## 7. Livrables attendus

### 7.1 Bilan sécurité (privé)
- Résumé exécutif
- Registre des risques/vulnérabilités
- Priorisation et plan de remédiation
- Preuves techniques et critères de validation

### 7.2 Liste de recommandations (publique possible)
- Mesures transverses de réduction de risque
- Bonnes pratiques de développement sécurisé
- Gouvernance et processus de suivi

### 7.3 Suivi des progrès (privé)

Tableau type :

| Tâche | Priorité | Responsable | Échéance | Statut |
|---|---|---|---|---|
| Contrôle d'accès serveur | Haute | À définir | À définir | [ ] |
| Durcissement upload fichiers | Haute | À définir | À définir | [ ] |
| Rotation des secrets | Moyenne | À définir | À définir | [ ] |

## 8. Position de publication GitHub

- Le plan méthodologique sanitisé est publiable.
- Le bilan détaillé et le suivi de correction détaillé ne doivent pas être publics tant que les vulnérabilités ne sont pas corrigées.

Recommandation de stockage pour le bilan et l'avancement :
1. outil privé avec ACL ;
2. ou stockage chiffré hors dépôt versionné ;
3. publication seulement après assainissement et correction.

## 9. Références internes (non sensibles)

- doc/development/review_checklist.md
- doc/security/idor_analysis.md
- doc/reviews/pr80_forms_module.md
- doc/reviews/openflyers_review_20250913.md
- doc/authorization_backup/CODE_AUDIT.md
- .claude/commands/security-audit.md

## 10. Outils d'analyse recommandés (open-source ou gratuits)

Objectif : disposer d'un socle d'outillage reproductible pour l'audit.

### 10.1 Code (SAST)
- Semgrep (règles communautaires)
- SonarQube Community
- PHPStan
- Psalm

### 10.2 Dépendances et CVE (SCA)
- Trivy
- Grype
- OWASP Dependency-Check

### 10.3 Secrets
- Gitleaks
- detect-secrets

### 10.4 Audit dynamique web (DAST)
- OWASP ZAP
- Nikto
- sqlmap (uniquement sur environnement autorisé)

### 10.5 Infrastructure
- Lynis
- OpenSCAP
- Nmap
- testssl.sh

### 10.6 Socle minimal recommandé
- Développeur local : gitleaks + semgrep + phpstan
- CI sécurité : semgrep + trivy + gitleaks + zap (scan léger)
- Audit infra périodique : lynis + nmap + revue sauvegardes/restauration
