// @ts-check
/**
 * Global setup, before running tests:
 *  - stop immediately if the test users are missing (e.g. after a database
 *    restore), instead of letting every login fail;
 *  - clear the failed login attempts of the local IP addresses: past
 *    DX_max_login_attempts, the login page requires a CAPTCHA and every
 *    automated login fails;
 *  - replace production email with a test address. The original value is
 *    saved to a temp file to be restored by global-teardown.js.
 */
const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');
const { DB_CONFIG } = require('./tests/helpers/gvv-config');

const TEST_EMAIL = 'gvv-test@yopmail.com';
const CONFIG_KEY = 'vd.email.sender_email';
const SAVE_FILE = path.join(__dirname, '.original_sender_email.tmp');
const REQUIRED_TEST_USERS = ['testadmin', 'asterix', 'obelix', 'abraracourcix', 'goudurix', 'panoramix'];
const LOCAL_IPS = ['127.0.0.1', '::1'];

module.exports = async function globalSetup() {
    const connection = await mysql.createConnection(DB_CONFIG);

    const [users] = await connection.query(
        'SELECT username FROM users WHERE username IN (?)',
        [REQUIRED_TEST_USERS]
    );
    const found = users.map((u) => u.username);
    const missing = REQUIRED_TEST_USERS.filter((u) => !found.includes(u));
    if (missing.length > 0) {
        await connection.end();
        throw new Error(
            `[global-setup] Utilisateurs de test absents : ${missing.join(', ')}. ` +
            'Lancer bin/create_test_users.sh (par exemple après une restauration de la base).'
        );
    }

    const [cleared] = await connection.query(
        'DELETE FROM login_attempts WHERE ip_address IN (?)',
        [LOCAL_IPS]
    );
    if (cleared.affectedRows > 0) {
        console.log(`[global-setup] ${cleared.affectedRows} failed login attempt(s) cleared for ${LOCAL_IPS.join(', ')}`);
    }

    // Save original value to temp file
    const [rows] = await connection.execute(
        'SELECT valeur FROM configuration WHERE cle = ?',
        [CONFIG_KEY]
    );
    const original = rows.length > 0 ? rows[0].valeur : '';
    fs.writeFileSync(SAVE_FILE, original, 'utf8');

    // Replace with test address
    await connection.execute(
        'UPDATE configuration SET valeur = ? WHERE cle = ?',
        [TEST_EMAIL, CONFIG_KEY]
    );

    await connection.end();
    console.log(`[global-setup] sender_email set to ${TEST_EMAIL} (was: ${original})`);
};
