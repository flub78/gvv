# Command: phpunit

Generate a phpunit test for the file specified in $ARGUMENT

## Steps
1. Read the file at path: $ARGUMENTS
2. Check if there is already a phpunit test for this file under application/tests
3. If there is already a test report it and stop
4. Analyse the file to test to check if it is self contained in which case a unit test must be generated, if it is an integration test, an integration Mysql model or a controller test. Generate the test in the correct folder according to this analysis
5. Generate the test in replicating the logic from existing tests. Especially look at MyHtmlIntegrationTest.php to see how the file under test must be loaded.
6. Always test for nominal and non nominal cases
7. Run the generated test and check that it passes
8. Aims for 80 % of test coverage, run it with coverage information to check it
 