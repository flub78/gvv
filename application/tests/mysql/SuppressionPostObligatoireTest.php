<?php

use PHPUnit\Framework\TestCase;

/**
 * @feature Authentification et autorisations
 *
 * Les suppressions des pages de gestion n'acceptent que POST (jeton CSRF) :
 * un lien piégé ou une image sur un autre site ne peut plus rien supprimer.
 *
 * Requêtes HTTP réelles sur gvv.net, comme ProduitsTarifsInlineTest. Le
 * terrain de test est créé et supprimé par le test.
 */
class SuppressionPostObligatoireTest extends TestCase
{
    const OACI = '00PHPU';

    private $CI;
    private $cookie;

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        $this->CI->db->where('oaci', self::OACI)->delete('terrains');
        $this->CI->db->insert('terrains', array(
            'oaci' => self::OACI,
            'nom' => 'Terrain test PHPUnit',
            'comment' => 'Créé par SuppressionPostObligatoireTest',
        ));
        $this->cookie = $this->login_as_admin();
        $this->assertNotNull($this->cookie, 'La connexion admin doit renvoyer un cookie de session.');
    }

    protected function tearDown(): void
    {
        $this->CI->db->where('oaci', self::OACI)->delete('terrains');
    }

    private function base_url()
    {
        return 'http://gvv.net/index.php/';
    }

    private function terrain_existe()
    {
        return $this->CI->db->where('oaci', self::OACI)->count_all_results('terrains') > 0;
    }

    private function extract_session_cookie(array $headers)
    {
        $cookie = null;
        foreach ($headers as $h) {
            if (stripos($h, 'Set-Cookie:') === 0 && stripos($h, 'ci_session=') !== false) {
                $pair = trim(substr($h, strlen('Set-Cookie:')));
                $cookie = explode(';', $pair)[0];
            }
        }
        return $cookie;
    }

    private function login_as_admin()
    {
        $context = stream_context_create(array(
            'http' => array(
                'method'          => 'POST',
                'header'          => "Content-Type: application/x-www-form-urlencoded\r\n" . gvv_test_csrf_headers(),
                'content'         => http_build_query(array('username' => 'testadmin', 'password' => 'password')),
                'ignore_errors'   => true,
                'follow_location' => 0,
                'timeout'         => 20,
            ),
        ));
        @file_get_contents($this->base_url() . 'auth/login', false, $context);
        return $this->extract_session_cookie(isset($http_response_header) ? $http_response_header : array());
    }

    /**
     * @return array [code HTTP, corps]
     */
    private function request($method, $url, $header)
    {
        $context = stream_context_create(array(
            'http' => array(
                'method'          => $method,
                'header'          => $header,
                'content'         => '',
                'ignore_errors'   => true,
                'follow_location' => 0,
                'timeout'         => 20,
            ),
        ));
        $body = @file_get_contents($url, false, $context);
        $status = 0;
        if (isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0], $m)) {
            $status = (int) $m[1];
        }
        return array($status, $body === false ? '' : $body);
    }

    /**
     * Scénario: Une suppression ouverte par un simple lien est refusée
     *   Étant donné un administrateur connecté
     *   Et un terrain enregistré
     *   Quand l'adresse de suppression du terrain est ouverte directement
     *   Alors la demande est refusée avec un message qui l'explique
     *   Et le terrain est toujours enregistré
     */
    public function testDeleteByGetIsRefusedAndKeepsData()
    {
        list($status, $body) = $this->request('GET', $this->base_url() . 'terrains/delete/' . self::OACI,
            "Cookie: " . $this->cookie . "\r\n");

        $this->assertSame(405, $status);
        $this->assertStringContainsString("Aucune donnée n'a été modifiée", $body);
        $this->assertTrue($this->terrain_existe());
    }

    /**
     * Scénario: Une suppression envoyée par l'application est effectuée
     *   Étant donné un administrateur connecté
     *   Et un terrain enregistré
     *   Quand il demande la suppression du terrain depuis GVV
     *   Alors le terrain est supprimé
     */
    public function testDeleteByPostWithTokenDeletes()
    {
        list($status) = $this->request('POST', $this->base_url() . 'terrains/delete/' . self::OACI,
            gvv_test_csrf_headers($this->cookie) . "Content-Type: application/x-www-form-urlencoded\r\n");

        $this->assertNotSame(405, $status);
        $this->assertNotSame(403, $status);
        $this->assertFalse($this->terrain_existe());
    }
}
