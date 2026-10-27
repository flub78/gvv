<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Newsletter_mail_parser
 *
 * Analyse minimale d'un message RFC 822 brut, suffisante pour les réponses
 * des relecteurs : en-têtes dépliés et décodés, texte (text/plain de
 * préférence, sinon text/html nettoyé), pièces jointes, et retrait de la
 * partie citée du message d'origine.
 *
 * Sans dépendance (pas d'extension mailparse ni imap).
 */
class Newsletter_mail_parser {

    /**
     * @param string $raw  Message complet
     * @return array  subject, from_email, from_name, message_id, date, text, attachments[]
     *                (chaque pièce jointe : name, type, content)
     */
    public static function parse($raw) {
        list($head, $body) = self::split_head_body($raw);
        $headers = self::parse_headers($head);

        $out = array('text' => '', 'html' => '', 'attachments' => array());
        self::walk_part($headers, $body, $out);

        $text = $out['text'] !== '' ? $out['text'] : self::html_to_text($out['html']);
        list($from_email, $from_name) = self::parse_address($headers['from'] ?? '');
        $subject = self::decode_header($headers['subject'] ?? '');
        $date    = trim($headers['date'] ?? '');

        // Sans Message-ID, un identifiant stable tiré du message garde l'anti-doublon
        $message_id = trim($headers['message-id'] ?? '');
        if ($message_id === '') {
            $message_id = '<sans-id-' . sha1($from_email . '|' . $date . '|' . $subject . '|' . $body) . '@gvv>';
        }

        return array(
            'subject'     => $subject,
            'from_email'  => $from_email,
            'from_name'   => $from_name,
            'message_id'  => $message_id,
            'date'        => $date,
            'text'        => trim(self::normalize_newlines($text)),
            'attachments' => $out['attachments'],
        );
    }


    /**
     * Retire la citation du message d'origine : lignes commençant par « > »
     * et tout ce qui suit une ligne d'introduction de citation (« Le … a
     * écrit : », séparateur « Message d'origine », ou bloc d'en-têtes
     * « De : … » suivi d'un autre en-tête « Envoyé : », « Objet : »…).
     */
    public static function strip_quoted($text) {
        $lines = preg_split('/\R/', $text);
        $kept = array();
        foreach ($lines as $i => $line) {
            $t = trim($line);
            if (preg_match('/^(Le .+ a écrit\s*:|On .+ wrote\s*:|-{2,}\s*(Original Message|Message d\'origine|Forwarded message|Message transféré)\s*-{2,})$/iu', $t)) {
                break;
            }
            if (preg_match('/^\*?(De|From)\s*:\*?\s.+/iu', $t) && self::is_header_block(array_slice($lines, $i + 1, 2))) {
                break;
            }
            if (strpos($t, '>') === 0) continue;
            $kept[] = $line;
        }
        return trim(implode("\n", $kept));
    }

    /**
     * La ligne non vide qui suit « De : » est un autre en-tête du message cité.
     */
    private static function is_header_block(array $lines) {
        foreach ($lines as $l) {
            if (trim($l) === '') continue;
            return (bool) preg_match('/^\*?(Envoyé|Sent|Date|À|A|To|Cc|Objet|Subject)\s*:/iu', trim($l));
        }
        return FALSE;
    }

    // ── Interne ───────────────────────────────────────────────────────────

    private static function normalize_newlines($s) {
        return str_replace(array("\r\n", "\r"), "\n", $s);
    }

    private static function split_head_body($raw) {
        $raw = self::normalize_newlines($raw);
        $pos = strpos($raw, "\n\n");
        if ($pos === FALSE) return array($raw, '');
        return array(substr($raw, 0, $pos), substr($raw, $pos + 2));
    }

    /**
     * En-têtes dépliés, clés en minuscules (la première occurrence l'emporte).
     */
    private static function parse_headers($head) {
        $head = preg_replace('/\n[ \t]+/', ' ', $head);
        $headers = array();
        foreach (explode("\n", $head) as $line) {
            $p = strpos($line, ':');
            if ($p === FALSE) continue;
            $key = strtolower(trim(substr($line, 0, $p)));
            if (!isset($headers[$key])) {
                $headers[$key] = trim(substr($line, $p + 1));
            }
        }
        return $headers;
    }

    /**
     * Paramètre d'un en-tête (« name="x" »), y compris sous la forme étendue
     * RFC 2231 (« filename*=UTF-8''%C3%A9t%C3%A9.pdf », éventuellement
     * découpée en « filename*0*=… ; filename*1*=… »).
     */
    private static function header_param($value, $param) {
        $q = preg_quote($param, '/');
        if (preg_match_all('/(?:^|;)\s*' . $q . '\*(\d+)?(\*)?\s*=\s*("([^"]*)"|[^;\s]+)/i', $value, $all, PREG_SET_ORDER)) {
            usort($all, function ($a, $b) { return (int) $a[1] - (int) $b[1]; });
            $out = '';
            $charset = 'UTF-8';
            foreach ($all as $k => $m) {
                $v = isset($m[4]) && $m[4] !== '' ? $m[4] : trim($m[3], '"');
                if ($m[2] === '*') {
                    if ($k === 0 && preg_match("/^([^']*)'[^']*'(.*)$/", $v, $cs)) {
                        $charset = $cs[1] ?: 'UTF-8';
                        $v = $cs[2];
                    }
                    $v = rawurldecode($v);
                }
                $out .= $v;
            }
            if (strtoupper($charset) !== 'UTF-8') {
                $converted = @mb_convert_encoding($out, 'UTF-8', $charset);
                if ($converted !== FALSE) $out = $converted;
            }
            return trim($out);
        }
        if (preg_match('/(?:^|;)\s*' . $q . '\s*=\s*(?:"([^"]*)"|([^;\s]+))/i', $value, $m)) {
            return trim(isset($m[2]) && $m[2] !== '' ? $m[2] : $m[1]);
        }
        return '';
    }

    private static function walk_part($headers, $body, &$out) {
        $ctype = strtolower($headers['content-type'] ?? 'text/plain');
        $type  = trim(strtok($ctype, ';'));
        $disp  = strtolower($headers['content-disposition'] ?? '');

        if (strpos($type, 'multipart/') === 0) {
            $boundary = self::header_param($headers['content-type'], 'boundary');
            if ($boundary === '') return;
            $chunks = explode('--' . $boundary, $body);
            array_shift($chunks); // préambule
            foreach ($chunks as $chunk) {
                if (strpos($chunk, '--') === 0) break; // fin du multipart
                list($h, $b) = self::split_head_body(ltrim($chunk, "\n"));
                self::walk_part(self::parse_headers($h), $b, $out);
            }
            return;
        }

        $content = self::decode_body($body, strtolower(trim($headers['content-transfer-encoding'] ?? '')));
        $name = self::header_param($headers['content-disposition'] ?? '', 'filename')
             ?: self::header_param($headers['content-type'] ?? '', 'name');

        if ($name !== '' || strpos($disp, 'attachment') === 0) {
            $out['attachments'][] = array(
                'name'    => self::decode_header($name ?: 'piece_jointe'),
                'type'    => $type,
                'content' => $content,
            );
            return;
        }

        $charset = self::header_param($headers['content-type'] ?? '', 'charset') ?: 'UTF-8';
        if (strtoupper($charset) !== 'UTF-8') {
            $converted = @mb_convert_encoding($content, 'UTF-8', $charset);
            if ($converted !== FALSE) $content = $converted;
        }
        if ($type === 'text/plain') {
            // Plusieurs parties texte (texte coupé par une image insérée) : mises bout à bout
            $out['text'] = trim($out['text'] . "\n\n" . trim(self::normalize_newlines($content)));
        } elseif ($type === 'text/html' && $out['html'] === '') {
            $out['html'] = $content;
        }
    }

    private static function decode_body($body, $encoding) {
        if ($encoding === 'base64') {
            $d = base64_decode(preg_replace('/\s+/', '', $body), TRUE);
            return $d === FALSE ? $body : $d;
        }
        if ($encoding === 'quoted-printable') {
            return quoted_printable_decode($body);
        }
        return $body;
    }

    private static function html_to_text($html) {
        if ($html === '') return '';
        // Contenu non affiché (feuilles de style des clients mail, scripts)
        $html = preg_replace('#<(head|style|script)\b.*?</\1>#is', '', $html);
        $html = preg_replace('#<(br|/p|/div|/li|/tr|/h\d)\b[^>]*>#i', "\n", $html);
        $html = preg_replace('#<blockquote\b.*?</blockquote>#is', '', $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return preg_replace("/\n{3,}/", "\n\n", $text);
    }

    public static function decode_header($str) {
        if (strpos($str, '=?') === FALSE) return $str;
        if (function_exists('iconv_mime_decode')) {
            $d = @iconv_mime_decode($str, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
            if ($d !== FALSE) return $d;
        }
        return mb_decode_mimeheader($str);
    }

    /**
     * @return array [email en minuscules, nom affiché]
     */
    private static function parse_address($value) {
        $value = self::decode_header($value);
        if (preg_match('/^\s*"?([^"<]*?)"?\s*<([^>]+)>/', $value, $m)) {
            return array(strtolower(trim($m[2])), trim($m[1]));
        }
        return array(strtolower(trim($value)), '');
    }
}
