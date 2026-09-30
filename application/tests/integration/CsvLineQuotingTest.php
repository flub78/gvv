<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for GVVMetadata::csv_line(), used by the generic csv() and
 * csv_table() exports.
 *
 * Regression: values containing a line break (e.g. bank transfer motives in
 * the journal description) were written raw, splitting one record over
 * several lines when the CSV was opened in a spreadsheet.
 */
class CsvLineQuotingTest extends TestCase
{
    private $gvvmetadata;

    public function setUp(): void
    {
        $CI = get_instance();
        $CI->load->helper('form');
        $CI->load->helper('download');

        if (!class_exists('GVVMetadata')) {
            require_once APPPATH . 'libraries/MetaData.php';
            require_once APPPATH . 'libraries/Gvvmetadata.php';
        }

        $this->gvvmetadata = new GVVMetadata();
    }

    private function csvLine($cells)
    {
        $method = new ReflectionMethod('GVVMetadata', 'csv_line');
        $method->setAccessible(true);
        return $method->invoke($this->gvvmetadata, $cells);
    }

    private function parse($line)
    {
        $records = array();
        $fh = fopen('php://memory', 'r+');
        fwrite($fh, $line);
        rewind($fh);
        while (($record = fgetcsv($fh, 0, ';', '"', '')) !== false) {
            $records[] = $record;
        }
        fclose($fh);
        return $records;
    }

    /**
     * Plain values keep the historical "a; b; c; " layout byte for byte.
     */
    public function testPlainValuesKeepHistoricalLayout()
    {
        $this->assertSame("12; 01/01/2026; 198,56; \n", $this->csvLine(array(12, '01/01/2026', '198,56')));
    }

    /**
     * Multi-line, semicolon and quote values are quoted so that the line
     * parses back as a single record with the original values.
     */
    public function testSpecialValuesAreQuotedAndParseAsOneRecord()
    {
        $description = "DATE: 04/01/2026\nMOTIF: cotisation; \"junior\"";
        $line = $this->csvLine(array('38007', $description, '100,00'));

        $records = $this->parse($line);
        $this->assertCount(1, $records, 'The multi-line value must not split the record');
        $this->assertSame('38007', $records[0][0]);
        // The space following each separator stays part of the value, as before
        $this->assertSame(' ' . $description, $records[0][1]);
        $this->assertSame(' 100,00', $records[0][2]);
    }

    /**
     * A special character in the first cell is quoted without a leading space.
     */
    public function testFirstCellIsQuotedWithoutLeadingSpace()
    {
        $this->assertSame("\"a;b\"; c; \n", $this->csvLine(array('a;b', 'c')));
    }
}
