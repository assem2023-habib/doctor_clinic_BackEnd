<?php

namespace App\Domains\Auth\Resources;

use App\Domains\Doctors\Resources\DoctorResource;
use App\Domains\Patients\Resources\PatientResource;
use App\Domains\Receptionists\Resources\ReceptionistResource;
use App\Domains\Shared\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $this->user;

        $userResource = match (true) {
            $user->hasRole('patient') => new PatientResource($user),
            $user->hasRole('doctor') => new DoctorResource($user),
            $user->hasRole('receptionist') => new ReceptionistResource($user),
            default => new UserResource($user),
        };

        return [
            'access_token' => $this->tokenData->accessToken,
            'refresh_token' => $this->tokenData->refreshToken,
            'expires_in' => $this->tokenData->expiresIn,
            'token_type' => 'Bearer',
            'user' => $userResource,
        ];
    }
}
