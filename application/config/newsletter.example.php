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

// ── Template email de relecture ───────────────────────────────────────────
// Variables : {period} {version} {date} {deadline} {from_name} {draft_name}
$config['newsletter_review_subject'] = '[Veille ACS] Brouillon {period} (v{version}) — relecture souhaitée';
$config['newsletter_review_body']    = "Bonjour,\n\nJe vous envoie en pièce jointe le brouillon v{version} de la lettre de veille sécurité et réglementation pour la période : {period}.\n\nMerci de me faire parvenir vos remarques, corrections ou suggestions d'articles en répondant à cet email, avant le {deadline}.\n\nCe brouillon est préparé automatiquement à partir des sources réglementaires et fédérales (EASA, BEA, DSAC, FFA, FFVP, FFPLUM…). Vos retours permettront d'affiner la sélection et la rédaction avant validation par le CPS.\n\nCordialement,\n{from_name}\n\n---\nVeille sécurité et réglementation — {period}\nAéroclub d'Abbeville – Buigny – Baie de Somme";

// ── Chemins des fichiers de l'agent IA ────────────────────────────────────
$config['newsletter_context_path']  = '/srv/newsletter/context.md';
$config['newsletter_output_path']   = '/srv/newsletter/output';

// ── Agent IA ──────────────────────────────────────────────────────────────
// Clé API Anthropic pour les tests et la génération automatique
$config['newsletter_anthropic_api_key'] = '';
// Modèle par défaut : claude-opus-4-8 | claude-sonnet-4-6 | claude-haiku-4-5-20251001
$config['newsletter_anthropic_model']   = 'claude-sonnet-4-6';

// ── Options ───────────────────────────────────────────────────────────────
$config['newsletter_alerts_enabled'] = false;

/* End of file newsletter.example.php */
