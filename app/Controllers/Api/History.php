<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\Visitor;
use App\Models\HistoryModel;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Promptiful;

/** Historial de prompts del navegador. GET /api/history lista; POST /api/history {action: add|update|delete|clear|get}. */
class History extends BaseController
{
    private const TYPES = ['imagen', 'texto', 'codigo', 'datos', 'otro'];

    public function index(): ResponseInterface
    {
        $vid = (new Visitor($this->request, $this->response))->id();

        return $this->items($vid, []);
    }

    public function action(): ResponseInterface
    {
        $cfg   = config(Promptiful::class);
        $input = $this->request->getJSON(true);
        if (! is_array($input)) {
            return $this->fail('Petición no válida.', 400);
        }
        $action  = $this->str($input['action'] ?? '', 20);
        $visitor = new Visitor($this->request, $this->response, $cfg);
        $vid     = $visitor->id();
        if ($vid === null) {
            if ($action !== 'add') {
                return $this->items(null, []);          // sin cookie todavía no hay historial
            }
            $vid = $visitor->ensure();
        }
        $model = new HistoryModel();

        switch ($action) {
            case 'get':
                $item = $model->getFor($vid, $this->str($input['id'] ?? '', 32));

                return $item === null ? $this->fail('No encontrado.', 404) : $this->items($vid, ['item' => $item]);

            case 'add':
                $request = $this->str($input['request'] ?? '', 1500);
                $prompt  = $this->str($input['prompt'] ?? '', 20000);
                if ($request === '' || $prompt === '') {
                    return $this->fail('Datos incompletos.', 400);
                }
                $type = $this->str($input['type'] ?? '', 10);
                $id   = $model->addFor(
                    $vid,
                    $request,
                    $prompt,
                    in_array($type, self::TYPES, true) ? $type : 'otro',
                    ! empty($input['fuentes']),
                    $this->cleanMessages($input['messages'] ?? [], $cfg),
                    $cfg->anonHistoryMax,
                );

                return $this->items($vid, ['id' => $id]);

            case 'update':
                $changes = [];
                if (isset($input['title'])) {
                    $title = $this->str($input['title'], 120);
                    if ($title !== '') {
                        $changes['title'] = $title;
                    }
                }
                if (array_key_exists('fav', $input)) {
                    $changes['fav'] = $input['fav'] ? 1 : 0;
                }
                $touch = false;
                if (isset($input['prompt'])) {
                    $prompt = $this->str($input['prompt'], 20000);
                    if ($prompt !== '') {
                        $changes['prompt'] = $prompt;
                        $touch             = true;
                    }
                }
                if (isset($input['messages'])) {
                    $changes['messages'] = json_encode($this->cleanMessages($input['messages'], $cfg), JSON_UNESCAPED_UNICODE);
                    $touch               = true;
                }
                if ($touch) {
                    $changes['updated_at'] = time();
                }

                return $model->updateFor($vid, $this->str($input['id'] ?? '', 32), $changes)
                    ? $this->items($vid, [])
                    : $this->fail('No encontrado.', 404);

            case 'delete':
                $model->deleteFor($vid, $this->str($input['id'] ?? '', 32));

                return $this->items($vid, []);

            case 'clear':
                $model->clearFor($vid);

                return $this->items($vid, []);
        }

        return $this->fail('Acción no válida.', 400);
    }

    /** @param array<string,mixed> $extra */
    private function items(?string $vid, array $extra): ResponseInterface
    {
        $items = $vid === null ? [] : (new HistoryModel())->listFor($vid);

        return $this->response->setJSON(array_merge(['ok' => true, 'items' => $items], $extra));
    }

    private function fail(string $message, int $status): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON(['ok' => false, 'error' => $message]);
    }

    private function str(mixed $v, int $max): string
    {
        return mb_substr(trim(is_scalar($v) ? (string) $v : ''), 0, $max);
    }

    /** @return list<array{role:string,content:string}> */
    private function cleanMessages(mixed $m, Promptiful $cfg): array
    {
        if (! is_array($m)) {
            return [];
        }
        $res = [];
        foreach ($m as $x) {
            if (! is_array($x) || ! isset($x['role'], $x['content']) || ! is_string($x['content'])) {
                continue;
            }
            if (! in_array($x['role'], ['user', 'assistant'], true)) {
                continue;
            }
            $res[] = ['role' => $x['role'], 'content' => mb_substr($x['content'], 0, $cfg->maxCharsMessage)];
        }

        return array_slice($res, -$cfg->maxMessages);
    }
}
