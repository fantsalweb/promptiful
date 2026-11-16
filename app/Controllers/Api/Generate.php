<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\DeepSeekClient;
use App\Libraries\PromptRules;
use App\Libraries\TrialQuota;
use App\Libraries\Visitor;
use App\Models\ApiCallModel;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Promptiful;

/** POST /api/generate: conversación con DeepSeek (preguntas o prompt final). Sustituye al antiguo api.php. */
class Generate extends BaseController
{
    public function index(): ResponseInterface
    {
        $cfg = config(Promptiful::class);
        if ($cfg->deepseekApiKey === '') {
            return $this->fail('Falta configurar la API key de DeepSeek (promptiful.deepseekApiKey en .env).', 500, 'not_configured');
        }

        $input = $this->request->getJSON(true);
        if (! is_array($input) || ! isset($input['messages']) || ! is_array($input['messages'])) {
            return $this->fail('Petición no válida.', 400);
        }

        // Identificación anónima y límite de peticiones por visitante
        $visitor = new Visitor($this->request, $this->response, $cfg);
        $vid     = $visitor->ensure();
        $ipHash  = $visitor->ipHash();
        $key     = 'gen_' . substr($vid, 0, 16) . '_' . substr($ipHash, 0, 16);
        if (! service('throttler')->check($key, $cfg->rateLimitRequests, $cfg->rateLimitWindow)) {
            return $this->fail('Has hecho demasiadas peticiones. Espera unos minutos e inténtalo de nuevo.', 429, 'rate_limited');
        }

        // El cliente solo puede enviar mensajes de usuario y de asistente (nunca «system»)
        $history = [];
        foreach ($input['messages'] as $m) {
            if (! is_array($m) || ! isset($m['role'], $m['content']) || ! is_string($m['content'])) {
                continue;
            }
            if (! in_array($m['role'], ['user', 'assistant'], true)) {
                continue;
            }
            $history[] = ['role' => $m['role'], 'content' => mb_substr($m['content'], 0, $cfg->maxCharsMessage)];
        }
        if ($history === [] || count($history) > $cfg->maxMessages || end($history)['role'] !== 'user') {
            return $this->fail('Historial no válido.', 400);
        }

        // Prueba gratuita
        $quota = new TrialQuota($cfg);
        $state = $quota->status($vid, $ipHash);
        if ($state['blocked']) {
            return $this->fail(
                'Has usado las ' . $state['limit'] . ' consultas de prueba gratuitas. El registro, con más consultas al mes y un historial más amplio, estará disponible muy pronto.',
                429,
                'trial_exhausted',
                $state,
            );
        }

        // Tope global de gasto diario
        $calls = new ApiCallModel();
        if ($cfg->dailyBudgetEur > 0 && $calls->spentToday() >= $cfg->dailyBudgetEur) {
            return $this->fail('Hoy se ha alcanzado el límite de uso gratuito. Vuelve mañana.', 503, 'budget_exhausted', $state);
        }

        // Rondas de preguntas: el servidor también aplica el máximo
        $rounds = 0;
        foreach ($history as $m) {
            $j = $m['role'] === 'assistant' ? json_decode($m['content'], true) : null;
            if (is_array($j) && ($j['type'] ?? '') === 'questions') {
                $rounds++;
            }
        }
        $forceFinal = ! empty($input['force_final']) || $rounds >= $cfg->maxQuestionRounds;

        $result = (new DeepSeekClient($cfg))->chat(array_merge(
            [['role' => 'system', 'content' => PromptRules::systemPrompt($forceFinal)]],
            $history,
        ));

        $cost = ($result['tokensIn'] * $cfg->priceInputPer1M + $result['tokensOut'] * $cfg->priceOutputPer1M) / 1_000_000;
        $log  = fn (string $kind, ?string $type) => $calls->insert([
            'visitor_id' => $vid, 'ip_hash' => $ipHash, 'kind' => $kind, 'output_type' => $type,
            'tokens_in' => $result['tokensIn'], 'tokens_out' => $result['tokensOut'],
            'cost_eur' => $cost, 'http_status' => $result['status'],
        ]);

        if (! $result['ok']) {
            $log('error', null);

            return $this->fail('El servicio de IA no ha respondido correctamente. Inténtalo de nuevo.', 502, 'upstream_error');
        }

        $normalized = PromptRules::normalize(json_decode($result['content'], true), $forceFinal);
        if ($normalized === null) {
            $log('error', null);
            log_message('error', '[Promptiful] Salida inesperada del modelo: ' . substr($result['content'], 0, 500));

            return $this->fail('La IA devolvió una respuesta inesperada. Vuelve a intentarlo.', 502, 'bad_output');
        }

        if ($normalized['type'] === 'final') {
            $log('final', $normalized['prompt']['tipo_salida']);
            $quota->record($vid, $ipHash);                    // una consulta = un prompt final generado
            $state = $quota->status($vid, $ipHash);
        } else {
            $log('questions', null);
        }

        return $this->response->setJSON(['ok' => true, 'data' => $normalized, 'raw' => $result['content'], 'quota' => $state]);
    }

    /** @param array<string,mixed>|null $quota */
    private function fail(string $message, int $status, string $code = 'error', ?array $quota = null): ResponseInterface
    {
        $body = ['ok' => false, 'error' => $message, 'code' => $code];
        if ($quota !== null) {
            $body['quota'] = $quota;
        }

        return $this->response->setStatusCode($status)->setJSON($body);
    }
}
