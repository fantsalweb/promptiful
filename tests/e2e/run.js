/**
 * Arranca un DeepSeek simulado y la aplicación (servidor integrado de PHP con SQLite),
 * ejecuta tests/e2e/e2e.js y lo limpia todo. Requisitos: PHP 8.2+ con intl, mbstring y sqlite3,
 * y haber ejecutado `composer install`. Uso: npm run test:e2e   (PHP_BIN=ruta/a/php si no está en el PATH)
 */
const { spawn, spawnSync } = require('child_process');
const fs = require('fs');
const path = require('path');
const mock = require('./mock-deepseek');
const { runTests } = require('./e2e');

const ROOT = path.join(__dirname, '..', '..');
const REWRITE = path.join(ROOT, 'vendor', 'codeigniter4', 'framework', 'system', 'rewrite.php');
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

async function portInUse(port) {
  try { await fetch(`http://127.0.0.1:${port}/`); return true; } catch { return false; }
}

/** Mata el proceso y sus hijos (en Windows, `child.kill()` solo mata al padre y deja el servidor vivo). */
async function killTree(child) {
  if (!child || child.exitCode !== null) return;
  const exited = new Promise(resolve => child.once('exit', resolve));
  try {
    if (process.platform === 'win32') spawnSync('taskkill', ['/pid', String(child.pid), '/T', '/F'], { stdio: 'ignore' });
    else child.kill('SIGTERM');
  } catch { /* ya había terminado */ }
  await Promise.race([exited, new Promise(r => setTimeout(r, 3000))]);
}

/** Borra el archivo de la base de datos; si Windows aún lo tiene bloqueado, reintenta y, si no puede, avisa sin fallar. */
async function removeDb(file) {
  for (let i = 0; i < 6; i++) {
    try { fs.rmSync(file, { force: true }); return; } catch { await new Promise(r => setTimeout(r, 300)); }
  }
  console.warn(`Aviso: no se pudo borrar ${file}; se borrará en la próxima ejecución.`);
}

(async () => {
  const dbFile = path.join(ROOT, 'writable', DB);

  // Salvaguardas antes de tocar nada
  if (fs.existsSync(ENV_BACKUP)) {
    console.error(`Existe ${ENV_BACKUP}: es tu configuración real de una ejecución anterior que no terminó bien.\n` +
      'Restáurala (en PowerShell: Move-Item .env.e2e-backup .env -Force) y vuelve a lanzar la prueba.');
    process.exit(1);
  }
  if (await portInUse(APP_PORT) || await portInUse(MOCK_PORT)) {
    console.error(`Los puertos ${APP_PORT} o ${MOCK_PORT} están ocupados, probablemente por un servidor PHP de una prueba anterior.\n` +
      'Ciérralo (PowerShell: Get-CimInstance Win32_Process -Filter "Name=\'php.exe\'" | Select ProcessId, CommandLine, y luego Stop-Process -Id <id> -Force) y repite.');
    process.exit(1);
  }

  await removeDb(dbFile);
  installTestEnv();
  const restoreOnSignal = () => { restoreEnv(); process.exit(130); };
  process.on('SIGINT', restoreOnSignal);
  process.on('SIGTERM', restoreOnSignal);

  let mockServer = null;
  let app = null;
  let failures = 1;
  try {
    mockServer = await mock.start(MOCK_PORT);
    const mig = spawnSync(PHP, ['spark', 'migrate'], { cwd: ROOT, env, encoding: 'utf8' });
    if (mig.status !== 0) throw new Error('Falló la migración:\n' + mig.stdout + mig.stderr);
    // Mismo comando que `php spark serve`, pero directo: así solo hay un proceso que parar
    app = spawn(PHP, ['-S', `127.0.0.1:${APP_PORT}`, '-t', path.join(ROOT, 'public'), REWRITE], { cwd: ROOT, env, stdio: 'ignore' });
    await waitForServer(`http://127.0.0.1:${APP_PORT}/`);
    failures = await runTests(`http://127.0.0.1:${APP_PORT}`);
  } catch (e) {
    console.error(e);
  } finally {
    // Lo primero y a prueba de fallos: devolver tu .env; después, el resto de la limpieza
    try { restoreEnv(); } catch (e) { console.error('No se pudo restaurar el .env:', e.message); }
    try { await killTree(app); } catch { /* ignorar */ }
    try { if (mockServer) mockServer.close(); } catch { /* ignorar */ }
    await removeDb(dbFile);
  }
  process.exit(failures ? 1 : 0);
})();
