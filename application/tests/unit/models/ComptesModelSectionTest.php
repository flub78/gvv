<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'models/comptes_model.php';

class FakeDbResult
{
    private $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function result_array()
    {
        return $this->rows;
    }

    public function row_array()
    {
        return $this->rows[0] ?? null;
    }

    public function num_rows()
    {
        return count($this->rows);
    }
}

class FakeDb
{
    public $selects = [];
    public $from = '';
    public $joins = [];
    public $wheres = [];
    public $orderBys = [];

    private $rows;
    private $countResult;

    public function __construct(array $rows = [], int $countResult = 0)
    {
        $this->rows = $rows;
        $this->countResult = $countResult;
    }

    public function select($fields)
    {
        $this->selects[] = $fields;
        return $this;
    }

    public function from($table)
    {
        $this->from = $table;
        return $this;
    }

    public function join($table, $cond, $type = '')
    {
        $this->joins[] = [$table, $cond, $type];
        return $this;
    }

    public function where($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->wheres[] = [$k, $v];
            }
        } else {
            $this->wheres[] = [$key, $value];
        }
        return $this;
    }

    public function order_by($field, $direction = '')
    {
        $this->orderBys[] = [$field, $direction];
        return $this;
    }

    public function get($table = '', $limit = null, $offset = null)
    {
        return new FakeDbResult($this->rows);
    }

    public function count_all_results($table = '')
    {
        return $this->countResult;
    }
}

class StubMembresModel
{
    private $records;

    public function __construct(array $records)
    {
        $this->records = $records;
    }

    public function get_by_id($keyid, $keyvalue)
    {
        return $this->records[$keyvalue] ?? null;
    }
}

class StubSectionsModel
{
    private $currentSection;
    private $sections;

    public function __construct($currentSection = null, array $sections = [])
    {
        $this->currentSection = $currentSection;
        $this->sections = $sections;
    }

    public function section()
    {
        return $this->currentSection;
    }

    public function section_id()
    {
        return $this->currentSection['id'] ?? null;
    }

    public function get_by_id($keyid, $keyvalue)
    {
        return $this->sections[$keyvalue] ?? null;
    }
}

class TestableComptesModel extends Comptes_model
{
    public function __construct($db, $membres_model, $sections_model)
    {
        // Avoid parent constructor to bypass CI loader wiring for unit tests
        $this->db = $db;
        $this->membres_model = $membres_model;
        $this->sections_model = $sections_model;
        $this->table = 'comptes';
    }
}

class ComptesModelSectionTest extends TestCase
{
    public function test_get_pilote_comptes_uses_account_owner_and_filters_active_unmasked()
    {
        $accounts = [
            ['id' => 1, 'nom' => 'Compte A', 'club' => 2, 'section_name' => 'Section 2'],
            ['id' => 2, 'nom' => 'Compte B', 'club' => 3, 'section_name' => 'Section 3'],
        ];

        $db = new FakeDb($accounts);
        $membres = new StubMembresModel([
            'pilot1' => ['membre_payeur' => 'payer1'],
        ]);
        $sections = new StubSectionsModel(null, [2 => ['id' => 2, 'nom' => 'Section 2']]);

        $model = new TestableComptesModel($db, $membres, $sections);
        $result = $model->get_pilote_comptes('pilot1');

        $this->assertSame($accounts, $result);
        $this->assertContains(['comptes.pilote', 'payer1'], $db->wheres, 'Should filter on account owner (membre_payeur)');
        $this->assertContains(['comptes.masked', 0], $db->wheres, 'Should exclude masked accounts');
        $this->assertContains(['comptes.actif', 1], $db->wheres, 'Should keep only active accounts');
        $this->assertContains(['comptes.codec', '411'], $db->wheres, 'Should target 411 accounts');
    }

    public function test_has_compte_in_section_checks_owner_and_filters()
    {
        $db = new FakeDb([], 1); // count_all_results returns 1
        $membres = new StubMembresModel([
            'child' => ['membre_payeur' => 'parent_user'],
        ]);
        $sections = new StubSectionsModel();

        $model = new TestableComptesModel($db, $membres, $sections);
        $hasAccount = $model->has_compte_in_section('child', 5);

        $this->assertTrue($hasAccount, 'Account should be detected when count_all_results > 0');
        $this->assertContains(['pilote', 'parent_user'], $db->wheres, 'Should check parent payer account');
        $this->assertContains(['club', 5], $db->wheres, 'Should filter by requested section');
        $this->assertContains(['masked', 0], $db->wheres, 'Should exclude masked accounts');
        $this->assertContains(['actif', 1], $db->wheres, 'Should require active account');
    }

    public function test_compte_pilote_filters_by_explicit_section_and_returns_first_match()
    {
        $rows = [
            ['id' => 42, 'nom' => 'Compte Section', 'club' => 7, 'pilote' => 'pilot2'],
        ];

        $db = new FakeDb($rows);
        $membres = new StubMembresModel(['pilot2' => ['membre_payeur' => null]]);
        $sections = new StubSectionsModel(null, [7 => ['id' => 7, 'nom' => 'Section 7']]);

        $model = new TestableComptesModel($db, $membres, $sections);
        $compte = $model->compte_pilote('pilot2', 7);

        $this->assertSame($rows[0], $compte);
        $this->assertContains(['comptes.club', 7], $db->wheres, 'Should filter on provided section');
        $this->assertContains(['pilote', 'pilot2'], $db->wheres, 'Should look up the pilot account');
    }
}
