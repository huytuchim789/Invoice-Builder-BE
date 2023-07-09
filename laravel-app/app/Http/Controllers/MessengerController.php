<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;

class MessengerController extends Controller
{

    private $appChat;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->appChat = env('APP_CHAT_SERVER');
    }

    public function index(Request $request)
    {
        $url = $this->appChat . 'conversations';

        $response = Http::withHeaders([
            'Cookie' => $request->header('Cookie'), // Pass cookies from the incoming request
        ])->get($url);

        if ($response->successful()) {
            // API request was successful
            $conversations = $response->json();
            // Process the conversations data as needed
            return Response::customJson(200, $conversations, "Conversations retrieved successfully", ['Service-name' => 'chat']);
        } else {
            // API request failed
            $errorMessage = $response->body();
            return Response::customJson($response->status(), null, $errorMessage, ['Service-name' => 'chat']);
        }
    }
}
