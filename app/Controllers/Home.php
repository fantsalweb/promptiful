<?php

namespace App\Controllers;

use App\Libraries\TrialQuota;
use App\Libraries\Visitor;
use Config\Promptiful;

class Home extends BaseController
{
    /** Landing page, con la barra de prueba y las pruebas gratuitas que le quedan al visitante. */
    public function index(): string
    {
        $cfg     = config(Promptiful::class);
        $visitor = new Visitor($this->request, $this->response, $cfg);

        return view('landing', [
            'appUrl' => site_url('app'),
            'quota'  => (new TrialQuota($cfg))->status($visitor->id(), $visitor->ipHash()),
        ]);
    }

    /** La herramienta. Recibe las rutas de la API, el token CSRF y el estado de la prueba gratuita. */
    public function tool(): string
    {
        helper('form');
        $cfg     = config(Promptiful::class);
        $visitor = new Visitor($this->request, $this->response, $cfg);
        $quota   = (new TrialQuota($cfg))->status($visitor->id(), $visitor->ipHash());

        return view('tool', [
            'appUrl' => site_url('app'),
            'pf'     => [
                'api'         => site_url('api/generate'),
                'history'     => site_url('api/history'),
                'csrf'        => csrf_hash(),
                'csrfHeader'  => csrf_header(),
                'quota'       => $quota,
                'historyMax'  => $cfg->anonHistoryMax,
            ],
        ]);
    }
}
