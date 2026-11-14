# Promptiful · beautiful prompt

Promptiful convierte una idea sencilla («créame un post para mi blog sobre viajes por América») en un **prompt profesional** listo para pegar en ChatGPT, Claude, Gemini o cualquier otra IA. Está pensado para personas que no saben de *prompt engineering*: escriben lo que quieren, responden unas pocas preguntas y reciben un prompt bien estructurado que pueden editar y copiar.

La interfaz imita la portada de un buscador (logo con letras de colores y una barra centrada), y los prompts se generan con la API de DeepSeek a través de un pequeño proxy en PHP que oculta la API key.

> **Estado:** prototipo / demo en desarrollo. No hay cuentas de usuario, ni cobros, ni tope de gasto. Lee [Estado y limitaciones](#estado-y-limitaciones) antes de exponerlo públicamente.

## Características

- **Preguntas dinámicas.** La IA decide qué falta en tu idea y hace de 2 a 5 preguntas cortas (máximo 2 rondas). Cada pregunta lleva un *badge* con el apartado del prompt al que va dirigida (Rol, Contexto, Tema…).
- **Prompt estructurado en 9 apartados:** Rol, Contexto, Tema, Formato, Tono, Ejemplo, Restricciones, Indicadores de éxito e Instrucción de ejecución. Se muestra en un cuadro de texto editable.
- **Detección del tipo de salida** (imagen, texto, código, datos, otro). La *Instrucción de ejecución* fija qué debe entregar la IA (por ejemplo, ordena **generar** la imagen en lugar de describirla).
- **Enlaces a IAs:** ChatGPT, Claude, Perplexity, Copilot, Grok, Gemini, DeepSeek y Mistral. El prompt se copia al portapapeles y, donde existe un parámetro de URL no oficial (`?q=`), se abre ya escrito. Para prompts de imagen se priorizan las IAs que suelen generar imágenes.
- **Historial sin cuentas.** Cada navegador recibe un identificador aleatorio y su historial se guarda en un JSON propio en el servidor: búsqueda, favoritos, renombrar, copiar y borrar. Se ofrece en una barra lateral plegable y como lista de recientes bajo la barra de entrada.
- **Landing page** explicativa, modales de ayuda («Sobre Promptiful» y «Prompt engineering»), modo oscuro y diseño adaptable a móvil.

## Cómo funciona

```
Navegador (admin/index.html)
   │  POST {messages, force_final}            (el historial de la conversación viaja completo)
   ▼
admin/api.php ── añade el prompt de sistema ──► API de DeepSeek (modo JSON)
   │  valida y normaliza la respuesta
   ▼
{type: "questions", questions: [{apartado, pregunta}]}   o   {type: "final", prompt: {...9 apartados}}

Navegador ◄──► admin/history.php ◄──► admin/data/history/<id-del-navegador>.json
```

## Estructura

```
.
├── index.html                 Landing page
├── admin/                     La herramienta
│   ├── index.html             Interfaz (HTML + CSS + JS, sin dependencias)
│   ├── api.php                Proxy a DeepSeek (oculta la API key)
│   ├── history.php            Historial por navegador (JSON)
│   ├── config.example.php     Plantilla de configuración → copiar a config.php
│   ├── .htaccess              Bloquea el acceso directo a config.php (Apache)
│   └── data/.htaccess         Bloquea el acceso a los historiales (Apache)
├── tests/smoke.js             Prueba de humo del frontend (jsdom)
└── CHANGELOG.md               Crónica de cómo ha evolucionado el proyecto
```

## Requisitos

- PHP 8.0 o superior con las extensiones `curl` y `mbstring`.
- Una API key de DeepSeek.
- Apache si quieres usar los `.htaccess` incluidos (si usas otro servidor, protege tú esos archivos o muévelos fuera de la carpeta pública).

## Instalación

```bash
git clone <url-del-repositorio> promptiful
cd promptiful
cp admin/config.example.php admin/config.php   # y escribe tu API key en 'api_key'
php -S localhost:8000                          # servidor de desarrollo
```

Abre `http://localhost:8000/` (landing) y `http://localhost:8000/admin/` (herramienta). El servidor integrado de PHP ignora los `.htaccess`; en un servidor real revisa la sección de seguridad.

## Configuración (`admin/config.php`)

| Clave | Por defecto | Descripción |
|---|---|---|
| `api_key` | — | API key de DeepSeek. **Nunca la subas al repositorio** (`config.php` está en el `.gitignore`). |
| `api_url` | `https://api.deepseek.com/chat/completions` | Endpoint compatible con el formato de chat completions. |
| `model` | `deepseek-chat` | Modelo a usar. |
| `temperature` | `0.7` | Más baja = preguntas y prompts más estables. |
| `max_tokens` | `2000` | Límite de salida por llamada. |
| `rate_limit_requests` / `rate_limit_window` | `20` / `600` | Peticiones permitidas por sesión de navegador en esa ventana (segundos). |
| `max_messages` / `max_chars_message` | `20` / `12000` | Límites del historial de conversación que acepta `api.php`. |
| `history_dir` | `admin/data/history` | Dónde se guardan los historiales. En producción, mejor **fuera** de la carpeta pública. |
| `history_max_items` | `50` | Prompts guardados por navegador (los favoritos no se descartan). |

## Seguridad y privacidad

**Implementado**

- La API key solo existe en el servidor; el navegador nunca la ve.
- El cliente no puede inyectar mensajes `system`; el servidor valida roles, longitudes y la estructura del JSON que devuelve el modelo.
- Límite de peticiones por sesión en `api.php`; los errores de PHP van al log y no a la respuesta.
- Historial: identificador aleatorio de 128 bits en una cookie `HttpOnly` y `SameSite=Lax`, validado con una expresión regular antes de tocar el sistema de archivos; escritura con bloqueo de archivo; solo acepta JSON y comprueba el `Origin`; límites de tamaño.
- La interfaz inserta el contenido del usuario o de la IA con `textContent`; `innerHTML` solo se usa con iconos SVG constantes.

**No implementado todavía**

- CAPTCHA, límite por IP, tope global de gasto diario y registro de tokens consumidos. El límite actual es **por sesión**: un script que no guarde cookies lo esquiva. Si lo expones, fija un saldo limitado en tu cuenta del proveedor.
- Cuentas de usuario y cobros.
- Cifrado de los historiales: se guardan como JSON en texto plano en el servidor.
- Protección de `data/` y `config.php` en servidores que no sean Apache (usa `history_dir` fuera de la web y mueve `config.php`).

**Privacidad:** los textos que escribe el usuario se envían a la API de DeepSeek. Conviene avisarlo en tu política de privacidad y no introducir datos personales o confidenciales.

## Decisiones técnicas

- **La API no tiene memoria entre llamadas**, así que el cliente guarda la conversación completa y la reenvía en cada petición. El servidor la valida y le antepone el prompt de sistema.
- **Contrato JSON explícito** entre el modelo y la interfaz (`questions` / `final`), con modo JSON en la API y validación en el servidor. Si el modelo devuelve algo inesperado, se descarta con un error claro.
- **Tipo de salida + instrucción de ejecución.** Surgió de un fallo real: con «Hazme un cartel para un concierto» el prompt generado producía una descripción del diseño en vez de la imagen. Ahora el prompt termina con una orden explícita de entrega.
- **Historial por navegador en JSON** en lugar de una base de datos: sin instalación y fácil de migrar a cuentas más adelante. A cambio, depende de una cookie.
- **Sin frameworks ni dependencias** en el frontend ni en el backend.

## Pruebas

```bash
npm install
npm test
```

`tests/smoke.js` carga `admin/index.html` en jsdom y simula en memoria las respuestas de `api.php` y `history.php`: comprueba el flujo de preguntas, los badges, el resultado, el guardado y la edición, el historial, la barra lateral y los recientes. **No hay pruebas automatizadas del backend PHP.**

## Estado y limitaciones

- Es una demo. `api.php` se ha probado manualmente con la API real de DeepSeek; `history.php` solo tiene la revisión de código y la simulación del frontend.
- La calidad de las preguntas y de los prompts depende del modelo y puede variar entre ejecuciones.
- La lista de IAs que generan imágenes (`AIS` en `admin/index.html`) y el parámetro `?q=` de cada web no son oficiales y pueden quedar desactualizados.
- La accesibilidad se ha cuidado (etiquetas, foco visible, enlace «Saltar al contenido» en la landing), pero no se ha auditado.
- No se han hecho comprobaciones visuales automatizadas ni pruebas en varios navegadores.

## Ideas en estudio (no son compromisos)

- Varias piezas a partir de un único briefing (p. ej. Instagram, WhatsApp y email) sin repetir preguntas.
- Proyectos o perfiles de marca guardados y plantillas por sector.
- Recomendador de IA según el tipo de prompt, con una tabla de capacidades fechada y reglas, no decidido por el propio modelo.
- Cuentas, cuotas, tope diario de gasto y suscripción.

## Sobre el desarrollo

Proyecto desarrollado de forma iterativa con ayuda de un asistente de IA (Claude, de Anthropic). El historial contiene el commit inicial de GitHub (LICENSE) y un commit con el proyecto en su estado actual; la evolución real está resumida en [CHANGELOG.md](CHANGELOG.md).

## Licencia

Consulta el archivo LICENSE.
