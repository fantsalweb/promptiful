<?php

namespace App\Models;

use CodeIgniter\Model;

class ApiCallModel extends Model
{
    protected $table         = 'api_calls';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['visitor_id', 'ip_hash', 'kind', 'output_type', 'tokens_in', 'tokens_out', 'cost_eur', 'http_status'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    /** Gasto estimado de hoy (sirve para el tope global diario). */
    public function spentToday(): float
    {
        // Constructor de consultas directo: first() añadiría un ORDER BY por id que MySQL
        // (ONLY_FULL_GROUP_BY) rechaza junto a una suma.
        $row = $this->db->table($this->table)->selectSum('cost_eur', 'total')->where('created_at >=', date('Y-m-d 00:00:00'))->get()->getRowArray();

        return (float) ($row['total'] ?? 0);
    }
}
