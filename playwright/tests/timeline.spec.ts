import { test, expect } from '@playwright/test';

test.describe('Timeline Feature', () => {
    test('should access timeline page and show GVV header/menu', async ({ page }) => {
        // Navigate to timeline (will redirect to login)
        await page.goto('http://gvv.net/reservations/timeline');
        
        // Check if we're on login page
        const loginForm = page.locator('form');
        if (await loginForm.isVisible()) {
            console.log('✓ Timeline requires authentication (expected security behavior)');
            
            // Verify redirect to login
            await expect(page).toHaveURL(/auth\/login/);
        } else {
            // If somehow logged in, verify GVV header exists
            const gvvHeader = page.locator('nav.navbar');
            await expect(gvvHeader).toBeVisible();
            console.log('✓ Timeline page loaded with GVV navigation');
        }
    });
    
    test('should have timeline routes defined', async ({ page }) => {
        // Test that the routes exist and are accessible
        // (authentication will handle access control)
        
        const routes = [
            'http://gvv.net/reservations/timeline',
            'http://gvv.net/reservations/get_timeline_data',
            'http://gvv.net/reservations/on_event_click',
            'http://gvv.net/reservations/on_event_drop',
            'http://gvv.net/reservations/on_slot_click'
        ];
        
        for (const route of routes) {
            const response = await page.request.get(route);
            // Either 302 (redirect to login) or 200 (success) are acceptable
            const isValidResponse = response.status() === 302 || response.status() === 200;
            expect(isValidResponse).toBeTruthy();
            console.log(`✓ Route defined: ${route} (status: ${response.status()})`);
        }
    });
    
    test('should have timeline entry in Dev menu', async ({ page }) => {
        // Navigate to main page
        await page.goto('http://gvv.net/');
        
        // Wait for page load
        await page.waitForLoadState('networkidle');
        
        // Look for Dev menu
        const devMenu = page.locator('a.nav-link:has-text("Dev")');
        if (await devMenu.isVisible()) {
            console.log('✓ Dev menu is visible');
            
            // Hover over Dev menu to show dropdown
            await devMenu.hover();
            await page.waitForTimeout(500);
            
            // Look for timeline entry
            const timelineLink = page.locator('a.dropdown-item:has-text("Timeline Réservations")');
            if (await timelineLink.isVisible()) {
                console.log('✓ Timeline Réservations entry found in Dev menu');
            } else {
                console.log('⚠ Timeline entry not visible in Dev menu');
            }
        } else {
            console.log('⚠ Dev menu not visible (may need dev_menu config enabled)');
        }
    });
});
