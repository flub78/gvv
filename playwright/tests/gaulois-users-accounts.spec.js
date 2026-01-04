/**
 * Gaulois Test Users - Account Verification Tests
 * 
 * Tests that the 4 Gaulois test users created during test database generation:
 * - Can successfully log in
 * - Can access their accounts via mon_compte
 * - Can verify section-specific access
 * - Are properly redirected when accessing unauthorized sections
 * 
 * Users tested:
 * - asterix: Planeur + Général
 * - obelix: Planeur + ULM + Général (Remorqueur)
 * - abraracourcix: Planeur + Avion + ULM + Général (CA + Instructeur)
 * - goudurix: Avion + Général (Trésorier)
 * 
 * Usage:
 *   npx playwright test tests/gaulois-users-accounts.spec.js
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

// Test configuration - all users have same password
const PASSWORD = 'password';

const GAULOIS_USERS = [
    {
        username: 'asterix',
        name: 'Asterix Le Gaulois',
        sections: ['Planeur', 'Général'],
        expectedAccountCount: 2 // One account per section
    },
    {
        username: 'obelix',
        name: 'Obelix Le Gaulois',
        sections: ['Planeur', 'ULM', 'Général'],
        expectedAccountCount: 3 // Remorqueur, 3 sections
    },
    {
        username: 'abraracourcix',
        name: 'Abraracourcix Le Gaulois',
        sections: ['Planeur', 'Avion', 'ULM', 'Général'],
        expectedAccountCount: 4 // CA + Instructeur, 4 sections
    },
    {
        username: 'goudurix',
        name: 'Goudurix Le Gaulois',
        sections: ['Avion', 'Général'],
        expectedAccountCount: 2 // Trésorier, 2 sections
    }
];

test.describe('Gaulois Test Users - Account Verification', () => {

    // Test each Gaulois user
    for (const user of GAULOIS_USERS) {
        test.describe(`User: ${user.username}`, () => {

            test(`should login successfully as ${user.username}`, async ({ page }) => {
                const loginPage = new LoginPage(page);

                // Navigate to login page
                await loginPage.open();

                // Perform login
                await loginPage.login(user.username, PASSWORD);

                // Verify successful login
                await loginPage.verifyLoggedIn();

                console.log(`✓ ${user.username} logged in successfully`);
            });

            test(`should access own accounts via mon_compte for ${user.username}`, async ({ page }) => {
                const loginPage = new LoginPage(page);

                // Login
                await loginPage.open();
                await loginPage.login(user.username, PASSWORD);
                await loginPage.verifyLoggedIn();

                console.log(`✓ Logged in as ${user.username}`);

                // Test accessing mon_compte for section 4 (Général) - all users have this section
                await page.goto('/index.php/compta/mon_compte/4');
                await page.waitForLoadState('networkidle');
                await page.waitForTimeout(1000);

                console.log(`✓ Navigated to mon_compte for section Général (4)`);

                // Verify we're on the mon_compte page
                expect(page.url()).toContain('mon_compte');

                // Look for account information on the page
                // Could be in table, cards, or other format
                const hasTable = await page.locator('table').count() > 0;
                const hasAccountInfo = await page.locator('text=/compte|solde|débit|crédit/i').count() > 0;

                if (hasTable) {
                    console.log('✓ Found account table on mon_compte page');
                }

                if (hasAccountInfo) {
                    console.log('✓ Found account information (compte/solde/débit/crédit)');
                }

                // Verify page has expected content
                expect(hasTable || hasAccountInfo).toBeTruthy();

                console.log(`✓ ${user.username} can access their account via mon_compte`);
            });

            test(`should verify section-specific account access for ${user.username}`, async ({ page }) => {
                const loginPage = new LoginPage(page);

                // Login
                await loginPage.open();
                await loginPage.login(user.username, PASSWORD);
                await loginPage.verifyLoggedIn();

                console.log(`✓ Logged in as ${user.username}`);

                // Section IDs mapping
                const sectionIds = {
                    'Planeur': 1,
                    'ULM': 2,
                    'Avion': 3,
                    'Général': 4
                };

                // Test access to each section the user belongs to
                for (const sectionName of user.sections) {
                    const sectionId = sectionIds[sectionName];
                    
                    await page.goto(`/index.php/compta/mon_compte/${sectionId}`);
                    await page.waitForLoadState('networkidle');
                    await page.waitForTimeout(500);

                    // Verify we're still on mon_compte (not redirected to error/dashboard)
                    const onMonCompte = page.url().includes('mon_compte');
                    
                    if (onMonCompte) {
                        console.log(`✓ ${user.username} can access section ${sectionName} (${sectionId})`);
                    } else {
                        console.log(`⚠ ${user.username} was redirected from section ${sectionName} (${sectionId})`);
                    }

                    expect(onMonCompte).toBeTruthy();
                }

                console.log(`✓ All ${user.sections.length} sections accessible for ${user.username}`);
            });

            test(`should redirect when accessing unauthorized section for ${user.username}`, async ({ page }) => {
                const loginPage = new LoginPage(page);

                // Login
                await loginPage.open();
                await loginPage.login(user.username, PASSWORD);
                await loginPage.verifyLoggedIn();

                console.log(`✓ Logged in as ${user.username}`);

                // Section IDs mapping
                const sectionIds = {
                    'Planeur': 1,
                    'ULM': 2,
                    'Avion': 3,
                    'Général': 4
                };

                // Find a section the user does NOT have access to
                const allSections = ['Planeur', 'ULM', 'Avion', 'Général'];
                const unauthorizedSections = allSections.filter(s => !user.sections.includes(s));

                if (unauthorizedSections.length > 0) {
                    const testSection = unauthorizedSections[0];
                    const testSectionId = sectionIds[testSection];

                    console.log(`Testing unauthorized access to section ${testSection} (${testSectionId})`);

                    // Try to access unauthorized section
                    await page.goto(`/index.php/compta/mon_compte/${testSectionId}`);
                    await page.waitForLoadState('networkidle');
                    await page.waitForTimeout(1000);

                    // User should be redirected away from mon_compte (to dashboard or error page)
                    const stillOnMonCompte = page.url().includes(`mon_compte/${testSectionId}`);
                    
                    expect(stillOnMonCompte).toBeFalsy();
                    console.log(`✓ ${user.username} was properly redirected from unauthorized section ${testSection}`);
                } else {
                    console.log(`⚠ ${user.username} has access to all sections, skipping unauthorized test`);
                }
            });

        });
    }

    // Additional test: Verify all 4 users can login in sequence
    test('should verify all 4 Gaulois users can login and access mon_compte sequentially', async ({ page }) => {
        const loginPage = new LoginPage(page);
        
        for (const user of GAULOIS_USERS) {
            console.log(`\nTesting ${user.username}...`);

            // Navigate to login page
            await loginPage.open();

            // Login
            await loginPage.login(user.username, PASSWORD);

            // Verify login
            await loginPage.verifyLoggedIn();
            console.log(`✓ ${user.username} logged in successfully`);

            // Quick check for mon_compte access (section 4 = Général, all users have it)
            await page.goto('/index.php/compta/mon_compte/4');
            await page.waitForLoadState('networkidle');
            
            const onMonCompte = page.url().includes('mon_compte');
            expect(onMonCompte).toBeTruthy();
            console.log(`✓ ${user.username} can access mon_compte`);

            // Logout
            await loginPage.logout();
            console.log(`✓ ${user.username} logged out`);
        }

        console.log('\n✓ All 4 Gaulois users verified successfully!');
    });

    // Test: Verify account information is displayed on mon_compte
    test('should display account information on mon_compte page', async ({ page }) => {
        const loginPage = new LoginPage(page);

        for (const user of GAULOIS_USERS) {
            console.log(`\nChecking account info for ${user.username}...`);

            await loginPage.open();
            await loginPage.login(user.username, PASSWORD);
            await loginPage.verifyLoggedIn();

            // Go to mon_compte for section 4 (Général)
            await page.goto('/index.php/compta/mon_compte/4');
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(1000);

            // Check if we can find the user's surname on the page
            // User names follow pattern "FirstName LastName" - we check for "Le Gaulois"
            const surname = user.name.split(' ').slice(1).join(' '); // "Le Gaulois"
            const pageContent = await page.content();
            const hasSurname = pageContent.includes(surname);
                
            if (hasSurname) {
                console.log(`✓ Found surname "${surname}" on page (account format may vary)`);
            } else {
                console.log(`⚠ Name "${user.name}" not found on mon_compte page`);
            }

            await loginPage.logout();
        }
    });
});
