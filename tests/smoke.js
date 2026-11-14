/**
 * Prueba de humo del frontend (admin/index.html) con jsdom.
 *
 * Simula en memoria las respuestas de api.php y history.php, así que NO prueba el backend PHP:
 * solo el flujo de la interfaz (preguntas, badges, resultado, historial, barra lateral).
 * Uso: npm install && npm test
 */
const { JSDOM } = require('jsdom');
const fs = require('fs');
const path = require('path');
const html = fs.readFileSync(path.join(__dirname, '..', 'admin', 'index.html'), 'utf8');

// ---- Servidor simulado en memoria (misma lógica que history.php, simplificada) ----
let store = [];
let idc = 0;
const pub = it => { const { messages, ...r } = it; return r; };
const sorted = () => [...store].sort((a, b) => b.updated - a.updated).map(pub);
let apiCalls = [];
async function fakeFetch(url, opts = {}) {
  const body = opts.body ? JSON.parse(opts.body) : null;
  const json = obj => ({ ok: true, status: 200, json: async () => obj });
  if (url === 'api.php') {
    apiCalls.push(body);
    const last = body.messages[body.messages.length - 1].content;
    const wantsNews = /noticias/i.test(body.messages[0].content);
    if (!body.force_final && body.messages.length === 1 && !wantsNews) {
      return json({ ok: true, raw: '{"type":"questions"}', data: { type: 'questions', intro: 'Dime más', questions: [{ apartado: 'tema', pregunta: '¿Qué artista?' }, '¿Dónde?'] } });
    }
    const prompt = { tipo_salida: wantsNews ? 'texto' : 'imagen', necesita_fuentes: wantsNews, rol: 'R', contexto: 'C', tema: 'T', formato: 'F', tono: 'To', ejemplo: 'E', restricciones: 'Re', indicadores_exito: 'I', orden_final: 'Genera la imagen' };
    return json({ ok: true, raw: JSON.stringify({ type: 'final', prompt }), data: { type: 'final', prompt } });
  }
  if (url === 'history.php') {
    if (!body) return json({ ok: true, items: sorted() });
    const now = Math.floor(Date.now() / 1000) + (++idc);
    switch (body.action) {
      case 'add': { const it = { id: 'id' + idc, title: body.request.slice(0, 60), request: body.request, prompt: body.prompt, type: body.type, fuentes: !!body.fuentes, fav: false, created: now, updated: now, messages: body.messages }; store.unshift(it); return json({ ok: true, items: sorted(), id: it.id }); }
      case 'update': { const it = store.find(x => x.id === body.id); if (body.title) it.title = body.title; if ('fav' in body) it.fav = body.fav; if (body.prompt) { it.prompt = body.prompt; it.updated = now; } if (body.messages) it.messages = body.messages; return json({ ok: true, items: sorted() }); }
      case 'get': return json({ ok: true, items: sorted(), item: store.find(x => x.id === body.id) });
      case 'delete': store = store.filter(x => x.id !== body.id); return json({ ok: true, items: sorted() });
      case 'clear': store = []; return json({ ok: true, items: [] });
    }
  }
  throw new Error('url inesperada ' + url);
}

(async () => {
  const dom = new JSDOM(html, {
    runScripts: 'dangerously', pretendToBeVisual: true, url: 'http://localhost/promptfull/',
    beforeParse(win) { // los scripts de la página se ejecutan al parsear: los mocks deben existir antes
      win.matchMedia = q => ({ matches: false, media: q, addEventListener() {}, removeListener() {} });
      win.fetch = fakeFetch;
    },
  });
  const w = dom.window;
  w.fetch = fakeFetch;
  w.confirm = () => true;
  w.matchMedia = q => ({ matches: false, media: q, addEventListener() {}, removeListener() {} });
  w.Element.prototype.scrollIntoView = () => {};
  w.scrollTo = () => {};
  // jsdom no implementa <dialog>
  w.HTMLDialogElement.prototype.showModal = function () { this.setAttribute('open', ''); };
  w.HTMLDialogElement.prototype.close = function () { this.removeAttribute('open'); };
  Object.defineProperty(w.HTMLDialogElement.prototype, 'open', { get() { return this.hasAttribute('open'); } });
  w.document.dispatchEvent(new w.Event('DOMContentLoaded'));
  const d = w.document, $ = id => d.getElementById(id);
  const wait = (ms = 30) => new Promise(r => setTimeout(r, ms));
  const ok = (c, m) => { console.log((c ? 'OK  ' : 'FALLO ') + m); if (!c) process.exitCode = 1; };

  await wait(50);
  ok(!$('clearBtn') && $('historyClear').hidden, 'historial vacío: botón borrar oculto');

  // 1) Flujo completo: petición -> preguntas -> respuestas -> prompt final
  $('basicPrompt').value = 'Hazme un cartel para un concierto';
  $('btnImprove').click(); await wait(60);
  ok(!$('questionsPanel').hidden, 'aparecen las preguntas');
  const lab0 = d.querySelector('label[for="q0"]'), lab1 = d.querySelector('label[for="q1"]');
  const badge = lab0.querySelector('.q-badge');
  ok(badge && badge.textContent === 'Tema' && badge.classList.contains('c-yellow') && lab0.firstChild === badge, 'la pregunta 1 lleva el badge "Tema" (amarillo) al inicio de la misma línea');
  ok(lab0.textContent === 'Tema¿Qué artista?' && !lab1.querySelector('.q-badge') && lab1.textContent === '¿Dónde?', 'la pregunta sin apartado no lleva badge y el texto es correcto');
  d.getElementById('q0').value = 'PromptFull'; d.getElementById('q1').value = 'Granada';
  $('btnSendAnswers').click(); await wait(80);
  const sent = apiCalls[apiCalls.length - 1].messages.slice(-1)[0].content;
  ok(sent.includes('1. [Tema] ¿Qué artista?') && sent.includes('2. ¿Dónde?'), 'las respuestas enviadas a la IA incluyen el apartado entre corchetes');
  ok(!$('resultPanel').hidden && $('output').value.includes('## Rol'), 'se muestra el prompt final');
  const names = () => [...d.querySelectorAll('.rec-item strong')].map(x => x.textContent);
  ok(JSON.stringify(names()) === JSON.stringify(['ChatGPT', 'Gemini', 'Copilot']), 'imagen: el podio es ChatGPT, Gemini y Copilot');
  ok(d.querySelectorAll('.rec-star').length === 1 && d.querySelector('.rec-item .rec-star').textContent === 'Recomendada', 'solo la primera lleva la etiqueta "Recomendada"');
  ok(d.querySelectorAll('.rec-open').length === 3 && d.querySelector('.rec-item p').textContent.startsWith('Genera imágenes de forma nativa'), '3 tarjetas con botón Abrir y motivo de imagen');
  const groups = [...d.querySelectorAll('.rec-group')].map(g => ({ why: g.querySelector('.rec-reason').textContent, ias: [...g.querySelectorAll('a')].map(a => a.textContent) }));
  const noImg = groups.find(g => g.why.startsWith('No genera imágenes'));
  ok(noImg && noImg.ias.length === 2 && noImg.ias.includes('Claude') && noImg.ias.includes('DeepSeek'), 'Claude y DeepSeek agrupados: no generan imágenes');
  ok(groups.some(g => g.ias.includes('Perplexity') && g.why.includes('buscar y citar fuentes')), 'Perplexity con su motivo específico');
  ok(!d.getElementById('recMore').open && d.getElementById('recNote').textContent.includes('noviembre de 2026'), 'las otras opciones van plegadas y se muestra la fecha de revisión');
  const open0 = d.querySelector('.rec-open');
  open0.dispatchEvent(new w.MouseEvent('click', { bubbles: true, cancelable: true }));
  ok(open0.href.startsWith('https://chatgpt.com/?q='), 'el botón Abrir prepara el enlace con el prompt (ChatGPT admite ?q=)');
  ok(store.length === 1 && store[0].type === 'imagen' && store[0].messages.length >= 4, 'se guardó UNA entrada con tipo y mensajes');

  // 2) Editar el textarea se guarda (debounce 900 ms)
  $('output').value += '\nEDITADO'; $('output').dispatchEvent(new w.Event('input'));
  await wait(1100);
  ok(store[0].prompt.endsWith('EDITADO'), 'la edición del prompt se persiste');

  // 3) Regenerar actualiza la misma entrada (no duplica)
  $('btnRegenerate').click(); await wait(80);
  ok(store.length === 1, 'regenerar no crea entrada duplicada');

  // 4) Panel: lista, favorito, renombrar, copiar, borrar
  ok(!$('sidebar').classList.contains('open') && !d.body.classList.contains('side-open'), 'la barra empieza cerrada (columna de solo icono)');
  ok($('sideToggle').getAttribute('aria-expanded') === 'false' && $('sideToggle').title === 'Abrir historial', 'el icono indica "Abrir historial"');
  $('sideToggle').click(); await wait(60);
  ok($('sidebar').classList.contains('open') && d.body.classList.contains('side-open') && $('sideToggle').getAttribute('aria-expanded') === 'true', 'al pulsar el icono se abre la barra');
  ok(w.localStorage.getItem('pf_side') === '1', 'se recuerda que está abierta');
  ok(d.querySelectorAll('.h-item').length === 1, 'la barra muestra 1 entrada');
  ok([...d.querySelectorAll('.h-group')].map(x => x.textContent).join() === 'Hoy', 'agrupada en "Hoy"');
  d.querySelector('.h-actions .icon-btn').click(); await wait(40);
  ok(store[0].fav === true && d.querySelector('.h-group').textContent === 'Favoritos', 'marcar favorito la mueve a Favoritos');
  d.querySelectorAll('.h-actions .icon-btn')[2].click(); await wait(20);
  const input = d.querySelector('.h-edit'); input.value = 'Cartel concierto'; input.dispatchEvent(new w.KeyboardEvent('keydown', { key: 'Enter', bubbles: true })); await wait(40);
  ok(store[0].title === 'Cartel concierto', 'renombrar funciona');
  $('historySearch').value = 'CÁRTEL'; $('historySearch').dispatchEvent(new w.Event('input')); await wait(10);
  ok(d.querySelectorAll('.h-item').length === 1, 'buscar (sin acentos/mayúsculas) encuentra la entrada');
  $('historySearch').value = 'zzz'; $('historySearch').dispatchEvent(new w.Event('input')); await wait(10);
  ok(d.querySelector('.h-empty').textContent === 'Sin resultados.', 'búsqueda sin resultados');
  $('historySearch').value = ''; $('historySearch').dispatchEvent(new w.Event('input'));

  // 5) Recientes bajo la barra + abrir entrada
  $('sideToggle').click(); await wait(10);
  ok(!$('sidebar').classList.contains('open') && w.localStorage.getItem('pf_side') === '0', 'al pulsar de nuevo se cierra y queda solo el icono');
  $('brandLink').click(); await wait(10); // volver al inicio
  ok($('basicPrompt').value === '' && $('resultPanel').hidden, 'logo = volver al inicio');
  $('basicPrompt').dispatchEvent(new w.Event('click')); await wait(10);
  ok(!$('suggest').hidden && $('suggest').children.length === 1, 'recientes visibles al hacer clic en la barra');
  $('basicPrompt').value = 'xyz'; $('basicPrompt').dispatchEvent(new w.Event('input'));
  ok($('suggest').hidden, 'al escribir algo sin coincidencias se ocultan');
  $('basicPrompt').value = ''; $('basicPrompt').dispatchEvent(new w.Event('input'));
  $('basicPrompt').dispatchEvent(new w.KeyboardEvent('keydown', { key: 'ArrowDown', bubbles: true }));
  ok($('suggest').children[0].classList.contains('active'), 'flecha abajo selecciona la primera sugerencia');
  $('basicPrompt').dispatchEvent(new w.KeyboardEvent('keydown', { key: 'Enter', bubbles: true, cancelable: true })); await wait(60);
  ok(!$('resultPanel').hidden && $('output').value.includes('## Rol') && $('basicPrompt').value === 'Hazme un cartel para un concierto', 'Enter abre la entrada elegida');

  // 6) Regenerar tras abrir desde historial usa el contexto guardado
  apiCalls = [];
  $('btnRegenerate').click(); await wait(80);
  ok(apiCalls.length === 1 && apiCalls[0].messages.length >= 5 && apiCalls[0].force_final === true, 'regenerar tras abrir conserva el contexto (' + apiCalls[0].messages.length + ' mensajes)');
  ok(store.length === 1, 'sigue habiendo una sola entrada');

  // 7) Borrar y vaciar
  $('sideToggle').click(); await wait(40);
  d.querySelectorAll('.h-actions .icon-btn')[3].click(); await wait(40);
  ok(store.length === 0 && d.querySelector('.h-empty'), 'borrar entrada');
  // 8) Texto que necesita fuentes: Perplexity pasa a ser la recomendada y el dato se guarda
  $('brandLink').click(); await wait(10);
  $('basicPrompt').value = 'Resumen de noticias del sector';
  $('btnImprove').click(); await wait(80);
  ok(!$('resultPanel').hidden, 'petición con noticias: llega el resultado');
  ok(JSON.stringify(names()) === JSON.stringify(['Perplexity', 'ChatGPT', 'Claude']), 'texto + fuentes: podio Perplexity, ChatGPT y Claude');
  ok(d.querySelector('.rec-item p').textContent.startsWith('Busca en la web y cita sus fuentes'), 'motivo de Perplexity: busca y cita fuentes');
  ok(store.length === 1 && store[0].fuentes === true && store[0].type === 'texto', 'se guarda que el prompt necesita fuentes');
  // reabrirlo desde los recientes mantiene la recomendación
  $('brandLink').click(); await wait(10);
  $('basicPrompt').dispatchEvent(new w.Event('click')); await wait(10);
  $('suggest').children[0].dispatchEvent(new w.MouseEvent('mousedown', { bubbles: true, cancelable: true })); await wait(60);
  ok(JSON.stringify(names()) === JSON.stringify(['Perplexity', 'ChatGPT', 'Claude']), 'al reabrir desde el historial se mantiene la recomendación');
  console.log(process.exitCode ? '\nHAY FALLOS' : '\nTODO OK');
  process.exit();
})().catch(e => { console.error('EXCEPCIÓN', e); process.exit(1); });
