const { test, expect } = require('@playwright/test');
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const LoginPage = require('./helpers/LoginPage');

/**
 * ULM Billing Scenarios - End-to-end Playwright test
 *
 * Purpose:
 * - Validate ULM billing behavior on all active ULM machines.
 * - Validate pilot billing impact by checking 411 account balance before and after each flight.
 *
 * Coverage:
 * - Scenarios: standard, instruction, owner, trial, towing, discovery, BIA, open day.
 * - Machines: all active ULM aircraft from table machinesa (club/section ULM).
 *
 * Billing checks:
 * - Standard flights: billed with machine hourly tariff.
 * - Instruction flights: billed with hourly tariff + double-command supplement when defined.
 * - Owner flights: billed with owner tariff (machine-specific maprixproprio or fallback hdv_proprio).
 * - Trial, towing, discovery, BIA, open-day flights: expected to be free for pilot (delta 0 on account 411).
 *
 * Data source for expected amounts:
 * - Tariffs are read from table tarifs using reference + section + flight date.
 * - Account balance is read from table comptes (debit - credit) on pilot 411 account for ULM section.
 */

const TEST_ADMIN = { username: 'testadmin', password: 'password', section: '2' }; // ULM
const PILOT_LOGIN = 'obelix';
const ULM_SECTION_ID = 2;

const FLIGHT_DURATION_HOURS = 1.0;

const SCENARIOS = [
  { key: 'standard', label: 'Vol standard', category: 0, instruction: false, freeForPilot: false, ownerTariff: false },
  { key: 'instruction', label: 'Vol instruction', category: 0, instruction: true, freeForPilot: false, ownerTariff: false },
  { key: 'owner', label: 'Vol proprietaire', category: 4, instruction: false, freeForPilot: false, ownerTariff: true },
  { key: 'trial', label: 'Vol essai', category: 2, instruction: false, freeForPilot: true, ownerTariff: false },
  { key: 'towing', label: 'Remorquage', category: 3, instruction: false, freeForPilot: true, ownerTariff: false },
  { key: 'discovery', label: 'Vol decouverte', category: 1, instruction: false, freeForPilot: true, ownerTariff: false },
  { key: 'bia', label: 'Vol BIA', category: 6, instruction: false, freeForPilot: true, ownerTariff: false },
  { key: 'open_day', label: 'Vol porte ouverte', category: 5, instruction: false, freeForPilot: true, ownerTariff: false }
];

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

function toNumber(value) {
  return Number.parseFloat(value || '0') || 0;
}

function round2(value) {
  return Math.round(value * 100) / 100;
}

function getPilot411AccountId(pilotLogin, sectionId) {
  const rows = mysqlRows(
    `SELECT id FROM comptes WHERE pilote = '${escapeSqlString(pilotLogin)}' AND codec = '411' AND club = ${sectionId} LIMIT 1`
  );
  if (rows.length === 0) {
    throw new Error(`No 411 account found for pilot=${pilotLogin} in section=${sectionId}`);
  }
  return Number.parseInt(rows[0][0], 10);
}

function getAccountBalance(accountId) {
  const rows = mysqlRows(`SELECT ROUND(debit - credit, 2) FROM comptes WHERE id = ${accountId}`);
  if (rows.length === 0) {
    throw new Error(`Account not found: ${accountId}`);
  }
  return toNumber(rows[0][0]);
}

function getUlmMachines(sectionId) {
  const rows = mysqlRows(
    `SELECT macimmat, maprix, IFNULL(maprixdc, ''), IFNULL(maprixproprio, ''), maprive
     FROM machinesa
     WHERE actif = 1 AND club = ${sectionId}
     ORDER BY macimmat`
  );

  return rows.map((r) => ({
    immat: r[0],
    hourlyRef: r[1],
    dcRef: r[2],
    ownerRef: r[3],
    privateFlag: Number.parseInt(r[4], 10) || 0
  }));
}

function getTariffPrice(reference, date, sectionId) {
  if (!reference) {
    return 0;
  }

  const rows = mysqlRows(
    `SELECT prix
     FROM tarifs
     WHERE reference = '${escapeSqlString(reference)}'
       AND club = ${sectionId}
       AND date <= '${escapeSqlString(date)}'
     ORDER BY date DESC
     LIMIT 1`
  );

  if (rows.length === 0) {
    return 0;
  }

  return toNumber(rows[0][0]);
}

function expectedDeltaForScenario(machine, scenario, date) {
  if (scenario.freeForPilot) {
    return {
      expected: 0,
      baseRef: null,
      basePrice: 0,
      dcRef: null,
      dcPrice: 0
    };
  }

  const baseRef = scenario.ownerTariff
    ? (machine.ownerRef || 'hdv_proprio')
    : machine.hourlyRef;
  const basePrice = getTariffPrice(baseRef, date, ULM_SECTION_ID);

  let dcRef = null;
  let dcPrice = 0;

  if (scenario.instruction && machine.dcRef) {
    dcRef = machine.dcRef;
    dcPrice = getTariffPrice(dcRef, date, ULM_SECTION_ID);
  }

  const expected = round2(FLIGHT_DURATION_HOURS * (basePrice + dcPrice));

  return {
    expected,
    baseRef,
    basePrice,
    dcRef,
    dcPrice
  };
}

function findFlightIdByObservation(observation, sectionId) {
  const rows = mysqlRows(
    `SELECT vaid FROM volsa WHERE vaobs = '${escapeSqlString(observation)}' AND club = ${sectionId} ORDER BY vaid DESC LIMIT 1`
  );
  if (rows.length === 0) {
    return null;
  }
  return Number.parseInt(rows[0][0], 10);
}

async function selectOptionIfExists(page, selector, value) {
  const option = page.locator(`${selector} option[value="${value}"]`);
  await expect(option, `Missing option ${value} for selector ${selector}`).toHaveCount(1);
  await page.selectOption(selector, String(value));
}

async function setCheckbox(page, selector, checked) {
  const checkbox = page.locator(selector);
  if ((await checkbox.count()) === 0) {
    return;
  }
  if (checked) {
    await checkbox.check();
  } else {
    await checkbox.uncheck();
  }
}

async function createUlmFlight(page, machine, scenario, flightDate, uniqueTag) {
  await page.goto('/index.php/vols_avion/create');
  await page.waitForLoadState('networkidle');

  const flightDateFr = flightDate.split('-').reverse().join('/');
  await page.fill('input[name="vadate"]', flightDateFr);
  await selectOptionIfExists(page, 'select[name="vamacid"]', machine.immat);
  await selectOptionIfExists(page, 'select[name="vapilid"]', PILOT_LOGIN);
  await selectOptionIfExists(page, 'select[name="vacategorie"]', scenario.category);

  // Keep explicit wall-clock values for UI readability.
  await page.fill('input[name="vahdeb"]', '10:00');
  await page.fill('input[name="vahfin"]', '11:00');
  await page.fill('input[name="vaatt"]', '1');

  const vacdebRaw = await page.locator('input[name="vacdeb"]').inputValue();
  const vacdeb = toNumber(vacdebRaw);
  const vacfin = round2(vacdeb + 1);

  await page.evaluate((args) => {
    const debut = document.querySelector('input[name="vacdeb"]');
    const fin = document.querySelector('input[name="vacfin"]');
    const duree = document.querySelector('input[name="vaduree"]');
    if (debut) debut.value = args.vacdeb;
    if (fin) fin.value = args.vacfin;
    if (duree) duree.value = '1';
  }, {
    vacdeb: String(vacdeb),
    vacfin: String(vacfin)
  });

  await setCheckbox(page, 'input[type="checkbox"][name="vadc"], #vadc', !!scenario.instruction);

  const observation = `PW_ULM_BILLING_${uniqueTag}_${machine.immat}_${scenario.key}`;
  await page.fill('textarea[name="vaobs"], input[name="vaobs"]', observation);

  await page.click('button[type="submit"], input[type="submit"]');
  await page.waitForLoadState('networkidle');

  const bodyText = await page.textContent('body');
  expect(bodyText).not.toContain('Fatal error');
  expect(bodyText).not.toContain('A PHP Error was encountered');
  expect(bodyText).not.toContain('An uncaught Exception was encountered');

  // Persistency check: the flight must exist in table volsa.
  let createdFlightId = null;
  for (let i = 0; i < 10; i += 1) {
    createdFlightId = findFlightIdByObservation(observation, ULM_SECTION_ID);
    if (createdFlightId) {
      break;
    }
    await page.waitForTimeout(300);
  }

  expect(createdFlightId, `Flight not found in volsa for observation=${observation}`).not.toBeNull();
}

// Computed once at module load (synchronous DB calls via execSync).
const TODAY = new Date().toISOString().slice(0, 10);
const UNIQUE_TAG = String(Date.now());
const MACHINES = getUlmMachines(ULM_SECTION_ID);
const PILOT_ACCOUNT_ID = getPilot411AccountId(PILOT_LOGIN, ULM_SECTION_ID);

// One entry per (machine × scenario) combination, used as test.each input.
const COMBINATIONS = MACHINES.flatMap((machine) =>
  SCENARIOS.map((scenario) => ({ machine, scenario }))
);

test.describe('ULM billing scenarios on all machines', () => {
  // Serial mode is required: tests share the pilot 411 account balance.
  // Parallel execution would corrupt before/after delta measurements.
  test.describe.configure({ mode: 'serial' });

  test.beforeEach(async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(TEST_ADMIN.username, TEST_ADMIN.password, TEST_ADMIN.section);
    const sectionValue = await page.locator('select[name="section"]').inputValue();
    expect(sectionValue).toBe(TEST_ADMIN.section);
  });

  for (const { machine, scenario } of COMBINATIONS) {
    test(`machine=${machine.immat} scenario=${scenario.key}`, async ({ page }) => {
      const before = getAccountBalance(PILOT_ACCOUNT_ID);
      const expectedInfo = expectedDeltaForScenario(machine, scenario, TODAY);

      await createUlmFlight(page, machine, scenario, TODAY, UNIQUE_TAG);

      const after = getAccountBalance(PILOT_ACCOUNT_ID);
      const delta = round2(after - before);

      expect(
        delta,
        [
          `machine=${machine.immat}`,
          `scenario=${scenario.key}`,
          `before=${before}`,
          `after=${after}`,
          `delta=${delta}`,
          `expected=${expectedInfo.expected}`,
          `baseRef=${expectedInfo.baseRef}`,
          `basePrice=${expectedInfo.basePrice}`,
          `dcRef=${expectedInfo.dcRef}`,
          `dcPrice=${expectedInfo.dcPrice}`
        ].join(' | ')
      ).toBe(expectedInfo.expected);
    });
  }
});
