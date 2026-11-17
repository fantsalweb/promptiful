<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Ajustes de Promptiful. Cualquier valor se puede sobrescribir desde .env:
 *   promptiful.deepseekApiKey = sk-...
 *   promptiful.temperature = 0.5
 */
class Promptiful extends BaseConfig
{
    // --- DeepSeek ---
    public string $deepseekApiKey = '';
    public string $deepseekApiUrl = 'https://api.deepseek.com/chat/completions';
    public string $deepseekModel  = 'deepseek-chat';
    public float $temperature     = 0.7;
    public int $maxTokens         = 2000;

    // --- Conversación ---
    public int $maxMessages        = 20;     // mensajes máximos que acepta el servidor
    public int $maxCharsMessage    = 12000;  // caracteres máximos por mensaje
    public int $maxQuestionRounds  = 2;      // rondas de preguntas antes de forzar el prompt final

    // --- Límite de peticiones (por visitante) ---
    public int $rateLimitRequests = 20;
    public int $rateLimitWindow   = 600;     // segundos

    // --- Prueba gratuita sin registro ---
    public int $trialPerVisitor = 5;         // consultas en total por navegador (cookie)
    public int $trialPerIp      = 15;        // tope más holgado por IP (redes compartidas)
    public int $trialWindowDays = 30;        // el contador caduca pasado este tiempo

    // --- Historial anónimo ---
    public int $anonHistoryMax = 5;          // prompts guardados por navegador (los favoritos no se descartan)

    // --- Control de coste (0 = desactivado) ---
    public float $dailyBudgetEur   = 0.0;    // tope global diario; al superarlo se pausa la generación
    public float $priceInputPer1M  = 0.0;    // EUR por millón de tokens de entrada (pon el precio vigente)
    public float $priceOutputPer1M = 0.0;    // EUR por millón de tokens de salida

    // --- Privacidad ---
    public string $ipSalt = '';              // sal para guardar la IP solo como hash (si está vacía se deriva de la instalación)
}
