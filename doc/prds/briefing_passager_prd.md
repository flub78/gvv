# PRD — Briefing Passager

Date : 21 mars 2026
Plan : [doc/plans/briefing_passager.md](../plans/briefing_passager.md)

## Contexte

Les exploitants qui effectuent des vols de découverte (VLD) ont l'obligation réglementaire de faire signer une déclaration d'acceptation des risques à chaque passager, après lui avoir présenté les consignes de sécurité. Ce document signé doit être archivé pendant au minimum trois mois.

Ce module permet de gérer ce processus, soit via un document papier scanné (UC1), soit via une signature numérique directe sur le téléphone du pilote ou par lien/QRCode envoyé au passager (UC2). Il s'appuie sur le système d'archivage documentaire existant pour le stockage des documents.

Les consignes de sécurité sont propres à chaque type d'appareil (planeur, avion, ULM) et donc à chaque section. Elles doivent pouvoir être mises à jour par les administrateurs.

## Objectifs

- Permettre l'enregistrement du briefing passager (papier scanné ou signature numérique) associé à un vol de découverte.
- Archiver les briefings dans le système documentaire existant pendant au moins trois mois.
- Permettre aux administrateurs de prouver la conformité réglementaire en consultant la liste des briefings signés.
- Présenter les consignes de sécurité au passager avant signature, dans la langue appropriée.
- Gérer des consignes de sécurité spécifiques par section.

## Non-objectifs

- Remplacement du processus papier : le mode UC1 coexiste avec le papier.
- Signature électronique légale qualifiée (eIDAS, etc.).
- Envoi automatique du lien par SMS ou email.

## Portée

### Inclus

- Création d'un briefing passager rattaché à un vol de découverte, avec deux modes : upload d'un document scanné (UC1) ou signature numérique en ligne (UC2).
- Stockage des briefings dans le système d'archivage documentaire existant.
- Page de signature numérique accessible sans authentification via un lien unique ou QRCode, présentant les consignes de la section sous forme de PDF.
- Ajout du champ `aerodrome` (site de décollage) à la table `vols_decouverte`.
- Recherche d'un vol de découverte lors de la création du briefing (par nom/prénom, numéro partiel ou téléphone).
- Accès direct au briefing depuis la liste des vols de découverte (icône dédiée sur chaque ligne).
- Consultation des briefings des trois derniers mois par les administrateurs, avec export PDF.
- Gestion des consignes de sécurité par section : téléchargement d'un PDF de consignes, mise à jour possible.

### Exclus

- Signatures multiples pour un même vol.
- Intégration avec un tiers de confiance pour la valeur probante de la signature.
- Historique des versions des consignes de sécurité.

---

## Cas d'utilisation

### UC1 — Upload d'un document signé (mode papier)

**Acteur** : Pilote VLD

1. Le pilote identifie le vol de découverte (depuis la liste VLD ou via la recherche dans le formulaire de briefing).
2. Le pilote imprime le document de consignes de sécurité de sa section.
3. Le pilote présente le document au passager, lui explique les consignes et répond à ses questions.
4. Le passager signe le document papier.
5. Le pilote scanne ou photographie le document.
6. Le pilote télécharge le fichier (PDF ou image) dans le système via le formulaire de briefing.
7. Le briefing est archivé dans le système documentaire et associé au vol.

### UC2 — Signature numérique (mode dématérialisé)

**Acteur** : Pilote VLD, Passager

1. Le pilote identifie le vol de découverte.
2. Le pilote génère un lien de signature depuis le formulaire de briefing.
3. Le pilote ouvre la page sur son téléphone et la présente au passager.
4. La page affiche en premier un QRCode permettant au passager de l'ouvrir sur son propre téléphone. Si le passager scanne le QRCode, il accède à la même page sur son appareil.
5. Sous le QRCode, la page présente dans l'ordre :
   - Les consignes de sécurité de la section (PDF affiché en ligne).
   - Le formulaire passager pré-rempli (nom, prénom, date de naissance, poids déclaré, personne à prévenir), modifiable.
   - Le mécanisme d'acceptation : signature tactile ou case à cocher + bouton de confirmation.
6. Le passager complète et soumet le formulaire, que ce soit sur le téléphone du pilote ou sur le sien. La page confirme la prise en compte.
7. Le système génère un PDF récapitulatif du briefing (informations vol + informations passager + signature ou acceptation), l'archive dans le système documentaire et l'associe au vol.

### UC3 — Consultation administrative

**Acteur** : Administrateur

1. L'administrateur accède à la liste des briefings passagers des trois derniers mois.
2. La liste affiche pour chaque briefing : date du vol, aérodrome, identification de l'appareil, nom du passager, type (upload / numérique), statut (présent / absent).
3. L'administrateur clique sur un briefing pour visualiser le document archivé (scan ou PDF généré).
4. L'administrateur peut exporter la liste en PDF.

---

## Exigences fonctionnelles

### F1 — Extension de la table `vols_decouverte`

- Ajouter le champ `aerodrome` (site de décollage) à la table `vols_decouverte`.
- Ce champ est affiché et éditable dans le formulaire de création/modification d'un VLD.
- Il est pré-rempli dans le formulaire de signature numérique (UC2).

### F2 — Gestion des consignes de sécurité par section

- Chaque section peut avoir un document de consignes au format PDF.
- L'administrateur peut télécharger un nouveau PDF de consignes pour sa section via l'interface de configuration.
- Le document actif est celui présenté lors des signatures numériques (UC2) et disponible à l'impression (UC1).

### F3 — Accès au briefing depuis la liste des vols de découverte

- La liste des VLD affiche une icône par ligne indiquant l'état du briefing :
  - Icône neutre : aucun briefing enregistré (cliquable pour en créer un).
  - Icône validée : briefing présent (cliquable pour le consulter ou le remplacer).

### F4 — Formulaire de briefing standalone

- Un formulaire de briefing est accessible indépendamment de la liste VLD.
- Il comporte un champ de recherche de vol de découverte permettant de saisir des caractères partiels du nom/prénom du bénéficiaire, du numéro de vol ou du numéro de téléphone.
- Le sélecteur propose les VLD correspondants au fur et à mesure de la saisie.

### F5 — Mode upload (UC1)

- Le formulaire permet de télécharger un fichier (PDF, JPEG, PNG).
- La date de signature peut être saisie manuellement (par défaut : date du jour).
- Le fichier est archivé dans le système documentaire avec le type de document « briefing passager ».

### F6 — Mode signature numérique (UC2)

- Un lien unique (non-devinable, à usage unique) est généré pour chaque briefing.
- La page de signature est accessible sans authentification via ce lien unique.
- La page présente les éléments dans l'ordre suivant :
  1. Un QRCode encodant l'URL de la page elle-même, accompagné d'un texte invitant le passager à scanner pour ouvrir la page sur son propre appareil.
  2. Le PDF des consignes de sécurité de la section (affiché en ligne et téléchargeable).
  3. Les informations du vol (date, aérodrome, identification de l'appareil) — lecture seule.
  4. Le formulaire passager pré-rempli depuis le VLD : nom, prénom, date de naissance, poids déclaré, personne à prévenir en cas d'accident. Chaque champ est modifiable par le passager.
  5. La zone d'acceptation : signature tactile (*signature_pad*) et, en alternative, une case à cocher *« Je soussigné(e) atteste avoir pris connaissance des informations ci-dessus et accepte d'effectuer le vol dans ces conditions »* suivie d'un bouton de confirmation.
- Une fois soumise, la page confirme la prise en compte et ne peut plus être soumise.
- Le système enregistre la date, l'heure et l'adresse IP lors de la soumission.
- Si les informations saisies par le passager diffèrent de celles du VLD, le VLD est mis à jour avec les valeurs corrigées.
- Un PDF récapitulatif est généré (informations vol, informations passager telles que validées, signature ou acceptation), archivé dans le système documentaire et envoyé par email au passager.

### F7 — Intégration avec le système d'archivage documentaire

- Les briefings sont des documents archivés au sens du module d'archivage documentaire existant.
- Deux origines possibles pour un document de type « briefing passager » : fichier uploadé (UC1) ou PDF généré par le système (UC2).
- Les briefings sont associés au vol de découverte comme entité de rattachement.
- La durée de conservation minimale est de trois mois. Un briefing ne peut pas être supprimé avant l'échéance.

### F8 — Consultation et export administrateur

- L'administrateur accède à une vue listant les briefings des trois derniers mois (filtre par défaut, modifiable).
- Chaque ligne affiche : date du vol, aérodrome, identification appareil, nom du passager, mode (upload / numérique), date de signature.
- Un clic sur la ligne ouvre le document archivé.
- La liste peut être exportée en PDF.

---

## Règles métier

1. **Un briefing par vol** : un seul briefing peut être associé à un vol de découverte. Un briefing existant peut être remplacé (avec confirmation).
2. **Lien de signature à usage unique** : le lien est invalidé après soumission réussie.
3. **Consignes par section** : les consignes présentées sont celles de la section associée au vol de découverte.
4. **Conservation minimale** : un briefing ne peut pas être supprimé avant trois mois à compter de la date du vol.
5. **Indépendance de l'archivage** : la suppression ou l'annulation d'un VLD ne supprime pas le briefing associé.
6. **Synchronisation des champs passager** : le formulaire de signature numérique (UC2) est pré-rempli avec les données du VLD (`beneficiaire`, `urgence`, etc.). Le passager peut accepter ou corriger ces informations. À la soumission, si les valeurs diffèrent de celles du VLD, le VLD est mis à jour en conséquence.

---

## Critères d'acceptation

1. Le champ `aerodrome` est présent dans la table `vols_decouverte` et dans le formulaire VLD.
2. Un pilote peut créer un briefing par upload depuis la liste des VLD ou depuis le formulaire standalone avec recherche de vol.
3. Un pilote peut générer un lien et un QRCode de signature numérique pour un VLD.
4. Le passager accède à la page de signature sans compte, visualise les consignes de sa section, saisit ses informations, signe sur écran tactile ou coche l'acceptation, et valide.
5. La page de signature ne peut pas être soumise deux fois.
6. Un PDF récapitulatif est généré et archivé après signature numérique.
7. L'administrateur accède à la liste des briefings des trois derniers mois et peut visualiser chaque document.
8. L'export PDF de la liste des briefings fonctionne.
9. Les consignes de sécurité d'une section peuvent être mises à jour par upload d'un nouveau PDF.
10. Un briefing de moins de trois mois ne peut pas être supprimé.
11. La suppression d'un VLD ne supprime pas le briefing associé.
