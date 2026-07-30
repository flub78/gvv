<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit Tests — Database::unwrap_version_comments() / strip_line_comments().
 *
 * mysqldump wraps some standalone statements (SET NAMES, FOREIGN_KEY_CHECKS,
 * ALTER TABLE ... DISABLE/ENABLE KEYS...) in MySQL version-comment syntax
 * (/*!40101 ... *\/;), and precedes every DROP/LOCK TABLES statement with a
 * "-- Table structure for table `x`" comment block. CI's is_write_type() only
 * recognises a write query by its literal first keyword, and Database::sql()
 * splits statements on ";\n" — so a comment glued to the front of the next
 * statement (comments don't end in ";") makes is_write_type() misclassify it
 * as SELECT-like. CI then tries to build a result object from the boolean
 * TRUE that mysqli_query() returns for these statements — fatal under PHP 8
 * (mysqli_num_rows(): Argument #1 must be of type mysqli_result, true
 * given). This broke every /admin/do_restore of a mysqldump backup.
 */
class DatabaseRestoreTest extends TestCase
{
    private $database;

    protected function setUp(): void
    {
        require_once APPPATH . 'libraries/Database.php';
        $reflection = new ReflectionClass('Database');
        $this->database = $reflection->newInstanceWithoutConstructor();

        $fake_ci = new stdClass();
        $fake_ci->db = new FakeDbForDatabaseSqlTest();
        $prop = new ReflectionProperty('Database', 'CI');
        $prop->setAccessible(true);
        $prop->setValue($this->database, $fake_ci);
    }

    private function call($method_name, $sql)
    {
        $method = new ReflectionMethod('Database', $method_name);
        $method->setAccessible(true);
        return $method->invoke($this->database, $sql);
    }

    private function unwrap($sql)
    {
        return $this->call('unwrap_version_comments', $sql);
    }

    private function strip_comments($sql)
    {
        return $this->call('strip_line_comments', $sql);
    }

    private function is_write_type($sql)
    {
        // Mirrors system/database/DB_driver.php::is_write_type()
        return (bool) preg_match(
            '/^\s*"?(SET|INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|TRUNCATE|LOAD DATA|COPY|ALTER|GRANT|REVOKE|LOCK|UNLOCK)\s+/i',
            $sql
        );
    }

    public function test_unwraps_set_statement()
    {
        $sql = "/*!40101 SET NAMES utf8mb4 */;";
        $this->assertSame("SET NAMES utf8mb4;", $this->unwrap($sql));
    }

    public function test_unwraps_alter_table_disable_keys()
    {
        $sql = "/*!40000 ALTER TABLE `membres` DISABLE KEYS */;";
        $this->assertSame("ALTER TABLE `membres` DISABLE KEYS;", $this->unwrap($sql));
    }

    public function test_unwrapped_statement_is_recognised_as_write_type()
    {
        $lines = array(
            "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;",
            "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;",
            "/*!40000 ALTER TABLE `membres` DISABLE KEYS */;",
        );
        foreach ($lines as $line) {
            $this->assertFalse($this->is_write_type($line), "Precondition: line should be misclassified before unwrap: $line");
            $unwrapped = $this->unwrap($line);
            $this->assertTrue($this->is_write_type($unwrapped), "Line should be recognised as write-type after unwrap: $unwrapped");
        }
    }

    public function test_preserves_line_and_statement_count_of_a_dump_header()
    {
        $sql = "/*M!999999\- enable the sandbox mode */ \n"
            . "-- MariaDB dump 10.19\n"
            . "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n"
            . "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n"
            . "/*!40101 SET NAMES utf8mb4 */;\n"
            . "DROP TABLE IF EXISTS `membres`;\n"
            . "CREATE TABLE `membres` (`id` int(11));\n";

        $out = $this->unwrap($sql);

        $this->assertSame(
            substr_count($sql, "\n"),
            substr_count($out, "\n"),
            'Unwrapping must not merge or drop lines (would break the ";\n" statement splitter in Database::sql()).'
        );
        $this->assertStringContainsString("SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT;", $out);
        $this->assertStringContainsString("SET NAMES utf8mb4;", $out);
        // Untouched statements must survive as-is.
        $this->assertStringContainsString("CREATE TABLE `membres` (`id` int(11));", $out);
    }

    public function test_does_not_touch_inline_definer_comments_inside_create_statements()
    {
        // strip_definers() already handles DEFINER; unwrap_version_comments() must
        // not interfere with version comments that are not statement-level.
        $sql = "CREATE DEFINER=/*!50017 `root`@`localhost`*/ SQL SECURITY DEFINER VIEW `v` AS SELECT 1;";
        $this->assertSame($sql, $this->unwrap($sql));
    }

    public function test_strips_table_structure_comment_block()
    {
        $sql = "--\n-- Table structure for table `membres`\n--\n\nDROP TABLE IF EXISTS `membres`;\n";
        $out = $this->strip_comments($sql);
        $this->assertStringNotContainsString('--', $out);
        $this->assertStringContainsString("DROP TABLE IF EXISTS `membres`;", $out);
    }

    public function test_strips_mariadb_sandbox_mode_directive()
    {
        $sql = "/*M!999999\\- enable the sandbox mode */ \n";
        $out = $this->strip_comments($sql);
        $this->assertSame('', trim($out));
    }

    public function test_comment_glued_chunk_is_recognised_as_write_type_after_full_pipeline()
    {
        // Reproduces exactly what Database::sql() builds as one ";\n"-delimited
        // chunk: the previous statement's trailing "\n", then a mysqldump
        // "-- Table structure" block glued onto the following DROP TABLE.
        $sql = "--\n-- Table structure for table `acceptance_items`\n--\n\nDROP TABLE IF EXISTS `acceptance_items`";

        $this->assertFalse($this->is_write_type($sql), 'Precondition: raw chunk should be misclassified');

        $out = $this->strip_comments($sql);
        $this->assertTrue($this->is_write_type($out), "Chunk should be recognised as write-type after stripping: $out");
    }

    public function test_does_not_strip_inline_double_dash_inside_data_value()
    {
        // "--" must only be treated as a comment marker at the start of a line,
        // never mid-line (e.g. a member's address or name containing "--").
        $sql = "INSERT INTO `membres` VALUES (1,'Rue A -- B');\n";
        $this->assertSame($sql, $this->strip_comments($sql));
    }

    public function test_full_pipeline_leaves_no_misclassified_statement()
    {
        // A miniature mysqldump-shaped fixture combining every category this
        // bug covers: header directives, version-comment SET/ALTER, and
        // "-- Table structure" blocks before DROP/LOCK TABLES.
        $sql = "/*M!999999\\- enable the sandbox mode */ \n"
            . "-- MariaDB dump 10.19\n"
            . "/*!40101 SET NAMES utf8mb4 */;\n"
            . "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n"
            . "\n"
            . "--\n"
            . "-- Table structure for table `membres`\n"
            . "--\n"
            . "\n"
            . "DROP TABLE IF EXISTS `membres`;\n"
            . "CREATE TABLE `membres` (`id` int(11));\n"
            . "/*!40000 ALTER TABLE `membres` DISABLE KEYS */;\n"
            . "INSERT INTO `membres` VALUES (1,'Dupont');\n"
            . "/*!40000 ALTER TABLE `membres` ENABLE KEYS */;\n"
            . "\n"
            . "--\n"
            . "-- Dumping data for table `membres`\n"
            . "--\n"
            . "\n"
            . "LOCK TABLES `membres` WRITE;\n"
            . "UNLOCK TABLES;\n";

        $out = $this->unwrap($sql);
        $out = $this->strip_comments($out);

        $this->assertSame(
            substr_count($sql, "\n"),
            substr_count($out, "\n"),
            'Line count must be preserved so the ";\n" statement splitter keeps working.'
        );

        $reqs = preg_split("/;\n/", $out);
        $checked = 0;
        foreach ($reqs as $req) {
            if (trim($req) === '') {
                continue;
            }
            $this->assertTrue($this->is_write_type($req), "Statement misclassified after full pipeline: $req");
            $checked++;
        }
        // Sanity check: the fixture really does contain statements to verify.
        $this->assertGreaterThanOrEqual(7, $checked);
    }

    // ── Database::sql() error handling ─────────────────────────────────────

    private function sql($sql, $return_result = false)
    {
        return $this->database->sql($sql, $return_result);
    }

    private function fake_db()
    {
        $prop = new ReflectionProperty('Database', 'CI');
        $prop->setAccessible(true);
        return $prop->getValue($this->database)->db;
    }

    public function test_sql_runs_all_statements_when_none_fail()
    {
        $sql = "CREATE TABLE `a` (`id` int(11));\nINSERT INTO `a` VALUES (1);\nINSERT INTO `a` VALUES (2);\n";
        $this->sql($sql);
        // +1 for the "SET sql_mode=..." statement sql() always issues first.
        $this->assertCount(4, $this->fake_db()->queries);
    }

    public function test_sql_throws_on_query_failure_but_still_reports_all_errors()
    {
        $sql = "CREATE TABLE `a` (`id` int(11));\nCREATE TABLE `BOOM` (`id` int(11));\nINSERT INTO `a` VALUES (1);\n";
        $this->fake_db()->fail_on = array('BOOM');

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/requête\(s\) SQL en échec/');
        $this->sql($sql);
    }

    public function test_sql_continues_past_a_failing_statement_instead_of_aborting()
    {
        // A single bad statement (e.g. a table the dump can't recreate) must not
        // prevent the rest of a multi-hundred-statement restore from running —
        // that silent early-abort is exactly what left `users`/`membres` missing
        // in earlier incidents.
        $sql = "CREATE TABLE `BOOM` (`id` int(11));\nCREATE TABLE `after_boom` (`id` int(11));\n";
        $db = $this->fake_db();
        $db->fail_on = array('BOOM');

        try {
            $this->sql($sql);
            $this->fail('Expected an Exception to be thrown once all statements ran.');
        } catch (Exception $e) {
            // Both statements must have been attempted despite the first failing
            // (queries[0] is the leading "SET sql_mode=..." sql() always issues).
            $this->assertCount(3, $db->queries);
            $this->assertStringContainsString('BOOM', $db->queries[1]);
            $this->assertStringContainsString('after_boom', $db->queries[2]);
        }
    }

    public function test_sql_does_not_crash_when_write_statement_returns_boolean_true_with_return_result()
    {
        // mysqli_query() returns boolean TRUE (not an object) for write-type
        // statements. If sql() is called with $return_result = true while the
        // dump contains writes (as opposed to the pure-SELECT reporting callers
        // in plan_comptable.php/reports.php), it must not try to call
        // result_array() on that boolean.
        $sql = "INSERT INTO `a` VALUES (1);\n";
        $result = $this->sql($sql, true);
        $this->assertSame(array(), $result);
    }
}

/**
 * Minimal stand-in for CI's $this->CI->db, just enough for Database::sql().
 */
class FakeDbForDatabaseSqlTest
{
    public $queries = array();
    public $fail_on = array();
    private $last_error = array('code' => 0, 'message' => '');

    public function query($sql)
    {
        $this->queries[] = $sql;
        foreach ($this->fail_on as $needle) {
            if (strpos($sql, $needle) !== false) {
                $this->last_error = array('code' => 1064, 'message' => 'Simulated failure near: ' . $needle);
                return FALSE;
            }
        }
        // mysqli_query() returns boolean TRUE for write-type statements.
        return TRUE;
    }

    public function error()
    {
        return $this->last_error;
    }
}
