<?php

namespace App\Providers;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
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

            if ($data !== null) {
                $response['data'] = $data;
            }

            if ($message !== null) {
                $response['message'] = $message;
            }

            return response()->json($response, $status);
        });
    }
}
