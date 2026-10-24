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

        return array(
            'subject'     => self::decode_header($headers['subject'] ?? ''),
            'from_email'  => $from_email,
            'from_name'   => $from_name,
            'message_id'  => trim($headers['message-id'] ?? ''),
            'date'        => trim($headers['date'] ?? ''),
            'text'        => trim(self::normalize_newlines($text)),
            'attachments' => $out['attachments'],
        );
    }

    /**
     * Retire la citation du message d'origine : lignes commençant par « > »
     * et tout ce qui suit une ligne d'introduction de citation.
     */
    public static function strip_quoted($text) {
        $kept = array();
        foreach (preg_split('/\R/', $text) as $line) {
            $t = trim($line);
            if (preg_match('/^(Le .+ a écrit\s*:|On .+ wrote\s*:|-{2,}\s*(Original Message|Message d\'origine)\s*-{2,}|De\s*:\s.+|From\s*:\s.+)$/iu', $t)) {
                break;
            }
            if (strpos($t, '>') === 0) continue;
            $kept[] = $line;
        }
        return trim(implode("\n", $kept));
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

    private static function header_param($value, $param) {
        if (preg_match('/' . preg_quote($param, '/') . '\*?\s*=\s*"?([^";]+)"?/i', $value, $m)) {
            return trim($m[1]);
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
        if ($type === 'text/plain' && $out['text'] === '') {
            $out['text'] = $content;
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
