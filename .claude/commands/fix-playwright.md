# Command fix-playwright

Fixes issues related to Playwright tests, such as browser automation errors, test flakiness, or configuration problems.

## Steps
1. check test-results/playwright-junit.xml for failing tests
2. chose one of the failing tests to fix
3. read the test file and understand the test logic
4. identify the root cause of the failure (e.g., timing issues, incorrect selectors, or missing waits)
5. modify the test code to fix the issue. If the fail is an application code error ask for confirmation before to fix the application.
6. run the test suite to ensure the fix resolves the issue and does not introduce new failures