<?php

return [
    'enabled' => true,
    'urls' => [
        'default' => 'prometheus',
    ],
    'allowed_ips' => [],
    'default_namespace' => 'app',
    'middleware' => [
        Spatie\Prometheus\Http\Middleware\AllowIps::class,
    ],
    'actions' => [
        'render_collectors' => Spatie\Prometheus\Actions\RenderCollectorsAction::class,
    ],
    'wipe_storage_after_rendering' => false,
    'cache' => 'redis',
];
