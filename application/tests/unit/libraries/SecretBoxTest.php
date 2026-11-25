<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'libraries/Secret_box.php';

/**
 * @feature Synchronisation agenda Google
 *
 * Chiffrement des autorisations Google conservées par GVV. Chaque test
 * travaille sur un fichier de clé temporaire : la vraie clé de l'installation
 * n'est jamais lue ni modifiée.
 */
class SecretBoxTest extends TestCase
{
    private $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/gvv_secret_box_' . getmypid() . '_' . uniqid();
        mkdir($this->dir, 0700);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') as $file) {
            unlink($file);
        }
        rmdir($this->dir);
    }

    private function box($name = 'cle.php')
    {
        return new Secret_box(array('key_file' => $this->dir . '/' . $name));
    }

    /**
     * Scénario: Une autorisation chiffrée se relit à l'identique
     *   Étant donné une installation de GVV
     *   Quand une autorisation Google est chiffrée puis relue
     *   Alors on retrouve exactement l'autorisation d'origine
     *   Et la forme enregistrée ne la laisse pas apparaître
     */
    public function testEncryptionIsReversible()
    {
        $box = $this->box();
        $secret = 'jeton-de-renouvellement-factice_é';
        $blob = $box->encrypt($secret);

        $this->assertStringNotContainsString($secret, $blob);
        $this->assertSame($secret, $box->decrypt($blob));
        $this->assertNotSame($blob, $box->encrypt($secret), 'deux chiffrements du même texte diffèrent');
    }

    /**
     * Scénario: La clé de l'installation est créée une seule fois
     *   Étant donné une installation sans clé de chiffrement
     *   Quand une première autorisation est chiffrée
     *   Alors une clé est créée, lisible par le seul serveur et son groupe
     *   Et les chiffrements suivants réutilisent la même clé
     */
    public function testKeyIsCreatedOnceWithRestrictedRights()
    {
        $file = $this->dir . '/cle.php';
        $this->assertFalse(is_file($file));

        $blob = $this->box()->encrypt('jeton');
        $this->assertFileExists($file);
        $this->assertSame(0640, fileperms($file) & 0777);
        $content = file_get_contents($file);

        $this->assertSame('jeton', $this->box()->decrypt($blob), 'une nouvelle instance relit la même clé');
        $this->box()->encrypt('autre');
        $this->assertSame($content, file_get_contents($file), 'la clé n\'est pas régénérée');
    }

    /**
     * Scénario: Une autorisation chiffrée par une autre installation est refusée
     *   Étant donné une autorisation chiffrée avec la clé d'une autre installation
     *   Quand GVV tente de la relire
     *   Alors la lecture est refusée
     */
    public function testDecryptionWithAnotherKeyIsRefused()
    {
        $blob = $this->box('a.php')->encrypt('jeton');

        $this->assertFalse($this->box('b.php')->decrypt($blob));
        $this->assertFalse($this->box('a.php')->decrypt('pas du base64 !'));
        $this->assertFalse($this->box('a.php')->decrypt(base64_encode('court')));
    }
}
