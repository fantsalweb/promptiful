# Historial de cambios

El proyecto se subió en **un único commit** con su estado actual, precedido solo por el commit de GitHub con la licencia. Aquí se resume cómo se llegó hasta él. Es una crónica escrita a posteriori, por hitos y sin fechas, no un registro de versiones publicadas.

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
