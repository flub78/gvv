/**
 * Lot 5 (doc/plans/journalisation_crud_plan.md §2.3) — regression test.
 *
 * Bug: Common_Model::inject_audit_fields() used a plain isset() guard. A field
 * absent from the submitted HTML form comes back from CodeIgniter's
 * $this->input->post($field) as boolean FALSE (not NULL). Gvv_Controller::
 * form2database() puts that FALSE straight into the data array for every raw
 * DB column of the table — including created_at/created_by, which are never
 * rendered as inputs. isset(FALSE) is TRUE, so the auto-population was
 * silently skipped: every flight entered through the real web form got
 * created_at = '0000-00-00 00:00:00' and created_by = '0'.
 *
 * A model-level test cannot catch this (calling create() directly never
 * reproduces the FALSE values that only form2database() injects) — this must
 * go through the real HTML form.
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/audit-fields-vols-avion-create.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const LOGIN_URL = '/index.php/auth/login';
const CREATE_URL = '/index.php/vols_avion/create';

const ADMIN = { username: 'testadmin', password: 'password' };
const PILOT_LOGIN = 'obelix';
const MACHINE = 'F-JUFA'; // remorqueur, club=1 (Planeur) — matches testadmin's default section

function parsePhpDatabaseConfig() {
    const configPath = path.resolve(__dirname, '../../application/config/database.php');
    const content = fs.readFileSync(configPath, 'utf8');

    const readValue = (key) => {
        const regex = new RegExp(`\\$db\\['default'\\]\\['${key}'\\]\\s*=\\s*'([^']*)'`);
        const match = content.match(regex);
        if (!match) {
            throw new Error(`Unable to read database.${key} from ${configPath}`);
        }
        return match[1];
    };

    return {
        host: readValue('hostname'),
        user: readValue('username'),
        password: readValue('password'),
        database: readValue('database')
    };
}

const DB = parsePhpDatabaseConfig();

function escapeSqlString(value) {
    return String(value)
        .replace(/\\/g, '\\\\')
        .replace(/'/g, "\\'")
        .replace(/\0/g, '\\0')
        .replace(/\n/g, '\\n')
        .replace(/\r/g, '\\r')
        .replace(/\x1a/g, '\\Z');
}

function mysqlRows(sql) {
    const escapedSql = sql.replace(/"/g, '\\"');
    const command = `mysql -N -B -h${DB.host} -u${DB.user} -p${DB.password} ${DB.database} -e "${escapedSql}"`;
    const output = execSync(command, { encoding: 'utf8' }).trim();
    if (!output) {
        return [];
    }
    return output.split('\n').map((line) => line.split('\t'));
}

async function login(page, user) {
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

async function selectOptionIfExists(page, selector, value) {
    const option = page.locator(`${selector} option[value="${value}"]`);
    await expect(option, `Missing option ${value} for selector ${selector}`).toHaveCount(1);
    await page.selectOption(selector, String(value));
}

async function createFlightViaForm(page, uniqueTag) {
    await page.goto(CREATE_URL);
    await page.locator('input[name="vadate"]').waitFor();

    const today = new Date().toISOString().slice(0, 10);
    const dateFr = today.split('-').reverse().join('/');
    await page.fill('input[name="vadate"]', dateFr);

    await selectOptionIfExists(page, 'select[name="vamacid"]', MACHINE);
    await selectOptionIfExists(page, 'select[name="vapilid"]', PILOT_LOGIN);
    await page.selectOption('select[name="vacategorie"]', '0');

    await page.fill('input[name="vahdeb"]', '10:00');
    await page.fill('input[name="vahfin"]', '11:00');
    await page.fill('input[name="vaatt"]', '1');

    // Base the horamètre on the machine's last recorded value, like the ULM billing spec does,
    // to satisfy any consistency validation on vacdeb/vacfin.
    const lastVacfin = mysqlRows(
        `SELECT IFNULL(MAX(vacfin), 0) FROM volsa WHERE vamacid = '${escapeSqlString(MACHINE)}'`
    );
    const vacdeb = Number.parseFloat(lastVacfin[0][0]) || 0;
    const vacfin = Math.round((vacdeb + 1) * 100) / 100;

    await page.evaluate((args) => {
        const debut = document.querySelector('input[name="vacdeb"]');
        const fin = document.querySelector('input[name="vacfin"]');
        const duree = document.querySelector('input[name="vaduree"]');
        if (debut) debut.value = args.vacdeb;
        if (fin) fin.value = args.vacfin;
        if (duree) duree.value = '1';
    }, { vacdeb: String(vacdeb), vacfin: String(vacfin) });

    await page.fill('textarea[name="vaobs"], input[name="vaobs"]', uniqueTag);

    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('domcontentloaded');

    const bodyText = await page.textContent('body');
    expect(bodyText).not.toContain('Fatal error');
    expect(bodyText).not.toContain('A PHP Error was encountered');
    expect(bodyText).not.toContain('An uncaught Exception was encountered');

    let row = [];
    await expect.poll(
        () => {
            row = mysqlRows(
                `SELECT vaid, created_by, CAST(created_at AS CHAR) FROM volsa WHERE vaobs = '${escapeSqlString(uniqueTag)}'`
            );
            return row.length;
        },
        { message: `Flight not found in volsa for vaobs=${uniqueTag}`, timeout: 5000 }
    ).toBeGreaterThan(0);

    return { vaid: row[0][0], createdBy: row[0][1], createdAt: row[0][2] };
}

test.describe('Lot 5 - created_at/created_by on real vols_avion form submission', () => {
    // Both tests submit a flight for the same pilot/machine/time slot; running them
    // in parallel workers trips the is_person_in_flight()/is_machine_in_flight()
    // overlap check and one submission gets silently rejected.
    test.describe.configure({ mode: 'serial' });

    test('a flight entered through the real create form gets real audit fields, not zero-date/0', async ({ page }) => {
        await login(page, ADMIN);

        const uniqueTag = `PW_AUDIT_LOT5_${Date.now()}`;
        const { vaid, createdBy, createdAt } = await createFlightViaForm(page, uniqueTag);

        try {
            expect(createdAt, 'created_at must not be the zero-date sentinel').not.toBe('0000-00-00 00:00:00');
            expect(createdAt, 'created_at must not be empty/NULL').toBeTruthy();
            expect(createdBy, 'created_by must not be the placeholder "0"').not.toBe('0');
            expect(createdBy, 'created_by must not be empty/NULL').toBeTruthy();
        } finally {
            // Clean up through the application endpoint (not a raw DELETE) so any
            // billing cascade this flight triggered is properly reversed.
            await page.goto(`/index.php/vols_avion/delete/${vaid}`);
            await page.waitForLoadState('domcontentloaded');
        }
    });

    /**
     * Regression test: found in production after the Lot 5 fix shipped. Editing
     * vols_avion vaid=16631 as its own auto_planchiste owner reset created_by
     * from 'fpeignot' back to '0' and created_at back to the zero-date. The fix
     * only guarded the create() branch of inject_audit_fields(); on update(),
     * form2database() supplies the same poisoned FALSE for created_at/created_by
     * (no HTML input for them either), and it sailed straight into the UPDATE.
     */
    test('editing an existing flight through the real form preserves its original created_at/created_by', async ({ page }) => {
        await login(page, ADMIN);

        const uniqueTag = `PW_AUDIT_LOT5_EDIT_${Date.now()}`;
        const created = await createFlightViaForm(page, uniqueTag);

        try {
            await page.goto(`/index.php/vols_avion/edit/${created.vaid}`);
            await page.locator('input[name="vadate"]').waitFor();

            const editedObs = `${uniqueTag}_edited`;
            await page.fill('textarea[name="vaobs"], input[name="vaobs"]', editedObs);
            await page.click('button[type="submit"], input[type="submit"]');
            await page.waitForLoadState('domcontentloaded');

            const bodyText = await page.textContent('body');
            expect(bodyText).not.toContain('Fatal error');
            expect(bodyText).not.toContain('A PHP Error was encountered');

            const row = mysqlRows(
                `SELECT created_by, CAST(created_at AS CHAR), vaobs FROM volsa WHERE vaid = ${Number(created.vaid)}`
            );
            expect(row.length, 'Flight should still exist after edit').toBeGreaterThan(0);
            const [createdByAfter, createdAtAfter, vaobsAfter] = row[0];

            expect(vaobsAfter, 'the edit itself must have been applied').toBe(editedObs);
            expect(createdAtAfter, 'created_at must survive an edit unchanged').toBe(created.createdAt);
            expect(createdByAfter, 'created_by must survive an edit unchanged').toBe(created.createdBy);
        } finally {
            await page.goto(`/index.php/vols_avion/delete/${created.vaid}`);
            await page.waitForLoadState('domcontentloaded');
        }
    });
});
