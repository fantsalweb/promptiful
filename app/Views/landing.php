<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark">
<title>Promptiful - beautiful prompt</title>
<meta name="description" content="Promptiful convierte una idea sencilla en un prompt profesional para ChatGPT, Claude, Gemini y cualquier otra IA. Sin saber de prompt engineering.">
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
    --btn: #1a73e8;          /* azul más oscuro para botones: el texto blanco cumple el contraste */
    --btn-hover: #1765cc;
    --text: #202124;
    --muted: #5f6368;
    --line: #dfe1e5;
    --bg: #fff;
    --surface: #fff;
    --chip: #f8f9fa;
    --shadow: 0 1px 6px rgba(32,33,36,.18);
    --shadow-lg: 0 8px 30px rgba(32,33,36,.12);
  }
  @media (prefers-color-scheme: dark) {
    :root {
      --text: #e8eaed;
      --muted: #9aa0a6;
      --line: #3c4043;
      --bg: #202124;
      --surface: #292a2d;
      --chip: #2b2c2f;
      --shadow: 0 1px 6px rgba(0,0,0,.5);
      --shadow-lg: 0 8px 30px rgba(0,0,0,.45);
    }
  }
  * { box-sizing: border-box; }
  html { scroll-behavior: smooth; scroll-padding-top: 76px; }
  @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
  body {
    margin: 0; background: var(--bg); color: var(--text);
    font: 16px/1.6 Roboto, Arial, sans-serif; -webkit-font-smoothing: antialiased;
  }
  h1, h2, h3 { font-family: "Product Sans", "Google Sans", Outfit, sans-serif; font-weight: 500; line-height: 1.15; margin: 0; }
  a { color: inherit; }
  ol, ul { list-style: none; padding: 0; margin: 0; }
  :focus-visible { outline: 2px solid var(--blue); outline-offset: 3px; border-radius: 4px; }
  .container { max-width: 1080px; margin: 0 auto; padding: 0 24px; }

  /* Colores del logo como variable reutilizable */
  .c-blue { --c: var(--blue); } .c-red { --c: var(--red); } .c-yellow { --c: var(--yellow); } .c-green { --c: var(--green); }

  .skip { position: absolute; left: -9999px; top: 8px; background: var(--btn); color: #fff; padding: 8px 14px; border-radius: 8px; z-index: 100; }
  .skip:focus { left: 12px; }

  /* Marca: mismas letras de colores que la herramienta */
  .wordmark { font-family: "Product Sans", "Google Sans", Outfit, sans-serif; font-weight: 500; letter-spacing: -1px; line-height: 1; text-decoration: none; }
  .wordmark span:nth-child(5n+1) { color: var(--blue); }
  .wordmark span:nth-child(5n+2) { color: var(--red); }
  .wordmark span:nth-child(5n+3) { color: var(--yellow); }
  .wordmark span:nth-child(5n+4) { color: var(--green); }
  .wordmark span:nth-child(5n+5) { color: var(--red); }

  .strip { height: 4px; background: linear-gradient(90deg, var(--blue) 0 25%, var(--red) 25% 50%, var(--yellow) 50% 75%, var(--green) 75% 100%); }

  /* Botones */
  .btn {
    display: inline-flex; align-items: center; justify-content: center; padding: 10px 20px; border-radius: 999px;
    font: 500 15px Roboto, Arial, sans-serif; text-decoration: none; border: 1px solid transparent; cursor: pointer;
    transition: background .15s, box-shadow .15s;
  }
  .btn-primary { background: var(--btn); color: #fff; }
  .btn-primary:hover { background: var(--btn-hover); box-shadow: var(--shadow); }
  .btn-ghost { border-color: var(--line); color: var(--text); background: transparent; }
  .btn-ghost:hover { background: var(--chip); }
  .btn-lg { padding: 13px 26px; font-size: 16px; }

  /* Header */
  .site-header {
    position: sticky; top: 0; z-index: 50; border-bottom: 1px solid var(--line);
    background: color-mix(in srgb, var(--bg) 88%, transparent); backdrop-filter: blur(8px);
  }
  .bar { display: flex; align-items: center; gap: 24px; height: 64px; }
  .bar .wordmark { font-size: 28px; }
  .nav { display: flex; gap: 4px; margin-left: auto; }
  .nav a { padding: 8px 12px; border-radius: 8px; text-decoration: none; font-size: 15px; color: var(--muted); }
  .nav a:hover { background: var(--chip); color: var(--text); }

  /* Hero */
  .hero { padding: 72px 0 80px; }
  .hero-grid { display: grid; grid-template-columns: 1.1fr .9fr; gap: 48px; align-items: center; }
  .eyebrow {
    display: inline-flex; align-items: center; gap: 10px; margin: 0 0 20px; padding: 6px 14px;
    border: 1px solid var(--line); border-radius: 999px; background: var(--surface); font-size: 14px; color: var(--muted);
  }
  .dots { display: inline-flex; gap: 4px; }
  .dots i { width: 8px; height: 8px; border-radius: 50%; }
  .dots i:nth-child(1) { background: var(--blue); } .dots i:nth-child(2) { background: var(--red); }
  .dots i:nth-child(3) { background: var(--yellow); } .dots i:nth-child(4) { background: var(--green); }
  .hero h1 { font-size: clamp(36px, 5.5vw, 60px); letter-spacing: -1px; }
  .hl { background: linear-gradient(90deg, var(--blue), var(--red), var(--yellow), var(--green)) no-repeat 0 94% / 100% .14em; padding-bottom: .05em; }
  .lead { font-size: 19px; color: var(--muted); max-width: 34em; margin: 20px 0 28px; }
  .sr-only { position: absolute; width: 1px; height: 1px; margin: -1px; padding: 0; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; border: 0; }
  .try-box {
    display: flex; align-items: flex-start; gap: 12px; max-width: 560px; padding: 8px 8px 8px 20px;
    background: var(--surface); border: 1px solid var(--line); border-radius: 28px; box-shadow: var(--shadow);
  }
  .try-box:focus-within { border-color: var(--blue); box-shadow: 0 0 0 3px color-mix(in srgb, var(--blue) 25%, transparent); }
  .try-box textarea {
    flex: 1; min-height: 24px; max-height: 140px; padding: 8px 0; border: 0; outline: 0; resize: none; background: transparent;
    color: var(--text); font: 16px/24px Roboto, Arial, sans-serif;
  }
  .try-box textarea::placeholder { color: var(--muted); }
  .try-box textarea:focus-visible { outline: 0; }
  .try-box textarea:disabled, .try-box .btn:disabled { opacity: .55; cursor: not-allowed; }
  .try-note { margin: 10px 0 20px 8px; font-size: 14px; color: var(--muted); }
  .try-note a { color: var(--btn); }
  .cta-row { display: flex; flex-wrap: wrap; gap: 12px; }
  .fineprint { margin: 16px 0 0; font-size: 14px; color: var(--muted); }

  .mock { background: var(--surface); border: 1px solid var(--line); border-radius: 20px; padding: 20px; box-shadow: var(--shadow-lg); }
  .mock-label { margin: 0 0 8px; font-size: 12px; font-weight: 500; letter-spacing: .4px; text-transform: uppercase; color: var(--muted); }
  .mock-input { display: flex; align-items: center; gap: 10px; padding: 10px 16px; border: 1px solid var(--line); border-radius: 26px; font-size: 15px; }
  .mock-input svg { flex: none; width: 20px; height: 20px; fill: var(--muted); }
  .mock-arrow { text-align: center; margin: 8px 0; font-size: 22px; color: var(--muted); }
  .mock-prompt { background: var(--chip); border-radius: 12px; padding: 14px 16px; font-size: 14px; line-height: 1.55; }
  .mock-prompt p { margin: 0 0 10px; }
  .mock-prompt p:last-child { margin: 0; }
  .more { color: var(--muted); }

  /* Etiquetas con el color del logo (fondo suave: el texto siempre es legible) */
  .tag {
    display: inline-flex; align-items: center; gap: 6px; margin-right: 6px; padding: 1px 10px 1px 8px; border-radius: 999px;
    font-size: 12px; font-weight: 500; color: var(--text);
    background: color-mix(in srgb, var(--c) 14%, transparent); border: 1px solid color-mix(in srgb, var(--c) 40%, transparent);
  }
  .tag::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: var(--c); }

  /* Secciones */
  .section { padding: 76px 0; }
  .section.alt { background: var(--chip); }
  .section-head { max-width: 640px; margin: 0 auto 44px; text-align: center; }
  .section-head h2 { font-size: clamp(28px, 3.6vw, 40px); letter-spacing: -.5px; }
  .section-head p { margin: 14px 0 0; font-size: 18px; color: var(--muted); }
  .kicker { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 12px; font-size: 14px; color: var(--muted); }
  .kicker::before { content: ""; width: 10px; height: 10px; border-radius: 50%; background: var(--c, var(--blue)); }

  .grid { display: grid; gap: 20px; }
  .grid-2 { grid-template-columns: repeat(2, 1fr); }
  .grid-3 { grid-template-columns: repeat(3, 1fr); }
  .grid-4 { grid-template-columns: repeat(4, 1fr); }

  .card { position: relative; overflow: hidden; background: var(--surface); border: 1px solid var(--line); border-radius: 16px; padding: 24px; }
  .card.accent::before { content: ""; position: absolute; inset: 0 0 auto 0; height: 4px; background: var(--c); }
  .card h3 { font-size: 20px; margin-bottom: 8px; }
  .card p { margin: 0; font-size: 15px; color: var(--muted); }
  .icon { display: grid; place-items: center; width: 44px; height: 44px; margin-bottom: 16px; border-radius: 50%; background: var(--c); }
  .icon svg { width: 22px; height: 22px; fill: #fff; }
  .c-yellow .icon svg { fill: #202124; }

  /* Números: cada uno con un color del logo (el amarillo lleva texto oscuro para el contraste) */
  .num {
    display: grid; place-items: center; flex: none; width: 48px; height: 48px; margin-bottom: 16px; border-radius: 50%;
    background: var(--c); color: #fff; font: 600 24px Outfit, sans-serif;
  }
  .c-yellow .num { color: #202124; }

  .anat { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
  .anat li { display: flex; gap: 14px; padding: 18px; background: var(--surface); border: 1px solid var(--line); border-radius: 14px; }
  .anat .num { width: 40px; height: 40px; margin: 0; font-size: 20px; }
  .anat h3 { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 4px; font-size: 17px; }
  .anat p { margin: 0; font-size: 14px; color: var(--muted); }
  .badge { padding: 2px 9px; border: 1px solid var(--line); border-radius: 999px; font: 500 12px Roboto, Arial, sans-serif; color: var(--muted); }
  .badge.req { color: var(--text); background: color-mix(in srgb, var(--blue) 14%, transparent); border-color: color-mix(in srgb, var(--blue) 40%, transparent); }

  .ex-head { display: flex; align-items: center; margin-bottom: 14px; }
  .quote { margin: 0 0 14px; padding: 12px 14px; background: var(--chip); border-radius: 10px; font-style: italic; }
  .excerpt-label { margin: 0 0 6px; font-size: 13px; color: var(--muted); }
  .excerpt {
    margin: 0; padding: 10px 14px; border-left: 4px solid var(--c); border-radius: 0 10px 10px 0;
    background: color-mix(in srgb, var(--c) 8%, transparent); font-size: 14px; line-height: 1.6;
  }
  .note { margin: 28px 0 0; text-align: center; font-size: 14px; color: var(--muted); }

  .faq { display: grid; gap: 12px; max-width: 760px; margin: 0 auto; }
  details { padding: 0 20px; background: var(--surface); border: 1px solid var(--line); border-left: 4px solid var(--c); border-radius: 14px; }
  summary { display: flex; justify-content: space-between; gap: 16px; padding: 18px 0; font-size: 17px; font-weight: 500; cursor: pointer; list-style: none; }
  summary::-webkit-details-marker { display: none; }
  summary::after { content: "+"; font-size: 24px; line-height: 1; color: var(--muted); }
  details[open] summary::after { content: "−"; }
  details p { margin: 0 0 18px; color: var(--muted); }

  .cta { position: relative; overflow: hidden; max-width: 880px; margin: 0 auto; padding: 56px 24px 48px; text-align: center; background: var(--surface); border: 1px solid var(--line); border-radius: 24px; }
  .cta .strip { position: absolute; inset: 0 0 auto 0; height: 6px; }
  .cta h2 { font-size: clamp(28px, 3.6vw, 40px); }
  .cta p { margin: 14px auto 28px; max-width: 32em; font-size: 18px; color: var(--muted); }

  /* Footer */
  .site-footer { margin-top: 76px; background: var(--chip); }
  .foot { display: grid; grid-template-columns: 1.4fr 1fr; gap: 32px; padding: 40px 24px 28px; }
  .foot .wordmark { font-size: 30px; }
  .foot p { margin: 10px 0 0; max-width: 28em; font-size: 14px; color: var(--muted); }
  .foot nav { display: flex; flex-wrap: wrap; gap: 4px 20px; align-content: flex-start; justify-content: flex-end; }
  .foot nav a { padding: 4px 0; font-size: 15px; color: var(--muted); text-decoration: none; }
  .foot nav a:hover { color: var(--text); text-decoration: underline; }
  .legal { padding: 16px 24px 28px; border-top: 1px solid var(--line); font-size: 13px; color: var(--muted); display: flex; flex-wrap: wrap; justify-content: space-between; gap: 8px; }

  @media (max-width: 900px) {
    .hero-grid { grid-template-columns: 1fr; }
    .grid-3, .grid-4, .anat { grid-template-columns: repeat(2, 1fr); }
  }
  @media (max-width: 760px) {
    .nav { display: none; }
    .bar .btn { margin-left: auto; }
    .hero { padding: 48px 0 56px; }
    .section { padding: 56px 0; }
    .foot { grid-template-columns: 1fr; }
    .foot nav { justify-content: flex-start; }
  }
  @media (max-width: 600px) {
    .grid-2, .grid-3, .grid-4, .anat { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>
<a class="skip" href="#contenido">Saltar al contenido</a>

<header class="site-header">
  <div class="container bar">
    <a class="wordmark" href="#top" aria-label="Promptiful, ir al inicio de la página"><span>P</span><span>r</span><span>o</span><span>m</span><span>p</span><span>t</span><span>i</span><span>f</span><span>u</span><span>l</span></a>
    <nav class="nav" aria-label="Principal">
      <a href="#incluye">Qué incluye</a>
      <a href="#como">Cómo funciona</a>
      <a href="#anatomia">Anatomía</a>
      <a href="#ejemplos">Ejemplos</a>
      <a href="#faq">Preguntas</a>
    </nav>
    <a class="btn btn-primary" href="<?= esc($appUrl, 'attr') ?>">Probar ahora</a>
  </div>
</header>

<main id="contenido">

  <!-- HERO -->
  <section class="hero" id="top">
    <div class="container hero-grid">
      <div>
        <p class="eyebrow"><span class="dots" aria-hidden="true"><i></i><i></i><i></i><i></i></span>beautiful prompt</p>
        <h1>Convierte una idea sencilla en un prompt <span class="hl">profesional</span></h1>
        <p class="lead">Escribe lo que quieres con tus palabras. Promptiful te hace las preguntas justas y te entrega un prompt completo, listo para ChatGPT, Claude, Gemini y cualquier otra IA.</p>
        <?php $blocked = $quota['blocked'] || $quota['remaining'] <= 0; ?>
        <form class="try-box" id="tryForm" action="<?= esc($appUrl, 'attr') ?>" method="get" data-app="<?= esc($appUrl, 'attr') ?>">
          <label class="sr-only" for="tryInput">Tu idea</label>
          <textarea id="tryInput" rows="1" maxlength="1500" placeholder="Escribe tu idea, p. ej.: créame un post para mi blog sobre viajes por América"<?= $blocked ? ' disabled' : '' ?>></textarea>
          <button class="btn btn-primary" type="submit"<?= $blocked ? ' disabled' : '' ?>>Probar</button>
        </form>
        <p class="try-note" id="tryNote">
          <?php if ($blocked): ?>
            Has usado tus <?= (int) $quota['limit'] ?> pruebas gratuitas. El registro, con más consultas al mes, llegará muy pronto. <a href="<?= esc($appUrl, 'attr') ?>">Abrir la herramienta</a>
          <?php else: ?>
            Sin registro: te quedan <?= (int) $quota['remaining'] ?> de <?= (int) $quota['limit'] ?> pruebas gratuitas.
          <?php endif; ?>
        </p>
        <div class="cta-row">
          <a class="btn btn-ghost btn-lg" href="#como">Ver cómo funciona</a>
        </div>
        <p class="fineprint">No hace falta saber de prompt engineering.</p>
      </div>

      <div class="mock" aria-hidden="true">
        <p class="mock-label">Tu idea</p>
        <div class="mock-input">
          <svg viewBox="0 0 24 24"><path d="M19 9l1.25-2.75L23 5l-2.75-1.25L19 1l-1.25 2.75L15 5l2.75 1.25L19 9zm-7.5.5L9 4 6.5 9.5 1 12l5.5 2.5L9 20l2.5-5.5L17 12l-5.5-2.5zM19 15l-1.25 2.75L15 19l2.75 1.25L19 23l1.25-2.75L23 19l-2.75-1.25L19 15z"/></svg>
          <span>créame un post para mi blog sobre viajes por América</span>
        </div>
        <div class="mock-arrow">↓</div>
        <p class="mock-label">Tu prompt Promptiful</p>
        <div class="mock-prompt">
          <p><span class="tag c-blue">Rol</span>Actúa como una periodista de viajes con 10 años de experiencia en Latinoamérica.</p>
          <p><span class="tag c-red">Contexto</span>Blog de viajes low cost; lectores jóvenes que preparan su primer viaje a América.</p>
          <p><span class="tag c-yellow">Tema</span>Las 5 mejores rutas para recorrer América del Sur en un mes.</p>
          <p><span class="tag c-green">Formato</span>Título, introducción, 5 apartados y conclusión (800-1.000 palabras).</p>
          <p class="more">+ Tono, ejemplo, restricciones, indicadores de éxito e instrucción de ejecución</p>
        </div>
      </div>
    </div>
  </section>

  <!-- QUÉ INCLUYE -->
  <section class="section alt" id="incluye">
    <div class="container">
      <div class="section-head c-blue">
        <span class="kicker">Qué incluye</span>
        <h2>Todo lo que necesitas para pedirle bien las cosas a una IA</h2>
        <p>Promptiful se ocupa de la parte difícil: saber qué información falta y cómo estructurarla.</p>
      </div>
      <div class="grid grid-3">
        <article class="card accent c-blue">
          <div class="icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 6h-2v9H6v2c0 .55.45 1 1 1h11l4 4V7c0-.55-.45-1-1-1zm-4 6V3c0-.55-.45-1-1-1H3c-.55 0-1 .45-1 1v14l4-4h10c.55 0 1-.45 1-1z"/></svg></div>
          <h3>Preguntas inteligentes</h3>
          <p>Detecta lo que falta en tu idea y te hace unas pocas preguntas cortas, en lenguaje cotidiano. Puedes omitirlas cuando quieras.</p>
        </article>
        <article class="card accent c-red">
          <div class="icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 14h4v-4H3v4zm0 5h4v-4H3v4zM3 9h4V5H3v4zm5 5h13v-4H8v4zm0 5h13v-4H8v4zM8 5v4h13V5H8z"/></svg></div>
          <h3>Prompt bien estructurado</h3>
          <p>Rol, contexto, tema, formato, tono, ejemplo, restricciones, indicadores de éxito e instrucción de ejecución.</p>
        </article>
        <article class="card accent c-yellow">
          <div class="icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a.996.996 0 0 0 0-1.41l-2.34-2.34a.996.996 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></div>
          <h3>Editable a tu medida</h3>
          <p>El resultado aparece en un cuadro de texto: cámbialo, añade lo que quieras y cópialo con un clic.</p>
        </article>
        <article class="card accent c-green">
          <div class="icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/></svg></div>
          <h3>A tu IA favorita</h3>
          <p>Enlaces a ChatGPT, Claude, Gemini, Copilot, Grok, Perplexity y más. Copia el prompt y ábrelo donde prefieras.</p>
        </article>
        <article class="card accent c-blue">
          <div class="icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg></div>
          <h3>Pensado también para imágenes</h3>
          <p>Si pides un cartel o una ilustración, el prompt ordena generar la imagen en lugar de limitarse a describirla.</p>
        </article>
        <article class="card accent c-red">
          <div class="icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.954 8.954 0 0 0 13 21a9 9 0 0 0 0-18zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg></div>
          <h3>Historial y favoritos</h3>
          <p>Tus prompts se guardan para que los vuelvas a abrir, los busques, los renombres o los marques como favoritos.</p>
        </article>
      </div>
    </div>
  </section>

  <!-- CÓMO FUNCIONA -->
  <section class="section" id="como">
    <div class="container">
      <div class="section-head c-red">
        <span class="kicker">Cómo funciona</span>
        <h2>De una frase a un prompt profesional en cuatro pasos</h2>
        <p>Sin plantillas que rellenar y sin tecnicismos.</p>
      </div>
      <ol class="grid grid-4">
        <li class="card c-blue">
          <span class="num">1</span>
          <h3>Escribe tu idea</h3>
          <p>Cuéntalo como se te ocurra: «créame un post para mi blog sobre viajes por América».</p>
        </li>
        <li class="card c-red">
          <span class="num">2</span>
          <h3>Responde unas preguntas</h3>
          <p>Promptiful pregunta solo lo que marca la diferencia: público, estilo, formato, datos clave.</p>
        </li>
        <li class="card c-yellow">
          <span class="num">3</span>
          <h3>Revisa y edita</h3>
          <p>Recibes el prompt completo en un cuadro de texto, listo para ajustar a tu gusto.</p>
        </li>
        <li class="card c-green">
          <span class="num">4</span>
          <h3>Úsalo en tu IA</h3>
          <p>Cópialo y ábrelo en ChatGPT, Claude, Gemini o la IA que prefieras.</p>
        </li>
      </ol>
    </div>
  </section>

  <!-- ANATOMÍA -->
  <section class="section alt" id="anatomia">
    <div class="container">
      <div class="section-head c-yellow">
        <span class="kicker">Anatomía de un prompt</span>
        <h2>Nueve apartados, cuatro imprescindibles</h2>
        <p>Rol, contexto, tema e instrucción de ejecución son la base. El resto afina el resultado cuando lo necesitas.</p>
      </div>
      <ol class="anat">
        <li class="c-blue"><span class="num">1</span><div><h3>Rol <span class="badge req">Obligatorio</span></h3><p>Quién quieres que sea la IA: su especialidad y su experiencia.</p></div></li>
        <li class="c-red"><span class="num">2</span><div><h3>Contexto <span class="badge req">Obligatorio</span></h3><p>La situación, el público al que va dirigido y el objetivo.</p></div></li>
        <li class="c-yellow"><span class="num">3</span><div><h3>Tema <span class="badge req">Obligatorio</span></h3><p>Qué hay que producir exactamente y con qué alcance.</p></div></li>
        <li class="c-green"><span class="num">4</span><div><h3>Formato <span class="badge">Opcional</span></h3><p>Estructura, extensión y secciones del resultado.</p></div></li>
        <li class="c-blue"><span class="num">5</span><div><h3>Tono <span class="badge">Opcional</span></h3><p>La voz y el estilo con los que debe expresarse.</p></div></li>
        <li class="c-red"><span class="num">6</span><div><h3>Ejemplo <span class="badge">Opcional</span></h3><p>Una muestra breve de lo que esperas obtener.</p></div></li>
        <li class="c-yellow"><span class="num">7</span><div><h3>Restricciones <span class="badge">Opcional</span></h3><p>Lo que debe evitar o respetar.</p></div></li>
        <li class="c-green"><span class="num">8</span><div><h3>Indicadores de éxito <span class="badge">Opcional</span></h3><p>Cómo saber que el resultado es bueno.</p></div></li>
        <li class="c-blue"><span class="num">9</span><div><h3>Instrucción de ejecución <span class="badge req">Obligatorio</span></h3><p>La orden final que fija qué debe entregar: el producto terminado, no una descripción.</p></div></li>
      </ol>
    </div>
  </section>

  <!-- EJEMPLOS -->
  <section class="section" id="ejemplos">
    <div class="container">
      <div class="section-head c-green">
        <span class="kicker">Ejemplos</span>
        <h2>Del «hazme algo» al prompt que da resultados</h2>
        <p>Cuatro ideas sencillas y un extracto de lo que Promptiful prepara para cada una.</p>
      </div>
      <div class="grid grid-2">
        <article class="card c-blue">
          <div class="ex-head"><span class="tag">Texto</span></div>
          <p class="quote">«Créame un post para mi blog sobre viajes por América»</p>
          <p class="excerpt-label">Prompt generado (extracto)</p>
          <p class="excerpt">Actúa como una periodista de viajes con 10 años de experiencia en Latinoamérica. El post es para un blog de viajes low cost cuyos lectores preparan su primer viaje a América. Escribe un post sobre las 5 mejores rutas para recorrer América del Sur en un mes… Escribe directamente el post completo, listo para publicar.</p>
        </article>
        <article class="card c-red">
          <div class="ex-head"><span class="tag">Imagen</span></div>
          <p class="quote">«Hazme un cartel para un concierto»</p>
          <p class="excerpt-label">Prompt generado (extracto)</p>
          <p class="excerpt">Genera una imagen de un cartel de concierto de rock con estética vintage, en formato cuadrado 1:1 y vertical A3. Incluye los textos indicados y usa [fecha por confirmar] si falta el dato… No te limites a describir el diseño: genera la imagen final.</p>
        </article>
        <article class="card c-yellow">
          <div class="ex-head"><span class="tag">Datos</span></div>
          <p class="quote">«Compárame tres herramientas de email marketing»</p>
          <p class="excerpt-label">Prompt generado (extracto)</p>
          <p class="excerpt">Actúa como consultor de marketing digital. Compara tres herramientas de email marketing para una pyme y entrega una tabla con funciones, ventajas e inconvenientes… No inventes precios: indica qué datos conviene verificar.</p>
        </article>
        <article class="card c-green">
          <div class="ex-head"><span class="tag">Código</span></div>
          <p class="quote">«Una función para validar un email»</p>
          <p class="excerpt-label">Prompt generado (extracto)</p>
          <p class="excerpt">Actúa como desarrollador PHP senior. Escribe una función que valide direcciones de email y explica sus casos límite. Evita dependencias externas… Entrega el código completo y funcional, con breves instrucciones de uso.</p>
        </article>
      </div>
      <p class="note">Ejemplos ilustrativos y abreviados: el prompt real incluye los nueve apartados y se adapta a tus respuestas.</p>
    </div>
  </section>

  <!-- FAQ -->
  <section class="section alt" id="faq">
    <div class="container">
      <div class="section-head c-blue">
        <span class="kicker">Preguntas frecuentes</span>
        <h2>Lo que suele preguntarse</h2>
      </div>
      <div class="faq">
        <details class="c-blue">
          <summary>¿Necesito saber de prompt engineering?</summary>
          <p>No. Escribes tu idea con tus palabras y Promptiful se encarga de estructurarla. Si tienes curiosidad, la herramienta incluye una pequeña guía que explica cada apartado.</p>
        </details>
        <details class="c-red">
          <summary>¿Con qué IAs puedo usar el prompt?</summary>
          <p>Con cualquiera, porque el resultado es texto. Hay enlaces para abrir las principales IAs. En algunas el prompt llega ya escrito y en otras solo tienes que pegarlo, pues el prompt se copia automáticamente al portapapeles.</p>
        </details>
        <details class="c-yellow">
          <summary>¿Se guardan mis prompts?</summary>
          <p>Sí: el historial se guarda en el servidor asociado a tu navegador, sin necesidad de cuenta. Puedes borrar entradas sueltas o todo el historial cuando quieras. Si borras las cookies del navegador, no podrás recuperarlo.</p>
        </details>
        <details class="c-green">
          <summary>¿Quién genera las preguntas y el prompt?</summary>
          <p>Un modelo de IA a través de la API de DeepSeek. Por eso tu texto se envía a ese servicio: evita incluir datos personales o confidenciales en tus ideas.</p>
        </details>
        <details class="c-blue">
          <summary>¿Habrá cuentas de usuario?</summary>
          <p>Es la siguiente fase del proyecto. Ahora mismo Promptiful se puede probar directamente, sin registro.</p>
        </details>
      </div>
    </div>
  </section>

  <!-- CTA FINAL -->
  <section class="section">
    <div class="container">
      <div class="cta">
        <div class="strip" aria-hidden="true"></div>
        <h2>Prueba Promptiful ahora</h2>
        <p>Escribe una idea y mira cómo se convierte en un prompt profesional en menos de un minuto.</p>
        <a class="btn btn-primary btn-lg" href="<?= esc($appUrl, 'attr') ?>">Probar Promptiful</a>
      </div>
    </div>
  </section>
</main>

<footer class="site-footer">
  <div class="strip" aria-hidden="true"></div>
  <div class="container">
    <div class="foot">
      <div>
        <a class="wordmark" href="#top" aria-label="Promptiful, volver arriba"><span>P</span><span>r</span><span>o</span><span>m</span><span>p</span><span>t</span><span>i</span><span>f</span><span>u</span><span>l</span></a>
        <p>beautiful prompt. Convierte una idea sencilla en un prompt profesional para cualquier IA.</p>
      </div>
      <nav aria-label="Pie de página">
        <a href="#incluye">Qué incluye</a>
        <a href="#como">Cómo funciona</a>
        <a href="#anatomia">Anatomía</a>
        <a href="#ejemplos">Ejemplos</a>
        <a href="#faq">Preguntas</a>
        <a href="<?= esc($appUrl, 'attr') ?>">Probar ahora</a>
      </nav>
    </div>
    <div class="legal">
      <span>© <span id="year">2026</span> Promptiful</span>
      <span>Hecho con HTML, CSS, JavaScript y PHP. Los prompts se generan con la API de DeepSeek.</span>
    </div>
  </div>
</footer>

<script>
  document.getElementById('year').textContent = new Date().getFullYear();

  // Barra de prueba: la idea viaja a la herramienta por sessionStorage (no queda en la URL ni en los logs)
  (function () {
    const form = document.getElementById('tryForm');
    if (!form) return;
    const input = document.getElementById('tryInput');
    const grow = () => { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 140) + 'px'; };
    input.addEventListener('input', grow);
    input.addEventListener('keydown', e => {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true })); }
    });
    form.addEventListener('submit', e => {
      e.preventDefault();
      const idea = input.value.trim();
      if (!idea) { input.focus(); return; }
      try { sessionStorage.setItem('pf_idea', idea); } catch (_) { /* sin almacenamiento: se abre la herramienta vacía */ }
      window.location.href = form.dataset.app;
    });
  })();
</script>
</body>
</html>
