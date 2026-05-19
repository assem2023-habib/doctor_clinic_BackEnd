# Auth Domain — التوثيق الكامل

نظام المصادقة الكامل للمنصة. يعمل بـ **Laravel Passport (OAuth2 password grant)** مع طبقة أمنية متعددة الاستراتيجيات.

---

## قائمة الـ Endpoints

| # | Method | Endpoint | Auth | Rate Limit | الصفحة |
|---|--------|----------|------|------------|--------|
| 1 | `POST` | `/api/v1/auth/register/patient` | ❌ | `register` (3/min) | [register-patient.md](register-patient.md) |
| 2 | `POST` | `/api/v1/auth/register/doctor` | ❌ | `register` (3/min) | [register-doctor.md](register-doctor.md) |
| 3 | `POST` | `/api/v1/auth/register/receptionist` | ❌ | `register` (3/min) | [register-receptionist.md](register-receptionist.md) |
| 4 | `POST` | `/api/v1/auth/login` | ❌ | `login` (5/min) | [login.md](login.md) |
| 5 | `POST` | `/api/v1/auth/refresh` | ❌ | — | [refresh.md](refresh.md) |
| 6 | `POST` | `/api/v1/auth/logout` | ✅ Bearer | — | [logout.md](logout.md) |
| 7 | `PUT` | `/api/v1/auth/password` | ✅ Bearer | — | [change-password.md](change-password.md) |
| 8 | `DELETE` | `/api/v1/auth/account` | ✅ Bearer | — | [delete-account.md](delete-account.md) |
| 9 | `GET` | `/api/v1/auth/me` | ✅ Bearer | — | [me.md](me.md) |
| 10 | `PUT` | `/api/v1/auth/me` | ✅ Bearer | — | [update-profile.md](update-profile.md) |

---

## الأنماط المعمارية المستخدمة في Auth

| النمط | أين يستخدم |
|-------|-----------|
| **Action Pattern** | كل Use case له Action واحد (`execute()`) |
| **Strategy Pattern** | `BlockingStrategyInterface` مع 3 استراتيجيات |
| **Repository Pattern** | `LoginAttemptRepositoryInterface` / `EloquentLoginAttemptRepository` |
| **DTO Pattern** | Immutable data objects مع `fromRequest()` factories |
| **Service Pattern** | `AuthService` (OAuth), `LoginSecurityManager`, `DeviceFingerprintService` |
| **Event/Listener** | `LoginFromNewDevice`, `SuspiciousLoginAttempts` |

---

## تدفق المصادقة العام

```
Client → POST /login
  ├── 1. LoginRequest (validation)
  ├── 2. LoginData::fromRequest() (DTO)
  ├── 3. LoginSecurityManager::checkBlocked() ← Strategies
  │     ├── SuspiciousActivityStrategy  (priority 5, warning only)
  │     ├── FingerprintBlockingStrategy (priority 10)
  │     └── IpBlockingStrategy          (priority 20)
  ├── 4. LoginAction → User lookup + Hash::check
  ├── 5. LoginSecurityManager::handleSuccess/Failure
  ├── 6. AuthService::issueToken() → OAuth2 Password Grant
  └── 7. AuthResource (tokens + user data)
```

---

## هيكل المجلدات

```
app/Domains/Auth/
├── Actions/
│   ├── ChangePasswordAction.php
│   ├── DeleteAccountAction.php
│   ├── LoginAction.php
│   ├── LogoutAction.php
│   ├── RegisterDoctorAction.php
│   ├── RegisterPatientAction.php
│   ├── RegisterReceptionistAction.php
│   └── UpdateProfileAction.php
├── Contracts/
│   ├── BlockingStrategyInterface.php
│   ├── DeviceFingerprintServiceInterface.php
│   └── LoginAttemptRepositoryInterface.php
├── DTOs/
│   ├── BlockingDecision.php
│   ├── LoginAttemptData.php
│   ├── LoginData.php
│   ├── LoginSecurityContext.php
│   ├── RegisterAdminData.php
│   ├── RegisterDoctorData.php
│   ├── RegisterPatientData.php
│   ├── RegisterReceptionistData.php
│   ├── TokenData.php
│   └── UpdateProfileData.php
├── Events/
│   ├── LoginFromNewDevice.php
│   └── SuspiciousLoginAttempts.php
├── Listeners/
│   ├── NotifySuspiciousActivity.php
│   └── SendNewDeviceNotification.php
├── Models/
│   ├── DeviceFingerprint.php
│   ├── KnownUserDevice.php
│   └── LoginAttempt.php
├── Providers/
│   └── LoginSecurityServiceProvider.php
├── Repositories/
│   └── EloquentLoginAttemptRepository.php
├── Requests/
│   ├── ChangePasswordRequest.php
│   ├── DeleteAccountRequest.php
│   ├── LoginRequest.php
│   ├── RegisterDoctorRequest.php
│   ├── RegisterPatientRequest.php
│   ├── RegisterReceptionistRequest.php
│   └── UpdateProfileRequest.php
├── Resources/
│   └── AuthResource.php
├── Services/
│   ├── AuthService.php
│   ├── DeviceFingerprintService.php
│   └── LoginSecurityManager.php
└── Strategies/
    ├── FingerprintBlockingStrategy.php
    ├── IpBlockingStrategy.php
    └── SuspiciousActivityStrategy.php
```
