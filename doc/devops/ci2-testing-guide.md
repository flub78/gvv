# Adding JUnit XML Support to CodeIgniter 2.x Unit Testing

This guide explains how to extend CodeIgniter 2's native unit testing capabilities to support JUnit XML output without requiring Composer or PHPUnit.

## Overview

CodeIgniter 2.x comes with a basic unit testing class (`CI_Unit_test`). While functional, it only outputs results in HTML format. This extension adds JUnit XML output support while maintaining backward compatibility with the original class.

## Implementation

### File Location
Save the extended class as `application/libraries/MY_Unit_test.php`. CodeIgniter will automatically use this extended version instead of the original class.

### Code

```php
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Unit_test extends CI_Unit_test {
    protected $test_cases = array();
    protected $junit_path = APPPATH . 'logs/junit.xml';
    
    public function __construct() {
        parent::__construct();
        $this->results = array();
    }
    
    public function run($test, $expected = TRUE, $test_name = 'undefined', $notes = '') {
        $result = parent::run($test, $expected, $test_name, $notes);
        
        // Store more detailed test information
        $this->test_cases[] = array(
            'name' => $test_name,
            'class' => get_class($this),
            'status' => ($result['Result'] === 'Passed') ? 'pass' : 'fail',
            'message' => $notes,
            'time' => microtime(true)
        );
        
        return $result;
    }
    
    public function generate_junit_xml($suite_name = 'CodeIgniter Tests') {
        $passed = 0;
        $failed = 0;
        $total_time = 0;
        
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><testsuites/>');
        
        $testsuite = $xml->addChild('testsuite');
        $testsuite->addAttribute('name', $suite_name);
        
        foreach ($this->test_cases as $case) {
            $testcase = $testsuite->addChild('testcase');
            $testcase->addAttribute('name', $case['name']);
            $testcase->addAttribute('class', $case['class']);
            $testcase->addAttribute('time', number_format($case['time'], 6));
            
            if ($case['status'] === 'fail') {
                $failure = $testcase->addChild('failure');
                $failure->addAttribute('message', $case['message']);
                $failure->addAttribute('type', 'AssertionFailure');
                $failed++;
            } else {
                $passed++;
            }
            
            $total_time += $case['time'];
        }
        
        $testsuite->addAttribute('tests', count($this->test_cases));
        $testsuite->addAttribute('failures', $failed);
        $testsuite->addAttribute('errors', '0');
        $testsuite->addAttribute('time', number_format($total_time, 6));
        
        // Create logs directory if it doesn't exist
        $logs_dir = dirname($this->junit_path);
        if (!file_exists($logs_dir)) {
            mkdir($logs_dir, 0777, true);
        }
        
        // Save the XML file
        $xml->asXML($this->junit_path);
    }
}
```

## Usage

### Basic Test Controller Example

```php
class Test_Controller extends CI_Controller {
    public function run_tests() {
        $this->load->library('unit_test');
        
        // Your tests
        $this->unit->run(1 + 1, 2, 'Basic addition test');
        $this->unit->run(strtoupper('test'), 'TEST', 'String uppercase test');
        
        // Generate regular HTML report
        echo $this->unit->report();
        
        // Generate JUnit XML
        $this->unit->generate_junit_xml('My Test Suite');
    }
}
```

## Key Features

1. **Backward Compatibility**
   - Extends the native `CI_Unit_test` class
   - All existing test code continues to work
   - HTML report generation still available

2. **JUnit XML Support**
   - Generates standard JUnit XML format
   - Includes test execution times
   - Supports test suite naming
   - Creates logs directory if needed

3. **Test Information**
   - Test name and class
   - Pass/fail status
   - Execution time
   - Failure messages

## XML Output Format

The generated XML follows the JUnit standard format:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
    <testsuite name="My Test Suite" tests="2" failures="0" errors="0" time="0.001234">
        <testcase name="Basic addition test" class="MY_Unit_test" time="0.000123">
        </testcase>
        <testcase name="String uppercase test" class="MY_Unit_test" time="0.000234">
        </testcase>
    </testsuite>
</testsuites>
```

## Configuration

- Default XML output path: `application/logs/junit.xml`
- To change the output path, modify the `$junit_path` property
- Test suite name can be specified when calling `generate_junit_xml()`

## Integration with CI Tools

The generated JUnit XML file is compatible with most CI/CD tools including:
- Jenkins
- GitLab CI
- GitHub Actions
- CircleCI
- Travis CI

These tools can parse the XML file to display test results and track test history over time.

## Best Practices

1. **Test Organization**
   - Group related tests in the same controller
   - Use descriptive test names
   - Include meaningful failure messages

2. **File Management**
   - Consider adding the XML output directory to `.gitignore`
   - Implement cleanup of old test reports if needed
   - Ensure write permissions for the logs directory

3. **Error Handling**
   - Add try-catch blocks around XML generation if needed
   - Validate the existence of the logs directory
   - Check file write permissions

## Troubleshooting

Common issues and solutions:

1. **XML Not Generated**
   - Check write permissions for the logs directory
   - Ensure the logs path is correct
   - Verify that tests are actually running

2. **Invalid XML**
   - Check for special characters in test names
   - Ensure proper encoding of test messages
   - Validate XML format if modified

3. **Timing Issues**
   - Very fast tests might show as 0.000000 seconds
   - Consider adjusting time precision if needed

