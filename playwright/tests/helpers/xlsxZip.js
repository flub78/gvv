const { execFileSync } = require('child_process');

/**
 * Read one entry of a zip/xlsx file as text, via the system `unzip` binary
 * (avoids adding a new npm dependency just to inspect generated xlsx files
 * in tests).
 */
function readZipEntry(zipPath, entryName) {
  return execFileSync('unzip', ['-p', zipPath, entryName], { encoding: 'utf-8' });
}

module.exports = { readZipEntry };
