# Command: complete-phpunit

Complete a phpunit test for the file specified in $ARGUMENTS

- first arguments is the name of the file under test
- second argument optional, is the name of the test to complete

When the second argument is not provided and there are several tests covering the module, add the test in the unit test if it is possible. Only add new tests in the integration test if the function under test require more context.

## Steps
1. Read the file at path: $ARGUMENTS
2. Create addition tests to check all functions of the package to test
3. Always test for nominal and non nominal cases
4. Run the generated test and check that it passes
5. Aims for more than 80 % of test coverage, run it with coverage information to check it
6. report bug if some are found in the module under test
 