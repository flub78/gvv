<?php

use PHPUnit\Framework\TestCase;

/**
 * Canary test for the CI-core-duplication risk in application/libraries/MY_Migration.php
 * (doc/reviews/pr84_produits_tarifs_refactoring.md, finding #3).
 *
 * MY_Migration::__construct() and MY_Migration::version() are near-verbatim
 * copies of system/libraries/Migration.php (CI_Migration), because
 * CI_Migration's own constructor refuses to run its initialization logic for
 * any subclass — even via an explicit parent::__construct() call — via the
 * guard `if (get_parent_class($this) !== FALSE) return;`. system/ must not
 * be modified (project rule), so the duplication is the only option, but it
 * also means a future CodeIgniter core upgrade that changes that guard, the
 * protected properties MY_Migration reads/writes directly, or version()'s
 * signature won't propagate here and won't fail loudly on its own. This
 * test asserts the specific assumptions MY_Migration.php relies on, so an
 * upgrade that breaks them fails here instead of silently drifting.
 */
class MyMigrationCoreCompatibilityTest extends TestCase
{
    protected function setUp(): void
    {
        if (! class_exists('CI_Migration')) {
            require_once BASEPATH . 'libraries/Migration.php';
        }
    }

    public function testCoreConstructorStillGuardsAgainstSubclassReinit()
    {
        $source = file_get_contents(BASEPATH . 'libraries/Migration.php');
        $this->assertStringContainsString(
            'get_parent_class($this) !== FALSE',
            $source,
            'CI_Migration::__construct() no longer contains the subclass-init guard that ' .
            'MY_Migration.php\'s docblock relies on to justify duplicating the constructor ' .
            'instead of calling parent::__construct(). Re-check application/libraries/MY_Migration.php ' .
            'against the new system/libraries/Migration.php.'
        );
    }

    public function testCoreProtectedPropertiesStillExist()
    {
        $reflection = new ReflectionClass('CI_Migration');
        foreach (array('_migration_enabled', '_migration_path', '_error_string') as $prop) {
            $this->assertTrue(
                $reflection->hasProperty($prop),
                "CI_Migration no longer declares \$$prop — MY_Migration::__construct()/version() " .
                'read/write it directly and must be updated to match.'
            );
        }
    }

    public function testCoreVersionMethodSignatureUnchanged()
    {
        $reflection = new ReflectionClass('CI_Migration');
        $this->assertTrue($reflection->hasMethod('version'), 'CI_Migration::version() no longer exists.');

        $method = $reflection->getMethod('version');
        $this->assertSame(
            1,
            $method->getNumberOfParameters(),
            "CI_Migration::version()'s parameter count changed — MY_Migration::version() is a " .
            'near-verbatim copy of its control flow and must be updated to match.'
        );
    }
}
