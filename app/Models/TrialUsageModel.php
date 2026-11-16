<?php

namespace App\Models;

use CodeIgniter\Model;

class TrialUsageModel extends Model
{
    protected $table         = 'trial_usage';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['visitor_id', 'ip_hash'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public function countVisitor(string $visitorId, int $days): int
    {
        return $this->where('visitor_id', $visitorId)->where('created_at >=', $this->since($days))->countAllResults();
    }

    public function countIp(string $ipHash, int $days): int
    {
        return $this->where('ip_hash', $ipHash)->where('created_at >=', $this->since($days))->countAllResults();
    }

    private function since(int $days): string
    {
        return date('Y-m-d H:i:s', time() - $days * 86400);
    }
}
