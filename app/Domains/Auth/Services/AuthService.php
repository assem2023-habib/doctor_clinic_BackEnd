<?php

namespace App\Domains\Auth\Services;

use App\Domains\Auth\DTOs\LoginData;
use App\Domains\Auth\DTOs\TokenData;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use GuzzleHttp\Psr7\ServerRequest;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;

class AuthService
{
    public function __construct(
        private readonly AuthorizationServer $server,
    ) {}

    public function issueToken(LoginData $data): TokenData
    {
        $request = new ServerRequest('POST', 'http://localhost/oauth/token', [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]);

        $request = $request->withParsedBody([
            'grant_type' => 'password',
            'client_id' => config('passport.password_client_id'),
            'client_secret' => config('passport.password_client_secret'),
            'username' => $data->email,
            'password' => $data->password,
            'scope' => '',
        ]);

        try {
            $psrResponse = $this->server->respondToAccessTokenRequest($request, new GuzzleResponse());
            $body = json_decode((string) $psrResponse->getBody(), true);

            return TokenData::fromPassportResponse($body);
        } catch (OAuthServerException $e) {
            throw new \Illuminate\Auth\AuthenticationException($e->getMessage());
        }
    }

    public function refreshToken(string $refreshToken): TokenData
    {
        $request = new ServerRequest('POST', 'http://localhost/oauth/token', [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]);

        $request = $request->withParsedBody([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => config('passport.password_client_id'),
            'client_secret' => config('passport.password_client_secret'),
            'scope' => '',
        ]);

        try {
            $psrResponse = $this->server->respondToAccessTokenRequest($request, new GuzzleResponse());
            $body = json_decode((string) $psrResponse->getBody(), true);

            return TokenData::fromPassportResponse($body);
        } catch (OAuthServerException $e) {
            throw new \Illuminate\Auth\AuthenticationException($e->getMessage());
        }
    }
}
