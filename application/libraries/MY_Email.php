<?php

/**
 * MY_Email — Extension CodeIgniter de la bibliothèque Email
 *
 * Corrige un bug de CI 2.x dans _prep_q_encoding() : quand crlf="\r\n",
 * le preg_replace /^(.*)$/m capture le \r dans le groupe (.*) car $ en mode
 * multiline ancre avant \n mais pas avant \r. Le \r se retrouve alors à
 * l'intérieur du mot encodé (ex: =?utf-8?Q?...Pe\r?=), ce qui est invalide
 * selon RFC 2047 et provoque une erreur "554 Header parsing error" chez Brevo.
 *
 * Solution : utiliser mb_encode_mimeheader() qui produit un encodage RFC 2047
 * correct avec des repliements \r\n propres.
 */
class MY_Email extends CI_Email {

    /**
     * Prépare l'encodage Q (RFC 2047) d'un en-tête email.
     *
     * Remplace l'implémentation CI qui génère des \r parasites dans les mots
     * encodés quand le sujet nécessite un repliement (> ~63 octets encodés).
     *
     * @param  string $str   Chaîne à encoder
     * @param  bool   $from  TRUE lors du traitement du nom d'affichage From:
     * @return string
     */
    protected function _prep_q_encoding($str, $from = FALSE)
    {
        $str = str_replace(array("\r", "\n"), array('', ''), $str);

        // Si la chaîne ne contient pas de caractères non-ASCII ni de caractères
        // spéciaux, pas besoin d'encodage (même logique que CI pour les noms From)
        if ( ! preg_match('/[^\x20-\x7E]/', $str) && ! preg_match('/[_=?]/', $str))
        {
            return $str;
        }

        if (function_exists('mb_encode_mimeheader'))
        {
            // mb_encode_mimeheader produit un encodage RFC 2047 correct avec
            // des repliements \r\n propres, sans \r parasite dans les mots encodés
            return mb_encode_mimeheader($str, $this->charset, 'Q', "\r\n");
        }

        // Repli sur l'implémentation parente si mbstring n'est pas disponible
        return parent::_prep_q_encoding($str, $from);
    }
}
