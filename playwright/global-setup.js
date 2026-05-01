// @ts-check
/**
 * Global setup: replace production email with a test address before running tests.
 * The original value is saved to a temp file to be restored by global-teardown.js.
 */
const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');

const TEST_EMAIL = 'gvv-test@yopmail.com';
const CONFIG_KEY = 'vd.email.sender_email';
const SAVE_FILE = path.join(__dirname, '.original_sender_email.tmp');

module.exports = async function globalSetup() {
    const connection = await mysql.createConnection({
        host: 'localhost',
        user: 'gvv_user',
        password: 'lfoyfgbj',
        database: 'gvv2',
    });

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
