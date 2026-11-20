/**
 * Prueba de extremo a extremo: carga la herramienta real (servidor CodeIgniter + base de datos)
 * en jsdom y la maneja como lo haría una persona. La API de DeepSeek está simulada.
 */
const { JSDOM, VirtualConsole } = require('jsdom');
const fs = require('fs');
const path = require('path');

/** Consola de jsdom: se ignora el aviso esperado de «navegación no implementada» (la landing redirige a /app). */
function quietConsole() {
  const vc = new VirtualConsole();
  vc.on('jsdomError', err => { if (!/Not implemented: navigation/.test(err.message)) console.error(err); });
  return vc;
}

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
  async function open(opts = {}) {
    const resp = await bfetch(BASE + '/app');
    const html = await resp.text();
    if (!html.includes('id="quotaLine"')) throw new Error(`La herramienta no se cargó (HTTP ${resp.status}): ${html.slice(0, 600)}`);
    const dom = new JSDOM(html, {
      url: BASE + '/app', runScripts: 'dangerously', pretendToBeVisual: true, virtualConsole: quietConsole(),
      beforeParse(win) {
        if (opts.idea) win.sessionStorage.setItem('pf_idea', opts.idea);
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
  async function openLanding() {
    const res = await bfetch(BASE + '/');
    const html = await res.text();
    const dom = new JSDOM(html, { url: BASE + '/', runScripts: 'dangerously', pretendToBeVisual: true, virtualConsole: quietConsole() });
    return dom.window;
  }
  return { open, openLanding, jar };
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

  ok($('quotaLine').textContent === 'Te quedan 5 de 5 consultas de prueba gratuitas.', 'al entrar se muestra la prueba: 5 de 5');
  ok($('histMax').textContent === '5', 'la nota del historial muestra el máximo (5)');

  // 1) Flujo completo: petición -> preguntas -> respuestas -> prompt final
  $('basicPrompt').value = 'Hazme un cartel para un concierto';
  $('btnImprove').click();
  ok(await waitFor(() => !$('questionsPanel').hidden), 'aparecen las preguntas');
  const lab0 = d.querySelector('label[for="q0"]');
  ok(lab0.querySelector('.q-badge')?.textContent === 'Tema' && !d.querySelector('label[for="q1"] .q-badge'), 'badge "Tema" en la pregunta 1 y ninguno en la 2');
  ok($('quotaLine').textContent.includes('5 de 5'), 'las preguntas no gastan consultas');
  $('q0').value = 'PromptFull'; $('btnSendAnswers').click();
  ok(await waitFor(() => !$('resultPanel').hidden), 'se muestra el prompt final');
  ok($('output').value.includes('## Rol') && $('output').value.includes('- a\n- b'), 'el prompt tiene los apartados (y las listas se normalizan)');
  ok(JSON.stringify(names()) === JSON.stringify(['ChatGPT', 'Gemini', 'Copilot']), 'imagen: podio ChatGPT, Gemini y Copilot');
  ok(await waitFor(() => $('quotaLine').textContent.includes('4 de 5')), 'el prompt final gasta una consulta: quedan 4 de 5');

  // 2) Historial persistente en el servidor
  $('sideToggle').click();
  ok(await waitFor(() => d.querySelectorAll('.h-item').length === 1), 'el historial guarda la entrada');

  // 3) Regenerar cuenta como consulta y actualiza la misma entrada
  $('btnRegenerate').click();
  ok(await waitFor(() => $('quotaLine').textContent.includes('3 de 5')), 'regenerar gasta otra consulta: queda 3 de 5');
  await new Promise(r => setTimeout(r, 300));
  ok(d.querySelectorAll('.h-item').length === 1, 'regenerar no duplica la entrada del historial');

  // 4) Texto que necesita fuentes (la simulación responde sin preguntas)
  $('brandLink').click();
  $('basicPrompt').value = 'Resumen de noticias del sector';
  $('btnImprove').click();
  ok(await waitFor(() => !$('resultPanel').hidden), 'petición con noticias: llega el prompt final');
  ok(JSON.stringify(names()) === JSON.stringify(['Perplexity', 'ChatGPT', 'Claude']), 'texto + fuentes: podio Perplexity, ChatGPT y Claude');
  ok(await waitFor(() => $('quotaLine').textContent.includes('2 de 5')), 'quedan 2 de 5');

  // 5) Se agota la prueba (5 consultas) y la sexta se rechaza
  const quick = async (text, expected) => {
    $('brandLink').click();
    $('basicPrompt').value = text;
    $('btnImprove').click();
    await waitFor(() => !$('resultPanel').hidden);
    return waitFor(() => $('quotaLine').textContent === expected);
  };
  ok(await quick('noticias de hoy', 'Te quedan 1 de 5 consultas de prueba gratuitas.'), 'cuarta consulta: queda 1 de 5');
  ok(await quick('noticias de ayer', 'Has agotado tus consultas de prueba gratuitas.'), 'quinta consulta: la prueba queda agotada');
  $('brandLink').click();
  $('basicPrompt').value = 'una más';
  $('btnImprove').click();
  ok(await waitFor(() => !$('error').hidden), 'la sexta consulta muestra un error');
  ok($('error').textContent.includes('consultas de prueba') && $('resultPanel').hidden, 'el error explica la prueba agotada y no hay resultado');

  // 6) Recargar la página conserva el estado (cookie del visitante)
  w = await A.open(); d = w.document;
  ok($('quotaLine').textContent === 'Has agotado tus consultas de prueba gratuitas.', 'tras recargar, la prueba sigue agotada');
  $('sideToggle').click();
  ok(await waitFor(() => d.querySelectorAll('.h-item').length === 4), 'tras recargar, el historial conserva las 4 entradas');
  const fav = d.querySelectorAll('.h-actions .icon-btn')[0];
  fav.click();
  ok(await waitFor(() => d.querySelector('.h-group')?.textContent === 'Favoritos'), 'marcar favorito funciona contra el servidor');

  // 7) Otro navegador es otro visitante: su propia prueba y su propio historial
  const B = makeBrowser(BASE);
  const wb = await B.open(); const db = wb.document;
  ok(db.getElementById('quotaLine').textContent === 'Te quedan 5 de 5 consultas de prueba gratuitas.', 'otro navegador tiene su propia prueba de 5 consultas');
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
  ok(dc.getElementById('quotaLine').textContent.includes('5 de 5'), 'y no se descuenta ninguna consulta');

  // 10) Landing: barra de prueba con las pruebas restantes
  const C = makeBrowser(BASE);
  const wl = await C.openLanding(); const dl = wl.document;
  ok(dl.getElementById('tryNote').textContent.replace(/\s+/g, ' ').trim() === 'Sin registro: te quedan 5 de 5 pruebas gratuitas.', 'landing: un visitante nuevo ve «te quedan 5 de 5»');
  ok(dl.getElementById('tryForm').getAttribute('action').endsWith('/app') && !dl.getElementById('tryInput').disabled, 'landing: la barra está activa y apunta a /app');
  dl.getElementById('tryInput').value = 'Hazme un cartel desde la landing';
  dl.getElementById('tryForm').dispatchEvent(new wl.Event('submit', { cancelable: true, bubbles: true }));
  ok(wl.sessionStorage.getItem('pf_idea') === 'Hazme un cartel desde la landing', 'landing: al enviar, la idea queda en sessionStorage (no en la URL)');
  const wlA = await A.openLanding();
  ok(wlA.document.getElementById('tryInput').disabled && wlA.document.getElementById('tryNote').textContent.includes('Has usado tus 5 pruebas gratuitas'), 'landing: con la prueba agotada la barra se desactiva y lo explica');

  // 11) La herramienta recoge la idea de la landing y la lanza sola
  const wf = await C.open({ idea: 'Hazme un cartel desde la landing' }); const df = wf.document;
  ok(await waitFor(() => !df.getElementById('questionsPanel').hidden), 'herramienta: la idea de la landing se lanza sola y aparecen las preguntas');
  ok(df.getElementById('basicPrompt').value === 'Hazme un cartel desde la landing' && wf.sessionStorage.getItem('pf_idea') === null, 'herramienta: la idea queda escrita y se borra del sessionStorage');

  // 12) Vídeo o GIF de demostración en el hero (se detecta por los archivos de public/media)
  const mediaDir = path.join(__dirname, '..', '..', 'public', 'media');
  const demo = n => path.join(mediaDir, n);
  const heroState = async () => (await C.openLanding()).document.getElementById('heroMedia');
  const MEDIA = ['demo.gif', 'demo.mp4', 'demo.webm', 'poster.jpg'];
  // Si ya tienes tu vídeo real en public/media, se aparta durante la prueba y se restaura al terminar
  fs.mkdirSync(mediaDir, { recursive: true });
  for (const f of MEDIA) if (fs.existsSync(demo(f))) fs.renameSync(demo(f), demo(f) + '.e2e-backup');
  try {
    let h = await heroState();
    ok(h.dataset.state === 'placeholder' && h.textContent.includes('Próximamente') && !h.querySelector('video, img'), 'hero: sin archivos se ve el hueco «Próximamente»');
    fs.mkdirSync(mediaDir, { recursive: true });
    fs.writeFileSync(demo('demo.gif'), 'GIF89a');
    h = await heroState();
    ok(h.dataset.state === 'gif' && h.querySelector('img')?.getAttribute('src').endsWith('/media/demo.gif') && h.querySelector('img').alt.length > 20, 'hero: con demo.gif se muestra la imagen con texto alternativo');
    fs.writeFileSync(demo('demo.mp4'), 'x'); fs.writeFileSync(demo('poster.jpg'), 'x');
    h = await heroState();
    const v = h.querySelector('video');
    ok(h.dataset.state === 'video' && v && v.hasAttribute('controls') && v.getAttribute('poster').endsWith('/media/poster.jpg') && v.querySelector('source[type="video/mp4"]'), 'hero: con demo.mp4 el vídeo tiene controles, póster y su fuente (y prevalece sobre el GIF)');
    fs.writeFileSync(demo('demo.webm'), 'x');
    ok((await heroState()).querySelectorAll('source').length === 2, 'hero: con demo.webm y demo.mp4 se ofrecen las dos fuentes');
    for (const f of MEDIA) fs.rmSync(demo(f), { force: true });
    ok((await heroState()).dataset.state === 'placeholder', 'hero: al quitar los archivos vuelve el hueco');
  } finally {
    for (const f of MEDIA) {
      fs.rmSync(demo(f), { force: true });
      if (fs.existsSync(demo(f) + '.e2e-backup')) fs.renameSync(demo(f) + '.e2e-backup', demo(f));
    }
  }

  console.log(failures ? `\n${failures} FALLO(S)` : '\nTODO OK');
  return failures;
}

module.exports = { runTests };
