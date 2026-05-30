# POST /api/v1/auth/login

تسجيل الدخول باستخدام البريد الإلكتروني وكلمة السر. يتضمن نظام أمني متعدد الطبقات لمنع الاختراق.

---

## 1. Route Info

| Field | Value |
|-------|-------|
| **Method** | `POST` |
| **URL** | `/api/v1/auth/login` |
| **Auth** | ❌ لا يحتاج |
| **Middleware** | `throttle:login` (5 محاولات في الدقيقة) |
| **Content-Type** | `application/json` |

---

## 2. Request Body

| الحقل | النوع | الحالة | التحقق |
|-------|-------|--------|--------|
| `email` | `string` (email) | **مطلوب** | must exist in `users` table |
| `password` | `string` | **مطلوب** | — |
| `device_fingerprint` | `string` | اختياري | max:64 (قادم من FingerprintJS) |

### مثال:

```json
{
    "email": "ahmed@example.com",
    "password": "SecurePass123!",
    "device_fingerprint": "a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1"
}
```

> **ملاحظة:** حقل `device_fingerprint` اختياري لكن يُنصح بشدة بإرساله. يُستخدم لكشف الأجهزة الجديدة ومنع محاولات الاختراق. يُنشأ من **FingerprintJS v4** في الواجهة الأمامية (hash 64 حرفاً).

---

## 3. DTO: `LoginData`

**الملف:** `app/Domains\Auth\DTOs\LoginData.php`

```php
class LoginData
{
    public readonly string $email;
    public readonly string $password;
    public readonly ?string $deviceFingerprint;
    public readonly ?string $userAgent;
}
```

### `fromRequest(LoginRequest $request): self`

| مفتاح الـ Request | خاصية الـ DTO |
|-------------------|---------------|
| `email` | `$email` |
| `password` | `$password` |
| `device_fingerprint` | `$deviceFingerprint` |
| `User-Agent header` | `$userAgent` (من `$request->userAgent()`) |

### `fromCredentials(string $email, string $password): self`

طريقة factory تُستخدم عند التسجيل التلقائي (بعد Register). لا تحتوي `deviceFingerprint` أو `userAgent`.

---

## 4. Security Context: `LoginSecurityContext`

**الملف:** `app/Domains\Auth\DTOs\LoginSecurityContext.php`

ينشأ داخل `LoginAction::execute()` قبل أي عملية:

```php
$context = new LoginSecurityContext(
    email: $data->email,
    password: $data->password,
    ip: request()->ip(),
    deviceFingerprint: $data->deviceFingerprint,
    userAgent: $data->userAgent,
);
```

يُمرر إلى `LoginSecurityManager` لفحص الحظر.

---

## 5. Action: `LoginAction::execute()`

**الملف:** `app/Domains\Auth\Actions\LoginAction.php`

### التدفق الكامل:

```
LoginAction::execute(LoginData $data): TokenData
│
├── 1. إنشاء LoginSecurityContext
│
├── 2. LoginSecurityManager::checkBlocked($context)
│     ├── يمر على جميع الاستراتيجيات (مرتبة حسب الأولوية)
│     ├── SuspiciousActivityStrategy  (priority 5)
│     │     └── يكتشف 3+ أجهزة مختلفة على نفس الإيميل ← يرسل إنذار فقط
│     ├── FingerprintBlockingStrategy (priority 10)
│     │     ├── يتحقق هل الجهاز محظور؟ ← يرد BlockingDecision(blocked: true)
│     │     └── يتحقق: 5+ محاولات فاشلة خلال 30 دقيقة؟ ← يحظر الجهاز 60 دقيقة
│     └── IpBlockingStrategy          (priority 20)
│           ├── يتحقق هل الـ IP محظور؟ ← يرد BlockingDecision(blocked: true)
│           └── يتحقق: 10+ محاولات فاشلة خلال 15 دقيقة؟ ← يحظر الـ IP 30 دقيقة
│
├── 3. إذا كان $blockDecision->blocked === true
│     └── throw AuthenticationException($blockDecision->reason)
│
├── 4. User::where('email', $data->email)->first()
│
├── 5. if (!$user || !Hash::check($data->password, $user->password))
│     ├── LoginSecurityManager::handleFailure($context)
│     │     └── يسجل المحاولة ← يتحقق: 5+ فشل في الساعة أو 3+ أجهزة ← يرسل SuspiciousLoginAttempts
│     └── throw AuthenticationException(__('auth.failed'))
│
├── 6. if (!$user->is_active)
│     ├── LoginSecurityManager::handleFailure($context)
│     └── throw AuthenticationException(__('auth.not_activated'))
│
├── 7. LoginSecurityManager::handleSuccess($context)
│     ├── يسجل المحاولة الناجحة
│     ├── إذا كان deviceFingerprint موجود:
│     │     ├── هل الجهاز معروف؟
│     │     │   ├── لا ← يسجل جهاز جديد + يرسل LoginFromNewDevice event
│     │     │   └── نعم ← يحدث آخر ظهور فقط
│
└── 8. AuthService::issueToken($data) ← OAuth2 Password Grant
      └── يبني ServerRequest مع client_id + client_secret
          └── AuthorizationServer::respondToAccessTokenRequest()
              └── return TokenData (access_token, refresh_token, expires_in)
```

### Dependencies:

| الخدمة | الغرض |
|--------|-------|
| `AuthService` | إصدار توكن OAuth2 عبر Passport |
| `LoginSecurityManager` | إدارة استراتيجيات الحظر والتسجيل |

---

## 6. الـ Controller

**الملف:** `app/Http/Controllers/Api/V1/Auth/AuthController.php`

```php
public function login(LoginRequest $request): JsonResponse
{
    try {
        $dto = LoginData::fromRequest($request);
        $tokenData = $this->loginAction->execute($dto);
        $user = User::where('email', $dto->email)->with('roles')->firstOrFail();

        return ApiResponse::success(
            new AuthResource((object) compact('user', 'tokenData')),
            __('auth.login_success')
        );
    } catch (AuthenticationException $e) {
        return ApiResponse::unauthorized($e->getMessage());
    }
}
```

---

## 7. Response

### ✅ Success — `200 OK`

```json
{
    "status": 200,
    "message": "Logged in successfully.",
    "data": {
        "access_token": "eyJ0eXAiOiJKV1Qi...",
        "refresh_token": "def50200...",
        "expires_in": 86400,
        "token_type": "Bearer",
        "user": {
            "id": "0196f0a0-1234-7abc-def0-123456789abc",
            "first_name": "أحمد",
            "last_name": "محمد",
            "username": "ahmed_m",
            "email": "ahmed@example.com",
            "roles": ["Patient"],
            "is_active": true,
            "patient": { "id": "..." },
            "image": null
        }
    }
}
```

### ❌ Unauthorized — `401 Unauthorized`

```json
{
    "status": 401,
    "message": "These credentials do not match our records."
}
```

### ❌ Device Blocked — `401 Unauthorized`

```json
{
    "status": 401,
    "message": "Too many attempts from this device. Please try again in an hour."
}
```

### ❌ IP Blocked — `401 Unauthorized`

```json
{
    "status": 401,
    "message": "Too many attempts from this IP address. Please try again later."
}
```

---

## 8. الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `200` | `Logged in successfully.` | نجاح |
| `401` | `These credentials do not match our records.` | إيميل/باسورد خطأ |
| `401` | `Account is not activated.` | الحساب غير مفعّل |
| `401` | `This device is blocked...` | الجهاز محظور (بصمة الجهاز) |
| `401` | `Too many attempts from this IP...` | الـ IP محظور مؤقتاً |
| `422` | `Validation failed` | إيميل غير موجود أو باسورد ناقص |
| `429` | `Too Many Attempts.` | تجاوز rate limit (5 محاولات/دقيقة) |

---

## 9. Sequence Diagram

```
Client         Controller         LoginAction          LoginSecurityManager       AuthService
  │                │                  │                       │                      │
  │─ POST /login ─→│                  │                       │                      │
  │                │─ LoginData       │                       │                      │
  │                │─ ::fromRequest() │                       │                      │
  │                │─────────────────→│                       │                      │
  │                │                  │─ LoginSecurityContext  │                      │
  │                │                  │────────────────────────→│                      │
  │                │                  │                        │                      │
  │                │                  │─ checkBlocked(ctx) ────→│                      │
  │                │                  │                        │── SuspiciousActivity  │
  │                │                  │                        │── FingerprintStrategy │
  │                │                  │                        │── IpStrategy          │
  │                │                  │←── ?BlockingDecision ──│                      │
  │                │                  │                        │                      │
  │                │                  │─ if blocked → throw     │                      │
  │                │                  │                        │                      │
  │                │                  │─ User::where(email)     │                      │
  │                │                  │─ Hash::check(password)  │                      │
  │                │                  │                        │                      │
  │                │                  │─ handleSuccess/Failure  │                      │
  │                │                  │────────────────────────→│                      │
  │                │                  │                        │── recordAttempt()     │
  │                │                  │                        │── registerDevice()    │
  │                │                  │                        │── dispatch(Event)     │
  │                │                  │                        │                      │
  │                │                  │─ issueToken($data) ────│─────────────────────→│
  │                │                  │                        │                      │─ OAuth2
  │                │                  │←──── TokenData ────────│←────────────────────│
  │                │                  │                        │                      │
  │                │←── TokenData ────│                        │                      │
  │                │                  │                        │                      │
  │                │─ AuthResource    │                        │                      │
  │← 200 + JSON ───│                  │                        │                      │
```

---

## 10. الـ Request: `LoginRequest`

**الملف:** `app/Domains/Auth/Requests/LoginRequest.php`

```php
public function rules(): array
{
    return [
        'email' => ['required', 'email', 'exists:users,email'],
        'password' => ['required', 'string'],
        'device_fingerprint' => ['nullable', 'string', 'max:64'],
    ];
}
```

يستخدم `exists:users,email` للتأكد من وجود الإيميل. إذا لم يكن موجوداً → `422 Validation failed`.
