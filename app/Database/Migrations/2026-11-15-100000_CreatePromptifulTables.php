<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePromptifulTables extends Migration
{
    public function up(): void
    {
        // Consultas de la prueba gratuita (una fila por prompt final generado)
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'visitor_id' => ['type' => 'VARCHAR', 'constraint' => 32],
            'ip_hash'    => ['type' => 'VARCHAR', 'constraint' => 64],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('visitor_id');
        $this->forge->addKey('ip_hash');
        $this->forge->createTable('trial_usage', true);

        // Registro de llamadas a la IA (base de las métricas y del control de coste)
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'visitor_id'  => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'ip_hash'     => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'kind'        => ['type' => 'VARCHAR', 'constraint' => 16],           // questions | final | error
            'output_type' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'tokens_in'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'tokens_out'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'cost_eur'    => ['type' => 'DECIMAL', 'constraint' => '12,6', 'default' => 0],
            'http_status' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('created_at');
        $this->forge->createTable('api_calls', true);

        // Historial de prompts (por ahora solo anónimo, identificado por la cookie del navegador)
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'public_id'  => ['type' => 'VARCHAR', 'constraint' => 12],
            'visitor_id' => ['type' => 'VARCHAR', 'constraint' => 32],
            'title'      => ['type' => 'VARCHAR', 'constraint' => 120],
            'request'    => ['type' => 'TEXT'],
            'prompt'     => ['type' => 'MEDIUMTEXT'],
            'type'       => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'otro'],
            'fuentes'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'fav'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'messages'   => ['type' => 'MEDIUMTEXT', 'null' => true],
            'created_at' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'updated_at' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('public_id');
        $this->forge->addKey('visitor_id');
        $this->forge->createTable('history_entries', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('history_entries', true);
        $this->forge->dropTable('api_calls', true);
        $this->forge->dropTable('trial_usage', true);
    }
}
