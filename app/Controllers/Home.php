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
            'media'  => $this->heroMedia(),
        ]);
    }

    /**
     * Recurso del hero: si existe public/media/demo.mp4 (o .webm) se muestra el vídeo; si no, public/media/demo.gif;
     * y si no hay nada, un hueco «próximamente». Basta con copiar el archivo: no hace falta tocar código.
     *
     * @return array{state:string,mp4:bool,webm:bool,poster:bool}
     */
    private function heroMedia(): array
    {
        $has = static fn (string $file): bool => is_file(FCPATH . 'media/' . $file);
        $mp4 = $has('demo.mp4');
        $webm = $has('demo.webm');

        return [
            'state'  => ($mp4 || $webm) ? 'video' : ($has('demo.gif') ? 'gif' : 'placeholder'),
            'mp4'    => $mp4,
            'webm'   => $webm,
            'poster' => $has('poster.jpg'),
        ];
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
