<?php

use PHPUnit\Framework\TestCase;

/**
 * HTTP tests for Gvv_Controller::create() GET prefill (Lot 12 — export d'une
 * réponse de formulaire vers un formulaire de création GVV).
 *
 * Like FormsAdminSubmissionRotateTest, this can only be exercised over real
 * HTTP (session/role-gated controller, no curl in this environment — PHP's
 * http:// stream wrapper with a manually captured session cookie).
 */
class GvvControllerCreatePrefillTest extends TestCase
{
    private function base_url()
    {
        return 'http://gvv.net/index.php/';
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
        $body = http_build_query(array('username' => 'testadmin', 'password' => 'password'));
        $context = stream_context_create(array(
            'http' => array(
                'method'          => 'POST',
                'header'          => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content'         => $body,
                'ignore_errors'   => true,
                'follow_location' => 0,
                'timeout'         => 20,
            ),
        ));
        @file_get_contents($this->base_url() . 'auth/login', false, $context);
        $headers = isset($http_response_header) ? $http_response_header : array();

        return $this->extract_session_cookie($headers);
    }

    private function http_get($url, $cookie = null)
    {
        $header = "Cookie: " . ($cookie ?: '') . "\r\n";
        $context = stream_context_create(array(
            'http' => array(
                'method'          => 'GET',
                'header'          => $header,
                'ignore_errors'   => true,
                'follow_location' => 0,
                'timeout'         => 20,
            ),
        ));
        $body = @file_get_contents($url, false, $context);
        return $body === false ? '' : $body;
    }

    public function testCreateIsPrefilledFromKnownColumnQueryParam()
    {
        $cookie = $this->login_as_admin();
        $this->assertNotNull($cookie, 'La connexion admin doit renvoyer un cookie de session.');

        $value = 'SmokeExport' . rand(1000, 9999);
        $body = $this->http_get($this->base_url() . 'membre/create?mnom=' . urlencode($value), $cookie);

        $this->assertStringContainsString(
            'name="mnom" value="' . $value . '"',
            $body,
            'Un parametre GET correspondant a une colonne connue (mnom) doit prereplir le champ.'
        );
    }

    public function testCreateWithoutQueryParamsIsUnchanged()
    {
        $cookie = $this->login_as_admin();
        $body = $this->http_get($this->base_url() . 'membre/create', $cookie);

        $this->assertStringContainsString('name="mnom" value=""', $body);
    }

    public function testCreateIgnoresUnknownQueryParam()
    {
        $cookie = $this->login_as_admin();
        $body = $this->http_get($this->base_url() . 'membre/create?not_a_column=hacked', $cookie);

        $this->assertStringNotContainsString('hacked', $body);
    }
}
