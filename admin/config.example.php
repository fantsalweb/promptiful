<?php
/**
 * Configuración de ejemplo de Promptiful.
 *
 * 1. Copia este archivo como `config.php` (está en el .gitignore: nunca se sube al repositorio).
 * 2. Pon tu API key de DeepSeek en `api_key`.
 *
 * Este archivo solo hace "return" de un array, así que abrirlo por URL no muestra nada.
 * Aun así, lo ideal es moverlo FUERA de la carpeta pública y cambiar la ruta del require en
 * api.php y history.php. El .htaccess incluido bloquea su acceso directo en Apache.
 */
return [
    'api_key'      => 'PON_AQUI_TU_API_KEY_DE_DEEPSEEK',
    'api_url'      => 'https://api.deepseek.com/chat/completions',
    'model'        => 'deepseek-chat',
    'temperature'  => 0.7,
    'max_tokens'   => 2000,

    // Límites básicos anti-abuso (por sesión de navegador)
    'rate_limit_requests' => 20,   // peticiones permitidas...
    'rate_limit_window'   => 600,  // ...en esta ventana (segundos)
    'max_messages'        => 20,    // mensajes máximos del historial de la conversación
    'max_chars_message'   => 12000, // caracteres máximos por mensaje (el prompt final en JSON puede ser largo)

    // Historial de prompts (un JSON por navegador, sin cuentas).
    // Lo ideal es una carpeta FUERA de la web; la ruta por defecto va protegida por data/.htaccess (Apache).
    'history_dir'         => __DIR__ . '/data/history',
    'history_max_items'   => 50,    // prompts guardados por navegador (los favoritos no se descartan)
];
