<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests MY_Migration::version() : diagnostic détaillé en cas d'échec de
 * migration (SQL silencieux ou exception explicite), cf. étape 3 du plan
 * doc/plans/refactoring_produits_tarifs_plan.md.
 *
 * Les fixtures sont écrites dans un dossier temporaire (migration_path
 * surchargé) et les méthodes de suivi de version sont surchargées pour ne
 * jamais toucher la vraie table `migrations` de la base de test.
 */
class MyMigrationErrorHandlingTest extends TestCase
{
    private $fixturesPath;

    protected function setUp(): void
    {
        if (! class_exists('CI_Migration')) {
            require_once BASEPATH . 'libraries/Migration.php';
        }
        if (! class_exists('MY_Migration')) {
            require_once APPPATH . 'libraries/MY_Migration.php';
        }

        $this->fixturesPath = sys_get_temp_dir() . '/my_migration_test_' . uniqid() . '/';
        mkdir($this->fixturesPath);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->fixturesPath . '*.php') as $file) {
            unlink($file);
        }
        @rmdir($this->fixturesPath);
    }

    private function writeFixture($filename, $classSuffix, $body)
    {
        $code = "<?php\nclass Migration_$classSuffix extends CI_Migration {\n$body\n}\n";
        file_put_contents($this->fixturesPath . $filename, $code);
    }

    /**
     * Instance isolée de la vraie table `migrations` : la version courante
     * est fixée à 0 et l'enregistrement de la nouvelle version est un no-op.
     */
    private function newIsolatedMigration()
    {
        $config = array(
            'migration_enabled' => true,
            'migration_path' => $this->fixturesPath,
        );

        return new class($config) extends MY_Migration {
            protected function _get_version() {
                return 0;
            }
            protected function _update_version($migrations) {
                return true;
            }
        };
    }

    public function testSilentSqlErrorThrowsDetailedException()
    {
        // db_debug=FALSE en production : query() retourne FALSE sans lever
        // d'erreur PHP. simple_query() reproduit ce comportement.
        $this->writeFixture('001_test_failing.php', 'Test_failing', '
            public function up() {
                $this->db->simple_query("SELECT * FROM table_qui_nexiste_pas_xyz");
                return true;
            }
            public function down() {
                return true;
            }
        ');

        $migration = $this->newIsolatedMigration();

        try {
            $migration->version(1);
            $this->fail('Une exception aurait dû être levée pour une erreur SQL silencieuse.');
        } catch (Exception $e) {
            $this->assertStringContainsString('001_test_failing.php', $e->getMessage());
            $this->assertStringContainsString('up()', $e->getMessage());
            $this->assertStringContainsString('niveau visé 1', $e->getMessage());
            $this->assertStringContainsString('table_qui_nexiste_pas_xyz', $e->getMessage());
        }
    }

    public function testExplicitExceptionIsWrappedWithContext()
    {
        $this->writeFixture('001_test_throwing.php', 'Test_throwing', '
            public function up() {
                throw new Exception("erreur explicite de test");
            }
            public function down() {
                return true;
            }
        ');

        $migration = $this->newIsolatedMigration();

        try {
            $migration->version(1);
            $this->fail('Une exception aurait dû être levée et propagée.');
        } catch (Exception $e) {
            $this->assertStringContainsString('001_test_throwing.php', $e->getMessage());
            $this->assertStringContainsString('up()', $e->getMessage());
            $this->assertStringContainsString('erreur explicite de test', $e->getMessage());
        }
    }

    public function testSuccessfulMigrationDoesNotThrow()
    {
        $this->writeFixture('001_test_ok.php', 'Test_ok', '
            public function up() {
                return true;
            }
            public function down() {
                return true;
            }
        ');

        $migration = $this->newIsolatedMigration();

        $result = $migration->version(1);
        $this->assertSame(1, $result);
    }
}
