Context: GVV project (see copilot-instructions.md)
Devops: Refactoring CodeIgniter tests
Description: This project has a set of unit, model and integration tests which are using the Cogniter unit_test library. We want to migrate as much of possible of these tests using the phpunit framework. There are already several tests implemented this way, unit tests, Integration tests, model tests, etc.

The phpunit tests are run using the run_all_tests.sh bash shell.

The CodeIgniter tests are managed by the tests controller, index method. This controller display the result of the tests on different pages.

1) identify the tests which are implemented in CodeIgniter and have no equivalent with phpunit.
2) For each of them (if it is possible with the current phpunit test framework) create a phpunit test. It is likely difficult to test controllers that way, so keep the controller tests for a second phase.
3) Generate a list of CodeIgniter tests which have been replaced and can be removed
4) Generate a list of CodeIgniter tests whith no phpunit equivalent, that must be kept.
5) update the run_all_tests.sh if necessary

* Work step by step and in doubt ask for confirmation.
* Run the tests, they must pass
* Do not remove anything from application/tests as it is the directory for the new phpunit tests.
