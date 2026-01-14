# Playwright tests for GVV

## Prerequisites

The playwright tests require Node.js and npm to be installed.

They are also dependent on the "Test database" which contains predefined users and data. You can generate this test database by navigating to `/admin/generate_test_database` in your GVV installation.

This method generates the database but does not load it. You must load the test database by restoring install/base_de_test.enc.zip into your test environment.

## Configuration

### Base URL

By default, tests run against `http://gvv.net`. You can change the target URL using the `BASE_URL` environment variable:

```bash
# Run tests against default URL (http://gvv.net)
npx playwright test

# Run tests against a different URL
BASE_URL=https://staging.example.com npx playwright test
BASE_URL=https://prod.example.com npx playwright test
BASE_URL=http://localhost/gvv npx playwright test
```

You can also create a `.env` file (copy from `.env.example`) to set a persistent BASE_URL for your local environment.

## **Commandes Playwright:**

```bash
cd playwright
npx playwright test                       # Tous les tests
npx playwright test --headed              # Avec affichage navigateur
npx playwright test --project=chromium    # Navigateur spécifique
npx playwright show-report                # Rapport HTML
npx playwright test --reporter=line       # Results in 
npx playwright test tests/bugfix-payeur-selector.spec.js  # to run a single test

BASE_URL=https://gvvg.flub78.net npx playwright test
```