/**
 * Nettoyage des vols de découverte créés par les tests Playwright.
 *
 * Supprime les bons dont le bénéficiaire commence par l'un des préfixes donnés,
 * avec leur débit éventuel (achat « Vol découverte n°<id> - … », tickets et
 * écritures, soldes des comptes rétablis comme le fait delete_ecriture()) et
 * leur PDF stocké.
 */

const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');

const DB_CONFIG = {
    host: 'localhost',
    user: 'gvv_user',
    password: 'lfoyfgbj',
    database: 'gvv2',
};

/**
 * @param {string[]} prefixes préfixes de bénéficiaire propres au test (incluant un horodatage)
 * @returns {Promise<number>} nombre de bons supprimés
 */
async function deleteTestVds(prefixes) {
    const connection = await mysql.createConnection(DB_CONFIG);
    let count = 0;
    try {
        for (const prefix of prefixes.filter(Boolean)) {
            const [vds] = await connection.query(
                'SELECT id, pdf_path FROM vols_decouverte WHERE beneficiaire LIKE ?', [`${prefix}%`]);
            for (const vd of vds) {
                const [achats] = await connection.query(
                    'SELECT id FROM achats WHERE description LIKE ?', [`Vol découverte n°${vd.id} - %`]);
                for (const { id } of achats) {
                    const [ecritures] = await connection.query(
                        'SELECT id, compte1, compte2, montant FROM ecritures WHERE achat = ?', [id]);
                    for (const e of ecritures) {
                        await connection.query('UPDATE comptes SET debit = debit - ? WHERE id = ?', [e.montant, e.compte1]);
                        await connection.query('UPDATE comptes SET credit = credit - ? WHERE id = ?', [e.montant, e.compte2]);
                        await connection.query('DELETE FROM ecritures WHERE id = ?', [e.id]);
                    }
                    await connection.query('DELETE FROM tickets WHERE achat = ?', [id]);
                    await connection.query('DELETE FROM achats WHERE id = ?', [id]);
                }
                if (vd.pdf_path) {
                    const file = path.resolve(__dirname, '../../..', vd.pdf_path);
                    if (fs.existsSync(file)) fs.unlinkSync(file);
                }
                await connection.query('DELETE FROM vols_decouverte WHERE id = ?', [vd.id]);
                count++;
            }
        }
    } finally {
        await connection.end();
    }
    return count;
}

module.exports = { deleteTestVds };
