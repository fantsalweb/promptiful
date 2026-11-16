<?php

namespace App\Libraries;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Promptiful;

/**
 * Identificación anónima del visitante: una cookie con un id aleatorio de 128 bits y
 * la IP guardada únicamente como hash (HMAC con sal), nunca en claro.
 */
class Visitor
{
    public const COOKIE = 'pf_vid';

    public function __construct(
        private IncomingRequest $request,
        private ResponseInterface $response,
        private ?Promptiful $cfg = null,
    ) {
        $this->cfg ??= config(Promptiful::class);
    }

    /** Id del visitante si ya tiene cookie válida. */
    public function id(): ?string
    {
        $v = $this->request->getCookie(self::COOKIE);

        return (is_string($v) && preg_match('/^[a-f0-9]{32}$/', $v) === 1) ? $v : null;
    }

    /** Devuelve el id y, si no existía, crea la cookie en la respuesta. */
    public function ensure(): string
    {
        $id = $this->id();
        if ($id !== null) {
            return $id;
        }
        $id = bin2hex(random_bytes(16));
        $this->response->setCookie([
            'name'     => self::COOKIE,
            'value'    => $id,
            'expire'   => 31536000,
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => $this->request->isSecure(),
        ]);

        return $id;
    }

    public function ipHash(): string
    {
        $salt = $this->cfg->ipSalt !== '' ? $this->cfg->ipSalt : hash('sha256', ROOTPATH . 'promptiful');

        return hash_hmac('sha256', $this->request->getIPAddress(), $salt);
    }
}
