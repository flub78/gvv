<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Configuration de la newsletter de veille documentaire
|--------------------------------------------------------------------------
|
| Copier ce fichier en newsletter.php et compléter les valeurs.
| Ce fichier ne doit PAS être commité (contient le mot de passe IMAP).
|
*/

// ── Boîte mail de collecte (IMAP) ──────────────────────────────────────────
// Adresse email de la boîte surveillée par l'agent IA
$config['newsletter_imap_host']     = 'mail.example.com';
$config['newsletter_imap_port']     = 993;
$config['newsletter_imap_user']     = 'veille@example.com';
// Mot de passe stocké encodé en base64 (obfuscation — protéger ce fichier en lecture)
$config['newsletter_imap_password'] = '';
$config['newsletter_imap_ssl']      = true;
$config['newsletter_imap_mailbox']  = 'INBOX';

// ── Envoi (SMTP) ───────────────────────────────────────────────────────────
// Si newsletter_smtp_host est vide, GVV utilise la config SMTP globale (Brevo).
$config['newsletter_smtp_host']      = 'smtp.ionos.fr';
$config['newsletter_smtp_port']      = '587';
$config['newsletter_smtp_crypto']    = 'tls';   // 'tls' pour 587, 'ssl' pour 465
$config['newsletter_smtp_user']      = 'veille@example.com';
$config['newsletter_smtp_password']  = '';      // base64 comme le mot de passe IMAP
$config['newsletter_smtp_from']      = 'veille@example.com';
$config['newsletter_smtp_from_name'] = 'Veille sécurité — Nom du club';

// ── Relecteurs et validateurs ──────────────────────────────────────────────
// Liste d'adresses email séparées par des virgules
$config['newsletter_reviewers']     = '';
$config['newsletter_validators']    = '';

// ── Chemins des fichiers de l'agent IA ────────────────────────────────────
$config['newsletter_context_path']  = '/srv/newsletter/context.md';
$config['newsletter_output_path']   = '/srv/newsletter/output';

// ── Options ───────────────────────────────────────────────────────────────
$config['newsletter_alerts_enabled'] = false;

/* End of file newsletter.example.php */
