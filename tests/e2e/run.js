/**
 * Arranca un DeepSeek simulado y la aplicación (php spark serve con SQLite en memoria de disco),
 * ejecuta tests/e2e/e2e.js y lo limpia todo. Requisitos: PHP 8.2+ con intl, mbstring y sqlite3,
 * y haber ejecutado `composer install`. Uso: npm run test:e2e   (PHP_BIN=ruta/a/php si no está en el PATH)
 */
const { spawn, spawnSync } = require('child_process');
const fs = require('fs');
const path = require('path');
const mock = require('./mock-deepseek');
const { runTests } = require('./e2e');

const ROOT = path.join(__dirname, '..', '..');
const PHP = process.env.PHP_BIN || 'php';
const APP_PORT = 8765;
const MOCK_PORT = 8099;
const DB = 'e2e.sqlite';

// El servidor integrado de PHP no pasa a CodeIgniter las variables de entorno con puntos,
// así que la configuración de la prueba se escribe en un .env temporal (el tuyo se guarda y se restaura).
const ENV_FILE = path.join(ROOT, '.env');
const ENV_BACKUP = path.join(ROOT, '.env.e2e-backup');
const TEST_ENV = [
  'CI_ENVIRONMENT = development',
  `app.baseURL = 'http://127.0.0.1:${APP_PORT}/'`,
  'database.default.DBDriver = SQLite3',
  `database.default.database = ${DB}`,
  'promptiful.deepseekApiKey = test-key',
  `promptiful.deepseekApiUrl = http://127.0.0.1:${MOCK_PORT}/chat/completions`,
  'promptiful.ipSalt = e2e-salt',
].join('\n') + '\n';
const env = { ...process.env };

function installTestEnv() {
  if (fs.existsSync(ENV_FILE)) fs.renameSync(ENV_FILE, ENV_BACKUP);
  fs.writeFileSync(ENV_FILE, TEST_ENV);
}
function restoreEnv() {
  fs.rmSync(ENV_FILE, { force: true });
  if (fs.existsSync(ENV_BACKUP)) fs.renameSync(ENV_BACKUP, ENV_FILE);
}

async function waitForServer(url, ms = 20000) {
  const t = Date.now();
  while (Date.now() - t < ms) {
    try { const r = await fetch(url); if (r.status < 500) return; } catch { /* aún arrancando */ }
    await new Promise(r => setTimeout(r, 250));
  }
  throw new Error('La aplicación no arrancó a tiempo: revisa que PHP y las dependencias (composer install) estén bien.');
}

(async () => {
  const dbFile = path.join(ROOT, 'writable', DB);
  fs.rmSync(dbFile, { force: true });
  installTestEnv();
  process.on('SIGINT', () => { restoreEnv(); process.exit(130); });
  const mockServer = await mock.start(MOCK_PORT);
  let app = null;
  let failures = 1;
  try {
    const mig = spawnSync(PHP, ['spark', 'migrate'], { cwd: ROOT, env, encoding: 'utf8' });
    if (mig.status !== 0) throw new Error('Falló la migración:\n' + mig.stdout + mig.stderr);
    app = spawn(PHP, ['spark', 'serve', '--host', '127.0.0.1', '--port', String(APP_PORT)], { cwd: ROOT, env, stdio: 'ignore' });
    await waitForServer(`http://127.0.0.1:${APP_PORT}/`);
    failures = await runTests(`http://127.0.0.1:${APP_PORT}`);
  } catch (e) {
    console.error(e);
  } finally {
    if (app) app.kill();
    mockServer.close();
    fs.rmSync(dbFile, { force: true });
    restoreEnv();
  }
  process.exit(failures ? 1 : 0);
})();
