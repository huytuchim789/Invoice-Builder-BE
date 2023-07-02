<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MessengerController extends Controller
{
    public function index(Request $request)
    {
        $url = 'http://localhost:3001/api/conversations';

        $response = Http::withHeaders([
            'Cookie' => $request->header('Cookie'), // Pass cookies from the incoming request
        ])->get($url);

        if ($response->successful()) {
            // API request was successful
            $conversations = $response->json();
            // Process the conversations data as needed

            return response()->json($conversations);
        } else {
            // API request failed
            $errorMessage = $response->body();
            return response()->json(['error' => $errorMessage], $response->status());
        }
    }
}
