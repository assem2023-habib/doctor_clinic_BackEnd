<?php

namespace App\Domains\Auth\Services;

use App\Domains\Auth\DTOs\LoginData;
use App\Domains\Auth\DTOs\TokenData;
use Illuminate\Support\Facades\Http;

class AuthService
{
    public function issueToken(LoginData $data): TokenData
    {
        $response = Http::asForm()->post(route('passport.token'), [
            'grant_type' => 'password',
            'client_id' => config('passport.password_client_id'),
            'client_secret' => config('passport.password_client_secret'),
            'username' => $data->email,
            'password' => $data->password,
            'scope' => '',
        ]);

        $response->throw();

        return TokenData::fromPassportResponse($response->json());
    }

    public function refreshToken(string $refreshToken): TokenData
    {
        $response = Http::asForm()->post(route('passport.token'), [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => config('passport.password_client_id'),
            'client_secret' => config('passport.password_client_secret'),
            'scope' => '',
        ]);

        $response->throw();

        return TokenData::fromPassportResponse($response->json());
    }
}
