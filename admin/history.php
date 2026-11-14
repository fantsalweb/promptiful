<?php
declare(strict_types=1);

/**
 * Historial de prompts de Promptiful.
 * Sin cuentas: cada navegador recibe un identificador aleatorio en una cookie (pf_vid)
 * y su historial se guarda en su propio archivo JSON, así que nadie ve el de otros.
 *
 *   GET  history.php                      -> lista (sin los mensajes de contexto)
 *   POST history.php {action: add|update|delete|clear|get, ...}
 */

ini_set('display_errors', '0');
ini_set('log_errors', '1');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function s($v, int $max): string {
    return mb_substr(trim(is_scalar($v) ? (string)$v : ''), 0, $max);
}

function clean_messages($m, int $maxMsgs, int $maxChars): array {
    if (!is_array($m)) return [];
    $res = [];
    foreach ($m as $x) {
        if (!is_array($x) || !isset($x['role'], $x['content']) || !is_string($x['content'])) continue;
        if (!in_array($x['role'], ['user', 'assistant'], true)) continue;
        $res[] = ['role' => $x['role'], 'content' => mb_substr($x['content'], 0, $maxChars)];
    }
    return array_slice($res, -$maxMsgs);
}

function public_item(array $it): array {
    unset($it['messages']); // pesan mucho y solo hacen falta al abrir un prompt
    return $it;
}

/** Lee (y opcionalmente reescribe) el JSON del visitante con bloqueo de archivo. */
function with_store(string $file, callable $fn, bool $write): array {
    $fh = fopen($file, 'c+');
    if (!$fh) out(['ok' => false, 'error' => 'No se pudo abrir el almacenamiento.'], 500);
    flock($fh, $write ? LOCK_EX : LOCK_SH);
    $raw   = stream_get_contents($fh);
    $items = json_decode($raw ?: '[]', true);
    if (!is_array($items)) $items = [];
    [$items, $extra] = $fn($items);
    if ($write && empty($extra['_status'])) {
        rewind($fh);
        ftruncate($fh, 0);
        fwrite($fh, json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        fflush($fh);
    }
    flock($fh, LOCK_UN);
    fclose($fh);
    return [$items, $extra];
}

$config   = require __DIR__ . '/config.php';
$dir      = $config['history_dir'] ?? (__DIR__ . '/data/history');
$maxItems = (int)($config['history_max_items'] ?? 50);
$maxChars = (int)($config['max_chars_message'] ?? 12000);
$maxMsgs  = (int)($config['max_messages'] ?? 20);
$types    = ['imagen', 'texto', 'codigo', 'datos', 'otro'];

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'POST'], true)) out(['ok' => false, 'error' => 'Método no permitido.'], 405);

$input = [];
if ($method === 'POST') {
    // Protección CSRF básica: solo JSON y, si hay Origin, que sea el mismo host
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== 0) {
        out(['ok' => false, 'error' => 'Tipo de contenido no válido.'], 415);
    }
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin !== '' && parse_url($origin, PHP_URL_HOST) !== parse_url('//' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST)) {
        out(['ok' => false, 'error' => 'Origen no permitido.'], 403);
    }
    if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 2000000) out(['ok' => false, 'error' => 'Petición demasiado grande.'], 413);
    $input = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($input)) out(['ok' => false, 'error' => 'Petición no válida.'], 400);
}
$action = $method === 'GET' ? 'list' : s($input['action'] ?? '', 20);

// Identificador anónimo del visitante (128 bits aleatorios; se valida para evitar rutas maliciosas)
$vid    = $_COOKIE['pf_vid'] ?? '';
$hasVid = is_string($vid) && preg_match('/^[a-f0-9]{32}$/', $vid) === 1;
if (!$hasVid) {
    if ($action !== 'add') out(['ok' => true, 'items' => []]); // sin cookie no hay historial todavía
    $vid = bin2hex(random_bytes(16));
    setcookie('pf_vid', $vid, [
        'expires'  => time() + 31536000,
        'path'     => rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\') . '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
    out(['ok' => false, 'error' => 'No se pudo crear el almacenamiento.'], 500);
}
$file = rtrim($dir, '/\\') . '/' . $vid . '.json';

[$items, $extra] = with_store($file, function (array $items) use ($action, $input, $maxItems, $maxMsgs, $maxChars, $types) {
    $now = time();
    $err = fn(string $msg, int $code) => [$items, ['_status' => $code, 'error' => $msg]];

    switch ($action) {
        case 'list':
            return [$items, []];

        case 'get':
            $id = s($input['id'] ?? '', 32);
            foreach ($items as $it) {
                if (($it['id'] ?? '') === $id) return [$items, ['item' => $it]];
            }
            return $err('No encontrado.', 404);

        case 'add':
            $request = s($input['request'] ?? '', 1500);
            $prompt  = s($input['prompt'] ?? '', 20000);
            if ($request === '' || $prompt === '') return $err('Datos incompletos.', 400);
            $type = s($input['type'] ?? '', 10);
            $item = [
                'id'       => bin2hex(random_bytes(6)),
                'title'    => mb_substr($request, 0, 60),
                'request'  => $request,
                'prompt'   => $prompt,
                'type'     => in_array($type, $types, true) ? $type : 'otro',
                'fav'      => false,
                'created'  => $now,
                'updated'  => $now,
                'messages' => clean_messages($input['messages'] ?? [], $maxMsgs, $maxChars),
            ];
            array_unshift($items, $item);
            // Límite: se descartan los más antiguos que no sean favoritos
            for ($i = count($items) - 1; $i >= 0 && count($items) > $maxItems; $i--) {
                if (empty($items[$i]['fav'])) array_splice($items, $i, 1);
            }
            return [$items, ['id' => $item['id']]];

        case 'update':
            $id = s($input['id'] ?? '', 32);
            $found = false;
            foreach ($items as &$it) {
                if (($it['id'] ?? '') !== $id) continue;
                $found = true;
                if (isset($input['title'])) {
                    $t = s($input['title'], 120);
                    if ($t !== '') $it['title'] = $t;
                }
                if (array_key_exists('fav', $input)) $it['fav'] = (bool)$input['fav'];
                $touch = false;
                if (isset($input['prompt'])) {
                    $p = s($input['prompt'], 20000);
                    if ($p !== '') { $it['prompt'] = $p; $touch = true; }
                }
                if (isset($input['messages'])) {
                    $it['messages'] = clean_messages($input['messages'], $maxMsgs, $maxChars);
                    $touch = true;
                }
                if ($touch) $it['updated'] = $now;
                break;
            }
            unset($it);
            return $found ? [$items, []] : $err('No encontrado.', 404);

        case 'delete':
            $id = s($input['id'] ?? '', 32);
            $items = array_values(array_filter($items, fn($it) => ($it['id'] ?? '') !== $id));
            return [$items, []];

        case 'clear':
            return [[], []];
    }
    return $err('Acción no válida.', 400);
}, !in_array($action, ['list', 'get'], true));

if (!empty($extra['_status'])) out(['ok' => false, 'error' => $extra['error']], (int)$extra['_status']);

usort($items, fn($a, $b) => ($b['updated'] ?? 0) <=> ($a['updated'] ?? 0));
out(array_merge(['ok' => true, 'items' => array_map('public_item', $items)], $extra));
