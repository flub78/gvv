# Exécution des tests PHPUnit

Il faut que la base soit définie dans application/config/database.php

GRANT ALL PRIVILEGES ON `gvv\_test` . * TO 'gvv_user'@'localhost' WITH GRANT OPTION ;

Importer install/test_database_17.sql

Pour les controllers, toujours le problème de connection. Il faut:
export TEST=1 (mais cela ne fonctionne que pour les tests individuels)

Donc on sait tester
    * les contrôleurs
    * les librairies
    * les helpers
    * les modèles

## Septembre 2025

Tentative de relancer les tests phpunit

source setenv.sh 
php --version
PHP 7.4.33 (cli) (built: Jul  3 2025 16:41:49) ( NTS )
Copyright (c) The PHP Group
Zend Engine v3.4.0, Copyright (c) Zend Technologies
    with Zend OPcache v7.4.33, Copyright (c), by Zend Technologies
    with Xdebug v3.1.6, Copyright (c) 2002-2022, by Derick Rethans

php /usr/local/bin/phpunit
Xdebug: [Step Debug] Could not connect to debugging client. Tried: 127.0.0.1:9003 (through xdebug.client_host/xdebug.client_port) :-(
PHPUnit 8.5.44 by Sebastian Bergmann and contributors.

Usage:
  phpunit [options] UnitTest [UnitTest.php]
  phpunit [options] <directory>

Code Coverage Options:
  --coverage-clover <file>    Generate code coverage report in Clover XML format
  --coverage-crap4j <file>    Generate code coverage report in Crap4J XML format
  --coverage-html <dir>       Generate code coverage report in HTML format
  --coverage-php <file>       Export PHP_CodeCoverage object to file
  --coverage-text=<file>      Generate code coverage report in text format [default: standard output]
  --coverage-xml <dir>        Generate code coverage report in PHPUnit XML format
  --whitelist <dir>           Whitelist <dir> for code coverage analysis
  --disable-coverage-ignore   Disable annotations for ignoring code coverage
  --no-coverage               Ignore code coverage configuration
  --dump-xdebug-filter <file> Generate script to set Xdebug code coverage filter

Logging Options:
  --log-junit <file>          Log test execution in JUnit XML format to file
  --log-teamcity <file>       Log test execution in TeamCity format to file
  --testdox-html <file>       Write agile documentation in HTML format to file
  --testdox-text <file>       Write agile documentation in Text format to file
  --testdox-xml <file>        Write agile documentation in XML format to file
  --reverse-list              Print defects in reverse order
  --no-logging                Ignore logging configuration

Test Selection Options:
  --filter <pattern>          Filter which tests to run
  --testsuite <name>          Filter which testsuite to run
  --group <name>              Only runs tests from the specified group(s)
  --exclude-group <name>      Exclude tests from the specified group(s)
  --list-groups               List available test groups
  --list-suites               List available test suites
  --list-tests                List available tests
  --list-tests-xml <file>     List available tests in XML format
  --test-suffix <suffixes>    Only search for test in files with specified suffix(es). Default: Test.php,.phpt

Test Execution Options:
  --dont-report-useless-tests Do not report tests that do not test anything
  --strict-coverage           Be strict about @covers annotation usage
  --strict-global-state       Be strict about changes to global state
  --disallow-test-output      Be strict about output during tests
  --disallow-resource-usage   Be strict about resource usage during small tests
  --enforce-time-limit        Enforce time limit based on test size
  --default-time-limit=<sec>  Timeout in seconds for tests without @small, @medium or @large
  --disallow-todo-tests       Disallow @todo-annotated tests

  --process-isolation         Run each test in a separate PHP process
  --globals-backup            Backup and restore $GLOBALS for each test
  --static-backup             Backup and restore static attributes for each test

  --colors=<flag>             Use colors in output ("never", "auto" or "always")
  --columns <n>               Number of columns to use for progress output
  --columns max               Use maximum number of columns for progress output
  --stderr                    Write to STDERR instead of STDOUT
  --stop-on-defect            Stop execution upon first not-passed test
  --stop-on-error             Stop execution upon first error
  --stop-on-failure           Stop execution upon first error or failure
  --stop-on-warning           Stop execution upon first warning
  --stop-on-risky             Stop execution upon first risky test
  --stop-on-skipped           Stop execution upon first skipped test
  --stop-on-incomplete        Stop execution upon first incomplete test
  --fail-on-warning           Treat tests with warnings as failures
  --fail-on-risky             Treat risky tests as failures
  -v|--verbose                Output more verbose information
  --debug                     Display debugging information

  --loader <loader>           TestSuiteLoader implementation to use
  --repeat <times>            Runs the test(s) repeatedly
  --teamcity                  Report test execution progress in TeamCity format
  --testdox                   Report test execution progress in TestDox format
  --testdox-group             Only include tests from the specified group(s)
  --testdox-exclude-group     Exclude tests from the specified group(s)
  --no-interaction            Disable TestDox progress animation
  --printer <printer>         TestListener implementation to use

  --order-by=<order>          Run tests in order: default|defects|duration|no-depends|random|reverse|size
  --random-order-seed=<N>     Use a specific random seed <N> for random order
  --cache-result              Write test results to cache file
  --do-not-cache-result       Do not write test results to cache file

Configuration Options:
  --prepend <file>            A PHP script that is included as early as possible
  --bootstrap <file>          A PHP script that is included before the tests run
  -c|--configuration <file>   Read configuration from XML file
  --no-configuration          Ignore default configuration file (phpunit.xml)
  --no-extensions             Do not load PHPUnit extensions
  --include-path <path(s)>    Prepend PHP's include_path with given path(s)
  -d <key[=value]>            Sets a php.ini value
  --generate-configuration    Generate configuration file with suggested settings
  --cache-result-file=<file>  Specify result cache path and filename

PHAR Options:
  --manifest                  Print Software Bill of Materials (SBOM) in plain-text format
  --sbom                      Print Software Bill of Materials (SBOM) in CycloneDX XML format
  --composer-lock             Print composer.lock file used to build the PHAR

Miscellaneous Options:
  -h|--help                   Prints this usage information
  --version                   Prints the version and exits
  --atleast-version <min>     Checks that version is greater than min and exits
  --check-version             Checks whether PHPUnit is the latest version and exits


Donc php et phpunit sont installés et compatibles.

https://github.com/kenjis/ci-phpunit-test

Pour CodeIgniter 2 + PHP 7.4, vos options :

fmalk/codeigniter-phpunit - Spécifiquement conçu pour CodeIgniter 2 GitHub - fmalk/codeigniter-phpunit: Hack to make CodeIgniter work with PHPUnit.
bashcomposer require --dev fmalk/codeigniter-phpunit

Version legacy de ci-phpunit-test - Cherchez les versions 0.12.x à 0.16.x (avant la migration vers CI3)

git clone -b 2.x https://github.com/fmalk/codeigniter-phpunit.git

# Tentative d'activation de fmalk/codeigniter-phpunit

1216  rm -r ../gvv/application/tests
 1217  ls application/
 1218  cp -r application/tests/ ../gvv/application/
 1219  cp -r system/core/ ../gvv/system/

 Pas de regression évidente après installation...

========================================================================
 php /usr/local/bin/phpunit application/tests/helpers/HelperTest.php 

While trying to execute phpunit tests with fmalk/codeigniter-phpunit 
I get no output from this execution. How can I debug it?

php /usr/local/bin/phpunit application/tests/helpers/HelperTest.php 
Xdebug: [Step Debug] Could not connect to debugging client. Tried: 127.0.0.1:9003 (through xdebug.client_host/xdebug.client_port) :-(

The phpunit.xml is this one:

<?xml version="1.0" encoding="UTF-8" ?>
<phpunit bootstrap="application/tests/bootstrap.php">
    <testsuites>
		<testsuite name="TestSuite">
			<directory>application/tests</directory>
		</testsuite>
	</testsuites>
	<php>
		<const name="PHPUNIT_TEST" value="1" />
		<const name="PHPUNIT_CHARSET" value="UTF-8" />
		<server name="REMOTE_ADDR" value="0.0.0.0" />
	</php>
	<filter>
		<blacklist>
			<directory suffix=".php">system</directory>
			<!--directory suffix=".php">application/libraries</directory-->
		</blacklist>
	</filter>
</phpunit>

php -d xdebug.mode=off /usr/local/bin/phpunit --verbose application/tests/helpers/HelperTest.php
    
