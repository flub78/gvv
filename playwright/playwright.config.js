// @ts-check
import { defineConfig, devices } from '@playwright/test';

/**
 * Read environment variables from file.
 * https://github.com/motdotla/dotenv
 */
// import dotenv from 'dotenv';
// import path from 'path';
// dotenv.config({ path: path.resolve(__dirname, '.env') });

/**
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
  testDir: './tests',
  /* Run tests in files in parallel */
  fullyParallel: true,
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  /* Retry on CI only */
  retries: process.env.CI ? 2 : 0,
  /* Opt out of parallel tests on CI. */
  workers: process.env.CI ? 1 : undefined,
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: 'html',
  /* Global timeout for each test */
  timeout: 60000,
  /* Expect timeout for assertions */
  expect: {
    timeout: 10000,
  },
  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* Base URL to use in actions like `await page.goto('')`. */
    baseURL: process.env.BASE_URL || 'http://gvv.net',

    /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
    trace: 'on-first-retry',
    /* Take screenshot on failure */
    screenshot: 'only-on-failure',
    /* Action timeout */
    actionTimeout: 15000,
    /* Navigation timeout */
    navigationTimeout: 30000,
  },

  /* Configure projects for major browsers */
  projects: [
    /**
     * Tests destructeurs / modificateurs d'état global (base de données, sections).
     * Exécutés en premier, séquentiellement, avant tous les autres tests.
     * Inclure ici tout test qui :
     *   - exécute des migrations (downgrade/upgrade)
     *   - modifie des enregistrements partagés (sections, configuration)
     *   - effectue des backup/restore
     */
    {
      name: 'sequential',
      use: { ...devices['Desktop Chrome'] },
      testMatch: [
        '**/migration-test.spec.js',
        '**/categorie-seance-test.spec.js',
        '**/sections_menu_flags.spec.js',
      ],
      fullyParallel: false,
    },

    /**
     * Tous les autres tests : exécutés en parallèle.
     * Utiliser "npm test" ou "npm run test:all" pour exécuter sequential en premier.
     * Pour le développement quotidien : npx playwright test --project=chromium
     */
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
      testIgnore: [
        '**/migration-test.spec.js',
        '**/categorie-seance-test.spec.js',
        '**/sections_menu_flags.spec.js',
      ],
    },
  ],

  /* Run your local dev server before starting the tests */
  // webServer: {
  //   command: 'npm run start',
  //   url: 'http://localhost:3000',
  //   reuseExistingServer: !process.env.CI,
  // },
});

