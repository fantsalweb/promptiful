# Historial de cambios

El proyecto se subió en **un único commit** con su estado de entonces, precedido solo por el commit de GitHub con la licencia. Los hitos 1 a 9 resumen cómo se llegó hasta ahí; es una crónica escrita a posteriori, por hitos y sin fechas, no un registro de versiones publicadas. A partir del hito 10, cada cambio llega en su propia rama y commit.

## Hito 1 · Prototipo inicial (entonces «Promptfull»)

- Portada inspirada en el buscador de Google: logo con letras de colores y una barra de búsqueda centrada.
- Proxy `api.php` para DeepSeek, con la API key en un archivo `config.php` aparte.
- La API no tiene memoria entre llamadas: el cliente guarda la conversación y la reenvía completa.
- Contrato JSON entre el modelo y la interfaz: o bien preguntas, o bien el prompt final. El servidor valida la respuesta.
- Prompt final con 8 apartados (Rol, Contexto, Tema, Formato, Tono, Ejemplo, Restricciones, Indicadores de éxito) en un cuadro de texto editable.
- Enlaces a IAs: el prompt se copia al portapapeles y, donde se puede, se abre ya escrito.
- Límite de peticiones por sesión y máximo de dos rondas de preguntas.

## Hito 2 · Corrección de compatibilidad con PHP 8.5

- `curl_close()` está obsoleta desde PHP 8.5 y su aviso se imprimía antes del JSON, rompiendo la respuesta. Se eliminó la llamada.
- `display_errors` se desactiva y los errores van al log.

## Hito 3 · Tipo de salida e instrucción de ejecución

- Fallo detectado: para «Hazme un cartel para un concierto», el prompt generado devolvía una descripción del diseño y no la imagen.
- El modelo clasifica la petición (`imagen`, `texto`, `codigo`, `datos`, `otro`) y el prompt incorpora un noveno apartado, la **Instrucción de ejecución**, que ordena entregar el producto final.
- Reglas específicas para imágenes (generar, formatos, textos literales, logotipos como espacio reservado).
- La interfaz marca qué IAs suelen generar imágenes y las prioriza para ese tipo de prompt.

## Hito 4 · Marca y modales informativos

- Cambio de nombre a **Promptiful** y eslogan «beautiful prompt».
- Modal «Sobre Promptiful».
- Modal «Prompt engineering», con cada apartado explicado, un ejemplo y la etiqueta Obligatorio/Opcional.

## Hito 5 · Pulido de la interfaz

- Se quita el recuadro azul de foco de la barra de entrada.
- El logo pasa a ser un enlace al inicio.
- Se probó un botón de enviar dentro de la barra y se descartó: la lupa se sustituyó por un icono decorativo de chispas, y la acción queda en el botón «Mejorar prompt».

## Hito 6 · Historial sin cuentas

- `history.php`: un JSON por navegador, identificado con una cookie aleatoria.
- Guardado automático al generar y al editar; favoritos, búsqueda, renombrar, copiar y borrar.
- Lista de recientes bajo la barra de entrada y barra lateral plegable (columna de solo icono al cerrarla; en móvil, un panel superpuesto).
- Se amplían los límites de conversación en `config.php` para que el JSON del prompt final no se recorte al regenerar.

## Hito 7 · Landing y reestructuración

- La landing pasa a ser el `index.html` de la raíz (header, hero, qué incluye, cómo funciona, anatomía del prompt, ejemplos, preguntas frecuentes, llamada a la acción y footer).
- La herramienta se mueve a `admin/` sin cambios de código.

## Hito 8 · Badges por apartado en las preguntas

- Cada pregunta indica a qué apartado del prompt alimenta (`apartado`) y se muestra con un badge de color al inicio de la misma línea.
- Las respuestas que se envían al modelo incluyen el apartado entre corchetes.

## Hito 9 · Preparación del repositorio

- `config.php` pasa a ser `config.example.php`; la configuración real queda fuera del control de versiones.
- `.gitignore`, README y una prueba de humo del frontend con jsdom.

## Hito 10 · Recomendador de IA

- La API devuelve `necesita_fuentes` junto al tipo de salida, sin llamadas adicionales.
- Podio de tres IAs con su motivo y el resto plegado con motivos agrupados. El orden sale de reglas sobre un catálogo (`AI_CATALOG`), no del modelo.
- Las capacidades verificables (generación de imágenes, búsqueda) se comprobaron con fuentes públicas; las afinidades por tipo de prompt son criterio editorial orientativo.
- El historial guarda si el prompt necesitaba fuentes, para mantener la recomendación al reabrirlo.
- La prueba de humo del frontend cubre el recomendador.

## Hito 11 · Migración a CodeIgniter 4 (fase 1)

- El backend en PHP simple se reescribe en CodeIgniter 4.7: controladores (`Home`, `Api\Generate`, `Api\History`), librerías (`PromptRules`, `DeepSeekClient`, `Visitor`, `TrialQuota`), modelos y una migración con tres tablas (`trial_usage`, `api_calls`, `history_entries`).
- Nuevas rutas: `/` (landing), `/app` (herramienta), `/api/generate` y `/api/history`. La carpeta `admin/` desaparece.
- Protección CSRF de CodeIgniter en los POST; el frontend envía el token en una cabecera.
- **Prueba gratuita sin registro:** 3 consultas en total por navegador (cookie), con un tope de 10 por IP y caducidad a los 30 días. La interfaz muestra las consultas restantes y el servidor devuelve un error claro al agotarlas.
- **Historial anónimo en base de datos** en lugar de ficheros JSON, con un máximo de 5 prompts por navegador (antes 50). Los favoritos no se descartan.
- El máximo de rondas de preguntas se aplica también en el servidor.
- Control de coste: se registran los tokens de cada llamada y hay un tope global diario opcional.
- La IP del visitante se guarda solo como hash con sal.
- Prueba de extremo a extremo (`npm run test:e2e`): DeepSeek simulado, aplicación real con SQLite y la interfaz manejada con jsdom. Sustituye a la prueba de humo con respuestas simuladas.
- Se excluyen del esqueleto oficial el workflow de GitHub que cierra pull requests y los tests de ejemplo.
- Corrección menor: el texto de ayuda de la herramienta todavía decía «Promptfull».

## Hito 12 · Prueba en la landing y 5 pruebas gratuitas

- La portada tiene una barra de prueba: al escribir una idea y enviarla, la herramienta se abre en `/app` y la lanza sola. La idea pasa por `sessionStorage`, no por la URL.
- Bajo la barra se muestran las pruebas restantes, calculadas en el servidor. Con la prueba agotada, la barra se desactiva y lo explica.
- La prueba gratuita sube de 3 a **5 consultas** por navegador en una ventana de 30 días, y el tope por IP de 10 a 15.
- La prueba de extremo a extremo cubre la barra de la landing y el paso de la idea (32 comprobaciones).

## Hito 13 · Hueco para el vídeo de demostración

- La maqueta estática del hero se sustituye por un hueco multimedia con la franja de los cuatro colores.
- Si existe `public/media/demo.mp4` (o `.webm`) se muestra el vídeo con controles y, si hay `poster.jpg`, su imagen previa; si no, `demo.gif`; y si no hay nada, un hueco «Próximamente». No hace falta tocar código.
- La prueba de extremo a extremo cubre los tres estados (36 comprobaciones).

## Hito 14 · Prueba de extremo a extremo más robusta (Windows)

- El ejecutor arranca el servidor integrado de PHP directamente en lugar de `php spark serve`: en Windows, parar `spark serve` dejaba vivo el proceso hijo, que seguía bloqueando la base de datos de la prueba y ocupando el puerto.
- La limpieza devuelve primero tu `.env`, mata el árbol de procesos y reintenta el borrado de la base de datos sin fallar.
- Salvaguardas al empezar: si existe `.env.e2e-backup` o los puertos están ocupados, se niega a arrancar con un mensaje claro en lugar de pisar tu configuración.
- Se silencia el aviso esperado de jsdom «navegación no implementada» al enviar la barra de la landing.

