<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\JsonResponse;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        \Response::macro('json', function ($data = [], $status = 200, array $headers = [], $options = 0) {
            return new JsonResponse($data, $status, $headers, $options | JSON_UNESCAPED_UNICODE);
        });
    }
}
