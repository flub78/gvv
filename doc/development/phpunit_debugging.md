# PHPUnit Debugging Guide

## Method 1: VS Code Debugging

The launch.json file has been updated with three new debugging configurations:

1. **Debug PHPUnit Tests** - Debug all tests
2. **Debug Current PHPUnit Test File** - Debug the currently open test file
3. **Debug Specific PHPUnit Test Method** - Debug a specific test method by name

### How to use:
1. Set breakpoints in your test files or source code
2. Open Run and Debug panel (Ctrl+Shift+D)
3. Select one of the PHPUnit debug configurations
4. Press F5 or click the green play button

## Method 2: Command Line Debugging

### Enable Xdebug for PHPUnit runs:
```bash
# Debug all tests
source setenv.sh
XDEBUG_CONFIG="idekey=VSCODE" php -d xdebug.mode=debug -d xdebug.start_with_request=yes /usr/local/bin/phpunit

# Debug specific test file
source setenv.sh  
XDEBUG_CONFIG="idekey=VSCODE" php -d xdebug.mode=debug -d xdebug.start_with_request=yes /usr/local/bin/phpunit application/tests/helpers/ValidationHelperTest.php

# Debug specific test method
source setenv.sh
XDEBUG_CONFIG="idekey=VSCODE" php -d xdebug.mode=debug -d xdebug.start_with_request=yes /usr/local/bin/phpunit --filter testEmailValidation

# Debug with breakpoint at start (stops on first line)
source setenv.sh
XDEBUG_CONFIG="idekey=VSCODE" php -d xdebug.mode=debug -d xdebug.start_with_request=yes -d xdebug.start_upon_error=yes /usr/local/bin/phpunit --filter testEmailValidation
```

### Alternative using environment variables:
```bash
# Set environment for session
source setenv.sh
export XDEBUG_CONFIG="idekey=VSCODE"
export XDEBUG_MODE=debug

# Then run any phpunit command
php -d xdebug.start_with_request=yes /usr/local/bin/phpunit --filter testEmailValidation
```

## Method 3: Debugging with xdebug_break()

Add `xdebug_break();` directly in your test code where you want to break:

```php
public function testEmailValidation()
{
    xdebug_break(); // Debugger will stop here
    
    // Test valid emails
    $this->assertTrue(valid_email('test@example.com'));
    // ... rest of test
}
```

## Method 4: PHPStorm/IntelliJ Debugging

If using PHPStorm:
1. Set breakpoints in test files
2. Right-click on test file → "Debug"
3. Or create a PHPUnit run configuration pointing to your phpunit.xml

## Debugging Tips

### 1. Verify Xdebug is working:
```bash
source setenv.sh
php -i | grep -i xdebug
```

### 2. Test Xdebug connection:
```bash
source setenv.sh
php -d xdebug.mode=debug -d xdebug.start_with_request=yes -r "echo 'Xdebug enabled';"
```

### 3. Check if VS Code PHP Debug extension is installed:
- Install "PHP Debug" by Xdebug
- Make sure it's enabled and listening on port 9003

### 4. Common issues:
- **Port conflicts**: Make sure port 9003 is not used by other applications
- **Firewall**: Ensure port 9003 is open for localhost connections
- **Path mappings**: Verify that paths in launch.json match your actual file paths

### 5. Debug specific scenarios:

#### Debug a failing test:
```bash
source setenv.sh
XDEBUG_CONFIG="idekey=VSCODE" php -d xdebug.mode=debug -d xdebug.start_with_request=yes /usr/local/bin/phpunit --filter testFromConversions
```

#### Debug with verbose output:
```bash
source setenv.sh
XDEBUG_CONFIG="idekey=VSCODE" php -d xdebug.mode=debug -d xdebug.start_with_request=yes /usr/local/bin/phpunit --verbose --filter testFromConversions
```

#### Debug library tests:
```bash
source setenv.sh
XDEBUG_CONFIG="idekey=VSCODE" php -d xdebug.mode=debug -d xdebug.start_with_request=yes /usr/local/bin/phpunit application/tests/libraries/BitfieldTest.php
```

## Current Project Context

Your GVV project already has:
- ✅ Xdebug 3.1.6 installed and configured
- ✅ PHPUnit 8.5.44 working
- ✅ Test files in application/tests/
- ✅ VS Code launch configurations added

You can now debug any of your tests:
- Helper tests: ValidationHelperTest.php
- Model tests: ConfigurationModelTest.php  
- Library tests: BitfieldTest.php
