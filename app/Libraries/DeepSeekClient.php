<?php

namespace App\Libraries;

use CodeIgniter\HTTP\CURLRequest;
use Config\Promptiful;
use Config\Services;

/** Cliente mínimo de la API de DeepSeek (formato chat completions). */
class DeepSeekClient
{
    private Promptiful $cfg;
    private ?CURLRequest $http;

    public function __construct(?Promptiful $cfg = null, ?CURLRequest $http = null)
    {
        $this->cfg  = $cfg ?? config(Promptiful::class);
        $this->http = $http;
    }

    /**
     * @param list<array{role:string,content:string}> $messages
     * @return array{ok:bool,status:int,content:string,tokensIn:int,tokensOut:int,error:?string}
     */
    public function chat(array $messages): array
    {
        $http = $this->http ?? Services::curlrequest(['timeout' => 90, 'http_errors' => false], null, null, false);

        try {
            $res = $http->post($this->cfg->deepseekApiUrl, [
                'headers' => ['Authorization' => 'Bearer ' . $this->cfg->deepseekApiKey],
                'json'    => [
                    'model'           => $this->cfg->deepseekModel,
                    'messages'        => $messages,
                    'temperature'     => $this->cfg->temperature,
                    'max_tokens'      => $this->cfg->maxTokens,
                    'response_format' => ['type' => 'json_object'],
                    'stream'          => false,
                ],
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[Promptiful] DeepSeek: ' . $e->getMessage());

            return ['ok' => false, 'status' => 0, 'content' => '', 'tokensIn' => 0, 'tokensOut' => 0, 'error' => 'connection'];
        }

        $status = $res->getStatusCode();
        $data   = json_decode($res->getBody(), true);
        if ($status !== 200 || ! is_array($data)) {
            log_message('error', '[Promptiful] DeepSeek HTTP ' . $status . ': ' . substr((string) $res->getBody(), 0, 500));

            return ['ok' => false, 'status' => $status, 'content' => '', 'tokensIn' => 0, 'tokensOut' => 0, 'error' => 'http'];
        }

        return [
            'ok'        => true,
            'status'    => $status,
            'content'   => (string) ($data['choices'][0]['message']['content'] ?? ''),
            'tokensIn'  => (int) ($data['usage']['prompt_tokens'] ?? 0),
            'tokensOut' => (int) ($data['usage']['completion_tokens'] ?? 0),
            'error'     => null,
        ];
    }
}
