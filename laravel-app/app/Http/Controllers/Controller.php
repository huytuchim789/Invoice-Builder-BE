<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    public function respond($data, $msg = null)
    {
        return ResponseBuilder::asSuccess()->withData($data)->withMessage($msg)->build();
    }

    public function respondWithMessage($msg)
    {
        return ResponseBuilder::asSuccess()->withMessage($msg)->build();
    }

    public function respondWithError($api_code, $http_code, $msg)
    {
        return ResponseBuilder::asError($api_code)->withHttpCode($http_code)->withMessage($msg)->build();
    }

    public function respondBadRequest($api_code, $msg)
    {
        return $this->respondWithError($api_code, 400, $msg);
    }
    public function respondUnAuthorizedRequest($api_code, $msg)
    {
        return $this->respondWithError($api_code, 401, $msg);
    }
    public function respondNotFound($api_code, $msg)
    {
        return $this->respondWithError($api_code, 404, $msg);
    }
}
