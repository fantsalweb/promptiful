<?php
declare(strict_types=1);

// Los avisos/deprecations de PHP van al log, nunca a la salida (romperían el JSON)
ini_set('display_errors', '0');
ini_set('log_errors', '1');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

session_start();

function fail(string $message, int $status = 400): void {
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Método no permitido.', 405);
}

// Si mueves config.php fuera de la web, cambia esta ruta (ej: __DIR__ . '/../../promptfull_config.php')
$config = require __DIR__ . '/config.php';

if (empty($config['api_key']) || str_starts_with($config['api_key'], 'PON_AQUI')) {
    fail('Falta configurar la API key de DeepSeek en config.php.', 500);
}

// ---- Rate limit sencillo por sesión ----
$now = time();
$window = (int)$config['rate_limit_window'];
$_SESSION['hits'] = array_filter($_SESSION['hits'] ?? [], fn($t) => $t > $now - $window);
if (count($_SESSION['hits']) >= (int)$config['rate_limit_requests']) {
    fail('Has hecho demasiadas peticiones. Espera unos minutos e inténtalo de nuevo.', 429);
}
$_SESSION['hits'][] = $now;

// ---- Entrada ----
$input = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($input) || !isset($input['messages']) || !is_array($input['messages'])) {
    fail('Petición no válida.');
}

$history = [];
foreach ($input['messages'] as $m) {
    if (!is_array($m) || !isset($m['role'], $m['content']) || !is_string($m['content'])) continue;
    if (!in_array($m['role'], ['user', 'assistant'], true)) continue; // el cliente NO puede inyectar "system"
    $history[] = [
        'role'    => $m['role'],
        'content' => mb_substr($m['content'], 0, (int)$config['max_chars_message']),
    ];
}
if (!$history || count($history) > (int)$config['max_messages'] || end($history)['role'] !== 'user') {
    fail('Historial no válido.');
}
$forceFinal = !empty($input['force_final']);

// ---- Prompt de sistema (aquí está la "inteligencia" de Promptfull) ----
$system = <<<'SYS'
Eres Promptiful, un experto en prompt engineering. Tu trabajo es convertir la petición básica de un usuario SIN conocimientos técnicos en un prompt profesional, listo para pegar en cualquier IA.

PROCESO
1. Analiza la petición. Detecta qué información falta y marcaría una diferencia real en el resultado (público objetivo, ubicación, extensión, propósito, nivel de conocimiento, idioma, estilo, datos concretos...).
2. Si faltan datos importantes, haz entre 2 y 5 preguntas cortas, claras y en lenguaje cotidiano (sin jerga de prompts). Si el usuario aún no ha respondido ninguna ronda de preguntas, normalmente conviene preguntar. No preguntes lo que ya se puede deducir. Si lo que quiere es una imagen o algo visual, pregunta por lo visual: estilo, colores, uso y formato/tamaño, textos que deben aparecer y si hay logotipos.
3. Cuando tengas suficiente información (o si se te indica generar ya el resultado), entrega el prompt final. Si falta algún dato menor, asume lo más razonable y hazlo explícito dentro del prompt.

FORMATO DE RESPUESTA
Responde SIEMPRE con un único objeto JSON válido, sin texto fuera del JSON, de una de estas dos formas:

A) Necesitas más información:
{"type":"questions","intro":"frase breve y amable que explica por qué preguntas","questions":[{"apartado":"contexto","pregunta":"pregunta 1"},{"apartado":"formato","pregunta":"pregunta 2"}]}
Cada pregunta debe indicar en "apartado" a qué apartado del prompt final alimentará su respuesta, usando exactamente una de estas claves: rol, contexto, tema, formato, tono, ejemplo, restricciones, indicadores_exito, orden_final.

B) Prompt final:
{"type":"final","prompt":{
  "tipo_salida":"imagen | texto | codigo | datos | otro",
  "rol":"...",
  "contexto":"...",
  "tema":"...",
  "formato":"...",
  "tono":"...",
  "ejemplo":"...",
  "restricciones":"...",
  "indicadores_exito":"...",
  "orden_final":"..."
}}

TIPO DE SALIDA Y ORDEN DE ENTREGA
- tipo_salida clasifica lo que el usuario quiere obtener: "imagen" (cartel, logo, ilustración, foto, diseño gráfico o cualquier cosa visual que deba GENERARSE como imagen), "texto" (artículos, posts, emails, guiones...), "codigo", "datos" (tablas, análisis) u "otro".
- REGLA DE ORO: el prompt debe lograr que la IA ENTREGUE el producto final, no una descripción, propuesta, esquema o guía sobre cómo hacerlo, salvo que el usuario pida eso expresamente.
- orden_final es el último bloque del prompt: una orden imperativa, inequívoca, que fija el entregable.
- Si tipo_salida es "imagen":
  * El prompt debe ordenar GENERAR la imagen con la herramienta de generación de imágenes de la IA.
  * rol: quien produce la imagen final (generador de imágenes / director de arte que la crea), nunca un consultor que la describe.
  * tema: empieza por "Genera una imagen de..." e indica el sujeto.
  * formato: número de imágenes, proporción u orientación (1:1, 16:9, vertical A3...) y alta resolución; no pidas bloques de texto explicativos.
  * ejemplo: breve descripción visual de la imagen esperada (composición, estilo, paleta), no un texto.
  * Textos dentro de la imagen: cítalos exactos entre comillas, pocos y breves, y exige que sean legibles y literales. Si faltan datos como fecha u hora, usa marcadores entre corchetes.
  * Si se mencionan logotipos oficiales, ordena dejarles un espacio reservado o representarlos solo como texto, sin inventar el logotipo.
  * restricciones: incluye que no entregue descripciones, bocetos ni instrucciones para un diseñador; solo la imagen.
  * Si el usuario pide varias versiones o formatos, ordena generar una imagen por cada uno, con la misma identidad gráfica.
  * orden_final: algo como "No te limites a describir el diseño ni a dar una propuesta textual. Debes generar la(s) imagen(es) final(es) usando la herramienta de generación de imágenes: [lista de formatos]. Deben ser diseños gráficos completos, no bocetos. Incorpora directamente los textos indicados respetando su jerarquía y legibilidad. Si algún texto o logotipo no puede reproducirse correctamente, indícalo después de generar la imagen."
- Si es "texto": orden_final ordena escribir directamente el contenido final completo, listo para usar, sin resumirlo, sin esquema previo y sin explicar el proceso.
- Si es "codigo": orden_final ordena entregar el código completo y funcional, con breves instrucciones de uso.
- Para "datos" u "otro": orden_final ordena entregar directamente el resultado terminado en el formato indicado.

CRITERIOS DE CALIDAD DEL PROMPT FINAL
- Está escrito para ser enviado a otra IA (segunda persona: "Actúa como...", "Escribe...").
- rol: especialidad concreta y experiencia relevante de quien debe responder.
- contexto: situación, audiencia y objetivo con los datos que dio el usuario.
- tema: qué hay que producir exactamente, con alcance claro.
- formato: estructura, extensión, secciones, tipo de entrega.
- tono: voz y estilo adecuados a la audiencia.
- ejemplo: un fragmento breve ilustrativo del resultado esperado (p. ej. un titular o un párrafo de muestra).
- restricciones: lo que debe evitar o respetar (en texto, usa guiones para listar).
- indicadores_exito: cómo saber que el resultado es bueno (en texto, usa guiones para listar).
- Responde en el mismo idioma en que escribe el usuario.
- No inventes datos del usuario que no te haya dado; si hace falta, deja marcadores como [tu ciudad].
SYS;

if ($forceFinal) {
    $system .= "\n\nINSTRUCCIÓN ACTUAL: ya no hagas más preguntas. Responde únicamente con el JSON de tipo \"final\", asumiendo lo razonable en lo que falte.";
}

$payload = [
    'model'           => $config['model'],
    'messages'        => array_merge([['role' => 'system', 'content' => $system]], $history),
    'temperature'     => $config['temperature'],
    'max_tokens'      => $config['max_tokens'],
    'response_format' => ['type' => 'json_object'],
    'stream'          => false,
];

// ---- Llamada a DeepSeek ----
$ch = curl_init($config['api_url']);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 90,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $config['api_key'],
    ],
    CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
]);
$response = curl_exec($ch);
$status   = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
unset($ch); // curl_close() está deprecada (no hace nada desde PHP 8.0); el handle se libera solo

if ($response === false) {
    error_log('[Promptfull] cURL: ' . $curlErr);
    fail('No se pudo conectar con el servicio de IA. Inténtalo de nuevo.', 502);
}
if ($status !== 200) {
    error_log('[Promptfull] DeepSeek HTTP ' . $status . ': ' . $response);
    fail('El servicio de IA devolvió un error (' . $status . ').', 502);
}

$data = json_decode($response, true);
$raw  = $data['choices'][0]['message']['content'] ?? '';
$out  = json_decode($raw, true);

// ---- Validación de la salida del modelo ----
$fields = ['rol', 'contexto', 'tema', 'formato', 'tono', 'ejemplo', 'restricciones', 'indicadores_exito', 'orden_final'];
$valid = false;
if (is_array($out) && ($out['type'] ?? '') === 'questions' && !$forceFinal
    && !empty($out['questions']) && is_array($out['questions'])) {
    // Cada pregunta: {apartado, pregunta}. Se aceptan también textos sueltos y se descartan apartados desconocidos.
    $qs = [];
    foreach ($out['questions'] as $q) {
        if (is_array($q)) {
            $text = trim((string)($q['pregunta'] ?? $q['question'] ?? ''));
            $ap   = strtolower(trim((string)($q['apartado'] ?? '')));
        } else {
            $text = is_scalar($q) ? trim((string)$q) : '';
            $ap   = '';
        }
        if ($text === '') continue;
        $qs[] = ['apartado' => in_array($ap, $fields, true) ? $ap : null, 'pregunta' => $text];
    }
    $out['questions'] = $qs;
    $valid = count($qs) > 0;
} elseif (is_array($out) && ($out['type'] ?? '') === 'final' && isset($out['prompt']) && is_array($out['prompt'])) {
    foreach ($fields as $f) {
        $v = $out['prompt'][$f] ?? '';
        $out['prompt'][$f] = is_array($v) ? '- ' . implode("\n- ", array_map('strval', $v)) : (string)$v;
    }
    $tipo = strtolower(trim((string)($out['prompt']['tipo_salida'] ?? 'texto')));
    $out['prompt']['tipo_salida'] = in_array($tipo, ['imagen', 'texto', 'codigo', 'datos', 'otro'], true) ? $tipo : 'otro';
    $valid = true;
}

if (!$valid) {
    error_log('[Promptfull] Salida inesperada: ' . $raw);
    fail('La IA devolvió una respuesta inesperada. Vuelve a intentarlo.', 502);
}

echo json_encode(['ok' => true, 'data' => $out, 'raw' => $raw], JSON_UNESCAPED_UNICODE);
