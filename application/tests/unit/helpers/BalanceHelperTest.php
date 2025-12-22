<?php

use PHPUnit\Framework\TestCase;

/**
 * Mock class for Gvvmetadata
 */
class MockGvvmetadata
{
    public function label($table, $field)
    {
        $labels = [
            'vue_comptes' => [
                'codec' => 'Code',
                'nom' => 'Nom',
                'solde_debit' => 'Débit',
                'solde_credit' => 'Crédit',
                'section_name' => 'Section'
            ]
        ];
        return isset($labels[$table][$field]) ? $labels[$table][$field] : $field;
    }
}

/**
 * Mock class for Lang
 */
class MockLang
{
    public function line($key)
    {
        $lines = [
            'gvv_str_actions' => 'Actions',
            'gvv_button_edit' => 'Modifier',
            'gvv_button_delete' => 'Supprimer',
            'gvv_str_no_data' => 'Aucune donnée',
            'comptes_confirm_delete_account' => 'Confirmer la suppression du compte'
        ];
        return isset($lines[$key]) ? $lines[$key] : $key;
    }

    public function load($file)
    {
        return true;
    }
}

/**
 * Mock class for Config
 */
class MockConfig
{
    private $config = [
        'base_url' => 'http://localhost/',
        'index_page' => 'index.php'
    ];

    public function item($item)
    {
        return isset($this->config[$item]) ? $this->config[$item] : null;
    }

    public function set_item($item, $value)
    {
        $this->config[$item] = $value;
    }

    public function slash_item($item)
    {
        $value = $this->item($item);
        if (empty($value)) {
            return '';
        }
        return rtrim($value, '/') . '/';
    }

    public function site_url($uri = '', $protocol = null)
    {
        $base_url = $this->item('base_url');
        $index_page = $this->item('index_page');

        if (empty($base_url)) {
            $base_url = 'http://localhost/';
        }

        if (substr($base_url, -1) !== '/') {
            $base_url .= '/';
        }

        if (!empty($index_page)) {
            $base_url .= $index_page . '/';
        }

        return $base_url . ltrim($uri, '/');
    }

    public function base_url($uri = '', $protocol = null)
    {
        $base_url = $this->item('base_url');

        if (empty($base_url)) {
            $base_url = 'http://localhost/';
        }

        if (substr($base_url, -1) !== '/') {
            $base_url .= '/';
        }

        return $base_url . ltrim($uri, '/');
    }
}

/**
 * Mock class for Loader
 */
class MockLoader
{
    public function helper($helper)
    {
        return true;
    }

    public function library($library)
    {
        return true;
    }

    public function model($model)
    {
        return true;
    }
}

/**
 * Mock class for CI - compatible with all tests via magic methods
 */
class MockCIForBalance
{
    public $lang;
    public $config;
    public $load;
    public $my_parsedown;

    private $dynamicProperties = [];

    public function __construct()
    {
        $this->lang = new MockLang();
        $this->config = new MockConfig();
        $this->load = new MockLoader();

        if (class_exists('MY_Parsedown')) {
            $this->my_parsedown = new MY_Parsedown();
        }
    }

    public function __get($name)
    {
        if (!isset($this->dynamicProperties[$name])) {
            $this->dynamicProperties[$name] = null;
        }
        return $this->dynamicProperties[$name];
    }

    public function __set($name, $value)
    {
        $this->dynamicProperties[$name] = $value;
    }

    public function __isset($name)
    {
        return isset($this->dynamicProperties[$name]);
    }
}

/**
 * Test class for balance helper functions
 */
class BalanceHelperTest extends TestCase
{
    private $mockGvvmetadata;
    private $mockCI;
    private $originalCI;

    protected function setUp(): void
    {
        parent::setUp();

        require_once __DIR__ . '/../../../helpers/balance_helper.php';

        global $CI;
        $this->originalCI = $CI;

        $this->mockGvvmetadata = new MockGvvmetadata();
        $this->mockCI = new MockCIForBalance();

        $CI = $this->mockCI;
    }

    protected function tearDown(): void
    {
        global $CI;
        $CI = $this->originalCI;

        parent::tearDown();
    }

    public function testBalanceAccordionHeaderWithDebitBalance()
    {
        $row = [
            'codec' => '6',
            'nom' => 'Charges',
            'solde_debit' => 1500.50,
            'solde_credit' => 0
        ];

        $html = balance_accordion_header($row, $this->mockGvvmetadata);

        $this->assertStringContainsString('<table class="table table-sm mb-0">', $html);
        $this->assertStringContainsString('Code', $html);
        $this->assertStringContainsString('6', $html);
        $this->assertStringContainsString('Charges', $html);
        $this->assertStringContainsString('1&nbsp;500,50&nbsp;€', $html);
    }

    public function testBalanceAccordionHeaderWithCreditBalance()
    {
        $row = [
            'codec' => '7',
            'nom' => 'Produits',
            'solde_debit' => 0,
            'solde_credit' => 2000.75
        ];

        $html = balance_accordion_header($row, $this->mockGvvmetadata);

        $this->assertStringContainsString('2&nbsp;000,75&nbsp;€', $html);
        $this->assertStringContainsString('Produits', $html);
    }

    public function testBalanceAccordionHeaderWithZeroBalance()
    {
        $row = [
            'codec' => '8',
            'nom' => 'Compte Vide',
            'solde_debit' => 0,
            'solde_credit' => 0
        ];

        $html = balance_accordion_header($row, $this->mockGvvmetadata);

        $this->assertStringContainsString('0,00&nbsp;€', $html);
        $this->assertStringContainsString('Compte Vide', $html);
    }

    public function testBalanceAccordionHeaderWithSpecialCharacters()
    {
        $row = [
            'codec' => '6.1',
            'nom' => 'Charges & <Frais>',
            'solde_debit' => 100,
            'solde_credit' => 0
        ];

        $html = balance_accordion_header($row, $this->mockGvvmetadata);

        $this->assertStringContainsString('Charges &amp; &lt;Frais&gt;', $html);
        $this->assertStringNotContainsString('Charges & <Frais>', $html);
    }

    public function testBalanceDetailDatatableSmallDataset()
    {
        $details = [
            [
                'id' => 1,
                'codec' => '6.1',
                'nom' => 'Compte 1',
                'section_name' => 'Section A',
                'solde_debit' => 100,
                'solde_credit' => 0
            ],
            [
                'id' => 2,
                'codec' => '6.2',
                'nom' => 'Compte 2',
                'section_name' => 'Section B',
                'solde_debit' => 200,
                'solde_credit' => 0
            ]
        ];

        $html = balance_detail_datatable(
            $details,
            '6',
            $this->mockGvvmetadata,
            'comptes',
            true,
            ['id' => 1]
        );

        $this->assertStringNotContainsString('balance_searchable_datatable', $html);
        $this->assertStringContainsString('Compte 1', $html);
        $this->assertStringContainsString('Compte 2', $html);
        $this->assertStringContainsString('fa-edit', $html);
        $this->assertStringContainsString('Total', $html);
        $this->assertStringContainsString('300,00&nbsp;€', $html);
    }

    public function testBalanceDetailDatatableLargeDataset()
    {
        $details = [];
        for ($i = 1; $i <= 15; $i++) {
            $details[] = [
                'id' => $i,
                'codec' => "6.$i",
                'nom' => "Compte $i",
                'section_name' => "Section $i",
                'solde_debit' => $i * 100,
                'solde_credit' => 0
            ];
        }

        $html = balance_detail_datatable(
            $details,
            '6',
            $this->mockGvvmetadata,
            'comptes',
            true,
            ['id' => 1]
        );

        $this->assertStringContainsString('balance_searchable_datatable', $html);
        $this->assertStringContainsString('12&nbsp;000,00&nbsp;€', $html);
    }

    public function testBalanceDetailDatatableWithoutModificationRights()
    {
        $details = [
            [
                'id' => 1,
                'codec' => '6.1',
                'nom' => 'Compte 1',
                'section_name' => 'Section A',
                'solde_debit' => 100,
                'solde_credit' => 0
            ]
        ];

        $html = balance_detail_datatable(
            $details,
            '6',
            $this->mockGvvmetadata,
            'comptes',
            false,
            null
        );

        $this->assertStringNotContainsString('fa-edit', $html);
        $this->assertStringNotContainsString('fa-trash', $html);
    }

    public function testBalanceDetailDatatableSingleAccount()
    {
        $details = [
            [
                'id' => 1,
                'codec' => '6.1',
                'nom' => 'Compte Unique',
                'section_name' => 'Section A',
                'solde_debit' => 100,
                'solde_credit' => 0
            ]
        ];

        $html = balance_detail_datatable(
            $details,
            '6',
            $this->mockGvvmetadata,
            'comptes',
            true,
            ['id' => 1]
        );

        $this->assertStringNotContainsString('Total', $html);
    }

    public function testBalanceDetailDatatableWithMixedBalances()
    {
        $details = [
            [
                'id' => 1,
                'codec' => '6.1',
                'nom' => 'Compte Débit',
                'section_name' => 'Section A',
                'solde_debit' => 100,
                'solde_credit' => 0
            ],
            [
                'id' => 2,
                'codec' => '6.2',
                'nom' => 'Compte Crédit',
                'section_name' => 'Section B',
                'solde_debit' => 0,
                'solde_credit' => 50
            ],
            [
                'id' => 3,
                'codec' => '6.3',
                'nom' => 'Compte Zéro',
                'section_name' => 'Section C',
                'solde_debit' => 0,
                'solde_credit' => 0
            ]
        ];

        $html = balance_detail_datatable(
            $details,
            '6',
            $this->mockGvvmetadata,
            'comptes',
            true,
            ['id' => 1]
        );

        $this->assertStringContainsString('100,00&nbsp;€', $html);
        $this->assertStringContainsString('50,00&nbsp;€', $html);
        $this->assertStringContainsString('Compte Zéro', $html);
        $this->assertStringContainsString('0,00&nbsp;€', $html);
    }

    public function testBalanceDetailDatatableWithJournalLinks()
    {
        $details = [
            [
                'id' => 123,
                'codec' => '6.1',
                'nom' => 'Compte Test',
                'section_name' => 'Section A',
                'solde_debit' => 100,
                'solde_credit' => 0
            ]
        ];

        $html = balance_detail_datatable(
            $details,
            '6',
            $this->mockGvvmetadata,
            'comptes',
            true,
            ['id' => 1]
        );

        $this->assertStringContainsString('compta/journal_compte/123', $html);
        $this->assertStringContainsString('<a href=', $html);
    }

    public function testBalanceAccordionItemCollapsed()
    {
        $general_row = [
            'codec' => '6',
            'nom' => 'Charges',
            'solde_debit' => 1500,
            'solde_credit' => 0
        ];

        $details = [
            [
                'id' => 1,
                'codec' => '6.1',
                'nom' => 'Compte 1',
                'section_name' => 'Section A',
                'solde_debit' => 1500,
                'solde_credit' => 0
            ]
        ];

        $html = balance_accordion_item(
            $general_row,
            $details,
            0,
            $this->mockGvvmetadata,
            'comptes',
            true,
            ['id' => 1],
            false
        );

        $this->assertStringContainsString('accordion-item', $html);
        $this->assertStringContainsString('accordion-button collapsed', $html);
        $this->assertStringContainsString('aria-expanded="false"', $html);
        $this->assertStringContainsString('id="heading_6"', $html);
        $this->assertStringContainsString('id="collapse_6"', $html);
        $this->assertStringContainsString('id="datatable_6"', $html);
    }

    public function testBalanceAccordionItemExpanded()
    {
        $general_row = [
            'codec' => '7',
            'nom' => 'Produits',
            'solde_debit' => 0,
            'solde_credit' => 2000
        ];

        $details = [
            [
                'id' => 1,
                'codec' => '7.1',
                'nom' => 'Compte 1',
                'section_name' => 'Section A',
                'solde_debit' => 0,
                'solde_credit' => 2000
            ]
        ];

        $html = balance_accordion_item(
            $general_row,
            $details,
            0,
            $this->mockGvvmetadata,
            'comptes',
            true,
            ['id' => 1],
            true
        );

        $this->assertStringContainsString('accordion-button', $html);
        $this->assertStringNotContainsString('accordion-button collapsed', $html);
        $this->assertStringContainsString('aria-expanded="true"', $html);
        $this->assertStringContainsString('accordion-collapse collapse show', $html);
    }

    public function testBalanceAccordionItemWithEmptyDetails()
    {
        $general_row = [
            'codec' => '8',
            'nom' => 'Compte Sans Détails',
            'solde_debit' => 0,
            'solde_credit' => 0
        ];

        $details = [];

        $html = balance_accordion_item(
            $general_row,
            $details,
            0,
            $this->mockGvvmetadata,
            'comptes',
            true,
            ['id' => 1],
            false
        );

        $this->assertStringContainsString('Aucune donnée', $html);
        $this->assertStringNotContainsString('balance-datatable-wrapper', $html);
    }

    public function testBalanceAccordionItemWithDotsInCodec()
    {
        $general_row = [
            'codec' => '6.1.2',
            'nom' => 'Sous-compte',
            'solde_debit' => 100,
            'solde_credit' => 0
        ];

        $details = [
            [
                'id' => 1,
                'codec' => '6.1.2.1',
                'nom' => 'Détail',
                'section_name' => 'Section A',
                'solde_debit' => 100,
                'solde_credit' => 0
            ]
        ];

        $html = balance_accordion_item(
            $general_row,
            $details,
            0,
            $this->mockGvvmetadata,
            'comptes',
            true,
            ['id' => 1],
            false
        );

        $this->assertStringContainsString('id="heading_6_1_2"', $html);
        $this->assertStringContainsString('id="collapse_6_1_2"', $html);
        $this->assertStringContainsString('id="datatable_6_1_2"', $html);
    }

    public function testBalanceDetailDatatableUniqueIds()
    {
        $details = [
            [
                'id' => 1,
                'codec' => '6.1',
                'nom' => 'Compte',
                'section_name' => 'Section',
                'solde_debit' => 100,
                'solde_credit' => 0
            ]
        ];

        $html = balance_detail_datatable(
            $details,
            '6.1.2',
            $this->mockGvvmetadata,
            'comptes',
            true,
            ['id' => 1]
        );

        $this->assertStringContainsString('id="datatable_6_1_2"', $html);
    }

    public function testBalanceDetailDatatableDeleteConfirmation()
    {
        $details = [
            [
                'id' => 1,
                'codec' => '6.1',
                'nom' => 'Compte à Supprimer',
                'section_name' => 'Section',
                'solde_debit' => 100,
                'solde_credit' => 0
            ]
        ];

        $html = balance_detail_datatable(
            $details,
            '6',
            $this->mockGvvmetadata,
            'comptes',
            true,
            ['id' => 1]
        );

        $this->assertStringContainsString('onclick="return confirm(', $html);
        $this->assertStringContainsString('Confirmer la suppression du compte', $html);
        $this->assertStringContainsString('Compte à Supprimer', $html);
    }
}

?>
