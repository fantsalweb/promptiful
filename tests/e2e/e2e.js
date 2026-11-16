/**
 * Prueba de extremo a extremo: carga la herramienta real (servidor CodeIgniter + base de datos)
 * en jsdom y la maneja como lo haría una persona. La API de DeepSeek está simulada.
 */
const { JSDOM } = require('jsdom');

/** «Navegador» con su propio almacén de cookies (cada uno es un visitante distinto). */
function makeBrowser(BASE) {
  const jar = new Map();
  const cookieHeader = () => [...jar].map(([k, v]) => `${k}=${v}`).join('; ');
  async function bfetch(url, opts = {}) {
    const res = await fetch(url, { ...opts, headers: { ...(opts.headers || {}), Cookie: cookieHeader() }, redirect: 'manual' });
    for (const c of res.headers.getSetCookie()) {
      const [pair] = c.split(';'); const i = pair.indexOf('=');
      jar.set(pair.slice(0, i).trim(), pair.slice(i + 1).trim());
    }
    return res;
  }
  async function open() {
    const resp = await bfetch(BASE + '/app');
    const html = await resp.text();
    if (!html.includes('id="quotaLine"')) throw new Error(`La herramienta no se cargó (HTTP ${resp.status}): ${html.slice(0, 600)}`);
    const dom = new JSDOM(html, {
      url: BASE + '/app', runScripts: 'dangerously', pretendToBeVisual: true,
      beforeParse(win) {
        win.fetch = bfetch;
        win.matchMedia = q => ({ matches: false, media: q, addEventListener() {}, removeListener() {} });
        win.HTMLDialogElement.prototype.showModal = function () { this.setAttribute('open', ''); };
        win.HTMLDialogElement.prototype.close = function () { this.removeAttribute('open'); };
        Object.defineProperty(win.HTMLDialogElement.prototype, 'open', { get() { return this.hasAttribute('open'); } });
        win.Element.prototype.scrollIntoView = () => {};
        win.scrollTo = () => {};
        win.confirm = () => true;
      },
    });
    return dom.window;
  }
  return { open, jar };
}

async function runTests(BASE) {
  let failures = 0;
  const ok = (cond, msg) => { console.log((cond ? 'OK    ' : 'FALLO ') + msg); if (!cond) failures++; };
  const waitFor = async (fn, ms = 8000) => {
    const t = Date.now();
    while (Date.now() - t < ms) { if (fn()) return true; await new Promise(r => setTimeout(r, 30)); }
    return false;
  };
  const A = makeBrowser(BASE);
  let w = await A.open();
  let d = w.document;
  const $ = id => d.getElementById(id);
  const names = () => [...d.querySelectorAll('.rec-item strong')].map(x => x.textContent);

  ok($('quotaLine').textContent === 'Te quedan 3 de 3 consultas de prueba gratuitas.', 'al entrar se muestra la prueba: 3 de 3');
  ok($('histMax').textContent === '5', 'la nota del historial muestra el máximo (5)');

  // 1) Flujo completo: petición -> preguntas -> respuestas -> prompt final
  $('basicPrompt').value = 'Hazme un cartel para un concierto';
  $('btnImprove').click();
  ok(await waitFor(() => !$('questionsPanel').hidden), 'aparecen las preguntas');
  const lab0 = d.querySelector('label[for="q0"]');
  ok(lab0.querySelector('.q-badge')?.textContent === 'Tema' && !d.querySelector('label[for="q1"] .q-badge'), 'badge "Tema" en la pregunta 1 y ninguno en la 2');
  ok($('quotaLine').textContent.includes('3 de 3'), 'las preguntas no gastan consultas');
  $('q0').value = 'PromptFull'; $('btnSendAnswers').click();
  ok(await waitFor(() => !$('resultPanel').hidden), 'se muestra el prompt final');
  ok($('output').value.includes('## Rol') && $('output').value.includes('- a\n- b'), 'el prompt tiene los apartados (y las listas se normalizan)');
  ok(JSON.stringify(names()) === JSON.stringify(['ChatGPT', 'Gemini', 'Copilot']), 'imagen: podio ChatGPT, Gemini y Copilot');
  ok(await waitFor(() => $('quotaLine').textContent.includes('2 de 3')), 'el prompt final gasta una consulta: quedan 2 de 3');

  // 2) Historial persistente en el servidor
  $('sideToggle').click();
  ok(await waitFor(() => d.querySelectorAll('.h-item').length === 1), 'el historial guarda la entrada');

  // 3) Regenerar cuenta como consulta y actualiza la misma entrada
  $('btnRegenerate').click();
  ok(await waitFor(() => $('quotaLine').textContent.includes('1 de 3')), 'regenerar gasta otra consulta: queda 1 de 3');
  await new Promise(r => setTimeout(r, 300));
  ok(d.querySelectorAll('.h-item').length === 1, 'regenerar no duplica la entrada del historial');

  // 4) Texto que necesita fuentes (la simulación responde sin preguntas)
  $('brandLink').click();
  $('basicPrompt').value = 'Resumen de noticias del sector';
  $('btnImprove').click();
  ok(await waitFor(() => !$('resultPanel').hidden), 'petición con noticias: llega el prompt final');
  ok(JSON.stringify(names()) === JSON.stringify(['Perplexity', 'ChatGPT', 'Claude']), 'texto + fuentes: podio Perplexity, ChatGPT y Claude');
  ok(await waitFor(() => $('quotaLine').textContent === 'Has agotado tus consultas de prueba gratuitas.'), 'tercera consulta: la prueba queda agotada');

  // 5) Cuarta consulta: el servidor la rechaza
  $('brandLink').click();
  $('basicPrompt').value = 'una más';
  $('btnImprove').click();
  ok(await waitFor(() => !$('error').hidden), 'la cuarta consulta muestra un error');
  ok($('error').textContent.includes('consultas de prueba') && $('resultPanel').hidden, 'el error explica la prueba agotada y no hay resultado');

  // 6) Recargar la página conserva el estado (cookie del visitante)
  w = await A.open(); d = w.document;
  ok($('quotaLine').textContent === 'Has agotado tus consultas de prueba gratuitas.', 'tras recargar, la prueba sigue agotada');
  $('sideToggle').click();
  ok(await waitFor(() => d.querySelectorAll('.h-item').length === 2), 'tras recargar, el historial conserva las 2 entradas');
  const fav = d.querySelectorAll('.h-actions .icon-btn')[0];
  fav.click();
  ok(await waitFor(() => d.querySelector('.h-group')?.textContent === 'Favoritos'), 'marcar favorito funciona contra el servidor');

  // 7) Otro navegador es otro visitante: su propia prueba y su propio historial
  const B = makeBrowser(BASE);
  const wb = await B.open(); const db = wb.document;
  ok(db.getElementById('quotaLine').textContent === 'Te quedan 3 de 3 consultas de prueba gratuitas.', 'otro navegador tiene su propia prueba de 3 consultas');
  db.getElementById('sideToggle').click();
  ok(await waitFor(() => db.querySelector('.h-empty')), 'otro navegador no ve el historial del primero');

  // 8) Sin token CSRF el servidor rechaza la petición
  const noCsrf = await fetch(BASE + '/api/generate', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: '{"messages":[{"role":"user","content":"x"}]}' });
  ok(noCsrf.status === 403, 'una petición POST sin token CSRF recibe 403');

  // 9) Respuesta rota de la IA: error claro y sin gastar consulta
  const wc = await B.open(); const dc = wc.document;
  dc.getElementById('basicPrompt').value = 'respuesta rota';
  dc.getElementById('btnImprove').click();
  ok(await waitFor(() => !dc.getElementById('error').hidden), 'si la IA devuelve algo inválido se muestra un error');
  ok(dc.getElementById('quotaLine').textContent.includes('3 de 3'), 'y no se descuenta ninguna consulta');

  console.log(failures ? `\n${failures} FALLO(S)` : '\nTODO OK');
  return failures;
}

module.exports = { runTests };
