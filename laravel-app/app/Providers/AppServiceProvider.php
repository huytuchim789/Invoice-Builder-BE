<?php

namespace App\Providers;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Response::mixin(new \App\Http\Mixins\ResponseMacros);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Response::macro('customJson', function ($status, $data = null, $message = null) {
            $response = [];
            $response['data'] = $data;
            $response['message'] = $message;
            return response()->json($response, $status);
        });
    }
}
