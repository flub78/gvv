/**
 * Reads GVV PHP configuration values for use in Playwright tests.
 *
 * USE_NEW_AUTHORIZATION mirrors $config['use_new_authorization'] from
 * application/config/gvv_config.php. When true, legacy test users
 * (testuser, testca, testbureau, testplanchiste, testtresorier) are not
 * reliable — their DX_Auth roles are not mapped into user_roles_per_section
 * the same way as Gaulois users, so tests that depend on them should be
 * skipped with: test.skip(USE_NEW_AUTHORIZATION, SKIP_LEGACY_USERS_REASON)
 *
 * DB_CONFIG holds the MySQL connection settings of the 'default' group in
 * application/config/database.php (not tracked by git), in the format
 * expected by mysql2.createConnection(). Tests must use it instead of
 * hard-coding database credentials.
 */

const fs = require('fs');
const path = require('path');

const configPath = path.resolve(__dirname, '../../../application/config/gvv_config.php');

function readUseNewAuthorization() {
  try {
    const content = fs.readFileSync(configPath, 'utf8');
    const match = content.match(/\$config\['use_new_authorization'\]\s*=\s*(true|false)\s*;/);
    return match ? match[1] === 'true' : false;
  } catch (e) {
    return false;
  }
}

const USE_NEW_AUTHORIZATION = readUseNewAuthorization();

const databasePath = path.resolve(__dirname, '../../../application/config/database.php');

function readDbConfig() {
  const content = fs.readFileSync(databasePath, 'utf8');
  const value = (key) => {
    const re = new RegExp("^\\s*\\$db\\['default'\\]\\['" + key + "'\\]\\s*=\\s*'([^']*)'\\s*;", 'm');
    const match = content.match(re);
    if (!match) {
      throw new Error(`$db['default']['${key}'] not found in ${databasePath}`);
    }
    return match[1];
  };
  return {
    host: value('hostname'),
    user: value('username'),
    password: value('password'),
    database: value('database'),
  };
}

const DB_CONFIG = readDbConfig();

const SKIP_LEGACY_USERS_REASON =
  'Legacy test users (testuser/testca/testbureau/testplanchiste/testtresorier) ' +
  'are not applicable when use_new_authorization=true';

module.exports = { USE_NEW_AUTHORIZATION, SKIP_LEGACY_USERS_REASON, DB_CONFIG };
