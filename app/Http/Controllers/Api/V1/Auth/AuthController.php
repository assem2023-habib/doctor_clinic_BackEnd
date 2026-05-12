<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domains\Auth\Actions\ChangePasswordAction;
use App\Domains\Auth\Actions\DeleteAccountAction;
use App\Domains\Auth\Actions\LoginAction;
use App\Domains\Auth\Actions\LogoutAction;
use App\Domains\Auth\Actions\RegisterDoctorAction;
use App\Domains\Auth\Actions\RegisterPatientAction;
use App\Domains\Auth\Actions\RegisterReceptionistAction;
use App\Domains\Auth\Requests\ChangePasswordRequest;
use App\Domains\Auth\Requests\DeleteAccountRequest;
use App\Domains\Auth\DTOs\LoginData;
use App\Domains\Auth\DTOs\RegisterDoctorData;
use App\Domains\Auth\DTOs\RegisterPatientData;
use App\Domains\Auth\DTOs\RegisterReceptionistData;
use App\Domains\Auth\Requests\LoginRequest;
use App\Models\User;
use App\Domains\Auth\Requests\RegisterDoctorRequest;
use App\Domains\Auth\Requests\RegisterPatientRequest;
use App\Domains\Auth\Requests\RegisterReceptionistRequest;
use App\Domains\Auth\Resources\AuthResource;
use App\Domains\Auth\Services\AuthService;
use App\Domains\Doctors\Resources\DoctorResource;
use App\Domains\Patients\Resources\PatientResource;
use App\Domains\Receptionists\Resources\ReceptionistResource;
use App\Domains\Shared\Resources\UserResource;
use App\Domains\Shared\Responses\ApiResponse;
use App\Enums\RoleEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController
{
    public function __construct(
        private readonly RegisterPatientAction $registerPatientAction,
        private readonly RegisterDoctorAction $registerDoctorAction,
        private readonly RegisterReceptionistAction $registerReceptionistAction,
        private readonly LoginAction $loginAction,
        private readonly LogoutAction $logoutAction,
        private readonly DeleteAccountAction $deleteAccountAction,
        private readonly ChangePasswordAction $changePasswordAction,
        private readonly AuthService $authService,
    ) {}

    public function registerPatient(RegisterPatientRequest $request): JsonResponse
    {
        $dto = RegisterPatientData::fromRequest($request);
        $user = $this->registerPatientAction->execute($dto);
        $tokenData = $this->loginAction->execute(LoginData::fromCredentials($dto->email, $dto->password));

        return ApiResponse::created(
            new AuthResource((object) compact('user', 'tokenData')),
            __('auth.register_success')
        );
    }

    public function registerDoctor(RegisterDoctorRequest $request): JsonResponse
    {
        $dto = RegisterDoctorData::fromRequest($request);
        $user = $this->registerDoctorAction->execute($dto);
        $tokenData = $this->loginAction->execute(LoginData::fromCredentials($dto->email, $dto->password));

        return ApiResponse::created(
            new AuthResource((object) compact('user', 'tokenData')),
            __('auth.register_success')
        );
    }

    public function registerReceptionist(RegisterReceptionistRequest $request): JsonResponse
    {
        $dto = RegisterReceptionistData::fromRequest($request);
        $user = $this->registerReceptionistAction->execute($dto);
        $tokenData = $this->loginAction->execute(LoginData::fromCredentials($dto->email, $dto->password));

        return ApiResponse::created(
            new AuthResource((object) compact('user', 'tokenData')),
            __('auth.register_success')
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $dto = LoginData::fromRequest($request);
            $tokenData = $this->loginAction->execute($dto);
            $user = User::where('email', $dto->email)->firstOrFail();

            return ApiResponse::success(
                new AuthResource((object) compact('user', 'tokenData')),
                __('auth.login_success')
            );
        } catch (\Illuminate\Auth\AuthenticationException $e) {
            return ApiResponse::unauthorized($e->getMessage());
        }
    }

    public function logout(): JsonResponse
    {
        $this->logoutAction->execute();

        return ApiResponse::success(null, __('auth.logout_success'));
    }

    public function refresh(Request $request): JsonResponse
    {
        $request->validate(['refresh_token' => 'required|string']);

        try {
            $tokenData = $this->authService->refreshToken($request->refresh_token);

            return ApiResponse::success([
                'access_token' => $tokenData->accessToken,
                'refresh_token' => $tokenData->refreshToken,
                'expires_in' => $tokenData->expiresIn,
                'token_type' => 'Bearer',
            ], __('auth.refresh_success'));
        } catch (\Illuminate\Http\Client\RequestException $e) {
            return ApiResponse::unauthorized(__('Invalid or expired refresh token.'));
        }
    }

    public function deleteAccount(DeleteAccountRequest $request): JsonResponse
    {
        $this->deleteAccountAction->execute($request->user());

        return ApiResponse::success(null, __('auth.account_deleted'));
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->changePasswordAction->execute(
            user: $request->user(),
            newPassword: $request->new_password,
        );

        return ApiResponse::success(null, __('auth.password_changed'));
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $resource = match ($user->role) {
            RoleEnum::Patient => new PatientResource($user),
            RoleEnum::Doctor => new DoctorResource($user),
            RoleEnum::Receptionist => new ReceptionistResource($user),
            default => new UserResource($user),
        };

        return ApiResponse::success($resource, __('auth.profile_retrieved'));
    }
}
