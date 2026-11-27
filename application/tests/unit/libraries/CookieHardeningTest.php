<?php

use PHPUnit\Framework\TestCase;

if (!class_exists('CI_Security')) {
    require_once BASEPATH . 'core/Security.php';
}
if (!class_exists('MY_Security')) {
    require_once APPPATH . 'core/MY_Security.php';
}

/**
 * @feature Authentification et autorisations
 *
 * Renforcement des cookies remis par GVV (MY_Security::harden_cookie_header).
 */
class CookieHardeningTest extends TestCase
{
    private $saved_server;

    protected function setUp(): void
    {
        $this->saved_server = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->saved_server;
    }

    /**
     * Scénario: Le cookie de session est protégé sur un site sécurisé
     *   Étant donné un site consulté en connexion sécurisée
     *   Quand GVV remet le cookie de session
     *   Alors il n'est joint qu'aux navigations vers GVV
     *   Et aucun script de la page ne peut le lire
     *   Et il n'est jamais transmis sur une connexion non chiffrée
     */
    public function testSessionCookieOverHttpsGetsAllAttributes()
    {
        $h = MY_Security::harden_cookie_header('Set-Cookie: ci_session=abc; path=/', TRUE);

        $this->assertSame('Set-Cookie: ci_session=abc; path=/; SameSite=Lax; HttpOnly; Secure', $h);
    }

    /**
     * Scénario: Sur un site non sécurisé, le cookie de session reste utilisable
     *   Étant donné un site consulté sans connexion sécurisée, comme le site de développement
     *   Quand GVV remet le cookie de session
     *   Alors il est protégé contre les autres sites et contre la lecture par un script
     *   Mais il n'est pas réservé aux connexions sécurisées, sans quoi le navigateur le refuserait
     */
    public function testSessionCookieOverHttpHasNoSecureFlag()
    {
        $h = MY_Security::harden_cookie_header('Set-Cookie: ci_session=abc; path=/', FALSE);

        $this->assertStringContainsString('; SameSite=Lax', $h);
        $this->assertStringContainsString('; HttpOnly', $h);
        $this->assertStringNotContainsString('Secure', $h);
    }

    /**
     * Scénario: Le jeton anti-falsification reste lisible par les pages de GVV
     *   Étant donné un site consulté en connexion sécurisée
     *   Quand GVV remet le jeton anti-falsification de la session
     *   Alors les pages de GVV peuvent le lire pour l'ajouter à leurs envois
     *   Et il n'est jamais transmis sur une connexion non chiffrée
     */
    public function testCsrfCookieStaysReadableByScripts()
    {
        $h = MY_Security::harden_cookie_header('Set-Cookie: gvv_csrf_cookie=0123; path=/', TRUE);

        $this->assertStringNotContainsString('HttpOnly', $h);
        $this->assertStringContainsString('; Secure', $h);
        $this->assertStringContainsString('; SameSite=Lax', $h);
    }

    /**
     * Scénario: Une protection déjà indiquée n'est ni répétée ni remplacée
     *   Étant donné un cookie qui précise déjà ses protections
     *   Quand GVV le remet
     *   Alors ses protections sont conservées telles quelles, sans doublon
     */
    public function testExistingAttributesAreKept()
    {
        $original = 'Set-Cookie: autologin=x; path=/; SameSite=Strict; HttpOnly; Secure';

        $this->assertSame($original, MY_Security::harden_cookie_header($original, TRUE));
    }

    /**
     * Scénario: Une connexion sécurisée est reconnue, même derrière un relais
     *   Étant donné un site servi en connexion sécurisée, directement ou par un relais qui le signale
     *   Quand GVV examine la connexion
     *   Alors il la reconnaît comme sécurisée
     *   Mais une connexion non sécurisée n'est pas prise pour une connexion sécurisée
     */
    public function testHttpsDetection()
    {
        unset($_SERVER['HTTPS'], $_SERVER['HTTP_X_FORWARDED_PROTO']);
        $_SERVER['SERVER_PORT'] = 80;
        $this->assertFalse(MY_Security::is_https());

        $_SERVER['HTTPS'] = 'off';
        $this->assertFalse(MY_Security::is_https());

        $_SERVER['HTTPS'] = 'on';
        $this->assertTrue(MY_Security::is_https());

        unset($_SERVER['HTTPS']);
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $this->assertTrue(MY_Security::is_https());

        unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
        $_SERVER['SERVER_PORT'] = 443;
        $this->assertTrue(MY_Security::is_https());
    }
}
