<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Historial de prompts de un visitante anónimo. Las fechas son marcas de tiempo
 * (enteros) y se gestionan a mano: renombrar o marcar favorito no cambia «updated».
 */
class HistoryModel extends Model
{
    protected $table         = 'history_entries';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['public_id', 'visitor_id', 'title', 'request', 'prompt', 'type', 'fuentes', 'fav', 'messages', 'created_at', 'updated_at'];

    /** @return list<array<string,mixed>> sin los mensajes de contexto, del más reciente al más antiguo */
    public function listFor(string $visitorId): array
    {
        $rows = $this->where('visitor_id', $visitorId)->orderBy('updated_at', 'DESC')->orderBy('id', 'DESC')->findAll();

        return array_map([$this, 'publicItem'], $rows);
    }

    /** @return array<string,mixed>|null con los mensajes incluidos */
    public function getFor(string $visitorId, string $publicId): ?array
    {
        $row = $this->where('visitor_id', $visitorId)->where('public_id', $publicId)->first();
        if ($row === null) {
            return null;
        }
        $item             = $this->publicItem($row);
        $item['messages'] = json_decode((string) $row['messages'], true) ?: [];

        return $item;
    }

    /** @param list<array{role:string,content:string}> $messages */
    public function addFor(string $visitorId, string $request, string $prompt, string $type, bool $fuentes, array $messages, int $max): string
    {
        $now      = time();
        $publicId = bin2hex(random_bytes(6));
        $this->insert([
            'public_id'  => $publicId,
            'visitor_id' => $visitorId,
            'title'      => mb_substr($request, 0, 60),
            'request'    => $request,
            'prompt'     => $prompt,
            'type'       => $type,
            'fuentes'    => $fuentes ? 1 : 0,
            'fav'        => 0,
            'messages'   => json_encode($messages, JSON_UNESCAPED_UNICODE),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->prune($visitorId, $max);

        return $publicId;
    }

    /** Descarta los más antiguos que no sean favoritos hasta respetar el máximo. */
    private function prune(string $visitorId, int $max): void
    {
        $count = $this->where('visitor_id', $visitorId)->countAllResults();
        if ($count <= $max) {
            return;
        }
        $old = $this->where('visitor_id', $visitorId)->where('fav', 0)->orderBy('created_at', 'ASC')->orderBy('id', 'ASC')->findAll($count - $max);
        foreach ($old as $row) {
            $this->delete($row['id']);
        }
    }

    /** @param array<string,mixed> $changes columnas ya validadas */
    public function updateFor(string $visitorId, string $publicId, array $changes): bool
    {
        $row = $this->where('visitor_id', $visitorId)->where('public_id', $publicId)->first();
        if ($row === null) {
            return false;
        }
        if ($changes !== []) {
            $this->update($row['id'], $changes);
        }

        return true;
    }

    public function deleteFor(string $visitorId, string $publicId): void
    {
        $this->where('visitor_id', $visitorId)->where('public_id', $publicId)->delete();
    }

    public function clearFor(string $visitorId): void
    {
        $this->where('visitor_id', $visitorId)->delete();
    }

    /** @param array<string,mixed> $row */
    private function publicItem(array $row): array
    {
        return [
            'id'      => $row['public_id'],
            'title'   => $row['title'],
            'request' => $row['request'],
            'prompt'  => $row['prompt'],
            'type'    => $row['type'],
            'fav'     => (bool) $row['fav'],
            'fuentes' => (bool) $row['fuentes'],
            'created' => (int) $row['created_at'],
            'updated' => (int) $row['updated_at'],
        ];
    }
}
