<?php

/**
 * Client HTTP des tests PHPUnit qui interrogent gvv.net (suite mysql).
 *
 * Remplace les fonctions de connexion et de requête que chaque test
 * recopiait. Toutes les requêtes portent le jeton CSRF du test
 * (gvv_test_csrf_headers()), sauf avec l'option 'csrf' => FALSE.
 *
 * Chaque requête renvoie :
 *   status    code HTTP (0 si le serveur n'a pas répondu)
 *   headers   en-têtes de la réponse ($http_response_header)
 *   body      corps de la réponse ('' si aucun)
 *   location  en-tête Location, ou NULL
 *   cookies   cookies posés par la réponse : nom => valeur
 */
class GvvTestHttp
{
    const BASE_URL = 'http://gvv.net/index.php/';

    public static function base_url()
    {
        return self::BASE_URL;
    }

    /**
     * Cookie de session (« ci_session=... ») trouvé dans les en-têtes d'une
     * réponse : le dernier posé, ou NULL.
     */
    public static function session_cookie(array $headers)
    {
        $cookie = null;
        foreach ($headers as $h) {
            if (stripos($h, 'Set-Cookie:') === 0 && stripos($h, 'ci_session=') !== false) {
                $cookie = explode(';', trim(substr($h, strlen('Set-Cookie:'))))[0];
            }
        }
        return $cookie;
    }

    /**
     * Connexion ; renvoie le cookie de session, ou NULL en cas d'échec.
     */
    public static function login($username = 'testadmin', $password = 'password')
    {
        $response = self::post(self::BASE_URL . 'auth/login',
            array('username' => $username, 'password' => $password));
        return self::session_cookie($response['headers']);
    }

    public static function get($url, $cookie = null, array $options = array())
    {
        return self::request('GET', $url, $cookie, $options);
    }

    /** POST d'un formulaire (application/x-www-form-urlencoded). */
    public static function post($url, array $fields = array(), $cookie = null, array $options = array())
    {
        $options['content'] = http_build_query($fields);
        $options['content_type'] = 'application/x-www-form-urlencoded';
        return self::request('POST', $url, $cookie, $options);
    }

    /**
     * POST multipart/form-data.
     * @param array $files nom du champ => array('path', 'type', 'filename' facultatif)
     */
    public static function post_multipart($url, array $fields, array $files, $cookie = null, array $options = array())
    {
        $boundary = '----GvvTest' . uniqid();
        $body = '';
        foreach ($fields as $name => $value) {
            $body .= "--$boundary\r\nContent-Disposition: form-data; name=\"$name\"\r\n\r\n$value\r\n";
        }
        foreach ($files as $name => $file) {
            $filename = isset($file['filename']) ? $file['filename'] : basename($file['path']);
            $body .= "--$boundary\r\nContent-Disposition: form-data; name=\"$name\"; filename=\"$filename\"\r\n"
                . "Content-Type: {$file['type']}\r\n\r\n" . file_get_contents($file['path']) . "\r\n";
        }
        $body .= "--$boundary--\r\n";

        $options['content'] = $body;
        $options['content_type'] = "multipart/form-data; boundary=$boundary";
        return self::request('POST', $url, $cookie, $options);
    }

    /**
     * Requête HTTP.
     * @param string|null $cookie cookies à envoyer (« ci_session=... »)
     * @param array $options content, content_type, headers (lignes
     *        supplémentaires terminées par \r\n), follow (suivre les
     *        redirections, FALSE par défaut), csrf (TRUE par défaut)
     */
    public static function request($method, $url, $cookie = null, array $options = array())
    {
        $csrf = !isset($options['csrf']) || $options['csrf'];
        if ($csrf) {
            $header = gvv_test_csrf_headers($cookie);
        } else {
            $header = ($cookie !== null && $cookie !== '') ? "Cookie: $cookie\r\n" : '';
        }
        if (isset($options['content_type'])) {
            $header .= 'Content-Type: ' . $options['content_type'] . "\r\n";
        }
        if (isset($options['headers'])) {
            $header .= $options['headers'];
        }

        $http = array(
            'method'          => $method,
            'header'          => $header,
            'ignore_errors'   => true,
            'follow_location' => !empty($options['follow']) ? 1 : 0,
            'timeout'         => 20,
        );
        if (isset($options['content'])) {
            $http['content'] = $options['content'];
        }

        $body = @file_get_contents($url, false, stream_context_create(array('http' => $http)));
        $headers = isset($http_response_header) ? $http_response_header : array();

        $status = 0;
        $location = null;
        $cookies = array();
        foreach ($headers as $h) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) {
                $status = (int) $m[1]; // dernier statut si des redirections sont suivies
            } elseif (stripos($h, 'Location:') === 0) {
                $location = trim(substr($h, strlen('Location:')));
            } elseif (stripos($h, 'Set-Cookie:') === 0) {
                $kv = explode('=', explode(';', trim(substr($h, strlen('Set-Cookie:'))))[0], 2);
                if (count($kv) === 2) {
                    $cookies[trim($kv[0])] = trim($kv[1]);
                }
            }
        }

        return array(
            'status'   => $status,
            'headers'  => $headers,
            'body'     => $body === false ? '' : $body,
            'location' => $location,
            'cookies'  => $cookies,
        );
    }
}
