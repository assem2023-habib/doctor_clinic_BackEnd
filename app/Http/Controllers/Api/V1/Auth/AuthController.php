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
use OpenApi\Attributes as OA;

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

    #[OA\Post(
        path: '/api/v1/auth/register/patient',
        summary: 'Register a new patient',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'first_name', description: 'First name', type: 'string', example: 'Ahmed'),
                new OA\Property(property: 'last_name', description: 'Last name', type: 'string', example: 'Ali'),
                new OA\Property(property: 'username', description: 'Username (unique)', type: 'string', example: 'ahmedali'),
                new OA\Property(property: 'email', description: 'Email (unique)', type: 'string', format: 'email', example: 'patient@example.com'),
                new OA\Property(property: 'phone', description: 'Phone number', type: 'string', example: '+963912345678'),
                new OA\Property(property: 'address', description: 'Address', type: 'string', example: 'Damascus, Syria'),
                new OA\Property(property: 'gender', description: 'Gender', type: 'string', enum: ['male', 'female'], example: 'male'),
                new OA\Property(property: 'birthday_date', description: 'Birthday date', type: 'string', format: 'date', example: '1995-06-15'),
                new OA\Property(property: 'password', description: 'Password (min 8 characters)', type: 'string', format: 'password', example: 'Password@123'),
            ])
        ),
        responses: [
            new OA\Response(response: 201, description: 'Patient registered and logged in', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'access_token', description: 'JWT access token', type: 'string', example: 'eyJ0eXAiOiJKV1Qi...'),
                new OA\Property(property: 'refresh_token', description: 'Refresh token', type: 'string', example: 'def50200...'),
                new OA\Property(property: 'expires_in', description: 'Token expiration in seconds', type: 'integer', example: 31536000),
                new OA\Property(property: 'token_type', description: 'Token type', type: 'string', example: 'Bearer'),
                new OA\Property(property: 'user', description: 'User profile', type: 'object', properties: [
                    new OA\Property(property: 'id', description: 'User UUID', type: 'string', format: 'uuid', example: '019e1d0f-1ec6-7289-8cb3-eb9bdb0f1009'),
                    new OA\Property(property: 'first_name', type: 'string', example: 'Ahmed'),
                    new OA\Property(property: 'last_name', type: 'string', example: 'Ali'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'patient@example.com'),
                    new OA\Property(property: 'role', type: 'string', enum: ['admin', 'doctor', 'patient', 'receptionist'], example: 'patient'),
                    new OA\Property(property: 'phone', type: 'string', example: '+963912345678'),
                    new OA\Property(property: 'address', type: 'string', example: 'Damascus, Syria'),
                    new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'male'),
                    new OA\Property(property: 'birthday_date', type: 'string', format: 'date', example: '1995-06-15'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]),
            ])),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Validation failed'),
                new OA\Property(property: 'errors', description: 'Validation errors', type: 'object', example: ['email' => ['The email has already been taken.']]),
            ])),
        ]
    )]
    public function registerPatient(RegisterPatientRequest $request): AuthResource
    {
        $dto = RegisterPatientData::fromRequest($request);
        $user = $this->registerPatientAction->execute($dto);
        $tokenData = $this->loginAction->execute(LoginData::fromCredentials($dto->email, $dto->password));

        return new AuthResource((object) compact('user', 'tokenData'));
    }

    #[OA\Post(
        path: '/api/v1/auth/register/doctor',
        summary: 'Register a new doctor',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'first_name', description: 'First name', type: 'string', example: 'Khaled'),
                new OA\Property(property: 'last_name', description: 'Last name', type: 'string', example: 'Suleiman'),
                new OA\Property(property: 'username', description: 'Username (unique)', type: 'string', example: 'drkhaled'),
                new OA\Property(property: 'email', description: 'Email (unique)', type: 'string', format: 'email', example: 'doctor@example.com'),
                new OA\Property(property: 'phone', description: 'Phone number', type: 'string', example: '+963912345679'),
                new OA\Property(property: 'address', description: 'Address', type: 'string', example: 'Aleppo, Syria'),
                new OA\Property(property: 'gender', description: 'Gender', type: 'string', enum: ['male', 'female'], example: 'male'),
                new OA\Property(property: 'birthday_date', description: 'Birthday date', type: 'string', format: 'date', example: '1985-03-20'),
                new OA\Property(property: 'password', description: 'Password (min 8 characters)', type: 'string', format: 'password', example: 'Password@123'),
            ])
        ),
        responses: [
            new OA\Response(response: 201, description: 'Doctor registered and logged in', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'access_token', description: 'JWT access token', type: 'string', example: 'eyJ0eXAiOiJKV1Qi...'),
                new OA\Property(property: 'refresh_token', description: 'Refresh token', type: 'string', example: 'def50200...'),
                new OA\Property(property: 'expires_in', description: 'Token expiration in seconds', type: 'integer', example: 31536000),
                new OA\Property(property: 'token_type', description: 'Token type', type: 'string', example: 'Bearer'),
                new OA\Property(property: 'user', description: 'User profile', type: 'object', properties: [
                    new OA\Property(property: 'id', description: 'User UUID', type: 'string', format: 'uuid', example: '019e1d0f-1ec6-7289-8cb3-eb9bdb0f1009'),
                    new OA\Property(property: 'first_name', type: 'string', example: 'Khaled'),
                    new OA\Property(property: 'last_name', type: 'string', example: 'Suleiman'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'doctor@example.com'),
                    new OA\Property(property: 'role', type: 'string', enum: ['admin', 'doctor', 'patient', 'receptionist'], example: 'doctor'),
                    new OA\Property(property: 'phone', type: 'string', example: '+963912345679'),
                    new OA\Property(property: 'address', type: 'string', example: 'Aleppo, Syria'),
                    new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'male'),
                    new OA\Property(property: 'birthday_date', type: 'string', format: 'date', example: '1985-03-20'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]),
            ])),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Validation failed'),
                new OA\Property(property: 'errors', description: 'Validation errors', type: 'object', example: ['email' => ['The email has already been taken.']]),
            ])),
        ]
    )]
    public function registerDoctor(RegisterDoctorRequest $request): AuthResource
    {
        $dto = RegisterDoctorData::fromRequest($request);
        $user = $this->registerDoctorAction->execute($dto);
        $tokenData = $this->loginAction->execute(LoginData::fromCredentials($dto->email, $dto->password));

        return new AuthResource((object) compact('user', 'tokenData'));
    }

    #[OA\Post(
        path: '/api/v1/auth/register/receptionist',
        summary: 'Register a new receptionist',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'first_name', description: 'First name', type: 'string', example: 'Layla'),
                new OA\Property(property: 'last_name', description: 'Last name', type: 'string', example: 'Hassan'),
                new OA\Property(property: 'username', description: 'Username (unique)', type: 'string', example: 'laylah'),
                new OA\Property(property: 'email', description: 'Email (unique)', type: 'string', format: 'email', example: 'receptionist@example.com'),
                new OA\Property(property: 'phone', description: 'Phone number', type: 'string', example: '+963912345680'),
                new OA\Property(property: 'address', description: 'Address', type: 'string', example: 'Homs, Syria'),
                new OA\Property(property: 'gender', description: 'Gender', type: 'string', enum: ['male', 'female'], example: 'female'),
                new OA\Property(property: 'birthday_date', description: 'Birthday date', type: 'string', format: 'date', example: '1998-11-05'),
                new OA\Property(property: 'password', description: 'Password (min 8 characters)', type: 'string', format: 'password', example: 'Password@123'),
                new OA\Property(property: 'shift_start', description: 'Shift start time', type: 'string', format: 'time', example: '09:00'),
                new OA\Property(property: 'shift_end', description: 'Shift end time', type: 'string', format: 'time', example: '17:00'),
            ])
        ),
        responses: [
            new OA\Response(response: 201, description: 'Receptionist registered and logged in', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'access_token', description: 'JWT access token', type: 'string', example: 'eyJ0eXAiOiJKV1Qi...'),
                new OA\Property(property: 'refresh_token', description: 'Refresh token', type: 'string', example: 'def50200...'),
                new OA\Property(property: 'expires_in', description: 'Token expiration in seconds', type: 'integer', example: 31536000),
                new OA\Property(property: 'token_type', description: 'Token type', type: 'string', example: 'Bearer'),
                new OA\Property(property: 'user', description: 'User profile', type: 'object', properties: [
                    new OA\Property(property: 'id', description: 'User UUID', type: 'string', format: 'uuid', example: '019e1d0f-1ec6-7289-8cb3-eb9bdb0f1009'),
                    new OA\Property(property: 'first_name', type: 'string', example: 'Layla'),
                    new OA\Property(property: 'last_name', type: 'string', example: 'Hassan'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'receptionist@example.com'),
                    new OA\Property(property: 'role', type: 'string', enum: ['admin', 'doctor', 'patient', 'receptionist'], example: 'receptionist'),
                    new OA\Property(property: 'phone', type: 'string', example: '+963912345680'),
                    new OA\Property(property: 'address', type: 'string', example: 'Homs, Syria'),
                    new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'female'),
                    new OA\Property(property: 'birthday_date', type: 'string', format: 'date', example: '1998-11-05'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    new OA\Property(property: 'shift_start', type: 'string', format: 'time', example: '09:00'),
                    new OA\Property(property: 'shift_end', type: 'string', format: 'time', example: '17:00'),
                ]),
            ])),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Validation failed'),
                new OA\Property(property: 'errors', description: 'Validation errors', type: 'object', example: ['email' => ['The email has already been taken.']]),
            ])),
        ]
    )]
    public function registerReceptionist(RegisterReceptionistRequest $request): AuthResource
    {
        $dto = RegisterReceptionistData::fromRequest($request);
        $user = $this->registerReceptionistAction->execute($dto);
        $tokenData = $this->loginAction->execute(LoginData::fromCredentials($dto->email, $dto->password));

        return new AuthResource((object) compact('user', 'tokenData'));
    }

    #[OA\Post(
        path: '/api/v1/auth/login',
        summary: 'Login with email and password',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'email', description: 'User email', type: 'string', format: 'email', example: 'admin@gmail.com'),
                new OA\Property(property: 'password', description: 'User password', type: 'string', format: 'password', example: 'password'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login successful', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'access_token', description: 'JWT access token', type: 'string', example: 'eyJ0eXAiOiJKV1Qi...'),
                new OA\Property(property: 'refresh_token', description: 'Refresh token', type: 'string', example: 'def50200...'),
                new OA\Property(property: 'expires_in', description: 'Token expiration in seconds', type: 'integer', example: 31536000),
                new OA\Property(property: 'token_type', description: 'Token type', type: 'string', example: 'Bearer'),
                new OA\Property(property: 'user', description: 'User profile', type: 'object', properties: [
                    new OA\Property(property: 'id', description: 'User UUID', type: 'string', format: 'uuid', example: '019e1d0f-1ec6-7289-8cb3-eb9bdb0f1009'),
                    new OA\Property(property: 'first_name', type: 'string', example: 'Admin'),
                    new OA\Property(property: 'last_name', type: 'string', example: 'User'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@gmail.com'),
                    new OA\Property(property: 'role', type: 'string', enum: ['admin', 'doctor', 'patient', 'receptionist'], example: 'admin'),
                    new OA\Property(property: 'phone', type: 'string', example: '+963912345678'),
                    new OA\Property(property: 'address', type: 'string', example: 'Damascus, Syria'),
                    new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female']),
                    new OA\Property(property: 'birthday_date', type: 'string', format: 'date'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]),
            ])),
            new OA\Response(response: 401, description: 'Invalid credentials', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Invalid credentials'),
            ])),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', description: 'Error message', type: 'string', example: 'Validation failed'),
                new OA\Property(property: 'errors', description: 'Validation errors', type: 'object', example: ['email' => ['The email field is required.']]),
            ])),
        ]
    )]
    public function login(LoginRequest $request): AuthResource
    {
        $dto = LoginData::fromRequest($request);
        $tokenData = $this->loginAction->execute($dto);
        $user = User::where('email', $dto->email)->firstOrFail();

        return new AuthResource((object) compact('user', 'tokenData'));
    }

    #[OA\Post(
        path: '/api/v1/auth/logout',
        summary: 'Logout and revoke current token',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Logged out successfully', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Logged out successfully'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ])),
        ]
    )]
    public function logout(): JsonResponse
    {
        $this->logoutAction->execute();

        return response()->json(['message' => __('auth.logout')]);
    }

    #[OA\Post(
        path: '/api/v1/auth/refresh',
        summary: 'Refresh access token using refresh token',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'refresh_token', description: 'Refresh token from login/register', type: 'string', example: 'def50200...'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'New tokens issued', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'access_token', description: 'New JWT access token', type: 'string', example: 'eyJ0eXAiOiJKV1Qi...'),
                new OA\Property(property: 'refresh_token', description: 'New refresh token', type: 'string', example: 'def50200...'),
                new OA\Property(property: 'expires_in', description: 'Token expiration in seconds', type: 'integer', example: 31536000),
                new OA\Property(property: 'token_type', description: 'Token type', type: 'string', example: 'Bearer'),
            ])),
            new OA\Response(response: 401, description: 'Invalid refresh token', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Invalid refresh token'),
            ])),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
                new OA\Property(property: 'errors', description: 'Validation errors', type: 'object', example: ['refresh_token' => ['The refresh token field is required.']]),
            ])),
        ]
    )]
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

    #[OA\Delete(
        path: '/api/v1/auth/account',
        summary: 'Delete authenticated user account permanently',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Account deleted successfully', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Account deleted successfully'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ])),
        ]
    )]
    public function deleteAccount(DeleteAccountRequest $request): JsonResponse
    {
        $this->deleteAccountAction->execute($request->user());

        return response()->json(['message' => __('auth.account_deleted')]);
    }

    #[OA\Put(
        path: '/api/v1/auth/password',
        summary: 'Change authenticated user password',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'old_password', description: 'Current password', type: 'string', format: 'password', example: 'password'),
                new OA\Property(property: 'new_password', description: 'New password (min 8 characters)', type: 'string', format: 'password', example: 'NewPassword@123'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Password changed successfully', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Password changed successfully'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated or wrong old password', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Wrong old password'),
            ])),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
                new OA\Property(property: 'errors', description: 'Validation errors', type: 'object', example: ['new_password' => ['The new password must be at least 8 characters.']]),
            ])),
        ]
    )]
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->changePasswordAction->execute(
            user: $request->user(),
            newPassword: $request->new_password,
        );

        return response()->json(['message' => __('auth.password_changed')]);
    }

    #[OA\Get(
        path: '/api/v1/auth/me',
        summary: 'Get authenticated user profile',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'User profile retrieved', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'id', description: 'User UUID', type: 'string', format: 'uuid', example: '019e1d0f-1ec6-7289-8cb3-eb9bdb0f1009'),
                new OA\Property(property: 'first_name', type: 'string', example: 'Admin'),
                new OA\Property(property: 'last_name', type: 'string', example: 'User'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@gmail.com'),
                new OA\Property(property: 'role', type: 'string', enum: ['admin', 'doctor', 'patient', 'receptionist'], example: 'admin'),
                new OA\Property(property: 'phone', type: 'string', example: '+963912345678'),
                new OA\Property(property: 'address', type: 'string', example: 'Damascus, Syria'),
                new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female']),
                new OA\Property(property: 'birthday_date', type: 'string', format: 'date'),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ])),
        ]
    )]
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
