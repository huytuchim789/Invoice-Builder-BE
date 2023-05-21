<?php

namespace App\Http\Mixins;

use Illuminate\Support\Arr;

class ResponseMacros
{
    public function customJson()
    {
        return function ($statusCode = 200, $data = null, $message = null) {
            $response = [];

            if ($data !== null) {
                $response['data'] = $data;
            }

            if ($message !== null) {
                $response['message'] = $message;
            }

            return response()->json($response, $statusCode);
        };
    }
}
