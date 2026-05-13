<?php

namespace App\Domains\Auth\Services;

use App\Domains\Auth\DTOs\LoginData;
use App\Domains\Auth\DTOs\TokenData;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use GuzzleHttp\Psr7\ServerRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;

class AuthService
{
    public function __construct(
        private readonly AuthorizationServer $server,
    ) {}

    public function issueToken(LoginData $data): TokenData
    {
        Log::info('[Login] Step 4a: AuthService building OAuth request', [
            'client_id' => config('passport.password_client_id'),
        ]);

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

        Log::info('[Login] Step 4b: Calling AuthorizationServer::respondToAccessTokenRequest');

        try {
            $psrResponse = $this->server->respondToAccessTokenRequest($request, new GuzzleResponse);
            $body = json_decode((string) $psrResponse->getBody(), true);

            Log::info('[Login] Step 4c: Token issued by AuthorizationServer', [
                'expires_in' => $body['expires_in'] ?? 'N/A',
            ]);

            $this->logIssuedPassportTokens(__FUNCTION__, $body);

            return TokenData::fromPassportResponse($body);
        } catch (OAuthServerException $e) {
            Log::error('[Login] Step 4c: OAuthServerException: '.$e->getMessage());
            throw new AuthenticationException($e->getMessage());
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
            $psrResponse = $this->server->respondToAccessTokenRequest($request, new GuzzleResponse);
            $body = json_decode((string) $psrResponse->getBody(), true);

            $this->logIssuedPassportTokens(__FUNCTION__, $body);

            return TokenData::fromPassportResponse($body);
        } catch (OAuthServerException $e) {
            throw new AuthenticationException($e->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function logIssuedPassportTokens(string $issuer, array $body): void
    {
        if (! config('api_auth_debug.enabled')) {
            return;
        }

        Log::info('[API Debug] OAuth tokens issued (outgoing)', [
            'direction' => 'outgoing',
            'issuer' => $issuer,
            'access_token' => $body['access_token'] ?? null,
            'refresh_token' => $body['refresh_token'] ?? null,
            'expires_in' => $body['expires_in'] ?? null,
            'token_type' => $body['token_type'] ?? null,
        ]);
    }
}
