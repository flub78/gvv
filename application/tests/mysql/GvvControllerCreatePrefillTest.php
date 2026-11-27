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

    private function http_get($url, $cookie = null)
    {
        return GvvTestHttp::get($url, $cookie)['body'];
    }

    public function testCreateIsPrefilledFromKnownColumnQueryParam()
    {
        $cookie = GvvTestHttp::login();
        $this->assertNotNull($cookie, 'La connexion admin doit renvoyer un cookie de session.');

        $value = 'SmokeExport' . rand(1000, 9999);
        $body = $this->http_get(GvvTestHttp::base_url() . 'membre/create?mnom=' . urlencode($value), $cookie);

        $this->assertStringContainsString(
            'name="mnom" value="' . $value . '"',
            $body,
            'Un parametre GET correspondant a une colonne connue (mnom) doit prereplir le champ.'
        );
    }

    public function testCreateWithoutQueryParamsIsUnchanged()
    {
        $cookie = GvvTestHttp::login();
        $body = $this->http_get(GvvTestHttp::base_url() . 'membre/create', $cookie);

        $this->assertStringContainsString('name="mnom" value=""', $body);
    }

    public function testCreateIgnoresUnknownQueryParam()
    {
        $cookie = GvvTestHttp::login();
        $body = $this->http_get(GvvTestHttp::base_url() . 'membre/create?not_a_column=hacked', $cookie);

        $this->assertStringNotContainsString('hacked', $body);
    }
}
