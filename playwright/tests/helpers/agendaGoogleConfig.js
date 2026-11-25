/**
 * Réglages de program.php pour les tests de la synchronisation Google Agenda.
 *
 * Active le bouchon qui remplace Google (plateforme de test uniquement) et la
 * fonction pour le club, puis restaure exactement les valeurs d'origine des
 * clés touchées (les autres lignes du fichier ne sont jamais modifiées).
 */
const fs = require('fs');
const path = require('path');

const PROGRAM_FILE = path.join(__dirname, '..', '..', '..', 'application', 'config', 'program.php');

function keyPattern(key) {
    return new RegExp(`^\\$config\\['${key}'\\]\\s*=.*;[^\\n]*\\n?`, 'm');
}

function literal(value) {
    if (value === true) return 'TRUE';
    if (value === false) return 'FALSE';
    return `'${String(value).replace(/\\/g, '\\\\').replace(/'/g, "\\'")}'`;
}

/**
 * Écrit les clés demandées et rend une fonction de restauration.
 * @param {Object} values clé => valeur (booléen ou chaîne)
 */
function applyProgramConfig(values) {
    const original = fs.readFileSync(PROGRAM_FILE, 'utf8');
    const previous = {};
    let content = original;
    for (const [key, value] of Object.entries(values)) {
        const match = content.match(keyPattern(key));
        previous[key] = match ? match[0] : null;
        const line = `$config['${key}'] = ${literal(value)};\n`;
        content = match ? content.replace(keyPattern(key), line) : content.replace(/\?>\s*$/, '') + (content.endsWith('\n') ? '' : '\n') + line;
    }
    fs.writeFileSync(PROGRAM_FILE, content);

    return function restore() {
        let current = fs.readFileSync(PROGRAM_FILE, 'utf8');
        for (const [key, line] of Object.entries(previous)) {
            current = line === null ? current.replace(keyPattern(key), '') : current.replace(keyPattern(key), line);
        }
        fs.writeFileSync(PROGRAM_FILE, current);
    };
}

/**
 * Réglages complets : fonction active, application déclarée, bouchon actif.
 */
function enableAgendaGoogleWithBouchon(overrides = {}) {
    return applyProgramConfig(Object.assign({
        agenda_google_sync: true,
        agenda_google_client_id: 'bouchon-client.apps.googleusercontent.com',
        agenda_google_client_secret: 'bouchon-secret',
        agenda_google_bouchon: true,
    }, overrides));
}

/**
 * Attend que le serveur relise program.php (cache d'opcodes) : le bouchon
 * répond autre chose que 404 quand il est actif.
 */
async function waitForBouchon(request, active = true) {
    for (let i = 0; i < 40; i++) {
        const resp = await request.get('/index.php/agenda_google_bouchon/autoriser');
        if ((resp.status() !== 404) === active) return;
        await new Promise(r => setTimeout(r, 250));
    }
    throw new Error(`Le bouchon Google Agenda ne passe pas à l'état ${active ? 'actif' : 'inactif'}`);
}

module.exports = { applyProgramConfig, enableAgendaGoogleWithBouchon, waitForBouchon };
