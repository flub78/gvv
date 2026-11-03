<?php

use PHPUnit\Framework\TestCase;

/**
 * @feature Configuration
 *
 * Écriture ciblée d'un paramètre dans un fichier de configuration.
 * Tous les tests travaillent sur des fichiers temporaires, jamais sur application/config.
 */
class ConfigFileWriterTest extends TestCase
{
    private $writer;
    private $tmp_files = array();

    protected function setUp(): void
    {
        require_once APPPATH . 'libraries/Config_file_writer.php';
        $this->writer = new Config_file_writer();
    }

    protected function tearDown(): void
    {
        foreach ($this->tmp_files as $path) {
            if (is_file($path)) {
                @chmod($path, 0644);
                @unlink($path);
            }
        }
        $this->tmp_files = array();
    }

    private function tmp_config($content)
    {
        $path = tempnam(sys_get_temp_dir(), 'gvv_cfg_');
        file_put_contents($path, $content);
        $this->tmp_files[] = $path;
        return $path;
    }

    private function sample()
    {
        return "<?php if (!defined('BASEPATH')) exit('No direct script access allowed');\n"
            . "// \$config['nom_club'] = 'commentée';\n"
            . "\$config['nom_club'] = \"Club d'essai\";\n"
            . "\$config['email_club'] = 'club@example.org';\n"
            . "\$config['gestion_vd'] = false;\n"
            . "\$config['medical_id'] = 26;\n"
            . "/* End of file */\n";
    }

    /**
     * Scénario: Modifier un paramètre ne touche pas aux autres
     *   Étant donné un fichier de configuration du club avec plusieurs paramètres et un commentaire
     *   Quand l'administrateur change l'adresse e-mail du club
     *   Alors la nouvelle adresse est enregistrée
     *   Et les autres paramètres et le commentaire restent identiques
     */
    public function testWrite_ReplacesOnlyTargetKey()
    {
        $file = $this->tmp_config($this->sample());
        $this->assertTrue($this->writer->write($file, 'email_club', 'nouveau@example.org'));

        $this->assertSame('nouveau@example.org', $this->writer->read($file, 'email_club'));
        $this->assertSame("Club d'essai", $this->writer->read($file, 'nom_club'));
        $this->assertFalse($this->writer->read($file, 'gestion_vd'));
        $this->assertSame(26, $this->writer->read($file, 'medical_id'));
        $this->assertStringContainsString("// \$config['nom_club'] = 'commentée';", file_get_contents($file));
    }

    /**
     * Scénario: Les caractères spéciaux d'une valeur sont conservés tels quels
     *   Étant donné un fichier de configuration du club
     *   Quand l'administrateur saisit un nom contenant apostrophe, guillemets, barre oblique inverse et symbole dollar
     *   Alors le nom relu est exactement celui saisi
     *   Et le fichier reste valide
     */
    public function testWrite_PreservesSpecialCharacters()
    {
        $file = $this->tmp_config($this->sample());
        $value = "L'Aéro \"club\" \\ \$1 \${x} \\'";
        $this->assertTrue($this->writer->write($file, 'nom_club', $value));
        $this->assertSame($value, $this->writer->read($file, 'nom_club'));

        $config = array();
        define_basepath_if_needed();
        include $file;
        $this->assertSame($value, $config['nom_club']);
    }

    /**
     * Scénario: Un paramètre absent du fichier y est ajouté
     *   Étant donné un fichier de configuration sans contact RGPD
     *   Quand l'administrateur renseigne le contact RGPD
     *   Alors il est ajouté au fichier sans modifier les paramètres existants
     */
    public function testWrite_AppendsMissingKey()
    {
        $file = $this->tmp_config($this->sample());
        $this->assertTrue($this->writer->write($file, 'contact_rgpd', 'Le président'));
        $this->assertSame('Le président', $this->writer->read($file, 'contact_rgpd'));
        $this->assertSame('club@example.org', $this->writer->read($file, 'email_club'));
    }

    /**
     * Scénario: Un paramètre ajouté reste dans le code du fichier même s'il se termine par une balise de fin
     *   Étant donné un fichier de configuration terminé par une balise de fin de code
     *   Quand un nouveau paramètre est ajouté
     *   Alors il est placé avant la balise et reste lisible
     */
    public function testWrite_AppendsBeforeClosingTag()
    {
        $file = $this->tmp_config("<?php\n\$config['a'] = 'x';\n?>\n");
        $this->assertTrue($this->writer->write($file, 'b', 'y'));
        $content = file_get_contents($file);
        $this->assertLessThan(strpos($content, '?>'), strpos($content, "\$config['b']"));
        $this->assertSame('y', $this->writer->read($file, 'b'));
    }

    /**
     * Scénario: Activer un module et changer un nombre
     *   Étant donné un fichier de configuration avec un module désactivé et un nombre
     *   Quand l'administrateur active le module et change le nombre
     *   Alors le module est relu comme activé et le nombre comme nombre
     */
    public function testWrite_BooleansAndIntegers()
    {
        $file = $this->tmp_config($this->sample());
        $this->assertTrue($this->writer->write($file, 'gestion_vd', TRUE));
        $this->assertTrue($this->writer->write($file, 'medical_id', 42));
        $this->assertTrue($this->writer->read($file, 'gestion_vd'));
        $this->assertSame(42, $this->writer->read($file, 'medical_id'));
    }

    /**
     * Scénario: Un fichier non modifiable est signalé et non altéré
     *   Étant donné un fichier de configuration que le serveur ne peut pas modifier
     *   Quand l'administrateur tente de changer un paramètre
     *   Alors l'échec est signalé avec son motif
     *   Et le fichier est inchangé
     */
    public function testWrite_ReportsReadOnlyFile()
    {
        if (function_exists('posix_getuid') && posix_getuid() === 0) {
            $this->markTestSkipped('root peut écrire dans un fichier en lecture seule');
        }
        $file = $this->tmp_config($this->sample());
        $before = file_get_contents($file);
        chmod($file, 0444);
        $this->assertFalse($this->writer->write($file, 'email_club', 'x@example.org'));
        $this->assertSame('not_writable', $this->writer->error);
        $this->assertSame($before, file_get_contents($file));
    }

    /**
     * Scénario: Un fichier absent est signalé
     *   Étant donné un fichier de configuration qui n'existe pas sur le serveur
     *   Quand l'administrateur tente de changer un paramètre
     *   Alors l'échec est signalé avec son motif
     *   Et la lecture rend la valeur par défaut
     */
    public function testMissingFile()
    {
        $file = sys_get_temp_dir() . '/gvv_cfg_absent_' . uniqid() . '.php';
        $this->assertFalse($this->writer->write($file, 'a', 'b'));
        $this->assertSame('missing_file', $this->writer->error);
        $this->assertSame('defaut', $this->writer->read($file, 'a', 'defaut'));
    }
}

if (!function_exists('define_basepath_if_needed')) {
    function define_basepath_if_needed()
    {
        if (!defined('BASEPATH')) {
            define('BASEPATH', __DIR__);
        }
    }
}
