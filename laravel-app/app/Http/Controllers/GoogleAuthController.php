<?php

namespace App\Http\Controllers;

use App\ApiCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    public function login(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'code' => 'required|string|max:255',
            ]);
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $validatedData['code'],
                'client_id' => env('GOOGLE_CLIENT_ID'),
                'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
                'grant_type' => 'authorization_code',
            ]);

            $responseAuth = json_decode($response->body());

            $userInfo = json_decode(json_encode($this->getUserInfo($responseAuth->access_token)), FALSE);
            $user = User::updateOrCreate(['email' => $userInfo->email], ['password' => $userInfo->access_token, 'avatar_url' => $userInfo->avatarUrl]);
            if (!$token = auth()->attempt(["email" => $user->email, "password" => $userInfo->access_token])) {
                return $this->respondUnAuthorizedRequest(ApiCode::INVALID_CREDENTIALS, "Unauthenticated");
            }

            return $this->respondWithToken($token);
        } catch (\Exception $e) {
            return $this->respondUnAuthorizedRequest(ApiCode::VALIDATION_ERROR, $e->getMessage());
        }
    }
    private function getUserInfo($accessToken)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept' => 'application/json'
        ])->get('https://people.googleapis.com/v1/people/me?personFields=names,emailAddresses,photos');

        $userInfo = $response->json();
        $name = $userInfo['names'][0]['displayName'];
        $email = $userInfo['emailAddresses'][0]['value'];
        $avatarUrl = $userInfo['photos'][0]['url'];

        return [
            'name' => $name,
            'email' => $email,
            'avatarUrl' => $avatarUrl,
            'access_token' => $accessToken
        ];
    }
    private function respondWithToken($token)
    {
        return $this->respond([
            'token' => $token,
            'access_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ], "Login Successful");
    }
}
