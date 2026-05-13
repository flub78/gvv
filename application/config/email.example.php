<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
| Email configuration for Brevo (Sendinblue) SMTP
| Works on Oracle Free Tier: use 587 + STARTTLS
*/

$config['protocol']    = 'smtp';
$config['smtp_host']   = 'smtp-relay.brevo.com';
$config['smtp_port']   = 587;              // 587=STARTTLS, 465=SSL, 2525=STARTTLS
$config['smtp_crypto'] = 'tls';            // 'tls' for 587/2525, 'ssl' for 465

// Brevo credentials
$config['smtp_user']   = 'xxxxxxxx@smtp-brevo.com';         // Brevo SMTP username is literally 'apikey'

// Clé Brevo 
$config['smtp_pass']   = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';

// General email options
$config['mailtype']    = 'html';
$config['charset']     = 'utf-8';
$config['wordwrap']    = TRUE;
$config['smtp_timeout']= 20;               // seconds
$config['newline']     = "\r\n";           // required by many SMTP servers
$config['crlf']        = "\r\n";
$config['useragent']   = 'GVV';
$config['validate']    = TRUE;

/* End of file email.php */
/* Location: ./application/config/email.php */
