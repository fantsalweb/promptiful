# Promptiful · beautiful prompt

Promptiful convierte una idea sencilla («créame un post para mi blog sobre viajes por América») en un **prompt profesional** listo para pegar en ChatGPT, Claude, Gemini o cualquier otra IA. Está pensado para personas que no saben de *prompt engineering*: escriben lo que quieren, responden unas pocas preguntas y reciben un prompt bien estructurado que pueden editar y copiar.

La interfaz imita la portada de un buscador (logo con letras de colores y una barra centrada). Los prompts se generan con la API de DeepSeek a través de un backend en **CodeIgniter 4** que oculta la API key y controla el uso.

> **Estado:** prototipo en desarrollo. Esta es la **fase 1** de la migración a CodeIgniter 4: misma funcionalidad que la versión en PHP simple, con prueba gratuita limitada e historial en base de datos. Todavía **no hay cuentas de usuario, panel de administración ni cobros**. Lee [Estado y limitaciones](#estado-y-limitaciones) antes de exponerlo públicamente.

## Características

- **Preguntas dinámicas.** La IA decide qué falta en tu idea y hace de 2 a 5 preguntas cortas (máximo 2 rondas, también aplicado en el servidor). Cada pregunta lleva un *badge* con el apartado del prompt al que va dirigida.
- **Prompt de 9 apartados:** Rol, Contexto, Tema, Formato, Tono, Ejemplo, Restricciones, Indicadores de éxito e Instrucción de ejecución, en un cuadro de texto editable.
- **Tipo de salida** (imagen, texto, código, datos, otro) e **instrucción de ejecución**: el prompt termina con una orden explícita de entrega (por ejemplo, *generar* la imagen en lugar de describirla).
- **Recomendador de IA.** Ordena ChatGPT, Claude, Perplexity, Copilot, Grok, Gemini, DeepSeek y Mistral según el tipo de prompt, con un podio de tres, el motivo de cada recomendación y el resto de opciones plegadas con sus explicaciones. El orden se obtiene mediante reglas sobre un catálogo editable (`AI_CATALOG` en `app/Views/tool.php`), no lo decide el modelo. La IA indica el tipo de salida y si el prompt necesita fuentes. El prompt se copia al portapapeles y, cuando existe un parámetro de URL `?q=`, se abre ya escrito.
- **Prueba gratuita sin registro:** 3 consultas en total por navegador (cookie), con un límite más amplio por IP (10) para no castigar a redes compartidas. El contador caduca a los 30 días. Una consulta es un prompt final generado: las rondas de preguntas no cuentan y regenerar sí.
- **Historial anónimo en base de datos**, ligado a la cookie del navegador: hasta 5 prompts (los favoritos no se descartan), búsqueda, favoritos, renombrar, copiar y borrar.
- **Control de abuso y de coste:** límite de peticiones por visitante, tope global diario de gasto (opcional) y registro de los tokens de cada llamada.
- Landing page, modales de ayuda, modo oscuro y diseño adaptable a móvil.

## Cómo funciona

| Ruta | Qué hace |
|---|---|
| `GET /` | Landing page |
| `GET /app` | La herramienta (recibe las rutas de la API, el token CSRF y el estado de la prueba) |
| `POST /api/generate` | Conversación con DeepSeek: devuelve preguntas o el prompt final |
| `GET /api/history`, `POST /api/history` | Historial del navegador (`add`, `get`, `update`, `delete`, `clear`) |

Flujo de la API:

```text
Api\Generate: CSRF → límite de peticiones → cuota de prueba → tope de gasto
               → llamada a DeepSeek → validación del JSON → registro de tokens
```

- **La API de DeepSeek no tiene memoria**, así que el navegador guarda la conversación y la reenvía completa. El servidor la valida y le antepone el prompt de sistema.
- **Contrato JSON** entre el modelo y la interfaz: `questions` (con `apartado` y `pregunta`) o `final` (con los 9 apartados, `tipo_salida` y `necesita_fuentes`). Lo que no cumple el contrato se descarta.

## Estructura

```text
app/
├── Config/Promptiful.php         Ajustes de Promptiful (se sobrescriben desde .env)
├── Controllers/
│   ├── Home.php                  Landing y herramienta
│   └── Api/{Generate,History}.php
├── Libraries/                    PromptRules (prompt de sistema + validación), DeepSeekClient,
│                                 Visitor (cookie + IP hasheada), TrialQuota
├── Models/                       HistoryModel, TrialUsageModel, ApiCallModel
├── Database/Migrations/          trial_usage, api_calls, history_entries
└── Views/                        landing.php, tool.php

tests/e2e/                        Prueba de extremo a extremo (DeepSeek simulado + jsdom)
env                               Plantilla de configuración → copiar a .env
```

## Requisitos

- PHP 8.2 o superior con `intl`, `mbstring` y `curl`; y `mysqli` (producción) o `sqlite3` (pruebas).
- [Composer](https://getcomposer.org/).
- MySQL o MariaDB en producción.
- Una API key de DeepSeek.
- Node 20+ solo para las pruebas.

## Instalación

```bash
git clone <url-del-repositorio> promptiful
cd promptiful
composer install
cp env .env          # en Windows: copy env .env
# edita .env: app.baseURL, la base de datos y promptiful.deepseekApiKey
php spark migrate
php spark serve
```

Abre `http://localhost:8080/` (landing) y `http://localhost:8080/app` (herramienta). Para probar sin MySQL, en `.env` usa `database.default.DBDriver = SQLite3` y `database.default.database = promptiful.sqlite`.

## Configuración

Todo se ajusta en `.env` con el prefijo `promptiful.` (los valores por defecto están en `app/Config/Promptiful.php`).

| Clave | Por defecto | Descripción |
|---|---|---|
| `deepseekApiKey` | — | API key de DeepSeek. Nunca la subas al repositorio. |
| `deepseekApiUrl` / `deepseekModel` | `https://api.deepseek.com/chat/completions` / `deepseek-chat` | Endpoint compatible con chat completions y modelo. |
| `temperature` / `maxTokens` | `0.7` / `2000` | Más baja = preguntas y prompts más estables. |
| `maxMessages` / `maxCharsMessage` | `20` / `12000` | Límites de la conversación que acepta el servidor. |
| `maxQuestionRounds` | `2` | Rondas de preguntas antes de forzar el prompt final. |
| `rateLimitRequests` / `rateLimitWindow` | `20` / `600` | Peticiones permitidas por visitante en esa ventana (segundos). |
| `trialPerVisitor` / `trialPerIp` | `3` / `10` | Consultas de prueba por navegador y por IP. |
| `trialWindowDays` | `30` | A los cuántos días caduca el contador de la prueba. |
| `anonHistoryMax` | `5` | Prompts guardados por navegador anónimo. |
| `dailyBudgetEur` | `0` | Tope global diario en euros (0 = desactivado). |
| `priceInputPer1M` / `priceOutputPer1M` | `0` / `0` | Precio por millón de tokens de entrada y salida: **ponlos tú** con el precio vigente; sin ellos el coste estimado es 0 y el tope no se activa. |
| `ipSalt` | derivada | Sal para guardar la IP solo como hash. Pon una aleatoria. |

## Despliegue

- Apunta el servidor web a la carpeta `public/` (por ejemplo, un subdominio). Es lo que recomienda CodeIgniter: así `app/`, `vendor/` y `.env` quedan fuera de la web.
- En `.env`: `CI_ENVIRONMENT = production`, `app.baseURL` con la URL pública y barra final, `cookie.secure = true` si usas HTTPS y los datos de MySQL.
- `php spark migrate` crea las tablas. `writable/` debe ser escribible por el servidor (caché, logs).
- Detrás de un proxy o CDN, configura `app.proxyIPs` para que la IP del visitante sea la real; si no, el límite por IP agrupará a todos los visitantes.
- Si no puedes cambiar el *document root*, se puede publicar el contenido de `public/` en la carpeta web y mover el resto fuera de ella, ajustando la ruta a `Paths.php` en `public/index.php`. Esa variante no está probada aquí.

## Seguridad y privacidad

**Implementado**

- La API key solo existe en el servidor. `.env` está en el `.gitignore`.
- Protección CSRF de CodeIgniter en todas las peticiones POST (el token llega en una cabecera y dura 24 h; pasado ese tiempo hay que recargar la página).
- El cliente no puede inyectar mensajes `system`; el servidor valida roles, longitudes, número de mensajes y la estructura del JSON del modelo.
- Límite de peticiones por visitante (throttler de CodeIgniter), tope de rondas de preguntas también en el servidor y tope global de gasto diario opcional.
- Identificación anónima con una cookie `HttpOnly` y `SameSite=Lax` de 128 bits aleatorios. **La IP se guarda solo como hash** (HMAC con sal), nunca en claro.
- El historial de un navegador solo es accesible con su cookie; las consultas a base de datos usan el constructor de consultas de CodeIgniter.
- La interfaz inserta el contenido del usuario o de la IA con `textContent`; `innerHTML` solo se usa con iconos SVG constantes.

**No implementado todavía**

- CAPTCHA, cuentas de usuario, panel de administración y cobros.
- Cifrado de los historiales: se guardan en texto plano en la base de datos.
- Política de privacidad, términos de uso y aviso de cookies (necesarios antes de abrirlo al público).
- Una prueba gratuita por IP puede esquivarse con una VPN; el tope global de gasto limita el daño, no lo impide.

**Privacidad:** los textos del usuario se envían a la API de DeepSeek. Hay que avisarlo en la política de privacidad y recomendar no introducir datos personales o confidenciales.

## Decisiones técnicas

- **CodeIgniter 4 en lugar de PHP simple.** Con cuentas, cuotas y un panel de administración en el horizonte, conviene apoyarse en un framework para lo delicado (CSRF, validación, migraciones, sesiones, límites de peticiones).
- **Una «consulta» es un prompt final generado.** Las rondas de preguntas no cuentan; regenerar sí, porque cuesta una llamada de salida larga.
- **El recomendador de IA lo deciden reglas, no el modelo.** DeepSeek solo indica el tipo de salida y si el prompt necesita fuentes; el orden sale de un catálogo propio con fecha de revisión visible. Así se evita delegar la selección en un modelo que puede tener sesgos o información desactualizada sobre otras herramientas.
- **Tipo de salida + instrucción de ejecución.** Surgió de un fallo real: con «Hazme un cartel para un concierto» el prompt producía una descripción del diseño en vez de la imagen.
- **Registro de cada llamada** (tokens y coste estimado): es la base del control de coste y de las futuras métricas.
- **La API no tiene memoria entre llamadas**, así que el cliente guarda la conversación completa y la reenvía en cada petición. El servidor la valida y le antepone el prompt de sistema.
- **Contrato JSON explícito** entre el modelo y la interfaz (`questions` / `final`), con modo JSON en la API y validación en el servidor. Si el modelo devuelve algo inesperado, se descarta con un error claro.

## Pruebas

```bash
npm install
npm run test:e2e      # necesita PHP en el PATH (o PHP_BIN=ruta/a/php) y composer install hecho
```

La prueba arranca un DeepSeek simulado y la aplicación real con SQLite, abre la herramienta en jsdom y la maneja como una persona. Comprueba, entre otras cosas: el flujo de preguntas y prompt final, los badges, el recomendador, el consumo de la prueba (3 consultas y bloqueo a la cuarta), el historial persistente y aislado por navegador, el rechazo sin token CSRF y el manejo de respuestas inválidas de la IA. Para no tocar tu configuración, usa un `.env` temporal y restaura el tuyo al terminar.

## Estado y limitaciones

- La prueba de extremo a extremo y las pruebas manuales se han hecho con **SQLite**. Las migraciones y las consultas no se han ejecutado contra MySQL en este entorno.
- No hay pruebas unitarias con PHPUnit ni pruebas visuales automatizadas ni en varios navegadores.
- La llamada real a la API de DeepSeek no está cubierta por las pruebas (se simula); su comportamiento depende del modelo y puede variar entre ejecuciones.
- El catálogo de IAs (`AI_CATALOG` en `app/Views/tool.php`) combina capacidades verificables y *afinidades* editoriales orientativas. Los parámetros `?q=` de cada web no son oficiales: conviene revisarlos periódicamente.
- Los historiales guardados con la versión anterior (ficheros JSON) no se migran.

## Hoja de ruta

1. **Fase 1 (esta):** migración a CodeIgniter 4, prueba gratuita de 3 consultas e historial anónimo.
2. **Fase 2:** registro con verificación de email, 20 consultas al mes, historial de 10 en base de datos e importación del historial anónimo.
3. **Fase 3:** panel de superadministrador con usuarios y métricas (embudo, uso, coste, abuso).
4. **Más adelante, solo si hay demanda:** plan de pago.

## Sobre el desarrollo

Proyecto desarrollado de forma iterativa con ayuda de un asistente de IA (Claude, de Anthropic). La evolución está resumida en [CHANGELOG.md](CHANGELOG.md).

## Licencia

Consulta el archivo [LICENSE](LICENSE).
