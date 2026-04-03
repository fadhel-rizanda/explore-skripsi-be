<?php

namespace App\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Spatie\Prometheus\Facades\Prometheus;

class PrometheusServiceProvider extends ServiceProvider
{
    public function register()
    {
        Prometheus::addGauge('http_requests_total', function () {
            return (int) Cache::get('http_requests_count', 0);
        });

        Prometheus::addGauge('queue_pending', function () {
            return DB::table('jobs')->count();
        });

        Prometheus::addGauge('queue_failed', function () {
            return DB::table('failed_jobs')->count();
        });

        Prometheus::addGauge('users_total', function () {
            return \App\Models\User::count();
        });

        Prometheus::addGauge('database_size_mb', function () {
            $result = DB::select('SELECT pg_database_size(current_database()) / 1024 / 1024 as size');

            return round($result[0]->size ?? 0, 2);
        });
    }
}
