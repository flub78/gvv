# PRD : Paiement Générique par QR Code

**Produit :** GVV (Gestion Vol à Voile)
**Fonctionnalité :** Page de paiement générique via HelloAsso avec QR Code
**Version :** 1.0
**Statut :** Proposition
**Créé :** 2026-05-07

---

## 1. Résumé

Le trésorier doit pouvoir encaisser n'importe quel paiement via HelloAsso exactement comme il enregistre un chèque ou un virement : en saisissant un montant, une description, un compte comptable cible. Le système génère un lien de paiement et un QR code à présenter ou transmettre au payeur. À réception du paiement, l'écriture comptable est créée automatiquement avec la description saisie.

---

## 2. Contexte

### 2.1 Situation actuelle

Le trésorier encaisse les paiements non-automatisés (cotisations, remboursements, locations ponctuelles, etc.) en saisissant manuellement une écriture comptable dans GVV après réception du chèque ou virement. Cette saisie manuelle est fiable mais implique un délai et une présence physique.

HelloAsso est déjà intégré dans GVV pour trois cas spécifiques : provisionnement de compte pilote, bar externe, et vol découverte. Chacun est un flux dédié avec sa propre logique métier.

### 2.2 Besoin

Il manque un flux **générique** : le trésorier veut initier un paiement HelloAsso pour n'importe quel motif, sans qu'un flux métier spécifique soit nécessaire. Ce flux doit produire exactement la même écriture comptable qu'une saisie manuelle.

---

## 3. Utilisateurs cibles

| Rôle | Usage |
|------|-------|
| Trésorier (admin) | Créer les demandes de paiement, consulter les paiements reçus |
| Payeur (membre ou externe) | Payer via HelloAsso depuis le lien ou QR code |

---

## 4. Exigences fonctionnelles

### 4.1 Création d'une demande de paiement

Le trésorier saisit :

- **Montant** (obligatoire) — dans les limites configurées HelloAsso
- **Description** (obligatoire) — libellé libre, repris dans l'écriture comptable et dans les listings
- **Compte comptable cible** (obligatoire) — sélectionné parmi les comptes actifs du plan comptable
- **Email du payeur** (optionnel) — pré-remplit le formulaire HelloAsso

À la validation, le système génère :
- Un lien de paiement HelloAsso
- Un QR code pointant vers ce lien
- Un identifiant de transaction GVV unique

### 4.2 Paiement par le payeur

Le payeur accède à la page HelloAsso via le lien ou le QR code et effectue le paiement par carte bancaire. Aucune interface supplémentaire dans GVV n'est nécessaire pour le payeur.

### 4.3 Traitement automatique à réception

À réception du webhook HelloAsso :

- Une écriture comptable est créée avec la description saisie par le trésorier
- Le compte comptable cible reçoit le crédit
- Le compte de passage HelloAsso est débité
- La transaction est marquée comme complétée

### 4.4 Annulation et expiration

- Le trésorier peut annuler une demande en attente
- Une demande non payée au bout de 7 jours est automatiquement annulée
- Aucune écriture n'est créée pour les demandes annulées ou expirées

### 4.5 Consultation des paiements génériques

Le trésorier dispose d'une vue listant les demandes de paiement génériques avec :
- Statut (en attente, payé, annulé, expiré)
- Date de création et date de paiement
- Montant
- Description
- Accès à l'écriture comptable générée

---

## 5. Exigences non-fonctionnelles

- La description est limitée à 255 caractères
- Le lien de paiement est à usage unique : un second paiement sur le même lien est ignoré
- La création d'une demande requiert le rôle trésorier
- La configuration HelloAsso (clés API, compte de passage) est partagée avec les flux existants
- Tout paiement générique doit apparaître dans le rapprochement bancaire HelloAsso existant

---

## 6. Hors périmètre

- Paiements récurrents ou par échéances
- Personnalisation de la page HelloAsso (logo, texte)
- Génération de reçu PDF
- Paiements en plusieurs fois
