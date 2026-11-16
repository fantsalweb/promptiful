<?php

namespace App\Libraries;

use App\Models\TrialUsageModel;
use Config\Promptiful;

/**
 * Prueba gratuita sin registro: N consultas EN TOTAL (no al día) por navegador, con un
 * tope más holgado por IP para no castigar a quien comparte red. El contador caduca a los
 * `trialWindowDays` días. Una «consulta» es un prompt final generado.
 */
class TrialQuota
{
    private Promptiful $cfg;
    private TrialUsageModel $model;

    public function __construct(?Promptiful $cfg = null, ?TrialUsageModel $model = null)
    {
        $this->cfg   = $cfg ?? config(Promptiful::class);
        $this->model = $model ?? new TrialUsageModel();
    }

    /** @return array{limit:int,used:int,remaining:int,blocked:bool} */
    public function status(?string $visitorId, string $ipHash): array
    {
        $used   = $visitorId !== null ? $this->model->countVisitor($visitorId, $this->cfg->trialWindowDays) : 0;
        $usedIp = $this->model->countIp($ipHash, $this->cfg->trialWindowDays);
        $limit  = $this->cfg->trialPerVisitor;
        $blocked = $used >= $limit || $usedIp >= $this->cfg->trialPerIp;

        return ['limit' => $limit, 'used' => $used, 'remaining' => $blocked ? 0 : max(0, $limit - $used), 'blocked' => $blocked];
    }

    public function record(string $visitorId, string $ipHash): void
    {
        $this->model->insert(['visitor_id' => $visitorId, 'ip_hash' => $ipHash]);
    }
}
