<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * Quand APP_TUNNEL=ngrok, on trust tous les proxies pour que Laravel détecte
     * correctement HTTPS et le host derrière le tunnel. En local/prod on garde
     * null (= désactivé) sauf si TRUSTED_PROXIES est défini explicitement.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;

    public function __construct()
    {
        $envProxies = env('TRUSTED_PROXIES');

        if ($envProxies) {
            $this->proxies = $envProxies === '*' ? '*' : explode(',', $envProxies);
        } elseif (env('APP_TUNNEL') === 'ngrok') {
            $this->proxies = '*';
        }
    }

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
