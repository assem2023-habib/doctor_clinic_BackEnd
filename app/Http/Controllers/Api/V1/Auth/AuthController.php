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

    public function registerPatient(RegisterPatientRequest $request): AuthResource
    {
        $dto = RegisterPatientData::fromRequest($request);
        $user = $this->registerPatientAction->execute($dto);
        $tokenData = $this->loginAction->execute(LoginData::fromCredentials($dto->email, $dto->password));

        return new AuthResource((object) compact('user', 'tokenData'));
    }

    public function registerDoctor(RegisterDoctorRequest $request): AuthResource
    {
        $dto = RegisterDoctorData::fromRequest($request);
        $user = $this->registerDoctorAction->execute($dto);
        $tokenData = $this->loginAction->execute(LoginData::fromCredentials($dto->email, $dto->password));

        return new AuthResource((object) compact('user', 'tokenData'));
    }

    public function registerReceptionist(RegisterReceptionistRequest $request): AuthResource
    {
        $dto = RegisterReceptionistData::fromRequest($request);
        $user = $this->registerReceptionistAction->execute($dto);
        $tokenData = $this->loginAction->execute(LoginData::fromCredentials($dto->email, $dto->password));

        return new AuthResource((object) compact('user', 'tokenData'));
    }

    public function login(LoginRequest $request): AuthResource
    {
        $dto = LoginData::fromRequest($request);
        $tokenData = $this->loginAction->execute($dto);
        $user = User::where('email', $dto->email)->firstOrFail();

        return new AuthResource((object) compact('user', 'tokenData'));
    }

    public function logout(): JsonResponse
    {
        $this->logoutAction->execute();

        return response()->json(['message' => __('auth.logout')]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $request->validate(['refresh_token' => 'required|string']);

        $tokenData = $this->authService->refreshToken($request->refresh_token);

        return response()->json([
            'access_token' => $tokenData->accessToken,
            'refresh_token' => $tokenData->refreshToken,
            'expires_in' => $tokenData->expiresIn,
            'token_type' => 'Bearer',
        ]);
    }

    public function deleteAccount(DeleteAccountRequest $request): JsonResponse
    {
        $this->deleteAccountAction->execute($request->user());

        return response()->json(['message' => __('auth.account_deleted')]);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->changePasswordAction->execute(
            user: $request->user(),
            newPassword: $request->new_password,
        );

        return response()->json(['message' => __('auth.password_changed')]);
    }

    public function me(Request $request): UserResource
    {
        $user = $request->user();

        return match ($user->role) {
            RoleEnum::Patient => new PatientResource($user),
            RoleEnum::Doctor => new DoctorResource($user),
            RoleEnum::Receptionist => new ReceptionistResource($user),
            default => new UserResource($user),
        };
    }
}
