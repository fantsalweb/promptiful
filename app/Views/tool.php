<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Promptiful - beautiful prompt</title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Ctext x='32' y='50' font-family='Arial' font-size='56' font-weight='700' text-anchor='middle' fill='%234285f4'%3EP%3C/text%3E%3C/svg%3E">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --blue: #4285f4;
    --red: #ea4335;
    --yellow: #fbbc05;
    --green: #34a853;
    --text: #202124;
    --muted: #5f6368;
    --line: #dfe1e5;
    --hover-shadow: 0 1px 6px rgba(32,33,36,.28);
    --bg: #fff;
    --surface: #fff;
    --chip: #f8f9fa;
  }
  @media (prefers-color-scheme: dark) {
    :root {
      --text: #e8eaed;
      --muted: #9aa0a6;
      --line: #5f6368;
      --bg: #202124;
      --surface: #303134;
      --chip: #303134;
      --hover-shadow: 0 1px 6px rgba(0,0,0,.6);
    }
  }
  * { box-sizing: border-box; }
  html, body { height: 100%; margin: 0; }
  body {
    background: var(--bg);
    color: var(--text);
    font-family: Roboto, Arial, sans-serif;
    display: flex;
    flex-direction: column;
    align-items: center;
  }
  main {
    width: 100%;
    max-width: 700px;
    padding: 0 20px 60px;
    margin: auto 0;
    display: flex;
    flex-direction: column;
    align-items: center;
  }
  /* Cuando hay resultado, la marca sube y deja sitio al contenido */
  body.has-output main { margin-top: 40px; }

  .logo {
    font-family: "Product Sans", "Google Sans", Outfit, sans-serif;
    font-weight: 500;
    font-size: clamp(48px, 13vw, 84px);
    letter-spacing: -2px;
    line-height: 1;
    margin: 0 0 6px;
    user-select: none;
  }
  .brand { display: flex; flex-direction: column; align-items: center; text-decoration: none; color: inherit; border-radius: 8px; }
  .tagline { margin: 0 0 28px; font-size: 14px; color: var(--muted); letter-spacing: .4px; text-align: center; }
  body.has-output .tagline { margin-bottom: 18px; }

  .topbar { position: absolute; top: 0; left: 0; right: 0; display: flex; justify-content: space-between; padding: 14px 20px; }
  .about-link {
    background: none; border: 0; box-shadow: none; padding: 6px 4px; height: auto;
    font-size: 14px; color: var(--text); border-radius: 4px;
  }
  .about-link:hover { background: none; border: 0; box-shadow: none; text-decoration: underline; }

  dialog {
    border: 0; padding: 0; border-radius: 16px; width: min(560px, calc(100% - 32px));
    background: var(--surface); color: var(--text); box-shadow: 0 8px 40px rgba(0,0,0,.35);
  }
  dialog::backdrop { background: rgba(32,33,36,.6); }
  .modal-body { padding: 28px; text-align: left; font-size: 15px; line-height: 1.6; }
  .modal-body h2 { margin: 0 0 12px; font-size: 20px; font-weight: 500; }
  .modal-body h3 { margin: 20px 0 8px; font-size: 15px; font-weight: 500; }
  .modal-body p { margin: 0 0 12px; }
  .modal-body ol { margin: 0 0 14px; padding-left: 22px; }
  .modal-body li { margin-bottom: 8px; }
  .modal-body .privacy { font-size: 13px; color: var(--muted); }
  dialog { max-height: calc(100dvh - 32px); overflow-y: auto; }
  .badge { display: inline-block; margin-left: 8px; padding: 2px 10px; border-radius: 10px; font-size: 12px; font-weight: 500; vertical-align: middle; }
  .badge.req { background: var(--blue); color: #fff; }
  .badge.opt { background: var(--chip); color: var(--muted); border: 1px solid var(--line); }
  .modal-body .example {
    background: var(--chip); border-left: 3px solid var(--blue); border-radius: 6px;
    padding: 10px 14px; font-size: 14px; margin-bottom: 4px;
  }
  .logo span:nth-child(5n+1) { color: var(--blue); }
  .logo span:nth-child(5n+2) { color: var(--red); }
  .logo span:nth-child(5n+3) { color: var(--yellow); }
  .logo span:nth-child(5n+4) { color: var(--green); }
  .logo span:nth-child(5n+5) { color: var(--red); }
  body.has-output .logo { font-size: 44px; }

  .searchbox {
    width: 100%;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    border: 1px solid var(--line);
    border-radius: 26px;
    padding: 10px 14px 10px 20px;
    background: var(--surface);
    transition: box-shadow .15s;
  }
  .searchbox:hover, .searchbox:focus-within { box-shadow: var(--hover-shadow); border-color: transparent; }
  .searchbox svg { flex: none; width: 20px; height: 20px; margin-top: 8px; fill: var(--muted); }
  .searchbox textarea {
    flex: 1;
    border: 0;
    outline: 0;
    resize: none;
    background: transparent;
    color: var(--text);
    font: 16px/24px Roboto, Arial, sans-serif;
    min-height: 24px;
    max-height: 160px;
    padding: 5px 0;
    overflow-y: auto;
  }
  .searchbox textarea::placeholder { color: var(--muted); }
  /* La regla global de :focus-visible tiene la misma especificidad que "outline: 0" de arriba y gana por orden; esta es más específica */
  .searchbox textarea:focus-visible { outline: 0; }

  .actions { margin-top: 28px; display: flex; gap: 12px; }
  button {
    font: 14px Roboto, Arial, sans-serif;
    color: var(--text);
    background: var(--chip);
    border: 1px solid var(--chip);
    border-radius: 4px;
    padding: 0 18px;
    height: 36px;
    cursor: pointer;
  }
  button:hover { border-color: var(--line); box-shadow: 0 1px 1px rgba(0,0,0,.1); }
  button:focus-visible, a:focus-visible, textarea:focus-visible, input:focus-visible { outline: 2px solid var(--blue); outline-offset: 2px; }
  button.primary { background: var(--blue); border-color: var(--blue); color: #fff; }
  button.primary:hover { background: #3b78e7; }
  button:disabled { opacity: .55; cursor: default; box-shadow: none; }

  .quota { margin: 8px 0 0; font-size: 13px; color: var(--muted); text-align: center; }
  .quota.low { color: var(--red); }
  .hint { margin-top: 22px; font-size: 14px; color: var(--muted); text-align: center; line-height: 1.5; }

  /* Bloques de contenido (preguntas, resultado) */
  .panel { width: 100%; margin-top: 28px; text-align: left; }
  .panel[hidden] { display: none; }
  .panel h2 { font-size: 16px; font-weight: 500; margin: 0 0 6px; }
  .panel p.sub { margin: 0 0 16px; color: var(--muted); font-size: 14px; line-height: 1.5; }

  .c-blue { --c: var(--blue); } .c-red { --c: var(--red); } .c-yellow { --c: var(--yellow); } .c-green { --c: var(--green); }
  .q-badge {
    display: inline-flex; align-items: center; gap: 6px; margin-right: 8px; padding: 1px 10px 1px 8px;
    border-radius: 999px; font-size: 12px; font-weight: 500; vertical-align: middle; color: var(--text);
    background: color-mix(in srgb, var(--c) 14%, transparent); border: 1px solid color-mix(in srgb, var(--c) 40%, transparent);
  }
  .q-badge::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: var(--c); }
  .question { margin-bottom: 16px; }
  .question label { display: block; font-size: 14px; margin-bottom: 6px; line-height: 1.4; }
  .question input {
    width: 100%;
    font: 15px Roboto, Arial, sans-serif;
    color: var(--text);
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 8px;
    padding: 10px 14px;
  }
  .row { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 8px; }

  #output {
    width: 100%;
    min-height: 420px;
    resize: vertical;
    font: 14px/1.6 Roboto, Arial, sans-serif;
    color: var(--text);
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 16px;
  }

  .ia-title { margin: 26px 0 10px; font-size: 14px; color: var(--muted); }
  .ia-list { display: flex; flex-wrap: wrap; gap: 10px; }
  .ia-list a {
    text-decoration: none;
    color: var(--text);
    font-size: 14px;
    border: 1px solid var(--line);
    border-radius: 18px;
    padding: 8px 16px;
    background: var(--surface);
  }
  .ia-list a:hover { box-shadow: var(--hover-shadow); border-color: transparent; }
  .ia-list a.dim { opacity: .55; }
  .note[hidden] { display: none; }
  #imgNote { margin: 0 0 12px; }
  .note { margin-top: 12px; font-size: 13px; color: var(--muted); line-height: 1.5; }

  /* ---- Recomendador de IA ---- */
  .rec-list { display: grid; gap: 10px; margin: 0 0 12px; padding: 0; list-style: none; }
  .rec-item { display: flex; align-items: center; gap: 14px; padding: 14px 16px; background: var(--surface); border: 1px solid var(--line); border-radius: 14px; }
  .rec-num { flex: none; display: grid; place-items: center; width: 36px; height: 36px; border-radius: 50%; background: var(--c); color: #fff; font: 600 18px Outfit, sans-serif; }
  .rec-item.c-yellow .rec-num { color: #202124; }
  .rec-body { flex: 1; min-width: 0; }
  .rec-head { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 16px; font-weight: 500; }
  .rec-star {
    padding: 1px 9px; border-radius: 999px; font-size: 12px; font-weight: 500;
    background: color-mix(in srgb, var(--c) 14%, transparent); border: 1px solid color-mix(in srgb, var(--c) 40%, transparent);
  }
  .rec-body p { margin: 2px 0 0; font-size: 13px; line-height: 1.45; color: var(--muted); }
  .rec-open { flex: none; padding: 8px 16px; border-radius: 18px; background: #1a73e8; color: #fff; font-size: 14px; text-decoration: none; }
  .rec-open:hover { background: #1765cc; }
  .rec-more { margin-top: 4px; }
  .rec-more summary { cursor: pointer; font-size: 14px; color: var(--muted); padding: 6px 0; }
  .rec-group { margin: 10px 0; }
  .rec-reason { margin: 0 0 6px; font-size: 13px; color: var(--muted); }
  @media (max-width: 520px) {
    .rec-item { flex-wrap: wrap; }
    .rec-body { flex-basis: calc(100% - 60px); }
    .rec-open { margin-left: 50px; }
  }

  .loader { display: flex; gap: 6px; justify-content: center; margin-top: 30px; }
  .loader[hidden] { display: none; }
  .loader i { width: 10px; height: 10px; border-radius: 50%; animation: bounce 1s infinite ease-in-out; }
  .loader i:nth-child(1) { background: var(--blue); }
  .loader i:nth-child(2) { background: var(--red); animation-delay: .15s; }
  .loader i:nth-child(3) { background: var(--yellow); animation-delay: .3s; }
  .loader i:nth-child(4) { background: var(--green); animation-delay: .45s; }
  @keyframes bounce { 0%, 80%, 100% { transform: translateY(0); } 40% { transform: translateY(-9px); } }
  @media (prefers-reduced-motion: reduce) { .loader i { animation: none; } }

  .error { color: var(--red); font-size: 14px; margin-top: 18px; text-align: center; }
  .error[hidden] { display: none; }

  footer { width: 100%; padding: 14px 20px; font-size: 13px; color: var(--muted); text-align: center; background: var(--chip); border-top: 1px solid var(--line); }

  /* ---- Historial ---- */
  main { padding-top: 56px; }
  .topbar { flex-wrap: wrap; gap: 0 8px; }
  .topbar-left { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
  .about-link { display: inline-flex; align-items: center; gap: 6px; }
  .about-link svg { width: 18px; height: 18px; fill: currentColor; }
  @media (max-width: 520px) { .about-link { font-size: 13px; } }

  #historySearch {
    width: 100%; font: 14px Roboto, Arial, sans-serif; color: var(--text); background: var(--chip);
    border: 1px solid var(--line); border-radius: 20px; padding: 9px 14px; margin-bottom: 4px;
  }
  .history-list { flex: 1; overflow-y: auto; margin: 0 -6px; padding: 0 6px; }
  .h-group { margin: 14px 0 4px; font-size: 12px; font-weight: 500; color: var(--muted); }
  .h-item { display: flex; align-items: center; gap: 2px; border-radius: 10px; }
  .h-item:hover { background: var(--chip); }
  .h-main {
    flex: 1; min-width: 0; text-align: left; background: none; border: 0; box-shadow: none; height: auto;
    padding: 8px 6px; border-radius: 8px; display: flex; flex-direction: column; gap: 2px; cursor: pointer;
  }
  .h-main:hover { background: none; border: 0; box-shadow: none; }
  .h-title { font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .h-meta { font-size: 12px; color: var(--muted); }
  .h-actions { display: flex; flex: none; }
  .h-edit {
    flex: 1; font: 14px Roboto, Arial, sans-serif; padding: 8px 10px; margin: 4px 0;
    border: 1px solid var(--blue); border-radius: 8px; background: var(--surface); color: var(--text);
  }
  .h-empty { color: var(--muted); font-size: 14px; line-height: 1.5; padding: 12px 6px; }
  .icon-btn {
    width: 30px; height: 30px; padding: 0; background: none; border: 0; border-radius: 50%;
    display: grid; place-items: center; box-shadow: none; flex: none;
  }
  .icon-btn:hover { background: var(--line); border: 0; box-shadow: none; }
  .icon-btn svg { width: 18px; height: 18px; fill: var(--muted); }
  .icon-btn.on svg { fill: var(--yellow); }
  .icon-btn.ok svg { fill: var(--green); }
  .drawer-note { margin: 10px 0 6px; font-size: 12px; color: var(--muted); line-height: 1.4; }
  .danger-link { align-self: flex-start; background: none; border: 0; box-shadow: none; height: auto; padding: 4px 0; color: var(--red); font-size: 13px; }
  .danger-link:hover { background: none; border: 0; box-shadow: none; text-decoration: underline; }
  .danger-link[hidden] { display: none; }

  /* ---- Recientes bajo la barra (estilo Google) ---- */
  .searchwrap { position: relative; width: 100%; }
  .searchwrap.open .searchbox { border-radius: 26px 26px 0 0; box-shadow: var(--hover-shadow); border-color: transparent; }
  .suggest {
    position: absolute; left: 0; right: 0; top: 100%; z-index: 20; margin: 0; padding: 4px 0 12px; list-style: none;
    background: var(--surface); border-radius: 0 0 26px 26px; box-shadow: var(--hover-shadow);
    clip-path: inset(0 -12px -12px -12px);
  }
  .suggest[hidden] { display: none; }
  .suggest li { display: flex; align-items: center; gap: 14px; padding: 8px 20px; font-size: 15px; cursor: pointer; }
  .suggest li svg { flex: none; width: 18px; height: 18px; fill: var(--muted); }
  .suggest li span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .suggest li:hover, .suggest li.active { background: var(--chip); }

  /* ---- Barra lateral del historial (columna con solo el icono al cerrarla) ---- */
  :root { --rail: 56px; --panel: 300px; }
  body { padding-left: var(--rail); transition: padding-left .2s ease; }
  body.side-open { padding-left: var(--panel); }
  .topbar { left: var(--rail); transition: left .2s ease; }
  body.side-open .topbar { left: var(--panel); }

  .sidebar {
    position: fixed; top: 0; left: 0; bottom: 0; z-index: 40; width: var(--rail);
    background: var(--chip); border-right: 1px solid var(--line);
    display: flex; flex-direction: column; overflow: hidden; transition: width .2s ease;
  }
  .sidebar.open { width: var(--panel); }
  .side-head { display: flex; align-items: center; gap: 8px; padding: 10px; min-height: 56px; flex: none; }
  .side-title { display: none; font-size: 16px; font-weight: 500; white-space: nowrap; }
  .sidebar.open .side-title { display: block; }
  .rail-btn {
    width: 36px; height: 36px; padding: 0; flex: none; display: grid; place-items: center;
    background: none; border: 0; box-shadow: none; border-radius: 8px; color: var(--muted);
  }
  .rail-btn:hover { background: var(--line); border: 0; box-shadow: none; color: var(--text); }
  .rail-btn svg { width: 22px; height: 22px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
  .side-content {
    display: none; flex-direction: column; flex: 1; min-height: 0;
    min-width: calc(var(--panel) - 1px); padding: 0 14px 14px; text-align: left;
  }
  .sidebar.open .side-content { display: flex; }
  .scrim { display: none; position: fixed; inset: 0; z-index: 30; background: rgba(32,33,36,.5); }

  @media (max-width: 700px) {
    :root { --rail: 0px; }                                   /* en móvil no hay columna */
    body, body.side-open { padding-left: 0; }
    .topbar, body.side-open .topbar { left: 56px; }          /* deja sitio al icono flotante */
    /* Cerrada: sin columna ni fondo, solo el icono flotando arriba a la izquierda */
    .sidebar:not(.open) { width: auto; bottom: auto; background: none; border-right: 0; overflow: visible; pointer-events: none; }
    .sidebar:not(.open) .rail-btn { pointer-events: auto; }
    /* Abierta: el panel se superpone al contenido, con fondo oscuro detrás */
    .sidebar.open { width: min(var(--panel), 85vw); box-shadow: 0 0 24px rgba(0,0,0,.35); }
    body.side-open .scrim { display: block; }
  }
  @media (prefers-reduced-motion: reduce) {
    body, .topbar, .sidebar { transition: none; }
  }
</style>
</head>
<body>

<header class="topbar">
  <div class="topbar-left">
    <button type="button" class="about-link" id="aboutLink">Sobre Promptiful</button>
  </div>
  <button type="button" class="about-link" id="peLink">Prompt engineering</button>
</header>

<dialog id="peDialog" aria-labelledby="peTitle">
  <div class="modal-body">
    <h2 id="peTitle">Qué es el prompt engineering</h2>
    <p>El prompt engineering (ingeniería de prompts) es el arte de escribir instrucciones claras para que una IA te dé justo lo que necesitas. Una petición vaga como «escribe un post sobre viajes» produce resultados genéricos; una petición bien estructurada produce resultados útiles y previsibles.</p>
    <p>Promptiful organiza tu idea en los apartados que ves a continuación. Todos los ejemplos parten de la misma petición: «créame un post para mi blog sobre viajes por América».</p>

    <p>Cada apartado lleva una etiqueta: los <strong>obligatorios</strong> son la base de cualquier buen prompt y los <strong>opcionales</strong> mejoran el resultado cuando los necesitas. Si escribes un prompt a mano puedes dejar fuera los opcionales; Promptiful los rellena todos para darte una versión más completa.</p>

    <h3>Rol<span class="badge req">Obligatorio</span></h3>
    <p>Quién quieres que sea la IA. Darle una especialidad hace que responda con el enfoque y el vocabulario adecuados.</p>
    <p class="example"><strong>Ejemplo:</strong> Actúa como una periodista de viajes con 10 años de experiencia en Latinoamérica.</p>

    <h3>Contexto<span class="badge req">Obligatorio</span></h3>
    <p>La situación, a quién va dirigido y para qué. Cuanto más contexto, menos tiene que adivinar la IA.</p>
    <p class="example"><strong>Ejemplo:</strong> El post es para mi blog de viajes low cost. Mis lectores son jóvenes españoles que preparan su primer viaje a América.</p>

    <h3>Tema<span class="badge req">Obligatorio</span></h3>
    <p>Qué hay que producir exactamente y con qué alcance.</p>
    <p class="example"><strong>Ejemplo:</strong> Escribe un post sobre las 5 mejores rutas para recorrer América del Sur en un mes.</p>

    <h3>Formato<span class="badge opt">Opcional</span></h3>
    <p>Cómo debe entregarse el resultado: estructura, extensión y secciones.</p>
    <p class="example"><strong>Ejemplo:</strong> Título, introducción breve, 5 apartados con subtítulo y una conclusión. Entre 800 y 1.000 palabras.</p>

    <h3>Tono<span class="badge opt">Opcional</span></h3>
    <p>La voz y el estilo con los que debe expresarse.</p>
    <p class="example"><strong>Ejemplo:</strong> Cercano, entusiasta y práctico, sin lenguaje técnico.</p>

    <h3>Ejemplo<span class="badge opt">Opcional</span></h3>
    <p>Una muestra breve de lo que esperas obtener, para que la IA imite el estilo o la estructura.</p>
    <p class="example"><strong>Ejemplo:</strong> Un titular como «Perú en 10 días: la ruta que me cambió la forma de viajar».</p>

    <h3>Restricciones<span class="badge opt">Opcional</span></h3>
    <p>Lo que debe evitar o respetar. Ayuda a prevenir errores y relleno.</p>
    <p class="example"><strong>Ejemplo:</strong> No inventes precios ni datos, evita tópicos como «paraíso terrenal» y no superes las 1.000 palabras.</p>

    <h3>Indicadores de éxito<span class="badge opt">Opcional</span></h3>
    <p>Cómo saber si el resultado es bueno. Sirven a la IA de lista de comprobación y a ti para revisarlo.</p>
    <p class="example"><strong>Ejemplo:</strong> El post sirve para planificar el viaje, incluye 5 rutas distintas y se puede publicar sin cambios.</p>

    <h3>Instrucción de ejecución<span class="badge req">Obligatorio</span></h3>
    <p>La orden final que fija qué debe entregar la IA. Evita que responda con un esquema o una descripción en lugar del resultado. En peticiones de imagen, ordena generar la imagen.</p>
    <p class="example"><strong>Ejemplo:</strong> Escribe directamente el post completo, listo para publicar. No lo resumas ni expliques el proceso.</p>

    <div class="row"><button type="button" class="primary" id="peClose">Cerrar</button></div>
  </div>
</dialog>

<dialog id="aboutDialog" aria-labelledby="aboutTitle">
  <div class="modal-body">
    <h2 id="aboutTitle">Sobre Promptiful</h2>
    <p>Promptiful (beautiful prompt) convierte una idea sencilla en un prompt profesional para cualquier IA. No hace falta saber de «prompt engineering»: escribes lo que quieres con tus palabras y Promptiful se encarga del resto.</p>
    <h3>Cómo funciona</h3>
    <ol>
      <li>Escribe tu idea tal como se te ocurra, por ejemplo «créame un post para mi blog sobre viajes por América».</li>
      <li>Responde unas preguntas cortas si Promptiful necesita más datos. Puedes omitirlas.</li>
      <li>Recibe un prompt completo con rol, contexto, tema, formato, tono, ejemplo, restricciones, indicadores de éxito e instrucción de ejecución. Edítalo, cópialo o ábrelo directamente en la IA que prefieras.</li>
    </ol>
    <p class="privacy">Tu texto se envía a un servicio de IA (DeepSeek) para generar el prompt, así que evita incluir datos personales o confidenciales.</p>
    <div class="row"><button type="button" class="primary" id="aboutClose">Cerrar</button></div>
  </div>
</dialog>

<aside class="sidebar" id="sidebar" aria-label="Historial de prompts">
  <div class="side-head">
    <button type="button" class="rail-btn" id="sideToggle" aria-expanded="false" aria-controls="sideContent" aria-label="Abrir historial" title="Abrir historial">
      <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="3.5"/><path d="M9.5 4v16"/></svg>
    </button>
    <span class="side-title">Historial</span>
  </div>
  <div class="side-content" id="sideContent">
    <input type="search" id="historySearch" placeholder="Buscar en el historial" aria-label="Buscar en el historial" autocomplete="off">
    <div id="historyList" class="history-list"></div>
    <p class="drawer-note">Se guarda en el servidor, asociado a este navegador (sin cuenta), hasta <span id="histMax">5</span> prompts. Si borras las cookies, no podrás recuperarlo.</p>
    <button type="button" class="danger-link" id="historyClear" hidden>Borrar todo el historial</button>
  </div>
</aside>
<div class="scrim" id="sideScrim"></div>

<main>
  <a class="brand" id="brandLink" href="<?= esc($appUrl, 'attr') ?>" title="Ir al inicio">
  <h1 class="logo" aria-label="Promptiful"><span>P</span><span>r</span><span>o</span><span>m</span><span>p</span><span>t</span><span>i</span><span>f</span><span>u</span><span>l</span></h1>
  <p class="tagline">beautiful prompt</p>
  </a>

  <div class="searchwrap" id="searchWrap">
    <form class="searchbox" id="searchForm">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 9l1.25-2.75L23 5l-2.75-1.25L19 1l-1.25 2.75L15 5l2.75 1.25L19 9zm-7.5.5L9 4 6.5 9.5 1 12l5.5 2.5L9 20l2.5-5.5L17 12l-5.5-2.5zM19 15l-1.25 2.75L15 19l2.75 1.25L19 23l1.25-2.75L23 19l-2.75-1.25L19 15z"/></svg>
      <textarea id="basicPrompt" rows="1" maxlength="1500" placeholder="Escribe tu idea, p. ej.: créame un post para mi blog sobre viajes por América" aria-label="Tu prompt básico" autofocus></textarea>
    </form>
    <ul class="suggest" id="suggest" role="listbox" aria-label="Prompts recientes" hidden></ul>
  </div>

  <div class="actions" id="mainActions">
    <button type="button" class="primary" id="btnImprove">Mejorar prompt</button>
    <button type="button" id="btnReset">Empezar de nuevo</button>
  </div>

  <p class="hint" id="hint">Escribe lo que quieres pedirle a una IA, tal como se te ocurra.<br>Promptiful te hará unas preguntas y lo convertirá en un prompt profesional.</p>
  <p class="quota" id="quotaLine"></p>

  <div class="loader" id="loader" hidden aria-live="polite" aria-label="Cargando"><i></i><i></i><i></i><i></i></div>
  <p class="error" id="error" role="alert" hidden></p>

  <!-- Preguntas de la IA -->
  <section class="panel" id="questionsPanel" hidden>
    <h2 id="questionsIntro">Para afinar tu prompt, cuéntame un poco más</h2>
    <p class="sub">Responde lo que sepas. Lo que dejes en blanco lo decidiré yo.</p>
    <form id="questionsForm"></form>
    <div class="row">
      <button type="button" class="primary" id="btnSendAnswers">Continuar</button>
      <button type="button" id="btnSkip">Omitir y generar ya</button>
    </div>
  </section>

  <!-- Resultado -->
  <section class="panel" id="resultPanel" hidden>
    <h2>Tu prompt profesional</h2>
    <p class="sub">Puedes editarlo antes de usarlo.</p>
    <textarea id="output" spellcheck="false" aria-label="Prompt profesional"></textarea>
    <div class="row">
      <button type="button" class="primary" id="btnCopy">Copiar prompt</button>
      <button type="button" id="btnRegenerate">Generar otra versión</button>
    </div>

    <p class="ia-title">Dónde usar este prompt</p>
    <p class="note" id="imgNote" hidden>Este prompt es para generar una imagen. Los generadores a veces fallan con textos largos y logotipos: si pasa, genera el fondo y la ilustración y añade los textos y logos oficiales después con un editor gráfico.</p>
    <ol class="rec-list" id="recList"></ol>
    <details class="rec-more" id="recMore">
      <summary>Otras opciones y por qué no son las primeras</summary>
      <div id="recOthers"></div>
    </details>
    <p class="note" id="recNote"></p>
    <p class="note" id="iaNote">Al pulsar, el prompt se copia al portapapeles. En las IAs que lo admiten se abre con el prompt ya escrito; en el resto, solo tienes que pegarlo (Ctrl+V).</p>
  </section>
</main>

<footer>Promptiful · beautiful prompt</footer>

<script>
  // Configuración que entrega el servidor (rutas de la API, token CSRF y estado de la prueba gratuita)
  window.PF = <?= json_encode($pf, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script>
(() => {
  'use strict';

  const PF = window.PF;
  const API_URL = PF.api;

  // Cabeceras comunes: JSON, petición «AJAX» y token CSRF de CodeIgniter
  function pfHeaders(json = true) {
    const h = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', [PF.csrfHeader]: PF.csrf };
    if (json) h['Content-Type'] = 'application/json';
    return h;
  }
  const CSRF_MSG = 'La sesión de seguridad ha caducado. Recarga la página e inténtalo de nuevo.';

  // Consultas de prueba restantes
  function renderQuota(q) {
    const line = document.getElementById('quotaLine');
    if (!line || !q) return;
    const out = q.blocked || q.remaining <= 0;
    line.textContent = out
      ? 'Has agotado tus consultas de prueba gratuitas.'
      : `Te quedan ${q.remaining} de ${q.limit} consultas de prueba gratuitas.`;
    line.classList.toggle('low', out || q.remaining === 1);
  }
  const MAX_ROUNDS = 2; // rondas de preguntas como máximo

  // ---- Estado: DeepSeek no tiene memoria, así que guardamos el historial aquí
  //      y se reenvía completo en cada petición.
  let messages = [];
  let rounds = 0;
  let currentQuestions = [];
  let currentId = null;      // id del prompt abierto en el historial
  let currentRequest = '';   // petición original del usuario
  let gen = 0;               // evita asignar ids a una petición ya descartada

  const $ = id => document.getElementById(id);
  const el = {
    form: $('searchForm'), basic: $('basicPrompt'), improve: $('btnImprove'), reset: $('btnReset'),
    hint: $('hint'), loader: $('loader'), error: $('error'),
    qPanel: $('questionsPanel'), qIntro: $('questionsIntro'), qForm: $('questionsForm'),
    send: $('btnSendAnswers'), skip: $('btnSkip'),
    rPanel: $('resultPanel'), output: $('output'), copy: $('btnCopy'), regen: $('btnRegenerate'),
    recList: $('recList'), recOthers: $('recOthers'), recNote: $('recNote'), recMore: $('recMore'), imgNote: $('imgNote'),
  };

  // ===== Catálogo de IAs y criterios del recomendador =====
  // - `imagen` y `busqueda` son datos verificables; revísalos de vez en cuando
  //   (la disponibilidad puede depender del plan o de la región).
  //   busqueda: 2 = centrada en buscar y citar fuentes, 1 = incluye búsqueda web, 0 = no.
  // - `afinidad` (0-5 por tipo de prompt) es CRITERIO EDITORIAL de Promptiful, no un ranking oficial:
  //   ajústalo con tus propias pruebas. Los valores iniciales son orientativos.
  // - `param` = parámetro de URL no oficial que admite el prompt prellenado (puede cambiar);
  //   el copiado al portapapeles es el plan B.
  const AI_CATALOG = {
    revisado: 'noviembre de 2026',
    ias: [
      { name: 'ChatGPT', url: 'https://chatgpt.com/', param: 'q', imagen: true, busqueda: 1,
        fortaleza: 'Asistente generalista y muy extendido.',
        afinidad: { texto: 4, imagen: 5, codigo: 5, datos: 4, otro: 4 } },
      { name: 'Claude', url: 'https://claude.ai/new', param: 'q', imagen: false, busqueda: 1,
        fortaleza: 'Muy usado para redactar textos largos y programar.',
        afinidad: { texto: 4, imagen: 0, codigo: 5, datos: 4, otro: 4 } },
      { name: 'Perplexity', url: 'https://www.perplexity.ai/search', param: 'q', imagen: true, busqueda: 2,
        fortaleza: 'Motor de respuestas que busca en la web y cita sus fuentes.',
        limite: 'Está pensada sobre todo para buscar y citar fuentes, más que para crear contenido.',
        afinidad: { texto: 2, imagen: 2, codigo: 2, datos: 3, otro: 3 } },
      { name: 'Copilot', url: 'https://copilot.microsoft.com/', param: 'q', imagen: true, busqueda: 1,
        fortaleza: 'Asistente de Microsoft, integrado con sus herramientas.',
        afinidad: { texto: 3, imagen: 4, codigo: 3, datos: 3, otro: 3 } },
      { name: 'Grok', url: 'https://grok.com/', param: 'q', imagen: true, busqueda: 1,
        fortaleza: 'Asistente de xAI, con acceso a información de la red X.',
        afinidad: { texto: 3, imagen: 3, codigo: 3, datos: 3, otro: 3 } },
      { name: 'Gemini', url: 'https://gemini.google.com/app', param: null, imagen: true, busqueda: 1,
        fortaleza: 'Asistente de Google, integrado con sus servicios.',
        afinidad: { texto: 4, imagen: 5, codigo: 4, datos: 4, otro: 4 } },
      { name: 'DeepSeek', url: 'https://chat.deepseek.com/', param: null, imagen: false, busqueda: 1,
        fortaleza: 'Razonamiento y código, y de uso gratuito en su web.',
        afinidad: { texto: 3, imagen: 0, codigo: 4, datos: 3, otro: 3 } },
      { name: 'Mistral', url: 'https://chat.mistral.ai/chat', param: null, imagen: true, busqueda: 1,
        fortaleza: 'Alternativa europea (Le Chat), con búsqueda web y plan gratuito.',
        afinidad: { texto: 3, imagen: 4, codigo: 3, datos: 3, otro: 3 } },
    ],
  };
  const SECTION_COLOR = { rol: 'blue', contexto: 'red', tema: 'yellow', formato: 'green', tono: 'blue', ejemplo: 'red', restricciones: 'yellow', indicadores_exito: 'green', orden_final: 'blue' };
  const MAX_URL_PROMPT = 4000; // si el prompt codificado es más largo, no lo mandamos por URL

  const SECTIONS = [
    ['rol', 'Rol'], ['contexto', 'Contexto'], ['tema', 'Tema'], ['formato', 'Formato'],
    ['tono', 'Tono'], ['ejemplo', 'Ejemplo'], ['restricciones', 'Restricciones'],
    ['indicadores_exito', 'Indicadores de éxito'], ['orden_final', 'Instrucción de ejecución'],
  ];

  const SECTION_TITLE = Object.fromEntries(SECTIONS);

  // ---- Utilidades de UI
  const show = (node, on = true) => { node.hidden = !on; };
  function setBusy(busy) {
    show(el.loader, busy);
    el.improve.disabled = busy; el.send.disabled = busy; el.skip.disabled = busy; el.regen.disabled = busy;
  }
  function showError(msg) { el.error.textContent = msg; show(el.error, !!msg); }
  function autoGrow() { el.basic.style.height = 'auto'; el.basic.style.height = Math.min(el.basic.scrollHeight, 160) + 'px'; }

  function resetAll() {
    messages = []; rounds = 0; currentQuestions = [];
    currentId = null; currentRequest = ''; gen++;
    hideSuggest();
    el.basic.value = ''; autoGrow();
    show(el.qPanel, false); show(el.rPanel, false); show(el.hint, true);
    showError(''); setBusy(false);
    document.body.classList.remove('has-output');
    el.basic.focus();
  }

  // ---- Llamada al backend (siempre con el historial completo)
  async function callApi(forceFinal = false) {
    showError(''); setBusy(true);
    try {
      const res = await fetch(API_URL, {
        method: 'POST',
        headers: pfHeaders(),
        body: JSON.stringify({ messages, force_final: forceFinal }),
      });
      if (res.status === 403) throw new Error(CSRF_MSG);
      let json;
      try { json = await res.json(); } catch { throw new Error('Respuesta no válida del servidor.'); }
      if (json.quota) renderQuota(json.quota);
      if (!res.ok || !json.ok) throw new Error(json.error || 'Error inesperado.');

      messages.push({ role: 'assistant', content: json.raw }); // recordamos lo que dijo la IA
      handleResponse(json.data);
    } catch (err) {
      // Si falló, retiramos el último mensaje del usuario para poder reintentar sin duplicarlo
      if (messages.length && messages[messages.length - 1].role === 'user') messages.pop();
      if (messages.length && el.rPanel.hidden && currentQuestions.length) show(el.qPanel, true);
      showError(err.message || 'No se pudo completar la petición.');
    } finally {
      setBusy(false);
    }
  }

  function handleResponse(data) {
    show(el.hint, false);
    document.body.classList.add('has-output');
    if (data.type === 'questions') {
      rounds++;
      renderQuestions(data);
    } else {
      renderResult(data.prompt, data.prompt.tipo_salida);
    }
  }

  // ---- Preguntas
  function renderQuestions(data) {
    show(el.rPanel, false);
    // Normaliza: cada pregunta es {apartado, pregunta}; admite textos sueltos
    currentQuestions = data.questions.map(q => typeof q === 'string' ? { apartado: null, pregunta: q } : q);
    el.qIntro.textContent = data.intro || 'Para afinar tu prompt, cuéntame un poco más';
    el.qForm.replaceChildren();
    currentQuestions.forEach((q, i) => {
      const wrap = document.createElement('div'); wrap.className = 'question';
      const label = document.createElement('label'); label.htmlFor = 'q' + i;
      const title = SECTION_TITLE[q.apartado];
      if (title) { // badge al inicio, en la misma línea que la pregunta
        const badge = document.createElement('span');
        badge.className = 'q-badge c-' + SECTION_COLOR[q.apartado]; badge.textContent = title;
        label.append(badge);
      }
      label.append(document.createTextNode(q.pregunta));
      const input = document.createElement('input'); input.type = 'text'; input.id = 'q' + i; input.autocomplete = 'off'; input.maxLength = 500;
      wrap.append(label, input); el.qForm.append(wrap);
    });
    show(el.qPanel, true);
    el.qForm.querySelector('input')?.focus();
  }

  function collectAnswers() {
    const lines = currentQuestions.map((q, i) => {
      const a = (document.getElementById('q' + i)?.value || '').trim();
      return `${i + 1}. ${SECTION_TITLE[q.apartado] ? '[' + SECTION_TITLE[q.apartado] + '] ' : ''}${q.pregunta}\n   Respuesta: ${a || '(sin respuesta, decide tú lo más razonable)'}`;
    });
    return 'Mis respuestas a tus preguntas:\n' + lines.join('\n');
  }

  function submitAnswers(skip = false) {
    messages.push({ role: 'user', content: skip ? 'Prefiero no responder. Genera el prompt final con lo que ya sabes.' : collectAnswers() });
    show(el.qPanel, false);
    callApi(skip || rounds >= MAX_ROUNDS);
  }

  // ---- Resultado
  function assemble(p) {
    return SECTIONS.map(([key, title]) => `## ${title}\n${(p[key] || '').trim()}`).join('\n\n');
  }

  function renderResult(p, tipo) {
    show(el.qPanel, false);
    el.output.value = assemble(p);
    show(el.rPanel, true);
    const fuentes = p.necesita_fuentes === true;
    buildAiLinks(tipo, fuentes);
    saveCurrent(tipo, fuentes);
    el.rPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  async function copyText(text) {
    try { await navigator.clipboard.writeText(text); return true; }
    catch {
      const ta = document.createElement('textarea');
      ta.value = text; ta.style.cssText = 'position:fixed;opacity:0;';
      (document.querySelector('dialog[open]') || document.body).append(ta);
      ta.select();
      let ok = false;
      try { ok = document.execCommand('copy'); } catch {}
      ta.remove();
      return ok;
    }
  }

  function flash(btn, msg) {
    const old = btn.dataset.label || btn.textContent; btn.dataset.label = old;
    btn.textContent = msg; setTimeout(() => { btn.textContent = old; }, 1600);
  }

  // ---- Recomendador de IA: orden por reglas a partir del catálogo (la IA de DeepSeek no decide el orden)
  function recommend(tipo, fuentes) {
    const t = ['texto', 'imagen', 'codigo', 'datos'].includes(tipo) ? tipo : 'otro';
    const rows = AI_CATALOG.ias.map(ai => {
      const blocked = t === 'imagen' && !ai.imagen;           // requisito imprescindible
      let score = ai.afinidad[t] ?? 3;
      if (fuentes) score += ai.busqueda === 2 ? 3 : (ai.busqueda === 1 ? 0 : -1);
      return { ai, blocked, score };
    });
    rows.sort((a, b) => (a.blocked - b.blocked) || (b.score - a.score)); // a igualdad, orden del catálogo
    const top = rows.slice(0, 3).map(r => ({ ai: r.ai, why: reasonTop(r.ai, t, fuentes) }));
    const groups = new Map(); // los motivos repetidos se agrupan
    rows.slice(3).forEach(r => {
      const why = reasonNot(r.ai, t, fuentes);
      if (!groups.has(why)) groups.set(why, []);
      groups.get(why).push(r.ai);
    });
    return { top, groups: [...groups].map(([why, ias]) => ({ why, ias })) };
  }
  function reasonTop(ai, t, fuentes) {
    if (t === 'imagen') return `Genera imágenes de forma nativa. ${ai.fortaleza}`;
    if (fuentes && ai.busqueda === 2) return 'Busca en la web y cita sus fuentes: encaja con un prompt que necesita información actual y verificable.';
    if (fuentes && ai.busqueda === 1) return `${ai.fortaleza} Incluye búsqueda web (puede depender del plan).`;
    return ai.fortaleza;
  }
  function reasonNot(ai, t, fuentes) {
    if (t === 'imagen' && !ai.imagen) return 'No genera imágenes de forma nativa.';
    if (fuentes && ai.busqueda !== 2) return 'Menos centrada en buscar y citar fuentes.';
    if (ai.limite && (ai.afinidad[t] ?? 3) <= 2) return ai.limite;
    return 'Buena alternativa, pero para este tipo de prompt las tres primeras encajan algo mejor según el criterio de Promptiful.';
  }

  // Enlace a una IA: copia el prompt (versión editada) y, si la web lo admite, lo abre ya escrito
  function makeAiLink(ai, text, cls) {
    const a = document.createElement('a');
    a.textContent = text; a.href = ai.url; a.target = '_blank'; a.rel = 'noopener noreferrer';
    if (cls) a.className = cls;
    a.title = `Copia el prompt y abre ${ai.name}`;
    a.addEventListener('click', () => {
      const prompt = el.output.value.trim();
      copyText(prompt);
      const enc = encodeURIComponent(prompt);
      a.href = (ai.param && enc.length <= MAX_URL_PROMPT) ? `${ai.url}?${ai.param}=${enc}` : ai.url;
    });
    return a;
  }

  function buildAiLinks(tipo, fuentes = false) {
    show(el.imgNote, tipo === 'imagen');
    const { top, groups } = recommend(tipo, fuentes);
    const colors = ['blue', 'red', 'yellow'];
    el.recList.replaceChildren();
    top.forEach(({ ai, why }, i) => {
      const li = document.createElement('li'); li.className = 'rec-item c-' + colors[i];
      const num = document.createElement('span'); num.className = 'rec-num'; num.textContent = String(i + 1);
      const body = document.createElement('div'); body.className = 'rec-body';
      const head = document.createElement('div'); head.className = 'rec-head';
      const name = document.createElement('strong'); name.textContent = ai.name; head.append(name);
      if (i === 0) {
        const tag = document.createElement('span'); tag.className = 'rec-star'; tag.textContent = 'Recomendada'; head.append(tag);
      }
      const reason = document.createElement('p'); reason.textContent = why;
      body.append(head, reason);
      const open = makeAiLink(ai, 'Abrir', 'rec-open');
      open.setAttribute('aria-label', `Copiar el prompt y abrir ${ai.name}`);
      li.append(num, body, open);
      el.recList.append(li);
    });
    el.recOthers.replaceChildren();
    groups.forEach(({ why, ias }) => {
      const g = document.createElement('div'); g.className = 'rec-group';
      const r = document.createElement('p'); r.className = 'rec-reason'; r.textContent = why;
      const list = document.createElement('div'); list.className = 'ia-list';
      ias.forEach(ai => list.append(makeAiLink(ai, ai.name)));
      g.append(r, list);
      el.recOthers.append(g);
    });
    el.recMore.open = false;
    el.recNote.textContent = `Recomendaciones orientativas, según el criterio editorial de Promptiful (revisado en ${AI_CATALOG.revisado}). Las capacidades y los planes de cada IA cambian con frecuencia: compruébalo en su web.`;
  }

  // ---- Modales informativos (Esc los cierra de serie al ser un <dialog>)
  function wireDialog(dialogId, openId, closeId) {
    const dlg = $(dialogId);
    $(openId).addEventListener('click', () => { dlg.showModal(); dlg.scrollTop = 0; });
    $(closeId).addEventListener('click', () => dlg.close());
    dlg.addEventListener('click', e => { if (e.target === dlg) dlg.close(); }); // clic en el fondo oscuro
  }
  wireDialog('aboutDialog', 'aboutLink', 'aboutClose');
  wireDialog('peDialog', 'peLink', 'peClose');

  // ===== Historial (guardado en el servidor, un JSON por navegador) =====
  const TYPE_LABEL = { texto: 'Texto', imagen: 'Imagen', codigo: 'Código', datos: 'Datos', otro: 'Otro' };
  const svgIcon = d => `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="${d}"/></svg>`;
  const ICON = {
    clock: svgIcon('M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.954 8.954 0 0 0 13 21a9 9 0 0 0 0-18zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z'),
    starOn: svgIcon('M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z'),
    starOff: svgIcon('M22 9.24l-7.19-.62L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27 18.18 21l-1.63-7.03L22 9.24zM12 15.4l-3.76 2.27 1-4.28-3.32-2.88 4.38-.38L12 6.1l1.71 4.04 4.38.38-3.32 2.88 1 4.28L12 15.4z'),
    copy: svgIcon('M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z'),
    edit: svgIcon('M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a.996.996 0 0 0 0-1.41l-2.34-2.34a.996.996 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z'),
    trash: svgIcon('M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z'),
  };
  const norm = t => (t || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  const suggest = $('suggest'), searchWrap = $('searchWrap');
  let historyItems = [];
  let editingId = null;
  let sugItems = [], sugActive = -1;

  // --- Comunicación con history.php (los fallos del historial nunca rompen la herramienta)
  async function hApi(body) {
    const res = await fetch(PF.history, body
      ? { method: 'POST', headers: pfHeaders(), body: JSON.stringify(body) }
      : { method: 'GET', headers: pfHeaders(false) });
    if (res.status === 403) throw new Error(CSRF_MSG);
    const json = await res.json();
    if (!res.ok || !json.ok) throw new Error(json.error || 'Error en el historial.');
    return json;
  }
  async function mutate(body) {
    try { const j = await hApi(body); historyItems = j.items; renderHistory(); return j; }
    catch { return null; }
  }
  async function refreshHistory() {
    try { historyItems = (await hApi()).items; } catch { /* sin historial disponible */ }
    renderHistory();
  }

  // --- Guardar el prompt actual
  async function saveCurrent(tipo, fuentes = false) {
    if (!currentRequest) return;
    const prompt = el.output.value;
    if (currentId) { await mutate({ action: 'update', id: currentId, prompt, messages }); return; }
    const myGen = gen;
    const j = await mutate({ action: 'add', request: currentRequest, prompt, type: tipo || 'otro', fuentes, messages });
    if (j && j.id && myGen === gen) currentId = j.id;
  }
  // Las ediciones que haga el usuario en el textarea también se guardan
  let editTimer = null;
  el.output.addEventListener('input', () => {
    if (!currentId) return;
    clearTimeout(editTimer);
    const id = currentId;
    editTimer = setTimeout(() => { if (id === currentId) mutate({ action: 'update', id, prompt: el.output.value }); }, 900);
  });

  // --- Abrir un prompt guardado
  async function openEntry(id) {
    let item;
    try { item = (await hApi({ action: 'get', id })).item; }
    catch { showError('No se pudo abrir ese prompt.'); return; }
    gen++;
    messages = Array.isArray(item.messages) ? item.messages : [];
    rounds = MAX_ROUNDS; currentQuestions = [];
    currentId = item.id; currentRequest = item.request;
    el.basic.value = item.request; autoGrow();
    showError(''); show(el.qPanel, false); show(el.hint, false);
    document.body.classList.add('has-output');
    el.output.value = item.prompt;
    show(el.rPanel, true);
    buildAiLinks(item.type, !!item.fuentes);
    hideSuggest();
    if (mqMobile.matches) setSidebar(false);
    el.rPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  // --- Panel lateral
  function groupOf(ts) {
    const now = new Date(), day = 86400000;
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime();
    const t = ts * 1000;
    if (t >= today) return 'Hoy';
    if (t >= today - day) return 'Ayer';
    if (t >= today - 7 * day) return 'Últimos 7 días';
    return 'Anteriores';
  }
  function fmtDate(ts) {
    const d = new Date(ts * 1000);
    return groupOf(ts) === 'Hoy'
      ? d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })
      : d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
  }
  function iconBtn(label, html, onClick, cls = '') {
    const b = document.createElement('button');
    b.type = 'button'; b.className = 'icon-btn ' + cls; b.title = label; b.setAttribute('aria-label', label);
    b.innerHTML = html; // SVG constante: nunca contiene datos del usuario
    b.addEventListener('click', e => { e.stopPropagation(); onClick(b); });
    return b;
  }
  function historyRow(it) {
    const row = document.createElement('div'); row.className = 'h-item';
    if (editingId === it.id) {
      const input = document.createElement('input');
      input.type = 'text'; input.className = 'h-edit'; input.value = it.title; input.maxLength = 120;
      input.setAttribute('aria-label', 'Nuevo título');
      let done = false;
      const finish = save => {
        if (done) return; done = true; editingId = null;
        const v = input.value.trim();
        if (save && v && v !== it.title) mutate({ action: 'update', id: it.id, title: v }); else renderHistory();
      };
      input.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); finish(true); }
        else if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); finish(false); }
      });
      input.addEventListener('blur', () => finish(true));
      row.append(input);
      setTimeout(() => { input.focus(); input.select(); });
      return row;
    }
    const main = document.createElement('button'); main.type = 'button'; main.className = 'h-main';
    const title = document.createElement('span'); title.className = 'h-title'; title.textContent = it.title;
    const meta = document.createElement('span'); meta.className = 'h-meta';
    meta.textContent = `${TYPE_LABEL[it.type] || 'Otro'} · ${fmtDate(it.updated)}`;
    main.append(title, meta);
    main.addEventListener('click', () => openEntry(it.id));
    const actions = document.createElement('div'); actions.className = 'h-actions';
    actions.append(
      iconBtn(it.fav ? 'Quitar de favoritos' : 'Marcar como favorito', it.fav ? ICON.starOn : ICON.starOff,
        () => mutate({ action: 'update', id: it.id, fav: !it.fav }), it.fav ? 'on' : ''),
      iconBtn('Copiar prompt', ICON.copy, async b => {
        if (await copyText(it.prompt)) { b.classList.add('ok'); setTimeout(() => b.classList.remove('ok'), 1200); }
      }),
      iconBtn('Renombrar', ICON.edit, () => { editingId = it.id; renderHistory(); }),
      iconBtn('Borrar', ICON.trash, () => { if (confirm('¿Borrar este prompt del historial?')) mutate({ action: 'delete', id: it.id }); })
    );
    row.append(main, actions);
    return row;
  }
  function renderHistory() {
    const list = $('historyList');
    const q = norm($('historySearch').value.trim());
    $('historyClear').hidden = !historyItems.length;
    const items = historyItems.filter(it => !q || norm(`${it.title} ${it.request} ${it.prompt}`).includes(q));
    list.replaceChildren();
    if (!items.length) {
      const p = document.createElement('p'); p.className = 'h-empty';
      p.textContent = historyItems.length ? 'Sin resultados.' : 'Aún no hay prompts guardados. Los que generes aparecerán aquí.';
      list.append(p); return;
    }
    const groups = new Map();
    items.forEach(it => {
      const g = it.fav ? 'Favoritos' : groupOf(it.updated);
      if (!groups.has(g)) groups.set(g, []);
      groups.get(g).push(it);
    });
    ['Favoritos', 'Hoy', 'Ayer', 'Últimos 7 días', 'Anteriores'].forEach(g => {
      if (!groups.has(g)) return;
      const h = document.createElement('h3'); h.className = 'h-group'; h.textContent = g; list.append(h);
      groups.get(g).forEach(it => list.append(historyRow(it)));
    });
  }

  // --- Recientes bajo la barra
  function hideSuggest() { suggest.hidden = true; searchWrap.classList.remove('open'); sugActive = -1; }
  function showSuggest() {
    const q = norm(el.basic.value.trim());
    sugItems = historyItems.filter(it => !q || norm(`${it.request} ${it.title}`).includes(q)).slice(0, 5);
    suggest.replaceChildren();
    if (!sugItems.length) { hideSuggest(); return; }
    sugItems.forEach((it, i) => {
      const li = document.createElement('li'); li.setAttribute('role', 'option'); li.id = 'sug' + i;
      li.innerHTML = ICON.clock; // SVG constante
      const text = document.createElement('span'); text.textContent = it.request;
      li.append(text);
      li.addEventListener('mousedown', e => { e.preventDefault(); openEntry(it.id); }); // mousedown: evita perder el foco
      suggest.append(li);
    });
    sugActive = -1; suggest.hidden = false; searchWrap.classList.add('open');
  }
  function moveSuggest(d) {
    const n = sugItems.length;
    sugActive = (sugActive + 1 + d + n + 1) % (n + 1) - 1; // recorre -1 (ninguno) .. n-1
    [...suggest.children].forEach((li, i) => li.classList.toggle('active', i === sugActive));
  }

  // --- Conexión del panel
  const sidebar = $('sidebar'), sideToggle = $('sideToggle');
  const mqMobile = window.matchMedia('(max-width: 700px)');
  function setSidebar(open, persist = true) {
    sidebar.classList.toggle('open', open);
    document.body.classList.toggle('side-open', open);
    sideToggle.setAttribute('aria-expanded', String(open));
    const label = open ? 'Cerrar historial' : 'Abrir historial';
    sideToggle.title = label; sideToggle.setAttribute('aria-label', label);
    if (open) { editingId = null; renderHistory(); refreshHistory(); }
    if (persist) { try { localStorage.setItem('pf_side', open ? '1' : '0'); } catch { /* almacenamiento no disponible */ } }
  }
  sideToggle.addEventListener('click', () => setSidebar(!sidebar.classList.contains('open')));
  $('sideScrim').addEventListener('click', () => setSidebar(false));
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && sidebar.classList.contains('open') && sidebar.contains(document.activeElement)) setSidebar(false);
  });
  // Escritorio: recuerda si la dejaste abierta o cerrada. Móvil: siempre empieza cerrada.
  let startOpen = false;
  try { startOpen = localStorage.getItem('pf_side') === '1'; } catch { /* ignorar */ }
  setSidebar(startOpen && !mqMobile.matches, false);
  $('historySearch').addEventListener('input', renderHistory);
  $('historyClear').addEventListener('click', () => {
    if (confirm('¿Borrar TODO el historial, incluidos los favoritos? No se puede deshacer.')) mutate({ action: 'clear' });
  });
  document.getElementById('histMax').textContent = PF.historyMax;
  renderQuota(PF.quota);
  refreshHistory();

  // ---- Eventos
  function start() {
    const text = el.basic.value.trim();
    if (!text) { el.basic.focus(); return; }
    messages = []; rounds = 0;
    currentId = null; currentRequest = text; gen++;
    hideSuggest();
    show(el.qPanel, false); show(el.rPanel, false);
    messages.push({ role: 'user', content: 'Petición del usuario: ' + text });
    callApi(false);
  }

  el.form.addEventListener('submit', e => { e.preventDefault(); start(); });
  el.basic.addEventListener('keydown', e => {
    const open = !suggest.hidden;
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      if (!open) { if (e.key === 'ArrowDown') { e.preventDefault(); showSuggest(); } return; }
      e.preventDefault(); moveSuggest(e.key === 'ArrowDown' ? 1 : -1); return;
    }
    if (e.key === 'Escape' && open) { hideSuggest(); return; }
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      if (open && sugActive >= 0) openEntry(sugItems[sugActive].id); else start();
    }
  });
  el.basic.addEventListener('input', () => { autoGrow(); showSuggest(); });
  el.basic.addEventListener('click', showSuggest);
  el.basic.addEventListener('blur', hideSuggest);
  el.improve.addEventListener('click', start);
  el.reset.addEventListener('click', resetAll);
  $('brandLink').addEventListener('click', e => {
    // Clic normal: volver al inicio sin recargar. Con Ctrl/Cmd/Shift o clic central se deja el comportamiento del enlace.
    if (e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
    e.preventDefault();
    resetAll();
    window.scrollTo(0, 0);
  });
  el.send.addEventListener('click', () => submitAnswers(false));
  el.skip.addEventListener('click', () => submitAnswers(true));
  el.qForm.addEventListener('submit', e => { e.preventDefault(); submitAnswers(false); });
  el.qForm.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); submitAnswers(false); } });
  el.copy.addEventListener('click', async () => flash(el.copy, (await copyText(el.output.value)) ? '¡Copiado!' : 'No se pudo copiar'));
  el.regen.addEventListener('click', () => {
    messages.push({ role: 'user', content: 'Genera otra versión distinta y mejorada del prompt final.' });
    callApi(true);
  });

  // Idea enviada desde la landing: se recoge una sola vez y se lanza sola
  try {
    const idea = sessionStorage.getItem('pf_idea');
    if (idea) {
      sessionStorage.removeItem('pf_idea');
      el.basic.value = idea.slice(0, 1500);
      autoGrow();
      start();
    }
  } catch (_) { /* sin almacenamiento: la herramienta se abre vacía */ }
})();
</script>
</body>
</html>
