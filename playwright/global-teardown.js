// @ts-check
/**
 * Global teardown: restore the original sender email after all tests.
 */
const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');

const CONFIG_KEY = 'vd.email.sender_email';
const SAVE_FILE = path.join(__dirname, '.original_sender_email.tmp');

module.exports = async function globalTeardown() {
    if (!fs.existsSync(SAVE_FILE)) {
        console.warn('[global-teardown] save file not found, skipping restore');
        return;
    }

    const original = fs.readFileSync(SAVE_FILE, 'utf8');
    fs.unlinkSync(SAVE_FILE);

    const connection = await mysql.createConnection({
        host: 'localhost',
        user: 'gvv_user',
        password: 'lfoyfgbj',
        database: 'gvv2',
    });

    await connection.execute(
        'UPDATE configuration SET valeur = ? WHERE cle = ?',
        [original, CONFIG_KEY]
    );

    await connection.end();
    console.log(`[global-teardown] sender_email restored to: ${original}`);
};
